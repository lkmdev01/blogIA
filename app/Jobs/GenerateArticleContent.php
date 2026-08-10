<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\GenerationRun;
use App\Services\Seo\ArticleGeneratorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateArticleContent implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $articleId,
        public bool $force = false,
        public ?int $generationRunId = null,
    ) {
    }

    public function handle(ArticleGeneratorService $articleGeneratorService): void
    {
        $article = Article::query()->findOrFail($this->articleId);
        $run = $this->generationRunId
            ? GenerationRun::query()->find($this->generationRunId)
            : null;

        $articleGeneratorService->generate($article, $this->force, $run);
    }
}
