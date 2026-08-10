<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $category->seo_title ?: $category->name }} - {{ $project->name }}</title>
        <meta name="description" content="{{ $category->seo_description ?: $category->description }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-stone-50 text-zinc-950 antialiased">
        <main class="mx-auto max-w-5xl px-6 py-12">
            <nav class="flex flex-wrap gap-2 text-sm font-medium text-lime-700">
                <a href="{{ route('blogs.index', $project->slug) }}">Blog</a>
                <span>/</span>
                <span class="text-zinc-500">{{ $category->name }}</span>
            </nav>
            <header class="mt-6 rounded-[2rem] bg-white p-8 shadow-sm ring-1 ring-zinc-200">
                <p class="text-sm font-medium uppercase tracking-[0.25em] text-lime-700">Categoria</p>
                <h1 class="mt-3 text-4xl font-semibold">{{ $category->name }}</h1>
                <p class="mt-3 max-w-2xl text-zinc-600">{{ $category->description }}</p>
            </header>

            <section class="mt-8 grid gap-5">
                @forelse ($articles as $article)
                    <article class="rounded-[1.5rem] bg-white p-6 shadow-sm ring-1 ring-zinc-200">
                        <p class="text-xs text-zinc-500">{{ $article->published_at?->format('d/m/Y') }} · {{ $article->focus_keyword }}</p>
                        <h2 class="mt-3 text-2xl font-semibold"><a href="{{ route('blogs.article', [$project->slug, $article->slug]) }}" class="hover:text-lime-700">{{ $article->title }}</a></h2>
                        <p class="mt-3 text-zinc-600">{{ $article->excerpt ?: $article->meta_description }}</p>
                    </article>
                @empty
                    <p class="rounded-[1.5rem] bg-white p-8 text-zinc-600 shadow-sm ring-1 ring-zinc-200">Nenhum artigo publicado nesta categoria.</p>
                @endforelse
            </section>
        </main>
    </body>
</html>
