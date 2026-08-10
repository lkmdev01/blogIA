<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Project;
use App\Services\Seo\SitemapService;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('/blogs/{project:slug}', function (Project $project) {
    return view('blogs.index', [
        'project' => $project->load([
            'categories',
            'pillars.clusters',
            'articles' => fn ($query) => $query->published()->latest('published_at'),
        ]),
    ]);
})->name('blogs.index');

Route::get('/blogs/{project:slug}/categories/{category}', function (Project $project, string $category) {
    $categoryModel = Category::query()
        ->where('project_id', $project->id)
        ->where('slug', $category)
        ->firstOrFail();

    return view('blogs.category', [
        'project' => $project,
        'category' => $categoryModel,
        'articles' => Article::query()
            ->where('project_id', $project->id)
            ->where('category_id', $categoryModel->id)
            ->published()
            ->latest('published_at')
            ->get(),
    ]);
})->name('blogs.category');

Route::get('/blogs/{project:slug}/articles/{article}', function (Project $project, string $article) {
    $articleModel = Article::query()
        ->where('project_id', $project->id)
        ->where('slug', $article)
        ->firstOrFail();

    abort_unless($articleModel->status === 'published' || $articleModel->project->user_id === auth()->id(), 404);

    return view('blogs.article', [
        'project' => $project,
        'article' => $articleModel->load('category', 'internalLinks.linkedArticle'),
    ]);
})->name('blogs.article');

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
