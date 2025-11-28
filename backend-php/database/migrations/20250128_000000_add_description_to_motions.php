<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return static function (Capsule $capsule): void {
    $capsule::schema()->table('Motions', static function ($table) {
        $table->text('description')->nullable()->after('vote_result');
    });
};