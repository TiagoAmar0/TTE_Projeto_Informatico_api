<?php

use App\Mail\SendCredentialsMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('should return a list of users', function () {
    $user = User::factory()->create(['type' => 'admin']);
    $token = $user->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    User::factory(3)->create();

    $response = $this->json('GET', '/api/users', [], $headers);

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'id',
                'name',
                'email',
                'service',
                'service_id',
                'type',
                'shifts',
                'type_normalized',
            ],
        ],
    ]);
});

it('should create a new user', function () {

    $user = User::factory()->create(['type' => 'admin']);
    $token = $user->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    $params = [
        'name' => fake()->name(),
        'email' => fake()->safeEmail(),
        'type' => fake()->randomElement(['admin', 'nurse', 'lead-nurse']),
    ];

    Mail::fake();

    $response = $this->json('POST', '/api/users', $params, $headers);

    $response->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'name' => $params['name'],
        'email' => $params['email'],
        'type' => $params['type'],
    ]);

    Mail::assertSent(SendCredentialsMail::class, function ($mail) use ($params) {
        return $mail->hasTo($params['email']);
    });
});

it('should show a specific user', function () {
    $admin = User::factory()->create(['type' => 'admin']);
    $token = $admin->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    $user = User::factory()->create();

    $response = $this->json('GET', '/api/users/' . $user->id, [], $headers);

    $response->assertStatus(200);

    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'email',
            'service',
            'service_id',
            'type',
            'shifts',
            'type_normalized',
        ],
    ]);
});

it('should update a user', function () {
    $admin = User::factory()->create(['type' => 'admin']);
    $token = $admin->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    $user = User::factory()->create();

    $params = [
        'name' => fake()->name(),
        'email' => fake()->safeEmail(),
        'type' => fake()->randomElement(['admin', 'nurse', 'lead-nurse']),
    ];

    $response = $this->json('PUT', '/api/users/' . $user->id, $params, $headers);

    $response->assertStatus(200);

    $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $params['name'],
            'email' => $params['email'],
            'type' => $params['type'],
        ]
    );
});

it('should not delete a user with service', function () {
    $admin = User::factory()->create(['type' => 'admin']);
    $token = $admin->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    $user = User::factory()->create();

    $response = $this->json('DELETE', '/api/users/' . $user->id, [], $headers);

    $response->assertStatus(422);

    $response->assertJsonStructure([
        'message'
    ]);
});

it('should delete a user without service', function () {
    $admin = User::factory()->create(['type' => 'admin']);
    $token = $admin->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    $user = User::factory()->create(['service_id' => null]);

    $response = $this->json('DELETE', '/api/users/' . $user->id, [], $headers);

    $response->assertStatus(200);

    $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]
    );

    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'email',
            'service',
            'service_id',
            'type',
            'shifts',
            'type_normalized',
        ],
    ]);
});
