<?php

namespace App\Jobs;

use App\Models\GenerationRun;
use App\Models\Project;
use App\Services\Seo\ProjectContentGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateGoogleOpportunityContent implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $projectId,
        public ?int $generationRunId = null,
    ) {
    }

    public function handle(ProjectContentGenerationService $projectContentGenerationService): void
    {
        $project = Project::query()->findOrFail($this->projectId);
        $run = $this->generationRunId
            ? GenerationRun::query()->find($this->generationRunId)
            : null;

        $projectContentGenerationService->generateGoogleOpportunityContent($project, $run);
    }
}
