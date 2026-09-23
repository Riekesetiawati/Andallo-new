<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'provider_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'price_unit',
        'duration_minutes',
        'includes',
        'excludes',
        'requires_visit',
        'image_path',
        'is_active',
        'is_featured',
        'is_demo',
        'rating_avg',
        'rating_count',
        'demo_rating',
        'demo_review_count',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'requires_visit' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_demo' => 'boolean',
            'rating_avg' => 'decimal:2',
            'demo_rating' => 'decimal:2',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeListed(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereHas('provider', function (Builder $provider): void {
                $provider->where('verification_status', 'approved')->where('is_active', true);
            });
    }

    public function displayRating(): ?float
    {
        if ($this->rating_count > 0) {
            return (float) $this->rating_avg;
        }

        if ($this->is_demo && $this->demo_rating !== null) {
            return (float) $this->demo_rating;
        }

        return $this->provider?->displayRating();
    }

    public function displayReviewCount(): int
    {
        if ($this->rating_count > 0) {
            return (int) $this->rating_count;
        }

        if ($this->is_demo && $this->demo_review_count > 0) {
            return (int) $this->demo_review_count;
        }

        return (int) ($this->provider?->displayReviewCount() ?? 0);
    }

    public function usesDemoRating(): bool
    {
        return $this->rating_count === 0 && (
            ($this->is_demo && $this->demo_rating !== null)
            || ($this->provider?->usesDemoRating() ?? false)
        );
    }

    public function refreshRating(): void
    {
        $query = $this->reviews()->where('is_demo', false);
        $count = (clone $query)->count();
        $avg = $count > 0 ? round((float) $query->avg('rating'), 2) : 0;
        $this->forceFill([
            'rating_avg' => $avg,
            'rating_count' => $count,
        ])->save();
        $this->provider?->refreshRating();
    }
}
