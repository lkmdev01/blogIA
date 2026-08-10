<section class="space-y-6">
    <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-lime-700 dark:text-lime-300">Conteudo</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white">Lista de artigos</h1>
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Gerencie ideias, rascunhos, agendamentos, SEO score e publicacao.</p>
            </div>

            <div class="grid gap-3 md:grid-cols-[1fr_170px_170px_auto]">
                <input wire:model.live.debounce.300ms="search" placeholder="Buscar titulo ou keyword" class="rounded-2xl border border-zinc-200 bg-white/90 px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">

                <select wire:model.live="projectId" class="rounded-2xl border border-zinc-200 bg-white/90 px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                    <option value="">Todos projetos</option>
                    @foreach ($this->projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="status" class="rounded-2xl border border-zinc-200 bg-white/90 px-3 py-2 text-sm shadow-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                    <option value="">Todos status</option>
                    <option value="idea">Ideia</option>
                    <option value="draft">Rascunho</option>
                    <option value="scheduled">Agendado</option>
                    <option value="published">Publicado</option>
                </select>

                <flux:button wire:click="generatePending" wire:loading.attr="disabled" wire:target="generatePending" variant="primary">
                    <span wire:loading.remove wire:target="generatePending">Gerar pendentes</span>
                    <span wire:loading wire:target="generatePending">Gerando...</span>
                </flux:button>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-[2rem] border border-white/70 bg-white/90 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
        <div class="grid grid-cols-[1.3fr_0.7fr_120px_150px_190px] gap-4 bg-zinc-50 px-5 py-3 text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 max-xl:hidden">
            <span>Titulo</span>
            <span>Keyword</span>
            <span>Status</span>
            <span>Publicacao</span>
            <span>Acoes</span>
        </div>

        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($this->articles as $article)
                <div wire:key="article-row-{{ $article->id }}" class="grid gap-4 px-5 py-5 transition hover:bg-lime-50/60 dark:hover:bg-zinc-800/60 xl:grid-cols-[1.3fr_0.7fr_120px_150px_190px] xl:items-center">
                    <div>
                        <a href="{{ route('articles.edit', $article) }}" wire:navigate class="font-semibold text-zinc-950 hover:underline dark:text-white">{{ $article->title }}</a>
                        <p class="mt-1 text-xs text-zinc-500">{{ $article->project->name }} - {{ $article->category?->name ?: 'Sem categoria' }} - SEO {{ $article->seo_score ? $article->seo_score.'%' : '-' }}</p>
                        <p class="mt-1 text-xs text-zinc-500">Geracao: {{ $article->generation_status }} @if (data_get($article->source_payload, 'provider') === 'fallback') - fallback usado @endif</p>
                    </div>
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $article->focus_keyword }}</p>
                    <span class="w-fit rounded-full bg-zinc-100 px-3 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">{{ $article->status }}</span>
                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $article->published_at?->format('d/m/Y H:i') ?: $article->scheduled_for?->format('d/m/Y H:i') ?: '-' }}</p>
                    <div class="flex flex-wrap gap-2">
                        <flux:button size="sm" wire:click="generate({{ $article->id }})">Gerar</flux:button>
                        <flux:button size="sm" wire:click="publish({{ $article->id }})" variant="primary">Publicar</flux:button>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center">
                    <p class="font-medium text-zinc-950 dark:text-white">Nenhum artigo encontrado.</p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Gere uma pauta dentro de um projeto para popular a lista.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
