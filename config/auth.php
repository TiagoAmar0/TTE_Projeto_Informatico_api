<?php

return [
    'CLIENT_ID' => env('PASSPORT_CLIENT_ID'),
    'CLIENT_SECRET' =>  env('PASSPORT_CLIENT_SECRET'),
    'PASSPORT_SERVER_URL' =>  env('PASSPORT_SERVER_URL'),
    'defaults' => [
        'guard' => 'api',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 1440, // 1 day,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
