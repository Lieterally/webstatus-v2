<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DowntimeHistory extends Model
{
    protected $fillable = [
        'site_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'affected_pages',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'affected_pages' => 'array',
            'duration_seconds' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Whether this outage is still ongoing.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Get the live duration in seconds (uses ended_at if resolved, now if active).
     */
    public function getLiveDurationSeconds(): int
    {
        $end = $this->ended_at ?? now();
        return (int) $this->started_at->diffInSeconds($end);
    }

    /**
     * Get affected pages as array, defaulting to empty array if null.
     */
    public function getAffectedPagesAttribute($value): array
    {
        return $value ? json_decode($value, true) : [];
    }
}
