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
            // Ajouter description si elle n'existe pas
            if (!Schema::hasColumn('media', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            
            // Ajouter les champs Cloudinary
            if (!Schema::hasColumn('media', 'url')) {
                $table->string('url')->nullable()->after('file_path');
            }
            
            if (!Schema::hasColumn('media', 'public_id')) {
                $table->string('public_id')->nullable()->after('url');
            }
            
            if (!Schema::hasColumn('media', 'format')) {
                $table->string('format')->nullable()->after('public_id');
            }
            
            if (!Schema::hasColumn('media', 'size')) {
                $table->bigInteger('size')->nullable()->after('format');
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
            $columns = ['description', 'url', 'public_id', 'format', 'size'];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('media', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}; 