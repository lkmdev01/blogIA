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
    'hero_description',
    'hero_image_url',
    'ga4_measurement_id',
    'posthog_api_key',
    'posthog_host',
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

    public function isPrimaryPublicProject(): bool
    {
        if (! $this->exists) {
            return false;
        }

        $primaryProjectId = static::query()->oldest('id')->value('id');

        return $primaryProjectId !== null && $this->getKey() === (int) $primaryProjectId;
    }

    /**
     * @param  array<string, scalar|null>  $query
     */
    public function publicIndexUrl(array $query = []): string
    {
        if ($this->isPrimaryPublicProject()) {
            return route('home', array_filter($query, fn ($value) => filled($value)));
        }

        return route('blogs.index', array_filter([
            'project' => $this->slug,
            ...$query,
        ], fn ($value) => filled($value)));
    }

    /**
     * @param  array<string, scalar|null>  $query
     */
    public function publicCategoryUrl(Category|string $category, array $query = []): string
    {
        $categorySlug = $category instanceof Category ? $category->slug : $category;

        if ($this->isPrimaryPublicProject()) {
            return route('blogs.primary.category', array_filter([
                'category' => $categorySlug,
                ...$query,
            ], fn ($value) => filled($value)));
        }

        return route('blogs.category', array_filter([
            'project' => $this->slug,
            'category' => $categorySlug,
            ...$query,
        ], fn ($value) => filled($value)));
    }

    /**
     * @param  array<string, scalar|null>  $query
     */
    public function publicArticleUrl(Article|string $article, array $query = []): string
    {
        $articleSlug = $article instanceof Article ? $article->slug : $article;

        if ($this->isPrimaryPublicProject()) {
            return route('blogs.primary.article', array_filter([
                'article' => $articleSlug,
                ...$query,
            ], fn ($value) => filled($value)));
        }

        return route('blogs.article', array_filter([
            'project' => $this->slug,
            'article' => $articleSlug,
            ...$query,
        ], fn ($value) => filled($value)));
    }

    /**
     * @param  array<string, scalar|null>  $query
     */
    public function publicArticleSocialImageUrl(Article|string $article, array $query = []): string
    {
        $articleSlug = $article instanceof Article ? $article->slug : $article;

        if ($this->isPrimaryPublicProject()) {
            return route('blogs.primary.article.og-image', array_filter([
                'article' => $articleSlug,
                ...$query,
            ], fn ($value) => filled($value)));
        }

        return route('blogs.article.og-image', array_filter([
            'project' => $this->slug,
            'article' => $articleSlug,
            ...$query,
        ], fn ($value) => filled($value)));
    }

    /**
     * @param  array<string, scalar|null>  $query
     */
    public function publicArticleCtaUrl(Article|string $article, array $query = []): string
    {
        $articleSlug = $article instanceof Article ? $article->slug : $article;

        if ($this->isPrimaryPublicProject()) {
            return route('blogs.primary.article.cta', array_filter([
                'article' => $articleSlug,
                ...$query,
            ], fn ($value) => filled($value)));
        }

        return route('blogs.article.cta', array_filter([
            'project' => $this->slug,
            'article' => $articleSlug,
            ...$query,
        ], fn ($value) => filled($value)));
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
