<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return static function (Capsule $capsule): void {
    $schema = $capsule->schema();

    if (!$schema->hasTable('Files')) {
        $schema->create('Files', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('committee_id')->nullable();
            $table->enum('type', ['position_paper', 'working_paper', 'draft_resolution', 'press_release', 'other']);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('content_path', 500)->nullable();
            $table->unsignedBigInteger('submitted_by');
            $table->timestamp('submitted_at')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'published', 'rejected'])->default('draft');
            $table->enum('visibility', ['committee_only', 'all_committees', 'public'])->default('committee_only');
            $table->text('dias_fb')->nullable();
            $table->timestampsTz();
            $table->foreign('committee_id')->references('id')->on('Committees')->onDelete('restrict');
            $table->foreign('submitted_by')->references('id')->on('Users')->onDelete('restrict');
            $table->index('submitted_by');
            $table->index('committee_id');
            $table->index('status');
        });
    }
};