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
    
    // Check if SpeakerListEntries table exists
    echo "Checking SpeakerListEntries table...\n";
    $result = $db->query("SHOW TABLES LIKE 'SpeakerListEntries'");
    $exists = $result->fetch();
    
    if ($exists) {
        echo "✓ SpeakerListEntries table exists\n\n";
        echo "Table structure:\n";
        $result = $db->query("DESCRIBE SpeakerListEntries");
        foreach ($result as $row) {
            echo "  {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']} {$row['Extra']}\n";
        }
        
        echo "\nTable data count:\n";
        $result = $db->query("SELECT COUNT(*) as count FROM SpeakerListEntries");
        $count = $result->fetch();
        echo "  Total entries: {$count['count']}\n";
        
        if ($count['count'] > 0) {
            echo "\nSample entries:\n";
            $result = $db->query("SELECT * FROM SpeakerListEntries ORDER BY created_at DESC LIMIT 5");
            foreach ($result as $row) {
                echo "  ID: {$row['id']}, Speaker List: {$row['speaker_list_id']}, Delegate: {$row['delegate_id']}, Position: {$row['position']}, Status: {$row['status']}\n";
            }
        }
    } else {
        echo "✗ SpeakerListEntries table does NOT exist\n";
        echo "\nCreating SpeakerListEntries table...\n";
        
        $db->exec("
            CREATE TABLE SpeakerListEntries (
                id INT AUTO_INCREMENT PRIMARY KEY,
                speaker_list_id INT NOT NULL,
                delegate_id INT NOT NULL,
                position INT NOT NULL,
                status ENUM('waiting', 'speaking', 'removed') DEFAULT 'waiting',
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (speaker_list_id) REFERENCES SpeakerLists(id) ON DELETE CASCADE,
                FOREIGN KEY (delegate_id) REFERENCES Delegates(id) ON DELETE CASCADE,
                UNIQUE KEY unique_position (speaker_list_id, position)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        echo "✓ SpeakerListEntries table created successfully!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
