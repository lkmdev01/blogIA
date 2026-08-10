<?php

use App\Models\Article;
use App\Services\Seo\ArticleGeneratorService;
use App\Services\Seo\InternalLinkingService;
use App\Services\Seo\SitemapService;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public Article $article;

    public string $title = '';
    public string $slug = '';
    public string $focus_keyword = '';
    public string $seo_title = '';
    public string $meta_description = '';
    public string $excerpt = '';
    public string $content = '';
    public string $cta = '';
    public string $status = 'draft';
    public string $scheduled_for = '';
    public string $featured_image_path = '';
    public string $featured_image_alt = '';
    public $featuredImageUpload = null;
    public string $tagsText = '';
    public string $longTailText = '';
    public ?int $category_id = null;

    public function mount(Article $article): void
    {
        $this->article = $article->load('project', 'category', 'internalLinks.linkedArticle');

        $this->fillFromArticle();
    }

    #[Computed]
    public function categories()
    {
        return $this->article->project->categories()->orderBy('name')->get();
    }

    #[Computed]
    public function seoChecklist(): array
    {
        $normalizedContent = Str::of(strip_tags(Str::markdown($this->content)))->lower()->squish()->toString();
        $normalizedKeyword = Str::of($this->focus_keyword)->lower()->squish()->toString();
        $wordCount = str_word_count($normalizedContent);
        $keywordOccurrences = $normalizedKeyword !== '' ? substr_count($normalizedContent, $normalizedKeyword) : 0;
        $density = $wordCount > 0 ? round(($keywordOccurrences / $wordCount) * 100, 2) : 0;
        $slugExists = Article::query()
            ->where('project_id', $this->article->project_id)
            ->where('slug', Str::slug($this->slug))
            ->whereKeyNot($this->article->id)
            ->exists();

        return [
            ['label' => 'Palavra-chave no titulo', 'passed' => Str::contains(Str::lower($this->title), Str::lower($this->focus_keyword))],
            ['label' => 'Meta description entre 120 e 160 caracteres', 'passed' => Str::length($this->meta_description) >= 120 && Str::length($this->meta_description) <= 160],
            ['label' => 'Conteudo com pelo menos 800 palavras', 'passed' => $wordCount >= 800],
            ['label' => 'Estrutura com H2', 'passed' => substr_count($this->content, '## ') >= 3],
            ['label' => 'Slug unico no projeto', 'passed' => ! $slugExists],
            ['label' => 'Densidade da keyword entre 0.5% e 2.5%', 'passed' => $density >= 0.5 && $density <= 2.5],
            ['label' => 'Imagem destacada com alt text', 'passed' => filled($this->featured_image_path) && filled($this->featured_image_alt)],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:180'],
            'slug' => ['required', 'string', 'max:220'],
            'focus_keyword' => ['required', 'string', 'max:160'],
            'seo_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:170'],
            'excerpt' => ['nullable', 'string', 'max:600'],
            'content' => ['nullable', 'string'],
            'cta' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'string', 'in:idea,draft,scheduled,published'],
            'scheduled_for' => ['nullable', 'date'],
            'featured_image_path' => ['nullable', 'string', 'max:255'],
            'featured_image_alt' => ['nullable', 'string', 'max:255'],
            'featuredImageUpload' => ['nullable', 'image', 'max:4096'],
            'tagsText' => ['nullable', 'string', 'max:500'],
            'longTailText' => ['nullable', 'string', 'max:800'],
            'category_id' => ['nullable', 'integer'],
        ]);

        if (Article::query()
            ->where('project_id', $this->article->project_id)
            ->where('slug', Str::slug($validated['slug']))
            ->whereKeyNot($this->article->id)
            ->exists()) {
            $this->addError('slug', 'Ja existe um artigo com este slug neste projeto.');

            return;
        }

        if ($this->featuredImageUpload) {
            $path = $this->featuredImageUpload->store('article-images', 'public');
            $validated['featured_image_path'] = Storage::url($path);
        }

        $publishedAt = $this->article->published_at;
        $wordCount = str_word_count(strip_tags(Str::markdown($validated['content'] ?? '')));
        $keywordDensity = $this->keywordDensity((string) ($validated['content'] ?? ''), $validated['focus_keyword']);

        if ($validated['status'] === 'published' && blank($publishedAt)) {
            $publishedAt = now();
        }

        if ($validated['status'] !== 'published') {
            $publishedAt = null;
        }

        $this->article->fill([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['slug']),
            'focus_keyword' => $validated['focus_keyword'],
            'seo_title' => $validated['seo_title'] ?: $validated['title'],
            'meta_description' => $validated['meta_description'],
            'excerpt' => $validated['excerpt'],
            'content' => $validated['content'],
            'cta' => $validated['cta'],
            'status' => $validated['status'],
            'scheduled_for' => filled($validated['scheduled_for']) ? Carbon::parse($validated['scheduled_for']) : null,
            'published_at' => $publishedAt,
            'featured_image_path' => $validated['featured_image_path'],
            'featured_image_alt' => $validated['featured_image_alt'],
            'tags' => $this->csvToArray($validated['tagsText']),
            'long_tail_keywords' => $this->csvToArray($validated['longTailText']),
            'category_id' => $validated['category_id'],
            'word_count' => $wordCount,
            'keyword_density' => $keywordDensity,
            'seo_score' => $this->seoScore($wordCount, $keywordDensity),
        ])->save();

        Flux::toast(variant: 'success', text: 'Artigo salvo.');

        $this->article = $this->article->refresh()->load('project', 'category', 'internalLinks.linkedArticle');
        $this->featuredImageUpload = null;
    }

    public function regenerate(ArticleGeneratorService $articleGeneratorService): void
    {
        $this->article = $articleGeneratorService->generate($this->article, force: true);
        $this->fillFromArticle();

        Flux::toast(variant: 'success', text: 'Artigo regenerado.');
    }

    public function refreshLinks(InternalLinkingService $internalLinkingService): void
    {
        $internalLinkingService->refreshArticleLinks($this->article->loadMissing('project'));

        $this->article = $this->article->refresh()->load('project', 'category', 'internalLinks.linkedArticle');

        Flux::toast(variant: 'success', text: 'Interlinkagem atualizada.');
    }

    public function publish(SitemapService $sitemapService): void
    {
        $this->status = 'published';
        $this->save();

        $sitemapService->store($this->article->project);

        Flux::toast(variant: 'success', text: 'Artigo publicado e sitemap atualizado.');
    }

    protected function fillFromArticle(): void
    {
        $this->title = $this->article->title;
        $this->slug = $this->article->slug;
        $this->focus_keyword = $this->article->focus_keyword;
        $this->seo_title = $this->article->seo_title ?: $this->article->title;
        $this->meta_description = $this->article->meta_description ?: '';
        $this->excerpt = $this->article->excerpt ?: '';
        $this->content = $this->article->content ?: '';
        $this->cta = $this->article->cta ?: '';
        $this->status = $this->article->status;
        $this->scheduled_for = $this->article->scheduled_for?->format('Y-m-d\TH:i') ?: '';
        $this->featured_image_path = $this->article->featured_image_path ?: '';
        $this->featured_image_alt = $this->article->featured_image_alt ?: '';
        $this->tagsText = implode(', ', $this->article->tags ?? []);
        $this->longTailText = implode(', ', $this->article->long_tail_keywords ?? []);
        $this->category_id = $this->article->category_id;
    }

    /**
     * @return array<int, string>
     */
    protected function csvToArray(?string $value): array
    {
        return Str::of((string) $value)
            ->replace(["\r\n", "\n"], ',')
            ->explode(',')
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function keywordDensity(string $content, string $keyword): float
    {
        $normalizedContent = Str::of(strip_tags(Str::markdown($content)))->lower()->squish()->toString();
        $normalizedKeyword = Str::of($keyword)->lower()->squish()->toString();

        if ($normalizedKeyword === '') {
            return 0;
        }

        return round((substr_count($normalizedContent, $normalizedKeyword) / max(1, str_word_count($normalizedContent))) * 100, 2);
    }

    protected function seoScore(int $wordCount, float $keywordDensity): int
    {
        return collect($this->seoChecklist)
            ->filter(fn (array $item): bool => $item['passed'])
            ->count() * 10
            + ($wordCount >= 1200 ? 20 : 0)
            + ($keywordDensity >= 0.5 && $keywordDensity <= 2.5 ? 10 : 0);
    }
};
