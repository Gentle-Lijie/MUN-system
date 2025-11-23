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
    echo "Connected successfully!\n";
} catch (Exception $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

try {
    echo "Dropping foreign key constraints...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS=0");
    
    echo "Dropping Motions table...\n";
    $db->exec("DROP TABLE IF EXISTS Motions");
    
    echo "Creating Motions table with correct schema...\n";
    $db->exec("
        CREATE TABLE Motions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT,
            motion_type ENUM(
                'open_main_list',
                'close_main_list',
                'set_speaking_time',
                'moderated_caucus',
                'unmoderated_caucus',
                'introduce_draft_resolution',
                'introduce_amendment',
                'vote_on_draft_resolution',
                'vote_on_amendment',
                'consultation_of_the_whole',
                'roll_call_vote',
                'adjourn_meeting'
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
            FOREIGN KEY (session_id) REFERENCES Sessions (id) ON DELETE RESTRICT,
            FOREIGN KEY (proposer_id) REFERENCES Delegates (id) ON DELETE SET NULL,
            FOREIGN KEY (speaker_list_id) REFERENCES SpeakerLists (id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "Re-enabling foreign key checks...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS=1");
    
    echo "Motions table recreated successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    $db->exec("SET FOREIGN_KEY_CHECKS=1");
    exit(1);
}
