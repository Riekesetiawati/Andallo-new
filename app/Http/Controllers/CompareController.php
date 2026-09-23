<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Support\UserLocation;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    public function index()
    {
        $ids = array_slice(array_map('intval', session('compare', [])), 0, 3);
        $services = Service::query()->listed()->with(['provider', 'category'])->whereIn('id', $ids)->get()
            ->sortBy(fn (Service $service) => array_search($service->id, $ids, true))
            ->values();

        $location = UserLocation::current();
        $services->each(function (Service $service) use ($location) {
            $service->distance_km = null;
            if ($location['lat'] !== null && $service->provider?->latitude !== null) {
                $service->distance_km = haversine_km(
                    $location['lat'],
                    $location['lng'],
                    (float) $service->provider->latitude,
                    (float) $service->provider->longitude,
                );
            }
        });

        $units = $services->pluck('price_unit')->unique();
        $coverageDiffers = $services->map(fn (Service $service) => trim((string) $service->includes))->unique()->count() > 1;

        return view('compare.index', [
            'services' => $services,
            'unitNote' => $units->count() > 1,
            'coverageNote' => $coverageDiffers && $services->count() > 1,
            'location' => $location,
        ]);
    }

    public function toggle(Request $request)
    {
        $data = $request->validate(['service_id' => ['required', 'integer', 'exists:services,id']]);
        $service = Service::query()->listed()->find($data['service_id']);
        if (! $service) {
            return back()->with('error', 'Jasa tidak dapat dibandingkan.');
        }

        $ids = array_map('intval', session('compare', []));
        if (in_array($service->id, $ids, true)) {
            $ids = array_values(array_diff($ids, [$service->id]));
            $message = 'Jasa dihapus dari perbandingan.';
        } else {
            if (count($ids) >= 3) {
                return back()->with('error', 'Perbandingan maksimal tiga jasa. Hapus salah satu terlebih dahulu.');
            }
            $ids[] = $service->id;
            $message = 'Jasa ditambahkan ke perbandingan.';
        }

        session(['compare' => $ids]);

        return back()->with('success', $message);
    }
}
