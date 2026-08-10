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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_pillar_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('content_cluster_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('focus_keyword');
            $table->json('long_tail_keywords')->nullable();
            $table->string('status')->default('idea');
            $table->boolean('is_pillar_page')->default(false);
            $table->string('seo_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('excerpt')->nullable();
            $table->text('introduction')->nullable();
            $table->json('outline')->nullable();
            $table->longText('content')->nullable();
            $table->text('conclusion')->nullable();
            $table->text('cta')->nullable();
            $table->json('tags')->nullable();
            $table->unsignedTinyInteger('seo_score')->nullable();
            $table->unsignedSmallInteger('internal_links_count')->default(0);
            $table->unsignedSmallInteger('external_links_count')->default(0);
            $table->decimal('keyword_density', 5, 2)->nullable();
            $table->timestamp('scheduled_for')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('generation_status')->default('pending');
            $table->string('featured_image_path')->nullable();
            $table->string('featured_image_alt')->nullable();
            $table->unsignedInteger('word_count')->default(0);
            $table->json('source_payload')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
