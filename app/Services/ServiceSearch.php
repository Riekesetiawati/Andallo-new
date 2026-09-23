<?php

namespace App\Services;

use App\Models\Service;
use Illuminate\Support\Collection;

class ServiceSearch
{
    public function results(array $filters, ?float $lat, ?float $lng): Collection
    {
        $query = Service::query()
            ->listed()
            ->with(['provider', 'category']);

        if (! empty($filters['q'])) {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($inner) use ($term) {
                $inner->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereHas('provider', fn ($provider) => $provider->where('business_name', 'like', $term))
                    ->orWhereHas('category', fn ($category) => $category->where('name', 'like', $term));
            });
        }

        if (! empty($filters['category'])) {
            $query->whereHas('category', fn ($category) => $category->where('slug', $filters['category']));
        }

        if (! empty($filters['city'])) {
            $query->whereHas('provider', fn ($provider) => $provider->where('city', $filters['city']));
        }

        if (($filters['min_price'] ?? null) !== null && $filters['min_price'] !== '') {
            $query->where('price', '>=', (float) $filters['min_price']);
        }

        if (($filters['max_price'] ?? null) !== null && $filters['max_price'] !== '') {
            $query->where('price', '<=', (float) $filters['max_price']);
        }

        $services = $query->get()->map(function (Service $service) use ($lat, $lng) {
            $provider = $service->provider;
            $service->distance_km = null;
            if ($lat !== null && $lng !== null && $provider?->latitude !== null && $provider?->longitude !== null) {
                $service->distance_km = haversine_km($lat, $lng, (float) $provider->latitude, (float) $provider->longitude);
            }
            $service->sort_rating = $service->displayRating() ?? -1;

            return $service;
        });

        if (($filters['min_rating'] ?? null) !== null && $filters['min_rating'] !== '') {
            $min = (float) $filters['min_rating'];
            $services = $services->filter(fn (Service $service) => ($service->displayRating() ?? 0) >= $min);
        }

        $sort = $filters['sort'] ?? 'rating';

        $services = match ($sort) {
            'price_asc' => $services->sortBy(fn (Service $service) => (float) $service->price),
            'price_desc' => $services->sortByDesc(fn (Service $service) => (float) $service->price),
            'distance' => $services->sortBy(fn (Service $service) => $service->distance_km ?? INF),
            default => $services->sortByDesc(fn (Service $service) => $service->sort_rating),
        };

        return $services->values();
    }
}
