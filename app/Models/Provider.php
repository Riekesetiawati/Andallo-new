<?php

namespace App\Models;

use App\Enums\ProviderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'business_name',
        'whatsapp',
        'description',
        'portfolio_summary',
        'address',
        'city',
        'district',
        'latitude',
        'longitude',
        'service_area',
        'verification_status',
        'revision_note',
        'is_active',
        'is_demo',
        'rating_avg',
        'rating_count',
        'demo_rating',
        'demo_review_count',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
            'rating_avg' => 'decimal:2',
            'demo_rating' => 'decimal:2',
            'verified_at' => 'datetime',
            'verification_status' => ProviderStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class);
    }

    public function availabilitySlots(): HasMany
    {
        return $this->hasMany(AvailabilitySlot::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isApproved(): bool
    {
        return $this->verification_status === ProviderStatus::Approved && $this->is_active;
    }

    public function displayRating(): ?float
    {
        if ($this->rating_count > 0) {
            return (float) $this->rating_avg;
        }

        if ($this->is_demo && $this->demo_rating !== null) {
            return (float) $this->demo_rating;
        }

        return null;
    }

    public function displayReviewCount(): int
    {
        if ($this->rating_count > 0) {
            return (int) $this->rating_count;
        }

        return $this->is_demo ? (int) $this->demo_review_count : 0;
    }

    public function usesDemoRating(): bool
    {
        return $this->rating_count === 0 && $this->is_demo && $this->demo_rating !== null;
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
    }
}
