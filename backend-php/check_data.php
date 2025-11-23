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
    
    echo "Checking SpeakerLists...\n";
    $result = $db->query("SELECT * FROM SpeakerLists");
    $count = 0;
    foreach ($result as $row) {
        $count++;
        echo "  ID: {$row['id']}, Committee: {$row['committee_id']}\n";
    }
    echo "Total: $count\n\n";
    
    echo "Checking Sessions...\n";
    $result = $db->query("SELECT * FROM Sessions WHERE speaker_list_id IS NOT NULL");
    $count = 0;
    foreach ($result as $row) {
        $count++;
        echo "  Session ID: {$row['id']}, Committee: {$row['committee_id']}, Type: {$row['type']}, Speaker List: {$row['speaker_list_id']}\n";
    }
    echo "Total with speaker_list_id: $count\n\n";
    
    echo "Checking Motions...\n";
    $result = $db->query("SELECT * FROM Motions ORDER BY created_at DESC LIMIT 10");
    $count = 0;
    foreach ($result as $row) {
        $count++;
        echo "  Motion ID: {$row['id']}, Session: {$row['session_id']}, Type: {$row['motion_type']}, Speaker List: {$row['speaker_list_id']}, State: {$row['state']}\n";
    }
    echo "Total: $count\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
