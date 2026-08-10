<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->words(2, true));

        return [
            'project_id' => Project::factory(),
            'content_pillar_id' => null,
            'content_cluster_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(10, 999),
            'description' => fake()->sentence(),
            'seo_title' => $name.' | BlogIA',
            'seo_description' => fake()->sentence(12),
        ];
    }
}
