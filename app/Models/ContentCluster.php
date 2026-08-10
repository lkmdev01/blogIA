<?php

namespace App\Models;

use Database\Factories\ContentClusterFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'project_id',
    'content_pillar_id',
    'title',
    'slug',
    'description',
    'focus_keyword',
    'long_tail_keywords',
    'status',
    'article_goal',
    'sort_order',
])]
class ContentCluster extends Model
{
    /** @use HasFactory<ContentClusterFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $cluster): void {
            if (blank($cluster->slug)) {
                $cluster->slug = Str::slug($cluster->title);
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'long_tail_keywords' => 'array',
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
     * @return BelongsTo<ContentPillar, $this>
     */
    public function pillar(): BelongsTo
    {
        return $this->belongsTo(ContentPillar::class, 'content_pillar_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
