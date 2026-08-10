<?php

namespace App\Services\Seo;

use App\Jobs\GenerateArticleContent;
use App\Jobs\GenerateGoogleOpportunityContent;
use App\Jobs\GenerateProjectStrategy;
use App\Models\Article;
use App\Models\GenerationRun;
use App\Models\Project;
use App\Services\Groq\GroqClient;
use Illuminate\Database\Eloquent\Builder;

class ProjectContentGenerationService
{
    public function __construct(
        protected ContentPlannerService $contentPlannerService,
        protected ArticleGeneratorService $articleGeneratorService,
        protected GoogleOpportunityService $googleOpportunityService,
        protected GroqClient $groqClient,
    ) {
    }

    /**
     * @return array{strategy: array<string, mixed>, generated_articles: int}
     */
    public function generateStrategyAndConfiguredContent(Project $project): array
    {
        $strategy = $this->contentPlannerService->generateStrategy($project);
        $generatedArticles = $project->auto_generate_content
            ? $this->generatePendingArticles($project)
            : 0;

        return [
            'strategy' => $strategy,
            'generated_articles' => $generatedArticles,
        ];
    }

    public function queueStrategyAndConfiguredContent(Project $project): GenerationRun
    {
        $run = $project->generationRuns()->create([
            'type' => 'strategy',
            'provider' => $this->shouldUseGroq($project) ? 'groq' : 'fallback',
            'model' => $this->shouldUseGroq($project) ? $this->groqClient->model() : 'fallback',
            'status' => 'queued',
            'prompt_payload' => json_encode([
                'project' => $project->only(['name', 'niche', 'description', 'primary_keywords', 'writing_tone', 'language']),
                'automation' => [
                    'batch_size' => $project->generation_batch_size,
                    'delay_seconds' => $project->generation_delay_seconds,
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ]);

        GenerateProjectStrategy::dispatch($project->id, $run->id);

        return $run;
    }

    /**
     * @return array{signals: array<string, mixed>, opportunities: array<int, array<string, mixed>>, created_articles: array<int, array<string, mixed>>, generated_articles: int}
     */
    public function generateGoogleOpportunityContent(Project $project, ?GenerationRun $run = null): array
    {
        return $this->googleOpportunityService->generate($project, $run);
    }

    /**
     * @return array{signals: array<string, mixed>, diagnostics: array<string, mixed>}
     */
    public function previewGoogleOpportunitySignals(Project $project): array
    {
        return $this->googleOpportunityService->previewSignals($project);
    }

    public function queueGoogleOpportunityContent(Project $project): GenerationRun
    {
        $run = $project->generationRuns()->create([
            'type' => 'trend',
            'provider' => $this->shouldUseGroq($project) ? 'groq' : 'fallback',
            'model' => $this->shouldUseGroq($project) ? $this->groqClient->model() : 'fallback',
            'status' => 'queued',
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
                'seed_queries' => $this->googleOpportunityService->seedQueriesFor($project),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ]);

        GenerateGoogleOpportunityContent::dispatch($project->id, $run->id);

        return $run;
    }

    public function queuePendingArticles(Project $project, ?int $limit = null): int
    {
        $queuedArticles = 0;
        $delaySeconds = max(0, $project->generation_delay_seconds);

        $this->pendingArticlesQuery($project, $limit)
            ->get()
            ->each(function (Article $article, int $index) use (&$queuedArticles, $delaySeconds): void {
                $article->forceFill([
                    'generation_status' => 'queued',
                ])->save();

                $run = $article->generationRuns()->create([
                    'project_id' => $article->project_id,
                    'type' => 'article',
                    'provider' => $this->shouldUseGroq($article->project) ? 'groq' : 'fallback',
                    'model' => $this->shouldUseGroq($article->project) ? $this->groqClient->model() : 'fallback',
                    'status' => 'queued',
                    'prompt_payload' => json_encode([
                        'article' => $article->only(['title', 'focus_keyword', 'long_tail_keywords']),
                        'queued_with_delay_seconds' => $delaySeconds * $index,
                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                ]);

                GenerateArticleContent::dispatch($article->id, false, $run->id)
                    ->delay(now()->addSeconds($delaySeconds * $index));

                $queuedArticles++;
            });

        return $queuedArticles;
    }

    public function generatePendingArticles(Project $project, ?int $limit = null): int
    {
        $generatedArticles = 0;

        $this->pendingArticlesQuery($project, $limit)->get()->each(function (Article $article) use (&$generatedArticles): void {
            $this->articleGeneratorService->generate($article);
            $generatedArticles++;
        });

        return $generatedArticles;
    }

    public function queueConfiguredArticles(Project $project): int
    {
        if (! $project->auto_generate_content) {
            return 0;
        }

        return $this->queuePendingArticles($project, max(1, $project->generation_batch_size));
    }

    protected function pendingArticlesQuery(Project $project, ?int $limit = null): Builder
    {
        $query = Article::query()
            ->where('project_id', $project->id)
            ->whereIn('status', ['idea', 'draft'])
            ->where(function (Builder $query): void {
                $query->whereNull('content')
                    ->orWhere('content', '')
                    ->orWhereIn('generation_status', ['pending', 'failed']);
            })
            ->orderBy('scheduled_for')
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query;
    }

    protected function shouldUseGroq(Project $project): bool
    {
        return $project->ai_provider === 'groq' && $this->groqClient->isConfigured();
    }
}
