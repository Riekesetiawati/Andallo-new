<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    protected $fillable = [
        'code',
        'customer_id',
        'provider_id',
        'service_id',
        'scheduled_date',
        'start_time',
        'end_time',
        'city',
        'address',
        'customer_notes',
        'latitude',
        'longitude',
        'service_name_snapshot',
        'price_snapshot',
        'price_unit_snapshot',
        'duration_snapshot',
        'includes_snapshot',
        'excludes_snapshot',
        'requires_visit_snapshot',
        'total',
        'status',
        'payment_status',
        'refund_status',
        'provider_response_deadline',
        'payment_deadline',
        'admin_note',
        'provider_note',
        'cancellation_policy_snapshot',
        'whatsapp_dispatch_status',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'price_snapshot' => 'decimal:2',
            'total' => 'decimal:2',
            'requires_visit_snapshot' => 'boolean',
            'status' => BookingStatus::class,
            'payment_status' => PaymentStatus::class,
            'refund_status' => RefundStatus::class,
            'provider_response_deadline' => 'datetime',
            'payment_deadline' => 'datetime',
            'is_demo' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class)->orderBy('id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function reservation(): HasOne
    {
        return $this->hasOne(ProviderSlotReservation::class);
    }

    public function cancellationRequests(): HasMany
    {
        return $this->hasMany(CancellationRequest::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function scheduleLabel(): string
    {
        $start = substr((string) $this->start_time, 0, 5);
        $end = substr((string) $this->end_time, 0, 5);

        return $this->scheduled_date?->translatedFormat('l, d M Y').' · '.$start.'–'.$end;
    }

    public function involves(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($this->customer_id === $user->id) {
            return true;
        }

        return $user->isProvider() && $user->provider && $this->provider_id === $user->provider->id;
    }
}
