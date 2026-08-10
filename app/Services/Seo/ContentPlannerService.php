<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Models\Category;
use App\Models\ContentCluster;
use App\Models\ContentPillar;
use App\Models\GenerationRun;
use App\Models\Project;
use App\Services\Groq\GroqClient;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ContentPlannerService
{
    public function __construct(
        protected GroqClient $groqClient,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function generateStrategy(Project $project, ?GenerationRun $run = null): array
    {
        $run = $run
            ? $this->markRunAsRunning($run)
            : $this->startRun($project);

        try {
            $strategy = $this->shouldUseGroq($project)
                ? $this->generateWithGroq($project)
                : $this->fallbackStrategy($project);

            DB::transaction(function () use ($project, $strategy): void {
                $this->persistStrategy($project, $strategy);
            });

            $project->forceFill([
                'last_strategy_generated_at' => now(),
            ])->save();

            $run->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
                'response_payload' => json_encode($strategy, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ])->save();

            return $strategy;
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
     * @return array<string, mixed>
     */
    protected function generateWithGroq(Project $project): array
    {
        $prompt = [
            'project' => [
                'name' => $project->name,
                'niche' => $project->niche,
                'description' => $project->description,
                'primary_keywords' => $project->primary_keywords,
                'writing_tone' => $project->writing_tone,
                'language' => $project->language,
                'blog_type' => $project->blog_type,
                'posts_per_day' => $project->posts_per_day,
            ],
            'response_shape' => [
                'pillars' => [
                    [
                        'title' => 'string',
                        'description' => 'string',
                        'primary_keyword' => 'string',
                        'target_intent' => 'string',
                        'seo_notes' => 'string',
                        'clusters' => [
                            [
                                'title' => 'string',
                                'description' => 'string',
                                'focus_keyword' => 'string',
                                'long_tail_keywords' => ['string'],
                                'category' => ['name' => 'string', 'description' => 'string'],
                                'articles' => [
                                    [
                                        'title' => 'string',
                                        'focus_keyword' => 'string',
                                        'long_tail_keywords' => ['string'],
                                        'is_pillar_page' => 'boolean',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = $this->groqClient->chat([
                [
                    'role' => 'system',
                    'content' => 'Voce e um estrategista de SEO para blogs B2B. Responda apenas JSON valido, sem markdown.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($prompt, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                ],
            ], temperature: 0.3);

            if (blank($response)) {
                return $this->fallbackStrategy($project);
            }

            $decoded = $this->decodeJson($response);
            $strategy = $this->normalizeStrategy($project, $decoded);

            return filled(data_get($strategy, 'pillars'))
                ? $strategy
                : $this->fallbackStrategy($project);
        } catch (Throwable) {
            return $this->fallbackStrategy($project);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function fallbackStrategy(Project $project): array
    {
        $keywords = collect($project->primary_keywords)->filter()->values();

        if ($keywords->isEmpty()) {
            $keywords = collect([$project->niche]);
        }

        $pillars = $keywords->take(3)->values()->map(function (string $keyword, int $index): array {
            $pillarTitle = Str::title($keyword);

            return [
                'title' => $pillarTitle,
                'description' => "Pilar SEO para capturar buscas relacionadas a {$keyword} e consolidar autoridade no assunto.",
                'primary_keyword' => $keyword,
                'target_intent' => 'educational',
                'seo_notes' => 'Combine guias, comparativos e conteudos orientados a decisao.',
                'clusters' => [
                    [
                        'title' => "{$pillarTitle} na pratica",
                        'description' => "Cluster para mostrar implementacao, operacao e resultados ligados a {$keyword}.",
                        'focus_keyword' => "{$keyword} na pratica",
                        'long_tail_keywords' => [
                            "{$keyword} exemplos",
                            "como aplicar {$keyword}",
                            "{$keyword} para marketing",
                        ],
                        'category' => [
                            'name' => $pillarTitle,
                            'description' => "Categoria central do pilar {$pillarTitle}.",
                        ],
                        'articles' => [
                            [
                                'title' => "O guia definitivo de {$pillarTitle} para marcas que querem escalar",
                                'focus_keyword' => $keyword,
                                'long_tail_keywords' => [
                                    "{$keyword} para empresas",
                                    "{$keyword} b2b",
                                    "guia de {$keyword}",
                                ],
                                'is_pillar_page' => true,
                            ],
                            [
                                'title' => "Como aplicar {$pillarTitle} no funil de conteudo da sua empresa",
                                'focus_keyword' => "como aplicar {$keyword}",
                                'long_tail_keywords' => [
                                    "{$keyword} no funil",
                                    "{$keyword} para branding",
                                    "{$keyword} para leads",
                                ],
                                'is_pillar_page' => false,
                            ],
                            [
                                'title' => "{$pillarTitle}: erros comuns e como acelerar resultados em SEO",
                                'focus_keyword' => "{$keyword} em seo",
                                'long_tail_keywords' => [
                                    "erros de {$keyword}",
                                    "{$keyword} seo",
                                    "{$keyword} estrategia",
                                ],
                                'is_pillar_page' => false,
                            ],
                        ],
                    ],
                    [
                        'title' => "{$pillarTitle} para crescer no Google",
                        'description' => "Cluster para conectar {$keyword} com arquitetura, interlinkagem e frequencia editorial.",
                        'focus_keyword' => "{$keyword} para seo",
                        'long_tail_keywords' => [
                            "{$keyword} para ranquear",
                            "{$keyword} no google",
                            "{$keyword} e clusters",
                        ],
                        'category' => [
                            'name' => "{$pillarTitle} SEO",
                            'description' => 'Categoria com foco em SEO, clusters e performance organica.',
                        ],
                        'articles' => [
                            [
                                'title' => "{$pillarTitle} para SEO: como montar clusters de conteudo",
                                'focus_keyword' => "{$keyword} clusters",
                                'long_tail_keywords' => [
                                    "{$keyword} clusters",
                                    "{$keyword} pauta",
                                    "{$keyword} autoridade",
                                ],
                                'is_pillar_page' => false,
                            ],
                            [
                                'title' => "{$pillarTitle} e interlinkagem: a estrutura que aumenta autoridade topical",
                                'focus_keyword' => "{$keyword} interlinkagem",
                                'long_tail_keywords' => [
                                    "{$keyword} links internos",
                                    "{$keyword} pillar page",
                                    "{$keyword} seo tecnico",
                                ],
                                'is_pillar_page' => false,
                            ],
                        ],
                    ],
                ],
                'sort_order' => $index + 1,
            ];
        })->all();

        return [
            'pillars' => $pillars,
        ];
    }

    /**
     * @param  array<string, mixed>  $strategy
     */
    protected function persistStrategy(Project $project, array $strategy): void
    {
        $scheduleStart = $this->nextScheduleStart($project);
        $articleCounter = 0;

        foreach (data_get($strategy, 'pillars', []) as $pillarIndex => $pillarData) {
            $pillar = ContentPillar::query()->updateOrCreate(
                [
                    'project_id' => $project->id,
                    'slug' => Str::slug((string) data_get($pillarData, 'title')),
                ],
                [
                    'title' => (string) data_get($pillarData, 'title'),
                    'description' => data_get($pillarData, 'description'),
                    'primary_keyword' => (string) data_get($pillarData, 'primary_keyword'),
                    'target_intent' => data_get($pillarData, 'target_intent'),
                    'seo_notes' => data_get($pillarData, 'seo_notes'),
                    'sort_order' => $pillarIndex + 1,
                    'article_goal' => count(data_get($pillarData, 'clusters', [])),
                ],
            );

            foreach (data_get($pillarData, 'clusters', []) as $clusterIndex => $clusterData) {
                $cluster = ContentCluster::query()->updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'slug' => Str::slug((string) data_get($clusterData, 'title')),
                    ],
                    [
                        'content_pillar_id' => $pillar->id,
                        'title' => (string) data_get($clusterData, 'title'),
                        'description' => data_get($clusterData, 'description'),
                        'focus_keyword' => (string) data_get($clusterData, 'focus_keyword'),
                        'long_tail_keywords' => data_get($clusterData, 'long_tail_keywords', []),
                        'status' => 'planned',
                        'article_goal' => count(data_get($clusterData, 'articles', [])),
                        'sort_order' => $clusterIndex + 1,
                    ],
                );

                $categoryData = data_get($clusterData, 'category', []);

                $category = Category::query()->updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'slug' => Str::slug((string) data_get($categoryData, 'name', data_get($clusterData, 'title'))),
                    ],
                    [
                        'content_pillar_id' => $pillar->id,
                        'content_cluster_id' => $cluster->id,
                        'name' => (string) data_get($categoryData, 'name', data_get($clusterData, 'title')),
                        'description' => data_get($categoryData, 'description', data_get($clusterData, 'description')),
                        'seo_title' => (string) data_get($categoryData, 'name', data_get($clusterData, 'title')).' | '.$project->name,
                        'seo_description' => data_get($categoryData, 'description', data_get($clusterData, 'description')),
                    ],
                );

                foreach (data_get($clusterData, 'articles', []) as $articleData) {
                    $slug = Str::slug((string) data_get($articleData, 'title'));
                    $article = Article::query()->firstOrNew([
                        'project_id' => $project->id,
                        'slug' => $slug,
                    ]);

                    $article->fill([
                        'content_pillar_id' => $pillar->id,
                        'content_cluster_id' => $cluster->id,
                        'category_id' => $category->id,
                        'title' => (string) data_get($articleData, 'title'),
                        'focus_keyword' => (string) data_get($articleData, 'focus_keyword'),
                        'long_tail_keywords' => data_get($articleData, 'long_tail_keywords', []),
                        'status' => $article->exists && $article->status !== 'idea' ? $article->status : 'idea',
                        'is_pillar_page' => (bool) data_get($articleData, 'is_pillar_page', false),
                        'seo_title' => (string) data_get($articleData, 'title'),
                        'meta_description' => $article->meta_description ?: 'Conteudo orientado a SEO sobre '.data_get($articleData, 'focus_keyword').'.',
                        'scheduled_for' => $article->scheduled_for ?: $scheduleStart->copy()->addHours((int) floor($articleCounter / max(1, $project->posts_per_day)) * 24),
                        'generation_status' => $article->generation_status ?: 'pending',
                    ]);

                    $article->save();
                    $articleCounter++;
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function normalizeStrategy(Project $project, array $payload): array
    {
        $pillars = collect(data_get($payload, 'pillars', []))
            ->filter(fn (mixed $pillar): bool => filled(data_get($pillar, 'title')))
            ->values()
            ->map(function (array $pillar, int $index) use ($project): array {
                return [
                    'title' => (string) data_get($pillar, 'title'),
                    'description' => (string) data_get($pillar, 'description', "Pilar de autoridade para {$project->niche}."),
                    'primary_keyword' => (string) data_get($pillar, 'primary_keyword', data_get($pillar, 'title')),
                    'target_intent' => (string) data_get($pillar, 'target_intent', 'educational'),
                    'seo_notes' => (string) data_get($pillar, 'seo_notes', 'Conecte conteudo pilar, cluster e artigos de apoio.'),
                    'sort_order' => $index + 1,
                    'clusters' => collect(data_get($pillar, 'clusters', []))
                        ->filter(fn (mixed $cluster): bool => filled(data_get($cluster, 'title')))
                        ->values()
                        ->map(function (array $cluster): array {
                            return [
                                'title' => (string) data_get($cluster, 'title'),
                                'description' => (string) data_get($cluster, 'description', data_get($cluster, 'title')),
                                'focus_keyword' => (string) data_get($cluster, 'focus_keyword', data_get($cluster, 'title')),
                                'long_tail_keywords' => collect(data_get($cluster, 'long_tail_keywords', []))->filter()->values()->take(5)->all(),
                                'category' => [
                                    'name' => (string) data_get($cluster, 'category.name', data_get($cluster, 'title')),
                                    'description' => (string) data_get($cluster, 'category.description', data_get($cluster, 'description', data_get($cluster, 'title'))),
                                ],
                                'articles' => collect(data_get($cluster, 'articles', []))
                                    ->filter(fn (mixed $article): bool => filled(data_get($article, 'title')))
                                    ->values()
                                    ->map(fn (array $article): array => [
                                        'title' => (string) data_get($article, 'title'),
                                        'focus_keyword' => (string) data_get($article, 'focus_keyword', data_get($article, 'title')),
                                        'long_tail_keywords' => collect(data_get($article, 'long_tail_keywords', []))->filter()->values()->take(4)->all(),
                                        'is_pillar_page' => (bool) data_get($article, 'is_pillar_page', false),
                                    ])
                                    ->all(),
                            ];
                        })
                        ->all(),
                ];
            })
            ->all();

        return [
            'pillars' => $pillars,
        ];
    }

    protected function startRun(Project $project): GenerationRun
    {
        return $project->generationRuns()->create([
            'type' => 'strategy',
            'provider' => $this->shouldUseGroq($project) ? 'groq' : 'fallback',
            'model' => $this->shouldUseGroq($project) ? $this->groqClient->model() : 'fallback',
            'status' => 'running',
            'started_at' => now(),
            'prompt_payload' => json_encode([
                'project' => $project->only(['name', 'niche', 'description', 'primary_keywords', 'writing_tone', 'language']),
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

    protected function shouldUseGroq(Project $project): bool
    {
        return $project->ai_provider === 'groq' && $this->groqClient->isConfigured();
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

        throw new \RuntimeException('A resposta da Groq nao retornou JSON valido para a estrategia.');
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
