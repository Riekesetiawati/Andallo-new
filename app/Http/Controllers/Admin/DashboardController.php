<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProviderStatus;
use App\Enums\RefundStatus;
use App\Exceptions\BookingRuleException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CancellationRequest;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Provider;
use App\Models\Refund;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\BookingService;
use App\Services\CancellationService;
use App\Services\MarketplaceNotifier;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'providers' => Provider::query()->where('verification_status', ProviderStatus::Pending)->count(),
            'reviews' => Booking::query()->where('status', BookingStatus::PendingReview)->count(),
            'payments' => Payment::query()->where('status', PaymentStatus::UnderReview)->count(),
            'cancellations' => CancellationRequest::query()->whereIn('status', ['pending', 'pending_funds_check', 'awaiting_customer'])->count(),
            'refunds' => Refund::query()->whereIn('status', [RefundStatus::Processing, RefundStatus::Failed, RefundStatus::PendingReview])->count(),
            'complaints' => Complaint::query()->whereIn('status', ['open', 'in_review'])->count(),
            'failed' => NotificationLog::query()->where('status', 'failed')->count(),
            'paidTotal' => Payment::query()->where('status', PaymentStatus::Paid)->sum('amount'),
        ]);
    }

    public function users()
    {
        $users = User::query()->latest()->paginate(20);

        return view('admin.users', compact('users'));
    }

    public function toggleUser(User $user)
    {
        abort_if($user->isAdmin() && auth()->id() === $user->id, 403);
        $user->is_active = ! $user->is_active;
        $user->save();

        return back()->with('success', 'Status akun diperbarui.');
    }

    public function providers()
    {
        $providers = Provider::query()->with('user')->latest()->paginate(20);

        return view('admin.providers', compact('providers'));
    }

    public function verifyProvider(Request $request, Provider $provider)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,revision_requested,rejected'],
            'revision_note' => ['nullable', 'string', 'max:1000'],
        ]);
        if ($data['decision'] === 'approved' && (! $provider->whatsapp || $provider->services()->where('is_active', true)->doesntExist())) {
            return back()->with('error', 'Mitra perlu memiliki WhatsApp dan minimal satu layanan aktif.');
        }
        $provider->verification_status = ProviderStatus::from($data['decision']);
        $provider->revision_note = $data['revision_note'] ?? null;
        $provider->verified_at = $data['decision'] === 'approved' ? now() : null;
        $provider->verified_by = $request->user()->id;
        $provider->save();

        return back()->with('success', 'Status verifikasi mitra diperbarui.');
    }

    public function categories()
    {
        $categories = Category::query()->orderBy('sort_order')->get();

        return view('admin.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'icon' => ['required', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        Category::query()->create($data + [
            'slug' => Str::slug($data['name']),
            'sort_order' => (int) Category::query()->max('sort_order') + 1,
        ]);

        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function updateCategory(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'icon' => ['required', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $category->fill([
            'name' => $data['name'],
            'icon' => $data['icon'],
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ])->save();

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function services()
    {
        $services = Service::query()->with(['provider', 'category'])->latest()->paginate(20);

        return view('admin.services', compact('services'));
    }

    public function toggleService(Service $service)
    {
        $service->is_active = ! $service->is_active;
        $service->save();

        return back()->with('success', 'Status jasa diperbarui.');
    }

    public function bookings(Request $request)
    {
        $status = $request->string('status')->toString();
        $bookings = Booking::query()->with(['customer', 'provider', 'payment'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.bookings', compact('bookings', 'status'));
    }

    public function forward(Request $request, Booking $booking, BookingService $bookings)
    {
        try {
            $bookings->forwardToProvider($booking, $request->user());
        } catch (BookingRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Pesanan diteruskan ke penyedia. Batas respons mulai dihitung sekarang.');
    }

    public function rejectBooking(Request $request, Booking $booking, BookingService $bookings)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        try {
            $bookings->rejectReview($booking, $request->user(), $data['reason']);
        } catch (BookingRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Pesanan ditolak.');
    }

    public function payments()
    {
        $payments = Payment::query()->with('booking.customer', 'booking.provider')->latest()->paginate(20);

        return view('admin.payments', compact('payments'));
    }

    public function approvePayment(Request $request, Payment $payment, PaymentService $payments)
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:500']]);
        try {
            $payments->approve($payment->booking, $request->user(), $data['note'] ?? null);
        } catch (BookingRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Pembayaran diverifikasi. Layanan boleh dilanjutkan.');
    }

    public function rejectPayment(Request $request, Payment $payment, PaymentService $payments)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        try {
            $payments->rejectProof($payment->booking, $request->user(), $data['reason']);
        } catch (BookingRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Bukti ditolak. Pelanggan dapat mengunggah ulang.');
    }

    public function cancellations()
    {
        $requests = CancellationRequest::query()->with('booking.customer', 'booking.provider')->latest()->paginate(20);

        return view('admin.cancellations', compact('requests'));
    }

    public function resolveCancellation(Request $request, CancellationRequest $cancellation, CancellationService $cancellations)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['funds_missing', 'funds_received', 'approve', 'propose'])],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            match ($data['action']) {
                'funds_missing' => $cancellations->resolveFunds($cancellation, $request->user(), false, $data['note'] ?? null),
                'funds_received' => $cancellations->resolveFunds($cancellation, $request->user(), true, $data['note'] ?? null),
                'approve' => $cancellations->approveBeforeStart($cancellation, $request->user(), $data['note'] ?? null),
                'propose' => $cancellations->proposeSettlement($cancellation, $request->user(), (float) ($data['amount'] ?? 0), $data['note'] ?? 'Usulan pengembalian.'),
            };
        } catch (BookingRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Permintaan pembatalan diproses.');
    }

    public function refunds()
    {
        $refunds = Refund::query()->with('booking', 'payment')->latest()->paginate(20);

        return view('admin.refunds', compact('refunds'));
    }

    public function confirmRefund(Request $request, Refund $refund, CancellationService $cancellations)
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
        ]);
        $path = $request->hasFile('proof') ? $request->file('proof')->store('refund-proofs', 'local') : null;
        try {
            $cancellations->confirmRefund($refund, $request->user(), $data['note'], $path);
        } catch (BookingRuleException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Refund dikonfirmasi. Status dikembalikan hanya setelah langkah ini.');
    }

    public function failRefund(Request $request, Refund $refund, CancellationService $cancellations)
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        $cancellations->failRefund($refund, $request->user(), $data['note']);

        return back()->with('success', 'Refund ditandai gagal dan dapat diproses ulang.');
    }

    public function complaints()
    {
        $complaints = Complaint::query()->with('booking', 'user')->latest()->paginate(20);

        return view('admin.complaints', compact('complaints'));
    }

    public function updateComplaint(Request $request, Complaint $complaint)
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,in_review,resolved,closed'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $complaint->fill($data + ['handled_by' => $request->user()->id])->save();

        return back()->with('success', 'Komplain diperbarui.');
    }

    public function logs()
    {
        $logs = NotificationLog::query()->with('booking')->latest()->paginate(30);

        return view('admin.logs', compact('logs'));
    }

    public function retryLog(NotificationLog $log, MarketplaceNotifier $notifier)
    {
        $fresh = $notifier->retry($log);

        return back()->with($fresh->status === 'failed' ? 'error' : 'success', 'Percobaan ulang dicatat: '.$fresh->statusLabel());
    }

    public function settings()
    {
        $keys = array_keys(config('andallo.defaults'));
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = Setting::getValue($key);
        }

        return view('admin.settings', compact('values'));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'require_admin_review' => ['nullable', 'boolean'],
            'provider_response_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'payment_window_minutes' => ['required', 'integer', 'min:5', 'max:10080'],
            'payment_review_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'refund_before_start_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'bank_name' => ['required', 'string', 'max:120'],
            'bank_account_number' => ['required', 'string', 'max:40'],
            'bank_account_holder' => ['required', 'string', 'max:160'],
            'support_email' => ['required', 'email'],
            'support_phone' => ['required', 'string', 'max:20'],
            'support_hours' => ['required', 'string', 'max:120'],
            'cancellation_policy' => ['required', 'string', 'max:4000'],
        ]);
        $data['require_admin_review'] = $request->boolean('require_admin_review') ? '1' : '0';
        foreach ($data as $key => $value) {
            Setting::setValue($key, $value);
        }

        return back()->with('success', 'Pengaturan disimpan.');
    }
}
