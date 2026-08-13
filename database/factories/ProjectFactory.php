<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' Blog';

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(10, 999),
            'domain' => fake()->optional()->domainName(),
            'target_location' => fake()->optional()->randomElement([
                'Guaruja',
                'Santos',
                'Sao Paulo',
            ]),
            'search_console_property' => fake()->optional()->randomElement([
                'sc-domain:imobiliariaguaruja.com.br',
                'https://www.blogia.test/',
            ]),
            'target_country' => 'BRA',
            'google_trends_country' => 'BR',
            'google_trends_region' => fake()->optional()->randomElement([
                'Sao Paulo',
                'Rio de Janeiro',
                'Parana',
            ]),
            'niche' => fake()->randomElement([
                'IA para empresas',
                'marketing de conteudo',
                'automacao comercial',
            ]),
            'description' => fake()->paragraph(),
            'hero_description' => fake()->sentence(12),
            'hero_image_url' => fake()->imageUrl(1600, 900, 'business', true),
            'is_primary_public' => false,
            'ga4_measurement_id' => null,
            'posthog_api_key' => null,
            'posthog_host' => null,
            'primary_keywords' => [
                fake()->words(2, true),
                fake()->words(3, true),
                fake()->words(2, true),
            ],
            'writing_tone' => fake()->randomElement(['consultivo', 'tecnico', 'didatico']),
            'average_article_words' => fake()->numberBetween(1200, 2500),
            'posting_frequency' => fake()->randomElement(['daily', 'weekdays', 'three-times-week']),
            'posts_per_day' => fake()->numberBetween(1, 3),
            'language' => 'pt-BR',
            'blog_type' => fake()->randomElement(['institutional', 'niche', 'authority']),
            'ai_provider' => 'fallback',
            'generate_images' => false,
            'enable_interlinking' => true,
            'auto_generate_content' => true,
            'auto_publish' => false,
            'generation_batch_size' => 3,
            'generation_delay_seconds' => 20,
            'article_depth' => 'standard',
            'h2_count' => 6,
            'h3_count' => 2,
            'include_faq' => true,
            'target_persona' => 'gestores e empreendedores',
            'default_cta' => 'Fale com nossa equipe para transformar SEO em crescimento previsivel.',
            'last_strategy_generated_at' => now()->subDay(),
            'last_trend_scanned_at' => now()->subHours(12),
            'last_search_console_synced_at' => now()->subHours(12),
            'last_google_trends_synced_at' => now()->subHours(12),
            'last_sitemap_generated_at' => now()->subHours(6),
        ];
    }
}
