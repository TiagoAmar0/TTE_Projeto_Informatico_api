<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Swap extends Model
{
    use HasFactory;

    protected $fillable = [
        'proposing_user_id',
        'target_user_id',
        'target_shift_user',
        'payment_shift_user',
        'direct',
        'status'
    ];

    public function proposingUser()
    {
        return $this->belongsTo(User::class, 'proposing_user_id');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function targetShiftUser()
    {
        return $this->belongsTo(ShiftUser::class, 'target_shift_user');
    }

    public function paymentShiftUser()
    {
        return $this->belongsTo(ShiftUser::class, 'payment_shift_user');
    }
}
