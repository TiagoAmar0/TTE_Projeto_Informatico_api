<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'service_id', 'start', 'end', 'nurses_qty', 'minutes'];

    public function service(){
        return $this->belongsTo(Service::class);
    }

    public function users(){
        return $this->belongsToMany(User::class)
            ->withPivot(['date']);
    }

    public function shiftUsers(): HasMany
    {
        return $this->hasMany(ShiftUser::class);
    }

}
