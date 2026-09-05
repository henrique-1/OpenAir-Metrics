<?php

namespace App\Services;

use App\Jobs\ResolveReverseGeocodingJob;
use App\Models\Estacao;
use App\Models\MalhaViaria;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Raio da Terra em metros para cálculo geodésico de fallback.
     */
    protected const RAIO_TERRA_METROS = 6371000.0;

    /**
     * Busca as vias públicas no banco de dados MariaDB dentro do raio especificado em metros,
     * utilizando a função espacial nativa ST_Distance_Sphere e coordenadas POINT(longitude latitude).
     *
     * @return array<int, array{id: int, distancia: float, geometry: array<int, array{lat: float, lon: float}>, tags: array<string, mixed>}>
     */
    public function buscarViasProximas(float $lat, float $lng, float $raioMetros = 200.0): array
    {
        try {
            if (DB::getDriverName() === 'sqlite') {
                return $this->buscarViasProximasSqlite($lat, $lng, $raioMetros);
            }

            // Cria Bounding Box para filtro via MBRIntersects utilizando o SPATIAL INDEX do MariaDB
            $deltaLat = $raioMetros / 111320.0;
            $deltaLng = $raioMetros / (111320.0 * max(0.1, cos(deg2rad($lat))));

            $minLat = $lat - $deltaLat;
            $maxLat = $lat + $deltaLat;
            $minLng = $lng - $deltaLng;
            $maxLng = $lng + $deltaLng;

            // Formato POLYGON com coordenadas (longitude latitude) fechado
            $boxWkt = sprintf(
                'POLYGON((%.7f %.7f, %.7f %.7f, %.7f %.7f, %.7f %.7f, %.7f %.7f))',
                $minLng,
                $minLat,
                $maxLng,
                $minLat,
                $maxLng,
                $maxLat,
                $minLng,
                $maxLat,
                $minLng,
                $minLat
            );

            $rows = DB::select("
                SELECT id, logradouro, tipo_via,
                       ST_AsText(geometria) as wkt
                FROM malha_viaria
                WHERE MBRIntersects(geometria, ST_GeomFromText(?, 4326))
                  AND (tipo_via IS NULL OR tipo_via NOT IN ('motorway', 'trunk', 'motorway_link', 'trunk_link'))
            ", [$boxWkt]);

            $ways = [];
            foreach ($rows as $row) {
                $geometry = $this->extrairCoordenadasDeWkt($row->wkt ?? '');

                if (count($geometry) >= 2) {
                    $minDist = null;
                    for ($i = 0; $i < count($geometry) - 1; $i++) {
                        $pA = $geometry[$i];
                        $pB = $geometry[$i + 1];
                        $proj = $this->projectPointOnSegment($lat, $lng, $pA['lat'], $pA['lon'], $pB['lat'], $pB['lon']);
                        $dist = $this->calcularDistanciaHaversine($lat, $lng, $proj['lat'], $proj['lon']);

                        if ($minDist === null || $dist < $minDist) {
                            $minDist = $dist;
                        }
                    }

                    if ($minDist !== null && $minDist <= $raioMetros) {
                        $ways[] = [
                            'id' => (int) $row->id,
                            'distancia' => round($minDist, 2),
                            'geometry' => $geometry,
                            'tags' => [
                                'name' => $row->logradouro,
                                'highway' => $row->tipo_via,
                            ],
                        ];
                    }
                }
            }

            usort($ways, fn ($a, $b) => $a['distancia'] <=> $b['distancia']);

            return $ways;
        } catch (\Throwable $e) {
            Log::warning('Erro ao buscar vias no MariaDB: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Fallback geodésico para ambiente de testes utilizando SQLite em memória.
     *
     * @return array<int, array{id: int, distancia: float, geometry: array<int, array{lat: float, lon: float}>, tags: array<string, mixed>}>
     */
    protected function buscarViasProximasSqlite(float $lat, float $lng, float $raioMetros): array
    {
        $vias = MalhaViaria::where(function ($query) {
            $query->whereNull('tipo_via')
                ->orWhereNotIn('tipo_via', ['motorway', 'trunk', 'motorway_link', 'trunk_link']);
        })->get();
        $ways = [];

        foreach ($vias as $via) {
            $coords = $this->extrairCoordenadasDeWkt($via->geometria ?? '');
            if (count($coords) < 2) {
                continue;
            }

            $minDist = null;
            for ($i = 0; $i < count($coords) - 1; $i++) {
                $pA = $coords[$i];
                $pB = $coords[$i + 1];
                $proj = $this->projectPointOnSegment($lat, $lng, $pA['lat'], $pA['lon'], $pB['lat'], $pB['lon']);
                $dist = $this->calcularDistanciaHaversine($lat, $lng, $proj['lat'], $proj['lon']);

                if ($minDist === null || $dist < $minDist) {
                    $minDist = $dist;
                }
            }

            if ($minDist !== null && $minDist <= $raioMetros) {
                $ways[] = [
                    'id' => (int) $via->id,
                    'distancia' => round($minDist, 2),
                    'geometry' => $coords,
                    'tags' => [
                        'name' => $via->logradouro,
                        'highway' => $via->tipo_via,
                    ],
                ];
            }
        }

        usort($ways, fn ($a, $b) => $a['distancia'] <=> $b['distancia']);

        return $ways;
    }

    /**
     * Converte uma string WKT LINESTRING em array de coordenadas lat/lon.
     *
     * @return array<int, array{lat: float, lon: float}>
     */
    public function extrairCoordenadasDeWkt(string $wkt): array
    {
        $coords = [];
        if (preg_match('/LINESTRING\s*\((.*?)\)/i', $wkt, $matches)) {
            $points = explode(',', $matches[1]);
            foreach ($points as $point) {
                $parts = preg_split('/\s+/', trim($point));
                if (count($parts) >= 2) {
                    $coords[] = [
                        'lat' => (float) $parts[1],
                        'lon' => (float) $parts[0],
                    ];
                }
            }
        }

        return $coords;
    }

    /**
     * Projeta um ponto em um segmento de reta e retorna o ponto projetado delimitado [A, B].
     *
     * @return array{lat: float, lon: float}
     */
    protected function projectPointOnSegment(float $pLat, float $pLng, float $aLat, float $aLng, float $bLat, float $bLng): array
    {
        $dx = $bLng - $aLng;
        $dy = $bLat - $aLat;

        if (abs($dx) < 1e-9 && abs($dy) < 1e-9) {
            return ['lat' => $aLat, 'lon' => $aLng];
        }

        $t = (($pLng - $aLng) * $dx + ($pLat - $aLat) * $dy) / ($dx * $dx + $dy * $dy);
        $t = max(0.0, min(1.0, $t));

        return [
            'lat' => $aLat + $t * $dy,
            'lon' => $aLng + $t * $dx,
        ];
    }

    /**
     * Calcula a distância ortodrômica (Haversine) entre dois pontos em metros.
     */
    protected function calcularDistanciaHaversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::RAIO_TERRA_METROS * $c;
    }

    /**
     * Realiza o snap de uma coordenada geográfica para a via pública mais próxima no banco de dados,
     * com detecção de colisão (Anti-Overlap) em relação a outras estações existentes.
     *
     * @param  float  $candidateLat  Latitude desejada / estimada
     * @param  float  $candidateLng  Longitude desejada / estimada
     * @param  float|null  $originLat  Latitude da estação de origem (pai)
     * @param  float|null  $originLng  Longitude da estação de origem (pai)
     * @param  float  $maxDistanceOrigin  Distância máxima permitida até a origem em metros (padrão: 200.0)
     * @param  array<int, array<string, mixed>>|null  $ways  Vias pré-carregadas (opcional)
     * @param  array<int, array<string, mixed>>  $estacoesExistentes  Outras estações já posicionadas para checagem anti-overlap
     * @return array{
     *     latitude: float,
     *     longitude: float,
     *     distancia_via: float,
     *     distancia_origem: float,
     *     menor_distancia_outras: float,
     *     tem_colisao: bool,
     *     nome_rua: ?string,
     *     tipo_via: ?string,
     *     via_id: ?int
     * }|null
     */
    public function snapToRoad(
        float $candidateLat,
        float $candidateLng,
        ?float $originLat = null,
        ?float $originLng = null,
        float $maxDistanceOrigin = 200.0,
        ?array $ways = null,
        array $estacoesExistentes = []
    ): ?array {
        $temOrigem = ($originLat !== null && $originLng !== null);

        // Prepara lista de outras estações existentes para detecção de colisão (Anti-Overlap)
        $outrasEstacoes = [];
        foreach ($estacoesExistentes as $est) {
            $eLat = (float) ($est['latitude'] ?? $est['lat'] ?? 0.0);
            $eLng = (float) ($est['longitude'] ?? $est['lng'] ?? $est['lon'] ?? 0.0);
            if (abs($eLat) < 1e-6 && abs($eLng) < 1e-6) {
                continue;
            }
            // Ignora a própria estação de origem (pai à qual esta estação se conecta)
            if ($temOrigem && $this->calcularDistanciaHaversine($originLat, $originLng, $eLat, $eLng) < 10.0) {
                continue;
            }
            $outrasEstacoes[] = ['lat' => $eLat, 'lon' => $eLng];
        }

        if ($ways === null) {
            $raioBuscaCand = 250.0;
            $waysCand = $this->buscarViasProximas($candidateLat, $candidateLng, $raioBuscaCand);

            $waysMap = [];
            foreach ($waysCand as $w) {
                $waysMap[$w['id']] = $w;
            }

            if ($temOrigem) {
                $raioBuscaOrig = $maxDistanceOrigin + 50.0;
                $waysOrig = $this->buscarViasProximas($originLat, $originLng, $raioBuscaOrig);
                foreach ($waysOrig as $w) {
                    $waysMap[$w['id']] = $w;
                }
            }

            $ways = array_values($waysMap);
        }

        $forbiddenHighways = ['motorway', 'trunk', 'motorway_link', 'trunk_link'];

        $bestPoint = null;
        $bestDistCand = PHP_FLOAT_MAX;
        $bestDistOrig = 0.0;
        $bestMenorDistOutras = 0.0;
        $bestHadCollision = true;
        $bestRoadName = null;
        $bestRoadType = null;
        $bestRoadId = null;

        foreach ($ways as $way) {
            $roadType = $way['tags']['highway'] ?? null;
            if ($roadType !== null && in_array($roadType, $forbiddenHighways, true)) {
                continue;
            }

            $geometry = $way['geometry'] ?? [];
            $qtd = count($geometry);
            if ($qtd < 2) {
                continue;
            }

            $roadName = $way['tags']['name'] ?? null;
            $roadId = $way['id'] ?? null;

            for ($i = 0; $i < $qtd - 1; $i++) {
                $pA = $geometry[$i];
                $pB = $geometry[$i + 1];

                // 1. Projeção ortogonal do candidato sobre o segmento viário
                $proj = $this->projectPointOnSegment(
                    $candidateLat,
                    $candidateLng,
                    $pA['lat'],
                    $pA['lon'],
                    $pB['lat'],
                    $pB['lon']
                );

                // 2. Pontos a testar (projeção, extremos e nós discretizados ao longo da via)
                $pontosTeste = [$proj, $pA, $pB];

                $segDist = $this->calcularDistanciaHaversine($pA['lat'], $pA['lon'], $pB['lat'], $pB['lon']);
                if ($segDist > 15.0) {
                    $passos = (int) ceil($segDist / 10.0);
                    for ($p = 1; $p < $passos; $p++) {
                        $t = $p / $passos;
                        $pontosTeste[] = [
                            'lat' => $pA['lat'] + $t * ($pB['lat'] - $pA['lat']),
                            'lon' => $pA['lon'] + $t * ($pB['lon'] - $pA['lon']),
                        ];
                    }
                }

                foreach ($pontosTeste as $pt) {
                    $distOrig = 0.0;
                    if ($temOrigem) {
                        $distOrig = $this->calcularDistanciaHaversine($originLat, $originLng, $pt['lat'], $pt['lon']);
                        // Descarta qualquer ponto que ultrapasse a distância permitida da estação pai (ex: 200m)
                        if ($distOrig > $maxDistanceOrigin) {
                            continue;
                        }
                    }

                    // Detecção de colisão (Anti-Overlap) com outras estações existentes
                    $menorDistOutras = PHP_FLOAT_MAX;
                    $temColisao = false;

                    foreach ($outrasEstacoes as $outra) {
                        $dOutra = $this->calcularDistanciaHaversine($outra['lat'], $outra['lon'], $pt['lat'], $pt['lon']);
                        if ($dOutra < $menorDistOutras) {
                            $menorDistOutras = $dOutra;
                        }
                        // Se estiver a menos de 200m de qualquer outra estação (que não seja o pai), há colisão de raios
                        if ($dOutra < 200.0) {
                            $temColisao = true;
                        }
                    }

                    $distCand = $this->calcularDistanciaHaversine($candidateLat, $candidateLng, $pt['lat'], $pt['lon']);

                    // Critério de Seleção:
                    // 1. Prioriza SEMPRE pontos sem colisão (distância >= 200m de todas as outras estações).
                    // 2. Entre pontos sem colisão: escolhe o que minimiza distCand e atende à expansão máxima.
                    // 3. Se todos os pontos colidirem: escolhe o que maximiza a distância para as outras estações (menor sobreposição).
                    $isBetter = false;

                    if ($bestPoint === null) {
                        $isBetter = true;
                    } elseif (! $temColisao && $bestHadCollision) {
                        // Ponto sem colisão substitui qualquer ponto com colisão
                        $isBetter = true;
                    } elseif (! $temColisao && ! $bestHadCollision) {
                        // Ambos sem colisão: escolhe o mais próximo do alvo de projeção na via
                        if ($distCand < $bestDistCand) {
                            $isBetter = true;
                        }
                    } elseif ($temColisao && $bestHadCollision) {
                        // Ambos com colisão: escolhe o que maximiza a distância para as outras estações
                        if ($menorDistOutras > $bestMenorDistOutras) {
                            $isBetter = true;
                        }
                    }

                    if ($isBetter) {
                        $bestDistCand = $distCand;
                        $bestDistOrig = $distOrig;
                        $bestMenorDistOutras = ($menorDistOutras === PHP_FLOAT_MAX) ? 9999.0 : $menorDistOutras;
                        $bestHadCollision = $temColisao;
                        $bestPoint = $pt;
                        $bestRoadName = $roadName;
                        $bestRoadType = $roadType;
                        $bestRoadId = $roadId;
                    }
                }
            }
        }

        if ($bestPoint === null) {
            return null;
        }

        return [
            'latitude' => round($bestPoint['lat'], 7),
            'longitude' => round($bestPoint['lon'], 7),
            'distancia_via' => round($bestDistCand, 2),
            'distancia_origem' => round($bestDistOrig, 2),
            'menor_distancia_outras' => round($bestMenorDistOutras, 2),
            'tem_colisao' => $bestHadCollision,
            'nome_rua' => $bestRoadName,
            'tipo_via' => $bestRoadType,
            'via_id' => $bestRoadId ? (int) $bestRoadId : null,
        ];
    }

    /**
     * Busca os detalhes estruturados de endereço para uma coordenada geográfica.
     * Consulta primeiro o cache e as estações cadastradas próximas no MariaDB.
     * Se não encontrar, despacha o Job assíncrono em fila (ResolveReverseGeocodingJob) com RateLimiter.
     *
     * @return array{
     *     logradouro: ?string,
     *     numero: ?string,
     *     bairro: ?string,
     *     bairro_nome: ?string,
     *     cidade: ?string,
     *     cidade_nome: ?string,
     *     estado_uf: ?string,
     *     cep: ?string,
     *     endereco_completo: ?string,
     *     endereco_formatado: ?string
     * }|null
     */
    public static function obterDetalhesEndereco(float $lat, float $lng, ?int $estacaoId = null, bool $dispatchJobIfMissing = true): ?array
    {
        $roundLat = round($lat, 6);
        $roundLng = round($lng, 6);
        $cacheKey = "geocoding_details_{$roundLat}_{$roundLng}";

        // 1. Verifica cache local persistido
        $cached = Cache::get($cacheKey);
        if ($cached && is_array($cached)) {
            return $cached;
        }

        // 2. Busca estacao cadastrada muito próxima (raio <= 10m) no MariaDB principal
        $estacaoProxima = null;
        if (DB::getDriverName() === 'sqlite') {
            $estacaoProxima = Estacao::whereNotNull('logradouro')
                ->get()
                ->first(function ($e) use ($lat, $lng) {
                    return $e->latitude !== null && $e->longitude !== null &&
                        abs($e->latitude - $lat) <= 0.0001 && abs($e->longitude - $lng) <= 0.0001;
                });
        } else {
            $pointWkt = sprintf('POINT(%.7f %.7f)', $lng, $lat);
            $estacaoProxima = Estacao::whereNotNull('logradouro')
                ->whereRaw('ST_Distance_Sphere(coordenadas, ST_GeomFromText(?, 4326)) <= 10', [$pointWkt])
                ->first();
        }

        if ($estacaoProxima) {
            $detalhes = [
                'logradouro' => $estacaoProxima->logradouro,
                'numero' => $estacaoProxima->numero,
                'bairro' => $estacaoProxima->bairro_nome,
                'bairro_nome' => $estacaoProxima->bairro_nome,
                'cidade' => $estacaoProxima->cidade_nome,
                'cidade_nome' => $estacaoProxima->cidade_nome,
                'estado_uf' => $estacaoProxima->estado_uf,
                'cep' => $estacaoProxima->cep,
                'endereco_completo' => $estacaoProxima->endereco_completo,
                'endereco_formatado' => $estacaoProxima->logradouro.($estacaoProxima->numero ? ', '.$estacaoProxima->numero : ''),
            ];

            Cache::put($cacheKey, $detalhes, now()->addDays(30));

            return $detalhes;
        }

        // 3. Tenta obter o nome da rua mais próxima a partir da malha_viaria local
        $service = new self;
        $viasProximas = $service->buscarViasProximas($lat, $lng, 50.0);
        $nomeRuaLocal = $viasProximas[0]['tags']['name'] ?? null;

        $detalhesPreliminares = [
            'logradouro' => $nomeRuaLocal,
            'numero' => null,
            'bairro' => null,
            'bairro_nome' => null,
            'cidade' => null,
            'cidade_nome' => null,
            'estado_uf' => null,
            'cep' => null,
            'endereco_completo' => $nomeRuaLocal,
            'endereco_formatado' => $nomeRuaLocal,
        ];

        // 4. Despacha Job assíncrono para resolução precisa de número e CEP no Nominatim via fila com RateLimiter
        if ($dispatchJobIfMissing) {
            ResolveReverseGeocodingJob::dispatch($lat, $lng, $estacaoId);

            $cachedAfterJob = Cache::get($cacheKey);
            if ($cachedAfterJob && is_array($cachedAfterJob)) {
                return $cachedAfterJob;
            }
        }

        return $detalhesPreliminares;
    }

    /**
     * Helper simplificado para retornar apenas a string do endereço formatado.
     */
    public static function buscarEndereco(float $lat, float $lng): ?string
    {
        $detalhes = self::obterDetalhesEndereco($lat, $lng);

        return $detalhes['endereco_formatado'] ?? null;
    }
}
