<section class="grid gap-8 xl:grid-cols-[0.95fr_1.05fr]">
    <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-lime-700 dark:text-lime-300">Nova frente editorial</p>
        <h1 class="mt-3 text-4xl font-semibold tracking-tight text-zinc-950 dark:text-white">Cadastrar projeto</h1>
        <p class="mt-2 text-sm leading-6 text-zinc-500 dark:text-zinc-400">Configure nicho, tom, frequencia e automacoes. Imagens ficam desativadas por padrao para voce criar depois no editor.</p>

        <form wire:submit="createProject" class="mt-8 space-y-5">
            <flux:input wire:model="name" label="Nome do projeto" placeholder="BlogIA" />
            <flux:input wire:model="domain" label="Dominio" placeholder="blogia.test" />
            <flux:input wire:model="target_location" label="Cidade ou area alvo" placeholder="Guaruja" />
            <flux:input wire:model="search_console_property" label="Property do Search Console" placeholder="sc-domain:meusite.com.br" />
            <flux:input wire:model="niche" label="Nicho" placeholder="IA para empresas" />

            <flux:textarea wire:model="description" label="Descricao" rows="3" />
            <flux:textarea
                wire:model="hero_description"
                label="Texto do Hero"
                rows="3"
                placeholder="Conteudos sobre inteligencia artificial aplicada a empresas, com foco em automacao, produtividade e crescimento comercial."
            />
            <flux:input
                wire:model="hero_image_url"
                label="Imagem do Hero (URL)"
                placeholder="https://images.unsplash.com/..."
            />

            <flux:textarea wire:model="primaryKeywords" label="Palavras-chave principais" rows="3" placeholder="ia para empresas, automacao de marketing, conteudo seo" />

            <div class="grid gap-4 md:grid-cols-2">
                <flux:input wire:model="writing_tone" label="Tom da escrita" />
                <flux:input wire:model="language" label="Idioma" />
                <flux:input wire:model="target_country" label="Pais alvo (ISO-3)" placeholder="BRA" maxlength="3" />
                <flux:input wire:model="google_trends_country" label="Pais do Trends (ISO-2)" placeholder="BR" maxlength="2" />
                <flux:input wire:model="google_trends_region" label="Regiao do Trends" placeholder="Sao Paulo" />
                <flux:input wire:model="average_article_words" label="Tamanho medio" type="number" />
                <flux:input wire:model="posts_per_day" label="Posts por dia" type="number" min="1" max="10" />
                <flux:input wire:model="posting_frequency" label="Frequencia" placeholder="daily" />
                <flux:input wire:model="blog_type" label="Tipo de blog" placeholder="authority" />
            </div>

            <div class="rounded-3xl border border-zinc-200 bg-white/70 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                <h2 class="font-semibold text-zinc-950 dark:text-white">Controle da IA e fila</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Use lotes menores e delay maior para trabalhar bem com a Groq gratis.</p>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <flux:select wire:model="ai_provider" label="Provedor de conteudo">
                        <flux:select.option value="groq">Groq com fallback</flux:select.option>
                        <flux:select.option value="fallback">Fallback local</flux:select.option>
                    </flux:select>

                    <flux:select wire:model="article_depth" label="Profundidade do artigo">
                        <flux:select.option value="concise">Conciso</flux:select.option>
                        <flux:select.option value="standard">Padrao SEO</flux:select.option>
                        <flux:select.option value="deep">Profundo</flux:select.option>
                    </flux:select>

                    <flux:input wire:model="generation_batch_size" label="Artigos por lote" type="number" min="1" max="20" />
                    <flux:input wire:model="generation_delay_seconds" label="Intervalo entre artigos (segundos)" type="number" min="0" max="3600" />
                    <flux:input wire:model="h2_count" label="Quantidade de H2" type="number" min="3" max="12" />
                    <flux:input wire:model="h3_count" label="H3 por secao" type="number" min="0" max="5" />
                    <flux:input wire:model="target_persona" label="Persona alvo" placeholder="gestores e empreendedores" />
                    <flux:textarea wire:model="default_cta" label="CTA padrao" rows="3" />
                </div>
            </div>

            <div class="grid gap-3 rounded-3xl border border-lime-900/10 bg-lime-50/70 p-4 text-sm dark:border-lime-300/10 dark:bg-lime-300/5">
                <flux:checkbox wire:model="enable_interlinking" label="Gerar interlinkagem automaticamente" />
                <flux:checkbox wire:model="auto_generate_content" label="Gerar artigos completos automaticamente" />
                <flux:checkbox wire:model="auto_publish" label="Publicar automaticamente quando chegar a agenda" />
                <flux:checkbox wire:model="include_faq" label="Adicionar FAQ ao artigo" />
                <flux:checkbox wire:model="generate_images" label="Gerar imagens automaticamente (deixe desligado por enquanto)" />
            </div>

            <flux:button type="submit" variant="primary" class="w-full" wire:loading.attr="disabled" wire:target="createProject">
                <span wire:loading.remove wire:target="createProject">Criar projeto</span>
                <span wire:loading wire:target="createProject">Criando...</span>
            </flux:button>
        </form>
    </div>

    <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-zinc-950 dark:text-white">Meus projetos</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Projetos configurados para operar o blog principal.</p>
            </div>
        </div>

        <div class="mt-6 grid gap-4">
            @forelse ($this->projects as $project)
                <a wire:key="project-card-{{ $project->id }}" href="{{ route('projects.show', $project) }}" wire:navigate class="rounded-3xl border border-zinc-200/80 bg-white/70 p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-lime-400 hover:bg-lime-50/70 dark:border-zinc-800 dark:bg-zinc-950/30 dark:hover:bg-zinc-800">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-semibold text-zinc-950 dark:text-white">{{ $project->name }}</h3>
                                @if ($project->isPrimaryPublicProject())
                                    <span class="rounded-full bg-lime-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-lime-800 dark:bg-lime-400/10 dark:text-lime-200">Blog principal</span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $project->niche }}
                                @if ($project->target_location)
                                    - {{ $project->target_location }}
                                @endif
                            </p>
                            @if ($project->search_console_property)
                                <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">{{ $project->search_console_property }}</p>
                            @endif
                            @if ($project->google_trends_country)
                                <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">Trends {{ $project->google_trends_country }}@if($project->google_trends_region) - {{ $project->google_trends_region }}@endif</p>
                            @endif
                        </div>
                        <span class="rounded-full bg-lime-100 px-3 py-1 text-xs font-medium text-lime-800 dark:bg-lime-400/10 dark:text-lime-200">{{ $project->language }}</span>
                    </div>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                        <span class="rounded-full bg-zinc-100 px-3 py-1 dark:bg-zinc-800">{{ $project->articles_count }} artigos</span>
                        <span class="rounded-full bg-zinc-100 px-3 py-1 dark:bg-zinc-800">{{ $project->pillars_count }} pilares</span>
                        <span class="rounded-full bg-zinc-100 px-3 py-1 dark:bg-zinc-800">{{ $project->clusters_count }} clusters</span>
                    </div>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                    <p class="font-medium text-zinc-950 dark:text-white">Nenhum projeto ainda.</p>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Use o formulario ao lado para criar o primeiro.</p>
                </div>
            @endforelse
        </div>
    </div>
</section>
