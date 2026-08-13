<?php

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('project can be created with hero content defaults from dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('projects.index')
        ->set('name', 'Hero Blog')
        ->set('niche', 'IA para empresas')
        ->set('primaryKeywords', 'ia para empresas, automacao comercial')
        ->set('hero_description', 'Conteudos sobre IA para liderancas comerciais.')
        ->set('hero_image_url', 'https://cdn.example.com/hero-create.jpg')
        ->call('createProject')
        ->assertHasNoErrors();

    $project = Project::query()->where('slug', 'hero-blog')->first();

    expect($project)->not->toBeNull()
        ->and($project?->is_primary_public)->toBeTrue()
        ->and($project?->hero_description)->toBe('Conteudos sobre IA para liderancas comerciais.')
        ->and($project?->hero_image_url)->toBe('https://cdn.example.com/hero-create.jpg');
});

test('project hero content can be updated from dashboard settings', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create([
        'hero_description' => null,
        'hero_image_url' => null,
    ]);

    $this->actingAs($user);

    Livewire::test('projects.show', ['project' => $project])
        ->set('hero_description', 'Conteudos sobre inteligencia artificial aplicada a empresas com foco em produtividade.')
        ->set('hero_image_url', 'https://cdn.example.com/hero-update.jpg')
        ->call('saveGenerationSettings')
        ->assertHasNoErrors();

    $project->refresh();

    expect($project->hero_description)->toBe('Conteudos sobre inteligencia artificial aplicada a empresas com foco em produtividade.')
        ->and($project->hero_image_url)->toBe('https://cdn.example.com/hero-update.jpg');
});

test('project hero settings are preloaded in the dashboard form', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create([
        'hero_description' => 'Texto atual do hero.',
        'hero_image_url' => 'https://cdn.example.com/hero-current.jpg',
    ]);

    $this->actingAs($user);

    Livewire::test('projects.show', ['project' => $project])
        ->assertSet('hero_description', 'Texto atual do hero.')
        ->assertSet('hero_image_url', 'https://cdn.example.com/hero-current.jpg');
});

test('project analytics settings can be updated from dashboard', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create([
        'ga4_measurement_id' => null,
        'posthog_api_key' => null,
        'posthog_host' => null,
    ]);

    $this->actingAs($user);

    Livewire::test('projects.show', ['project' => $project])
        ->set('ga4_measurement_id', 'G-4N4LYT1CS')
        ->set('posthog_api_key', 'phc_test_project_key')
        ->set('posthog_host', 'https://us.i.posthog.com')
        ->call('saveGenerationSettings')
        ->assertHasNoErrors();

    $project->refresh();

    expect($project->ga4_measurement_id)->toBe('G-4N4LYT1CS')
        ->and($project->posthog_api_key)->toBe('phc_test_project_key')
        ->and($project->posthog_host)->toBe('https://us.i.posthog.com');
});

test('project can be promoted to primary public blog from dashboard', function () {
    $user = User::factory()->create();
    $primaryProject = Project::factory()->for($user)->create([
        'name' => 'Projeto Original',
        'is_primary_public' => true,
    ]);
    $candidateProject = Project::factory()->for($user)->create([
        'name' => 'Projeto Principal Novo',
        'is_primary_public' => false,
    ]);

    $this->actingAs($user);

    Livewire::test('projects.show', ['project' => $candidateProject])
        ->call('setAsPrimaryPublicProject')
        ->assertHasNoErrors();

    expect($candidateProject->fresh()->is_primary_public)->toBeTrue()
        ->and($candidateProject->fresh()->isPrimaryPublicProject())->toBeTrue()
        ->and($primaryProject->fresh()->is_primary_public)->toBeFalse();
});

test('project hero placeholders mirror the public fallback content in dashboard', function () {
    $user = User::factory()->create();
    $project = Project::factory()->for($user)->create([
        'hero_description' => null,
        'hero_image_url' => null,
    ]);

    $this->actingAs($user);

    $this->get(route('projects.show', $project))
        ->assertOk()
        ->assertSee('placeholder="Conteudos sobre inteligencia artificial aplicada a empresas, com foco em automacao, produtividade e crescimento comercial."', false)
        ->assertSee('placeholder="https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&amp;fit=crop&amp;w=1600&amp;q=80"', false);
});
