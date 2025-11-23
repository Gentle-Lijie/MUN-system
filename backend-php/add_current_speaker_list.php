<?php
/**
 * 为 CommitteeSessions 表添加 current_speaker_list_id 字段
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as DB;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$capsule = new DB;
$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST'],
    'port'      => $_ENV['DB_PORT'],
    'database'  => $_ENV['DB_DATABASE'],
    'username'  => $_ENV['DB_USERNAME'],
    'password'  => $_ENV['DB_PASSWORD'],
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== 添加 current_speaker_list_id 字段到 CommitteeSessions 表 ===\n\n";

try {
    // 先检查 SpeakerLists 的 id 类型
    $speakerListId = DB::select("SHOW COLUMNS FROM SpeakerLists WHERE Field = 'id'");
    echo "SpeakerLists.id 类型: " . $speakerListId[0]->Type . "\n\n";
    
    // 检查字段是否已存在
    $columns = DB::select("SHOW COLUMNS FROM CommitteeSessions WHERE Field = 'current_speaker_list_id'");
    
    if (count($columns) > 0) {
        echo "✓ current_speaker_list_id 字段已存在\n";
    } else {
        echo "添加 current_speaker_list_id 字段...\n";
        DB::statement("
            ALTER TABLE CommitteeSessions 
            ADD COLUMN current_speaker_list_id INT NULL AFTER duration_minutes,
            ADD CONSTRAINT fk_current_speaker_list 
                FOREIGN KEY (current_speaker_list_id) 
                REFERENCES SpeakerLists(id) 
                ON DELETE SET NULL
        ");
        echo "✓ current_speaker_list_id 字段添加成功\n";
    }
    
    echo "\n=== 完成 ===\n";
    
} catch (Exception $e) {
    echo "✗ 错误: " . $e->getMessage() . "\n";
    exit(1);
}
