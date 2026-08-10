<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\GenerationRun;
use App\Services\Seo\ProjectContentGenerationService;
use App\Services\Seo\ContentPlannerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateProjectStrategy implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $projectId,
        public ?int $generationRunId = null,
    ) {
    }

    public function handle(ContentPlannerService $contentPlannerService, ProjectContentGenerationService $projectContentGenerationService): void
    {
        $project = Project::query()->findOrFail($this->projectId);
        $run = $this->generationRunId
            ? GenerationRun::query()->find($this->generationRunId)
            : null;

        $contentPlannerService->generateStrategy($project, $run);
        $projectContentGenerationService->queueConfiguredArticles($project->refresh());
    }
}
