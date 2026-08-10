<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'project_id',
    'content_pillar_id',
    'content_cluster_id',
    'name',
    'slug',
    'description',
    'seo_title',
    'seo_description',
])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            if (blank($category->slug)) {
                $category->slug = Str::slug($category->name);
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
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
