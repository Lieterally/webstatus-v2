<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ITStaff extends Model
{
    use HasFactory;
    protected $table = 'it_staffs';

    protected $fillable = [
        'name',
        'position',
    ];

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'responsible_person_id');
    }
}
