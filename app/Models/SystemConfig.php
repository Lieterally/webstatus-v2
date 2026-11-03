<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get a configuration value by key, with an optional default.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $config = static::where('key', $key)->first();

        return $config?->value ?? $default;
    }

    /**
     * Whether monitoring cycles are globally turned on.
     */
    public static function isMonitoringEnabled(): bool
    {
        return static::getValue('is_monitoring', '1') === '1';
    }
}
