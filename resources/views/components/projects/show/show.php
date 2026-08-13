<?php

use App\Models\Article;
use App\Models\Project;
use App\Services\Seo\ArticleGeneratorService;
use App\Services\Seo\ProjectContentGenerationService;
use App\Services\Seo\SitemapService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Project $project;

    public ?array $signalDiagnostics = null;

    public string $ai_provider = 'groq';

    public int $generation_batch_size = 3;

    public int $generation_delay_seconds = 20;

    public string $article_depth = 'standard';

    public int $h2_count = 6;

    public int $h3_count = 2;

    public string $target_location = '';

    public string $search_console_property = '';

    public string $target_country = 'BRA';

    public string $google_trends_country = 'BR';

    public string $google_trends_region = 'Sao Paulo';

    public bool $include_faq = true;

    public string $target_persona = '';

    public string $default_cta = '';

    public string $hero_description = '';

    public string $hero_image_url = '';

    public string $ga4_measurement_id = '';

    public string $posthog_api_key = '';

    public string $posthog_host = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->fillGenerationSettings();
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'posts' => $this->project->articles()->count(),
            'ideas' => $this->project->articles()->where('status', 'idea')->count(),
            'drafts' => $this->project->articles()->where('status', 'draft')->count(),
            'published' => $this->project->articles()->where('status', 'published')->count(),
            'scheduled' => $this->project->articles()->where('status', 'scheduled')->count(),
            'pillars' => $this->project->pillars()->count(),
            'clusters' => $this->project->clusters()->count(),
            'categories' => $this->project->categories()->count(),
            'pending_content' => $this->project->articles()
                ->whereIn('status', ['idea', 'draft'])
                ->where(function ($query): void {
                    $query->whereNull('content')
                        ->orWhere('content', '')
                        ->orWhere('generation_status', '!=', 'completed');
                })
                ->count(),
            'queued' => $this->project->articles()->where('generation_status', 'queued')->count(),
            'running' => $this->project->generationRuns()->where('status', 'running')->count(),
            'completed_generations' => $this->project->generationRuns()->where('status', 'completed')->count(),
            'fallback_articles' => $this->project->articles()->where('source_payload->provider', 'fallback')->count(),
        ];
    }

    #[Computed]
    public function pillars()
    {
        return $this->project->pillars()
            ->with(['clusters.articles'])
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function articles()
    {
        return $this->project->articles()
            ->with('category', 'cluster')
            ->latest('scheduled_for')
            ->take(12)
            ->get();
    }

    #[Computed]
    public function latestTrendSnapshot(): ?array
    {
        $run = $this->project->generationRuns()
            ->where('type', 'trend')
            ->where('status', 'completed')
            ->latest('completed_at')
            ->first();

        if (! $run || blank($run->response_payload)) {
            return null;
        }

        $payload = json_decode($run->response_payload, true);

        if (! is_array($payload)) {
            return null;
        }

        return [
            'completed_at' => $run->completed_at,
            'signal_count' => count(data_get($payload, 'signals.suggestions', []))
                + count(data_get($payload, 'signals.news', []))
                + count(data_get($payload, 'signals.search_console.top_queries', []))
                + count(data_get($payload, 'signals.search_console.rising_queries', []))
                + count(data_get($payload, 'signals.search_console.low_ctr_queries', []))
                + count(data_get($payload, 'signals.google_trends.top_terms', []))
                + count(data_get($payload, 'signals.google_trends.rising_terms', [])),
            'queries' => data_get($payload, 'signals.queries', []),
            'search_console' => data_get($payload, 'signals.search_console', []),
            'google_trends' => data_get($payload, 'signals.google_trends', []),
            'opportunities' => collect(data_get($payload, 'opportunities', []))->take(4)->all(),
            'created_articles' => count(data_get($payload, 'created_articles', [])),
            'generated_articles' => (int) data_get($payload, 'generated_articles', 0),
        ];
    }

    #[Computed]
    public function commercialMetrics(): array
    {
        return [
            'views' => (int) $this->project->articles()->sum('public_view_count'),
            'cta_clicks' => (int) $this->project->articles()->sum('cta_click_count'),
            'articles_with_image' => $this->project->articles()->whereNotNull('featured_image_path')->where('featured_image_path', '!=', '')->count(),
            'articles_with_cta' => $this->project->articles()->whereNotNull('cta')->where('cta', '!=', '')->count(),
            'articles_with_links' => $this->project->articles()->where('internal_links_count', '>', 0)->count(),
            'low_seo_articles' => $this->project->articles()->where('status', 'published')->where(function ($query): void {
                $query->whereNull('seo_score')->orWhere('seo_score', '<', 70);
            })->count(),
        ];
    }

    #[Computed]
    public function topArticles()
    {
        return $this->project->articles()
            ->where('status', 'published')
            ->orderByDesc('public_view_count')
            ->orderByDesc('cta_click_count')
            ->latest('published_at')
            ->take(5)
            ->get();
    }

    #[Computed]
    public function editorialAlerts(): array
    {
        return [
            [
                'label' => 'Sem imagem',
                'count' => $this->project->articles()->where('status', 'published')->where(function ($query): void {
                    $query->whereNull('featured_image_path')->orWhere('featured_image_path', '');
                })->count(),
                'hint' => 'Artigos publicados sem apoio visual para clique e compartilhamento.',
            ],
            [
                'label' => 'Sem CTA',
                'count' => $this->project->articles()->where('status', 'published')->where(function ($query): void {
                    $query->whereNull('cta')->orWhere('cta', '');
                })->count(),
                'hint' => 'Paginas publicas sem proximo passo comercial claro.',
            ],
            [
                'label' => 'Sem links internos',
                'count' => $this->project->articles()->where('status', 'published')->where('internal_links_count', 0)->count(),
                'hint' => 'Conteudos isolados, com pouca retencao e menos distribuicao de autoridade.',
            ],
            [
                'label' => 'SEO abaixo de 70',
                'count' => $this->project->articles()->where('status', 'published')->where(function ($query): void {
                    $query->whereNull('seo_score')->orWhere('seo_score', '<', 70);
                })->count(),
                'hint' => 'Paginas que pedem revisao de estrutura, meta e densidade.',
            ],
            [
                'label' => 'Sem introducao editorial',
                'count' => $this->project->articles()->where('status', 'published')->where(function ($query): void {
                    $query->whereNull('excerpt')->orWhere('excerpt', '');
                })->count(),
                'hint' => 'Artigos que podem estar chegando sem promessa clara para o leitor.',
            ],
        ];
    }

    public function generateStrategy(ProjectContentGenerationService $projectContentGenerationService): void
    {
        $projectContentGenerationService->queueStrategyAndConfiguredContent($this->project);

        unset($this->stats, $this->pillars, $this->articles);

        Flux::toast(
            variant: 'success',
            text: 'Geracao enviada para a fila. A pauta sera criada e os artigos entrarao no lote configurado.',
        );
    }

    public function generateGoogleOpportunities(ProjectContentGenerationService $projectContentGenerationService): void
    {
        $projectContentGenerationService->queueGoogleOpportunityContent($this->project);

        unset($this->stats, $this->articles, $this->latestTrendSnapshot);

        Flux::toast(
            variant: 'success',
            text: 'Radar Google enviado para a fila. Vamos transformar os sinais em novas pautas e artigos.',
        );
    }

    public function runSignalDiagnostics(ProjectContentGenerationService $projectContentGenerationService): void
    {
        $preview = $projectContentGenerationService->previewGoogleOpportunitySignals($this->project);

        $this->signalDiagnostics = data_get($preview, 'diagnostics');
        $this->project = $this->project->refresh();

        Flux::toast(
            variant: 'success',
            text: 'Diagnostico atualizado. Voce ja pode revisar os sinais antes de gerar as pautas.',
        );
    }

    public function generateAllArticles(ProjectContentGenerationService $projectContentGenerationService): void
    {
        $queuedArticles = $projectContentGenerationService->queuePendingArticles(
            $this->project,
            max(1, $this->project->generation_batch_size),
        );

        unset($this->stats, $this->articles);

        Flux::toast(
            variant: 'success',
            text: $queuedArticles > 0
                ? "{$queuedArticles} artigo(s) enviados para a fila."
                : 'Nao ha artigos pendentes para gerar.',
        );
    }

    public function generateNextArticle(ArticleGeneratorService $articleGeneratorService): void
    {
        $article = $this->project->articles()
            ->whereIn('status', ['idea', 'draft'])
            ->oldest('scheduled_for')
            ->first();

        if (! $article instanceof Article) {
            Flux::toast(text: 'Nao ha artigos pendentes. Gere uma nova pauta primeiro.');

            return;
        }

        $articleGeneratorService->generate($article, force: true);

        unset($this->stats, $this->articles);

        Flux::toast(variant: 'success', text: 'Artigo gerado com sucesso.');
    }

    public function refreshSitemap(SitemapService $sitemapService): void
    {
        $sitemapService->store($this->project);

        Flux::toast(variant: 'success', text: 'Sitemap atualizado.');
    }

    public function setAsPrimaryPublicProject(): void
    {
        $this->project->setAsPrimaryPublic();
        $this->project = $this->project->refresh();

        Flux::toast(variant: 'success', text: 'Este projeto agora responde como blog principal publico.');
    }

    public function saveGenerationSettings(): void
    {
        $validated = $this->validate([
            'ai_provider' => ['required', 'string', 'in:groq,fallback'],
            'generation_batch_size' => ['required', 'integer', 'min:1', 'max:20'],
            'generation_delay_seconds' => ['required', 'integer', 'min:0', 'max:3600'],
            'article_depth' => ['required', 'string', 'in:concise,standard,deep'],
            'h2_count' => ['required', 'integer', 'min:3', 'max:12'],
            'h3_count' => ['required', 'integer', 'min:0', 'max:5'],
            'target_location' => ['nullable', 'string', 'max:160'],
            'search_console_property' => ['nullable', 'string', 'max:255'],
            'target_country' => ['nullable', 'string', 'size:3'],
            'google_trends_country' => ['nullable', 'string', 'size:2'],
            'google_trends_region' => ['nullable', 'string', 'max:160'],
            'include_faq' => ['boolean'],
            'target_persona' => ['nullable', 'string', 'max:160'],
            'default_cta' => ['nullable', 'string', 'max:1000'],
            'hero_description' => ['nullable', 'string', 'max:1000'],
            'hero_image_url' => ['nullable', 'url', 'max:2048'],
            'ga4_measurement_id' => ['nullable', 'string', 'max:32'],
            'posthog_api_key' => ['nullable', 'string', 'max:255'],
            'posthog_host' => ['nullable', 'url', 'max:255'],
        ]);

        $this->project->forceFill([
            ...$validated,
            'target_location' => $validated['target_location'] ?: null,
            'search_console_property' => $validated['search_console_property'] ?: null,
            'target_country' => filled($validated['target_country']) ? strtoupper($validated['target_country']) : null,
            'google_trends_country' => filled($validated['google_trends_country']) ? strtoupper($validated['google_trends_country']) : null,
            'google_trends_region' => $validated['google_trends_region'] ?: null,
            'target_persona' => $validated['target_persona'] ?: null,
            'default_cta' => $validated['default_cta'] ?: null,
            'hero_description' => $validated['hero_description'] ?: null,
            'hero_image_url' => $validated['hero_image_url'] ?: null,
            'ga4_measurement_id' => filled($validated['ga4_measurement_id']) ? strtoupper($validated['ga4_measurement_id']) : null,
            'posthog_api_key' => $validated['posthog_api_key'] ?: null,
            'posthog_host' => $validated['posthog_host'] ?: null,
        ])->save();

        $this->project = $this->project->refresh();
        $this->fillGenerationSettings();

        Flux::toast(variant: 'success', text: 'Configuracoes de geracao salvas.');
    }

    protected function fillGenerationSettings(): void
    {
        $this->ai_provider = $this->project->ai_provider;
        $this->generation_batch_size = $this->project->generation_batch_size;
        $this->generation_delay_seconds = $this->project->generation_delay_seconds;
        $this->article_depth = $this->project->article_depth;
        $this->h2_count = $this->project->h2_count;
        $this->h3_count = $this->project->h3_count;
        $this->target_location = $this->project->target_location ?: '';
        $this->search_console_property = $this->project->search_console_property ?: '';
        $this->target_country = $this->project->target_country ?: 'BRA';
        $this->google_trends_country = $this->project->google_trends_country ?: 'BR';
        $this->google_trends_region = $this->project->google_trends_region ?: 'Sao Paulo';
        $this->include_faq = $this->project->include_faq;
        $this->target_persona = $this->project->target_persona ?: '';
        $this->default_cta = $this->project->default_cta ?: '';
        $this->hero_description = $this->project->hero_description ?: '';
        $this->hero_image_url = $this->project->hero_image_url ?: '';
        $this->ga4_measurement_id = $this->project->ga4_measurement_id ?: '';
        $this->posthog_api_key = $this->project->posthog_api_key ?: '';
        $this->posthog_host = $this->project->posthog_host ?: '';
    }
};
