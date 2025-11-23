<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return static function (Capsule $capsule): void {
    $schema = $capsule->schema();

    if ($schema->hasTable('Crises')) {
        // Some existing DB instances may have been created before 'status' column was added.
        if (!$schema->hasColumn('Crises', 'status')) {
            $schema->table('Crises', static function (Blueprint $table): void {
                $table->enum('status', ['draft', 'active', 'resolved', 'archived'])->default('active')->after('target_committees');
            });
        }

        // Also ensure 'target_committees' column exists and is JSON-compatible
        if (!$schema->hasColumn('Crises', 'target_committees')) {
            $schema->table('Crises', static function (Blueprint $table): void {
                $table->json('target_committees')->nullable()->after('file_path');
            });
        }

        // Ensure responses_allowed present
        if (!$schema->hasColumn('Crises', 'responses_allowed')) {
            $schema->table('Crises', static function (Blueprint $table): void {
                $table->boolean('responses_allowed')->default(false)->after('status');
            });
        }
    }
};
