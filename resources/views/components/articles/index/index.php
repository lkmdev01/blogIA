<?php

use App\Models\Article;
use App\Services\Seo\ArticleGeneratorService;
use App\Services\Seo\InternalLinkingService;
use App\Services\Seo\ProjectContentGenerationService;
use App\Services\Seo\SitemapService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $search = '';
    public string $status = '';
    public string $projectId = '';

    #[Computed]
    public function projects()
    {
        return auth()->user()->projects()->orderBy('name')->get();
    }

    #[Computed]
    public function articles()
    {
        return Article::query()
            ->whereIn('project_id', auth()->user()->projects()->select('id'))
            ->with('project', 'category', 'cluster')
            ->when($this->projectId !== '', fn ($query) => $query->where('project_id', $this->projectId))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query->where('title', 'like', "%{$this->search}%")
                        ->orWhere('focus_keyword', 'like', "%{$this->search}%");
                });
            })
            ->latest('scheduled_for')
            ->take(60)
            ->get();
    }

    public function generate(int $articleId, ArticleGeneratorService $articleGeneratorService): void
    {
        $article = $this->findOwnedArticle($articleId);

        $articleGeneratorService->generate($article, force: true);

        unset($this->articles);

        Flux::toast(variant: 'success', text: 'Artigo gerado/regenerado.');
    }

    public function generatePending(ProjectContentGenerationService $projectContentGenerationService): void
    {
        $projects = auth()->user()
            ->projects()
            ->when($this->projectId !== '', fn ($query) => $query->where('id', $this->projectId))
            ->get();

        $queuedArticles = $projects->sum(
            fn ($project): int => $projectContentGenerationService->queuePendingArticles($project, max(1, $project->generation_batch_size)),
        );

        unset($this->articles);

        Flux::toast(
            variant: 'success',
            text: $queuedArticles > 0
                ? "{$queuedArticles} artigo(s) enviados para a fila."
                : 'Nao ha artigos pendentes para gerar.',
        );
    }

    public function publish(int $articleId, InternalLinkingService $internalLinkingService, SitemapService $sitemapService): void
    {
        $article = $this->findOwnedArticle($articleId);

        $article->forceFill([
            'status' => 'published',
            'published_at' => now(),
            'scheduled_for' => $article->scheduled_for ?: now(),
        ])->save();

        $internalLinkingService->refreshArticleLinks($article->refresh()->loadMissing('project'));
        $sitemapService->store($article->project);

        unset($this->articles);

        Flux::toast(variant: 'success', text: 'Artigo publicado e sitemap atualizado.');
    }

    protected function findOwnedArticle(int $articleId): Article
    {
        return Article::query()
            ->whereIn('project_id', auth()->user()->projects()->select('id'))
            ->findOrFail($articleId);
    }
};
