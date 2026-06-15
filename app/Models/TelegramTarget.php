<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramTarget extends Model
{
    protected $fillable = [
        'chat_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
