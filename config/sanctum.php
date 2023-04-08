<?php

use Laravel\Sanctum\Sanctum;

return [
    'stateful' => true,
    'expiration' => null,
    'prefix' => 'api',
    'middleware' => [
        'throttle:60,1',
        'bindings',
    ],
];
