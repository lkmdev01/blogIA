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
        Schema::create('content_clusters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('content_pillar_id')->nullable()->index();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('focus_keyword');
            $table->json('long_tail_keywords')->nullable();
            $table->string('status')->default('planned');
            $table->unsignedSmallInteger('article_goal')->default(3);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['project_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_clusters');
    }
};
