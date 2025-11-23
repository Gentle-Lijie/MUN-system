<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$app = new App\Application(dirname(__DIR__));
$app->run();
