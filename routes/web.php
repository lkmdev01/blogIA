<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Project;
use App\Services\Seo\SitemapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * @return array{
 *     project: Project,
 *     flow: int,
 *     search: string,
 *     selectedCategory: Category|null
 * }
 */
$buildBlogIndexPayload = static function (Request $request, Project $project): array {
    $search = trim((string) $request->string('search'));
    $categorySlug = trim((string) $request->string('category'));
    $flow = max(4, min(40, (int) $request->integer('flow', 8)));
    $selectedCategory = blank($categorySlug)
        ? null
        : Category::query()
            ->where('project_id', $project->id)
            ->where('slug', $categorySlug)
            ->first();

    return [
        'project' => $project->load([
            'categories',
            'articles' => fn ($query) => $query
                ->published()
                ->with('category')
                ->when($selectedCategory, fn ($query) => $query->where('category_id', $selectedCategory->id))
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('excerpt', 'like', "%{$search}%")
                            ->orWhere('meta_description', 'like', "%{$search}%")
                            ->orWhere('focus_keyword', 'like', "%{$search}%");
                    });
                })
                ->latest('published_at'),
        ]),
        'flow' => $flow,
        'search' => $search,
        'selectedCategory' => $selectedCategory,
    ];
};

Route::get('/', function (Request $request) use ($buildBlogIndexPayload) {
    $project = Project::query()->oldest('id')->first();

    if (! $project) {
        return view('welcome');
    }

    return view('blogs.index', $buildBlogIndexPayload($request, $project));
})->name('home');

Route::get('/blogs/{project:slug}', function (Request $request, Project $project) use ($buildBlogIndexPayload) {
    return view('blogs.index', $buildBlogIndexPayload($request, $project));
})->name('blogs.index');

Route::get('/blogs/{project:slug}/categories/{category}', function (Request $request, Project $project, string $category) {
    $categoryModel = Category::query()
        ->where('project_id', $project->id)
        ->where('slug', $category)
        ->firstOrFail();
    $flow = max(4, min(40, (int) $request->integer('flow', 8)));
    $order = (string) $request->string('order', 'recent');

    $articles = Article::query()
        ->where('project_id', $project->id)
        ->where('category_id', $categoryModel->id)
        ->published()
        ->when($order === 'popular', fn ($query) => $query->orderByDesc('public_view_count')->orderByDesc('published_at'))
        ->when($order === 'seo', fn ($query) => $query->orderByDesc('seo_score')->orderByDesc('published_at'))
        ->when(! in_array($order, ['popular', 'seo'], true), fn ($query) => $query->latest('published_at'))
        ->get();

    return view('blogs.category', [
        'project' => $project,
        'category' => $categoryModel,
        'articles' => $articles,
        'featuredArticle' => $articles->first(),
        'flow' => $flow,
        'order' => in_array($order, ['recent', 'popular', 'seo'], true) ? $order : 'recent',
    ]);
})->name('blogs.category');

