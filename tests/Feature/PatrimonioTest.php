<?php

use App\Models\Patrimonio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('usuario autenticado pode visualizar a listagem de patrimonios', function () {
    $user = User::factory()->create();
    Patrimonio::factory()->count(3)->create(['created_by' => $user->id]);

    $response = $this->actingAs($user)->get(route('patrimonios.index'));

    $response->assertOk();
    $response->assertViewIs('patrimonios.index');
    $response->assertViewHas('patrimonios');
});

test('usuario pode cadastrar um novo patrimonio individual', function () {
    $user = User::factory()->create();

    $payload = [
        'mac_address' => 'AA:BB:CC:DD:EE:11',
        'numero_patrimonio' => 'PAT-001',
        'tipo_sugerido' => 'Estação Matriz',
        'status' => 'Disponível',
        'data_aquisicao' => '2026-09-01',
        'observacoes' => 'Lote de teste',
    ];

    $response = $this->actingAs($user)->post(route('patrimonios.store'), $payload);

    $response->assertRedirect(route('patrimonios.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('patrimonios', [
        'mac_address' => 'AA:BB:CC:DD:EE:11',
        'numero_patrimonio' => 'PAT-001',
        'status' => 'Disponível',
    ]);
});

test('valida formato e unicidade do mac address no cadastro de patrimonio', function () {
    $user = User::factory()->create();
    Patrimonio::factory()->create(['mac_address' => 'AA:BB:CC:DD:EE:22']);

    // MAC Inválido
    $responseInvalido = $this->actingAs($user)->post(route('patrimonios.store'), [
        'mac_address' => 'INVALID_MAC',
        'tipo_sugerido' => 'Estação Matriz',
        'status' => 'Disponível',
    ]);
    $responseInvalido->assertSessionHasErrors(['mac_address']);

    // MAC Duplicado
    $responseDuplicado = $this->actingAs($user)->post(route('patrimonios.store'), [
        'mac_address' => 'AA:BB:CC:DD:EE:22',
        'tipo_sugerido' => 'Estação Matriz',
        'status' => 'Disponível',
    ]);
    $responseDuplicado->assertSessionHasErrors(['mac_address']);
});

test('usuario pode cadastrar multiplos patrimonios em lote', function () {
    $user = User::factory()->create();
    Patrimonio::factory()->create(['mac_address' => '11:22:33:44:55:66']);

    $batchText = "AA:11:22:33:44:55\nBB-11-22-33-44-55\nCC1122334455\n11:22:33:44:55:66\nINVALID_LINE";

    $response = $this->actingAs($user)->post(route('patrimonios.store-batch'), [
        'mac_addresses_batch' => $batchText,
        'tipo_sugerido' => 'Estação Satélite',
        'data_aquisicao' => '2026-09-01',
    ]);

    $response->assertRedirect(route('patrimonios.index'));
    $response->assertSessionHas('success');

    // Verifica que os 3 válidos foram inseridos e formatados com dois pontos
    $this->assertDatabaseHas('patrimonios', ['mac_address' => 'AA:11:22:33:44:55']);
    $this->assertDatabaseHas('patrimonios', ['mac_address' => 'BB:11:22:33:44:55']);
    $this->assertDatabaseHas('patrimonios', ['mac_address' => 'CC:11:22:33:44:55']);
});

test('usuario pode excluir um patrimonio nao vinculado', function () {
    $user = User::factory()->create();
    $patrimonio = Patrimonio::factory()->create(['created_by' => $user->id]);

    $response = $this->actingAs($user)->delete(route('patrimonios.destroy', $patrimonio->private_id));

    $response->assertRedirect(route('patrimonios.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('patrimonios', ['private_id' => $patrimonio->private_id]);
});

test('endpoint de patrimonios disponiveis retorna apenas status disponivel', function () {
    $user = User::factory()->create();
    Patrimonio::factory()->create(['status' => 'Disponível', 'mac_address' => '00:11:22:33:44:55']);
    Patrimonio::factory()->create(['status' => 'Instalado', 'mac_address' => '00:11:22:33:44:66']);

    $response = $this->actingAs($user)->getJson(route('api.patrimonios.disponiveis'));

    $response->assertOk();
    $data = $response->json();

    expect(count($data))->toBe(1);
    expect($data[0]['mac_address'])->toBe('00:11:22:33:44:55');
});
