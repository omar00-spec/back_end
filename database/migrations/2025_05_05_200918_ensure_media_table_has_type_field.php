<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // Vérifier si la colonne 'type' n'existe pas déjà
            if (!Schema::hasColumn('media', 'type')) {
                $table->string('type')->default('photo')->after('category_id');
            }

            // Vérifier si la colonne 'title' n'existe pas déjà
            if (!Schema::hasColumn('media', 'title')) {
                $table->string('title')->nullable()->after('type');
            }

            // Vérifier si la colonne 'file_path' n'existe pas déjà
            if (!Schema::hasColumn('media', 'file_path')) {
                $table->string('file_path')->after('title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // Supprimer les colonnes si elles existent
            if (Schema::hasColumn('media', 'type')) {
                $table->dropColumn('type');
            }

            if (Schema::hasColumn('media', 'title')) {
                $table->dropColumn('title');
            }

            if (Schema::hasColumn('media', 'file_path')) {
                $table->dropColumn('file_path');
            }
        });
    }
};
