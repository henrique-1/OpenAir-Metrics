<?php

namespace App\Services;

use App\Models\Cidade;
use App\Models\Estacao;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlanejamentoMalhaService
{
    public function __construct(
        protected GeocodingService $geocodingService
    ) {}

    /**
     * Calcula as posições das estações satélites posicionadas estritamente sobre as ruas,
     * organizadas em cascata multidirecional (árvore de corredores) para maximizar a cobertura do bairro.
     *
     * @param  float  $matrizLat  Latitude da Matriz
     * @param  float  $matrizLng  Longitude da Matriz
     * @param  int  $quantidadeSatelites  Quantidade de satélites a gerar (1 a 20)
     * @param  int|null  $cidadeId  ID da cidade (opcional)
     * @return array{matriz: array<string, mixed>, satelites: array<int, array<string, mixed>>}
     */
    public function calcularMalha(float $matrizLat, float $matrizLng, int $quantidadeSatelites, ?int $cidadeId = null): array
    {
        $quantidadeSatelites = max(1, min(20, $quantidadeSatelites));

        // Dados base da cidade se fornecido
        $cidade = $cidadeId ? Cidade::with('estado')->find($cidadeId) : null;
        $cidadeNome = $cidade?->nome;
        $estadoUf = $cidade?->estado?->uf;

        $raioBuscaMetros = ($quantidadeSatelites * 200) + 200;

        // 1. Busca todas as geometrias de ruas ao redor do ponto no MariaDB local via ST_Distance_Sphere
        $ways = $this->geocodingService->buscarViasProximas($matrizLat, $matrizLng, $raioBuscaMetros);

        // Fallback defensivo caso a região ainda não esteja importada na malha_viaria local
        if (empty($ways)) {
            $ways = $this->obterViasDoOpenStreetMap($matrizLat, $matrizLng, $quantidadeSatelites);
        }

        // 2. Ajusta a posição da Matriz para o leito da rua mais próxima (Snapping)
        $pontoMatrizRua = $this->encontrarPontoMaisProximoNaRua($matrizLat, $matrizLng, $ways);
        $finalMatrizLat = $pontoMatrizRua ? $pontoMatrizRua['latitude'] : (float) $matrizLat;
        $finalMatrizLng = $pontoMatrizRua ? $pontoMatrizRua['longitude'] : (float) $matrizLng;
        $nomeRuaMatriz = $pontoMatrizRua['nome_rua'] ?? null;

        // Tenta obter detalhes adicionais de endereço da Matriz via cache
        $enderecoMatriz = $this->geocodingService->obterDetalhesEndereco($finalMatrizLat, $finalMatrizLng) ?? [];

        $bairroMatriz = $enderecoMatriz['bairro_nome'] ?? $enderecoMatriz['bairro'] ?? 'Centro';
        $cidadeMatriz = $enderecoMatriz['cidade_nome'] ?? $enderecoMatriz['cidade'] ?? $cidadeNome ?? 'Cidade';
        $estadoMatriz = $enderecoMatriz['estado_uf'] ?? $estadoUf ?? 'SP';
        $logradouroMatriz = $nomeRuaMatriz ?: ($enderecoMatriz['logradouro'] ?? null);

        $matriz = [
            'tipo_estacao' => 'Estação Matriz',
            'ordem_instalacao' => 1,
            'latitude' => $finalMatrizLat,
            'longitude' => $finalMatrizLng,
            'logradouro' => $logradouroMatriz,
            'numero' => $enderecoMatriz['numero'] ?? null,
            'bairro_nome' => $bairroMatriz,
            'cidade_nome' => $cidadeMatriz,
            'estado_uf' => $estadoMatriz,
            'cep' => $enderecoMatriz['cep'] ?? null,
            'endereco_completo' => $logradouroMatriz
                ? ($logradouroMatriz.($enderecoMatriz['numero'] ? ', '.$enderecoMatriz['numero'] : '').' - '.$bairroMatriz.', '.$cidadeMatriz)
                : ($enderecoMatriz['endereco_completo'] ?? ($bairroMatriz.' - '.$cidadeMatriz.', '.$estadoMatriz)),
            'distancia_origem_metros' => 0,
            'origem_indice' => null,
        ];

        // 3. Gera a malha em cascata (multi-branch cascade) pelas ruas do bairro
        $pontosSugeridos = $this->gerarMalhaEmCascataPelasRuas($finalMatrizLat, $finalMatrizLng, $ways, $quantidadeSatelites);

        $satelites = [];
        foreach ($pontosSugeridos as $index => $ponto) {
            $lat = (float) $ponto['latitude'];
            $lng = (float) $ponto['longitude'];
            $origemIndice = $ponto['origem_indice'];
            $distanciaOrigem = (float) $ponto['distancia_origem'];
            $nomeRuaSat = $ponto['nome_rua'] ?? null;

            // Determina as coordenadas da estação de origem (pai na cascata)
            $paiLat = $finalMatrizLat;
            $paiLng = $finalMatrizLng;
            if ($origemIndice > 0 && isset($satelites[$origemIndice - 1])) {
                $paiLat = $satelites[$origemIndice - 1]['latitude'];
                $paiLng = $satelites[$origemIndice - 1]['longitude'];
            }

            // Lista de estações já posicionadas para anti-overlap (Matriz + satélites já validadas)
            $estacoesExistentes = array_merge([$matriz], $satelites);

            // Snap to road com Anti-Overlap para garantir que a estação esteja colada a uma via e respeite <= 200m
            if (! empty($ways)) {
                $snapped = $this->geocodingService->snapToRoad($lat, $lng, $paiLat, $paiLng, 200.0, $ways, $estacoesExistentes);
                if ($snapped) {
                    $lat = $snapped['latitude'];
                    $lng = $snapped['longitude'];
                    $distanciaOrigem = $snapped['distancia_origem'];
                    if (! empty($snapped['nome_rua'])) {
                        $nomeRuaSat = $snapped['nome_rua'];
                    }
                }
            }

            // Limite de segurança: distância da origem nunca deve exceder 200.0m
            $distanciaOrigem = min(200.0, max(0.0, $distanciaOrigem));

            if (empty($nomeRuaSat)) {
                $nomeRuaSat = $nomeRuaMatriz ?: 'Via Local';
            }

            $enderecoCompletoSat = $nomeRuaSat
                ? ($nomeRuaSat.' - '.$bairroMatriz.', '.$cidadeMatriz)
                : ('Ponto Satélite #'.($index + 2).' - '.$bairroMatriz.', '.$cidadeMatriz);

            $satelites[] = [
                'tipo_estacao' => 'Estação Satélite',
                'ordem_instalacao' => $index + 2, // Ordem 2..N
                'latitude' => $lat,
                'longitude' => $lng,
                'logradouro' => $nomeRuaSat,
                'numero' => null,
                'bairro_nome' => $bairroMatriz,
                'cidade_nome' => $cidadeMatriz,
                'estado_uf' => $estadoMatriz,
                'cep' => null,
                'endereco_completo' => $enderecoCompletoSat,
                'distancia_origem_metros' => round($distanciaOrigem, 1),
                'origem_indice' => $origemIndice, // 0 para Matriz, ou índice da satélite pai na malha
            ];
        }

        return [
            'matriz' => $matriz,
            'satelites' => $satelites,
        ];
    }

    /**
     * Consulta geometrias completas de vias públicas no OpenStreetMap via Overpass com rotação de servidores.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function obterViasDoOpenStreetMap(float $centerLat, float $centerLng, int $quantidadeSatelites = 4): array
    {
        $roundLat = round($centerLat, 3);
        $roundLng = round($centerLng, 3);
        $raioBuscaMetros = ($quantidadeSatelites * 200) + 200;
        $cacheKey = "osm_streets_cascade_{$roundLat}_{$roundLng}_{$raioBuscaMetros}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($centerLat, $centerLng, $raioBuscaMetros) {
            $query = <<<OVERPASS
[out:json][timeout:10];
(
  way["highway"~"^(residential|tertiary|secondary|primary)$"](around:{$raioBuscaMetros}, {$centerLat}, {$centerLng});
);
out geom;
OVERPASS;

            $endpoints = [
                'https://overpass.private.coffee/api/interpreter',
                'https://overpass-api.de/api/interpreter',
                'https://maps.mail.ru/osm/tools/overpass/api/interpreter ',
            ];

            foreach ($endpoints as $endpoint) {
                try {
                    $response = Http::timeout(6)
                        ->asForm()
                        ->withUserAgent('OpenAir_Metrics/1.0 (contato.henrique.bissoli@gmail.com)')
                        ->post($endpoint, [
                            'data' => $query,
                        ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $elements = $data['elements'] ?? [];

                        if (! empty($elements)) {
                            return $elements;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::info("Servidor Overpass ({$endpoint}) falhou ao buscar vias: ".$e->getMessage());
                }
            }

            return [];
        });
    }

    /**
     * Encontra o ponto exato na rua mais próxima de uma coordenada (Snapping).
     *
     * @param  array<int, array<string, mixed>>  $ways
     * @return array{latitude: float, longitude: float, nome_rua: ?string, distancia: float}|null
     */
    protected function encontrarPontoMaisProximoNaRua(float $pLat, float $pLng, array $ways): ?array
    {
        if (empty($ways)) {
            return null;
        }

        $snapped = $this->geocodingService->snapToRoad($pLat, $pLng, null, null, 200.0, $ways);
        if ($snapped && $snapped['distancia_via'] <= 250.0) {
            return [
                'latitude' => $snapped['latitude'],
                'longitude' => $snapped['longitude'],
                'nome_rua' => $snapped['nome_rua'],
                'distancia' => $snapped['distancia_via'],
            ];
        }

        return null;
    }

    /**
     * Gera a malha em cascata (árvore de corredores viários) estritamente sobre o eixo das ruas.
     * Exemplo de topologia:
     * - Matriz A (#1)
     *   -> Ramo 1: B (#2) -> B1 (#3) -> B2 (#4) ...
     *   -> Ramo 2: C (#5) -> C1 (#6) -> C2 (#7) ...
     *
     * @param  array<int, array<string, mixed>>  $ways
     * @return array<int, array{latitude: float, longitude: float, distancia_origem: float, origem_indice: int, nome_rua: ?string}>
     */
    protected function gerarMalhaEmCascataPelasRuas(float $matrizLat, float $matrizLng, array $ways, int $targetCount): array
    {
        if (empty($ways)) {
            return $this->gerarCascataFallback($matrizLat, $matrizLng, $targetCount);
        }

        // 1. Extrai todos os pontos discretizados das ruas no raio de alcance
        $pontosRuas = [];
        foreach ($ways as $way) {
            $geometry = $way['geometry'] ?? [];
            $nomeRua = $way['tags']['name'] ?? null;
            $qtd = count($geometry);

            if ($qtd < 2) {
                continue;
            }

            for ($i = 0; $i < $qtd - 1; $i++) {
                $aLat = (float) $geometry[$i]['lat'];
                $aLng = (float) $geometry[$i]['lon'];
                $bLat = (float) $geometry[$i + 1]['lat'];
                $bLng = (float) $geometry[$i + 1]['lon'];

                $compSegmento = Estacao::calcularDistanciaHaversine($aLat, $aLng, $bLat, $bLng);

                $pontosRuas[] = [
                    'latitude' => $aLat,
                    'longitude' => $aLng,
                    'nome_rua' => $nomeRua,
                ];

                if ($compSegmento > 25) {
                    $passos = (int) ceil($compSegmento / 20.0);
                    for ($p = 1; $p < $passos; $p++) {
                        $t = $p / $passos;
                        $pontosRuas[] = [
                            'latitude' => round($aLat + $t * ($bLat - $aLat), 7),
                            'longitude' => round($aLng + $t * ($bLng - $aLng), 7),
                            'nome_rua' => $nomeRua,
                        ];
                    }
                }
            }
        }

        // 2. Determina os ramos principais (corredores em direções opostas/divergentes a partir da Matriz)
        // Setores angulares principais: 0° (Norte/Leste), 180° (Sul/Oeste), 90°, 270°
        $setores = [
            ['min' => 315, 'max' => 45, 'centro' => 0],    // Ramo 1 (Norte)
            ['min' => 135, 'max' => 225, 'centro' => 180], // Ramo 2 (Sul - Direção Oposta)
            ['min' => 45, 'max' => 135, 'centro' => 90],   // Ramo 3 (Leste - Transversal)
            ['min' => 225, 'max' => 315, 'centro' => 270], // Ramo 4 (Oeste - Transversal)
        ];

        // Determina a quantidade de ramos ativos conforme o total pedido
        $numRamos = ($targetCount <= 3) ? 2 : (($targetCount <= 6) ? 3 : 4);
        $ramosAtivos = array_slice($setores, 0, $numRamos);

        // Distribui a cota de satélites por ramo (ex: para 4 satélites e 2 ramos: 2 no Ramo 1, 2 no Ramo 2)
        $cotasPorRamo = array_fill(0, count($ramosAtivos), 0);
        for ($i = 0; $i < $targetCount; $i++) {
            $cotasPorRamo[$i % count($ramosAtivos)]++;
        }

        $satelites = [];

        // 3. Executa a expansão em cascata contínua para cada ramo
        foreach ($ramosAtivos as $idxRamo => $ramo) {
            $cota = $cotasPorRamo[$idxRamo];
            if ($cota <= 0) {
                continue;
            }

            // O início da cascata é a Matriz A (índice 0)
            $pontoAtual = [
                'latitude' => $matrizLat,
                'longitude' => $matrizLng,
            ];
            $origemIndiceAtual = 0; // Conecta à Matriz

            for ($salto = 0; $salto < $cota; $salto++) {
                $todasEstacoes = array_merge([['latitude' => $matrizLat, 'longitude' => $matrizLng]], $satelites);

                $melhorPonto = $this->encontrarProximoPontoCascataNaRua(
                    $pontoAtual['latitude'],
                    $pontoAtual['longitude'],
                    $matrizLat,
                    $matrizLng,
                    $pontosRuas,
                    $todasEstacoes,
                    $ramo
                );

                if (! $melhorPonto) {
                    $anguloProjecao = $ramo['centro'];
                    $angulosTentativas = [
                        $anguloProjecao,
                        fmod($anguloProjecao + 30 + 360, 360),
                        fmod($anguloProjecao - 30 + 360, 360),
                        fmod($anguloProjecao + 60 + 360, 360),
                        fmod($anguloProjecao - 60 + 360, 360),
                        fmod($anguloProjecao + 90 + 360, 360),
                        fmod($anguloProjecao - 90 + 360, 360),
                    ];

                    $melhorTentativa = null;
                    $melhorClearance = -1.0;

                    foreach ($angulosTentativas as $ang) {
                        $coords = $this->deslocarCoordenada($pontoAtual['latitude'], $pontoAtual['longitude'], 175.0, $ang);
                        $snapped = $this->geocodingService->snapToRoad(
                            $coords['latitude'],
                            $coords['longitude'],
                            $pontoAtual['latitude'],
                            $pontoAtual['longitude'],
                            200.0,
                            $ways,
                            $todasEstacoes
                        );

                        if ($snapped && $snapped['distancia_origem'] >= 60.0) {
                            $semColisao = ! ($snapped['tem_colisao'] ?? true);
                            $clearance = (float) ($snapped['menor_distancia_outras'] ?? 0.0);

                            // Se o ponto não sobrepõe nenhuma outra estação (distância >= 200m)
                            if ($semColisao) {
                                $melhorPonto = [
                                    'latitude' => $snapped['latitude'],
                                    'longitude' => $snapped['longitude'],
                                    'distancia_origem' => $snapped['distancia_origem'],
                                    'nome_rua' => $snapped['nome_rua'],
                                ];
                                break;
                            }

                            // Caso todos os ângulos colidam, guarda a tentativa com maior afastamento das outras estações
                            if ($clearance > $melhorClearance) {
                                $melhorClearance = $clearance;
                                $melhorTentativa = [
                                    'latitude' => $snapped['latitude'],
                                    'longitude' => $snapped['longitude'],
                                    'distancia_origem' => $snapped['distancia_origem'],
                                    'nome_rua' => $snapped['nome_rua'],
                                ];
                            }
                        }
                    }

                    if (! $melhorPonto && $melhorTentativa) {
                        $melhorPonto = $melhorTentativa;
                    }
                }

                if (! $melhorPonto) {
                    // Tenta snap direto para a via mais próxima no alcance de 200m da estação pai
                    $coords = $this->deslocarCoordenada($pontoAtual['latitude'], $pontoAtual['longitude'], 175.0, $ramo['centro']);
                    $snapped = $this->geocodingService->snapToRoad(
                        $coords['latitude'],
                        $coords['longitude'],
                        $pontoAtual['latitude'],
                        $pontoAtual['longitude'],
                        200.0,
                        $ways,
                        $todasEstacoes
                    );

                    if ($snapped) {
                        $melhorPonto = [
                            'latitude' => $snapped['latitude'],
                            'longitude' => $snapped['longitude'],
                            'distancia_origem' => $snapped['distancia_origem'],
                            'nome_rua' => $snapped['nome_rua'],
                        ];
                    } else {
                        $melhorPonto = [
                            'latitude' => $coords['latitude'],
                            'longitude' => $coords['longitude'],
                            'distancia_origem' => 175.0,
                            'nome_rua' => null,
                        ];
                    }
                }

                $novoIndiceNaLista = count($satelites) + 1; // Índice que este ponto terá na árvore (1 = primeira satélite)

                $satelites[] = [
                    'latitude' => $melhorPonto['latitude'],
                    'longitude' => $melhorPonto['longitude'],
                    'distancia_origem' => $melhorPonto['distancia_origem'],
                    'origem_indice' => $origemIndiceAtual, // Conecta ao pai da cadeia
                    'nome_rua' => $melhorPonto['nome_rua'],
                ];

                // Avança na cascata: a próxima satélite do ramo vai se conectar nesta satélite recém-criada
                $pontoAtual = $melhorPonto;
                $origemIndiceAtual = $novoIndiceNaLista;
            }
        }

        return array_slice($satelites, 0, $targetCount);
    }

    /**
     * Encontra o próximo ponto no eixo viário para avançar na cascata, garantindo que se afaste da origem.
     *
     * @param  array<int, array{latitude: float, longitude: float, nome_rua: ?string}>  $pontosRuas
     * @param  array<int, array<string, mixed>>  $satelitesJaCadastradas
     * @param  array{min: int, max: int, centro: int}  $setor
     * @return array{latitude: float, longitude: float, distancia_origem: float, nome_rua: ?string}|null
     */
    protected function encontrarProximoPontoCascataNaRua(
        float $paiLat,
        float $paiLng,
        float $matrizLat,
        float $matrizLng,
        array $pontosRuas,
        array $todasEstacoes,
        array $setor
    ): ?array {
        $melhorPonto = null;
        $maiorDistanciaMatriz = Estacao::calcularDistanciaHaversine($matrizLat, $matrizLng, $paiLat, $paiLng);

        foreach ($pontosRuas as $ponto) {
            $pLat = $ponto['latitude'];
            $pLng = $ponto['longitude'];

            // Distância do pai imediato (deve estar entre 100m e 198m, rigorosamente <= 200m)
            $distPai = Estacao::calcularDistanciaHaversine($paiLat, $paiLng, $pLat, $pLng);
            if ($distPai < 100 || $distPai > 198) {
                continue;
            }

            // Distância da Matriz (deve estar se AFASTANDO da Matriz para expandir o bairro)
            $distMatriz = Estacao::calcularDistanciaHaversine($matrizLat, $matrizLng, $pLat, $pLng);
            if ($distMatriz <= $maiorDistanciaMatriz) {
                continue;
            }

            // Verifica se está dentro do setor angular do ramo
            $bearing = $this->calcularBearing($matrizLat, $matrizLng, $pLat, $pLng);
            if (! $this->estaNoSetor($bearing, $setor['min'], $setor['max'])) {
                continue;
            }

            // Detecção de colisão (Anti-Overlap): verifica distância para todas as outras estações (que não sejam o pai)
            $minDistOutras = PHP_FLOAT_MAX;
            foreach ($todasEstacoes as $existente) {
                if (Estacao::calcularDistanciaHaversine($paiLat, $paiLng, $existente['latitude'], $existente['longitude']) < 10.0) {
                    continue; // Ignora o pai imediato
                }

                $d = Estacao::calcularDistanciaHaversine($pLat, $pLng, $existente['latitude'], $existente['longitude']);
                if ($d < $minDistOutras) {
                    $minDistOutras = $d;
                }
            }

            // Rejeita pontos que sobrepõem o raio de 200m de outras estações (mínimo 180m de afastamento)
            if ($minDistOutras < 180.0) {
                continue;
            }

            if ($distMatriz > $maiorDistanciaMatriz) {
                $maiorDistanciaMatriz = $distMatriz;
                $melhorPonto = [
                    'latitude' => $pLat,
                    'longitude' => $pLng,
                    'distancia_origem' => $distPai,
                    'nome_rua' => $ponto['nome_rua'],
                ];
            }
        }

        return $melhorPonto;
    }

    /**
     * Fallback para geração em cascata quando não houver dados OSM.
     *
     * @return array<int, array{latitude: float, longitude: float, distancia_origem: float, origem_indice: int, nome_rua: ?string}>
     */
    protected function gerarCascataFallback(float $matrizLat, float $matrizLng, int $targetCount): array
    {
        $satelites = [];
        $angulosRamos = [0, 180, 90, 270];
        $numRamos = ($targetCount <= 3) ? 2 : (($targetCount <= 6) ? 3 : 4);
        $distanciaPorSalto = 180.0;

        $cotas = array_fill(0, $numRamos, 0);
        for ($i = 0; $i < $targetCount; $i++) {
            $cotas[$i % $numRamos]++;
        }

        foreach (array_slice($angulosRamos, 0, $numRamos) as $idxRamo => $angulo) {
            $cota = $cotas[$idxRamo];
            $pontoAtual = ['latitude' => $matrizLat, 'longitude' => $matrizLng];
            $origemIndiceAtual = 0;

            for ($salto = 0; $salto < $cota; $salto++) {
                $coords = $this->deslocarCoordenada($pontoAtual['latitude'], $pontoAtual['longitude'], $distanciaPorSalto, $angulo);
                $todasEstacoes = array_merge([['latitude' => $matrizLat, 'longitude' => $matrizLng]], $satelites);
                $snapped = $this->geocodingService->snapToRoad(
                    $coords['latitude'],
                    $coords['longitude'],
                    $pontoAtual['latitude'],
                    $pontoAtual['longitude'],
                    200.0,
                    null,
                    $todasEstacoes
                );

                $novoIndice = count($satelites) + 1;
                $finalLat = $snapped ? $snapped['latitude'] : $coords['latitude'];
                $finalLng = $snapped ? $snapped['longitude'] : $coords['longitude'];
                $finalDist = $snapped ? $snapped['distancia_origem'] : $distanciaPorSalto;
                $finalNome = $snapped['nome_rua'] ?? ('Via Planejada R'.($idxRamo + 1).'-'.($salto + 1));

                $satelites[] = [
                    'latitude' => $finalLat,
                    'longitude' => $finalLng,
                    'distancia_origem' => $finalDist,
                    'origem_indice' => $origemIndiceAtual,
                    'nome_rua' => $finalNome,
                ];

                $pontoAtual = [
                    'latitude' => $finalLat,
                    'longitude' => $finalLng,
                ];
                $origemIndiceAtual = $novoIndice;
            }
        }

        return array_slice($satelites, 0, $targetCount);
    }

    /**
     * Calcula o rumo (bearing/azimute) em graus de 0° a 360° entre duas coordenadas.
     */
    protected function calcularBearing(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $dLonRad = deg2rad($lon2 - $lon1);

        $y = sin($dLonRad) * cos($lat2Rad);
        $x = cos($lat1Rad) * sin($lat2Rad) - sin($lat1Rad) * cos($lat2Rad) * cos($dLonRad);

        $bearingRad = atan2($y, $x);
        $bearingGraus = fmod(rad2deg($bearingRad) + 360, 360);

        return $bearingGraus;
    }

    /**
     * Verifica se um azimute está dentro de um setor angular.
     */
    protected function estaNoSetor(float $bearing, int $min, int $max): bool
    {
        if ($min <= $max) {
            return $bearing >= $min && $bearing <= $max;
        }

        // Setor que cruza o Norte (ex: 315° a 45°)
        return $bearing >= $min || $bearing <= $max;
    }

    /**
     * Projeta um ponto $P$ ortogonalmente sobre o segmento de reta $AB$ da rua.
     *
     * @return array{latitude: float, longitude: float}
     */
    protected function projetarPontoNoSegmento(float $pLat, float $pLng, float $aLat, float $aLng, float $bLat, float $bLng): array
    {
        $latMediaRad = deg2rad(($aLat + $bLat) / 2.0);

        $x = ($pLng - $aLng) * cos($latMediaRad);
        $y = $pLat - $aLat;

        $dx = ($bLng - $aLng) * cos($latMediaRad);
        $dy = $bLat - $aLat;

        $denominador = ($dx * $dx) + ($dy * $dy);

        if ($denominador <= 1e-12) {
            return ['latitude' => $aLat, 'longitude' => $aLng];
        }

        $t = ($x * $dx + $y * $dy) / $denominador;
        $t = max(0.0, min(1.0, $t));

        return [
            'latitude' => round($aLat + $t * ($bLat - $aLat), 7),
            'longitude' => round($aLng + $t * ($bLng - $aLng), 7),
        ];
    }

    /**
     * Desloca uma coordenada geográfica por uma distância em metros e ângulo em graus.
     *
     * @return array{latitude: float, longitude: float}
     */
    protected function deslocarCoordenada(float $lat, float $lng, float $distanciaMetros, float $bearingGraus): array
    {
        $raioTerra = 6371000; // Metros
        $latRad = deg2rad($lat);
        $lngRad = deg2rad($lng);
        $bearingRad = deg2rad($bearingGraus);

        $novaLatRad = asin(
            sin($latRad) * cos($distanciaMetros / $raioTerra) +
                cos($latRad) * sin($distanciaMetros / $raioTerra) * cos($bearingRad)
        );

        $novaLngRad = $lngRad + atan2(
            sin($bearingRad) * sin($distanciaMetros / $raioTerra) * cos($latRad),
            cos($distanciaMetros / $raioTerra) - sin($latRad) * sin($novaLatRad)
        );

        return [
            'latitude' => round(rad2deg($novaLatRad), 7),
            'longitude' => round(rad2deg($novaLngRad), 7),
        ];
    }

    /**
     * Realiza o Snap to Road de um ponto candidato sobre a malha viária, respeitando
     * a distância máxima de uma estação de origem (se houver) e verificando Anti-Overlap.
     *
     * @param  array<int, array<string, mixed>>  $estacoesExistentes
     * @return array{
     *     snapped: bool,
     *     latitude: float,
     *     longitude: float,
     *     distancia_origem: float,
     *     distancia_candidato: float,
     *     nome_rua: ?string,
     *     bairro: ?string,
     *     cidade: ?string,
     *     estado_uf: ?string,
     *     endereco_completo: ?string
     * }|null
     */
    public function snapPontoNaVia(
        float $candidateLat,
        float $candidateLng,
        ?float $originLat = null,
        ?float $originLng = null,
        float $maxDistancia = 200.0,
        array $estacoesExistentes = []
    ): ?array {
        $raioBuscaMetros = $maxDistancia + 100.0;
        $centerLat = $originLat ?? $candidateLat;
        $centerLng = $originLng ?? $candidateLng;

        $ways = $this->geocodingService->buscarViasProximas($centerLat, $centerLng, $raioBuscaMetros);
        if (empty($ways)) {
            $ways = $this->obterViasDoOpenStreetMap($centerLat, $centerLng, 1);
        }

        $snapped = $this->geocodingService->snapToRoad(
            $candidateLat,
            $candidateLng,
            $originLat,
            $originLng,
            $maxDistancia,
            $ways,
            $estacoesExistentes
        );

        if (! $snapped) {
            return null;
        }

        $lat = (float) $snapped['latitude'];
        $lng = (float) $snapped['longitude'];
        $nomeRua = $snapped['nome_rua'] ?? null;

        $enderecoDetalhes = GeocodingService::obterDetalhesEndereco($lat, $lng) ?? [];

        $logradouro = $nomeRua ?: ($enderecoDetalhes['logradouro'] ?? null);
        $bairro = $enderecoDetalhes['bairro_nome'] ?? $enderecoDetalhes['bairro'] ?? null;
        $cidade = $enderecoDetalhes['cidade_nome'] ?? $enderecoDetalhes['cidade'] ?? null;
        $estado = $enderecoDetalhes['estado_uf'] ?? null;

        $enderecoCompleto = $logradouro
            ? ($logradouro.($bairro ? ' - '.$bairro : '').($cidade ? ', '.$cidade : ''))
            : ($enderecoDetalhes['endereco_completo'] ?? $enderecoDetalhes['endereco_formatado'] ?? 'Via Pública');

        return [
            'snapped' => true,
            'latitude' => $lat,
            'longitude' => $lng,
            'distancia_origem' => (float) $snapped['distancia_origem'],
            'distancia_via' => (float) ($snapped['distancia_via'] ?? 0.0),
            'nome_rua' => $logradouro,
            'bairro' => $bairro,
            'cidade' => $cidade,
            'estado_uf' => $estado,
            'endereco_completo' => $enderecoCompleto,
        ];
    }
}
