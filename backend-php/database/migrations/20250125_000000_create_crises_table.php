<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return static function (Capsule $capsule): void {
    $schema = $capsule->schema();

    if (!$schema->hasTable('Crises')) {
        $schema->create('Crises', static function (Blueprint $table): void {
            // use unsigned integer id to remain compatible with the legacy SQL schema
            $table->increments('id');
            $table->string('title', 255);
            $table->text('content');
            $table->string('file_path', 500)->nullable();
            $table->unsignedBigInteger('published_by');
            $table->timestamp('published_at')->nullable();
            $table->json('target_committees')->nullable();
            $table->enum('status', ['draft', 'active', 'resolved', 'archived'])->default('active');
            $table->boolean('responses_allowed')->default(false);
            $table->timestampsTz();
            $table->foreign('published_by')->references('id')->on('Users')->onDelete('restrict');
            $table->index('published_by');
            $table->index('published_at');
        });
    }

    if (!$schema->hasTable('CrisisResponses')) {
        $schema->create('CrisisResponses', static function (Blueprint $table): void {
            $table->increments('id');
            // use unsigned integer to match Crises.id which may be INT in existing DB
            $table->unsignedInteger('crisis_id');
            $table->unsignedInteger('user_id');
            $table->json('content');
            $table->string('file_path', 500)->nullable();
            $table->timestampsTz();

            $table->foreign('crisis_id')->references('id')->on('Crises')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('Users')->onDelete('restrict');
            $table->index('crisis_id');
            $table->index('user_id');
        });
    }
};
