<?php

namespace App\Models;

use Database\Factories\GenerationRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'project_id',
    'article_id',
    'type',
    'provider',
    'model',
    'status',
    'prompt_payload',
    'response_payload',
    'error_message',
    'started_at',
    'completed_at',
])]
class GenerationRun extends Model
{
    /** @use HasFactory<GenerationRunFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Article, $this>
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
