<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Category;
use App\Models\ContentCluster;
use App\Models\ContentPillar;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->first() ?? User::factory()->create([
            'name' => 'BlogIA Admin',
            'email' => 'admin@blogia.test',
        ]);

        $project = Project::query()->firstOrCreate(
            ['user_id' => $user->id, 'slug' => 'blogia'],
            [
                'name' => 'BlogIA',
                'domain' => 'blogia.test',
                'niche' => 'IA para empresas',
                'description' => 'Motor de conteudo SEO para fortalecer marca, gerar autoridade e escalar artigos com apoio de IA.',
                'primary_keywords' => ['ia para empresas', 'automacao de marketing', 'conteudo seo'],
                'writing_tone' => 'consultivo',
                'average_article_words' => 1800,
                'posting_frequency' => 'daily',
                'posts_per_day' => 1,
                'language' => 'pt-BR',
                'blog_type' => 'authority',
                'ai_provider' => 'groq',
                'generate_images' => false,
                'enable_interlinking' => true,
                'auto_generate_content' => true,
                'auto_publish' => false,
                'generation_batch_size' => 3,
                'generation_delay_seconds' => 20,
                'article_depth' => 'standard',
                'h2_count' => 6,
                'h3_count' => 2,
                'include_faq' => true,
                'target_persona' => 'gestores e empreendedores',
                'default_cta' => 'Fale com nossa equipe para transformar SEO em um motor de crescimento previsivel.',
                'last_strategy_generated_at' => now(),
                'last_sitemap_generated_at' => now(),
            ],
        );

        $pillar = ContentPillar::query()->firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'ia-para-empresas'],
            [
                'title' => 'IA para empresas',
                'description' => 'Pilar central para educar o mercado sobre ganho de produtividade, autoridade e branding com IA.',
                'primary_keyword' => 'ia para empresas',
                'target_intent' => 'educational',
                'seo_notes' => 'Construir autoridade com guias, comparativos e casos de uso.',
                'sort_order' => 1,
                'article_goal' => 4,
            ],
        );

        $cluster = ContentCluster::query()->firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'automacao-de-conteudo'],
            [
                'content_pillar_id' => $pillar->id,
                'title' => 'Automacao de conteudo',
                'description' => 'Cluster focado em processos, escala e previsibilidade editorial.',
                'focus_keyword' => 'automacao de conteudo',
                'long_tail_keywords' => ['automacao de conteudo para seo', 'fluxo de pauta com ia', 'blog automatico para empresas'],
                'status' => 'active',
                'article_goal' => 3,
                'sort_order' => 1,
            ],
        );

        $category = Category::query()->firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'seo-com-ia'],
            [
                'content_pillar_id' => $pillar->id,
                'content_cluster_id' => $cluster->id,
                'name' => 'SEO com IA',
                'description' => 'Conteudos sobre estrategia, automacao e performance organica.',
                'seo_title' => 'SEO com IA | BlogIA',
                'seo_description' => 'Aprenda como usar IA para montar um motor de conteudo SEO escalavel.',
            ],
        );

        Article::query()->firstOrCreate(
            ['project_id' => $project->id, 'slug' => 'como-usar-ia-para-empresas-e-ganhar-escala-no-seo'],
            [
                'content_pillar_id' => $pillar->id,
                'content_cluster_id' => $cluster->id,
                'category_id' => $category->id,
                'title' => 'Como usar IA para empresas e ganhar escala no SEO',
                'focus_keyword' => 'ia para empresas',
                'long_tail_keywords' => ['ia para empresas b2b', 'escala de conteudo seo', 'blog com ia para marcas'],
                'status' => 'published',
                'is_pillar_page' => true,
                'seo_title' => 'IA para empresas: guia para crescer no Google com escala',
                'meta_description' => 'Veja como estruturar um blog automatizado com IA, clusters SEO e publicacao recorrente para fortalecer sua marca.',
                'excerpt' => 'Um guia pratico para montar um motor de SEO com IA e consistencia editorial.',
                'introduction' => 'IA para empresas deixou de ser experimento e virou alavanca real de branding e geracao de demanda.',
                'outline' => [
                    ['heading' => 'Por que empresas estao adotando IA no conteudo', 'points' => ['Escala', 'Consistencia', 'Custo marginal']],
                    ['heading' => 'Como transformar isso em vantagem de SEO', 'points' => ['Clusters', 'Pilares', 'Interlinkagem']],
                ],
                'content' => "# Como usar IA para empresas e ganhar escala no SEO\n\n## Por que isso importa\n\nMarcas que publicam com constancia ocupam mais espaco no Google e ganham lembranca.\n\n## Como estruturar a operacao\n\n- Defina pilares claros.\n- Transforme cada pilar em clusters.\n- Crie artigos escaneaveis com foco em intencao de busca.\n\n## O papel da interlinkagem\n\nLinks internos ajudam o Google a entender hierarquia e relevancia entre paginas.\n\n## Conclusao\n\nCom processo, dados e IA, sua marca acelera a producao sem perder qualidade.",
                'conclusion' => 'A combinacao de estrategia editorial e IA transforma conteudo em um ativo previsivel de crescimento.',
                'cta' => 'Mapeie seu nicho, gere clusters e publique com regularidade para transformar SEO em canal de aquisicao.',
                'tags' => ['seo', 'ia', 'branding'],
                'seo_score' => 92,
                'internal_links_count' => 0,
                'external_links_count' => 0,
                'keyword_density' => 1.45,
                'scheduled_for' => now()->subHour(),
                'published_at' => now()->subHour(),
                'generation_status' => 'completed',
                'featured_image_path' => null,
                'featured_image_alt' => null,
                'word_count' => 1420,
                'source_payload' => ['provider' => 'seeder'],
            ],
        );
    }
}
