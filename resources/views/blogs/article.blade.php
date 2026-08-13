<!DOCTYPE html>
@php
    $rawHtml = \Illuminate\Support\Str::markdown($article->content ?: '', ['html_input' => 'strip']);
    $rawHtml = preg_replace('/^\s*<h1[^>]*>.*?<\/h1>\s*/is', '', $rawHtml, 1);
    $articleUrl = $project->publicArticleUrl($article);
    $metaTitle = $article->seo_title ?: $article->title;
    $metaDescription = $article->meta_description ?: $article->excerpt ?: $project->description ?: $article->title;
    $featuredImageUrl = blank($article->featured_image_path)
        ? null
        : (\Illuminate\Support\Str::startsWith($article->featured_image_path, ['http://', 'https://'])
            ? $article->featured_image_path
            : url($article->featured_image_path));
    $socialImageUrl = $project->publicArticleSocialImageUrl($article, ['v' => $article->updated_at?->timestamp]);
    $officialUrl = blank($project->domain)
        ? null
        : (\Illuminate\Support\Str::startsWith($project->domain, ['http://', 'https://']) ? $project->domain : 'https://'.$project->domain);
    $ctaUrl = $project->publicArticleCtaUrl($article);
    $relatedArticles = $article->internalLinks
        ->map(fn ($link) => $link->linkedArticle)
        ->filter(fn ($relatedArticle) => $relatedArticle && $relatedArticle->status === 'published' && $relatedArticle->project_id === $project->id)
        ->unique('id')
        ->values();
    $tableOfContents = [];
    $articleHtml = $rawHtml;

    if (filled($rawHtml)) {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previousLibxmlState = libxml_use_internal_errors(true);

        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$rawHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $usedHeadingIds = [];

        foreach (iterator_to_array($dom->getElementsByTagName('h2')) as $heading) {
            $label = trim($heading->textContent);

            if (blank($label)) {
                continue;
            }

            $baseId = \Illuminate\Support\Str::slug($label) ?: 'secao';
            $headingId = $baseId;
            $suffix = 2;

            while (in_array($headingId, $usedHeadingIds, true)) {
                $headingId = "{$baseId}-{$suffix}";
                $suffix++;
            }

            $usedHeadingIds[] = $headingId;
            $heading->setAttribute('id', $headingId);
            $heading->setAttribute('class', trim($heading->getAttribute('class').' scroll-mt-32'));

            $tableOfContents[] = [
                'id' => $headingId,
                'label' => $label,
            ];
        }

        $articleHtml = collect(iterator_to_array($dom->childNodes))
            ->reject(fn (\DOMNode $node): bool => $node->nodeType === XML_PI_NODE)
            ->map(fn (\DOMNode $node): string => $dom->saveHTML($node))
            ->implode('');

        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlState);
    }

    $showTableOfContents = count($tableOfContents) >= 2;
    $schema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $article->title,
        'description' => $metaDescription,
        'datePublished' => $article->published_at?->toAtomString(),
        'dateModified' => $article->updated_at?->toAtomString(),
        'author' => ['@type' => 'Organization', 'name' => $project->name],
        'publisher' => ['@type' => 'Organization', 'name' => $project->name],
        'mainEntityOfPage' => $articleUrl,
        'url' => $articleUrl,
        'image' => $socialImageUrl,
        'articleSection' => $article->category?->name ?: $project->niche,
    ], fn ($value) => filled($value));
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $metaTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ $articleUrl }}">
        <meta property="og:type" content="article">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ $articleUrl }}">
        <meta property="og:site_name" content="{{ $project->name }}">
        <meta property="og:image" content="{{ $socialImageUrl }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="{{ $article->title }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" content="{{ $socialImageUrl }}">
        <meta name="twitter:image:alt" content="{{ $article->title }}">
        <script type="application/ld+json">
            {!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>
        @php($pageType = 'article')
        @include('blogs.partials.analytics', ['project' => $project, 'pageType' => $pageType, 'article' => $article, 'category' => $article->category])
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-stone-50 text-zinc-950 antialiased">
        <main class="mx-auto max-w-6xl px-5 py-5 md:px-8 md:py-8">
            <section class="overflow-hidden rounded-lg bg-zinc-950 text-white">
                <div class="border-b border-white/10 px-6 py-5 md:px-10">
                    <nav class="flex flex-wrap items-center gap-2 text-sm text-zinc-300">
                        <a href="{{ $project->publicIndexUrl() }}" class="font-medium text-emerald-300 transition hover:text-white">{{ $project->name }}</a>
                        @if ($article->category)
                            <span>/</span>
                            <a href="{{ $project->publicCategoryUrl($article->category) }}" class="transition hover:text-white">{{ $article->category->name }}</a>
                        @endif
                        <span>/</span>
                        <span class="text-zinc-400">{{ $article->title }}</span>
                    </nav>
                </div>

                <div class="grid gap-10 px-6 py-10 md:px-10 md:py-12 lg:grid-cols-[minmax(0,1fr)_280px] lg:items-end">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-[0.25em] text-emerald-300">{{ $article->category?->name ?: $project->niche }}</p>
                        <h1 class="mt-4 max-w-4xl text-4xl font-semibold tracking-tight md:text-5xl">{{ $article->title }}</h1>
                        <p class="mt-5 max-w-3xl text-lg leading-8 text-zinc-200">{{ $article->excerpt ?: $article->meta_description }}</p>
                        <div class="mt-6 flex flex-wrap gap-2 text-sm text-zinc-400">
                            <span>{{ $article->published_at?->format('d/m/Y') }}</span>
                            @if ($article->updated_at && $article->updated_at->ne($article->published_at))
                                <span>&middot;</span>
                                <span>Atualizado em {{ $article->updated_at->format('d/m/Y') }}</span>
                            @endif
                            @if ($article->word_count > 0)
                                <span>&middot;</span>
                                <span>{{ max(1, (int) ceil($article->word_count / 200)) }} min de leitura</span>
                            @endif
                        </div>
                    </div>

                    <aside class="border-t border-white/15 pt-6 lg:border-t-0 lg:border-l lg:pl-8 lg:pt-0">
                        <p class="text-sm uppercase tracking-[0.2em] text-zinc-400">Direcao executiva</p>
                        <p class="mt-3 text-sm leading-7 text-zinc-200">{{ $project->default_cta ?: 'Conecte tecnologia, marketing e operacao em uma unica estrategia de crescimento.' }}</p>
                    </aside>
                </div>
            </section>

            <section class="grid gap-10 py-10 lg:grid-cols-[minmax(0,1fr)_280px]">
                <article>
                    @if ($article->featured_image_path)
                        <img src="{{ $featuredImageUrl }}" alt="{{ $article->featured_image_alt ?: $article->title }}" class="aspect-[16/9] w-full rounded-lg object-cover">
                    @endif

                    @if ($showTableOfContents)
                        <div class="mt-8 rounded-md border border-zinc-200 bg-white px-5 py-5 lg:hidden">
                            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-zinc-500">Neste artigo</p>
                            <div class="mt-4 flex flex-col gap-3">
                                @foreach ($tableOfContents as $item)
                                    <a
                                        href="#{{ $item['id'] }}"
                                        data-article-toc-link
                                        class="text-sm transition hover:text-emerald-700 {{ $loop->first ? 'font-semibold text-emerald-700' : 'font-medium text-zinc-700' }}"
                                        @if ($loop->first) aria-current="true" @endif
                                    >{{ $item['label'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="blogia-prose mt-10">
                        {!! $articleHtml !!}
                    </div>

                    <section class="mt-12 rounded-lg border border-emerald-200 bg-emerald-50 px-6 py-7">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-700">Diagnostico estrategico</p>
                        <h2 class="mt-3 text-2xl font-semibold tracking-tight text-zinc-950">Transforme esse tema em crescimento previsivel</h2>
                        <p class="mt-4 max-w-3xl text-sm leading-7 text-zinc-600">
                            {{ $article->cta ?: $project->default_cta ?: 'Leve esse aprendizado para uma operacao pratica com diagnostico, plano editorial e automacao conectada ao comercial.' }}
                        </p>
                        <div class="mt-5 grid gap-3 text-sm text-zinc-700 md:grid-cols-3">
                            <div class="rounded-md border border-emerald-200 bg-white px-4 py-4">
                                <p class="font-medium text-zinc-950">Diagnostico editorial</p>
                                <p class="mt-2 leading-6">Mapeamento rapido de oportunidades SEO, pauta e prioridades comerciais.</p>
                            </div>
                            <div class="rounded-md border border-emerald-200 bg-white px-4 py-4">
                                <p class="font-medium text-zinc-950">Conversa executiva</p>
                                <p class="mt-2 leading-6">Defina o que gera demanda agora, o que entra em automacao e o que precisa de prova tecnica.</p>
                            </div>
                            <div class="rounded-md border border-emerald-200 bg-white px-4 py-4">
                                <p class="font-medium text-zinc-950">Plano de execucao</p>
                                <p class="mt-2 leading-6">Receba um caminho objetivo para transformar conteudo em canal de aquisicao.</p>
                            </div>
                        </div>
                        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm text-zinc-600">Operacao orientada por SEO, automacao e conversao, sem conteudo solto.</p>
                            <a href="{{ $ctaUrl }}" data-analytics-event="blog_cta_click" data-analytics-location="article_final" data-analytics-label="Solicitar diagnostico" class="inline-flex items-center justify-center rounded-md bg-emerald-300 px-5 py-3 text-xs font-semibold uppercase tracking-[0.22em] text-zinc-950 transition hover:bg-emerald-200">
                                Solicitar diagnostico
                            </a>
                        </div>
                    </section>
                </article>

                <aside class="border-t border-zinc-200 pt-8 lg:border-t-0 lg:border-l lg:pl-8 lg:pt-0">
                    @if ($showTableOfContents)
                        <div class="border-b border-zinc-200 pb-6">
                            <p class="text-sm uppercase tracking-[0.2em] text-zinc-500">Neste artigo</p>
                            <div class="mt-4 space-y-3">
                                @foreach ($tableOfContents as $item)
                                    <a
                                        href="#{{ $item['id'] }}"
                                        data-article-toc-link
                                        class="block text-sm leading-6 transition hover:text-emerald-700 {{ $loop->first ? 'font-semibold text-emerald-700' : 'text-zinc-600' }}"
                                        @if ($loop->first) aria-current="true" @endif
                                    >{{ $item['label'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (filled($article->cta ?: $project->default_cta))
                        <div class="{{ $showTableOfContents ? 'mt-8' : '' }} border-b border-zinc-200 pb-6">
                            <p class="text-sm uppercase tracking-[0.2em] text-zinc-500">Proximo passo</p>
                            <p class="mt-3 text-sm leading-7 text-zinc-600">{{ $article->cta ?: $project->default_cta }}</p>
                            <a href="{{ $ctaUrl }}" data-analytics-event="blog_cta_click" data-analytics-location="article_sidebar" data-analytics-label="Falar com especialistas" class="mt-5 inline-flex items-center justify-center rounded-md bg-emerald-300 px-4 py-3 text-xs font-semibold uppercase tracking-[0.22em] text-zinc-950 transition hover:bg-emerald-200">
                                Falar com especialistas
                            </a>
                            <p class="mt-3 text-xs leading-5 text-zinc-500">Diagnostico, conversa executiva e plano de acao conectados ao seu nicho.</p>
                        </div>
                    @endif

                    <div class="mt-8 border-b border-zinc-200 pb-6">
                        <p class="text-sm uppercase tracking-[0.2em] text-zinc-500">Sobre a operacao</p>
                        <p class="mt-3 text-sm font-medium text-zinc-950">{{ $project->name }}</p>
                        <p class="mt-2 text-sm leading-7 text-zinc-600">{{ $project->description ?: 'Operacao editorial orientada por dados, SEO e crescimento comercial.' }}</p>
                        <div class="mt-4 space-y-2 text-sm text-zinc-600">
                            <p><span class="font-medium text-zinc-950">Especialidade:</span> {{ $project->niche }}</p>
                            @if (filled($project->target_persona))
                                <p><span class="font-medium text-zinc-950">Persona:</span> {{ $project->target_persona }}</p>
                            @endif
                            @if (filled($project->target_location))
                                <p><span class="font-medium text-zinc-950">Mercado:</span> {{ $project->target_location }}</p>
                            @endif
                        </div>
                        @if ($officialUrl)
                            <a href="{{ $officialUrl }}" target="_blank" rel="noreferrer" class="mt-4 inline-flex text-sm font-medium text-emerald-700 transition hover:text-emerald-900">
                                Conhecer a empresa
                            </a>
                        @endif
                    </div>

                    @if ($relatedArticles->isNotEmpty())
                        <div class="mt-8">
                            <h2 class="text-sm uppercase tracking-[0.2em] text-zinc-500">Leia tambem</h2>
                            <div class="mt-4 space-y-4">
                                @foreach ($relatedArticles as $relatedArticle)
                                    <a href="{{ $project->publicArticleUrl($relatedArticle) }}" class="block border-b border-zinc-200 pb-4 transition hover:text-emerald-700">
                                        <p class="text-sm font-medium">{{ $relatedArticle->title }}</p>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($sameCategoryArticles->isNotEmpty())
                        <div class="mt-8">
                            <h2 class="text-sm uppercase tracking-[0.2em] text-zinc-500">Explorar mesma categoria</h2>
                            <div class="mt-4 space-y-4">
                                @foreach ($sameCategoryArticles as $relatedArticle)
                                    <a href="{{ $project->publicArticleUrl($relatedArticle) }}" class="block border-b border-zinc-200 pb-4 transition hover:text-emerald-700">
                                        <p class="text-sm font-medium">{{ $relatedArticle->title }}</p>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($sameThemeArticles->isNotEmpty())
                        <div class="mt-8">
                            <h2 class="text-sm uppercase tracking-[0.2em] text-zinc-500">Mais sobre este tema</h2>
                            <div class="mt-4 space-y-4">
                                @foreach ($sameThemeArticles as $relatedArticle)
                                    <a href="{{ $project->publicArticleUrl($relatedArticle) }}" class="block border-b border-zinc-200 pb-4 transition hover:text-emerald-700">
                                        <p class="text-sm font-medium">{{ $relatedArticle->title }}</p>
                                        <p class="mt-1 text-xs text-zinc-500">{{ $relatedArticle->focus_keyword }}</p>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            </section>

            @if ($previousArticle || $nextArticle)
                <section class="border-t border-zinc-200 py-10">
                    <div class="grid gap-4 md:grid-cols-2">
                        @if ($previousArticle)
                            <a href="{{ $project->publicArticleUrl($previousArticle) }}" class="rounded-lg border border-zinc-200 bg-white px-5 py-5 transition hover:border-emerald-300 hover:bg-emerald-50/50">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Artigo anterior</p>
                                <p class="mt-3 text-lg font-semibold tracking-tight text-zinc-950">{{ $previousArticle->title }}</p>
                            </a>
                        @endif

                        @if ($nextArticle)
                            <a href="{{ $project->publicArticleUrl($nextArticle) }}" class="rounded-lg border border-zinc-200 bg-white px-5 py-5 transition hover:border-emerald-300 hover:bg-emerald-50/50">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500">Proximo artigo</p>
                                <p class="mt-3 text-lg font-semibold tracking-tight text-zinc-950">{{ $nextArticle->title }}</p>
                            </a>
                        @endif
                    </div>
                </section>
            @endif
        </main>

        @if ($showTableOfContents)
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const tocLinks = Array.from(document.querySelectorAll('[data-article-toc-link]'));
                    const sections = tocLinks
                        .map((link) => document.getElementById(link.getAttribute('href').replace('#', '')))
                        .filter(Boolean);

                    if (tocLinks.length === 0 || sections.length === 0) {
                        return;
                    }

                    const setActiveSection = (activeId) => {
                        tocLinks.forEach((link) => {
                            const isActive = link.getAttribute('href') === `#${activeId}`;

                            link.classList.toggle('text-emerald-700', isActive);
                            link.classList.toggle('font-semibold', isActive);
                            link.classList.toggle('text-zinc-600', !isActive && link.classList.contains('leading-6'));
                            link.classList.toggle('text-zinc-700', !isActive && !link.classList.contains('leading-6'));
                            link.classList.toggle('font-medium', !isActive && !link.classList.contains('leading-6'));

                            if (isActive) {
                                link.setAttribute('aria-current', 'true');
                            } else {
                                link.removeAttribute('aria-current');
                            }
                        });
                    };

                    setActiveSection(sections[0].id);

                    const observer = new IntersectionObserver((entries) => {
                        const visibleEntries = entries
                            .filter((entry) => entry.isIntersecting)
                            .sort((left, right) => left.boundingClientRect.top - right.boundingClientRect.top);

                        if (visibleEntries.length > 0) {
                            setActiveSection(visibleEntries[0].target.id);
                        }
                    }, {
                        rootMargin: '-20% 0px -65% 0px',
                        threshold: [0, 1],
                    });

                    sections.forEach((section) => observer.observe(section));
                });
            </script>
        @endif
    </body>
</html>
