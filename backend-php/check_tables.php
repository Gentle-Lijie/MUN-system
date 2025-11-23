<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
try {
    $config = require __DIR__ . '/config/database.php';
    $capsule->addConnection($config);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    
    $db = $capsule->getConnection()->getPdo();
    
    // Check table structures
    echo "Sessions table structure:\n";
    $result = $db->query("DESCRIBE Sessions");
    foreach ($result as $row) {
        if ($row['Field'] === 'id') {
            echo "  id: {$row['Type']}\n";
        }
    }
    
    echo "\nDelegates table structure:\n";
    $result = $db->query("DESCRIBE Delegates");
    foreach ($result as $row) {
        if ($row['Field'] === 'id') {
            echo "  id: {$row['Type']}\n";
        }
    }
    
    echo "\nSpeakerLists table structure:\n";
    $result = $db->query("DESCRIBE SpeakerLists");
    foreach ($result as $row) {
        if ($row['Field'] === 'id') {
            echo "  id: {$row['Type']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
