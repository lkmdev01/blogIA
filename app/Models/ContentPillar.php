<?php

namespace App\Models;

use Database\Factories\ContentPillarFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'project_id',
    'title',
    'slug',
    'description',
    'primary_keyword',
    'target_intent',
    'seo_notes',
    'sort_order',
    'article_goal',
])]
class ContentPillar extends Model
{
    /** @use HasFactory<ContentPillarFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $pillar): void {
            if (blank($pillar->slug)) {
                $pillar->slug = Str::slug($pillar->title);
            }
        });
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<ContentCluster, $this>
     */
    public function clusters(): HasMany
    {
        return $this->hasMany(ContentCluster::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
