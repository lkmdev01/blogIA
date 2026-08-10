<?php

namespace App\Models;

use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'project_id',
    'content_pillar_id',
    'content_cluster_id',
    'category_id',
    'title',
    'slug',
    'focus_keyword',
    'long_tail_keywords',
    'status',
    'is_pillar_page',
    'seo_title',
    'meta_description',
    'excerpt',
    'introduction',
    'outline',
    'content',
    'conclusion',
    'cta',
    'tags',
    'seo_score',
    'internal_links_count',
    'external_links_count',
    'keyword_density',
    'scheduled_for',
    'published_at',
    'generation_status',
    'featured_image_path',
    'featured_image_alt',
    'word_count',
    'source_payload',
])]
class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $article): void {
            if (blank($article->slug)) {
                $article->slug = Str::slug($article->title);
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
            'outline' => 'array',
            'tags' => 'array',
            'source_payload' => 'array',
            'is_pillar_page' => 'bool',
            'keyword_density' => 'decimal:2',
            'scheduled_for' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    /**
     * @param  Builder<Article>  $query
     * @return Builder<Article>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
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
     * @return BelongsTo<ContentCluster, $this>
     */
    public function cluster(): BelongsTo
    {
        return $this->belongsTo(ContentCluster::class, 'content_cluster_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<InternalLink, $this>
     */
    public function internalLinks(): HasMany
    {
        return $this->hasMany(InternalLink::class);
    }

    /**
     * @return HasMany<GenerationRun, $this>
     */
    public function generationRuns(): HasMany
    {
        return $this->hasMany(GenerationRun::class)->latest();
    }
}
