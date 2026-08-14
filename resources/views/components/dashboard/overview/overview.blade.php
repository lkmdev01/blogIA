<section class="space-y-8" wire:poll.10s>
    <div class="relative overflow-hidden rounded-[2.25rem] border border-white/70 bg-zinc-950 p-8 text-white shadow-2xl shadow-zinc-950/10 dark:border-zinc-800">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_20%,rgba(132,204,22,0.38),transparent_22rem),radial-gradient(circle_at_85%_15%,rgba(56,189,248,0.28),transparent_24rem)]"></div>
        <div class="absolute -bottom-32 right-10 h-72 w-72 rounded-full bg-lime-300/15 blur-3xl"></div>
        <div class="relative max-w-4xl space-y-4">
            <p class="text-sm font-semibold uppercase tracking-[0.3em] text-lime-200">BlogIA SEO Engine</p>
            <h1 class="text-4xl font-semibold tracking-tight md:text-6xl">Motor automatico de conteudo para ranquear e fortalecer marca.</h1>
            <p class="max-w-3xl text-base leading-7 text-zinc-300 md:text-lg">Crie projetos, gere clusters, transforme pautas em artigos completos com Groq, publique por agenda e mantenha interlinkagem e sitemap atualizados.</p>
            <div class="flex flex-wrap gap-2 pt-3 text-xs text-zinc-100">
                <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 backdrop-blur">Pautas SEO</span>
                <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 backdrop-blur">Artigos completos</span>
                <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 backdrop-blur">Interlinkagem</span>
                <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 backdrop-blur">Sitemap automatico</span>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-4">
        @foreach ([
            'Projetos' => $this->stats['projects'],
            'Posts' => $this->stats['posts'],
            'Publicados' => $this->stats['published'],
            'Agendados' => $this->stats['scheduled'],
            'Pendentes' => $this->stats['pending_content'],
            'Na fila' => $this->stats['queued'],
            'Fallback' => $this->stats['fallback_articles'],
            'Palavras-chave' => $this->stats['keywords'],
            'Clusters' => $this->stats['clusters'],
        ] as $label => $value)
            <div wire:key="dashboard-stat-{{ $label }}" class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-sm shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/80">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_0.8fr]">
        <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Projeto central</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Edite o blog principal e acompanhe a frente editorial que responde na home publica.</p>
                </div>
                <flux:button :href="route('projects.index')" wire:navigate variant="primary">Abrir projeto</flux:button>
            </div>

            <div class="mt-6 divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->projects as $project)
                    <div wire:key="dashboard-project-{{ $project->id }}" class="flex flex-col gap-4 py-5 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('projects.show', $project) }}" wire:navigate class="text-lg font-semibold text-zinc-950 hover:underline dark:text-white">{{ $project->name }}</a>
                                @if ($project->isPrimaryPublicProject())
                                    <span class="rounded-full bg-lime-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-lime-800 dark:bg-lime-400/10 dark:text-lime-200">Blog principal</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $project->niche }} · {{ implode(', ', $project->primary_keywords ?? []) }}</p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                                <span class="rounded-full bg-zinc-100 px-3 py-1 dark:bg-zinc-800">{{ $project->articles_count }} artigos</span>
                                <span class="rounded-full bg-zinc-100 px-3 py-1 dark:bg-zinc-800">{{ $project->clusters_count }} clusters</span>
                                <span class="rounded-full bg-zinc-100 px-3 py-1 dark:bg-zinc-800">{{ $project->published_articles_count }} publicados</span>
                                <span class="rounded-full bg-zinc-100 px-3 py-1 dark:bg-zinc-800">{{ $project->scheduled_articles_count }} agendados</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <flux:button size="sm" wire:click="generateStrategy({{ $project->id }})" wire:loading.attr="disabled" wire:target="generateStrategy({{ $project->id }})">
                                <span wire:loading.remove wire:target="generateStrategy({{ $project->id }})">Gerar pauta + artigos</span>
                                <span wire:loading wire:target="generateStrategy({{ $project->id }})">Gerando...</span>
                            </flux:button>
                            <flux:button size="sm" variant="ghost" :href="$project->publicIndexUrl()" target="_blank">Ver blog</flux:button>
                        </div>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                        <p class="font-medium text-zinc-950 dark:text-white">Nenhum projeto criado ainda.</p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Crie seu primeiro blog e gere a pauta SEO automaticamente.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Status da geracao IA</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Ultimos artigos no funil de pauta, rascunho, agenda e publicacao.</p>

            <div class="mt-6 space-y-4">
                @forelse ($this->recentArticles as $article)
                    <a wire:key="dashboard-article-{{ $article->id }}" href="{{ route('articles.edit', $article) }}" wire:navigate class="block rounded-2xl border border-zinc-100 bg-white/70 p-4 transition hover:border-lime-400 hover:bg-lime-50/70 dark:border-zinc-800 dark:bg-zinc-950/30 dark:hover:bg-zinc-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-zinc-950 dark:text-white">{{ $article->title }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $article->project->name }} · {{ $article->focus_keyword }}</p>
                            </div>
                            <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $article->status }}</span>
                        </div>
                    </a>
                @empty
                    <p class="rounded-2xl bg-zinc-50 p-5 text-sm text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">Gere uma pauta para ver artigos aqui.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>
