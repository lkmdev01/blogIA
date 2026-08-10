<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\Seo\InternalLinkingService;
use App\Services\Seo\SitemapService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishScheduledArticles implements ShouldQueue
{
    use Queueable;

    public function handle(InternalLinkingService $internalLinkingService, SitemapService $sitemapService): void
    {
        Article::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->get()
            ->groupBy('project_id')
            ->each(function ($articles) use ($internalLinkingService, $sitemapService): void {
                $articles->each(function (Article $article) use ($internalLinkingService): void {
                    $article->forceFill([
                        'status' => 'published',
                        'published_at' => now(),
                    ])->save();

                    $internalLinkingService->refreshArticleLinks($article->refresh()->loadMissing('project'));
                });

                /** @var Article $firstArticle */
                $firstArticle = $articles->first();
                $sitemapService->store($firstArticle->project()->firstOrFail());
            });
    }
}
