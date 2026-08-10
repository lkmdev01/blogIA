<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $article->seo_title ?: $article->title }}</title>
        <meta name="description" content="{{ $article->meta_description }}">
        <script type="application/ld+json">
            {!! json_encode([
                '@'.'context' => 'https://schema.org',
                '@type' => 'BlogPosting',
                'headline' => $article->title,
                'description' => $article->meta_description,
                'datePublished' => $article->published_at?->toAtomString(),
                'dateModified' => $article->updated_at?->toAtomString(),
                'author' => ['@type' => 'Organization', 'name' => $project->name],
                'publisher' => ['@type' => 'Organization', 'name' => $project->name],
                'mainEntityOfPage' => route('blogs.article', [$project->slug, $article->slug]),
                'image' => $article->featured_image_path,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-stone-50 text-zinc-950 antialiased">
        <main class="mx-auto max-w-4xl px-6 py-12">
            <nav class="flex flex-wrap gap-2 text-sm font-medium text-lime-700">
                <a href="{{ route('blogs.index', $project->slug) }}">Blog</a>
                @if ($article->category)
                    <span>/</span>
                    <a href="{{ route('blogs.category', [$project->slug, $article->category->slug]) }}">{{ $article->category->name }}</a>
                @endif
                <span>/</span>
                <span class="text-zinc-500">{{ $article->title }}</span>
            </nav>

            <article class="mt-6 rounded-[2rem] bg-white p-7 shadow-sm ring-1 ring-zinc-200 md:p-12">
                <header>
                    <p class="text-sm font-medium uppercase tracking-[0.25em] text-lime-700">{{ $article->category?->name ?: $project->niche }}</p>
                    <h1 class="mt-4 text-4xl font-semibold tracking-tight md:text-5xl">{{ $article->title }}</h1>
                    <p class="mt-5 text-lg text-zinc-600">{{ $article->excerpt ?: $article->meta_description }}</p>
                    <div class="mt-6 flex flex-wrap gap-2 text-sm text-zinc-500">
                        <span>{{ $article->published_at?->format('d/m/Y') }}</span>
                        <span>·</span>
                        <span>{{ $article->word_count }} palavras</span>
                        <span>·</span>
                        <span>{{ $article->focus_keyword }}</span>
                    </div>
                </header>

                @if ($article->featured_image_path)
                    <img src="{{ $article->featured_image_path }}" alt="{{ $article->featured_image_alt }}" class="mt-8 aspect-[16/9] w-full rounded-[1.5rem] object-cover">
                @endif

                <div class="blogia-prose mt-10">
                    @php
                        $html = \Illuminate\Support\Str::markdown($article->content ?: '', ['html_input' => 'strip']);
                        $html = preg_replace('/^\s*<h1[^>]*>.*?<\/h1>\s*/is', '', $html, 1);
                    @endphp

                    {!! $html !!}
                </div>

                @if ($article->internalLinks->isNotEmpty())
                    <aside class="mt-10 rounded-[1.5rem] bg-stone-50 p-6">
                        <h2 class="font-semibold">Continue lendo</h2>
                        <div class="mt-4 grid gap-3">
                            @foreach ($article->internalLinks as $link)
                                <a href="{{ route('blogs.article', [$project->slug, $link->linkedArticle->slug]) }}" class="rounded-2xl bg-white p-4 text-sm font-medium shadow-sm hover:bg-lime-100">{{ $link->linkedArticle->title }}</a>
                            @endforeach
                        </div>
                    </aside>
                @endif
            </article>
        </main>
    </body>
</html>
