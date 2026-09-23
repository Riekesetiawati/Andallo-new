<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'booking_id',
        'user_id',
        'channel',
        'recipient',
        'template',
        'body',
        'status',
        'error',
        'sent_at',
        'attempts',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'simulated' => 'Simulasi (belum terkirim)',
            'sent' => 'Terkirim',
            'failed' => 'Gagal',
            'manual' => 'Tautan manual',
            default => $this->status,
        };
    }
}
