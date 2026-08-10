<?php

namespace Database\Factories;

use App\Models\ContentCluster;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentCluster>
 */
class ContentClusterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'project_id' => Project::factory(),
            'content_pillar_id' => null,
            'title' => Str::title($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(10, 999),
            'description' => fake()->paragraph(),
            'focus_keyword' => fake()->words(3, true),
            'long_tail_keywords' => [
                fake()->words(4, true),
                fake()->words(5, true),
                fake()->words(4, true),
            ],
            'status' => fake()->randomElement(['planned', 'active']),
            'article_goal' => fake()->numberBetween(2, 5),
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
