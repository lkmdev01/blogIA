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
        Schema::table('articles', function (Blueprint $table) {
            $table->unsignedInteger('public_view_count')->default(0)->after('word_count');
            $table->unsignedInteger('cta_click_count')->default(0)->after('public_view_count');
            $table->timestamp('last_viewed_at')->nullable()->after('cta_click_count');
            $table->timestamp('last_cta_clicked_at')->nullable()->after('last_viewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn([
                'public_view_count',
                'cta_click_count',
                'last_viewed_at',
                'last_cta_clicked_at',
            ]);
        });
    }
};
