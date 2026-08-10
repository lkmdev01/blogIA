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
            $table->string('ai_provider')->default('groq')->after('blog_type');
            $table->unsignedTinyInteger('generation_batch_size')->default(3)->after('auto_publish');
            $table->unsignedSmallInteger('generation_delay_seconds')->default(20)->after('generation_batch_size');
            $table->string('article_depth')->default('standard')->after('generation_delay_seconds');
            $table->unsignedTinyInteger('h2_count')->default(6)->after('article_depth');
            $table->unsignedTinyInteger('h3_count')->default(2)->after('h2_count');
            $table->boolean('include_faq')->default(true)->after('h3_count');
            $table->string('target_persona')->nullable()->after('include_faq');
            $table->text('default_cta')->nullable()->after('target_persona');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'ai_provider',
                'generation_batch_size',
                'generation_delay_seconds',
                'article_depth',
                'h2_count',
                'h3_count',
                'include_faq',
                'target_persona',
                'default_cta',
            ]);
        });
    }
};
