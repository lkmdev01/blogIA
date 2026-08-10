<?php

namespace App\Models;

use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'user_id',
    'name',
    'slug',
    'domain',
    'target_location',
    'search_console_property',
    'target_country',
    'google_trends_country',
    'google_trends_region',
    'niche',
    'description',
    'primary_keywords',
    'writing_tone',
    'average_article_words',
    'posting_frequency',
    'posts_per_day',
    'language',
    'blog_type',
    'ai_provider',
    'generate_images',
    'enable_interlinking',
    'auto_generate_content',
    'auto_publish',
    'generation_batch_size',
    'generation_delay_seconds',
    'article_depth',
    'h2_count',
    'h3_count',
    'include_faq',
    'target_persona',
    'default_cta',
    'last_strategy_generated_at',
    'last_trend_scanned_at',
    'last_search_console_synced_at',
    'last_google_trends_synced_at',
    'last_sitemap_generated_at',
])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (self $project): void {
            if (blank($project->slug)) {
                $project->slug = Str::slug($project->name);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'primary_keywords' => 'array',
            'generate_images' => 'bool',
            'enable_interlinking' => 'bool',
            'auto_generate_content' => 'bool',
            'auto_publish' => 'bool',
            'generation_batch_size' => 'int',
            'generation_delay_seconds' => 'int',
            'h2_count' => 'int',
            'h3_count' => 'int',
            'include_faq' => 'bool',
            'last_strategy_generated_at' => 'datetime',
            'last_trend_scanned_at' => 'datetime',
            'last_search_console_synced_at' => 'datetime',
            'last_google_trends_synced_at' => 'datetime',
            'last_sitemap_generated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ContentPillar, $this>
     */
    public function pillars(): HasMany
    {
        return $this->hasMany(ContentPillar::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<ContentCluster, $this>
     */
    public function clusters(): HasMany
    {
        return $this->hasMany(ContentCluster::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('name');
    }

    /**
     * @return HasMany<Article, $this>
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class)->latest('scheduled_for');
    }

    /**
     * @return HasMany<GenerationRun, $this>
     */
    public function generationRuns(): HasMany
    {
        return $this->hasMany(GenerationRun::class)->latest();
    }
}
