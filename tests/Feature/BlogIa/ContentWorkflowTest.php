<?php

use App\Jobs\GenerateArticleContent;
use App\Jobs\GenerateGoogleOpportunityContent;
use App\Jobs\GenerateProjectStrategy;
use App\Models\Article;
use App\Models\Project;
use App\Models\User;
use App\Services\Groq\GroqClient;
use App\Services\Seo\ArticleGeneratorService;
use App\Services\Seo\ContentPlannerService;
use App\Services\Seo\ProjectContentGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('it generates seo strategy and article content without images', function () {
    config()->set('services.groq.key', null);

    $project = Project::factory()->for(User::factory())->create([
        'name' => 'BlogIA',
        'slug' => 'blogia-test',
        'niche' => 'IA para empresas',
        'primary_keywords' => ['ia para empresas', 'conteudo seo'],
        'generate_images' => false,
        'enable_interlinking' => true,
        'auto_publish' => false,
    ]);

    app(ContentPlannerService::class)->generateStrategy($project);

    expect($project->fresh()->pillars()->count())->toBeGreaterThan(0)
        ->and($project->clusters()->count())->toBeGreaterThan(0)
        ->and($project->articles()->where('status', 'idea')->count())->toBeGreaterThan(0);

    $supportArticle = Article::factory()->for($project)->published()->create([
        'focus_keyword' => 'ia para empresas',
        'tags' => ['seo', 'ia'],
        'long_tail_keywords' => ['ia para empresas'],
    ]);

    $article = $project->articles()->where('status', 'idea')->firstOrFail();

    app(ArticleGeneratorService::class)->generate($article, force: true);

    $article = $article->refresh();

    expect($article->content)->not->toBeEmpty()
        ->and($article->content)->toContain('**')
        ->and($article->content)->toContain('## ')
        ->and($article->content)->toContain('- **')
        ->and($article->content)->toContain('> ')
        ->and($article->meta_description)->not->toBeEmpty()
        ->and($article->slug)->not->toBeEmpty()
        ->and($article->featured_image_path)->toBeNull()
        ->and($article->generation_status)->toBe('completed')
        ->and($article->internalLinks()->where('linked_article_id', $supportArticle->id)->exists())->toBeTrue();
});

test('it generates full content for every pending article when project automation is enabled', function () {
    config()->set('services.groq.key', null);

    $project = Project::factory()->for(User::factory())->create([
        'name' => 'BlogIA',
        'slug' => 'blogia-full-content',
        'niche' => 'IA para empresas',
        'primary_keywords' => ['ia para empresas', 'automacao de marketing'],
        'auto_generate_content' => true,
        'auto_publish' => false,
        'generate_images' => false,
    ]);

    $result = app(ProjectContentGenerationService::class)->generateStrategyAndConfiguredContent($project);

    $articles = $project->fresh()->articles()->get();

    expect($result['generated_articles'])->toBe($articles->count())
        ->and($articles)->not->toBeEmpty()
        ->and($articles->every(fn (Article $article): bool => filled($article->content)))->toBeTrue()
        ->and($articles->every(fn (Article $article): bool => $article->generation_status === 'completed'))->toBeTrue()
        ->and($articles->every(fn (Article $article): bool => $article->featured_image_path === null))->toBeTrue();
});

test('it keeps strategy only when project content automation is disabled', function () {
    config()->set('services.groq.key', null);

    $project = Project::factory()->for(User::factory())->create([
        'slug' => 'blogia-strategy-only',
        'primary_keywords' => ['ia para empresas'],
        'auto_generate_content' => false,
    ]);

    $result = app(ProjectContentGenerationService::class)->generateStrategyAndConfiguredContent($project);

    expect($result['generated_articles'])->toBe(0)
        ->and($project->fresh()->articles()->count())->toBeGreaterThan(0)
        ->and($project->articles()->whereNotNull('content')->count())->toBe(0);
});

test('it queues strategy and article generation with project limits', function () {
    Queue::fake();

    $project = Project::factory()->for(User::factory())->create([
        'slug' => 'blogia-queue-controls',
        'auto_generate_content' => true,
        'generation_batch_size' => 2,
        'generation_delay_seconds' => 30,
    ]);

    Article::factory()->for($project)->idea()->count(4)->create();

    $run = app(ProjectContentGenerationService::class)->queueStrategyAndConfiguredContent($project);
    $queuedArticles = app(ProjectContentGenerationService::class)->queuePendingArticles($project, $project->generation_batch_size);

    expect($run->status)->toBe('queued')
        ->and($queuedArticles)->toBe(2)
        ->and($project->articles()->where('generation_status', 'queued')->count())->toBe(2);

    Queue::assertPushed(GenerateProjectStrategy::class);
    Queue::assertPushed(GenerateArticleContent::class, 2);
});

