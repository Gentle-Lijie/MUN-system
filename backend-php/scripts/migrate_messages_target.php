<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Application;
use Illuminate\Database\Connection;
use Throwable;

$app = new Application(dirname(__DIR__));
$capsule = $app->capsule();
/** @var Connection $connection */
$connection = $capsule->getConnection();
$database = (string) $connection->getDatabaseName();

function columnExists(Connection $connection, string $database, string $table, string $column): bool
{
    $sql = 'SELECT COUNT(1) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?';
    $row = $connection->selectOne($sql, [$database, $table, $column]);
    if (!$row) {
        return false;
    }
    if (is_array($row)) {
        return (int) ($row['cnt'] ?? 0) > 0;
    } elseif (is_object($row)) {
        return (int) ($row->cnt ?? 0) > 0;
    }
    return false;
}

function indexExists(Connection $connection, string $database, string $table, string $index): bool
{
    $sql = 'SELECT COUNT(1) AS cnt FROM information_schema.statistics WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?';
    $row = $connection->selectOne($sql, [$database, $table, $index]);
    if (!$row) {
        return false;
    }
    if (is_array($row)) {
        return (int) ($row['cnt'] ?? 0) > 0;
    } elseif (is_object($row)) {
        return (int) ($row->cnt ?? 0) > 0;
    }
    return false;
}

function findForeignKey(Connection $connection, string $database, string $table, string $column): ?string
{
    $sql = 'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1';
    $row = $connection->selectOne($sql, [$database, $table, $column]);
    if (!$row) {
        return null;
    }
    if (is_array($row)) {
        return $row['CONSTRAINT_NAME'] ?? $row['constraint_name'] ?? null;
    }
    if (is_object($row)) {
        return $row->CONSTRAINT_NAME ?? $row->constraint_name ?? null;
    }
    return null;
}

try {
    $table = 'Messages';

    if (!columnExists($connection, $database, $table, 'target')) {
        $connection->statement(<<<SQL
            ALTER TABLE `Messages`
            ADD COLUMN `target` ENUM('everyone','delegate','committee','dias') NOT NULL DEFAULT 'everyone'
            AFTER `channel`
        SQL);
    }

    $fkName = findForeignKey($connection, $database, $table, 'to_user_id');
    if ($fkName) {
        $connection->statement(sprintf('ALTER TABLE `Messages` DROP FOREIGN KEY `%s`', $fkName));
    }

    if (columnExists($connection, $database, $table, 'to_user_id')) {
        $connection->statement('ALTER TABLE `Messages` CHANGE COLUMN `to_user_id` `target_id` INT NULL');
    }

    $connection->statement(<<<SQL
        UPDATE `Messages`
        SET `target` = CASE `channel`
            WHEN 'private' THEN 'delegate'
            WHEN 'committee' THEN 'committee'
            WHEN 'dais' THEN 'dias'
            ELSE 'everyone'
        END
    SQL);

    $connection->statement("UPDATE `Messages` SET `target_id` = NULL WHERE `target` = 'everyone'");

    if (!indexExists($connection, $database, $table, 'idx_messages_target')) {
        $connection->statement('CREATE INDEX `idx_messages_target` ON `Messages` (`target`)');
    }

    if (!indexExists($connection, $database, $table, 'idx_messages_target_id')) {
        $connection->statement('CREATE INDEX `idx_messages_target_id` ON `Messages` (`target_id`)');
    }

    echo "Messages table migration completed successfully." . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
