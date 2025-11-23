<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = new App\Application(dirname(__DIR__));
$capsule = $app->capsule();

$files = glob(__DIR__ . '/migrations/*.php');
sort($files);

foreach ($files as $file) {
    $migration = require $file;
    if (is_callable($migration)) {
        $migration($capsule);
        echo 'Executed migration: ' . basename($file) . PHP_EOL;
    }
}
