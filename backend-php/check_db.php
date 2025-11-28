<?php
/**
 * 简单的数据库检查脚本
 */

$host = 'localhost';
$port = 3306;
$database = 'mun';
$username = 'root';
$password = '123456';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== 数据库连接成功 ===\n\n";
    
    // 检查表是否存在
    echo "=== 检查表是否存在 ===\n";
    $tables = ['Motions', 'Sessions', 'CommitteeSessions'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        $status = $exists ? '✓ 存在' : '✗ 不存在';
        if ($table === 'Sessions') {
            $status = $exists ? '✗ 仍然存在（应该被删除）' : '✓ 已删除';
        }
        echo "$table: $status\n";
    }
    
    echo "\n=== 检查 Motions 表结构 ===\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM Motions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        if ($column['Field'] === 'committee_session_id') {
            echo "✓ committee_session_id 字段存在\n";
        }
        if ($column['Field'] === 'session_id') {
            echo "✗ session_id 字段仍然存在（应该被删除）\n";
        }
        if ($column['Field'] === 'motion_type') {
            echo "motion_type 类型: {$column['Type']}\n";
        }
    }
    
    echo "\n=== 检查委员会数据 ===\n";
    $stmt = $pdo->query("SELECT id, name, code FROM Committees LIMIT 5");
    $committees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "找到 " . count($committees) . " 个委员会:\n";
    foreach ($committees as $committee) {
        echo "  - ID: {$committee['id']}, 名称: {$committee['name']}, 代码: {$committee['code']}\n";
    }
    
    echo "\n=== 测试完成 ===\n";
    
} catch (PDOException $e) {
    echo "数据库错误: " . $e->getMessage() . "\n";
    exit(1);
}
