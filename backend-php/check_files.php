<?php

require 'vendor/autoload.php';

try {
    $pdo = new PDO(
        'mysql:host=localhost;port=3306;dbname=mun;charset=utf8mb4',
        'root',
        '123456'
    );

    $stmt = $pdo->query('SELECT COUNT(*) as count FROM Files WHERE status = "published"');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Published files count: ' . $result['count'] . PHP_EOL;

    $stmt = $pdo->query('SELECT COUNT(*) as count FROM Files');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'Total files count: ' . $result['count'] . PHP_EOL;

    // Show status distribution
    $stmt = $pdo->query('SELECT status, COUNT(*) as count FROM Files GROUP BY status');
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo PHP_EOL . 'Status distribution:' . PHP_EOL;
    foreach ($statuses as $status) {
        echo "- Status: {$status['status']}, Count: {$status['count']}" . PHP_EOL;
    }

} catch (Exception $e) {
    echo 'Database error: ' . $e->getMessage() . PHP_EOL;
}