<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Models\Category;
use App\Models\GenerationRun;
use App\Models\Project;
use App\Services\Groq\GroqClient;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GoogleOpportunityService
{
    public function __construct(
        protected ArticleGeneratorService $articleGeneratorService,
        protected GroqClient $groqClient,
        protected SearchConsoleService $searchConsoleService,
        protected GoogleTrendsService $googleTrendsService,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function seedQueriesFor(Project $project): array
    {
        $keywords = collect($project->primary_keywords)->filter()->values();

        if ($keywords->isEmpty()) {
            $keywords = collect([$project->niche]);
        }

        return $keywords
            ->flatMap(function (string $keyword) use ($project): array {
                return array_filter([
                    $keyword,
                    $project->target_location ? "{$keyword} {$project->target_location}" : null,
                ]);
            })
            ->prepend($project->target_location ? "{$project->niche} {$project->target_location}" : $project->niche)
            ->filter()
            ->unique(fn (string $query): string => Str::lower(trim($query)))
            ->values()
            ->take(4)
            ->all();
    }

    /**
     * @return array{signals: array<string, mixed>, opportunities: array<int, array<string, mixed>>, created_articles: array<int, array<string, mixed>>, generated_articles: int}
     */
    public function generate(Project $project, ?GenerationRun $run = null): array
    {
        $run = $run
            ? $this->markRunAsRunning($run)
            : $this->startRun($project);

        try {
            $signals = $this->collectSignals($project);
            $opportunities = $this->shouldUseGroq($project)
                ? $this->analyzeWithGroq($project, $signals)
                : $this->fallbackOpportunities($project, $signals);

            if ($opportunities === []) {
                $opportunities = $this->fallbackOpportunities($project, $signals);
            }

            $articles = $this->materializeOpportunities($project, $opportunities);
            $generatedArticles = 0;

            if ($project->auto_generate_content) {
                $articles = $articles->map(function (Article $article) use (&$generatedArticles): Article {
                    $generatedArticles++;

                    return $this->articleGeneratorService->generate($article, force: true);
                });
            }

            $project->forceFill([
                'last_trend_scanned_at' => now(),
            ])->save();

            $payload = [
                'signals' => $signals,
                'opportunities' => $opportunities,
                'created_articles' => $articles->map(fn (Article $article): array => [
                    'id' => $article->id,
                    'title' => $article->title,
                    'slug' => $article->slug,
                    'status' => $article->status,
                ])->all(),
                'generated_articles' => $generatedArticles,
            ];

            $run->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
                'response_payload' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ])->save();

            return $payload;
        } catch (Throwable $exception) {
            $run->forceFill([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }

    /**
     * @return array{signals: array<string, mixed>, diagnostics: array<string, mixed>}
     */
    public function previewSignals(Project $project): array
    {
        $signals = $this->collectSignals($project);

        return [
            'signals' => $signals,
            'diagnostics' => [
                'queries' => data_get($signals, 'queries', []),
                'suggestions_count' => count(data_get($signals, 'suggestions', [])),
                'news_count' => count(data_get($signals, 'news', [])),
                'search_console' => [
                    'configured' => (bool) data_get($signals, 'search_console.configured', false),
                    'property' => data_get($signals, 'search_console.property'),
                    'country' => data_get($signals, 'search_console.country'),
                    'error_message' => data_get($signals, 'search_console.error_message'),
                    'top_queries' => collect(data_get($signals, 'search_console.top_queries', []))->pluck('query')->take(6)->all(),
                    'rising_queries' => collect(data_get($signals, 'search_console.rising_queries', []))->pluck('query')->take(6)->all(),
                ],
                'google_trends' => [
                    'configured' => (bool) data_get($signals, 'google_trends.configured', false),
                    'country' => data_get($signals, 'google_trends.country'),
                    'region' => data_get($signals, 'google_trends.region'),
                    'error_message' => data_get($signals, 'google_trends.error_message'),
                    'top_terms' => collect(data_get($signals, 'google_trends.top_terms', []))->pluck('term')->take(6)->all(),
                    'rising_terms' => collect(data_get($signals, 'google_trends.rising_terms', []))->pluck('term')->take(6)->all(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function collectSignals(Project $project): array
    {
        $queries = $this->seedQueriesFor($project);

        $suggestions = collect($queries)
            ->flatMap(function (string $query): Collection {
                return collect($this->fetchSuggestions($query))->map(fn (string $suggestion): array => [
                    'seed_query' => $query,
                    'value' => $suggestion,
                ]);
            })
            ->unique(fn (array $signal): string => Str::lower((string) $signal['value']))
            ->values()
            ->take(16)
            ->all();

        $news = collect($queries)
            ->flatMap(function (string $query): Collection {
                return collect($this->fetchNews($query))->map(fn (array $item): array => [
                    ...$item,
                    'seed_query' => $query,
                ]);
            })
            ->unique(fn (array $signal): string => Str::lower((string) $signal['title']))
            ->values()
            ->take(12)
            ->all();

        return [
            'queries' => $queries,
            'suggestions' => $suggestions,
            'news' => $news,
            'search_console' => $this->searchConsoleService->fetchSignals($project),
            'google_trends' => $this->googleTrendsService->fetchSignals($project),
        ];
    }

    /**
     * @param  array<string, mixed>  $signals
     * @return array<int, array<string, mixed>>
     */
    protected function analyzeWithGroq(Project $project, array $signals): array
    {
        try {
            $response = $this->groqClient->chat([
                [
                    'role' => 'system',
                    'content' => 'Voce e um estrategista de conteudo local que transforma sinais do Google em oportunidades editoriais. Responda apenas JSON valido, sem markdown.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'project' => [
                            'name' => $project->name,
                            'niche' => $project->niche,
                            'target_location' => $project->target_location,
                            'description' => $project->description,
                            'primary_keywords' => $project->primary_keywords,
                        ],
                        'signals' => $signals,
                        'response_shape' => [
                            'opportunities' => [
                                [
                                    'title' => 'string',
                                    'focus_keyword' => 'string',
                                    'long_tail_keywords' => ['string'],
                                    'rationale' => 'string',
                                    'search_intent' => 'informational|commercial|transactional',
                                    'trend_type' => 'breaking|seasonal|recurring',
                                    'content_angle' => 'string',
                                    'recommended_category' => 'string',
                                    'seed_query' => 'string',
                                ],
                            ],
                        ],
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                ],
            ], temperature: 0.35);

            if (blank($response)) {
                return [];
            }

            $decoded = $this->decodeJson($response);

            return $this->normalizeOpportunities($project, data_get($decoded, 'opportunities', []), $signals);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $signals
     * @return array<int, array<string, mixed>>
     */
    protected function fallbackOpportunities(Project $project, array $signals): array
    {
        $phrases = collect(data_get($signals, 'suggestions', []))
            ->pluck('value')
            ->merge($this->searchConsoleQueries($signals))
            ->merge($this->googleTrendsQueries($signals))
            ->merge(
                collect(data_get($signals, 'news', []))->map(function (array $item): string {
                    return Str::of((string) $item['title'])
                        ->beforeLast(' - ')
                        ->limit(90, '')
                        ->toString();
                }),
            )
            ->filter()
            ->map(fn (string $phrase): string => trim($phrase))
            ->unique(fn (string $phrase): string => Str::lower($phrase))
            ->values();

        if ($phrases->isEmpty()) {
            $phrases = collect($this->seedQueriesFor($project));
        }

        $queries = collect(data_get($signals, 'queries', []));
        $fallbackCategory = $project->target_location ?: Str::title($project->niche);
        $locationContext = $project->target_location ? " em {$project->target_location}" : '';

        return $phrases
            ->take(4)
            ->values()
            ->map(function (string $phrase, int $index) use ($project, $queries, $fallbackCategory, $locationContext, $signals): array {
                $focusKeyword = Str::of($phrase)->squish()->toString();
                $title = Str::of($focusKeyword)->headline()->toString().": como aproveitar essa demanda{$locationContext}";
                $seedQuery = $queries->get($index % max(1, $queries->count()), $project->niche);

                return [
                    'title' => $title,
                    'focus_keyword' => $focusKeyword,
                    'long_tail_keywords' => array_values(array_unique(array_filter([
                        $project->target_location ? "{$focusKeyword} {$project->target_location}" : null,
                        "como {$focusKeyword}",
                        "{$project->niche} {$focusKeyword}",
                    ]))),
                    'rationale' => "Tema recorrente nos sinais do Google para {$project->niche}{$locationContext}, com potencial para virar conteudo de captura e conversa comercial.",
                    'search_intent' => $this->detectSearchIntent($focusKeyword),
                    'trend_type' => 'recurring',
                    'content_angle' => "Explique por que {$focusKeyword} ganhou atencao agora, o que mudou para o publico e como agir com contexto pratico de {$project->niche}{$locationContext}.",
                    'recommended_category' => $fallbackCategory,
                    'seed_query' => $seedQuery,
                    'opportunity_score' => $this->opportunityScore($focusKeyword, $signals, $project),
                    'source_mix' => $this->sourceMix($focusKeyword, $signals),
                ];
            })
            ->sortByDesc('opportunity_score')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $opportunities
     * @param  array<string, mixed>  $signals
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeOpportunities(Project $project, array $opportunities, array $signals): array
    {
        $fallbackLocation = $project->target_location ?: Str::title($project->niche);

        return collect($opportunities)
            ->filter(fn (mixed $item): bool => filled(data_get($item, 'title')) || filled(data_get($item, 'focus_keyword')))
            ->values()
            ->take(4)
            ->map(function (array $item) use ($project, $fallbackLocation): array {
                $focusKeyword = (string) data_get($item, 'focus_keyword', data_get($item, 'title'));
                $title = (string) data_get($item, 'title', Str::headline($focusKeyword));

                return [
                    'title' => $title,
                    'focus_keyword' => $focusKeyword,
                    'long_tail_keywords' => collect(data_get($item, 'long_tail_keywords', []))
                        ->filter()
                        ->map(fn (string $keyword): string => trim($keyword))
                        ->unique()
                        ->values()
                        ->take(5)
                        ->all(),
                    'rationale' => (string) data_get($item, 'rationale', "Tema conectado ao nicho {$project->niche}."),
                    'search_intent' => (string) data_get($item, 'search_intent', $this->detectSearchIntent($focusKeyword)),
                    'trend_type' => (string) data_get($item, 'trend_type', 'recurring'),
                    'content_angle' => (string) data_get($item, 'content_angle', "Mostre como {$focusKeyword} impacta o mercado e quais proximos passos valem a pena."),
                    'recommended_category' => (string) data_get($item, 'recommended_category', $fallbackLocation),
                    'seed_query' => (string) data_get($item, 'seed_query', $project->niche),
                    'opportunity_score' => (int) data_get($item, 'opportunity_score', $this->opportunityScore($focusKeyword, $signals, $project)),
                    'source_mix' => data_get($item, 'source_mix', $this->sourceMix($focusKeyword, $signals)),
                ];
            })
            ->sortByDesc('opportunity_score')
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $opportunities
     * @return Collection<int, Article>
     */
    protected function materializeOpportunities(Project $project, array $opportunities): Collection
    {
        $scheduleStart = $this->nextScheduleStart($project);

        return collect($opportunities)
            ->values()
            ->map(function (array $opportunity, int $index) use ($project, $scheduleStart): Article {
                $category = $this->syncCategory($project, $opportunity);
                $slug = Str::slug((string) $opportunity['title']);
                $article = Article::query()->firstOrNew([
                    'project_id' => $project->id,
                    'slug' => $slug,
                ]);

                $existingSourcePayload = is_array($article->source_payload) ? $article->source_payload : [];
                $article->fill([
                    'category_id' => $category?->id,
                    'title' => (string) $opportunity['title'],
                    'focus_keyword' => (string) $opportunity['focus_keyword'],
                    'long_tail_keywords' => $opportunity['long_tail_keywords'],
                    'status' => $article->exists && $article->status !== 'idea' ? $article->status : 'idea',
                    'seo_title' => $article->seo_title ?: (string) $opportunity['title'],
                    'meta_description' => $article->meta_description ?: Str::limit((string) $opportunity['rationale'], 160, ''),
                    'excerpt' => $article->excerpt ?: (string) $opportunity['content_angle'],
                    'scheduled_for' => $article->scheduled_for ?: $scheduleStart->copy()->addHours($index * 24),
                    'generation_status' => 'pending',
                    'source_payload' => [
                        ...$existingSourcePayload,
                        'discovery' => [
                            'type' => 'google_signals',
                            'trend_type' => $opportunity['trend_type'],
                            'search_intent' => $opportunity['search_intent'],
                            'seed_query' => $opportunity['seed_query'],
                            'rationale' => $opportunity['rationale'],
                            'content_angle' => $opportunity['content_angle'],
                            'opportunity_score' => $opportunity['opportunity_score'] ?? null,
                            'source_mix' => $opportunity['source_mix'] ?? [],
                        ],
                    ],
                ]);

                if (! $article->exists || blank($article->content)) {
                    $article->content = null;
                    $article->word_count = 0;
                    $article->seo_score = null;
                    $article->keyword_density = null;
                    $article->published_at = null;
                }

                $article->save();

                return $article->refresh();
            });
    }

    /**
     * @param  array<string, mixed>  $opportunity
     */
    protected function syncCategory(Project $project, array $opportunity): ?Category
    {
        $name = (string) data_get($opportunity, 'recommended_category');

        if (blank($name)) {
            return null;
        }

        return Category::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'slug' => Str::slug($name),
            ],
            [
                'name' => $name,
                'description' => "Categoria alimentada pelo Radar Google para {$project->name}.",
                'seo_title' => "{$name} | {$project->name}",
                'seo_description' => Str::limit((string) data_get($opportunity, 'rationale'), 160, ''),
            ],
        );
    }

    /**
     * @return array<int, string>
     */
    protected function fetchSuggestions(string $query): array
    {
        try {
            $response = $this->request()
                ->acceptJson()
                ->get('https://suggestqueries.google.com/complete/search', [
                    'client' => 'firefox',
                    'hl' => 'pt-BR',
                    'q' => $query,
                ]);

            $response->throw();

            return collect(data_get($response->json(), '1', []))
                ->filter()
                ->map(fn (string $suggestion): string => trim($suggestion))
                ->values()
                ->take(6)
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, array{title: string, published_at: string|null}>
     */
    protected function fetchNews(string $query): array
    {
        try {
            $response = $this->request()->get('https://news.google.com/rss/search', [
                'q' => $query,
                'hl' => 'pt-BR',
                'gl' => 'BR',
                'ceid' => 'BR:pt-419',
            ]);

            $response->throw();

            $xml = simplexml_load_string($response->body());

            if ($xml === false) {
                return [];
            }

            return collect($xml->channel?->item ?? [])
                ->take(6)
                ->map(fn (mixed $item): array => [
                    'title' => trim((string) $item->title),
                    'published_at' => filled((string) $item->pubDate)
                        ? Carbon::parse((string) $item->pubDate)->toAtomString()
                        : null,
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    protected function request(): PendingRequest
    {
        return Http::timeout(20)
            ->retry(1, 300)
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (compatible; BlogIA Radar/1.0)',
            ]);
    }

    /**
     * @param  array<string, mixed>  $signals
     * @return array<int, string>
     */
    protected function searchConsoleQueries(array $signals): array
    {
        return collect([
            ...data_get($signals, 'search_console.top_queries', []),
            ...data_get($signals, 'search_console.rising_queries', []),
            ...data_get($signals, 'search_console.low_ctr_queries', []),
        ])->pluck('query')->filter()->unique()->values()->all();
    }

    /**
     * @param  array<string, mixed>  $signals
     * @return array<int, string>
     */
    protected function googleTrendsQueries(array $signals): array
    {
        return collect([
            ...data_get($signals, 'google_trends.top_terms', []),
            ...data_get($signals, 'google_trends.rising_terms', []),
        ])->pluck('term')->filter()->unique()->values()->all();
    }

    /**
     * @param  array<string, mixed>  $signals
     * @return array<int, string>
     */
    protected function sourceMix(string $focusKeyword, array $signals): array
    {
        $normalizedKeyword = Str::of($focusKeyword)->lower()->ascii()->toString();
        $sources = [];

        if (collect(data_get($signals, 'suggestions', []))->contains(fn (array $item): bool => Str::contains(Str::of((string) $item['value'])->lower()->ascii()->toString(), $normalizedKeyword))) {
            $sources[] = 'google_suggest';
        }

        if (collect(data_get($signals, 'news', []))->contains(fn (array $item): bool => Str::contains(Str::of((string) $item['title'])->lower()->ascii()->toString(), $normalizedKeyword))) {
            $sources[] = 'google_news';
        }

        if (collect(data_get($signals, 'search_console.top_queries', []))->contains(fn (array $item): bool => Str::contains(Str::of((string) $item['query'])->lower()->ascii()->toString(), $normalizedKeyword))) {
            $sources[] = 'search_console_top';
        }

        if (collect(data_get($signals, 'search_console.rising_queries', []))->contains(fn (array $item): bool => Str::contains(Str::of((string) $item['query'])->lower()->ascii()->toString(), $normalizedKeyword))) {
            $sources[] = 'search_console_rising';
        }

        if (collect(data_get($signals, 'search_console.low_ctr_queries', []))->contains(fn (array $item): bool => Str::contains(Str::of((string) $item['query'])->lower()->ascii()->toString(), $normalizedKeyword))) {
            $sources[] = 'search_console_low_ctr';
        }

        if (collect(data_get($signals, 'google_trends.top_terms', []))->contains(fn (array $item): bool => Str::contains(Str::of((string) $item['term'])->lower()->ascii()->toString(), $normalizedKeyword))) {
            $sources[] = 'google_trends_top';
        }

        if (collect(data_get($signals, 'google_trends.rising_terms', []))->contains(fn (array $item): bool => Str::contains(Str::of((string) $item['term'])->lower()->ascii()->toString(), $normalizedKeyword))) {
            $sources[] = 'google_trends_rising';
        }

        return array_values(array_unique($sources));
    }

    /**
     * @param  array<string, mixed>  $signals
     */
    protected function opportunityScore(string $focusKeyword, array $signals, Project $project): int
    {
        $normalizedKeyword = Str::of($focusKeyword)->lower()->ascii()->toString();
        $score = 40;

        if ($project->target_location && Str::contains($normalizedKeyword, Str::of($project->target_location)->lower()->ascii()->toString())) {
            $score += 10;
        }

        if (collect(data_get($signals, 'search_console.top_queries', []))->contains(fn (array $item): bool => Str::contains($normalizedKeyword, Str::of((string) $item['query'])->lower()->ascii()->toString()) || Str::contains(Str::of((string) $item['query'])->lower()->ascii()->toString(), $normalizedKeyword))) {
            $score += 20;
        }

        if (collect(data_get($signals, 'search_console.rising_queries', []))->contains(fn (array $item): bool => Str::contains($normalizedKeyword, Str::of((string) $item['query'])->lower()->ascii()->toString()) || Str::contains(Str::of((string) $item['query'])->lower()->ascii()->toString(), $normalizedKeyword))) {
            $score += 25;
        }

        if (collect(data_get($signals, 'search_console.low_ctr_queries', []))->contains(fn (array $item): bool => Str::contains($normalizedKeyword, Str::of((string) $item['query'])->lower()->ascii()->toString()) || Str::contains(Str::of((string) $item['query'])->lower()->ascii()->toString(), $normalizedKeyword))) {
            $score += 15;
        }

        if (collect(data_get($signals, 'google_trends.top_terms', []))->contains(fn (array $item): bool => Str::contains($normalizedKeyword, Str::of((string) $item['term'])->lower()->ascii()->toString()) || Str::contains(Str::of((string) $item['term'])->lower()->ascii()->toString(), $normalizedKeyword))) {
            $score += 15;
        }

        if (collect(data_get($signals, 'google_trends.rising_terms', []))->contains(fn (array $item): bool => Str::contains($normalizedKeyword, Str::of((string) $item['term'])->lower()->ascii()->toString()) || Str::contains(Str::of((string) $item['term'])->lower()->ascii()->toString(), $normalizedKeyword))) {
            $score += 20;
        }

        if (collect(data_get($signals, 'news', []))->contains(fn (array $item): bool => Str::contains(Str::of((string) $item['title'])->lower()->ascii()->toString(), $normalizedKeyword))) {
            $score += 10;
        }

        if (collect(data_get($signals, 'suggestions', []))->contains(fn (array $item): bool => Str::contains(Str::of((string) $item['value'])->lower()->ascii()->toString(), $normalizedKeyword))) {
            $score += 10;
        }

        return min(100, $score);
    }

    protected function detectSearchIntent(string $term): string
    {
        $normalized = Str::of($term)->lower()->ascii()->toString();

        if (Str::contains($normalized, ['comprar', 'alugar', 'preco', 'valor', 'investir', 'melhor bairro'])) {
            return 'commercial';
        }

        if (Str::contains($normalized, ['lancamento', 'disponivel', 'reserva', 'consulta'])) {
            return 'transactional';
        }

        return 'informational';
    }

    protected function shouldUseGroq(Project $project): bool
    {
        return $project->ai_provider === 'groq' && $this->groqClient->isConfigured();
    }

    protected function startRun(Project $project): GenerationRun
    {
        return $project->generationRuns()->create([
            'type' => 'trend',
            'provider' => $this->shouldUseGroq($project) ? 'groq' : 'fallback',
            'model' => $this->shouldUseGroq($project) ? $this->groqClient->model() : 'fallback',
            'status' => 'running',
            'started_at' => now(),
            'prompt_payload' => json_encode([
                'project' => $project->only([
                    'name',
                    'niche',
                    'target_location',
                    'target_country',
                    'google_trends_country',
                    'google_trends_region',
                    'search_console_property',
                    'description',
                    'primary_keywords',
                ]),
                'seed_queries' => $this->seedQueriesFor($project),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ]);
    }

    protected function markRunAsRunning(GenerationRun $run): GenerationRun
    {
        $run->forceFill([
            'status' => 'running',
            'started_at' => now(),
            'error_message' => null,
        ])->save();

        return $run;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJson(string $content): array
    {
        $normalized = trim($content);
        $withoutFences = preg_replace('/```(?:json)?\s*|\s*```/i', '', $normalized) ?: $normalized;

        $candidates = array_filter(array_unique([
            $normalized,
            $withoutFences,
            $this->extractJsonObject($normalized),
            $this->extractJsonObject($withoutFences),
        ]));

        foreach ($candidates as $candidate) {
            $decoded = json_decode($candidate, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new \RuntimeException('A resposta da Groq nao retornou JSON valido para o Radar Google.');
    }

    protected function extractJsonObject(string $content): ?string
    {
        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        return substr($content, $start, $end - $start + 1);
    }

    protected function nextScheduleStart(Project $project): CarbonInterface
    {
        $lastScheduledAt = $project->articles()
            ->whereNotNull('scheduled_for')
            ->max('scheduled_for');

        return $lastScheduledAt
            ? Carbon::parse($lastScheduledAt)->addHours(max(12, (int) floor(24 / max(1, $project->posts_per_day))))
            : now()->addHours(2)->startOfHour();
    }
}
