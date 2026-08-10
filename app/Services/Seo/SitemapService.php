<?php

namespace App\Services\Seo;

use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class SitemapService
{
    public function store(Project $project): string
    {
        $xml = $this->generate($project);

        Storage::disk('public')->put($this->path($project), $xml);

        $project->forceFill([
            'last_sitemap_generated_at' => now(),
        ])->save();

        return $xml;
    }

    public function generate(Project $project): string
    {
        $urls = $this->urls($project);

        $items = $urls->map(function (array $url): string {
            $lastmod = e($url['lastmod']);
            $loc = e($url['loc']);
            $changefreq = e($url['changefreq']);
            $priority = e($url['priority']);

            return <<<XML
    <url>
        <loc>{$loc}</loc>
        <lastmod>{$lastmod}</lastmod>
        <changefreq>{$changefreq}</changefreq>
        <priority>{$priority}</priority>
    </url>
XML;
        })->implode("\n");

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$items}
</urlset>
XML;
    }

    public function path(Project $project): string
    {
        return "sitemaps/{$project->slug}.xml";
    }

    public function read(Project $project): ?string
    {
        if (! Storage::disk('public')->exists($this->path($project))) {
            return null;
        }

        return Storage::disk('public')->get($this->path($project));
    }

    /**
     * @return Collection<int, array<string, string>>
     */
    protected function urls(Project $project): Collection
    {
        $project->loadMissing('categories', 'articles');

        $urls = collect([
            [
                'loc' => route('blogs.index', ['project' => $project->slug]),
                'lastmod' => $project->updated_at->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
        ]);

        $categoryUrls = $project->categories->map(fn ($category): array => [
            'loc' => route('blogs.category', ['project' => $project->slug, 'category' => $category->slug]),
            'lastmod' => $category->updated_at->toAtomString(),
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ]);

        $articleUrls = $project->articles
            ->where('status', 'published')
            ->map(fn ($article): array => [
                'loc' => route('blogs.article', ['project' => $project->slug, 'article' => $article->slug]),
                'lastmod' => optional($article->published_at ?: $article->updated_at)?->toAtomString() ?: now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => $article->is_pillar_page ? '0.9' : '0.8',
            ]);

        return $urls->merge($categoryUrls)->merge($articleUrls);
    }
}
