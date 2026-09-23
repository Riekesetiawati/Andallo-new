<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'provider_id',
        'description',
        'photo_path',
    ];

    protected static function booted(): void
    {
        static::saving(function (Profile $profile): void {
            $owners = (int) ($profile->user_id !== null) + (int) ($profile->provider_id !== null);
            if ($owners !== 1) {
                throw new InvalidArgumentException('Profil harus terhubung ke tepat satu pemilik.');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
