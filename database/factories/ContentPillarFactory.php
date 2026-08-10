<?php

namespace Database\Factories;

use App\Models\ContentPillar;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ContentPillar>
 */
class ContentPillarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'project_id' => Project::factory(),
            'title' => Str::title($title),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(10, 999),
            'description' => fake()->paragraph(),
            'primary_keyword' => fake()->words(3, true),
            'target_intent' => fake()->randomElement(['educational', 'commercial', 'comparison']),
            'seo_notes' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 6),
            'article_goal' => fake()->numberBetween(2, 6),
        ];
    }
}
