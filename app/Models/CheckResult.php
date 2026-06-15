<?php

namespace App\Models;

use App\Enums\ErrorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckResult extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'site_id',
        'page_id',
        'cycle_id',
        'http_code',
        'response_time_ms',
        'error_type',
        'checked_at',
    ];

    protected function casts(): array
    {
        return [
            'error_type' => ErrorType::class,
            'checked_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function checkingCycle(): BelongsTo
    {
        return $this->belongsTo(CheckingCycle::class, 'cycle_id');
    }
}
