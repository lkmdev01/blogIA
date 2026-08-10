<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Models\Category;
use App\Models\GenerationRun;
use App\Services\Groq\GroqClient;
use Illuminate\Support\Str;
use Throwable;

class ArticleGeneratorService
{
    public function __construct(
        protected GroqClient $groqClient,
        protected InternalLinkingService $internalLinkingService,
        protected SitemapService $sitemapService,
    ) {
    }

    public function generate(Article $article, bool $force = false, ?GenerationRun $run = null): Article
    {
        $article->loadMissing('project', 'pillar', 'cluster', 'category');

        if (! $force && filled($article->content) && $article->generation_status === 'completed') {
            return $article;
        }

        $run = $run
            ? $this->markRunAsRunning($run)
            : $this->startRun($article);

        try {
            $payload = $this->shouldUseGroq($article)
                ? $this->generateWithGroq($article)
                : $this->fallbackArticle($article);

            $payload = $this->normalizePayload($article, $payload);
            $existingSourcePayload = is_array($article->source_payload) ? $article->source_payload : [];

            $category = $this->syncCategory($article, $payload);
            $wordCount = $this->wordCount((string) data_get($payload, 'content'));
            $keywordDensity = $this->keywordDensity(
                (string) data_get($payload, 'content'),
                (string) data_get($payload, 'focus_keyword', $article->focus_keyword),
            );

            $article->fill([
                'category_id' => $category?->id,
                'title' => (string) data_get($payload, 'title'),
                'slug' => (string) data_get($payload, 'slug'),
                'focus_keyword' => (string) data_get($payload, 'focus_keyword', $article->focus_keyword),
                'long_tail_keywords' => data_get($payload, 'long_tail_keywords', $article->long_tail_keywords ?? []),
                'seo_title' => (string) data_get($payload, 'seo_title'),
                'meta_description' => (string) data_get($payload, 'meta_description'),
                'excerpt' => (string) data_get($payload, 'excerpt'),
                'introduction' => (string) data_get($payload, 'introduction'),
                'outline' => data_get($payload, 'outline', []),
                'content' => (string) data_get($payload, 'content'),
                'conclusion' => (string) data_get($payload, 'conclusion'),
                'cta' => (string) data_get($payload, 'cta'),
                'tags' => data_get($payload, 'tags', []),
                'word_count' => $wordCount,
                'keyword_density' => $keywordDensity,
                'generation_status' => 'completed',
                'source_payload' => array_filter([
                    ...$existingSourcePayload,
                    'provider' => (string) data_get($payload, '_provider', $this->shouldUseGroq($article) ? 'groq' : 'fallback'),
                    'model' => $this->shouldUseGroq($article) ? $this->groqClient->model() : 'fallback',
                    'fallback_reason' => data_get($payload, '_fallback_reason'),
                ], fn (mixed $value): bool => $value !== null),
            ]);

            $this->applyPublishingState($article);
            $article->save();

            $links = $this->internalLinkingService->refreshArticleLinks($article);

            $article->forceFill([
                'internal_links_count' => $links->count(),
                'seo_score' => $this->seoScore($article->refresh()),
            ])->save();

            if ($article->status === 'published') {
                $this->sitemapService->store($article->project);
            }

            $run->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
                'response_payload' => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ])->save();

