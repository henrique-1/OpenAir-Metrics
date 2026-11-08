<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('nao renderiza o script do Microsoft Clarity no layout da aplicacao', function () {
    $response = $this->get(route('login'));

    $response->assertSuccessful()
        ->assertDontSee('https://www.clarity.ms/tag/', false)
        ->assertDontSee('window.clarity', false);
});

test('nao renderiza o script do Microsoft Clarity quando usuario autenticado', function () {
    $admin = User::factory()->create([
        'nivel' => 'administrador',
        'ativo' => true,
    ]);

    $response = $this->actingAs($admin)->get(route('perfil.edit'));

    $response->assertSuccessful()
        ->assertDontSee('https://www.clarity.ms/tag/', false)
        ->assertDontSee('window.clarity', false);
});
