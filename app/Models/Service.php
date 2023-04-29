<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class)->orderBy('name');;
    }

    public function shifts(): HasMany {
        return $this->hasMany(Shift::class);
    }

    public function schedules(): HasMany {
        return $this->hasMany(Schedule::class);
    }
}
