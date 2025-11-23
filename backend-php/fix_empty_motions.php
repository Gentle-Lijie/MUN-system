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
    
    echo "Checking Motions with empty motion_type...\n";
    $result = $db->query("SELECT * FROM Motions WHERE motion_type = '' OR motion_type IS NULL");
    foreach ($result as $row) {
        echo "  Motion ID: {$row['id']}, Session: {$row['session_id']}, Type: '{$row['motion_type']}'\n";
    }
    
    echo "\nChecking Sessions for those motions...\n";
    $result = $db->query("SELECT * FROM Sessions WHERE id IN (SELECT session_id FROM Motions WHERE motion_type = '' OR motion_type IS NULL)");
    foreach ($result as $row) {
        echo "  Session ID: {$row['id']}, Type: '{$row['type']}', Approved: {$row['is_approved']}\n";
    }
    
    // Delete motions with empty motion_type
    echo "\nDeleting motions with empty motion_type...\n";
    $db->exec("DELETE FROM Motions WHERE motion_type = '' OR motion_type IS NULL");
    echo "✓ Deleted\n";
    
    echo "\nRemaining motions:\n";
    $result = $db->query("SELECT * FROM Motions ORDER BY created_at DESC");
    foreach ($result as $row) {
        echo "  Motion ID: {$row['id']}, Type: '{$row['motion_type']}', State: {$row['state']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
