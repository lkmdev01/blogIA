<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\InternalLink;
use App\Models\Project;
use App\Models\User;
use App\Services\Seo\SitemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('home page renders the primary blog when a project exists', function () {
    $project = Project::factory()->for(User::factory())->create([
        'slug' => 'blog-principal',
        'name' => 'Blog Principal',
        'description' => 'Conteudos para consolidar autoridade organica.',
        'hero_description' => 'Uma operacao editorial unica para concentrar marca, SEO e demanda.',
        'ga4_measurement_id' => 'G-HOME123456',
    ]);

    Category::factory()->for($project)->create([
        'slug' => 'estrategia',
        'name' => 'Estrategia',
    ]);

    Article::factory()->for($project)->published()->create([
        'title' => 'Biblioteca principal',
        'slug' => 'biblioteca-principal',
        'excerpt' => 'Leitura central do blog.',
    ]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Blog Principal')
        ->assertSee('Uma operacao editorial unica para concentrar marca, SEO e demanda.')
        ->assertSee('window.blogIAAnalytics', false)
        ->assertSee('G-HOME123456', false)
        ->assertDontSee('Welcome');
});

test('home page falls back to welcome when no project exists', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Welcome - BlogIA')
        ->assertDontSee('window.blogIAAnalytics');
});

