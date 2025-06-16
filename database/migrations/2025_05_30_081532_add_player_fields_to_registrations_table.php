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
        Schema::table('registrations', function (Blueprint $table) {
            // Ajouter les champs du joueur directement dans la table registration
            $table->string('player_firstname')->nullable()->after('player_id');
            $table->string('player_lastname')->nullable()->after('player_firstname');
            $table->date('birth_date')->nullable()->after('player_lastname');
            $table->unsignedBigInteger('category_id')->nullable()->after('birth_date');
            
            // Ajout de la clé étrangère pour category_id
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            
            // S'assurer que le champ status existe
            if (!Schema::hasColumn('registrations', 'status')) {
                $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending')->after('payment_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Supprimer la clé étrangère
            $table->dropForeign(['category_id']);
            
            // Supprimer les champs ajoutés
            $table->dropColumn([
                'player_firstname',
                'player_lastname',
                'birth_date',
                'category_id'
            ]);
        });
    }
};
