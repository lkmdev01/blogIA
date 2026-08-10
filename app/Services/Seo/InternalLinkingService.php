<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Models\InternalLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InternalLinkingService
{
    /**
     * @return Collection<int, InternalLink>
     */
    public function refreshArticleLinks(Article $article): Collection
    {
        if (! $article->project->enable_interlinking) {
            $article->internalLinks()->delete();
            $article->forceFill(['internal_links_count' => 0])->save();

            return collect();
        }

        $candidates = Article::query()
            ->where('project_id', $article->project_id)
            ->whereKeyNot($article->id)
            ->whereNotNull('content')
            ->get()
            ->map(fn (Article $candidate): array => [
                'article' => $candidate,
                'score' => $this->score($article, $candidate),
            ])
            ->filter(fn (array $candidate): bool => $candidate['score'] > 0)
            ->sortByDesc('score')
            ->take(3)
            ->values();

        $article->internalLinks()->delete();

        foreach ($candidates as $candidate) {
            /** @var Article $linkedArticle */
            $linkedArticle = $candidate['article'];

            $article->internalLinks()->create([
                'linked_article_id' => $linkedArticle->id,
                'anchor_text' => $linkedArticle->focus_keyword,
                'context' => $this->context($article, $linkedArticle),
            ]);
        }

        $links = $article->internalLinks()->with('linkedArticle')->get();

        $article->forceFill([
            'internal_links_count' => $links->count(),
        ])->save();

        return $links;
    }

    protected function score(Article $article, Article $candidate): int
    {
        $score = 0;

        if ($article->content_cluster_id && $article->content_cluster_id === $candidate->content_cluster_id) {
            $score += 4;
        }

        if ($article->content_pillar_id && $article->content_pillar_id === $candidate->content_pillar_id) {
            $score += 3;
        }

        if ($article->category_id && $article->category_id === $candidate->category_id) {
            $score += 2;
        }

        if ($candidate->is_pillar_page) {
            $score += 2;
        }

        $score += $this->sharedTerms($article, $candidate);

        return $score;
    }

    protected function sharedTerms(Article $article, Article $candidate): int
    {
        $articleTerms = collect([
            $article->focus_keyword,
            ...($article->tags ?? []),
            ...($article->long_tail_keywords ?? []),
        ])->map(fn (string $term): string => Str::lower($term))->unique();

        $candidateTerms = collect([
            $candidate->focus_keyword,
            ...($candidate->tags ?? []),
            ...($candidate->long_tail_keywords ?? []),
        ])->map(fn (string $term): string => Str::lower($term))->unique();

        return min(3, $articleTerms->intersect($candidateTerms)->count());
    }

    protected function context(Article $article, Article $candidate): string
    {
        if ($candidate->is_pillar_page || $candidate->content_pillar_id === $article->content_pillar_id) {
            return 'pillar';
        }

        if ($candidate->content_cluster_id === $article->content_cluster_id) {
            return 'cluster';
        }

        return 'related';
    }
}
