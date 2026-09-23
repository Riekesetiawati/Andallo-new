<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilitySlot extends Model
{
    protected $fillable = [
        'provider_id',
        'weekday',
        'specific_date',
        'start_time',
        'end_time',
        'is_blocked',
    ];

    protected function casts(): array
    {
        return [
            'specific_date' => 'date',
            'is_blocked' => 'boolean',
        ];
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
