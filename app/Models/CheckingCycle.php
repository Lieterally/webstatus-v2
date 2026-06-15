<?php

namespace App\Models;

use App\Enums\TriggerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckingCycle extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'started_at',
        'completed_at',
        'trigger_type',
        'sites_checked',
        'sites_down',
    ];

    protected function casts(): array
    {
        return [
            'trigger_type' => TriggerType::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function checkResults(): HasMany
    {
        return $this->hasMany(CheckResult::class, 'cycle_id');
    }
}
