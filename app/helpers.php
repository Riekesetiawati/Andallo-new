<?php

use App\Models\Setting;

function rupiah(int|float|string|null $amount): string
{
    return 'Rp'.number_format((float) ($amount ?? 0), 0, ',', '.');
}

function haversine_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $earth = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

    return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

function format_distance(?float $km): ?string
{
    if ($km === null) {
        return null;
    }

    return number_format($km, 1, ',', '.').' km';
}

function setting_value(string $key, mixed $default = null): mixed
{
    return Setting::getValue($key, $default);
}

function whatsapp_link(string $phone, string $text): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';

    return 'https://wa.me/'.$digits.'?text='.rawurlencode($text);
}
