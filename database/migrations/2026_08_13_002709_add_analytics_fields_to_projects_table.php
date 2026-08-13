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
            $table->string('ga4_measurement_id')->nullable()->after('hero_image_url');
            $table->string('posthog_api_key')->nullable()->after('ga4_measurement_id');
            $table->string('posthog_host')->nullable()->after('posthog_api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'ga4_measurement_id',
                'posthog_api_key',
                'posthog_host',
            ]);
        });
    }
};
