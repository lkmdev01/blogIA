<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $project->name }} - BlogIA</title>
        <meta name="description" content="{{ $project->description }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#f7f3e8] text-zinc-950 antialiased">
        <main class="mx-auto max-w-6xl px-6 py-12">
            <header class="relative overflow-hidden rounded-[2.5rem] bg-zinc-950 p-8 text-white shadow-2xl shadow-zinc-950/10 md:p-12">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(132,204,22,0.34),transparent_24rem)]"></div>
                <div class="relative">
                    <p class="text-sm font-medium uppercase tracking-[0.3em] text-lime-300">{{ $project->niche }}</p>
                    <h1 class="mt-4 max-w-4xl text-4xl font-semibold tracking-tight md:text-6xl">{{ $project->name }}</h1>
                    <p class="mt-5 max-w-2xl text-lg text-zinc-300">{{ $project->description }}</p>
                    <div class="mt-8 flex flex-wrap gap-2">
                        @foreach ($project->primary_keywords ?? [] as $keyword)
                            <span class="rounded-full bg-white/10 px-4 py-2 text-sm text-zinc-200">{{ $keyword }}</span>
                        @endforeach
                    </div>
                </div>
            </header>

            <section class="mt-10">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">Pilares SEO</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    @foreach ($project->pillars as $pillar)
                        <article class="rounded-[1.5rem] bg-white/90 p-5 shadow-sm ring-1 ring-zinc-200">
                            <p class="text-xs font-semibold uppercase tracking-wide text-lime-700">{{ $pillar->primary_keyword }}</p>
                            <h3 class="mt-2 text-xl font-semibold">{{ $pillar->title }}</h3>
                            <p class="mt-2 text-sm text-zinc-600">{{ $pillar->description }}</p>
                            <p class="mt-4 text-xs text-zinc-500">{{ $pillar->clusters->count() }} clusters relacionados</p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="mt-10 grid gap-6 md:grid-cols-[220px_1fr]">
                <aside class="space-y-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">Categorias</h2>
                    @foreach ($project->categories as $category)
                        <a href="{{ route('blogs.category', [$project->slug, $category->slug]) }}" class="block rounded-2xl bg-white px-4 py-3 text-sm font-medium shadow-sm hover:bg-lime-100">{{ $category->name }}</a>
                    @endforeach
                </aside>

                <div class="grid gap-5">
                    @forelse ($project->articles as $article)
                        <article class="rounded-[1.5rem] bg-white p-6 shadow-sm ring-1 ring-zinc-200">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                <span>{{ $article->published_at?->format('d/m/Y') }}</span>
                                <span>·</span>
                                <span>{{ $article->category?->name }}</span>
                                <span>·</span>
                                <span>{{ $article->focus_keyword }}</span>
                            </div>
                            <h2 class="mt-3 text-2xl font-semibold tracking-tight">
                                <a href="{{ route('blogs.article', [$project->slug, $article->slug]) }}" class="hover:text-lime-700">{{ $article->title }}</a>
                            </h2>
                            <p class="mt-3 text-zinc-600">{{ $article->excerpt ?: $article->meta_description }}</p>
                            <a href="{{ route('blogs.article', [$project->slug, $article->slug]) }}" class="mt-5 inline-flex rounded-full bg-zinc-950 px-5 py-2 text-sm font-medium text-white hover:bg-lime-700">Ler artigo</a>
                        </article>
                    @empty
                        <div class="rounded-[1.5rem] bg-white p-10 text-center shadow-sm ring-1 ring-zinc-200">
                            <p class="font-medium">Ainda nao ha artigos publicados.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </main>
    </body>
</html>
