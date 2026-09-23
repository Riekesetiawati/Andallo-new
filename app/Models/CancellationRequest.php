<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancellationRequest extends Model
{
    protected $fillable = [
        'booking_id',
        'requested_by',
        'stage',
        'reason',
        'status',
        'refund_estimate',
        'refund_amount',
        'admin_note',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'refund_estimate' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'resolved_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Peninjauan',
            'pending_funds_check' => 'Menunggu Pengecekan Dana',
            'awaiting_customer' => 'Menunggu Persetujuan Pelanggan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'customer_disputed' => 'Diperselisihkan',
            default => $this->status,
        };
    }
}
