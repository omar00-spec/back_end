<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Rendre le champ player_id nullable
            DB::statement('ALTER TABLE registrations MODIFY player_id BIGINT UNSIGNED NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Remettre le champ player_id comme non nullable
            DB::statement('ALTER TABLE registrations MODIFY player_id BIGINT UNSIGNED NOT NULL');
        });
    }
};
