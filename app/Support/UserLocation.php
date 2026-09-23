<?php

namespace App\Support;

class UserLocation
{
    public static function current(): array
    {
        $location = session('location');

        if (! is_array($location)) {
            return [
                'label' => 'Jakarta Selatan',
                'lat' => -6.2615,
                'lng' => 106.8106,
                'source' => 'default',
            ];
        }

        return [
            'label' => $location['label'] ?? 'Belum diatur',
            'lat' => isset($location['lat']) ? (float) $location['lat'] : null,
            'lng' => isset($location['lng']) ? (float) $location['lng'] : null,
            'source' => $location['source'] ?? 'manual',
        ];
    }

    public static function put(?string $label, ?float $lat, ?float $lng, string $source): void
    {
        session([
            'location' => [
                'label' => $label ?: 'Belum diatur',
                'lat' => $lat,
                'lng' => $lng,
                'source' => $source,
            ],
        ]);
    }

    public static function hasCoordinates(): bool
    {
        $location = self::current();

        return $location['lat'] !== null && $location['lng'] !== null;
    }
}
