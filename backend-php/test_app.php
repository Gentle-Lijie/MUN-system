<?php

require __DIR__ . '/vendor/autoload.php';

try {
    $app = new App\Application(__DIR__);
    echo "Application loaded successfully\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}