<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [
        'booking_id',
        'payment_id',
        'cancellation_request_id',
        'amount',
        'reason',
        'status',
        'responsible_admin_id',
        'processed_at',
        'proof_path',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => RefundStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function cancellationRequest(): BelongsTo
    {
        return $this->belongsTo(CancellationRequest::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_admin_id');
    }
}
