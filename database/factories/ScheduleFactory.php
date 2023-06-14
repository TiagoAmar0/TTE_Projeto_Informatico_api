<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Schedule>
 */
class ScheduleFactory extends Factory
{

    protected $model = Schedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::query()->inRandomOrder()->first(),
            'start' => $this->faker->dateTimeBetween('-1 week', '+1 week')->format('Y-m-d'),
            'end' => $this->faker->dateTimeBetween('+1 week', '+2 week')->format('Y-m-d'),
            'draft' => $this->faker->boolean,
        ];
    }
}
