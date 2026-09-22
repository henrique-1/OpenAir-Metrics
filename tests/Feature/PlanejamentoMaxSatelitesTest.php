<?php

use App\Models\Cidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('saving mesh rejects more than 20 satellites', function () {
    $cidade = Cidade::factory()->create();
    $user = User::factory()->create([
        'cidade_id' => $cidade->id,
        'nivel' => 'planejador',
    ]);

    $satelites = [];
    for ($i = 0; $i < 25; $i++) {
        $satelites[] = [
            'latitude' => -23.5 + ($i * 0.001),
            'longitude' => -46.6 + ($i * 0.001),
        ];
    }

    $response = $this->actingAs($user)->post(route('estacoes.salvar-malha'), [
        'cidade_id' => $cidade->id,
        'matriz' => [
            'latitude' => -23.55,
            'longitude' => -46.63,
            'nome' => 'Matriz Test',
        ],
        'satelites' => $satelites,
    ]);

    $response->assertSessionHasErrors('satelites');
});
