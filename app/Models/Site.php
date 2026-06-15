<?php

namespace App\Models;

use App\Enums\SiteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'category_id',
        'base_url',
        'description',
        'responsible_person_id',
        'status',
        'consecutive_down_count',
        'first_down_at',
        'notification_sent',
        'notification_cycle_counter',
        'avg_response_time',
    ];

    protected function casts(): array
    {
        return [
            'status' => SiteStatus::class,
            'first_down_at' => 'datetime',
            'notification_sent' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function responsiblePerson(): BelongsTo
    {
        return $this->belongsTo(ITStaff::class, 'responsible_person_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function checkResults(): HasMany
    {
        return $this->hasMany(CheckResult::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }
}
