<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HourlyStat extends Model
{
    protected $fillable = [
        'site_id',
        'period_start',
        'avg_response_time_ms',
        'downtime_seconds',
        'checks_count',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'datetime',
            'avg_response_time_ms' => 'float',
            'downtime_seconds' => 'integer',
            'checks_count' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
