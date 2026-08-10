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
        Schema::table('projects', function (Blueprint $table) {
            $table->string('search_console_property')->nullable()->after('target_location');
            $table->string('target_country', 3)->nullable()->after('search_console_property');
            $table->timestamp('last_search_console_synced_at')->nullable()->after('last_trend_scanned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'search_console_property',
                'target_country',
                'last_search_console_synced_at',
            ]);
        });
    }
};
