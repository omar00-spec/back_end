<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixAllMigrations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Cette migration va s'assurer que toutes les tables et colonnes essentielles existent
        // Elle va les créer si elles n'existent pas, et ne fera rien si elles existent déjà

        // 1. Créer la table categories si elle n'existe pas
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('age_min')->default(0);
                $table->integer('age_max')->default(99);
                $table->text('description')->nullable();
                $table->timestamps();
            });
            
            // Ajouter une catégorie par défaut (ID 1)
            DB::table('categories')->insert([
                'name' => 'Catégorie par défaut',
                'age_min' => 0,
                'age_max' => 99,
                'description' => 'Catégorie par défaut pour les coachs',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            // S'assurer que la colonne description existe
            if (!Schema::hasColumn('categories', 'description')) {
                Schema::table('categories', function (Blueprint $table) {
                    $table->text('description')->nullable();
                });
            }
        }

        // 2. S'assurer que la table coaches a category_id nullable
        if (Schema::hasTable('coaches') && Schema::hasColumn('coaches', 'category_id')) {
            // Vérifier si la colonne est déjà nullable
            $columnInfo = DB::select("SHOW COLUMNS FROM coaches WHERE Field = 'category_id'")[0];
            if (strpos($columnInfo->Null, 'YES') === false) {
                Schema::table('coaches', function (Blueprint $table) {
                    $table->unsignedBigInteger('category_id')->nullable()->change();
                });
            }
        }

        // 3. S'assurer que les tables qui causent des problèmes existent
        $this->ensureTableExists('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['player', 'parent', 'coach', 'admin'])->default('player');
            $table->rememberToken();
            $table->timestamps();
        });

        $this->ensureTableExists('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * S'assurer qu'une table existe, et la créer avec le schéma donné si ce n'est pas le cas
     */
    private function ensureTableExists($tableName, $schemaCallback)
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, $schemaCallback);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Cette migration ne fait que s'assurer que les tables et colonnes existent
        // On ne fait rien en cas de rollback
    }
} 