<?php
require __DIR__ . '/../vendor/autoload.php';
$app = new App\Application(dirname(__DIR__));
$capsule = $app->capsule();

use App\Models\Crisis;

try {
    $crisis = Crisis::create([
        'title' => '自动化测试危机',
        'content' => '这是用于验证数据库迁移后的自动化测试内容',
        'file_path' => null,
        'target_committees' => null,
        'status' => 'active',
        'responses_allowed' => true,
        'published_by' => 1,
        'published_at' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
    ]);
    echo "Inserted crisis id: " . $crisis->id . PHP_EOL;
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
