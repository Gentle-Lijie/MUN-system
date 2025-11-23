<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return static function (Capsule $capsule): void {
    $schema = $capsule->schema();

    if (!$schema->hasTable('Users')) {
        $schema->create('Users', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->enum('role', ['admin', 'dais', 'delegate', 'observer']);
            $table->string('organization')->nullable();
            $table->string('phone', 20)->nullable();
            $table->dateTime('last_login')->nullable();
            $table->string('session_token', 255)->nullable()->unique();
            $table->text('permissions')->default('[]');
            $table->timestampsTz();
        });
    }

    if (!$schema->hasTable('Committees')) {
        $schema->create('Committees', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->string('venue')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['preparation', 'in_session', 'paused', 'closed'])->default('preparation');
            $table->unsignedInteger('capacity')->default(40);
            $table->json('agenda_order')->nullable();
            $table->json('dais_json')->nullable();
            $table->json('time_config')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestampsTz();
        });
    }

    if (!$schema->hasTable('CommitteeSessions')) {
        $schema->create('CommitteeSessions', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('committee_id');
            $table->string('topic');
            $table->string('chair')->nullable();
            $table->dateTime('start_time')->nullable();
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->timestampsTz();
            $table->foreign('committee_id')->references('id')->on('Committees')->onDelete('cascade');
        });
    }

    if (!$schema->hasTable('Delegates')) {
        $schema->create('Delegates', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('committee_id');
            $table->string('country');
            $table->boolean('veto_allowed')->default(false);
            $table->timestampsTz();
            $table->foreign('user_id')->references('id')->on('Users')->onDelete('cascade');
            $table->foreign('committee_id')->references('id')->on('Committees')->onDelete('cascade');
            $table->unique(['user_id', 'committee_id'], 'uq_delegate_user_committee');
        });
    }
};
