<?php

namespace App\Models;

use App\Services\GeocodingService;
use Database\Factories\EstacaoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'tipo_estacao',
        'bairro_id',
        'logradouro',
        'numero',
        'bairro_nome',
        'cidade_nome',
        'estado_uf',
        'cep',
        'endereco_completo',
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
}
