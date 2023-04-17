<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = ['service_id', 'start', 'end'];

    public function service(){
        return $this->belongsTo(Service::class);
    }

    public function users(){
        return $this->belongsToMany(User::class)
            ->withPivot(['date']);
    }
}
