<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('swaps', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\User::class, 'proposing_user_id');
            $table->foreignIdFor(\App\Models\User::class, 'target_user_id');
            $table->foreignIdFor(\App\Models\ShiftUser::class, 'target_shift_user');
            $table->foreignIdFor(\App\Models\ShiftUser::class, 'payment_shift_user')->nullable();
            $table->boolean('direct');
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('swaps');
    }
};
