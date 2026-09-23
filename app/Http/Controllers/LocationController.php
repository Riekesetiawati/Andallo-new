<?php

namespace App\Http\Controllers;

use App\Support\UserLocation;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'source' => ['required', 'in:device,manual,clear'],
            'label' => ['nullable', 'string', 'max:80'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'city' => ['nullable', 'string', 'max:80'],
        ]);

        if ($data['source'] === 'clear') {
            UserLocation::put('Belum diatur', null, null, 'clear');

            return back()->with('success', 'Lokasi dihapus. Jarak tidak ditampilkan sampai koordinat tersedia.');
        }

        $lat = $data['lat'] ?? null;
        $lng = $data['lng'] ?? null;

        if ($data['source'] === 'manual') {
            $city = collect(config('cities'))->firstWhere('name', $data['city'] ?? '');
            if (! $city && ($lat === null || $lng === null)) {
                return back()->with('error', 'Pilih kota atau isi koordinat.');
            }
            if ($city && ($lat === null || $lng === null)) {
                UserLocation::put($city['name'], (float) $city['lat'], (float) $city['lng'], 'manual');
            } else {
                UserLocation::put($data['label'] ?? ($data['city'] ?? 'Lokasi pilihan'), (float) $lat, (float) $lng, 'manual');
            }

            return back()->with('success', 'Lokasi diperbarui. Hasil pencarian memakai koordinat baru.');
        }

        if ($lat === null || $lng === null) {
            return back()->with('error', 'Koordinat perangkat tidak diterima. Pilih kota secara manual.');
        }

        UserLocation::put($data['label'] ?? 'Lokasi perangkat', (float) $lat, (float) $lng, 'device');

        return back()->with('success', 'Lokasi perangkat digunakan. Ini bukan pelacakan perjalanan.');
    }
}
