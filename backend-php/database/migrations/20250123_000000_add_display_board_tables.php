<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

return static function (Capsule $capsule): void {
    $schema = $capsule->schema();

    // Add status column to Delegates table
    if ($schema->hasTable('Delegates') && !$schema->hasColumn('Delegates', 'status')) {
        $schema->table('Delegates', static function (Blueprint $table): void {
            $table->enum('status', ['present', 'absent'])->nullable()->after('veto_allowed');
        });
    }

    // Create Sessions table if not exists
    if (!$schema->hasTable('Sessions')) {
        $schema->create('Sessions', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('committee_id');
            $table->enum('type', ['main_list', 'moderated', 'unmoderated', 'special', 'other']);
            $table->unsignedInteger('unit_time_seconds')->nullable();
            $table->unsignedInteger('total_time_seconds')->nullable();
            $table->unsignedBigInteger('proposer_id')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->json('vote_result')->nullable();
            $table->unsignedBigInteger('speaker_list_id')->nullable();
            $table->timestampsTz();
            $table->foreign('committee_id')->references('id')->on('Committees')->onDelete('restrict');
            $table->foreign('proposer_id')->references('id')->on('Delegates')->onDelete('restrict');
        });
    }

    // Create SpeakerLists table if not exists
    if (!$schema->hasTable('SpeakerLists')) {
        $schema->create('SpeakerLists', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('committee_id');
            $table->timestampsTz();
            $table->foreign('committee_id')->references('id')->on('Committees')->onDelete('restrict');
        });
    }

    // Create SpeakerListEntries table if not exists
    if (!$schema->hasTable('SpeakerListEntries')) {
        $schema->create('SpeakerListEntries', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('speaker_list_id');
            $table->unsignedBigInteger('delegate_id');
            $table->unsignedInteger('position');
            $table->enum('status', ['waiting', 'speaking', 'removed'])->default('waiting');
            $table->timestampsTz();
            $table->foreign('speaker_list_id')->references('id')->on('SpeakerLists')->onDelete('restrict');
            $table->foreign('delegate_id')->references('id')->on('Delegates')->onDelete('restrict');
            $table->unique(['speaker_list_id', 'position'], 'uq_speaker_list_position');
        });
    }

    // Create Motions table if not exists
    if (!$schema->hasTable('Motions')) {
        $schema->create('Motions', static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('motion_type', 100);
            $table->unsignedBigInteger('proposer_id')->nullable();
            $table->unsignedBigInteger('file_id')->nullable();
            $table->unsignedInteger('unit_time_seconds')->nullable();
            $table->unsignedInteger('total_time_seconds')->nullable();
            $table->unsignedBigInteger('speaker_list_id')->nullable();
            $table->boolean('vote_required')->default(false);
            $table->boolean('veto_applicable')->default(false);
            $table->enum('state', ['passed', 'rejected', 'pending'])->default('pending');
            $table->json('vote_result')->nullable();
            $table->timestampsTz();
            $table->foreign('session_id')->references('id')->on('Sessions')->onDelete('restrict');
            $table->foreign('proposer_id')->references('id')->on('Delegates')->onDelete('restrict');
            $table->foreign('speaker_list_id')->references('id')->on('SpeakerLists')->onDelete('restrict');
        });
    }

};
