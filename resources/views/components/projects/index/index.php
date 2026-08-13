<?php

use App\Models\Project;
use Flux\Flux;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $name = '';

    public string $domain = '';

    public string $target_location = '';

    public string $search_console_property = '';

    public string $target_country = 'BRA';

    public string $google_trends_country = 'BR';

    public string $google_trends_region = 'Sao Paulo';

    public string $niche = '';

    public string $description = '';

    public string $hero_description = 'Conteudos sobre inteligencia artificial aplicada a empresas, com foco em automacao, produtividade e crescimento comercial.';

    public string $hero_image_url = '';

    public string $primaryKeywords = '';

    public string $writing_tone = 'consultivo';

    public int $average_article_words = 1800;

    public string $posting_frequency = 'daily';

    public int $posts_per_day = 1;

    public string $language = 'pt-BR';

    public string $blog_type = 'authority';

    public string $ai_provider = 'groq';

    public int $generation_batch_size = 3;

    public int $generation_delay_seconds = 20;

    public string $article_depth = 'standard';

    public int $h2_count = 6;

    public int $h3_count = 2;

    public bool $include_faq = true;

    public string $target_persona = 'gestores e empreendedores';

    public string $default_cta = 'Fale com nossa equipe para transformar SEO em crescimento previsivel.';

    public bool $generate_images = false;

    public bool $enable_interlinking = true;

    public bool $auto_generate_content = true;

    public bool $auto_publish = false;

    #[Computed]
    public function projects()
    {
        return auth()->user()
            ->projects()
            ->withCount(['articles', 'pillars', 'clusters'])
            ->orderByDesc('is_primary_public')
            ->latest()
            ->get();
    }

    public function createProject(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'domain' => ['nullable', 'string', 'max:255'],
            'target_location' => ['nullable', 'string', 'max:160'],
            'search_console_property' => ['nullable', 'string', 'max:255'],
            'target_country' => ['nullable', 'string', 'size:3'],
            'google_trends_country' => ['nullable', 'string', 'size:2'],
            'google_trends_region' => ['nullable', 'string', 'max:160'],
            'niche' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'hero_description' => ['nullable', 'string', 'max:1000'],
            'hero_image_url' => ['nullable', 'url', 'max:2048'],
            'primaryKeywords' => ['required', 'string', 'max:1000'],
            'writing_tone' => ['required', 'string', 'max:80'],
            'average_article_words' => ['required', 'integer', 'min:500', 'max:6000'],
            'posting_frequency' => ['required', 'string', 'max:80'],
            'posts_per_day' => ['required', 'integer', 'min:1', 'max:10'],
            'language' => ['required', 'string', 'max:20'],
            'blog_type' => ['required', 'string', 'max:80'],
            'ai_provider' => ['required', 'string', 'in:groq,fallback'],
            'generation_batch_size' => ['required', 'integer', 'min:1', 'max:20'],
            'generation_delay_seconds' => ['required', 'integer', 'min:0', 'max:3600'],
            'article_depth' => ['required', 'string', 'in:concise,standard,deep'],
            'h2_count' => ['required', 'integer', 'min:3', 'max:12'],
            'h3_count' => ['required', 'integer', 'min:0', 'max:5'],
            'include_faq' => ['boolean'],
            'target_persona' => ['nullable', 'string', 'max:160'],
            'default_cta' => ['nullable', 'string', 'max:1000'],
            'generate_images' => ['boolean'],
            'enable_interlinking' => ['boolean'],
            'auto_generate_content' => ['boolean'],
            'auto_publish' => ['boolean'],
        ]);

        $project = auth()->user()->projects()->create([
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'domain' => $validated['domain'] ?: null,
            'target_location' => $validated['target_location'] ?: null,
            'search_console_property' => $validated['search_console_property'] ?: null,
            'target_country' => filled($validated['target_country']) ? Str::upper($validated['target_country']) : null,
            'google_trends_country' => filled($validated['google_trends_country']) ? Str::upper($validated['google_trends_country']) : null,
            'google_trends_region' => $validated['google_trends_region'] ?: null,
            'niche' => $validated['niche'],
            'description' => $validated['description'] ?: null,
            'hero_description' => $validated['hero_description'] ?: null,
            'hero_image_url' => $validated['hero_image_url'] ?: null,
            'primary_keywords' => $this->keywordsFromText($validated['primaryKeywords']),
            'writing_tone' => $validated['writing_tone'],
            'average_article_words' => $validated['average_article_words'],
            'posting_frequency' => $validated['posting_frequency'],
            'posts_per_day' => $validated['posts_per_day'],
            'language' => $validated['language'],
            'blog_type' => $validated['blog_type'],
            'ai_provider' => $validated['ai_provider'],
            'generation_batch_size' => $validated['generation_batch_size'],
            'generation_delay_seconds' => $validated['generation_delay_seconds'],
            'article_depth' => $validated['article_depth'],
            'h2_count' => $validated['h2_count'],
            'h3_count' => $validated['h3_count'],
            'include_faq' => $validated['include_faq'],
            'target_persona' => $validated['target_persona'] ?: null,
            'default_cta' => $validated['default_cta'] ?: null,
            'generate_images' => $validated['generate_images'],
            'enable_interlinking' => $validated['enable_interlinking'],
            'auto_generate_content' => $validated['auto_generate_content'],
            'auto_publish' => $validated['auto_publish'],
        ]);

        Flux::toast(variant: 'success', text: 'Projeto criado. Agora voce pode gerar a pauta e os artigos.');

        $this->redirect(route('projects.show', $project), navigate: true);
    }

    /**
     * @return array<int, string>
     */
    protected function keywordsFromText(string $value): array
    {
        return Str::of($value)
            ->replace(["\r\n", "\n"], ',')
            ->explode(',')
            ->map(fn (string $keyword): string => trim($keyword))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $count = 2;

        while (Project::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$count}";
            $count++;
        }

        return $slug;
    }
};
