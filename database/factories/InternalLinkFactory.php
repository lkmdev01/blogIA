<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\InternalLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InternalLink>
 */
class InternalLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'article_id' => Article::factory(),
            'linked_article_id' => Article::factory(),
            'anchor_text' => fake()->words(4, true),
            'context' => fake()->randomElement(['related', 'pillar', 'cluster']),
        ];
    }
}