            return $article->refresh();
        } catch (Throwable $exception) {
            $article->forceFill([
                'generation_status' => 'failed',
            ])->save();

            $run->forceFill([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function generateWithGroq(Article $article): array
    {
        $prompt = [
            'project' => [
                'name' => $article->project->name,
                'niche' => $article->project->niche,
                'description' => $article->project->description,
                'writing_tone' => $article->project->writing_tone,
                'language' => $article->project->language,
                'average_article_words' => $article->project->average_article_words,
                'article_depth' => $article->project->article_depth,
                'h2_count' => $article->project->h2_count,
                'h3_count' => $article->project->h3_count,
                'include_faq' => $article->project->include_faq,
                'target_persona' => $article->project->target_persona,
                'default_cta' => $article->project->default_cta,
            ],
            'article' => [
                'title' => $article->title,
                'focus_keyword' => $article->focus_keyword,
                'long_tail_keywords' => $article->long_tail_keywords,
                'pillar' => $article->pillar?->title,
                'cluster' => $article->cluster?->title,
                'category' => $article->category?->name,
            ],
            'discovery' => data_get($article->source_payload, 'discovery'),
            'response_shape' => [
                'title' => 'string',
                'slug' => 'string',
                'focus_keyword' => 'string',
                'long_tail_keywords' => ['string'],
                'seo_title' => 'string',
                'meta_description' => 'string',
                'excerpt' => 'string',
                'introduction' => 'string',
                'outline' => [
                    ['heading' => 'string', 'subheadings' => ['string']],
                ],
                'content' => 'markdown string',
                'conclusion' => 'string',
                'cta' => 'string',
                'tags' => ['string'],
                'category' => ['name' => 'string', 'description' => 'string'],
            ],
        ];

        try {
            $response = $this->groqClient->chat([
                [
                    'role' => 'system',
                    'content' => 'Voce escreve artigos SEO em portugues brasileiro. Entregue apenas JSON valido, sem markdown fora do campo content. No campo content, use Markdown rico com H2, H3, negritos, listas, blockquotes, exemplos e FAQ quando solicitado.',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($prompt, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                ],
            ], temperature: 0.45);

            if (blank($response)) {
                return $this->fallbackArticle($article);
            }

            $decoded = $this->decodeJson($response);

            return filled(data_get($decoded, 'title')) || filled(data_get($decoded, 'content'))
                ? $decoded
                : $this->fallbackArticle($article);
        } catch (Throwable) {
            return array_merge($this->fallbackArticle($article), [
                '_provider' => 'fallback',
                '_fallback_reason' => 'groq_unavailable',
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function fallbackArticle(Article $article): array
    {
        $keyword = $article->focus_keyword;
        $longTailKeywords = collect($article->long_tail_keywords)->filter()->values();
        $project = $article->project;
        $sectionLimit = match ($project->article_depth) {
            'concise' => max(3, min($project->h2_count, 4)),
            'deep' => max(7, min($project->h2_count, 10)),
            default => max(5, min($project->h2_count, 7)),
        };
        $h3Limit = max(0, min($project->h3_count, 4));

        $sectionTemplates = collect([
            [
                'heading' => "O que e {$keyword} e por que isso importa agora",
                'subheadings' => ['Contexto de mercado', 'Impacto na operacao'],
            ],
            [
                'heading' => "Como {$keyword} fortalece autoridade e marca",
                'subheadings' => ['Branding orientado por busca', 'Confianca na jornada'],
            ],
            [
                'heading' => "Como planejar {$keyword} com foco em SEO",
                'subheadings' => ['Pilares de conteudo', 'Clusters e long tails'],
            ],
            [
                'heading' => "Passo a passo para aplicar {$keyword}",
                'subheadings' => ['Diagnostico inicial', 'Plano de execucao'],
            ],
            [
                'heading' => "Exemplos praticos de {$keyword}",
                'subheadings' => ['Marketing e vendas', 'Atendimento e produtividade'],
            ],
            [
                'heading' => "Erros comuns ao trabalhar {$keyword}",
                'subheadings' => ['Automacao sem estrategia', 'Conteudo sem revisao'],
            ],
            [
                'heading' => "Indicadores para medir resultado com {$keyword}",
                'subheadings' => ['Metricas de SEO', 'Metricas comerciais'],
            ],
            [
                'heading' => "Como escalar {$keyword} com consistencia",
                'subheadings' => ['Rotina editorial', 'Atualizacao continua'],
            ],
            [
                'heading' => "Checklist final para implementar {$keyword}",
                'subheadings' => ['Antes de publicar', 'Depois de publicar'],
            ],
            [
                'heading' => "Proximos passos para transformar {$keyword} em crescimento",
                'subheadings' => ['Priorizacao', 'Governanca'],
            ],
        ])->take($sectionLimit);

        $outline = $sectionTemplates
            ->map(fn (array $section): array => [
                'heading' => $section['heading'],
                'subheadings' => collect($section['subheadings'])->take($h3Limit)->values()->all(),
            ])
            ->when($project->include_faq, fn ($outline) => $outline->push([
                'heading' => "FAQ sobre {$keyword}",
                'subheadings' => ['Perguntas frequentes'],
            ]))
            ->values()
            ->all();

        $content = collect([
            $article->introduction ?: "**{$keyword}** deixou de ser um tema experimental e passou a fazer parte de uma estrategia real de crescimento para empresas que querem ganhar eficiencia, fortalecer marca e conquistar trafego organico qualificado.",
            '',
            'Este guia foi pensado para **'.($project->target_persona ?: 'gestores e empreendedores')."** que precisam entender como transformar {$keyword} em uma operacao de conteudo, SEO e demanda previsivel sem depender apenas de campanhas pagas.",
            '',
            '> A ideia central e simples: quando a empresa organiza conhecimento em pilares, clusters e artigos conectados, ela cria uma biblioteca capaz de responder duvidas reais do mercado, educar leads e sustentar decisoes de compra com mais autoridade.',
            '',
        ]);

        $sectionTemplates->each(function (array $section) use ($content, $keyword, $h3Limit): void {
            $content->push("## {$section['heading']}", '');
            $content->push("Trabalhar **{$keyword}** com seriedade exige mais do que publicar textos soltos. A empresa precisa conectar **objetivo de negocio**, **intencao de busca**, linguagem da marca e um calendario editorial que mantenha frequencia sem sacrificar qualidade.", '');
            $content->push("Na pratica, isso significa transformar perguntas recorrentes do mercado em ativos de conteudo. Cada artigo deve resolver uma duvida especifica, apontar proximos passos e se conectar a outros materiais para criar **profundidade topical**.", '');

            collect($section['subheadings'])->take($h3Limit)->each(function (string $subheading) use ($content, $keyword): void {
                $content->push("### {$subheading}", '');
                $content->push("Neste ponto, **{$keyword}** precisa ser traduzido em processo. Liste criterios, defina responsaveis, documente aprendizados e use revisao humana para manter **precisao**, **tom consultivo** e alinhamento com a promessa da marca.", '');
            });

            $content->push('- **Palavra-chave principal:** defina uma intencao clara antes de escrever.');
            $content->push('- **Arquitetura:** conecte o artigo a um pilar ou cluster relacionado.');
            $content->push('- **Exemplos:** use situacoes concretas para reduzir abstracao.');
            $content->push('- **SEO on-page:** revise meta title, meta description, CTA e links internos.', '');
        });

        if ($project->include_faq) {
            $content->push("## FAQ sobre {$keyword}", '');
            $content->push("### {$keyword} serve para empresas pequenas?", '');
            $content->push("Sim. O ponto principal e comecar com um **recorte claro**, priorizar problemas repetitivos e transformar o aprendizado em conteudo util para o publico certo.", '');
            $content->push("### Quanto tempo leva para ver resultado em SEO?", '');
            $content->push("SEO costuma ser uma estrategia de medio prazo. O ganho aparece quando a empresa combina **frequencia**, **arquitetura de clusters**, interlinkagem e melhoria continua dos artigos publicados.", '');
            $content->push("### A IA substitui a revisao humana?", '');
            $content->push("Nao. A IA acelera pesquisa, estrutura e rascunho, mas **revisao humana** continua essencial para corrigir contexto, posicionamento, exemplos e promessas comerciais.", '');
        }

        $content->push('', '## Conclusao', '');
        $content->push("**{$keyword}** funciona melhor quando deixa de ser uma acao isolada e vira sistema: pauta, producao, revisao, publicacao, interlinkagem e atualizacao constante.", '');
        $content->push('Com esse processo, o blog deixa de ser apenas um canal de publicacao e passa a funcionar como um **ativo de autoridade, educacao de mercado e geracao de demanda organica**.', '');
        $content->push('## CTA', '', $project->default_cta ?: 'Mapeie seus pilares, gere clusters e transforme o blog em um ativo continuo de crescimento.');

        $content = $content->implode("\n");

        return [
            '_provider' => 'fallback',
            'title' => $article->title,
            'slug' => Str::slug($article->title),
            'focus_keyword' => $keyword,
            'long_tail_keywords' => $longTailKeywords->all(),
            'seo_title' => $article->title,
            'meta_description' => "Entenda como usar {$keyword} para ganhar escala editorial, melhorar SEO e fortalecer a autoridade da marca.",
            'excerpt' => "Veja como estruturar {$keyword} com pilares, clusters e uma rotina de publicacao orientada a SEO.",
            'introduction' => "{$keyword} deixou de ser experimento e virou alavanca pratica para marcas que querem produzir melhor e crescer no Google.",
            'outline' => $outline,
            'content' => $content,
            'conclusion' => 'Com uma operacao editorial bem desenhada, sua marca publica com consistencia e melhora o posicionamento organico.',
            'cta' => null,
            'tags' => ['seo', 'conteudo', 'groq', 'blog automatizado'],
            'category' => [
                'name' => $article->category?->name ?: ($article->cluster?->title ?: 'SEO'),
                'description' => 'Categoria automatica criada a partir da estrategia do cluster.',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function normalizePayload(Article $article, array $payload): array
    {
        $rawContent = (string) data_get($payload, 'content', $article->content);

        // Remove leading Markdown H1 ("# Title") if present
        $rawContent = preg_replace('/^\s*#\s.*(\r?\n)+/m', '', $rawContent, 1) ?: $rawContent;

        // Also remove leading HTML <h1>...</h1> if present
        $rawContent = preg_replace('/^\s*<h1[^>]*>.*?<\/h1>\s*/is', '', $rawContent, 1) ?: $rawContent;

        return [
            'title' => (string) data_get($payload, 'title', $article->title),
            'slug' => Str::slug((string) data_get($payload, 'slug', data_get($payload, 'title', $article->title))),
            'focus_keyword' => (string) data_get($payload, 'focus_keyword', $article->focus_keyword),
            'long_tail_keywords' => collect(data_get($payload, 'long_tail_keywords', $article->long_tail_keywords ?? []))
                ->filter()
                ->values()
                ->take(6)
                ->all(),
            'seo_title' => (string) data_get($payload, 'seo_title', data_get($payload, 'title', $article->title)),
            'meta_description' => Str::limit((string) data_get($payload, 'meta_description', $article->meta_description), 160, ''),
            'excerpt' => (string) data_get($payload, 'excerpt', $article->excerpt),
            'introduction' => (string) data_get($payload, 'introduction', $article->introduction),
            'outline' => data_get($payload, 'outline', []),
            'content' => $rawContent,
            'conclusion' => (string) data_get($payload, 'conclusion', $article->conclusion),
            'cta' => (string) data_get($payload, 'cta', $article->cta),
            'tags' => collect(data_get($payload, 'tags', $article->tags ?? []))->filter()->values()->take(8)->all(),
            'category' => [
                'name' => (string) data_get($payload, 'category.name', $article->category?->name ?: ($article->cluster?->title ?: 'SEO')),
                'description' => (string) data_get($payload, 'category.description', $article->category?->description ?: 'Categoria gerada automaticamente.'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function syncCategory(Article $article, array $payload): ?Category
    {
        $name = (string) data_get($payload, 'category.name');

        if (blank($name)) {
            return $article->category;
        }

        return Category::query()->updateOrCreate(
            [
                'project_id' => $article->project_id,
                'slug' => Str::slug($name),
            ],
            [
                'content_pillar_id' => $article->content_pillar_id,
                'content_cluster_id' => $article->content_cluster_id,
                'name' => $name,
                'description' => (string) data_get($payload, 'category.description'),
                'seo_title' => "{$name} | {$article->project->name}",
                'seo_description' => (string) data_get($payload, 'meta_description'),
            ],
        );
    }

    protected function applyPublishingState(Article $article): void
    {
        if ($article->scheduled_for?->isFuture()) {
            $article->status = 'scheduled';
            $article->published_at = null;

            return;
        }

        if ($article->project->auto_publish) {
            $article->status = 'published';
            $article->published_at = now();

            return;
        }

        $article->status = 'draft';
        $article->published_at = null;
    }

    protected function wordCount(string $content): int
    {
        return str_word_count(strip_tags(Str::markdown($content)));
    }

    protected function keywordDensity(string $content, string $keyword): float
    {
        $normalizedContent = Str::of(strip_tags(Str::markdown($content)))->lower()->squish()->toString();
        $normalizedKeyword = Str::of($keyword)->lower()->squish()->toString();

        $words = max(1, str_word_count($normalizedContent));
        $occurrences = substr_count($normalizedContent, $normalizedKeyword);

        return round(($occurrences / $words) * 100, 2);
    }

    protected function seoScore(Article $article): int
    {
        $score = 40;

        if (Str::contains(Str::lower($article->title), Str::lower($article->focus_keyword))) {
            $score += 10;
        }

        if (filled($article->meta_description) && Str::length($article->meta_description) >= 120) {
            $score += 10;
        }

        if (count($article->outline ?? []) >= 3) {
            $score += 10;
        }

        if ($article->word_count >= (int) floor($article->project->average_article_words * 0.8)) {
            $score += 10;
        }

        if ($article->internal_links_count > 0) {
            $score += 10;
        }

        if (count($article->tags ?? []) >= 3) {
            $score += 10;
        }

        return min(100, $score);
    }

    protected function startRun(Article $article): GenerationRun
    {
        return $article->generationRuns()->create([
            'project_id' => $article->project_id,
            'type' => 'article',
            'provider' => $this->shouldUseGroq($article) ? 'groq' : 'fallback',
            'model' => $this->shouldUseGroq($article) ? $this->groqClient->model() : 'fallback',
            'status' => 'running',
            'started_at' => now(),
            'prompt_payload' => json_encode([
                'article' => $article->only(['title', 'focus_keyword', 'long_tail_keywords']),
                'project' => $article->project->only(['name', 'niche', 'writing_tone', 'language', 'average_article_words']),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ]);
    }

    protected function markRunAsRunning(GenerationRun $run): GenerationRun
    {
        $run->forceFill([
            'status' => 'running',
            'started_at' => now(),
            'error_message' => null,
        ])->save();

        return $run;
    }

    protected function shouldUseGroq(Article $article): bool
    {
        return $article->project->ai_provider === 'groq' && $this->groqClient->isConfigured();
    }

    /**
     * @return array<string, mixed>
     */
    protected function decodeJson(string $content): array
    {
        $normalized = trim($content);
        $withoutFences = preg_replace('/```(?:json)?\s*|\s*```/i', '', $normalized) ?: $normalized;

        $candidates = array_filter(array_unique([
            $normalized,
            $withoutFences,
            $this->extractJsonObject($normalized),
            $this->extractJsonObject($withoutFences),
        ]));

        foreach ($candidates as $candidate) {
            $decoded = json_decode($candidate, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new \RuntimeException('A resposta da Groq nao retornou JSON valido para o artigo.');
    }

    protected function extractJsonObject(string $content): ?string
    {
        $start = strpos($content, '{');
        $end = strrpos($content, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        return substr($content, $start, $end - $start + 1);
    }
}
