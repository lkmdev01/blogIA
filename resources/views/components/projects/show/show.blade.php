<section class="space-y-8">
    @php
        $heroDescriptionPlaceholder = $project->hero_description ?: 'Conteudos sobre inteligencia artificial aplicada a empresas, com foco em automacao, produtividade e crescimento comercial.';
        $heroImagePlaceholder = $project->hero_image_url ?: 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=1600&q=80';
    @endphp

    <div class="relative overflow-hidden rounded-[2rem] border border-white/70 bg-zinc-950 p-6 text-white shadow-2xl shadow-zinc-950/10 dark:border-zinc-800">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(132,204,22,0.36),transparent_22rem),linear-gradient(135deg,rgba(255,255,255,0.08),transparent_45%)]"></div>
        <div class="absolute -bottom-24 -right-16 h-64 w-64 rounded-full bg-sky-300/20 blur-3xl"></div>

        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-lime-200">Projeto SEO</p>
                <h1 class="mt-3 text-4xl font-semibold tracking-tight md:text-5xl">{{ $project->name }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-300 md:text-base">{{ $project->description ?: 'Sem descricao.' }}</p>
                <div class="mt-5 flex flex-wrap gap-2 text-xs text-zinc-100">
                    <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 backdrop-blur">{{ $project->niche }}</span>
                    @if ($project->target_location)
                        <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 backdrop-blur">{{ $project->target_location }}</span>
                    @endif
                    @if ($project->target_country)
                        <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 backdrop-blur">{{ $project->target_country }}</span>
                    @endif
                    <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 backdrop-blur">Tom {{ $project->writing_tone }}</span>
                    <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 backdrop-blur">{{ $project->posts_per_day }} post/dia</span>
                    <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1.5 backdrop-blur">Imagens: {{ $project->generate_images ? 'IA' : 'manual' }}</span>
                    <span class="rounded-full border border-lime-200/40 bg-lime-300/15 px-3 py-1.5 text-lime-100">Artigos automaticos: {{ $project->auto_generate_content ? 'ligado' : 'desligado' }}</span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 lg:justify-end">
                <flux:button wire:click="runSignalDiagnostics" wire:loading.attr="disabled" wire:target="runSignalDiagnostics" variant="ghost">
                    <span wire:loading.remove wire:target="runSignalDiagnostics">Testar sinais</span>
                    <span wire:loading wire:target="runSignalDiagnostics">Validando...</span>
                </flux:button>
                <flux:button wire:click="generateGoogleOpportunities" wire:loading.attr="disabled" wire:target="generateGoogleOpportunities" variant="primary">
                    <span wire:loading.remove wire:target="generateGoogleOpportunities">Radar Google + artigos</span>
                    <span wire:loading wire:target="generateGoogleOpportunities">Lendo sinais...</span>
                </flux:button>
                <flux:button wire:click="generateStrategy" wire:loading.attr="disabled" wire:target="generateStrategy" variant="primary">
                    <span wire:loading.remove wire:target="generateStrategy">Gerar pauta + artigos</span>
                    <span wire:loading wire:target="generateStrategy">Gerando...</span>
                </flux:button>
                <flux:button wire:click="generateAllArticles" wire:loading.attr="disabled" wire:target="generateAllArticles">
                    <span wire:loading.remove wire:target="generateAllArticles">Gerar todos pendentes</span>
                    <span wire:loading wire:target="generateAllArticles">Gerando...</span>
                </flux:button>
                <flux:button wire:click="generateNextArticle">Gerar proximo artigo</flux:button>
                <flux:button wire:click="refreshSitemap" variant="ghost">Atualizar sitemap</flux:button>
                <flux:button :href="$project->publicIndexUrl()" target="_blank" variant="ghost">Ver blog</flux:button>
            </div>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-5" wire:poll.5s>
        @foreach ([
            'Artigos' => $this->stats['posts'],
            'Pendentes' => $this->stats['pending_content'],
            'Na fila' => $this->stats['queued'],
            'Rodando' => $this->stats['running'],
            'Concluidos' => $this->stats['completed_generations'],
            'Fallback' => $this->stats['fallback_articles'],
            'Publicados' => $this->stats['published'],
            'Agendados' => $this->stats['scheduled'],
            'Pilares' => $this->stats['pillars'],
            'Clusters' => $this->stats['clusters'],
            'Categorias' => $this->stats['categories'],
        ] as $label => $value)
            <div wire:key="project-stat-{{ $label }}" class="rounded-3xl border border-white/70 bg-white/85 p-5 shadow-sm shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/80">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                <p class="mt-3 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Performance comercial</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Leitura, clique e sinais de conversao do blog publico.</p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                @foreach ([
                    'Leituras publicas' => $this->commercialMetrics['views'],
                    'Cliques em CTA' => $this->commercialMetrics['cta_clicks'],
                    'Artigos com imagem' => $this->commercialMetrics['articles_with_image'],
                    'Artigos com CTA' => $this->commercialMetrics['articles_with_cta'],
                    'Artigos com links' => $this->commercialMetrics['articles_with_links'],
                    'SEO baixo' => $this->commercialMetrics['low_seo_articles'],
                ] as $label => $value)
                    <div class="rounded-3xl bg-zinc-50 p-4 dark:bg-zinc-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                        <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800">
                <div class="grid grid-cols-[1fr_120px_120px] gap-3 bg-zinc-50 px-4 py-3 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:bg-zinc-800">
                    <span>Artigo</span>
                    <span>Leituras</span>
                    <span>CTA</span>
                </div>

                @forelse ($this->topArticles as $article)
                    <a wire:key="top-article-{{ $article->id }}" href="{{ route('articles.edit', $article) }}" wire:navigate class="grid grid-cols-[1fr_120px_120px] gap-3 border-t border-zinc-100 px-4 py-4 text-sm transition hover:bg-lime-50 dark:border-zinc-800 dark:hover:bg-zinc-800">
                        <span>
                            <span class="block font-medium text-zinc-950 dark:text-white">{{ $article->title }}</span>
                            <span class="mt-1 block text-xs text-zinc-500">{{ $article->focus_keyword }}</span>
                        </span>
                        <span class="text-zinc-600 dark:text-zinc-300">{{ $article->public_view_count }}</span>
                        <span class="text-zinc-600 dark:text-zinc-300">{{ $article->cta_click_count }}</span>
                    </a>
                @empty
                    <div class="p-6 text-sm text-zinc-500">Ainda nao ha dados publicos suficientes para ranquear artigos.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Saude editorial</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Use estes alertas para priorizar revisao, distribuicao e conversao.</p>

            <div class="mt-6 space-y-4">
                @foreach ($this->editorialAlerts as $alert)
                    <div class="rounded-3xl border border-zinc-200 bg-white/80 p-5 dark:border-zinc-800 dark:bg-zinc-950/30">
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ $alert['label'] }}</p>
                            <span class="text-2xl font-semibold text-zinc-950 dark:text-white">{{ $alert['count'] }}</span>
                        </div>
                        <p class="mt-3 text-sm leading-7 text-zinc-600 dark:text-zinc-300">{{ $alert['hint'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Radar Google + Search Console + Trends</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Mistura sinais abertos do Google, BigQuery oficial do Trends e queries reais do seu dominio para priorizar pautas com mais chance de gerar demanda.</p>
            </div>
            @if ($this->latestTrendSnapshot)
                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                    Ultima leitura:
                    {{ $this->latestTrendSnapshot['completed_at']?->format('d/m/Y H:i') }}
                </div>
            @endif
        </div>

        @if ($this->latestTrendSnapshot)
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl bg-zinc-50 p-4 dark:bg-zinc-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">Sinais lidos</p>
                    <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $this->latestTrendSnapshot['signal_count'] }}</p>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ implode(' | ', $this->latestTrendSnapshot['queries']) }}</p>
                </div>
                <div class="rounded-3xl bg-zinc-50 p-4 dark:bg-zinc-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">Pautas criadas</p>
                    <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $this->latestTrendSnapshot['created_articles'] }}</p>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Novos rascunhos ou ideias a partir do movimento de busca.</p>
                </div>
                <div class="rounded-3xl bg-zinc-50 p-4 dark:bg-zinc-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">Artigos gerados</p>
                    <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ $this->latestTrendSnapshot['generated_articles'] }}</p>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Conteudos completos que ja nasceram do Radar.</p>
                </div>
            </div>

            <div class="mt-4 rounded-3xl bg-zinc-50 p-4 dark:bg-zinc-950/40">
                <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-600 dark:text-zinc-300">
                    <span class="font-medium text-zinc-950 dark:text-white">Search Console</span>
                    @if ($project->search_console_property)
                        <span>{{ $project->search_console_property }}</span>
                    @else
                        <span>Nao configurado</span>
                    @endif
                    @if (data_get($this->latestTrendSnapshot, 'search_console.country'))
                        <span>|</span>
                        <span>{{ data_get($this->latestTrendSnapshot, 'search_console.country') }}</span>
                    @endif
                    @if ($project->last_search_console_synced_at)
                        <span>|</span>
                        <span>Ultima sync {{ $project->last_search_console_synced_at->format('d/m/Y H:i') }}</span>
                    @endif
                </div>
            </div>

            <div class="mt-4 rounded-3xl bg-zinc-50 p-4 dark:bg-zinc-950/40">
                <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-600 dark:text-zinc-300">
                    <span class="font-medium text-zinc-950 dark:text-white">Google Trends</span>
                    @if ($project->google_trends_country)
                        <span>{{ $project->google_trends_country }}</span>
                    @else
                        <span>Nao configurado</span>
                    @endif
                    @if (data_get($this->latestTrendSnapshot, 'google_trends.region'))
                        <span>|</span>
                        <span>{{ data_get($this->latestTrendSnapshot, 'google_trends.region') }}</span>
                    @endif
                    @if ($project->last_google_trends_synced_at)
                        <span>|</span>
                        <span>Ultima sync {{ $project->last_google_trends_synced_at->format('d/m/Y H:i') }}</span>
                    @endif
                </div>
            </div>

            <div class="mt-6 grid gap-4 xl:grid-cols-2">
                @foreach ($this->latestTrendSnapshot['opportunities'] as $opportunity)
                    <article class="rounded-3xl border border-zinc-100 bg-white/70 p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/35">
                        <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="rounded-full bg-lime-100 px-3 py-1 text-lime-800 dark:bg-lime-300/10 dark:text-lime-200">{{ $opportunity['trend_type'] }}</span>
                            <span>{{ $opportunity['search_intent'] }}</span>
                            <span>|</span>
                            <span>{{ $opportunity['focus_keyword'] }}</span>
                            @if (! empty($opportunity['opportunity_score']))
                                <span>|</span>
                                <span>score {{ $opportunity['opportunity_score'] }}</span>
                            @endif
                        </div>
                        <h3 class="mt-3 text-lg font-semibold text-zinc-950 dark:text-white">{{ $opportunity['title'] }}</h3>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $opportunity['rationale'] }}</p>
                        <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $opportunity['content_angle'] }}</p>
                        @if (! empty($opportunity['source_mix']))
                            <p class="mt-3 text-xs text-zinc-400 dark:text-zinc-500">{{ implode(', ', $opportunity['source_mix']) }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <div class="mt-6 rounded-3xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                <p class="font-medium text-zinc-950 dark:text-white">Nenhum radar gerado ainda.</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Use o Radar para descobrir temas quentes do seu nicho e converter isso em novos posts.</p>
            </div>
        @endif
    </div>

    <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Diagnostico do Radar</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Confira as conexoes e os termos lidos antes de mandar a IA gerar novas pautas.</p>
            </div>
            @if ($signalDiagnostics)
                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                    Seeds: {{ implode(' | ', data_get($signalDiagnostics, 'queries', [])) }}
                </div>
            @endif
        </div>

        @if ($signalDiagnostics)
            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl bg-zinc-50 p-4 dark:bg-zinc-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">Google Suggest</p>
                    <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ data_get($signalDiagnostics, 'suggestions_count', 0) }}</p>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Sugestoes abertas lidas para os seeds do projeto.</p>
                </div>
                <div class="rounded-3xl bg-zinc-50 p-4 dark:bg-zinc-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">Google News</p>
                    <p class="mt-2 text-3xl font-semibold text-zinc-950 dark:text-white">{{ data_get($signalDiagnostics, 'news_count', 0) }}</p>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Titulos recentes usados como contexto de mercado.</p>
                </div>
                <div class="rounded-3xl bg-zinc-50 p-4 dark:bg-zinc-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">Seeds</p>
                    <p class="mt-2 text-lg font-semibold text-zinc-950 dark:text-white">{{ count(data_get($signalDiagnostics, 'queries', [])) }}</p>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ implode(' | ', data_get($signalDiagnostics, 'queries', [])) }}</p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 xl:grid-cols-2">
                <article class="rounded-3xl border border-zinc-100 bg-white/70 p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/35">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <span class="rounded-full px-3 py-1 {{ data_get($signalDiagnostics, 'search_console.configured') ? 'bg-lime-100 text-lime-800 dark:bg-lime-300/10 dark:text-lime-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-300/10 dark:text-amber-200' }}">
                            {{ data_get($signalDiagnostics, 'search_console.configured') ? 'conectado' : 'revisar' }}
                        </span>
                        @if (data_get($signalDiagnostics, 'search_console.property'))
                            <span>{{ data_get($signalDiagnostics, 'search_console.property') }}</span>
                        @endif
                        @if (data_get($signalDiagnostics, 'search_console.country'))
                            <span>|</span>
                            <span>{{ data_get($signalDiagnostics, 'search_console.country') }}</span>
                        @endif
                    </div>
                    <h3 class="mt-3 text-lg font-semibold text-zinc-950 dark:text-white">Search Console</h3>
                    @if (data_get($signalDiagnostics, 'search_console.error_message'))
                        <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">{{ data_get($signalDiagnostics, 'search_console.error_message') }}</p>
                    @else
                        <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                            <div>
                                <p class="font-medium text-zinc-950 dark:text-white">Top queries</p>
                                <p class="mt-1">{{ implode(' | ', data_get($signalDiagnostics, 'search_console.top_queries', [])) ?: 'Nenhuma query retornada.' }}</p>
                            </div>
                            <div>
                                <p class="font-medium text-zinc-950 dark:text-white">Rising queries</p>
                                <p class="mt-1">{{ implode(' | ', data_get($signalDiagnostics, 'search_console.rising_queries', [])) ?: 'Nenhuma rising query retornada.' }}</p>
                            </div>
                        </div>
                    @endif
                </article>

                <article class="rounded-3xl border border-zinc-100 bg-white/70 p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/35">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <span class="rounded-full px-3 py-1 {{ data_get($signalDiagnostics, 'google_trends.configured') ? 'bg-lime-100 text-lime-800 dark:bg-lime-300/10 dark:text-lime-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-300/10 dark:text-amber-200' }}">
                            {{ data_get($signalDiagnostics, 'google_trends.configured') ? 'conectado' : 'revisar' }}
                        </span>
                        @if (data_get($signalDiagnostics, 'google_trends.country'))
                            <span>{{ data_get($signalDiagnostics, 'google_trends.country') }}</span>
                        @endif
                        @if (data_get($signalDiagnostics, 'google_trends.region'))
                            <span>|</span>
                            <span>{{ data_get($signalDiagnostics, 'google_trends.region') }}</span>
                        @endif
                    </div>
                    <h3 class="mt-3 text-lg font-semibold text-zinc-950 dark:text-white">Google Trends BigQuery</h3>
                    @if (data_get($signalDiagnostics, 'google_trends.error_message'))
                        <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">{{ data_get($signalDiagnostics, 'google_trends.error_message') }}</p>
                    @else
                        <div class="mt-4 space-y-3 text-sm text-zinc-600 dark:text-zinc-300">
                            <div>
                                <p class="font-medium text-zinc-950 dark:text-white">Top terms</p>
                                <p class="mt-1">{{ implode(' | ', data_get($signalDiagnostics, 'google_trends.top_terms', [])) ?: 'Nenhum termo retornado.' }}</p>
                            </div>
                            <div>
                                <p class="font-medium text-zinc-950 dark:text-white">Rising terms</p>
                                <p class="mt-1">{{ implode(' | ', data_get($signalDiagnostics, 'google_trends.rising_terms', [])) ?: 'Nenhum rising term retornado.' }}</p>
                            </div>
                        </div>
                    @endif
                </article>
            </div>
        @else
            <div class="mt-6 rounded-3xl border border-dashed border-zinc-300 p-8 text-center dark:border-zinc-700">
                <p class="font-medium text-zinc-950 dark:text-white">Nenhum diagnostico rodado ainda.</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Clique em "Testar sinais" para validar Search Console, BigQuery e ver os termos que vao alimentar a IA.</p>
            </div>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
            <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Estrutura SEO</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Pilares, clusters e artigos relacionados salvos no banco.</p>

            <div class="mt-6 space-y-4">
                @forelse ($this->pillars as $pillar)
                    <div wire:key="pillar-{{ $pillar->id }}" class="rounded-3xl border border-zinc-100 bg-white/70 p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/35">
                        <p class="font-semibold text-zinc-950 dark:text-white">{{ $pillar->title }}</p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $pillar->primary_keyword }}</p>

                        <div class="mt-4 space-y-3">
                            @foreach ($pillar->clusters as $cluster)
                                <div wire:key="cluster-{{ $cluster->id }}" class="rounded-2xl bg-lime-50/70 p-3 ring-1 ring-lime-900/5 dark:bg-zinc-800/80 dark:ring-white/5">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $cluster->title }}</p>
                                        <span class="text-xs text-zinc-500">{{ $cluster->articles->count() }} artigos</span>
                                    </div>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ implode(', ', $cluster->long_tail_keywords ?? []) }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="rounded-2xl bg-zinc-50 p-5 text-sm text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">Clique em "Gerar pauta IA" para criar a arquitetura SEO.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Lista de artigos</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Pauta, rascunhos, agendados e publicados.</p>
                </div>
                <flux:button :href="route('articles.index')" wire:navigate variant="ghost">Ver todos</flux:button>
            </div>

            <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800">
                <div class="grid grid-cols-[1fr_120px_120px] gap-3 bg-zinc-50 px-4 py-3 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:bg-zinc-800">
                    <span>Titulo</span>
                    <span>Status</span>
                    <span>SEO</span>
                </div>

                @forelse ($this->articles as $article)
                    <a wire:key="project-article-{{ $article->id }}" href="{{ route('articles.edit', $article) }}" wire:navigate class="grid grid-cols-[1fr_120px_120px] gap-3 border-t border-zinc-100 px-4 py-4 text-sm transition hover:bg-lime-50 dark:border-zinc-800 dark:hover:bg-zinc-800">
                        <span>
                            <span class="block font-medium text-zinc-950 dark:text-white">{{ $article->title }}</span>
                            <span class="mt-1 block text-xs text-zinc-500">{{ $article->focus_keyword }}</span>
                        </span>
                        <span class="text-zinc-600 dark:text-zinc-300">{{ $article->status }}</span>
                        <span class="text-zinc-600 dark:text-zinc-300">{{ $article->seo_score ? $article->seo_score.'%' : '-' }}</span>
                    </a>
                @empty
                    <div class="p-6 text-sm text-zinc-500">Nenhum artigo criado ainda.</div>
                @endforelse
            </div>
        </div>
    </div>

    <form wire:submit="saveGenerationSettings" class="rounded-[2rem] border border-white/70 bg-white/90 p-6 shadow-xl shadow-zinc-950/5 backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/85">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-zinc-950 dark:text-white">Configuracoes de geracao</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Controle lote, delay, profundidade e fallback do projeto.</p>
            </div>
            <flux:button type="submit" variant="primary">Salvar configuracoes</flux:button>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <flux:select wire:model="ai_provider" label="Provedor">
                <flux:select.option value="groq">Groq com fallback</flux:select.option>
                <flux:select.option value="fallback">Fallback local</flux:select.option>
            </flux:select>
            <flux:select wire:model="article_depth" label="Profundidade">
                <flux:select.option value="concise">Conciso</flux:select.option>
                <flux:select.option value="standard">Padrao SEO</flux:select.option>
                <flux:select.option value="deep">Profundo</flux:select.option>
            </flux:select>
            <flux:input wire:model="generation_batch_size" label="Artigos por lote" type="number" min="1" max="20" />
            <flux:input wire:model="generation_delay_seconds" label="Delay entre jobs (segundos)" type="number" min="0" max="3600" />
            <flux:input wire:model="h2_count" label="Quantidade H2" type="number" min="3" max="12" />
            <flux:input wire:model="h3_count" label="H3 por secao" type="number" min="0" max="5" />
            <flux:input wire:model="target_location" label="Cidade ou area alvo" />
            <flux:input wire:model="search_console_property" label="Property do Search Console" placeholder="sc-domain:meusite.com.br" />
            <flux:input wire:model="target_country" label="Pais alvo (ISO-3)" maxlength="3" />
            <flux:input wire:model="google_trends_country" label="Pais do Trends (ISO-2)" maxlength="2" />
            <flux:input wire:model="google_trends_region" label="Regiao do Trends" />
            <flux:input wire:model="target_persona" label="Persona alvo" />
            <flux:textarea wire:model="hero_description" label="Texto do Hero" rows="3" placeholder="{{ $heroDescriptionPlaceholder }}" />
            <flux:input wire:model="hero_image_url" label="Imagem do Hero (URL)" placeholder="{{ $heroImagePlaceholder }}" />
            <flux:textarea wire:model="default_cta" label="CTA padrao" rows="3" />
        </div>

        <div class="mt-4">
            <flux:checkbox wire:model="include_faq" label="Adicionar FAQ nos artigos fallback" />
        </div>

        <div class="mt-8 border-t border-zinc-200 pt-6 dark:border-zinc-800">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-950 dark:text-white">Analytics e compartilhamento</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Ative GA4, PostHog e deixe cada artigo sair com imagem social propria.</p>
                </div>
                @if ($this->topArticles->isNotEmpty())
                    <a href="{{ $project->publicArticleSocialImageUrl($this->topArticles->first()) }}" target="_blank" class="text-sm font-medium text-lime-700 transition hover:text-lime-800 dark:text-lime-300 dark:hover:text-lime-200">
                        Ver exemplo de OG image
                    </a>
                @endif
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <flux:input wire:model="ga4_measurement_id" label="GA4 Measurement ID" placeholder="G-XXXXXXXXXX" />
                <flux:input wire:model="posthog_api_key" label="PostHog Project Key" placeholder="phc_..." />
                <flux:input wire:model="posthog_host" label="PostHog Host" placeholder="https://us.i.posthog.com" />
            </div>

            <div class="mt-4 rounded-3xl border border-zinc-200 bg-zinc-50 px-4 py-4 text-sm leading-7 text-zinc-600 dark:border-zinc-800 dark:bg-zinc-950/30 dark:text-zinc-300">
                O blog passa a medir visualizacao de pagina, clique em CTA, busca e profundidade de leitura. A imagem social do artigo e gerada automaticamente com identidade editorial do projeto.
            </div>
        </div>
    </form>
</section>
