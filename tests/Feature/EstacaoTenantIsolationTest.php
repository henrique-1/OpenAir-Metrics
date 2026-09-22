<?php

use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Patrimonio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('technician cannot substitute sensor using equipment from another municipality', function () {
    $cidade1 = Cidade::factory()->create(['nome' => 'Cidade Alpha']);
    $cidade2 = Cidade::factory()->create(['nome' => 'Cidade Beta']);

    $techCidade1 = User::factory()->create([
        'cidade_id' => $cidade1->id,
        'nivel' => 'instalador',
    ]);

    $bairroCidade1 = Bairro::factory()->create(['cidade_id' => $cidade1->id]);

    $estacaoCidade1 = Estacao::factory()->create([
        'bairro_id' => $bairroCidade1->id,
        'status_instalacao' => 'Instalada',
    ]);

    $patrimonioCidade2 = Patrimonio::factory()->create([
        'cidade_id' => $cidade2->id,
        'status' => 'Disponível',
    ]);

    $response = $this->actingAs($techCidade1)
        ->post(route('estacoes.substituir-sensor', $estacaoCidade1->public_id), [
            'patrimonio_id' => $patrimonioCidade2->private_id,
            'motivo' => 'Tentativa cross-tenant',
        ]);

    $response->assertSessionHas('error');

    // Ensure the foreign asset was not appropriated
    expect($patrimonioCidade2->fresh()->status)->toBe('Disponível');
    expect($estacaoCidade1->fresh()->patrimonio_id)->not->toBe($patrimonioCidade2->private_id);
});
