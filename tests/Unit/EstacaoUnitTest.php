<?php

use App\Models\Estacao;

test('calculo de distancia haversine funciona com precisao', function () {
    // Ponto 1: Marco Zero SP (-23.55052, -46.633308)
    // Ponto 2: ~111 metros ao norte (-23.54952, -46.633308)
    $dist = Estacao::calcularDistanciaHaversine(-23.55052, -46.633308, -23.54952, -46.633308);

    expect($dist)->toBeGreaterThan(100);
    expect($dist)->toBeLessThan(120);
});

test('estacao define e recupera latitude e longitude via accessors e mutators', function () {
    $estacao = new Estacao([
        'latitude' => -23.55052,
        'longitude' => -46.633308,
    ]);

    expect($estacao->latitude)->toBe(-23.55052);
    expect($estacao->longitude)->toBe(-46.633308);
});

test('estacao extrai latitude e longitude a partir de texto WKT', function () {
    $estacao = new Estacao;
    $estacao->setRawAttributes([
        'coordenadas' => 'POINT(-46.633308 -23.550520)',
    ]);

    expect($estacao->latitude)->toBe(-23.55052);
    expect($estacao->longitude)->toBe(-46.633308);
});
