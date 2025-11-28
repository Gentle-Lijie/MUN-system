<?php
require 'vendor/autoload.php';
try {
    $pdo = new PDO('mysql:host=localhost;port=3306;dbname=mun;charset=utf8mb4', 'root', '123456');
    $pdo->exec("UPDATE Files SET title='db-update-test', status='published' WHERE id=1");
    $stmt = $pdo->query('SELECT id,title,status,updated_at FROM Files WHERE id=1');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);
} catch (Exception $e) {
    echo 'error: ' . $e->getMessage() . PHP_EOL;
}
