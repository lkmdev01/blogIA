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
        if (Schema::hasIndex('projects', ['slug'], 'unique')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasIndex('projects', ['user_id', 'slug'], 'unique')) {
                $table->dropUnique(['user_id', 'slug']);
            }

            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasIndex('projects', ['user_id', 'slug'], 'unique')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasIndex('projects', ['slug'], 'unique')) {
                $table->dropUnique(['slug']);
            }

            $table->unique(['user_id', 'slug']);
        });
    }
};
