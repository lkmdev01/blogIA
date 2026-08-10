<?php

namespace Database\Factories;

use App\Models\GenerationRun;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GenerationRun>
 */
class GenerationRunFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'article_id' => null,
            'type' => fake()->randomElement(['strategy', 'article', 'sitemap']),
            'provider' => fake()->randomElement(['groq', 'fallback']),
            'model' => fake()->randomElement(['openai/gpt-oss-20b', 'llama-3.3-70b-versatile']),
            'status' => fake()->randomElement(['pending', 'completed', 'failed']),
            'prompt_payload' => '{"kind":"factory"}',
            'response_payload' => '{"ok":true}',
            'error_message' => null,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(9),
        ];
    }
}
