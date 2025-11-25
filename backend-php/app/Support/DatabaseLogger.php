<?php

namespace App\Support;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use PDO;

class DatabaseLogger
{
    private static ?Connection $connection = null;

    public static function attach(Capsule $capsule): void
    {
        $connection = $capsule->getConnection();
        self::$connection = $connection;
        $connection->listen(static function (QueryExecuted $event): void {
            self::handleQuery($event);
        });
    }

    public static function logManual(
        string $action,
        ?string $targetTable = null,
        ?int $targetId = null,
        array $meta = []
    ): void {
        if (!self::$connection) {
            return;
        }
        self::insertRow($action, $targetTable, $targetId, $meta);
    }

    private static function handleQuery(QueryExecuted $event): void
    {
        if (!self::$connection || RequestContext::isLoggingMuted()) {
            return;
        }

        $sql = trim($event->sql);
        if ($sql === '') {
            return;
        }

        $action = strtoupper(strtok($sql, " \t\n\r")) ?: 'QUERY';
        $table = self::detectTable($action, $sql);

        if ($table && strcasecmp($table, 'Logs') === 0) {
            return;
        }

        $meta = [
            'sql' => $sql,
            'bindings' => $event->bindings,
            'timeMs' => $event->time,
        ];

        self::insertRow($action, $table, null, $meta);
    }

    private static function detectTable(string $action, string $sql): ?string
    {
        $patterns = [
            'SELECT' => '/\bFROM\s+`?([A-Za-z0-9_]+)`?/i',
            'DELETE' => '/\bFROM\s+`?([A-Za-z0-9_]+)`?/i',
            'INSERT' => '/\bINTO\s+`?([A-Za-z0-9_]+)`?/i',
            'UPDATE' => '/\bUPDATE\s+`?([A-Za-z0-9_]+)`?/i',
        ];

        if (isset($patterns[$action]) && preg_match($patterns[$action], $sql, $matches)) {
            return $matches[1];
        }

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $sql, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private static function insertRow(string $action, ?string $table, ?int $targetId, array $meta): void
    {
        RequestContext::withoutQueryLogging(static function () use ($action, $table, $targetId, $meta): void {
            if (!self::$connection) {
                return;
            }
            $metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($metaJson === false) {
                $metaJson = json_encode(['error' => 'failed to encode meta'], JSON_UNESCAPED_UNICODE);
            }
            $pdo = self::$connection->getPdo();
            $stmt = $pdo->prepare('INSERT INTO Logs (actor_user_id, action, target_table, target_id, meta_json) VALUES (:actor_user_id, :action, :target_table, :target_id, :meta_json)');
            $stmt->execute([
                ':actor_user_id' => RequestContext::userId(),
                ':action' => substr($action, 0, 255),
                ':target_table' => $table,
                ':target_id' => $targetId,
                ':meta_json' => $metaJson,
            ]);
        });
    }
}