test('published blog pages and sitemap are available publicly', function () {
    Storage::fake('public');

    $project = Project::factory()->for(User::factory())->create([
        'slug' => 'blogia-public',
        'name' => 'BlogIA Public',
        'domain' => 'blogia-public.test',
        'description' => 'Conteudos para SEO com IA.',
        'hero_description' => 'Conteudos sob medida para liderancas que querem aplicar IA com clareza comercial.',
        'hero_image_url' => 'https://cdn.example.com/hero-blogia.jpg',
        'ga4_measurement_id' => 'G-TEST123456',
        'posthog_api_key' => 'phc_test_key_123',
        'posthog_host' => 'https://us.i.posthog.com',
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
        'meta_description' => null,
        'excerpt' => 'Analise pratica de SEO com IA para marcas em crescimento.',
        'featured_image_path' => '/storage/posts/seo-ia.jpg',
        'featured_image_alt' => 'Painel de estrategia com inteligencia artificial',
        'published_at' => now()->subDays(3),
        'content' => "# SEO com IA para marcas\n\n> Ideia central para destacar.\n\n## Estrategia SEO\n\nTexto com **negrito importante**.\n\n- **Pilar:** organize a pauta.\n- **Cluster:** conecte artigos.\n\n## Automacao pratica\n\nMostre como transformar teoria em execucao.\n\n## Proximo passo comercial\n\nContinue otimizando.",
    ]);

    $publishedRelatedArticle = Article::factory()->for($project)->for($category)->published()->create([
        'slug' => 'automacao-com-ia-para-atendimento',
        'title' => 'Automacao com IA para atendimento',
        'focus_keyword' => 'automacao com ia',
        'excerpt' => 'Atendimento com IA para operacoes comerciais e produtividade.',
        'public_view_count' => 14,
        'published_at' => now()->subDays(1),
    ]);

    $draftRelatedArticle = Article::factory()->for($project)->for($category)->create([
        'slug' => 'rascunho-interno',
        'title' => 'Rascunho interno',
        'focus_keyword' => 'rascunho interno',
        'status' => 'draft',
        'published_at' => null,
    ]);

    InternalLink::factory()->create([
        'article_id' => $article->id,
        'linked_article_id' => $publishedRelatedArticle->id,
    ]);

    InternalLink::factory()->create([
        'article_id' => $article->id,
        'linked_article_id' => $draftRelatedArticle->id,
    ]);

    foreach (range(1, 10) as $index) {
        Article::factory()->for($project)->for($category)->published()->create([
            'slug' => "biblioteca-extra-{$index}",
            'title' => "Biblioteca extra {$index}",
            'focus_keyword' => "biblioteca {$index}",
            'published_at' => now()->subDays(20 + $index),
        ]);
    }

    app(SitemapService::class)->store($project);

    $this->get(route('blogs.index', $project->slug))
        ->assertOk()
        ->assertSee('BlogIA Public')
        ->assertSee('Conteudos sob medida para liderancas que querem aplicar IA com clareza comercial.')
        ->assertSee('https://cdn.example.com/hero-blogia.jpg')
        ->assertSee('window.blogIAAnalytics', false)
        ->assertSee('G-TEST123456', false)
        ->assertSee('phc_test_key_123', false)
        ->assertSee('SEO com IA para marcas')
        ->assertDontSee('Pilares SEO')
        ->assertSee('Todas as categorias')
        ->assertSee('Ver mais artigos');

    $this->get(route('blogs.index', ['project' => $project->slug, 'search' => 'atendimento']))
        ->assertOk()
        ->assertSee('Resultados para')
        ->assertSee('Automacao com IA para atendimento')
        ->assertDontSee('SEO com IA para marcas');

    $this->get(route('blogs.index', ['project' => $project->slug, 'search' => 'atendimento', 'category' => $category->slug]))
        ->assertOk()
        ->assertSee('Categoria')
        ->assertSee('SEO com IA')
        ->assertSee('<mark class="rounded bg-emerald-100 px-1 text-zinc-950">Atendimento</mark>', false);

    $this->get(route('blogs.index', ['project' => $project->slug, 'flow' => 16]))
        ->assertOk()
        ->assertSee('Biblioteca extra 10');

    $this->get(route('blogs.category', [$project->slug, $category->slug]))
        ->assertOk()
        ->assertSee('SEO com IA')
        ->assertSee('Destaque editorial')
        ->assertSee('Ordenar biblioteca');

    $this->get(route('blogs.category', ['project' => $project->slug, 'category' => $category->slug, 'order' => 'popular']))
        ->assertOk()
        ->assertSee('Mais lidos')
        ->assertSee('14 leituras');

    $articleUrl = route('blogs.article', [$project->slug, $article->slug]);
    $socialImageUrl = route('blogs.article.og-image', [
        'project' => $project->slug,
        'article' => $article->slug,
        'v' => $article->updated_at?->timestamp,
    ]);

    $this->get(route('blogs.article', [$project->slug, $article->slug]))
        ->assertOk()
        ->assertSee('SEO com IA para marcas')
        ->assertSee('Analise pratica de SEO com IA para marcas em crescimento.')
        ->assertSee('<strong>negrito importante</strong>', false)
        ->assertSee('<blockquote>', false)
        ->assertSee('<li><strong>Pilar:</strong> organize a pauta.</li>', false)
        ->assertSee('Neste artigo')
        ->assertSee('href="#estrategia-seo"', false)
        ->assertSee('href="#automacao-pratica"', false)
        ->assertSee('href="#proximo-passo-comercial"', false)
        ->assertSee('data-article-toc-link', false)
        ->assertSee('aria-current="true"', false)
        ->assertSee('<h2 id="estrategia-seo" class="scroll-mt-32">Estrategia SEO</h2>', false)
        ->assertSee('<h2 id="automacao-pratica" class="scroll-mt-32">Automacao pratica</h2>', false)
        ->assertSee('<h2 id="proximo-passo-comercial" class="scroll-mt-32">Proximo passo comercial</h2>', false)
        ->assertSee('const observer = new IntersectionObserver', false)
        ->assertSee('"@context"', false)
        ->assertSee('<meta name="description" content="Analise pratica de SEO com IA para marcas em crescimento.">', false)
        ->assertSee('<link rel="canonical" href="'.$articleUrl.'">', false)
        ->assertSee('<meta property="og:type" content="article">', false)
        ->assertSee('<meta property="og:image" content="'.$socialImageUrl.'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->assertSee('<meta property="og:image:width" content="1200">', false)
        ->assertSee('<meta property="og:image:height" content="630">', false)
        ->assertSee('<meta name="twitter:image" content="'.$socialImageUrl.'">', false)
        ->assertSee('"image": "'.$socialImageUrl.'"', false)
        ->assertSee('blog_cta_click', false)
        ->assertSee('window.blogIAAnalytics', false)
        ->assertSee('Proximo passo')
        ->assertSee('Falar com especialistas')
        ->assertSee('Solicitar diagnostico')
        ->assertSee('Diagnostico estrategico')
        ->assertSee('Sobre a operacao')
        ->assertSee('Atualizado em')
        ->assertSee('Explorar mesma categoria')
        ->assertSee('Artigo anterior')
        ->assertSee('Proximo artigo')
        ->assertSee('https://blogia-public.test')
        ->assertSee('Automacao com IA para atendimento')
        ->assertDontSee('Rascunho interno');

    expect($article->fresh()->public_view_count)->toBe(1)
        ->and($article->fresh()->last_viewed_at)->not->toBeNull();

    $this->get(route('blogs.article.cta', [$project->slug, $article->slug]))
        ->assertRedirect('https://blogia-public.test');

    expect($article->fresh()->cta_click_count)->toBe(1)
        ->and($article->fresh()->last_cta_clicked_at)->not->toBeNull();

    $this->get(route('blogs.article.og-image', [$project->slug, $article->slug]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8')
        ->assertSee('SEO com IA para marcas')
        ->assertSee('BlogIA Public');

    $this->get(route('projects.sitemap', $project->slug))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('seo-com-ia-para-marcas');
});
