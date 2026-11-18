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
        'note',
        'note_updated_by',
        'note_updated_at',
    ];

    /** Longest public note staff can write on an outage */
    public const NOTE_MAX_LENGTH = 500;

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'affected_pages' => 'array',
            'duration_seconds' => 'integer',
            'note_updated_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** The staff member who last changed the note */
    public function noteAuthor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'note_updated_by');
    }

    /**
     * Whether this outage is still ongoing.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Whether this outage was closed because monitoring was turned off, not because the site recovered.
     */
    public function wasMonitoringStopped(): bool
    {
        return $this->status === 'monitoring_stopped';
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
