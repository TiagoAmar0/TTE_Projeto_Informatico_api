<?php

use App\Mail\SendCredentialsMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('should return a list of users', function () {

    $user = User::factory()->create(['type' => 'admin']);
    $token = $user->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    // Crie alguns usuários
    User::factory(3)->create();

    // Chame a rota de listagem de usuários
    $response = $this->json('GET', '/api/users', [], $headers);

    // Verifique o status da resposta
    $response->assertStatus(200);

    // Verifique se a resposta contém os usuários corretos
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

    // Defina os parâmetros da solicitação
    $params = [
        'name' => fake()->name(),
        'email' => fake()->safeEmail(),
        'type' => fake()->randomElement(['admin', 'nurse', 'lead-nurse']),
    ];

    // Mock do envio de e-mail
    Mail::fake();

    // Chame a rota de criação de usuário
    $response = $this->json('POST', '/api/users', $params, $headers);

    // Verifique o status da resposta
    $response->assertStatus(200);

    // Verifique se o usuário foi criado corretamente no banco de dados
    $this->assertDatabaseHas('users', [
        'name' => $params['name'],
        'email' => $params['email'],
        'type' => $params['type'],
    ]);

    // Verifique se o e-mail de credenciais foi enviado
    Mail::assertSent(SendCredentialsMail::class, function ($mail) use ($params) {
        return $mail->hasTo($params['email']);
    });
});

it('should show a specific user', function () {
    $admin = User::factory()->create(['type' => 'admin']);
    $token = $admin->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    $user = User::factory()->create();

    // Chame a rota de visualização do usuário
    $response = $this->json('GET', '/api/users/' . $user->id, [], $headers);

    // Verifique o status da resposta
    $response->assertStatus(200);

    // Verifique se a resposta contém os dados do usuário correto
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

    // Defina os parâmetros da solicitação
    $params = [
        'name' => fake()->name(),
        'email' => fake()->safeEmail(),
        'type' => fake()->randomElement(['admin', 'nurse', 'lead-nurse']),
    ];

    // Chame a rota de atualização do usuário
    $response = $this->json('PUT', '/api/users/' . $user->id, $params, $headers);

    // Verifique o status da resposta
    $response->assertStatus(200);

    // Verifique se o usuário foi atualizado corretamente no banco de dados
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

    // Chame a rota de exclusão do usuário
    $response = $this->json('DELETE', '/api/users/' . $user->id, [], $headers);

    // Verifique o status da resposta
    $response->assertStatus(422);

    // Verifique se a resposta contém os dados do usuário excluído
    $response->assertJsonStructure([
        'message'
    ]);
});

it('should delete a user without service', function () {
    $admin = User::factory()->create(['type' => 'admin']);
    $token = $admin->createToken('TestToken')->accessToken;
    $headers = ['Authorization' => 'Bearer ' . $token];

    $user = User::factory()->create(['service_id' => null]);

    // Chame a rota de exclusão do usuário
    $response = $this->json('DELETE', '/api/users/' . $user->id, [], $headers);

    // Verifique o status da resposta
    $response->assertStatus(200);

    $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]
    );

    // Verifique se a resposta contém os dados do usuário excluído
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
