<?php
/**
 * 测试端点脚本
 * 用于验证 API 端点是否正常工作
 */

require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== 测试数据库连接 ===\n";

// 检查表是否存在
$tables = DB::select("SHOW TABLES LIKE 'Motions'");
echo "Motions 表: " . (count($tables) > 0 ? "✓ 存在\n" : "✗ 不存在\n");

$tables = DB::select("SHOW TABLES LIKE 'Sessions'");
echo "Sessions 表: " . (count($tables) > 0 ? "✗ 仍然存在（应该被删除）\n" : "✓ 已删除\n");

$tables = DB::select("SHOW TABLES LIKE 'CommitteeSessions'");
echo "CommitteeSessions 表: " . (count($tables) > 0 ? "✓ 存在\n" : "✗ 不存在\n");

echo "\n=== 检查 Motions 表结构 ===\n";
$columns = DB::select("SHOW COLUMNS FROM Motions");
foreach ($columns as $column) {
    if ($column->Field === 'committee_session_id') {
        echo "✓ committee_session_id 字段存在\n";
    }
    if ($column->Field === 'session_id') {
        echo "✗ session_id 字段仍然存在（应该被删除）\n";
    }
}

echo "\n=== 检查 motion_type ENUM 值 ===\n";
$result = DB::select("SHOW COLUMNS FROM Motions WHERE Field = 'motion_type'");
if (count($result) > 0) {
    echo "motion_type 类型: " . $result[0]->Type . "\n";
}

echo "\n=== 检查委员会数据 ===\n";
$committees = DB::select("SELECT id, name, code FROM Committees LIMIT 5");
echo "找到 " . count($committees) . " 个委员会:\n";
foreach ($committees as $committee) {
    echo "  - ID: {$committee->id}, 名称: {$committee->name}, 代码: {$committee->code}\n";
}

echo "\n=== 测试完成 ===\n";
