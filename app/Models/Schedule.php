<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Schedule extends Model
{

    use HasFactory;

    protected $fillable = [
        'start',
        'end',
        'service_id',
        'draft',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function userShifts(): HasMany
    {
        return $this->hasMany(ShiftUser::class);
    }

    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(
            User::class,
            Service::class,
            'id',
            'service_id',
            'service_id',
            'id'
        );
    }

    public function shifts(): HasManyThrough
    {
        return $this->hasManyThrough(
            Shift::class,
            Service::class,
            'id',
            'service_id',
            'service_id',
            'id'
        );
    }
}
