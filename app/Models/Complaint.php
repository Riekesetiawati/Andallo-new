<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    protected $fillable = [
        'booking_id',
        'user_id',
        'subject',
        'description',
        'status',
        'admin_note',
        'handled_by',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'open' => 'Terbuka',
            'in_review' => 'Ditinjau',
            'resolved' => 'Selesai Ditangani',
            'closed' => 'Ditutup',
            default => $this->status,
        };
    }
}
