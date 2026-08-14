<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @php
            $pageTitle = ($category->seo_title ?: $category->name).' - '.$project->name;
            $pageDescription = $category->seo_description ?: $category->description ?: 'Leituras para liderancas que precisam transformar estrategia em execucao.';
            $canonicalUrl = $project->publicCategoryUrl($category, array_filter([
                'category' => $category->slug,
                'order' => ($order ?? 'recent') !== 'recent' ? $order : null,
                'flow' => ($flow ?? 8) > 8 ? $flow : null,
            ]));
            $heroImage = $featuredArticle?->featured_image_path
                ? (\Illuminate\Support\Str::startsWith($featuredArticle->featured_image_path, ['http://', 'https://']) ? $featuredArticle->featured_image_path : url($featuredArticle->featured_image_path))
                : ($project->hero_image_url ?: 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1600&q=80');
            $flowLimit = max(4, min(40, (int) ($flow ?? 8)));
            $listArticles = $featuredArticle ? ($articles ?? collect())->skip(1) : ($articles ?? collect());
            $visibleArticles = $listArticles->take($flowLimit);
            $hasMoreArticles = $listArticles->count() > $visibleArticles->count();
            $nextFlowLimit = min(40, $flowLimit + 8);
        @endphp
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:site_name" content="{{ $project->name }}">
        <meta property="og:image" content="{{ $heroImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $pageTitle }}">
        <meta name="twitter:description" content="{{ $pageDescription }}">
        <meta name="twitter:image" content="{{ $heroImage }}">
        @php($pageType = 'blog_category')
        @include('blogs.partials.analytics', ['project' => $project, 'pageType' => $pageType, 'category' => $category])
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-stone-50 text-zinc-950 antialiased">
        <main class="mx-auto max-w-6xl px-5 py-5 md:px-8 md:py-8">
            <section class="overflow-hidden rounded-lg bg-zinc-950 text-white">
                <div class="border-b border-white/10 px-6 py-5 md:px-10">
                    <nav class="flex flex-wrap items-center gap-2 text-sm text-zinc-300">
                        <a href="{{ $project->publicIndexUrl() }}" class="inline-flex items-center gap-3 font-medium text-emerald-300 transition hover:text-white">
                            <span class="inline-flex rounded-md bg-white px-2 py-1">
                                <img src="{{ asset('logo-blog.png') }}" alt="{{ $project->name }}" class="h-6 w-auto object-contain">
                            </span>
                            <span>{{ $project->name }}</span>
                        </a>
                        <span>/</span>
                        <span class="text-zinc-400">{{ $category->name }}</span>
                    </nav>
                </div>

                <div class="grid gap-8 px-6 py-10 md:px-10 md:py-12 lg:grid-cols-[minmax(0,1fr)_280px] lg:items-end">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-[0.25em] text-emerald-300">Biblioteca setorial</p>
                        <h1 class="mt-4 text-4xl font-semibold tracking-tight md:text-5xl">{{ $category->name }}</h1>
                        <p class="mt-5 max-w-3xl text-lg leading-8 text-zinc-200">{{ $pageDescription }}</p>
                    </div>

                    <aside class="border-t border-white/15 pt-6 lg:border-t-0 lg:border-l lg:pl-8 lg:pt-0">
                        <p class="text-sm uppercase tracking-[0.2em] text-zinc-400">Contexto</p>
                        <p class="mt-3 text-sm leading-7 text-zinc-200">{{ $project->niche }}</p>
                        <div class="mt-4 space-y-2 text-sm text-zinc-300">
                            <p><span class="font-medium text-white">{{ ($articles ?? collect())->count() }}</span> artigos publicados</p>
                            @if ($featuredArticle)
                                <p>Atualizado com destaque editorial recente</p>
                            @endif
                        </div>
                    </aside>
                </div>
            </section>

            @if ($featuredArticle)
                <section class="grid gap-6 border-b border-zinc-200 py-10 lg:grid-cols-[minmax(0,1.2fr)_280px]">
                    <article class="overflow-hidden rounded-lg border border-zinc-200 bg-white">
                        <img src="{{ $heroImage }}" alt="{{ $featuredArticle->featured_image_alt ?: $featuredArticle->title }}" class="aspect-[16/9] w-full object-cover">
                        <div class="px-6 py-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Destaque editorial</p>
                            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-zinc-950">
                                <a href="{{ $project->publicArticleUrl($featuredArticle) }}" class="transition hover:text-emerald-700">{{ $featuredArticle->title }}</a>
                            </h2>
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs font-medium uppercase tracking-[0.18em] text-zinc-500">
                                <span>{{ $featuredArticle->published_at?->format('d/m/Y') }}</span>
                                @if ($featuredArticle->public_view_count > 0)
                                    <span>&middot;</span>
                                    <span>{{ $featuredArticle->public_view_count }} leituras</span>
                                @endif
                            </div>
                            <p class="mt-4 text-sm leading-7 text-zinc-600">{{ $featuredArticle->excerpt ?: $featuredArticle->meta_description }}</p>
                            <a href="{{ $project->publicArticleUrl($featuredArticle) }}" class="mt-5 inline-flex text-sm font-semibold text-emerald-700 transition hover:text-emerald-900">Ler destaque</a>
                        </div>
                    </article>

                    <aside class="rounded-lg border border-zinc-200 bg-white px-5 py-5">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-zinc-500">Ordenar biblioteca</p>
                        <form method="GET" action="{{ $project->publicCategoryUrl($category) }}" class="mt-4 space-y-3">
                            <select name="order" class="h-12 w-full rounded-md border border-zinc-200 bg-white px-4 text-sm text-zinc-700 outline-none">
                                <option value="recent" @selected(($order ?? 'recent') === 'recent')>Mais recentes</option>
                                <option value="popular" @selected(($order ?? 'recent') === 'popular')>Mais lidos</option>
                                <option value="seo" @selected(($order ?? 'recent') === 'seo')>Melhor SEO</option>
                            </select>
                            <input type="hidden" name="flow" value="{{ $flowLimit }}">
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-emerald-300 px-4 py-3 text-xs font-semibold uppercase tracking-[0.22em] text-zinc-950 transition hover:bg-emerald-200">
                                Aplicar
                            </button>
                        </form>
                    </aside>
                </section>
            @endif

            <section class="divide-y divide-zinc-200 py-10">
                @forelse ($visibleArticles as $article)
                    <article class="py-8">
                        <div class="flex flex-wrap items-center gap-2 text-sm text-zinc-500">
                            <span>{{ $article->published_at?->format('d/m/Y') }}</span>
                            <span>&middot;</span>
                            <span>{{ max(1, (int) ceil(max(200, $article->word_count) / 200)) }} min</span>
                            @if ($article->public_view_count > 0)
                                <span>&middot;</span>
                                <span>{{ $article->public_view_count }} leituras</span>
                            @endif
                        </div>
                        <h2 class="mt-3 max-w-4xl text-2xl font-semibold tracking-tight">
                            <a href="{{ $project->publicArticleUrl($article) }}" class="transition hover:text-emerald-700">{{ $article->title }}</a>
                        </h2>
                        <p class="mt-3 max-w-3xl leading-7 text-zinc-600">{{ $article->excerpt ?: $article->meta_description }}</p>
                        <a href="{{ $project->publicArticleUrl($article) }}" class="mt-4 inline-flex items-center text-sm font-semibold text-emerald-700 transition hover:text-emerald-900">Ler artigo</a>
                    </article>
                @empty
                    <p class="py-8 text-zinc-600">Nenhum artigo publicado nesta categoria.</p>
                @endforelse

                @if ($hasMoreArticles)
                    <div class="pt-10">
                        <a href="{{ $project->publicCategoryUrl($category, ['order' => $order !== 'recent' ? $order : null, 'flow' => $nextFlowLimit]) }}" class="inline-flex items-center rounded-md bg-emerald-300 px-6 py-3 text-xs font-semibold uppercase tracking-[0.22em] text-zinc-950 transition hover:bg-emerald-200">
                            Ver mais artigos
                        </a>
                    </div>
                @endif
            </section>
        </main>

        @include('blogs.partials.footer', [
            'project' => $project,
            'description' => $project->hero_description ?: ($project->description ?: 'Conteudos sobre inteligencia artificial aplicada a empresas, com foco em automacao, produtividade e crescimento comercial.'),
            'officialUrl' => blank($project->domain)
                ? null
                : (\Illuminate\Support\Str::startsWith($project->domain, ['http://', 'https://']) ? $project->domain : 'https://'.$project->domain),
            'navigationLinks' => [
                'Home do blog' => $project->publicIndexUrl(),
                'Categoria atual' => $project->publicCategoryUrl($category),
                'Biblioteca' => $project->publicIndexUrl().'#biblioteca',
                'Buscar no blog' => $project->publicIndexUrl().'#top',
            ],
            'contactHref' => $project->publicIndexUrl().'#conversa',
            'contactLabel' => 'Abrir conversa estrategica',
        ])
    </body>
</html>
