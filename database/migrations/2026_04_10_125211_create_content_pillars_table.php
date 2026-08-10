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
        Schema::create('content_pillars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('primary_keyword');
            $table->string('target_intent')->nullable();
            $table->text('seo_notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('article_goal')->default(3);
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_pillars');
    }
};
