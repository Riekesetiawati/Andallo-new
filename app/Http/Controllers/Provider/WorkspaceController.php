<?php

namespace App\Http\Controllers\Provider;

use App\Enums\ProviderStatus;
use App\Http\Controllers\Controller;
use App\Models\AvailabilitySlot;
use App\Models\Category;
use App\Models\Portfolio;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WorkspaceController extends Controller
{
    public function dashboard(Request $request)
    {
        $provider = $request->user()->provider;
        abort_unless($provider, 403);
        $provider->loadCount('services');
        $incoming = $provider->bookings()->with('service', 'customer')->latest()->limit(5)->get();

        return view('provider.dashboard', compact('provider', 'incoming'));
    }

    public function editProfile(Request $request)
    {
        $provider = $request->user()->provider()->with('profile')->firstOrFail();

        return view('provider.profile', compact('provider'));
    }

    public function updateProfile(Request $request)
    {
        $provider = $request->user()->provider()->firstOrFail();
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:160'],
            'whatsapp' => ['required', 'regex:/^(\+62|62|0)[0-9]{8,14}$/'],
            'description' => ['required', 'string', 'max:2000'],
            'portfolio_summary' => ['nullable', 'string', 'max:2000'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'service_area' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $digits = preg_replace('/\D+/', '', $data['whatsapp']) ?? '';
        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        }
        $data['whatsapp'] = $digits;
        unset($data['photo']);

        $provider->fill($data);
        if ($provider->verification_status === ProviderStatus::Revision) {
            $provider->verification_status = ProviderStatus::Pending;
        }
        $provider->save();

        $profile = $provider->profile ?: $provider->profile()->make();
        $profile->provider_id = $provider->id;
        $profile->user_id = null;
        $profile->description = $data['description'];
        if ($request->hasFile('photo')) {
            $profile->photo_path = $request->file('photo')->store('profiles', 'public');
        }
        $profile->save();

        return back()->with('success', 'Profil usaha disimpan.');
    }

    public function submitReview(Request $request)
    {
        $provider = $request->user()->provider()->withCount('services')->firstOrFail();
        if ($provider->services_count < 1 || ! $provider->description || ! $provider->whatsapp || ! $provider->city) {
            return back()->with('error', 'Lengkapi nama usaha, WhatsApp, deskripsi, area, dan minimal satu layanan sebelum diajukan.');
        }
        $provider->verification_status = ProviderStatus::Pending;
        $provider->revision_note = null;
        $provider->save();

        return back()->with('success', 'Profil diajukan untuk verifikasi admin.');
    }

    public function services(Request $request)
    {
        $provider = $request->user()->provider;
        $services = $provider->services()->with('category')->latest()->get();
        $categories = Category::query()->where('is_active', true)->orderBy('sort_order')->get();

        return view('provider.services', compact('provider', 'services', 'categories'));
    }

    public function storeService(Request $request)
    {
        $provider = $request->user()->provider;
        $data = $this->validateService($request);
        $data['provider_id'] = $provider->id;
        $data['slug'] = $this->uniqueSlug($provider->id, $data['name']);
        if ($request->hasFile('image')) {
            $data['image_path'] = 'storage/'.$request->file('image')->store('services', 'public');
        }
        Service::query()->create($data);

        return back()->with('success', 'Layanan ditambahkan.');
    }

    public function updateService(Request $request, Service $service)
    {
        $this->owns($request, $service);
        $data = $this->validateService($request);
        if ($request->hasFile('image')) {
            $data['image_path'] = 'storage/'.$request->file('image')->store('services', 'public');
        }
        $service->update($data);

        return back()->with('success', 'Layanan diperbarui. Harga baru tidak mengubah pesanan yang sudah dibuat.');
    }

    public function destroyService(Request $request, Service $service)
    {
        $this->owns($request, $service);
        if ($service->bookings()->exists()) {
            $service->is_active = false;
            $service->save();

            return back()->with('success', 'Layanan dinonaktifkan karena sudah memiliki pesanan.');
        }
        $service->delete();

        return back()->with('success', 'Layanan dihapus.');
    }

    public function storePortfolio(Request $request)
    {
        $provider = $request->user()->provider;
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'service_id' => ['nullable', 'integer'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);
        if (! empty($data['service_id'])) {
            abort_unless($provider->services()->whereKey($data['service_id'])->exists(), 403);
        }
        Portfolio::query()->create([
            'provider_id' => $provider->id,
            'service_id' => $data['service_id'] ?: null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'image_path' => 'storage/'.$request->file('image')->store('portfolios', 'public'),
        ]);

        return back()->with('success', 'Portofolio ditambahkan.');
    }

    public function schedule(Request $request)
    {
        $provider = $request->user()->provider;
        $slots = $provider->availabilitySlots()->whereNull('specific_date')->get()->keyBy('weekday');

        return view('provider.schedule', compact('provider', 'slots'));
    }

    public function updateSchedule(Request $request)
    {
        $provider = $request->user()->provider;
        $data = $request->validate([
            'days' => ['required', 'array'],
            'days.*.open' => ['nullable', 'boolean'],
            'days.*.start' => ['nullable', 'date_format:H:i'],
            'days.*.end' => ['nullable', 'date_format:H:i'],
        ]);

        $provider->availabilitySlots()->whereNull('specific_date')->delete();
        foreach ($data['days'] as $weekday => $day) {
            if (empty($day['open']) || empty($day['start']) || empty($day['end'])) {
                continue;
            }
            if ($day['start'] >= $day['end']) {
                return back()->with('error', 'Jam selesai harus lebih lambat dari jam mulai.');
            }
            AvailabilitySlot::query()->create([
                'provider_id' => $provider->id,
                'weekday' => (int) $weekday,
                'start_time' => $day['start'],
                'end_time' => $day['end'],
            ]);
        }

        return back()->with('success', 'Jadwal mingguan disimpan.');
    }

    private function validateService(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:2000'],
            'price' => ['required', 'numeric', 'min:0'],
            'price_unit' => ['required', Rule::in(['per jam', 'per kunjungan', 'per sesi', 'per item', 'per kg'])],
            'duration_minutes' => ['required', 'integer', 'min:30', 'max:480'],
            'includes' => ['required', 'string', 'max:2000'],
            'excludes' => ['nullable', 'string', 'max:2000'],
            'requires_visit' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ]);
        unset($data['image']);
        $data['requires_visit'] = $request->boolean('requires_visit');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function uniqueSlug(int $providerId, string $name): string
    {
        $base = Str::slug($name) ?: 'jasa';
        $slug = $base;
        $i = 2;
        while (Service::withTrashed()->where('provider_id', $providerId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function owns(Request $request, Service $service): void
    {
        abort_unless($service->provider_id === $request->user()->provider?->id, 403);
    }
}
