<?php

declare(strict_types=1);

// Enable comprehensive error reporting but disable HTML display for API
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');

// Set exception handler to catch uncaught exceptions
set_exception_handler(function($e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Uncaught Exception',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString()),
    ]);
    exit(1);
});

require __DIR__ . '/../vendor/autoload.php';

$app = new App\Application(dirname(__DIR__));
$app->run();
