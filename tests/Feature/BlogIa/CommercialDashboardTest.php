<?php

use App\Models\Article;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('commercial dashboard surfaces public performance and editorial health', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    Article::factory()->for($project)->published()->create([
        'title' => 'Artigo lider',
        'public_view_count' => 22,
        'cta_click_count' => 4,
        'featured_image_path' => '/storage/article.jpg',
        'cta' => 'Quero um diagnostico.',
        'internal_links_count' => 3,
        'seo_score' => 88,
    ]);

    Article::factory()->for($project)->published()->create([
        'title' => 'Artigo com gargalo',
        'public_view_count' => 3,
        'cta_click_count' => 0,
        'featured_image_path' => null,
        'cta' => null,
        'internal_links_count' => 0,
        'seo_score' => 62,
        'excerpt' => '',
    ]);

    $this->actingAs($user);

    $this->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('Performance comercial')
        ->assertSee('Saude editorial')
        ->assertSee('Analytics e compartilhamento')
        ->assertSee('GA4 Measurement ID')
        ->assertSee('PostHog Project Key')
        ->assertSee('Leituras publicas')
        ->assertSee('Cliques em CTA')
        ->assertSee('Artigo lider')
        ->assertSee('Sem imagem')
        ->assertSee('Sem CTA')
        ->assertSee('Sem links internos')
        ->assertSee('SEO baixo');
});
