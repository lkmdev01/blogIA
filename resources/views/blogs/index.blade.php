<!DOCTYPE html>
@php
    $pageTitle = $search !== ''
        ? 'Busca por '.$search.' - '.$project->name
        : ((isset($selectedCategory) && $selectedCategory) ? $selectedCategory->name.' - '.$project->name : $project->name);
    $pageDescription = $project->hero_description ?: ($project->description ?: 'Conteudos sobre inteligencia artificial aplicada a empresas, com foco em automacao, produtividade e crescimento comercial.');
    $pageImage = $project->hero_image_url ?: 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1600&q=80';
    $canonicalUrl = $project->publicIndexUrl(array_filter([
        'search' => $search !== '' ? $search : null,
        'category' => isset($selectedCategory) && $selectedCategory ? $selectedCategory->slug : null,
        'flow' => ($flow ?? 8) > 8 ? $flow : null,
    ]));
    $highlightText = function (?string $text, ?int $limit = null) use ($search): string {
        $normalizedText = $limit ? \Illuminate\Support\Str::limit((string) $text, $limit) : (string) $text;
        $safeText = e($normalizedText);

        if ($search === '') {
            return $safeText;
        }

        return preg_replace('/('.preg_quote(e($search), '/').')/iu', '<mark class="rounded bg-emerald-100 px-1 text-zinc-950">$1</mark>', $safeText) ?: $safeText;
    };
    $featuredArticle = $project->articles->first();
    $secondaryArticles = $project->articles->skip(1)->take(2);
    $gridArticles = $project->articles->skip(3);
    $flowLimit = max(4, min(40, (int) ($flow ?? 8)));
    $visibleGridArticles = ($gridArticles->isNotEmpty() ? $gridArticles : $project->articles->skip(1))->take($flowLimit);
    $hasMoreGridArticles = ($gridArticles->isNotEmpty() ? $gridArticles : $project->articles->skip(1))->count() > $visibleGridArticles->count();
    $nextFlowLimit = min(40, $flowLimit + 8);
    $primaryKeywords = collect($project->primary_keywords ?? [])->filter()->values();
    $officialUrl = blank($project->domain)
        ? null
        : (\Illuminate\Support\Str::startsWith($project->domain, ['http://', 'https://']) ? $project->domain : 'https://'.$project->domain);
    $fallbackImages = [
        'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1526628953301-3e589a6a8b74?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1516321497487-e288fb19713f?auto=format&fit=crop&w=900&q=80',
    ];
    $resolveImage = fn ($article, $index) => $article->featured_image_path ?: $fallbackImages[$index % count($fallbackImages)];
    $marqueeRows = [
        $primaryKeywords->filter(fn ($keyword, $index) => $index % 2 === 0)->values(),
        $primaryKeywords->filter(fn ($keyword, $index) => $index % 2 === 1)->values(),
    ];

    if ($marqueeRows[0]->isEmpty()) {
        $marqueeRows[0] = $primaryKeywords;
    }

    if ($marqueeRows[1]->isEmpty()) {
        $marqueeRows[1] = $primaryKeywords;
    }
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $pageDescription }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $pageDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:site_name" content="{{ $project->name }}">
        <meta property="og:image" content="{{ $pageImage }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $pageTitle }}">
        <meta name="twitter:description" content="{{ $pageDescription }}">
        <meta name="twitter:image" content="{{ $pageImage }}">
        @php($pageType = 'blog_index')
        @include('blogs.partials.analytics', ['project' => $project, 'pageType' => $pageType])
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body id="top" class="bg-stone-50 text-zinc-950 antialiased">
        <section class="relative overflow-hidden rounded-b-[1.75rem] bg-zinc-950 text-white">
            <img
                src="{{ $pageImage }}"
                alt="Equipe de tecnologia trabalhando em estrategia digital"
                class="absolute inset-0 h-full w-full object-cover opacity-25"
            >
            <div class="absolute inset-0 bg-zinc-950/70"></div>

            <div class="relative">
                <header class="border-b border-zinc-200 bg-white/96 text-zinc-950 backdrop-blur">
                    <div class="mx-auto flex max-w-6xl items-center justify-center gap-8 px-6 py-5 text-[11px] font-semibold uppercase tracking-[0.22em] md:gap-12 md:px-10">
                        <nav class="hidden items-center gap-8 md:flex">
                            <a href="#leituras" class="transition hover:text-emerald-700">Leituras</a>
                            <a href="#frentes" class="transition hover:text-emerald-700">Frentes</a>
                        </nav>

                        <a href="{{ $project->publicIndexUrl() }}" class="flex flex-col items-center justify-center leading-none">
                            <span class="text-[10px] tracking-[0.32em] text-zinc-500">INOVAFORCE</span>
                            <span class="mt-2 text-xs tracking-[0.28em] text-zinc-900">{{ $project->name }}</span>
                        </a>

                        <nav class="hidden items-center gap-8 md:flex">
                            <a href="#biblioteca" class="transition hover:text-emerald-700">Biblioteca</a>
                            @if (filled($project->default_cta))
                                <a href="#conversa" class="transition hover:text-emerald-700">Contato</a>
                            @endif
                        </nav>
                    </div>

                    <div class="border-t border-zinc-200/80 md:hidden">
                        <nav class="flex items-center justify-center gap-5 overflow-x-auto px-6 py-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-700">
                            <a href="#leituras" class="shrink-0 transition hover:text-emerald-700">Leituras</a>
                            <a href="#frentes" class="shrink-0 transition hover:text-emerald-700">Frentes</a>
                            <a href="#biblioteca" class="shrink-0 transition hover:text-emerald-700">Biblioteca</a>
                            @if (filled($project->default_cta))
                                <a href="#conversa" class="shrink-0 transition hover:text-emerald-700">Contato</a>
                            @endif
                        </nav>
                    </div>
                </header>

                <div class="px-6 py-12 md:px-10 md:py-14">
                    <div class="mx-auto max-w-4xl text-center">
                        <p class="text-sm font-medium uppercase tracking-[0.28em] text-emerald-300">{{ $project->niche }}</p>
                        <h1 class="mt-5 text-4xl font-semibold tracking-tight md:text-6xl">{{ $project->name }}</h1>
                        <p class="mx-auto mt-5 max-w-2xl text-base leading-7 text-zinc-200 md:text-lg">{{ $pageDescription }}</p>

                        <form method="GET" action="{{ $project->publicIndexUrl() }}" class="mx-auto mt-8 max-w-4xl" data-blog-search-form>
                            <label for="blog-search" class="sr-only">Buscar no blog</label>
                            <div class="overflow-hidden rounded-md bg-white shadow-2xl shadow-zinc-950/20 ring-1 ring-zinc-200">
                                <div class="flex flex-col md:flex-row">
                                    <div class="flex flex-1 items-center gap-3 px-4">
                                        <svg class="h-4 w-4 text-zinc-400" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.166 14.167 17.5 17.5M15.833 9.167a6.667 6.667 0 1 1-13.333 0 6.667 6.667 0 0 1 13.333 0Z" />
                                        </svg>
                                        <input
                                            id="blog-search"
                                            name="search"
                                            type="search"
                                            value="{{ $search }}"
                                            placeholder="Buscar no blog"
                                            class="h-14 w-full border-0 bg-transparent text-sm text-zinc-900 outline-none placeholder:text-zinc-400"
                                        >
                                    </div>
                                    <div class="border-t border-zinc-200 md:w-56 md:border-l md:border-t-0">
                                        <label for="blog-category" class="sr-only">Filtrar categoria</label>
                                        <select id="blog-category" name="category" class="h-14 w-full border-0 bg-transparent px-4 text-sm text-zinc-700 outline-none">
                                            <option value="">Todas as categorias</option>
                                            @foreach ($project->categories as $category)
                                                <option value="{{ $category->slug }}" @selected(isset($selectedCategory) && $selectedCategory?->id === $category->id)>{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="h-14 shrink-0 bg-emerald-300 px-6 text-xs font-semibold uppercase tracking-[0.22em] text-zinc-950 transition hover:bg-emerald-200 md:w-40">
                                        Buscar
                                    </button>
                                </div>
                            </div>
                        </form>

                        @if ($search !== '' || (isset($selectedCategory) && $selectedCategory))
                            <div class="mt-4 flex flex-wrap items-center justify-center gap-3 text-sm text-zinc-300">
                                @if ($search !== '')
                                    <p>Resultados para <span class="font-medium text-white">{{ $search }}</span></p>
                                @endif
                                @if (isset($selectedCategory) && $selectedCategory)
                                    <p>Categoria <span class="font-medium text-white">{{ $selectedCategory->name }}</span></p>
                                @endif
                                <a href="{{ $project->publicIndexUrl() }}" class="font-medium text-emerald-300 transition hover:text-white">Limpar filtros</a>
                            </div>
                        @endif

                        @if ($primaryKeywords->isNotEmpty())
                            <div class="mt-8 space-y-3">
                                @foreach ($marqueeRows as $rowIndex => $keywords)
                                    <div class="blogia-marquee" aria-label="Temas editoriais">
                                        <div class="blogia-marquee-track {{ $rowIndex % 2 === 1 ? 'blogia-marquee-track-reverse' : '' }}">
                                            @foreach (range(1, 2) as $duplicate)
                                                @foreach ($keywords as $keyword)
                                                    <span class="blogia-marquee-chip" @if ($duplicate === 2) aria-hidden="true" @endif>{{ $keyword }}</span>
                                                @endforeach
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <main class="mx-auto max-w-7xl px-5 py-8 md:px-8 md:py-10">
            @if ($featuredArticle)
                <section id="leituras" class="py-10">
                    <div class="grid gap-8 xl:grid-cols-[minmax(0,1.45fr)_minmax(0,0.85fr)_280px]">
                        <article class="border-b border-zinc-200 pb-8 xl:border-b-0">
                            <a href="{{ $project->publicArticleUrl($featuredArticle) }}" class="block overflow-hidden bg-zinc-100">
                                <img
                                    src="{{ $resolveImage($featuredArticle, 0) }}"
                                    alt="{{ $featuredArticle->featured_image_alt ?: $featuredArticle->title }}"
                                    loading="eager"
                                    class="aspect-[4/3] w-full object-cover transition duration-500 hover:scale-[1.02]"
                                >
                            </a>

                            <div class="mt-5 flex flex-wrap items-center gap-2 text-xs font-medium uppercase tracking-[0.2em] text-zinc-500">
                                <span>{{ $featuredArticle->category?->name ?: $project->niche }}</span>
                                <span>&middot;</span>
                                <span>{{ $featuredArticle->published_at?->format('d/m/Y') }}</span>
                            </div>
                            <h2 class="mt-4 max-w-3xl text-3xl font-semibold tracking-tight md:text-4xl">
                                <a href="{{ $project->publicArticleUrl($featuredArticle) }}" class="transition hover:text-emerald-700">{{ $featuredArticle->title }}</a>
                            </h2>
                            <p class="mt-4 max-w-3xl text-base leading-8 text-zinc-600">{!! $highlightText($featuredArticle->excerpt ?: $featuredArticle->meta_description, 240) !!}</p>
                            <a href="{{ $project->publicArticleUrl($featuredArticle) }}" class="mt-5 inline-flex items-center text-sm font-semibold text-emerald-700 transition hover:text-emerald-900">Ler analise</a>
                        </article>

                        <div class="space-y-8 border-b border-zinc-200 pb-8 xl:border-b-0">
                            @foreach ($secondaryArticles as $index => $article)
                                <article>
                                    <a href="{{ $project->publicArticleUrl($article) }}" class="block overflow-hidden bg-zinc-100">
                                        <img
                                            src="{{ $resolveImage($article, $index + 1) }}"
                                            alt="{{ $article->featured_image_alt ?: $article->title }}"
                                            loading="lazy"
                                            class="aspect-[4/3] w-full object-cover transition duration-500 hover:scale-[1.02]"
                                        >
                                    </a>
                                    <div class="mt-4 flex flex-wrap items-center gap-2 text-[11px] font-medium uppercase tracking-[0.18em] text-zinc-500">
                                        <span>{{ $article->category?->name ?: $project->niche }}</span>
                                        <span>&middot;</span>
                                        <span>{{ $article->published_at?->format('d/m/Y') }}</span>
                                    </div>
                                    <h3 class="mt-3 text-2xl font-semibold tracking-tight">
                                        <a href="{{ $project->publicArticleUrl($article) }}" class="transition hover:text-emerald-700">{{ $article->title }}</a>
                                    </h3>
                                    <p class="mt-3 text-sm leading-7 text-zinc-600">{!! $highlightText($article->excerpt ?: $article->meta_description, 120) !!}</p>
                                </article>
                            @endforeach
                        </div>

                        <aside class="space-y-4" id="frentes">
                            <div class="rounded-md border border-zinc-200 p-6">
                                <h3 class="text-2xl font-semibold tracking-tight">Topicos</h3>
                                <div class="mt-5 space-y-3">
                                    @foreach ($project->categories->take(6) as $category)
                                        <a href="{{ $project->publicCategoryUrl($category) }}" class="block text-sm text-zinc-600 transition hover:text-emerald-700">{{ $category->name }}</a>
                                    @endforeach
                                </div>
                            </div>

                            <div class="rounded-md border border-zinc-200 p-6">
                                <h3 class="text-2xl font-semibold tracking-tight">{{ $search !== '' ? 'Busca atual' : 'Visao rapida' }}</h3>
                                <div class="mt-5 space-y-4">
                                    @if ($search !== '')
                                        <div class="rounded-md border border-zinc-200 px-4 py-3 text-sm text-zinc-600">
                                            {{ $search }}
                                        </div>
                                        @if (isset($selectedCategory) && $selectedCategory)
                                            <div class="rounded-md border border-zinc-200 px-4 py-3 text-sm text-zinc-600">
                                                {{ $selectedCategory->name }}
                                            </div>
                                        @endif
                                        <a href="{{ $project->publicIndexUrl() }}" class="inline-flex text-sm font-medium text-emerald-700 transition hover:text-emerald-900">Limpar busca</a>
                                    @else
                                        <div class="space-y-3 text-sm text-zinc-600">
                                            <div class="flex items-center justify-between gap-3 border-b border-zinc-200 pb-3">
                                                <span>Artigos</span>
                                                <span class="font-medium text-zinc-900">{{ $project->articles->count() }}</span>
                                            </div>
                                            @if (filled($project->target_location))
                                                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 pb-3">
                                                    <span>Mercado</span>
                                                    <span class="font-medium text-zinc-900">{{ $project->target_location }}</span>
                                                </div>
                                            @endif
                                            @if (filled($project->target_persona))
                                                <div class="text-sm leading-7 text-zinc-600">{{ $project->target_persona }}</div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="rounded-md border border-zinc-200 p-6">
                                <h3 class="text-xl font-semibold tracking-tight">Conversa estrategica</h3>
                                <p class="mt-4 text-sm leading-7 text-zinc-600">{{ $project->default_cta ?: 'Fale com a nossa equipe para conectar conteudo, automacao e crescimento comercial em uma mesma operacao.' }}</p>
                                @if ($officialUrl)
                                    <a id="conversa" href="{{ $officialUrl }}" target="_blank" rel="noreferrer" class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-emerald-300 px-4 py-3 text-xs font-semibold uppercase tracking-[0.22em] text-zinc-950 transition hover:bg-emerald-200">Site oficial</a>
                                @else
                                    <a id="conversa" href="#top" class="mt-5 inline-flex w-full items-center justify-center rounded-md bg-emerald-300 px-4 py-3 text-xs font-semibold uppercase tracking-[0.22em] text-zinc-950 transition hover:bg-emerald-200">Falar com especialistas</a>
                                @endif
                            </div>
                        </aside>
                    </div>
                </section>
            @endif

            <section class="border-t border-zinc-200 py-10">
                <div class="border-b border-zinc-200 pb-4">
                    <p class="text-sm uppercase tracking-[0.24em] text-zinc-500">{{ $search !== '' ? 'Busca editorial' : 'Biblioteca editorial' }}</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-tight">
                        {{ $search !== '' ? 'Resultados encontrados no BlogIA' : 'Mais leituras para quem decide, opera e escala' }}
                    </h2>
                </div>

                <div class="mt-8 grid gap-x-6 gap-y-10 sm:grid-cols-2 xl:grid-cols-4">
                    @forelse ($visibleGridArticles as $article)
                        <article>
                            <a href="{{ $project->publicArticleUrl($article) }}" class="block overflow-hidden bg-zinc-100">
                                <img
                                    src="{{ $resolveImage($article, $loop->index + 3) }}"
                                    alt="{{ $article->featured_image_alt ?: $article->title }}"
                                    loading="lazy"
                                    class="aspect-[4/3] w-full object-cover transition duration-500 hover:scale-[1.02]"
                                >
                            </a>
                            <div class="mt-4 flex flex-wrap items-center gap-2 text-[11px] font-medium uppercase tracking-[0.18em] text-zinc-500">
                                <span>{{ $article->category?->name ?: $project->niche }}</span>
                                <span>&middot;</span>
                                <span>{{ $article->published_at?->format('d/m/Y') }}</span>
                            </div>
                            <h3 class="mt-3 text-xl font-semibold tracking-tight">
                                <a href="{{ $project->publicArticleUrl($article) }}" class="transition hover:text-emerald-700">{{ $article->title }}</a>
                            </h3>
                            <p class="mt-3 text-sm leading-7 text-zinc-600">{!! $highlightText($article->excerpt ?: $article->meta_description, 120) !!}</p>
                        </article>
                    @empty
                        <div class="col-span-full py-12 text-center">
                            <p class="font-medium">{{ $search !== '' ? 'Nenhum artigo encontrado para essa busca.' : 'Ainda nao ha artigos publicados.' }}</p>
                            @if ($search !== '')
                                <p class="mt-3 text-sm text-zinc-500">Tente buscar por outro termo, remover o filtro de categoria ou navegar pelos topicos em destaque.</p>
                                <div class="mt-5 flex flex-wrap justify-center gap-2">
                                    @foreach ($project->categories->take(4) as $category)
                                        <a href="{{ $project->publicIndexUrl(['category' => $category->slug]) }}" class="rounded-full border border-zinc-200 px-4 py-2 text-xs font-medium text-zinc-600 transition hover:border-emerald-300 hover:text-emerald-700">
                                            {{ $category->name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforelse
                </div>

                @if ($hasMoreGridArticles)
                    <div class="mt-10 flex justify-center">
                        <a
                            href="{{ $project->publicIndexUrl([
                                'search' => $search !== '' ? $search : null,
                                'category' => isset($selectedCategory) && $selectedCategory ? $selectedCategory->slug : null,
                                'flow' => $nextFlowLimit,
                            ]) }}#biblioteca"
                            class="inline-flex items-center rounded-md bg-emerald-300 px-6 py-3 text-xs font-semibold uppercase tracking-[0.22em] text-zinc-950 transition hover:bg-emerald-200"
                        >
                            Ver mais artigos
                        </a>
                    </div>
                @endif
            </section>
        </main>

        <footer class="mt-8 bg-zinc-950 text-white">
            <div class="mx-auto grid max-w-7xl gap-10 px-5 py-12 md:px-8 lg:grid-cols-[1.2fr_0.9fr_0.9fr_1fr]">
                <div>
                    <p class="text-xs font-medium uppercase tracking-[0.32em] text-emerald-300">Inovaforce</p>
                    <h2 class="mt-4 text-2xl font-semibold tracking-tight">{{ $project->name }}</h2>
                    <p class="mt-4 max-w-sm text-sm leading-7 text-zinc-300">{{ $pageDescription }}</p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-[0.22em] text-zinc-400">Categorias</h3>
                    <div class="mt-4 space-y-3">
                        @foreach ($project->categories->take(6) as $category)
                            <a href="{{ $project->publicCategoryUrl($category) }}" class="block text-sm text-zinc-300 transition hover:text-white">{{ $category->name }}</a>
                        @endforeach
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-[0.22em] text-zinc-400">Navegacao</h3>
                    <div class="mt-4 space-y-3 text-sm text-zinc-300">
                        <a href="#leituras" class="block transition hover:text-white">Leituras</a>
                        <a href="#frentes" class="block transition hover:text-white">Frentes</a>
                        <a href="#biblioteca" class="block transition hover:text-white">Biblioteca</a>
                        <a href="#top" class="block transition hover:text-white">Buscar no blog</a>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold uppercase tracking-[0.22em] text-zinc-400">Contato</h3>
                    <div class="mt-4 space-y-3 text-sm text-zinc-300">
                        <p>{{ $project->niche }}</p>
                        @if (filled($project->target_persona))
                            <p>{{ $project->target_persona }}</p>
                        @endif
                        @if (filled($project->target_location))
                            <p>{{ $project->target_location }}</p>
                        @endif
                        @if ($officialUrl)
                            <a href="{{ $officialUrl }}" target="_blank" rel="noreferrer" class="inline-flex pt-3 font-medium text-emerald-300 transition hover:text-white">Visitar site oficial</a>
                        @elseif (filled($project->default_cta))
                            <a href="#conversa" class="inline-flex pt-3 font-medium text-emerald-300 transition hover:text-white">Abrir conversa estrategica</a>
                        @endif
                    </div>
                </div>
            </div>
        </footer>
    </body>
</html>
