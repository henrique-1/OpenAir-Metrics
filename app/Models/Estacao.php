<?php

namespace App\Models;

use App\Services\GeocodingService;
use Database\Factories\EstacaoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Estacao extends Model
{
    /** @use HasFactory<EstacaoFactory> */
    use HasFactory;

    /**
     * O nome da tabela associada à model.
     * Necessário pois o Laravel pluralizaria automaticamente para 'estacaos' (em inglês).
     */
    protected $table = 'estacoes';

    /**
     * A chave primária associada à tabela.
     */
    protected $primaryKey = 'private_id';

    /**
     * Os atributos que são designáveis em massa (Mass Assignment).
     */
    protected $fillable = [
        'public_id',
        'mac_address',
        'patrimonio_id',
        'tipo_estacao',
        'status_instalacao',
        'ordem_instalacao',
        'matriz_pai_id',
        'estacao_origem_id',
        'distancia_origem_metros',
        'data_instalacao',
        'instalado_por',
        'bairro_id',
        'logradouro',
        'numero',
        'bairro_nome',
        'cidade_nome',
        'estado_uf',
        'cep',
        'endereco_completo',
        'solicitacao_substituicao',
        'solicitacao_substituicao_em',
        'motivo_substituicao',
        'solicitado_por',
        'created_by',
        'coordenadas',
        'latitude',
        'longitude',
    ];

    /**
     * Os atributos que devem ser incluídos na serialização do modelo.
     */
    protected $appends = [
        'latitude',
        'longitude',
        'endereco',
    ];

    /**
     * Obter os atributos que devem ser convertidos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_instalacao' => 'datetime',
            'solicitacao_substituicao' => 'boolean',
            'solicitacao_substituicao_em' => 'datetime',
            'ordem_instalacao' => 'integer',
            'distancia_origem_metros' => 'float',
        ];
    }

    /**
     * Variáveis internas para retenção de coordenadas quando definidas via latitude/longitude.
     */
    protected ?float $tempLatitude = null;

    protected ?float $tempLongitude = null;

    /**
     * O método "booted" do modelo.
     * Utilizado aqui para injetar um UUID automaticamente antes de criar o registro no banco
     * e converter latitude/longitude para a coluna geométrica POINT.
     */
    protected static function booted(): void
    {
        static::creating(function (Estacao $estacao) {
            if (empty($estacao->public_id)) {
                $estacao->public_id = (string) Str::uuid();
            }
        });

        static::saving(function (Estacao $estacao) {
            $lat = $estacao->tempLatitude ?? $estacao->latitude;
            $lng = $estacao->tempLongitude ?? $estacao->longitude;

            if ($lat !== null && $lng !== null) {
                $connection = $estacao->getConnection();
                $driver = $connection->getDriverName();

                if ($driver === 'mysql' || $driver === 'mariadb') {
                    $estacao->attributes['coordenadas'] = DB::raw("ST_GeomFromText('POINT({$lng} {$lat})', 4326)");
                } else {
                    $estacao->attributes['coordenadas'] = "POINT({$lng} {$lat})";
                }
            }

            unset($estacao->attributes['latitude'], $estacao->attributes['longitude']);
        });
    }

    /**
     * Accessor e Mutator para Latitude.
     */
    protected function latitude(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                if ($this->tempLatitude !== null) {
                    return $this->tempLatitude;
                }
                if ($value !== null && is_numeric($value)) {
                    return (float) $value;
                }
                if (isset($attributes['st_latitude']) && is_numeric($attributes['st_latitude'])) {
                    return (float) $attributes['st_latitude'];
                }
                if (! empty($attributes['coordenadas_wkt'])) {
                    $coords = $this->extractCoordinatesFromAttribute($attributes['coordenadas_wkt']);
                    if ($coords) {
                        return $coords['latitude'];
                    }
                }
                $coords = $this->extractCoordinatesFromAttribute($attributes['coordenadas'] ?? null);

                return $coords['latitude'] ?? null;
            },
            set: function ($value, array $attributes) {
                $this->tempLatitude = $value !== null ? (float) $value : null;

                return [];
            }
        );
    }

    /**
     * Accessor e Mutator para Longitude.
     */
    protected function longitude(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                if ($this->tempLongitude !== null) {
                    return $this->tempLongitude;
                }
                if ($value !== null && is_numeric($value)) {
                    return (float) $value;
                }
                if (isset($attributes['st_longitude']) && is_numeric($attributes['st_longitude'])) {
                    return (float) $attributes['st_longitude'];
                }
                if (! empty($attributes['coordenadas_wkt'])) {
                    $coords = $this->extractCoordinatesFromAttribute($attributes['coordenadas_wkt']);
                    if ($coords) {
                        return $coords['longitude'];
                    }
                }
                $coords = $this->extractCoordinatesFromAttribute($attributes['coordenadas'] ?? null);

                return $coords['longitude'] ?? null;
            },
            set: function ($value, array $attributes) {
                $this->tempLongitude = $value !== null ? (float) $value : null;

                return [];
            }
        );
    }

    /**
     * Formata um nome de logradouro em Title/Camel Case (ex: "Rua das Flores", "Avenida Brasil").
     */
    public static function formatarLogradouro(?string $logradouro): ?string
    {
        if (! $logradouro) {
            return null;
        }

        $logradouro = trim($logradouro);
        if ($logradouro === '') {
            return null;
        }

        $preposicoes = ['de', 'da', 'do', 'das', 'dos', 'e', 'em'];
        $palavras = preg_split('/\s+/', mb_strtolower($logradouro, 'UTF-8'));
        $formatadas = [];

        foreach ($palavras as $index => $palavra) {
            if ($index > 0 && in_array($palavra, $preposicoes, true)) {
                $formatadas[] = $palavra;
            } else {
                $formatadas[] = mb_convert_case($palavra, MB_CASE_TITLE, 'UTF-8');
            }
        }

        return implode(' ', $formatadas);
    }

    /**
     * Accessor e Mutator para Logradouro em Title/Camel Case.
     */
    protected function logradouro(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => self::formatarLogradouro($value),
            set: fn (?string $value) => self::formatarLogradouro($value),
        );
    }

    /**
     * Accessor para Endereço (Prioriza colunas salvas no banco, com fallback para GeocodingService).
     */
    protected function endereco(): Attribute
    {
        return Attribute::make(
            get: function ($value, array $attributes) {
                $logradouro = $attributes['logradouro'] ?? $this->logradouro ?? null;
                $numero = $attributes['numero'] ?? $this->numero ?? null;

                if ($logradouro) {
                    return $numero ? "{$logradouro}, {$numero}" : $logradouro;
                }

                if (! empty($attributes['endereco_completo'])) {
                    $parts = explode(',', $attributes['endereco_completo']);

                    return trim($parts[0] ?? '');
                }

                $lat = $this->latitude;
                $lng = $this->longitude;

                if ($lat === null || $lng === null) {
                    return null;
                }

                return GeocodingService::buscarEndereco($lat, $lng);
            }
        );
    }

    /**
     * Mutator para o atributo de coordenadas.
     */
    protected function coordenadas(): Attribute
    {
        return Attribute::make(
            set: function ($value) {
                if (is_array($value)) {
                    $lat = $value['latitude'] ?? $value['lat'] ?? $value[1] ?? null;
                    $lng = $value['longitude'] ?? $value['lng'] ?? $value[0] ?? null;
                    if ($lat !== null && $lng !== null) {
                        $this->tempLatitude = (float) $lat;
                        $this->tempLongitude = (float) $lng;
                    }
                } elseif (is_string($value)) {
                    $coords = $this->extractCoordinatesFromAttribute($value);
                    if ($coords) {
                        $this->tempLatitude = $coords['latitude'];
                        $this->tempLongitude = $coords['longitude'];
                    }
                }

                return $value;
            }
        );
    }

    /**
     * Extrai latitude e longitude a partir de texto WKT ou binário WKB do MySQL.
     *
     * @return array{latitude: float, longitude: float}|null
     */
    protected function extractCoordinatesFromAttribute($value): ?array
    {
        if (empty($value) || is_array($value)) {
            return null;
        }

        // Se for string WKT no formato POINT(lng lat)
        if (is_string($value) && preg_match('/POINT\s*\(\s*([-\d.]+)\s+([-\d.]+)\s*\)/i', $value, $matches)) {
            return [
                'longitude' => (float) $matches[1],
                'latitude' => (float) $matches[2],
            ];
        }

        // Se for binário WKB retornado pelo MySQL
        if (is_string($value) && strlen($value) >= 21) {
            // MySQL 8+ adiciona 4 bytes de SRID no início (25 bytes no total para Point)
            if (strlen($value) === 25) {
                $unpacked = @unpack('Vsrid/corder/Vtype/dlon/dlat', $value);
                if ($unpacked && isset($unpacked['dlat'], $unpacked['dlon'])) {
                    return [
                        'longitude' => (float) $unpacked['dlon'],
                        'latitude' => (float) $unpacked['dlat'],
                    ];
                }
            } elseif (strlen($value) === 21) {
                $unpacked = @unpack('corder/Vtype/dlon/dlat', $value);
                if ($unpacked && isset($unpacked['dlat'], $unpacked['dlon'])) {
                    return [
                        'longitude' => (float) $unpacked['dlon'],
                        'latitude' => (float) $unpacked['dlat'],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Relacionamento: A estação pertence a um bairro.
     */
    public function bairro(): BelongsTo
    {
        return $this->belongsTo(Bairro::class, 'bairro_id');
    }

    /**
     * Relacionamento: Uma estação possui várias medições ambientais.
     */
    public function medicoes(): HasMany
    {
        return $this->hasMany(Medicao::class, 'estacao_id', 'private_id');
    }

    /**
     * Relacionamento: A estação pertence a um usuário (criador).
     */
    public function criador(): BelongsTo
    {
        // O segundo parâmetro informa explicitamente qual é a chave estrangeira na tabela 'estacoes'
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relacionamento: Item de patrimônio físico vinculado.
     */
    public function patrimonio(): BelongsTo
    {
        return $this->belongsTo(Patrimonio::class, 'patrimonio_id', 'private_id');
    }

    /**
     * Relacionamento: Estação Matriz que coordena este cluster/malha.
     */
    public function matrizPai(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'matriz_pai_id', 'private_id');
    }

    /**
     * Relacionamento: Estação anterior da qual esta recebe sinal em topologia de rede.
     */
    public function estacaoOrigem(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'estacao_origem_id', 'private_id');
    }

    /**
     * Relacionamento: Estações Satélites que se conectam a esta.
     */
    public function satelitesFilhas(): HasMany
    {
        return $this->hasMany(Estacao::class, 'estacao_origem_id', 'private_id');
    }

    /**
     * Relacionamento: Todas as Estações Satélites pertencentes a esta malha/cluster.
     */
    public function satelitesMalha(): HasMany
    {
        return $this->hasMany(Estacao::class, 'matriz_pai_id', 'private_id')
            ->where('tipo_estacao', 'Estação Satélite');
    }

    /**
     * Escopo para carregar coordenadas de forma compatível com MySQL/MariaDB.
     */
    public function scopeWithCoordinates(Builder $query): Builder
    {
        $driver = $query->getConnection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            return $query->select('estacoes.*')
                ->selectRaw('ST_AsText(coordenadas) as coordenadas_wkt')
                ->selectRaw('ST_X(coordenadas) as st_longitude')
                ->selectRaw('ST_Y(coordenadas) as st_latitude');
        }

        return $query;
    }

    /**
     * Relacionamento: Usuário/técnico que realizou a instalação em campo.
     */
    public function instalador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instalado_por');
    }

    /**
     * Relacionamento: Usuário que solicitou a substituição da estação.
     */
    public function solicitanteSubstituicao(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    /**
     * Calcula o ciclo e a expectativa de vida útil dos sensores da estação.
     * Referência técnica: Vida útil mínima de 5 anos (especificação do sensor MH-Z19C).
     *
     * @return array{
     *     instalada: bool,
     *     data_instalacao: Carbon|null,
     *     data_expiracao: Carbon|null,
     *     anos_vida_util_nominal: int,
     *     dias_decorridos: int|null,
     *     dias_restantes: int|null,
     *     porcentagem_restante: float,
     *     status: string,
     *     badge_class: string
     * }
     */
    public function calcularVidaUtil(): array
    {
        if (! $this->data_instalacao) {
            return [
                'instalada' => false,
                'data_instalacao' => null,
                'data_expiracao' => null,
                'anos_vida_util_nominal' => 5,
                'dias_decorridos' => null,
                'dias_restantes' => null,
                'porcentagem_restante' => 100.0,
                'status' => 'Não Instalada',
                'badge_class' => 'bg-athens-gray-50 text-athens-gray-600 border border-athens-gray-200 dark:bg-athens-gray-800 dark:text-athens-gray-300',
            ];
        }

        $dataInstalacao = $this->data_instalacao;
        $dataExpiracao = $dataInstalacao->copy()->addYears(5);
        $totalDias = 5 * 365.25;
        $diasDecorridos = (int) $dataInstalacao->diffInDays(now(), false);
        $diasRestantes = (int) now()->diffInDays($dataExpiracao, false);

        $porcentagemRestante = (float) max(0, min(100, round(($diasRestantes / $totalDias) * 100, 1)));

        $status = 'Normal';
        $badgeClass = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400';

        if ($diasRestantes <= 0) {
            $status = 'Vencida';
            $badgeClass = 'bg-cinnabar-100 text-cinnabar-800 dark:bg-cinnabar-900/30 dark:text-cinnabar-400';
        } elseif ($diasRestantes <= 180) { // Menos de 6 meses
            $status = 'Crítica (< 6 meses)';
            $badgeClass = 'bg-tahiti-gold-100 text-tahiti-gold-800 dark:bg-tahiti-gold-900/30 dark:text-tahiti-gold-400';
        }

        return [
            'instalada' => true,
            'data_instalacao' => $dataInstalacao,
            'data_expiracao' => $dataExpiracao,
            'anos_vida_util_nominal' => 5,
            'dias_decorridos' => max(0, $diasDecorridos),
            'dias_restantes' => $diasRestantes,
            'porcentagem_restante' => $porcentagemRestante,
            'status' => $status,
            'badge_class' => $badgeClass,
        ];
    }

    /**
     * Calcula a menor distância em metros de uma coordenada até qualquer Estação Matriz cadastrada no banco.
     *
     * @param  int|null  $exceptPrivateId  ID de estação a desconsiderar (opcional para updates)
     * @return float|null Retorna a distância em metros ou null se não houver estações matrizes cadastradas.
     */
    public static function menorDistanciaAteMatriz(float $latitude, float $longitude, ?int $exceptPrivateId = null): ?float
    {
        $query = static::query()->where('tipo_estacao', 'Estação Matriz');

        if ($exceptPrivateId !== null) {
            $query->where('private_id', '!=', $exceptPrivateId);
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $result = $query->selectRaw(
                "MIN(ST_Distance_Sphere(coordenadas, ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326))) as menor_distancia",
                [$longitude, $latitude]
            )->value('menor_distancia');

            return $result !== null ? (float) $result : null;
        }

        // Fallback para SQLite ou drivers sem funções espaciais nativas
        $estacoes = $query->get();
        if ($estacoes->isEmpty()) {
            return null;
        }

        $menorDistancia = null;
        foreach ($estacoes as $estacao) {
            if ($estacao->latitude === null || $estacao->longitude === null) {
                continue;
            }

            $dist = static::calcularDistanciaHaversine(
                $latitude,
                $longitude,
                $estacao->latitude,
                $estacao->longitude
            );

            if ($menorDistancia === null || $dist < $menorDistancia) {
                $menorDistancia = $dist;
            }
        }

        return $menorDistancia;
    }

    /**
     * Calcula a menor distância em metros de uma coordenada até qualquer estação cadastrada no banco.
     *
     * @param  int|null  $exceptPrivateId  ID de estação a desconsiderar (opcional para updates)
     * @return float|null Retorna a distância em metros ou null se não houver estações cadastradas.
     */
    public static function menorDistanciaAte(float $latitude, float $longitude, ?int $exceptPrivateId = null): ?float
    {
        $query = static::query();

        if ($exceptPrivateId !== null) {
            $query->where('private_id', '!=', $exceptPrivateId);
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $result = $query->selectRaw(
                "MIN(ST_Distance_Sphere(coordenadas, ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326))) as menor_distancia",
                [$longitude, $latitude]
            )->value('menor_distancia');

            return $result !== null ? (float) $result : null;
        }

        // Fallback para SQLite ou drivers sem funções espaciais nativas
        $estacoes = $query->get();
        if ($estacoes->isEmpty()) {
            return null;
        }

        $menorDistancia = null;
        foreach ($estacoes as $estacao) {
            if ($estacao->latitude === null || $estacao->longitude === null) {
                continue;
            }

            $dist = static::calcularDistanciaHaversine(
                $latitude,
                $longitude,
                $estacao->latitude,
                $estacao->longitude
            );

            if ($menorDistancia === null || $dist < $menorDistancia) {
                $menorDistancia = $dist;
            }
        }

        return $menorDistancia;
    }

    /**
     * Calcula a distância entre dois pontos (em metros) utilizando a fórmula de Haversine.
     */
    public static function calcularDistanciaHaversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $raioTerra = 6371000; // Raio da Terra em metros

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $raioTerra * $c;
    }

    /**
     * Reordena uma coleção de estações da mesma malha em topologia de árvore/cascata (DFS a partir da Matriz).
     * Garante que a Estação Matriz seja o Passo #1 e que toda estação satélite apareça rigorosamente
     * após sua respectiva estação de origem (pai na malha).
     * Normaliza os valores de `ordem_instalacao` sequencialmente de 1 a N no banco e em memória.
     *
     * @param  iterable<int, Estacao>  $estacoes
     * @return Collection<int, Estacao>
     */
    public static function ordenarEmCascata(iterable $estacoes): Collection
    {
        $colecao = collect($estacoes);
        if ($colecao->isEmpty()) {
            return $colecao;
        }

        // Identifica a estação raiz (Matriz)
        $matriz = $colecao->first(function (Estacao $e) {
            return $e->tipo_estacao === 'Estação Matriz';
        }) ?? $colecao->first();

        $byId = $colecao->keyBy('private_id');

        // Mapeia filhos por estação pai (origem)
        $filhosPorPai = [];
        foreach ($colecao as $estacao) {
            if ($estacao->private_id === $matriz->private_id) {
                continue;
            }

            $paiId = $estacao->estacao_origem_id;

            // Se o pai não for informado, for a própria estação, ou não estiver na malha, assume a Matriz como pai
            if (! $paiId || $paiId === $estacao->private_id || ! $byId->has($paiId)) {
                $paiId = $matriz->private_id;
            }

            $filhosPorPai[$paiId][] = $estacao;
        }

        $ordenadas = collect();
        $visitados = [];

        // Travessia em profundidade (DFS) para garantir que cada ramo da malha viária
        // seja percorrido em cascata contínua (Pai -> Filho -> Neto -> ...)
        $dfs = function (Estacao $atual) use (&$dfs, &$ordenadas, &$visitados, &$filhosPorPai) {
            if (isset($visitados[$atual->private_id])) {
                return;
            }

            $visitados[$atual->private_id] = true;
            $ordenadas->push($atual);

            $filhos = $filhosPorPai[$atual->private_id] ?? [];

            // Ordena nós irmãos mantendo a ordem original ou ID para estabilidade
            usort($filhos, function (Estacao $a, Estacao $b) {
                $ordemA = $a->ordem_instalacao ?? PHP_INT_MAX;
                $ordemB = $b->ordem_instalacao ?? PHP_INT_MAX;

                if ($ordemA === $ordemB) {
                    return $a->private_id <=> $b->private_id;
                }

                return $ordemA <=> $ordemB;
            });

            foreach ($filhos as $filho) {
                $dfs($filho);
            }
        };

        $dfs($matriz);

        // Fallback defensivo: adiciona quaisquer estações desconectadas ou remanescentes
        foreach ($colecao as $estacao) {
            if (! isset($visitados[$estacao->private_id])) {
                $ordenadas->push($estacao);
                $visitados[$estacao->private_id] = true;
            }
        }

        // Normaliza a ordem_instalacao (1, 2, 3, ...) e atualiza no banco se necessário
        $novoById = $ordenadas->keyBy('private_id');

        foreach ($ordenadas as $posicao => $estacao) {
            $novoPasso = $posicao + 1;

            if ($estacao->ordem_instalacao !== $novoPasso) {
                $estacao->ordem_instalacao = $novoPasso;
                if ($estacao->exists) {
                    $estacao->updateQuietly(['ordem_instalacao' => $novoPasso]);
                }
            }

            // Garante que o relacionamento em memória aponte para a instância correta atualizada
            if ($estacao->estacao_origem_id && $novoById->has($estacao->estacao_origem_id)) {
                $estacao->setRelation('estacaoOrigem', $novoById->get($estacao->estacao_origem_id));
            }
        }

        return $ordenadas;
    }
}
