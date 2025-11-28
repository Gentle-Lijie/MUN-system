<?php

use App\Support\Env;

$driver = Env::get('DB_CONNECTION', 'mysql');

if ($driver === 'sqlite') {
    $database = Env::get('DB_DATABASE', ':memory:');
    if ($database === ':memory:') {
        $database = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mun_php_tests.sqlite';
    }
    if ($database !== ':memory:' && !file_exists($database)) {
        touch($database);
    }

    return [
        'driver' => 'sqlite',
        'database' => $database,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ];
}

return [
    'driver' => 'mysql',
    'host' => Env::get('DB_HOST', 'localhost'),
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
