<?php

use App\Models\Article;
use App\Models\Project;
use App\Services\Seo\ProjectContentGenerationService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function stats(): array
    {
        $projectIds = auth()->user()->projects()->pluck('id');

        return [
            'projects' => $projectIds->count(),
            'posts' => Article::query()->whereIn('project_id', $projectIds)->count(),
            'published' => Article::query()->whereIn('project_id', $projectIds)->where('status', 'published')->count(),
            'scheduled' => Article::query()->whereIn('project_id', $projectIds)->where('status', 'scheduled')->count(),
            'ideas' => Article::query()->whereIn('project_id', $projectIds)->where('status', 'idea')->count(),
            'queued' => Article::query()->whereIn('project_id', $projectIds)->where('generation_status', 'queued')->count(),
            'fallback_articles' => Article::query()->whereIn('project_id', $projectIds)->where('source_payload->provider', 'fallback')->count(),
            'pending_content' => Article::query()
                ->whereIn('project_id', $projectIds)
                ->whereIn('status', ['idea', 'draft'])
                ->where(function ($query): void {
                    $query->whereNull('content')
                        ->orWhere('content', '')
                        ->orWhere('generation_status', '!=', 'completed');
                })
                ->count(),
            'keywords' => Project::query()->whereIn('id', $projectIds)->get()->flatMap->primary_keywords->filter()->unique()->count(),
            'clusters' => auth()->user()->projects()->withCount('clusters')->get()->sum('clusters_count'),
        ];
    }

    #[Computed]
    public function projects()
    {
        return auth()->user()
            ->projects()
            ->withCount([
                'articles',
                'articles as published_articles_count' => fn ($query) => $query->where('status', 'published'),
                'articles as scheduled_articles_count' => fn ($query) => $query->where('status', 'scheduled'),
                'clusters',
            ])
            ->latest()
            ->get();
    }

    #[Computed]
    public function recentArticles()
    {
        return Article::query()
            ->whereIn('project_id', auth()->user()->projects()->select('id'))
            ->with('project')
            ->latest()
            ->take(6)
            ->get();
    }

    public function generateStrategy(int $projectId, ProjectContentGenerationService $projectContentGenerationService): void
    {
        $project = auth()->user()->projects()->findOrFail($projectId);

        $projectContentGenerationService->queueStrategyAndConfiguredContent($project);

        unset($this->stats, $this->projects, $this->recentArticles);

        Flux::toast(
            variant: 'success',
            text: 'Geracao enviada para a fila. Acompanhe o status nos cards.',
        );
    }
};
