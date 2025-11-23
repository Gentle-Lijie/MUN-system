<?php

use App\Support\Env;

$driver = Env::get('DB_CONNECTION', 'mysql');

if ($driver === 'sqlite') {
    return [
        'driver' => 'sqlite',
        'database' => Env::get('DB_DATABASE', ':memory:'),
        'prefix' => '',
        'foreign_key_constraints' => true,
    ];
}

return [
    'driver' => 'mysql',
    'host' => Env::get('DB_HOST', '106.15.139.140'),
    'port' => Env::get('DB_PORT', 3306),
    'database' => Env::get('DB_DATABASE', 'mun'),
    'username' => Env::get('DB_USERNAME', Env::get('MYSQL_USER', 'root')),
    'password' => Env::get('DB_PASSWORD', Env::get('MYSQL_PASSWORD', '123456')),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => false,
    'engine' => null,
    'options' => [
        \PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
