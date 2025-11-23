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
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    echo "=== DATABASE RESTRUCTURING ===\n\n";
    
    echo "Step 1: Disabling foreign key checks...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS=0");
    
    echo "Step 2: Dropping old Sessions table...\n";
    $db->exec("DROP TABLE IF EXISTS Sessions");
    echo "✓ Sessions table dropped\n\n";
    
    echo "Step 3: Dropping Motions table...\n";
    $db->exec("DROP TABLE IF EXISTS Motions");
    echo "✓ Motions table dropped\n\n";
    
    echo "Step 4: Creating new Motions table with updated schema...\n";
    $db->exec("
        CREATE TABLE Motions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            committee_session_id INT,
            motion_type ENUM(
                'open_main_list',
                'moderate_caucus',
                'unmoderated_caucus',
                'unmoderated_debate',
                'right_of_query',
                'begin_special_state',
                'end_special_state',
                'adjourn_meeting',
                'document_reading',
                'personal_speech',
                'vote',
                'right_of_reply'
            ) NOT NULL,
            proposer_id INT,
            file_id INT,
            unit_time_seconds INT,
            total_time_seconds INT,
            speaker_list_id INT DEFAULT NULL,
            vote_required BOOLEAN DEFAULT FALSE,
            veto_applicable BOOLEAN DEFAULT FALSE,
            state ENUM ('passed', 'rejected', 'pending') DEFAULT 'pending',
            vote_result JSON,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (committee_session_id) REFERENCES CommitteeSessions (id) ON DELETE CASCADE,
            FOREIGN KEY (proposer_id) REFERENCES Delegates (id) ON DELETE SET NULL,
            FOREIGN KEY (speaker_list_id) REFERENCES SpeakerLists (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Motions table created with new schema\n\n";
    
    echo "Step 5: Adding status column to Delegates table...\n";
    try {
        $db->exec("ALTER TABLE Delegates ADD COLUMN status ENUM('present', 'absent') DEFAULT 'present' AFTER veto_allowed");
        echo "✓ Status column added to Delegates\n\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "✓ Status column already exists\n\n";
        } else {
            throw $e;
        }
    }
    
    echo "Step 6: Re-enabling foreign key checks...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS=1");
    echo "✓ Foreign key checks enabled\n\n";
    
    echo "=== RESTRUCTURING COMPLETED SUCCESSFULLY ===\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    $db->exec("SET FOREIGN_KEY_CHECKS=1");
    exit(1);
}