test('it queues google opportunity discovery for a project', function () {
    Queue::fake();

    $project = Project::factory()->for(User::factory())->create([
        'slug' => 'blogia-google-radar-queue',
        'target_location' => 'Guaruja',
    ]);

    $run = app(ProjectContentGenerationService::class)->queueGoogleOpportunityContent($project);

    expect($run->type)->toBe('trend')
        ->and($run->status)->toBe('queued')
        ->and($run->provider)->toBe('fallback');

    Queue::assertPushed(GenerateGoogleOpportunityContent::class);
});

test('it previews google radar signals without creating articles', function () {
    config()->set('services.groq.key', null);
    config()->set('services.google_search_console.client_id', 'google-client-id');
    config()->set('services.google_search_console.client_secret', 'google-client-secret');
    config()->set('services.google_search_console.refresh_token', 'google-refresh-token');
    config()->set('services.google_trends_bigquery.project_id', 'google-trends-project');
    config()->set('services.google_trends_bigquery.client_email', 'trends-service@blogia.iam.gserviceaccount.com');
    config()->set('services.google_trends_bigquery.private_key', <<<'KEY'
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDChjlwmS/yy1y/
gXKSjVt0/ETlIU2D/GfCU4XtUj0epUlQ/JD0m8kXl3Wo0vQ4iULj1wWuLkydblWS
o6PkQEC3y37qKXLY8hJqzG2HY06TIUy9f1NZjgW9ecI1I36lnDv0UMnGQKv+8QzP
OIi1x2sW60l46/GEws3vLa9bmIi2Z5ZYxvZskZmNw3OO9yl4mdFuzl9OgU8uc84B
uqqU8EFYdgSb44qg8A0iMHohw2Z81WJ6SV3eS1XVMgfH2IRfZvuANzLxs3BsdpMb
7YKv8E13iXXK9tKoLiQs0Rf0Q6atV56wtm9Q+z1Z1xn8j+mD/i0Qw9y8qIajHhnu
Ef7YYcHnAgMBAAECggEAAj/AjT7+ijFaf9fEQuN0v0qm+GjF0v1T4vdJ/ekq6SxP
7sBimjk/kT9fUwcm1x/P9AC36IGPk5bSzgrcCtKXUhUjg9jUXPP3G6aYH4M1A1JW
vqfX6dzN0S3RCHj5/sq4HZA+e7J65ZeuLeHGRhZLwE6fdD7Md09KnPcafX5M5as/
VlrkcqBv2B3UrNThU6aQ6zM7TS0NYB/1Pj/vO8PYNemgWJxv+h36kgHI0SNj3M/o
SWG2l9n1IkM0t6d2fw5ekfcGvO5Nft4J9L3IjMlOKR+2qjL1rSc7/EOaI7UaQV5o
2MpiJ8IfFzfw5vVYvdITWbQ4x0EgRu9Y9FZ1uq8n4QKBgQDoi1hroQ5E6Riz3Xi9
Lh+vhVIO6L0H63Zh2ExEtuhR90A3vkY7bJvQOaM13spDpAp1FTz2V46H4h2d0L/K
UuVYxXy8LTsJQk2Dk6VRUFXNehv6MGlm9yFMRfWk9FOjnB+P7xVN0L4e1h9Vw6tf
86udN3pVWDgWx4WVW9Eeqr5ytQKBgQDU6VD65ruql1Sc6TjzK42D9bQw/EfoS+yx
gNdbNN2zw6i7gFsrtXumVKCQf0qXQv0eF7C41eSqq8RTmCUiPGt2EGAWWP8u3II8
2/tVUm8SmYhngRJ6Ck5I0nlv0+QDLf2W4sLwYlw8Y6vtE+0XlUQnQkhR9SXnJlGK
pZLrveQ2oQKBgQC7Y9iPLjvcP6Yci3N4lKLN/mE6kgk8SMktlbzNrwQ1r2LaAfTT
0M6lsJmthGkW/j0fd2V2q2x9lPK3W0mNb2gaJ4ut5IZlN16+wXluY43wmz1PzqG4
tcVSk2VZzMUTe5LB4EXBPtgD3cCI9V2k5KWAUJWDo3nvR1kEi5nfwFauRQKBgB8v
z0C0ok1DqKg84ms1PR1aLUD0+MJNHSiG/Tsf1ChN4AtOQm0WL2fqya8UCP7v0jRp
vDP4YmD3J9U+e9jx1kYJRy+P4Tqk+OqXwbZmcBkI+VwA8S8GvGr4d9f+4KcEDyGb
9QY6AB8VeMNm9SEjRVV+JFG6G4v52+Yslw0vN4xBAoGALApnvEppZY8vL3ANtNOQ
Yld+LgxfFZZsMxI5g+77mV6M42P2zvO8lWgT6V5t2NpFo4kPpU2Cc+L6RbaQHJFw
Z57lSdnrpikV4Q+ArjHbb38SK88gkW5xCrbhK7vwj8rTc7CVF5WdpNojI2tm+QKt
zk3Jrb4X9kbGvBNuNHoN8gc=
-----END PRIVATE KEY-----
KEY);
    cache()->forget('google-search-console-access-token');
    cache()->forget('google-trends-bigquery-access-token');

    Http::fake(function (Request $request) {
        if ($request->url() === 'https://oauth2.googleapis.com/token') {
            return Http::response([
                'access_token' => 'token-123',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200);
        }

        if (str_starts_with($request->url(), 'https://suggestqueries.google.com/')) {
            return Http::response([
                'imoveis no guaruja',
                [
                    'apartamento frente mar guaruja',
                    'casas a venda guaruja',
                ],
            ], 200);
        }

        if (str_starts_with($request->url(), 'https://news.google.com/')) {
            return Http::response(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <item>
            <title>Mercado imobiliario do Guaruja acelera no inverno - Jornal Local</title>
            <pubDate>Fri, 07 Aug 2026 09:00:00 GMT</pubDate>
        </item>
    </channel>
</rss>
XML, 200, ['Content-Type' => 'application/xml']);
        }

        if ($request->url() === 'https://bigquery.googleapis.com/bigquery/v2/projects/google-trends-project/queries') {
            $payload = json_decode($request->body(), true);
            $query = data_get($payload, 'query', '');

            if (str_contains($query, 'international_top_terms')) {
                return Http::response([
                    'rows' => [
                        [
                            'f' => [
                                ['v' => 'apartamento frente mar guaruja'],
                                ['v' => '100'],
                                ['v' => '1'],
                                ['v' => '4'],
                                ['v' => '2026-08-06'],
                            ],
                        ],
                    ],
                ], 200);
            }

            if (str_contains($query, 'international_top_rising_terms')) {
                return Http::response([
                    'rows' => [
                        [
                            'f' => [
                                ['v' => 'casas a venda guaruja'],
                                ['v' => '95'],
                                ['v' => '1'],
                                ['v' => '2'],
                                ['v' => '2026-08-06'],
                            ],
                        ],
                    ],
                ], 200);
            }
        }

        if (str_contains($request->url(), '/searchAnalytics/query')) {
            return Http::response([
                'rows' => [
                    [
                        'keys' => ['apartamento frente mar guaruja'],
                        'clicks' => 8,
                        'impressions' => 420,
                        'ctr' => 0.019,
                        'position' => 6.2,
                    ],
                ],
            ], 200);
        }

        return Http::response([], 404);
    });

    $project = Project::factory()->for(User::factory())->create([
        'name' => 'Imobiliaria Guaruja',
        'slug' => 'imobiliaria-guaruja-preview',
        'niche' => 'mercado imobiliario',
        'target_location' => 'Guaruja',
        'search_console_property' => 'sc-domain:imobiliariaguaruja.com.br',
        'target_country' => 'BRA',
        'google_trends_country' => 'BR',
        'google_trends_region' => 'Sao Paulo',
        'primary_keywords' => ['imoveis no guaruja', 'apartamento frente mar guaruja'],
        'auto_generate_content' => true,
    ]);

    $result = app(ProjectContentGenerationService::class)->previewGoogleOpportunitySignals($project);

    expect(data_get($result, 'diagnostics.search_console.configured'))->toBeTrue()
        ->and(data_get($result, 'diagnostics.google_trends.configured'))->toBeTrue()
        ->and(data_get($result, 'diagnostics.search_console.top_queries.0'))->toBe('apartamento frente mar guaruja')
        ->and(data_get($result, 'diagnostics.google_trends.top_terms.0'))->toBe('apartamento frente mar guaruja')
        ->and(data_get($result, 'diagnostics.google_trends.rising_terms.0'))->toBe('casas a venda guaruja')
        ->and($project->fresh()->articles()->count())->toBe(0);
});

test('it creates trend-driven articles from google and search console signals and generates content', function () {
    config()->set('services.groq.key', null);
    config()->set('services.google_search_console.client_id', 'google-client-id');
    config()->set('services.google_search_console.client_secret', 'google-client-secret');
    config()->set('services.google_search_console.refresh_token', 'google-refresh-token');
    config()->set('services.google_trends_bigquery.project_id', 'google-trends-project');
    config()->set('services.google_trends_bigquery.client_email', 'trends-service@blogia.iam.gserviceaccount.com');
    config()->set('services.google_trends_bigquery.private_key', <<<'KEY'
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDChjlwmS/yy1y/
gXKSjVt0/ETlIU2D/GfCU4XtUj0epUlQ/JD0m8kXl3Wo0vQ4iULj1wWuLkydblWS
o6PkQEC3y37qKXLY8hJqzG2HY06TIUy9f1NZjgW9ecI1I36lnDv0UMnGQKv+8QzP
OIi1x2sW60l46/GEws3vLa9bmIi2Z5ZYxvZskZmNw3OO9yl4mdFuzl9OgU8uc84B
uqqU8EFYdgSb44qg8A0iMHohw2Z81WJ6SV3eS1XVMgfH2IRfZvuANzLxs3BsdpMb
7YKv8E13iXXK9tKoLiQs0Rf0Q6atV56wtm9Q+z1Z1xn8j+mD/i0Qw9y8qIajHhnu
Ef7YYcHnAgMBAAECggEAAj/AjT7+ijFaf9fEQuN0v0qm+GjF0v1T4vdJ/ekq6SxP
7sBimjk/kT9fUwcm1x/P9AC36IGPk5bSzgrcCtKXUhUjg9jUXPP3G6aYH4M1A1JW
vqfX6dzN0S3RCHj5/sq4HZA+e7J65ZeuLeHGRhZLwE6fdD7Md09KnPcafX5M5as/
VlrkcqBv2B3UrNThU6aQ6zM7TS0NYB/1Pj/vO8PYNemgWJxv+h36kgHI0SNj3M/o
SWG2l9n1IkM0t6d2fw5ekfcGvO5Nft4J9L3IjMlOKR+2qjL1rSc7/EOaI7UaQV5o
2MpiJ8IfFzfw5vVYvdITWbQ4x0EgRu9Y9FZ1uq8n4QKBgQDoi1hroQ5E6Riz3Xi9
Lh+vhVIO6L0H63Zh2ExEtuhR90A3vkY7bJvQOaM13spDpAp1FTz2V46H4h2d0L/K
UuVYxXy8LTsJQk2Dk6VRUFXNehv6MGlm9yFMRfWk9FOjnB+P7xVN0L4e1h9Vw6tf
86udN3pVWDgWx4WVW9Eeqr5ytQKBgQDU6VD65ruql1Sc6TjzK42D9bQw/EfoS+yx
gNdbNN2zw6i7gFsrtXumVKCQf0qXQv0eF7C41eSqq8RTmCUiPGt2EGAWWP8u3II8
2/tVUm8SmYhngRJ6Ck5I0nlv0+QDLf2W4sLwYlw8Y6vtE+0XlUQnQkhR9SXnJlGK
pZLrveQ2oQKBgQC7Y9iPLjvcP6Yci3N4lKLN/mE6kgk8SMktlbzNrwQ1r2LaAfTT
0M6lsJmthGkW/j0fd2V2q2x9lPK3W0mNb2gaJ4ut5IZlN16+wXluY43wmz1PzqG4
tcVSk2VZzMUTe5LB4EXBPtgD3cCI9V2k5KWAUJWDo3nvR1kEi5nfwFauRQKBgB8v
z0C0ok1DqKg84ms1PR1aLUD0+MJNHSiG/Tsf1ChN4AtOQm0WL2fqya8UCP7v0jRp
vDP4YmD3J9U+e9jx1kYJRy+P4Tqk+OqXwbZmcBkI+VwA8S8GvGr4d9f+4KcEDyGb
9QY6AB8VeMNm9SEjRVV+JFG6G4v52+Yslw0vN4xBAoGALApnvEppZY8vL3ANtNOQ
Yld+LgxfFZZsMxI5g+77mV6M42P2zvO8lWgT6V5t2NpFo4kPpU2Cc+L6RbaQHJFw
Z57lSdnrpikV4Q+ArjHbb38SK88gkW5xCrbhK7vwj8rTc7CVF5WdpNojI2tm+QKt
zk3Jrb4X9kbGvBNuNHoN8gc=
-----END PRIVATE KEY-----
KEY);
    Carbon::setTestNow('2026-08-07 12:00:00');
    cache()->forget('google-search-console-access-token');
    cache()->forget('google-trends-bigquery-access-token');

    Http::fake(function (Request $request) {
        if ($request->url() === 'https://oauth2.googleapis.com/token') {
            return Http::response([
                'access_token' => 'token-123',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200);
        }

        if (str_starts_with($request->url(), 'https://suggestqueries.google.com/')) {
            return Http::response([
                'imoveis no guaruja',
                [
                    'apartamento frente mar guaruja',
                    'casas a venda guaruja',
                    'melhores bairros do guaruja',
                ],
            ], 200);
        }

        if (str_starts_with($request->url(), 'https://news.google.com/')) {
            return Http::response(<<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
        <item>
            <title>Mercado imobiliario do Guaruja atrai novos investidores - Jornal Local</title>
            <pubDate>Fri, 07 Aug 2026 09:00:00 GMT</pubDate>
        </item>
        <item>
            <title>Busca por apartamento frente mar no Guaruja cresce no inverno - Diario Imobiliario</title>
            <pubDate>Fri, 07 Aug 2026 11:00:00 GMT</pubDate>
        </item>
    </channel>
</rss>
XML, 200, ['Content-Type' => 'application/xml']);
        }

        if ($request->url() === 'https://bigquery.googleapis.com/bigquery/v2/projects/google-trends-project/queries') {
            $payload = json_decode($request->body(), true);
            $query = data_get($payload, 'query', '');

            if (str_contains($query, 'international_top_terms')) {
                return Http::response([
                    'rows' => [
                        [
                            'f' => [
                                ['v' => 'apartamento frente mar guaruja'],
                                ['v' => '100'],
                                ['v' => '1'],
                                ['v' => '4'],
                                ['v' => '2026-08-06'],
                            ],
                        ],
                        [
                            'f' => [
                                ['v' => 'mercado imobiliario guaruja'],
                                ['v' => '79'],
                                ['v' => '2'],
                                ['v' => '3'],
                                ['v' => '2026-08-06'],
                            ],
                        ],
                    ],
                ], 200);
            }

            if (str_contains($query, 'international_top_rising_terms')) {
                return Http::response([
                    'rows' => [
                        [
                            'f' => [
                                ['v' => 'casas a venda guaruja'],
                                ['v' => '95'],
                                ['v' => '1'],
                                ['v' => '2'],
                                ['v' => '2026-08-06'],
                            ],
                        ],
                    ],
                ], 200);
            }
        }

        if (str_contains($request->url(), '/searchAnalytics/query')) {
            $payload = json_decode($request->body(), true);
            $startDate = data_get($payload, 'startDate');

            return match ($startDate) {
                '2026-07-08' => Http::response([
                    'rows' => [
                        [
                            'keys' => ['apartamento frente mar guaruja'],
                            'clicks' => 8,
                            'impressions' => 420,
                            'ctr' => 0.019,
                            'position' => 6.2,
                        ],
                        [
                            'keys' => ['casas a venda guaruja'],
                            'clicks' => 30,
                            'impressions' => 240,
                            'ctr' => 0.125,
                            'position' => 4.1,
                        ],
                    ],
                ], 200),
                '2026-07-29' => Http::response([
                    'rows' => [
                        [
                            'keys' => ['apartamento frente mar guaruja'],
                            'clicks' => 6,
                            'impressions' => 150,
                            'ctr' => 0.04,
                            'position' => 5.9,
                        ],
                        [
                            'keys' => ['mercado imobiliario guaruja'],
                            'clicks' => 4,
                            'impressions' => 60,
                            'ctr' => 0.066,
                            'position' => 8.4,
                        ],
                    ],
                ], 200),
                '2026-07-22' => Http::response([
                    'rows' => [
                        [
                            'keys' => ['apartamento frente mar guaruja'],
                            'clicks' => 2,
                            'impressions' => 55,
                            'ctr' => 0.036,
                            'position' => 8.1,
                        ],
                        [
                            'keys' => ['mercado imobiliario guaruja'],
                            'clicks' => 1,
                            'impressions' => 18,
                            'ctr' => 0.055,
                            'position' => 10.4,
                        ],
                    ],
                ], 200),
                default => Http::response(['rows' => []], 200),
            };
        }

        return Http::response([], 404);
    });

    $project = Project::factory()->for(User::factory())->create([
        'name' => 'Imobiliaria Guaruja',
        'slug' => 'imobiliaria-guaruja',
        'niche' => 'mercado imobiliario',
        'target_location' => 'Guaruja',
        'search_console_property' => 'sc-domain:imobiliariaguaruja.com.br',
        'target_country' => 'BRA',
        'google_trends_country' => 'BR',
        'google_trends_region' => 'Sao Paulo',
        'primary_keywords' => ['imoveis no guaruja', 'apartamento frente mar guaruja'],
        'auto_generate_content' => true,
        'auto_publish' => false,
        'generate_images' => false,
    ]);

    $result = app(ProjectContentGenerationService::class)->generateGoogleOpportunityContent($project);
    $articles = $project->fresh()->articles()->get();

    expect($result['generated_articles'])->toBe($articles->count())
        ->and(data_get($result, 'signals.search_console.configured'))->toBeTrue()
        ->and(data_get($result, 'signals.google_trends.configured'))->toBeTrue()
        ->and(count(data_get($result, 'signals.search_console.top_queries', [])))->toBeGreaterThan(0)
        ->and(count(data_get($result, 'signals.google_trends.top_terms', [])))->toBeGreaterThan(0)
        ->and($articles)->not->toBeEmpty()
        ->and($articles->every(fn (Article $article): bool => filled($article->content)))->toBeTrue()
        ->and($articles->every(fn (Article $article): bool => data_get($article->source_payload, 'discovery.type') === 'google_signals'))->toBeTrue()
        ->and($articles->contains(fn (Article $article): bool => in_array('search_console_top', data_get($article->source_payload, 'discovery.source_mix', []), true)))->toBeTrue()
        ->and($articles->contains(fn (Article $article): bool => in_array('google_trends_top', data_get($article->source_payload, 'discovery.source_mix', []), true)))->toBeTrue()
        ->and($articles->max(fn (Article $article): int => (int) data_get($article->source_payload, 'discovery.opportunity_score', 0)))->toBeGreaterThan(60)
        ->and($project->fresh()->last_trend_scanned_at)->not->toBeNull()
        ->and($project->fresh()->last_search_console_synced_at)->not->toBeNull()
        ->and($project->fresh()->last_google_trends_synced_at)->not->toBeNull()
        ->and($project->generationRuns()->where('type', 'trend')->latest()->first()?->status)->toBe('completed');

    Carbon::setTestNow();
});

test('authenticated blogia management screens render', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create([
        'slug' => 'blogia-screens',
    ]);

    $article = Article::factory()->for($project)->create([
        'slug' => 'artigo-de-teste',
    ]);

    $this->actingAs($user);

    $this->get(route('dashboard'))->assertOk()->assertSee('BlogIA SEO Engine');
    $this->get(route('projects.index'))->assertRedirect(route('projects.show', $project));
    $this->get(route('projects.show', $project))->assertOk()->assertSee($project->name);
    $this->get(route('articles.index'))->assertOk()->assertSee('Lista de artigos');
    $this->get(route('articles.edit', $article))
        ->assertOk()
        ->assertSee('Editor de artigo')
        ->assertSee('Abrir previa formatada')
        ->assertSee('Previa formatada');
});

test('it falls back when groq returns invalid strategy json', function () {
    $project = Project::factory()->for(User::factory())->create([
        'name' => 'BlogIA',
        'slug' => 'blogia-groq-fallback',
        'niche' => 'IA para empresas',
        'primary_keywords' => ['ia para empresas', 'automacao de marketing'],
        'ai_provider' => 'groq',
    ]);

    $groqClient = Mockery::mock(GroqClient::class);
    $groqClient->shouldReceive('isConfigured')->andReturnTrue();
    $groqClient->shouldReceive('model')->andReturn('test-model');
    $groqClient->shouldReceive('chat')->once()->andReturn('Aqui esta a pauta: ```json { "pillars": [ } ```');

    app()->instance(GroqClient::class, $groqClient);

    app(ContentPlannerService::class)->generateStrategy($project);

    expect($project->fresh()->pillars()->count())->toBeGreaterThan(0)
        ->and($project->clusters()->count())->toBeGreaterThan(0)
        ->and($project->articles()->count())->toBeGreaterThan(0)
        ->and($project->generationRuns()->latest()->first()?->status)->toBe('completed');
});

test('it falls back when groq strategy request is rate limited', function () {
    $project = Project::factory()->for(User::factory())->create([
        'slug' => 'blogia-strategy-rate-limit',
        'niche' => 'IA para empresas',
        'primary_keywords' => ['ia para empresas'],
        'ai_provider' => 'groq',
    ]);

    $groqClient = Mockery::mock(GroqClient::class);
    $groqClient->shouldReceive('isConfigured')->andReturnTrue();
    $groqClient->shouldReceive('model')->andReturn('test-model');
    $groqClient->shouldReceive('chat')->once()->andThrow(new RuntimeException('Rate limit reached for model.'));

    app()->instance(GroqClient::class, $groqClient);

    app(ContentPlannerService::class)->generateStrategy($project);

    expect($project->fresh()->pillars()->count())->toBeGreaterThan(0)
        ->and($project->articles()->count())->toBeGreaterThan(0)
        ->and($project->generationRuns()->latest()->first()?->status)->toBe('completed');
});

test('it falls back when groq returns invalid article json', function () {
    $project = Project::factory()->for(User::factory())->create([
        'name' => 'BlogIA',
        'slug' => 'blogia-article-fallback',
        'niche' => 'IA para empresas',
        'primary_keywords' => ['ia para empresas'],
        'ai_provider' => 'groq',
        'auto_publish' => false,
    ]);

    $article = Article::factory()->for($project)->create([
        'title' => 'IA para empresas: guia pratico',
        'focus_keyword' => 'ia para empresas',
        'long_tail_keywords' => ['ia para empresas b2b'],
        'generation_status' => 'pending',
    ]);

    $groqClient = Mockery::mock(GroqClient::class);
    $groqClient->shouldReceive('isConfigured')->andReturnTrue();
    $groqClient->shouldReceive('model')->andReturn('test-model');
    $groqClient->shouldReceive('chat')->once()->andReturn('Nao consegui retornar JSON valido desta vez.');

    app()->instance(GroqClient::class, $groqClient);

    $generatedArticle = app(ArticleGeneratorService::class)->generate($article, force: true);

    expect($generatedArticle->content)->not->toBeEmpty()
        ->and($generatedArticle->meta_description)->not->toBeEmpty()
        ->and($generatedArticle->generation_status)->toBe('completed')
        ->and($generatedArticle->generationRuns()->latest()->first()?->status)->toBe('completed');
});

test('it falls back when groq article request is rate limited', function () {
    $project = Project::factory()->for(User::factory())->create([
        'slug' => 'blogia-article-rate-limit',
        'primary_keywords' => ['ia para empresas'],
        'ai_provider' => 'groq',
        'auto_publish' => false,
    ]);

    $article = Article::factory()->for($project)->create([
        'title' => 'IA para empresas e automacao',
        'focus_keyword' => 'ia para empresas',
        'generation_status' => 'pending',
    ]);

    $groqClient = Mockery::mock(GroqClient::class);
    $groqClient->shouldReceive('isConfigured')->andReturnTrue();
    $groqClient->shouldReceive('model')->andReturn('test-model');
    $groqClient->shouldReceive('chat')->once()->andThrow(new RuntimeException('HTTP request returned status code 429.'));

    app()->instance(GroqClient::class, $groqClient);

    $generatedArticle = app(ArticleGeneratorService::class)->generate($article, force: true);

    expect($generatedArticle->content)->not->toBeEmpty()
        ->and($generatedArticle->generation_status)->toBe('completed')
        ->and($generatedArticle->generationRuns()->latest()->first()?->status)->toBe('completed');
});
