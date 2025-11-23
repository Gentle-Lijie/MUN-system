<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
try {
    $config = require __DIR__ . '/config/database.php';
    echo "Connecting to database: {$config['host']}:{$config['port']}\n";
    $capsule->addConnection($config);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    
    $db = $capsule->getConnection()->getPdo();
    echo "Connected successfully!\n\n";
    
    // Fix SpeakerLists table
    echo "Adding timestamp columns to SpeakerLists table...\n";
    try {
        $db->exec("ALTER TABLE SpeakerLists 
                   ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                   ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "✓ SpeakerLists table updated successfully!\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ Columns already exist, skipping...\n";
        } else {
            throw $e;
        }
    }
    
    // Fix SpeakerListEntries table
    echo "\nAdding timestamp columns to SpeakerListEntries table...\n";
    try {
        $db->exec("ALTER TABLE SpeakerListEntries 
                   ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                   ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "✓ SpeakerListEntries table updated successfully!\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ Columns already exist, skipping...\n";
        } else {
            throw $e;
        }
    }
    
    // Fix Sessions table
    echo "\nAdding timestamp columns to Sessions table...\n";
    try {
        $db->exec("ALTER TABLE Sessions 
                   ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                   ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        echo "✓ Sessions table updated successfully!\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ Columns already exist, skipping...\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n✅ All tables fixed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
