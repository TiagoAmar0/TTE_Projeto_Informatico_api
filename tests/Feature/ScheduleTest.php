<?php

use App\Models\Schedule;
use App\Models\Service;
use App\Models\ShiftUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function(){
   DB::table('users')->truncate();
   DB::table('services')->truncate();
});

it('can list services', function () {
    $service = Service::factory()->create();
    $admin = User::factory()->create(['type' => 'lead-nurse', 'service_id' => $service->id]);
    $token = $admin->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    $schedules = Schedule::factory()->count(3)->create(['service_id' => $service->id]);

    $response = $this->json('GET', "/api/services/{$service->id}/schedules", [], $headers);

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'start',
                'end',
                'draft',
                'service_id',
                'created_at',
                'updated_at',
            ]
        ],
    ]);
});

test('store final schedule without shift users', function () {
    ShiftUser::query()->truncate();
    Schedule::query()->truncate();

    $service = Service::factory()->create();
    $admin = User::factory()->create(['type' => 'lead-nurse', 'service_id' => $service->id]);
    $token = $admin->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    $data = [
        'draft' => false,
        'date_range' => [
            Carbon::createFromFormat('Y-m-d', fake()->date())->format('Y-m-d\TH:i:s.u\Z'),
            Carbon::createFromFormat('Y-m-d', fake()->date())->format('Y-m-d\TH:i:s.u\Z')
        ],
        'data' => [
            [
                'nurses_total' => fake()->numberBetween(1, 5),
                'date' => Carbon::createFromFormat('Y-m-d', fake()->date())->format('d/m/Y'),
                'date_formatted' => Carbon::createFromFormat('Y-m-d', fake()->date())->format('d/m/Y'),
                'nurses' => []
            ]
        ]
    ];

    $response = $this->postJson("/api/services/{$service->id}/schedules", $data, $headers);

    $response->assertStatus(422);
    $response->assertJsonStructure([
        'message'
    ]);
});

it('can create a new schedule', function () {
    ShiftUser::query()->truncate();
    Schedule::query()->truncate();

    $service = Service::factory()->create();
    $admin = User::factory()->create(['type' => 'lead-nurse', 'service_id' => $service->id]);
    $token = $admin->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];


    $users = User::factory(20)->create(['service_id' => $service->id])->toArray();
    $shifts = User::factory(7)->create(['service_id' => $service->id]);

    $shifts_ids = $shifts->pluck('id')->toArray();

    $data = [
        'draft' => fake()->boolean,
        'date_range' => [
            Carbon::createFromFormat('Y-m-d', fake()->date())->format('Y-m-d\TH:i:s.u\Z'),
            Carbon::createFromFormat('Y-m-d', fake()->date())->format('Y-m-d\TH:i:s.u\Z')
        ],
        'data' => [
            [
                'nurses_total' => fake()->numberBetween(1, 5),
                'date' => Carbon::createFromFormat('Y-m-d', fake()->date())->format('d/m/Y'),
                'date_formatted' => Carbon::createFromFormat('Y-m-d', fake()->date())->format('d/m/Y'),
                'nurses' => array_map(function($user) use ($shifts_ids){
                    return [
                        'id' => $user['id'],
                        'shift' => fake()->randomElement($shifts_ids)
                    ];
                }, $users)
            ],
        ]
    ];

    $response = $this->postJson("/api/services/{$service->id}/schedules", $data, $headers);
    $response->assertStatus(200);

    $response->assertJsonStructure([
        'message',
        'data' => [
            'id',
            'start',
            'end',
            'draft',
            'service_id',
            'created_at',
            'updated_at',
        ]
    ]);
});

it('store with invalid data', function () {
    ShiftUser::query()->truncate();
    Schedule::query()->truncate();
    $service = Service::factory()->create();
    $admin = User::factory()->create(['type' => 'lead-nurse', 'service_id' => $service->id]);
    $token = $admin->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    $data = [];

    $response = $this->postJson("/api/services/{$service->id}/schedules", $data, $headers);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['draft', 'date_range', 'data']);
});



it('shows schedule', function () {
    $service = Service::factory()->create();
    $admin = User::factory()->create(['type' => 'lead-nurse', 'service_id' => $service->id]);
    $token = $admin->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    $schedule = Schedule::factory()->create(['service_id' => $service->id]);

    $response = $this->getJson("/api/services/{$service->id}/schedules/{$schedule->id}", $headers);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'start',
                'end',
                'draft',
            ]
        ]);
});
