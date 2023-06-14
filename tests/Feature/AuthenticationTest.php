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
    // Crie um usuário autenticado
    $user = User::factory()->create();
    $token = $user->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    // Chame a rota de logout
    $response = $this->json('DELETE', '/api/logout', [], $headers);

    // Verifique o status da resposta
    $response->assertStatus(200);

    // Verifique se o token do usuário foi excluído
    $this->assertCount(0, $user->tokens);
});

it('should change the user password', function () {
    // Crie um usuário autenticado
    $user = User::factory()->create();
    $token = $user->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    // Defina a senha atual e a nova senha
    $currentPassword = 'oldpassword';
    $newPassword = 'newpassword';

    // Defina os parâmetros da solicitação
    $params = [
        'current_password' => $currentPassword,
        'new_password' => $newPassword,
        'new_password_confirmation' => $newPassword,
    ];

    // Defina a senha atual do usuário
    $user->password = Hash::make($currentPassword);
    $user->save();

    // Chame a rota de alteração de senha
    $response = $this->json('PUT', '/api/password', $params, $headers);

    // Verifique o status da resposta
    $response->assertStatus(200);

    // Verifique se a senha do usuário foi alterada
    $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));
});

it('should send a password reset email', function () {
    // Crie um usuário
    $user = User::factory()->create();

    // Defina o email para recuperar a senha
    $email = $user->email;

    // Mock do envio de e-mail
    Mail::fake();

    // Chame a rota de esquecimento de senha
    $response = $this->json('POST', '/api/forgot-password', ['email' => $email]);

    // Verifique o status da resposta
    $response->assertStatus(200);

    // Verifique se o e-mail de recuperação de senha foi enviado
    Mail::assertSent(\App\Mail\SendResetPasswordEmail::class, function ($mail) use ($email) {
        return $mail->hasTo($email);
    });
});

it('should reset the user password', function () {
    // Crie um usuário
    $user = User::factory()->create();

    // Crie um token de redefinição de senha
    $token = Str::random(64);
    DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => $token,
        'created_at' => now(),
    ]);

    // Defina a nova senha
    $newPassword = 'newpassword';

    // Defina os parâmetros da solicitação
    $params = [
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
        'token' => $token,
    ];

    // Chame a rota de redefinição de senha
    $response = $this->json('PUT', '/api/reset-password', $params);

    // Verifique o status da resposta
    $response->assertStatus(200);

    // Verifique se a senha do usuário foi redefinida
    $this->assertTrue(Hash::check($newPassword, $user->fresh()->password));

    // Verifique se o token de redefinição de senha foi removido
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email, 'token' => $token]);
});
