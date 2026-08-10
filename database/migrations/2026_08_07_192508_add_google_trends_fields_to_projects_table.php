<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('google_trends_country', 2)->nullable()->after('target_country');
            $table->string('google_trends_region')->nullable()->after('google_trends_country');
            $table->timestamp('last_google_trends_synced_at')->nullable()->after('last_search_console_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'google_trends_country',
                'google_trends_region',
                'last_google_trends_synced_at',
            ]);
        });
    }
};
