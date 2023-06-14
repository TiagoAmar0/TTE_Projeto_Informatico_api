<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shift>
 */
class ShiftFactory extends Factory
{
    protected $model = Shift::class;
    public function definition(): array
    {
        $startTime = $this->faker->time('H:i');
        $endTime = Carbon::createFromFormat('H:i', $startTime)->addHours(8)->format('H:i');

        return [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'service_id' => Service::factory()->create()->id,
            'start' => $startTime,
            'end' => $endTime,
            'nurses_qty' => $this->faker->numberBetween(1, 10),
            'minutes' => 480,
        ];
    }
}
