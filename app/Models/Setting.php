<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cached = Cache::remember('setting.'.$key, 30, function () use ($key) {
            return static::query()->where('key', $key)->value('value');
        });

        if ($cached === null) {
            return config('andallo.defaults.'.$key, $default);
        }

        return $cached;
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value],
        );
        Cache::forget('setting.'.$key);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::getValue($key, $default ? '1' : '0');

        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function int(string $key, int $default): int
    {
        $value = static::getValue($key, $default);

        return (int) $value;
    }
}
