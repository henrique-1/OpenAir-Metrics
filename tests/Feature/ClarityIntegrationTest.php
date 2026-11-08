<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('nao renderiza o script do Microsoft Clarity quando project_id nao esta configurado', function () {
    config([
        'services.clarity.enabled' => true,
        'services.clarity.project_id' => null,
    ]);

    $response = $this->get(route('login'));

    $response->assertSuccessful()
        ->assertDontSee('https://www.clarity.ms/tag/', false);
});

test('renderiza o script do Microsoft Clarity quando project_id esta configurado e ativo', function () {
    config([
        'services.clarity.enabled' => true,
        'services.clarity.project_id' => 'clarity_test_123',
    ]);

    $response = $this->get(route('login'));

    $response->assertSuccessful()
        ->assertSee('https://www.clarity.ms/tag/', false)
        ->assertSee('clarity_test_123', false);
});

test('nao renderiza o script do Microsoft Clarity quando desativado via configuracao', function () {
    config([
        'services.clarity.enabled' => false,
        'services.clarity.project_id' => 'clarity_test_123',
    ]);

    $response = $this->get(route('login'));

    $response->assertSuccessful()
        ->assertDontSee('https://www.clarity.ms/tag/', false);
});

test('define tag customizada de perfil no Clarity quando o usuario esta autenticado', function () {
    config([
        'services.clarity.enabled' => true,
        'services.clarity.project_id' => 'clarity_test_123',
    ]);

    $admin = User::factory()->create([
        'nivel' => 'administrador',
        'ativo' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('perfil.edit'));

    $response->assertSuccessful()
        ->assertSee('window.clarity("set", "perfil",', false)
        ->assertSee('administrador', false);
});
