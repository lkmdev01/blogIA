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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('domain')->nullable();
            $table->string('niche');
            $table->text('description')->nullable();
            $table->json('primary_keywords');
            $table->string('writing_tone')->default('consultivo');
            $table->unsignedInteger('average_article_words')->default(1800);
            $table->string('posting_frequency')->default('daily');
            $table->unsignedTinyInteger('posts_per_day')->default(1);
            $table->string('language')->default('pt-BR');
            $table->string('blog_type')->default('authority');
            $table->boolean('generate_images')->default(false);
            $table->boolean('enable_interlinking')->default(true);
            $table->boolean('auto_generate_content')->default(true);
            $table->boolean('auto_publish')->default(false);
            $table->timestamp('last_strategy_generated_at')->nullable();
            $table->timestamp('last_sitemap_generated_at')->nullable();
            $table->timestamps();

            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
