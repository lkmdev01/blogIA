<?php

use App\Jobs\GenerateProjectStrategy;
use App\Jobs\PublishScheduledArticles;
use App\Jobs\RefreshProjectSitemap;
use App\Models\Article;
use App\Models\Project;
use App\Services\Seo\ProjectContentGenerationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new PublishScheduledArticles())->everyMinute();
Schedule::job(new RefreshProjectSitemap())->daily();

Schedule::call(function (): void {
    Project::query()
        ->where('auto_generate_content', true)
        ->get()
        ->each(function (Project $project): void {
            $ideaCount = Article::query()
                ->where('project_id', $project->id)
                ->where('status', 'idea')
                ->count();

            if ($ideaCount < max(3, $project->posts_per_day * 3)) {
                GenerateProjectStrategy::dispatch($project->id);

                return;
            }

            app(ProjectContentGenerationService::class)->queuePendingArticles(
                $project,
                max(1, $project->generation_batch_size),
            );
        });
})->hourly();
