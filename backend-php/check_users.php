<?php
require 'vendor/autoload.php';
try {
    $pdo = new PDO('mysql:host=localhost;port=3306;dbname=mun;charset=utf8mb4', 'root', '123456');
    $stmt = $pdo->query('SELECT id, name, role, session_token FROM Users LIMIT 20');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Users: \n";
    foreach ($rows as $r) {
        echo "- id={$r['id']} name={$r['name']} role={$r['role']} token=" . ($r['session_token'] ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo 'error: ' . $e->getMessage() . PHP_EOL;
}
