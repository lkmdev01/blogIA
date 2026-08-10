<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\Seo\SitemapService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshProjectSitemap implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?int $projectId = null,
    ) {
    }

    public function handle(SitemapService $sitemapService): void
    {
        $projects = Project::query()
            ->when($this->projectId, fn ($query) => $query->whereKey($this->projectId))
            ->get();

        $projects->each(fn (Project $project): string => $sitemapService->store($project));
    }
}
