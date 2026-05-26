<?php

return [
    'path' => 'super-admin',
    'domain' => null,
    'home_url' => '/',
    'brand' => 'TiT',
    'middleware' => [
        'authenticate' => [
            \Filament\Http\Middleware\Authenticate::class,
        ],
    ],
];
