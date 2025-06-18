<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->integer('age_min');
                $table->integer('age_max');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->enum('role', ['parent', 'player', 'coach']);
                $table->timestamps();
            });
        } else {
            // Only modify users table if it exists but doesn't have these columns
            if (!Schema::hasColumn('users', 'role')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->enum('role', ['player', 'parent', 'coach', 'admin'])->nullable();
                });
            }
        }

        if (!Schema::hasTable('coaches')) {
            Schema::create('coaches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('diploma')->nullable();
                $table->string('image')->nullable();
                $table->foreignId('category_id')->nullable()->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('players')) {
            Schema::create('players', function (Blueprint $table) {
                $table->id();
                $table->string('firstname');
                $table->string('lastname');
                $table->date('birth_date');
                $table->string('photo')->nullable();
                $table->string('team')->nullable();
                $table->foreignId('category_id')->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('schedules')) {
            Schema::create('schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained()->onDelete('cascade');
                $table->string('day');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('activity');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('registrations')) {
            Schema::create('registrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('player_id')->constrained()->onDelete('cascade');
                $table->string('parent_name');
                $table->string('parent_email');
                $table->json('documents')->nullable();
                $table->string('payment_method')->nullable();
                $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
                $table->enum('type', ['photo', 'video']);
                $table->string('title');
                $table->string('file_path');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('content');
                $table->string('image')->nullable();
                $table->timestamp('date')->useCurrent();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('matches_models')) {
            Schema::create('matches_models', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained()->onDelete('cascade');
                $table->date('date');
                $table->string('opponent');
                $table->string('location');
                $table->string('result')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('contacts')) {
            Schema::create('contacts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->text('message');
                $table->timestamp('date')->useCurrent();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('matches_models');
        Schema::dropIfExists('news');
        Schema::dropIfExists('media');
        Schema::dropIfExists('registrations');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('players');
        Schema::dropIfExists('coaches');
        Schema::dropIfExists('categories');
    }
};
