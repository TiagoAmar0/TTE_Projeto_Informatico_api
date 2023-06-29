<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

uses(WithFaker::class);

test('login with valid credentials', function () {
    $user = User::factory()->create(['email' => fake()->safeEmail()]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
        ]);
});

test('login with invalid credentials', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'invalid@example.com',
        'password' => 'invalid_password',
    ]);

    $response->assertJsonStructure([
        'message',
        'error',
        'error_description'
    ])->assertStatus(400);
});

test('login with missing email', function () {
    $response = $this->postJson('/api/login', [
        'password' => 'password',
    ]);

    $response->assertJsonValidationErrors(['email'])->assertStatus(422);
});

test('login with missing password', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'admin@tte.pt',
    ]);

    $response->assertJsonValidationErrors(['password'])->assertStatus(422);
});

it('should log out the user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    $response = $this->json('DELETE', '/api/logout', [], $headers);

    $response->assertStatus(200);

    $this->assertCount(0, $user->tokens);
});

it('should change the user password', function () {
    $user = User::factory()->create();
    $token = $user->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    $currentPassword = 'oldpassword';
    $newPassword = 'newpassword';

    $params = [
        'current_password' => $currentPassword,
        'new_password' => $newPassword,
        'new_password_confirmation' => $newPassword,
    ];

    $user->password = Hash::make($currentPassword);
    $user->save();

    $response = $this->json('PUT', '/api/password', $params, $headers);

    $response->assertStatus(200);

    $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
});

it('should send a password reset email', function () {
    $user = User::factory()->create();

    $email = $user->email;

    Mail::fake();

    $response = $this->json('POST', '/api/forgot-password', ['email' => $email]);

    $response->assertStatus(200);

    Mail::assertSent(\App\Mail\SendResetPasswordEmail::class, function ($mail) use ($email) {
        return $mail->hasTo($email);
    });
});

it('should reset the user password', function () {
    $user = User::factory()->create();

    $token = Str::random(64);
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => $token,
        'created_at' => now(),
    ]);

    $newPassword = 'newpassword';

    $params = [
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
        'token' => $token,
    ];

    $response = $this->json('PUT', '/api/reset-password', $params);

    $response->assertStatus(200);

    $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));

    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email, 'token' => $token]);
});
