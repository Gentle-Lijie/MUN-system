<?php

use App\Support\Env;

$domain = Env::get('url', Env::get('APP_DOMAIN', 'localhost'));
$frontendScheme = Env::get('FRONTEND_SCHEME', 'https');
$backendScheme = Env::get('BACKEND_SCHEME', 'https');
$frontendPort = Env::get('FRONTEND_PORT', '4173');
$backendPort = Env::get('BACKEND_PORT', '8000');

$defaultAppUrl = sprintf('%s://%s:%s', $backendScheme, $domain, $backendPort);
$defaultCorsOrigins = array_values(array_unique(array_filter([
    sprintf('%s://%s:%s', $frontendScheme, $domain, $frontendPort),
    sprintf('http://%s:%s', $domain, $frontendPort),
    $frontendPort === '80' ? sprintf('%s://%s', $frontendScheme, $domain) : null,
    'http://localhost:4173',
    'http://127.0.0.1:4173',
])));

return [
    'app' => [
        'name' => Env::get('APP_NAME', 'MUN Control PHP API'),
        'env' => Env::get('APP_ENV', 'production'),
        'debug' => Env::bool('APP_DEBUG', false),
        'url' => Env::get('APP_URL', $defaultAppUrl),
    ],
    'session' => [
        'cookie' => Env::get('SESSION_COOKIE', 'mun_session'),
        'secure' => Env::bool('SESSION_COOKIE_SECURE', false),
    ],
    'cors' => [
        'origins' => Env::array('CORS_ORIGINS', $defaultCorsOrigins),
    ],
];
