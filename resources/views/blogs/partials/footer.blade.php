@props([
    'project',
    'description',
    'officialUrl' => null,
    'navigationLinks' => [],
    'contactHref' => null,
    'contactLabel' => null,
])

<footer class="mt-8 bg-zinc-950 text-white">
    <div class="mx-auto grid max-w-7xl gap-10 px-5 py-12 md:px-8 lg:grid-cols-[1.2fr_0.9fr_0.9fr_1fr]">
        <div>
            <p class="text-xs font-medium uppercase tracking-[0.32em] text-emerald-300">Inovaforce</p>
            <h2 class="mt-4 text-2xl font-semibold tracking-tight">{{ $project->name }}</h2>
            <p class="mt-4 max-w-sm text-sm leading-7 text-zinc-300">{{ $description }}</p>
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
                @foreach ($navigationLinks as $label => $href)
                    <a href="{{ $href }}" class="block transition hover:text-white">{{ $label }}</a>
                @endforeach
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
                @elseif (filled($contactHref) && filled($contactLabel))
                    <a href="{{ $contactHref }}" class="inline-flex pt-3 font-medium text-emerald-300 transition hover:text-white">{{ $contactLabel }}</a>
                @endif
            </div>
        </div>
    </div>
</footer>
