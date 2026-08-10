<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use App\Services\Seo\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('published blog pages and sitemap are available publicly', function () {
    Storage::fake('public');

    $project = Project::factory()->for(User::factory())->create([
        'slug' => 'blogia-public',
        'name' => 'BlogIA Public',
        'description' => 'Conteudos para SEO com IA.',
        'primary_keywords' => ['seo com ia'],
    ]);

    $category = Category::factory()->for($project)->create([
        'slug' => 'seo-com-ia',
        'name' => 'SEO com IA',
    ]);

    $article = Article::factory()->for($project)->for($category)->published()->create([
        'slug' => 'seo-com-ia-para-marcas',
        'title' => 'SEO com IA para marcas',
        'focus_keyword' => 'seo com ia',
        'content' => "# SEO com IA para marcas\n\n> Ideia central para destacar.\n\n## Estrategia SEO\n\nTexto com **negrito importante**.\n\n- **Pilar:** organize a pauta.\n- **Cluster:** conecte artigos.\n\n### Otimizacao continua\n\nContinue otimizando.",
    ]);

    app(SitemapService::class)->store($project);

    $this->get(route('blogs.index', $project->slug))
        ->assertOk()
        ->assertSee('BlogIA Public')
        ->assertSee('SEO com IA para marcas');

    $this->get(route('blogs.category', [$project->slug, $category->slug]))
        ->assertOk()
        ->assertSee('SEO com IA');

    $this->get(route('blogs.article', [$project->slug, $article->slug]))
        ->assertOk()
        ->assertSee('SEO com IA para marcas')
        ->assertSee('seo com ia')
        ->assertSee('<h2>Estrategia SEO</h2>', false)
        ->assertSee('<strong>negrito importante</strong>', false)
        ->assertSee('<blockquote>', false)
        ->assertSee('<li><strong>Pilar:</strong> organize a pauta.</li>', false)
        ->assertSee('"@context"', false)
        ->assertDontSee('Proximo passo');

    $this->get(route('projects.sitemap', $project->slug))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('seo-com-ia-para-marcas');
});
