<?php

use App\Support\Env;

return [
    'app' => [
        'name' => Env::get('APP_NAME', 'MUN Control PHP API'),
        'env' => Env::get('APP_ENV', 'production'),
        'debug' => Env::bool('APP_DEBUG', false),
        'url' => Env::get('APP_URL', 'http://localhost:8000'),
    ],
    'session' => [
        'cookie' => Env::get('SESSION_COOKIE', 'mun_session'),
        'secure' => Env::bool('SESSION_COOKIE_SECURE', false),
        'domain' => Env::get('SESSION_COOKIE_DOMAIN'),
    ],
    'cors' => [
        'origins' => Env::array('CORS_ORIGINS', ['http://localhost:5173']),
    ],
];