Route::get('/blogs/{project:slug}/articles/{article}/social-image.svg', function (Project $project, string $article) {
    $articleModel = Article::query()
        ->where('project_id', $project->id)
        ->where('slug', $article)
        ->where('status', 'published')
        ->firstOrFail();

    $escape = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    $wrapText = static function (?string $value, int $maxChars, int $maxLines) use ($escape): string {
        $words = preg_split('/\s+/', trim((string) $value)) ?: [];
        $lines = [];
        $currentLine = '';

        foreach ($words as $word) {
            $candidate = trim($currentLine.' '.$word);

            if (mb_strlen($candidate) <= $maxChars) {
                $currentLine = $candidate;

                continue;
            }

            if ($currentLine !== '') {
                $lines[] = $currentLine;
            }

            $currentLine = $word;

            if (count($lines) === $maxLines - 1) {
                break;
            }
        }

        if ($currentLine !== '' && count($lines) < $maxLines) {
            $lines[] = $currentLine;
        }

        if (count($lines) === $maxLines && count($words) > 0) {
            $lastIndex = count($lines) - 1;
            $lines[$lastIndex] = rtrim(Str::limit($lines[$lastIndex], $maxChars, '...'));
        }

        return collect($lines)
            ->map(fn (string $line, int $index): string => '<tspan x="72" dy="'.($index === 0 ? '0' : '1.25em').'">'.$escape($line).'</tspan>')
            ->implode('');
    };

    $title = $articleModel->title;
    $description = $articleModel->excerpt ?: $articleModel->meta_description ?: $project->description;
    $categoryLabel = $articleModel->category?->name ?: $project->niche;
    $publishedLabel = $articleModel->published_at?->format('d/m/Y') ?: now()->format('d/m/Y');
    $titleLines = $wrapText($title, 26, 3);
    $descriptionLines = $wrapText($description, 42, 3);

    $svg = <<<SVG
<svg width="1200" height="630" viewBox="0 0 1200 630" fill="none" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="bg" x1="72" y1="48" x2="1128" y2="582" gradientUnits="userSpaceOnUse">
            <stop stop-color="#0A0A0A"/>
            <stop offset="1" stop-color="#1C3D14"/>
        </linearGradient>
        <linearGradient id="accent" x1="0" y1="0" x2="1" y2="1">
            <stop stop-color="#6EE7B7"/>
            <stop offset="1" stop-color="#A3E635"/>
        </linearGradient>
    </defs>
    <rect width="1200" height="630" rx="36" fill="url(#bg)"/>
    <circle cx="1020" cy="132" r="220" fill="#6EE7B7" fill-opacity="0.08"/>
    <circle cx="1090" cy="530" r="180" fill="#A3E635" fill-opacity="0.12"/>
    <rect x="72" y="72" width="212" height="44" rx="22" fill="#0F172A" fill-opacity="0.72" stroke="#6EE7B7" stroke-opacity="0.28"/>
    <text x="178" y="100" text-anchor="middle" fill="#6EE7B7" font-family="Arial, sans-serif" font-size="16" font-weight="700" letter-spacing="3">{$escape(mb_strtoupper((string) $categoryLabel))}</text>
    <text x="72" y="180" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="64" font-weight="700">{$titleLines}</text>
    <text x="72" y="364" fill="#D4D4D8" font-family="Arial, sans-serif" font-size="28" font-weight="400">{$descriptionLines}</text>
    <line x1="72" y1="480" x2="1128" y2="480" stroke="#FFFFFF" stroke-opacity="0.12"/>
    <text x="72" y="530" fill="#A1A1AA" font-family="Arial, sans-serif" font-size="18" font-weight="600" letter-spacing="2">{$escape($project->name)}</text>
    <text x="72" y="566" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="34" font-weight="700">{$escape($project->niche)}</text>
    <text x="1128" y="530" text-anchor="end" fill="#A1A1AA" font-family="Arial, sans-serif" font-size="18" font-weight="600">Atualizado para compartilhamento</text>
    <text x="1128" y="566" text-anchor="end" fill="#FFFFFF" font-family="Arial, sans-serif" font-size="26" font-weight="700">{$escape($publishedLabel)}</text>
</svg>
SVG;

    return response($svg, 200, [
        'Content-Type' => 'image/svg+xml; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('blogs.article.og-image');

Route::get('/blogs/{project:slug}/articles/{article}', function (Project $project, string $article) {
    $articleModel = Article::query()
        ->where('project_id', $project->id)
        ->where('slug', $article)
        ->firstOrFail();

    abort_unless($articleModel->status === 'published' || $articleModel->project->user_id === auth()->id(), 404);

    if ($articleModel->status === 'published') {
        Article::withoutTimestamps(function () use ($articleModel): void {
            Article::query()
                ->whereKey($articleModel->id)
                ->update([
                    'public_view_count' => DB::raw('public_view_count + 1'),
                    'last_viewed_at' => now(),
                ]);
        });

        $articleModel->refresh();
    }

    $relatedQuery = Article::query()
        ->where('project_id', $project->id)
        ->where('status', 'published')
        ->whereKeyNot($articleModel->id);

    $previousArticle = Article::query()
        ->where('project_id', $project->id)
        ->where('status', 'published')
        ->whereNotNull('published_at')
        ->where('published_at', '<', $articleModel->published_at)
        ->latest('published_at')
        ->first();

    $nextArticle = Article::query()
        ->where('project_id', $project->id)
        ->where('status', 'published')
        ->whereNotNull('published_at')
        ->where('published_at', '>', $articleModel->published_at)
        ->oldest('published_at')
        ->first();

    $sameCategoryArticles = $articleModel->category_id
        ? (clone $relatedQuery)
            ->where('category_id', $articleModel->category_id)
            ->latest('published_at')
            ->take(3)
            ->get()
        : collect();

    $sameThemeArticles = (clone $relatedQuery)
        ->where(function ($query) use ($articleModel) {
            $query->where('focus_keyword', $articleModel->focus_keyword);

            foreach (array_filter((array) $articleModel->long_tail_keywords) as $keyword) {
                $query->orWhere('focus_keyword', 'like', '%'.$keyword.'%');
            }
        })
        ->latest('published_at')
        ->take(3)
        ->get();

    return view('blogs.article', [
        'project' => $project,
        'article' => $articleModel->load('category', 'internalLinks.linkedArticle'),
        'previousArticle' => $previousArticle,
        'nextArticle' => $nextArticle,
        'sameCategoryArticles' => $sameCategoryArticles,
        'sameThemeArticles' => $sameThemeArticles,
    ]);
})->name('blogs.article');

Route::get('/blogs/{project:slug}/articles/{article}/cta', function (Project $project, string $article) {
    $articleModel = Article::query()
        ->where('project_id', $project->id)
        ->where('slug', $article)
        ->where('status', 'published')
        ->firstOrFail();

    Article::withoutTimestamps(function () use ($articleModel): void {
        Article::query()
            ->whereKey($articleModel->id)
            ->update([
                'cta_click_count' => DB::raw('cta_click_count + 1'),
                'last_cta_clicked_at' => now(),
            ]);
    });

    $destination = blank($project->domain)
        ? route('blogs.article', [$project->slug, $articleModel->slug])
        : (str_starts_with($project->domain, 'http://') || str_starts_with($project->domain, 'https://')
            ? $project->domain
            : 'https://'.$project->domain);

    return redirect()->away($destination);
})->name('blogs.article.cta');

Route::get('/sitemaps/{project:slug}.xml', function (Project $project, SitemapService $sitemapService) {
    $xml = $sitemapService->read($project) ?? $sitemapService->store($project);

    return response($xml, 200, [
        'Content-Type' => 'application/xml; charset=UTF-8',
    ]);
})->name('projects.sitemap');

Route::get('/robots.txt', function () {
    $project = Project::query()->first();
    $sitemap = $project ? route('projects.sitemap', $project) : url('/sitemap.xml');

    return response("User-agent: *\nAllow: /\nSitemap: {$sitemap}\n", 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
    ]);
})->name('robots');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('projects', 'projects.index')->name('projects.index');

    Route::get('projects/{project:slug}', function (Project $project) {
        abort_unless($project->user_id === auth()->id(), 403);

        return view('projects.show', [
            'project' => $project,
        ]);
    })->name('projects.show');

    Route::get('articles', function () {
        return view('articles.index');
    })->name('articles.index');

    Route::get('articles/{article}/edit', function (Article $article) {
        abort_unless($article->project->user_id === auth()->id(), 403);

        return view('articles.edit', [
            'article' => $article,
        ]);
    })->name('articles.edit');
});

require __DIR__.'/settings.php';
