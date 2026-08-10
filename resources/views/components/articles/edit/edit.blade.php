<section class="space-y-6">
    <div class="relative overflow-hidden rounded-[2rem] border border-white/70 bg-zinc-950 p-6 text-white shadow-2xl shadow-zinc-950/10 dark:border-zinc-800">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(132,204,22,0.3),transparent_22rem)]"></div>
        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.25em] text-lime-200">{{ $article->project->name }}</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight">Editor de artigo</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-300">Ajuste SEO, conteudo, agenda e imagem manualmente. A IA nao gera imagem nesta fase.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button wire:click="save" variant="primary">Salvar</flux:button>
                <flux:button wire:click="regenerate" wire:loading.attr="disabled" wire:target="regenerate">
                    <span wire:loading.remove wire:target="regenerate">Regenerar IA</span>
                    <span wire:loading wire:target="regenerate">Gerando...</span>
                </flux:button>
                <flux:button wire:click="refreshLinks" variant="ghost">Atualizar links</flux:button>
                <flux:button wire:click="publish" variant="ghost">Publicar</flux:button>
                <flux:button :href="route('blogs.article', [$article->project->slug, $article->slug])" target="_blank" variant="ghost">Ver post</flux:button>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Conteudo</h2>
                <div class="mt-6 space-y-5">
                    <flux:input wire:model="title" label="Titulo SEO" />
                    <flux:input wire:model="slug" label="Slug SEO" />
                    <flux:input wire:model="focus_keyword" label="Palavra-chave principal" />

                    <flux:textarea wire:model="content" label="Conteudo completo em Markdown" rows="24" class="font-mono" />

                    <flux:textarea wire:model="cta" label="CTA final" rows="3" />

                    <div class="rounded-3xl border border-zinc-200 bg-stone-50 p-5 dark:border-zinc-800 dark:bg-zinc-950/50">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Previa formatada</h3>
                        <div class="blogia-prose mt-4">
                            {!! \Illuminate\Support\Str::markdown($content ?: '', ['html_input' => 'strip']) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <aside class="space-y-6">
            <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">SEO</h2>
                <div class="mt-6 space-y-5">
                    <flux:input wire:model="seo_title" label="Meta title" />
                    <flux:textarea wire:model="meta_description" label="Meta description" rows="4" maxlength="170" />
                    <flux:textarea wire:model="excerpt" label="Resumo" rows="4" />
                    <flux:input wire:model="longTailText" label="Long tails" />
                    <flux:input wire:model="tagsText" label="Tags" />
                </div>

                <div class="mt-6 rounded-3xl bg-zinc-50 p-4 dark:bg-zinc-950/50">
                    <h3 class="font-semibold text-zinc-950 dark:text-white">Checklist SEO</h3>
                    <div class="mt-4 space-y-2">
                        @foreach ($this->seoChecklist as $item)
                            <div wire:key="seo-check-{{ $item['label'] }}" class="flex items-center justify-between gap-3 rounded-2xl bg-white px-3 py-2 text-sm dark:bg-zinc-900">
                                <span class="text-zinc-600 dark:text-zinc-300">{{ $item['label'] }}</span>
                                <span class="rounded-full px-2 py-1 text-xs font-medium {{ $item['passed'] ? 'bg-lime-100 text-lime-800 dark:bg-lime-300/10 dark:text-lime-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-300/10 dark:text-amber-200' }}">
                                    {{ $item['passed'] ? 'ok' : 'ajustar' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Publicacao</h2>
                <div class="mt-6 space-y-5">
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Status</label>
                        <select wire:model="status" class="mt-2 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                            <option value="idea">Ideia</option>
                            <option value="draft">Rascunho</option>
                            <option value="scheduled">Agendado</option>
                            <option value="published">Publicado</option>
                        </select>
                    </div>

                    <flux:input wire:model="scheduled_for" label="Data de publicacao" type="datetime-local" />

                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Categoria</label>
                        <select wire:model="category_id" class="mt-2 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                            <option value="">Sem categoria</option>
                            @foreach ($this->categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Imagem manual</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Cole aqui o caminho/URL da imagem quando voce criar depois.</p>
                <div class="mt-6 space-y-5">
                    <flux:input wire:model="featuredImageUpload" label="Upload de imagem" type="file" accept="image/*" />
                    <flux:input wire:model="featured_image_path" label="Imagem destacada" placeholder="/storage/posts/imagem.jpg" />
                    <flux:input wire:model="featured_image_alt" label="Alt text SEO" />

                    @if ($featured_image_path)
                        <img src="{{ $featured_image_path }}" alt="{{ $featured_image_alt }}" class="aspect-[16/9] w-full rounded-3xl object-cover ring-1 ring-zinc-200 dark:ring-zinc-800">
                    @endif
                </div>
            </div>

            <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Interlinkagem</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($article->internalLinks as $link)
                        <a wire:key="internal-link-{{ $link->id }}" href="{{ route('blogs.article', [$article->project->slug, $link->linkedArticle->slug]) }}" target="_blank" class="block rounded-2xl bg-lime-50/70 p-3 text-sm ring-1 ring-lime-900/5 dark:bg-zinc-800 dark:ring-white/5">
                            <span class="block font-medium text-zinc-950 dark:text-white">{{ $link->anchor_text }}</span>
                            <span class="text-xs text-zinc-500">{{ $link->context }} · {{ $link->linkedArticle->title }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Nenhum link interno criado ainda.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </form>
</section>
