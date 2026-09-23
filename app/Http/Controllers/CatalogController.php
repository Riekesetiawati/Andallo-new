<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Services\ScheduleService;
use App\Services\ServiceSearch;
use App\Support\UserLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function home(ServiceSearch $search)
    {
        $location = UserLocation::current();
        $categories = Category::query()->where('is_active', true)->orderBy('sort_order')->get();
        $featured = Service::query()
            ->listed()
            ->where('is_featured', true)
            ->with(['provider', 'category'])
            ->get();

        if ($featured->count() < 4) {
            $featured = $search->results(['sort' => 'distance'], $location['lat'], $location['lng'])->take(4);
        } else {
            $featured = $search->results(['sort' => 'distance'], $location['lat'], $location['lng'])
                ->filter(fn (Service $service) => $service->is_featured)
                ->take(4)
                ->values();
        }

        return view('home', compact('categories', 'featured', 'location'));
    }

    public function index(Request $request, ServiceSearch $search)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'min_rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'sort' => ['nullable', 'in:price_asc,price_desc,rating,distance'],
        ]);

        $location = UserLocation::current();
        $results = $search->results([
            'q' => $filters['q'] ?? null,
            'category' => $filters['category'] ?? null,
            'city' => $filters['city'] ?? null,
            'min_price' => $filters['min_price'] ?? null,
            'max_price' => $filters['max_price'] ?? null,
            'min_rating' => $filters['min_rating'] ?? null,
            'sort' => $filters['sort'] ?? 'rating',
        ], $location['lat'], $location['lng']);

        $categories = Category::query()->where('is_active', true)->orderBy('sort_order')->get();
        $cities = collect(config('cities'))->pluck('name');
        $compare = session('compare', []);

        if ($request->header('X-Andallo-Partial') === '1') {
            return view('services._results', compact('results', 'compare', 'location'));
        }

        return view('services.index', compact('results', 'categories', 'cities', 'filters', 'compare', 'location'));
    }

    public function show(Service $service, ScheduleService $schedule)
    {
        $service->load(['provider.user', 'provider.portfolios', 'category', 'reviews.customer', 'portfolios']);
        abort_unless($service->is_active && $service->provider?->isApproved(), 404);

        $location = UserLocation::current();
        $distance = null;
        if ($location['lat'] !== null && $service->provider->latitude !== null) {
            $distance = haversine_km($location['lat'], $location['lng'], (float) $service->provider->latitude, (float) $service->provider->longitude);
        }

        $slots = $schedule->slotsFor($service, now()->addDay()->startOfDay());
        $compare = session('compare', []);

        return view('services.show', compact('service', 'distance', 'location', 'slots', 'compare'));
    }

    public function slots(Request $request, Service $service, ScheduleService $schedule)
    {
        abort_unless($service->is_active && $service->provider?->isApproved(), 404);
        $data = $request->validate(['date' => ['required', 'date', 'after_or_equal:today']]);
        $slots = $schedule->slotsFor($service, Carbon::parse($data['date']));

        return response()->json(['slots' => $slots]);
    }

    public function providers(Request $request)
    {
        $providers = Provider::query()
            ->where('verification_status', 'approved')
            ->where('is_active', true)
            ->withCount(['services' => fn ($query) => $query->where('is_active', true)])
            ->when($request->string('q')->toString(), function ($query, $q) {
                $query->where('business_name', 'like', '%'.$q.'%');
            })
            ->orderByDesc('rating_count')
            ->orderBy('business_name')
            ->paginate(12)
            ->withQueryString();

        return view('providers.index', compact('providers'));
    }

    public function provider(Provider $provider)
    {
        abort_unless($provider->isApproved(), 404);
        $provider->load(['services' => fn ($query) => $query->listed(), 'portfolios', 'user']);

        return view('providers.show', compact('provider'));
    }
}
