<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $keyword = fake()->words(3, true);
        $title = Str::title($keyword.' para empresas');

        return [
            'project_id' => Project::factory(),
            'content_pillar_id' => null,
            'content_cluster_id' => null,
            'category_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(10, 999),
            'focus_keyword' => $keyword,
            'long_tail_keywords' => [
                $keyword.' b2b',
                'como usar '.$keyword,
                $keyword.' exemplos',
            ],
            'status' => 'draft',
            'is_pillar_page' => false,
            'seo_title' => $title,
            'meta_description' => fake()->sentence(20),
            'excerpt' => fake()->paragraph(),
            'introduction' => fake()->paragraph(),
            'outline' => [
                ['heading' => 'Panorama do tema', 'points' => ['Contexto', 'Oportunidade']],
                ['heading' => 'Boas praticas', 'points' => ['Planejamento', 'Execucao']],
            ],
            'content' => "# {$title}\n\n".fake()->paragraphs(5, true),
            'conclusion' => fake()->paragraph(),
            'cta' => 'Fale com nossa equipe para transformar este plano em resultados reais.',
            'tags' => ['seo', 'conteudo', 'branding'],
            'seo_score' => fake()->numberBetween(70, 96),
            'internal_links_count' => 0,
            'external_links_count' => fake()->numberBetween(0, 2),
            'keyword_density' => fake()->randomFloat(2, 0.8, 2.5),
            'scheduled_for' => now()->addDay(),
            'published_at' => null,
            'generation_status' => 'completed',
            'featured_image_path' => null,
            'featured_image_alt' => null,
            'word_count' => fake()->numberBetween(1200, 2200),
            'source_payload' => ['provider' => 'factory'],
        ];
    }

    public function idea(): static
    {
        return $this->state(fn (): array => [
            'status' => 'idea',
            'content' => null,
            'published_at' => null,
            'scheduled_for' => null,
            'generation_status' => 'pending',
            'word_count' => 0,
            'seo_score' => null,
            'keyword_density' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'status' => 'scheduled',
            'scheduled_for' => now()->addDay(),
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => 'published',
            'scheduled_for' => now()->subHour(),
            'published_at' => now()->subHour(),
        ]);
    }
}
