<?php

namespace App\Http\Controllers;

use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Estado;
use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocalidadeController extends Controller
{
    /**
     * Lista todos os estados brasileiros ordenados por nome.
     */
    public function estados(): JsonResponse
    {
        $estados = Estado::orderBy('nome')->get(['id', 'nome', 'uf']);

        return response()->json($estados);
    }

    /**
     * Lista as cidades de um determinado estado.
     */
    public function cidades(Estado $estado): JsonResponse
    {
        $cidades = $estado->cidades()->orderBy('nome')->get(['id', 'nome']);

        return response()->json($cidades);
    }

    /**
     * Lista as cidades a partir da sigla UF ou ID do estado.
     */
    public function cidadesPorUf(string $uf): JsonResponse
    {
        $estado = is_numeric($uf) ? Estado::find($uf) : Estado::where('uf', strtoupper($uf))->first();

        if (! $estado) {
            return response()->json([]);
        }

        $cidades = $estado->cidades()->orderBy('nome')->get(['id', 'nome']);

        return response()->json($cidades);
    }

    /**
     * Lista os bairros de uma determinada cidade.
     */
    public function bairros(Cidade $cidade): JsonResponse
    {
        if ($cidade->bairros()->doesntExist()) {
            $this->importarBairrosOverpass($cidade);
        }

        $bairros = $cidade->bairros()->orderBy('nome')->get(['id', 'nome']);

        return response()->json($bairros);
    }

    /**
     * Consulta a API Overpass Turbo com rotação de servidores espelho e filtro por UF,
     * cruzando os dados com o banco e cadastrando apenas os bairros inéditos para a cidade.
     */
    protected function importarBairrosOverpass(Cidade $cidade): void
    {
        $cidadeNome = addslashes($cidade->nome);
        $uf = $cidade->estado?->uf;

        // Query otimizada delimitando pelo estado (UF) para busca rápida sem sobrecarregar o dispatcher
        if ($uf) {
            $query = <<<OVERPASS
[out:json][timeout:10];
area["ISO3166-2"="BR-{$uf}"]["admin_level"="4"]->.state;
area["name"="{$cidadeNome}"]["admin_level"="8"](area.state)->.searchArea;
(
  node["place"~"suburb|neighbourhood"](area.searchArea);
  way["place"~"suburb|neighbourhood"](area.searchArea);
  relation["place"~"suburb|neighbourhood"](area.searchArea);
);
out tags;
OVERPASS;
        } else {
            $query = <<<OVERPASS
[out:json][timeout:10];
area["name"="{$cidadeNome}"]["admin_level"="8"]->.searchArea;
(
  node["place"~"suburb|neighbourhood"](area.searchArea);
  way["place"~"suburb|neighbourhood"](area.searchArea);
  relation["place"~"suburb|neighbourhood"](area.searchArea);
);
out tags;
OVERPASS;
        }

        // Lista de servidores Overpass oficiais para Failover / Redundância
        $endpoints = [
            'https://overpass-api.de/api/interpreter',
            'https://overpass.kumi.systems/api/interpreter',
            'https://overpass.private.coffee/api/interpreter',
        ];

        $elements = [];

        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::timeout(8)
                    ->asForm()
                    ->withUserAgent('OpenAir_Metrics/1.0 (contato.henrique.bissoli@gmail.com)')
                    ->post($endpoint, [
                        'data' => $query,
                    ]);

                if ($response->successful()) {
                    $elements = $response->json('elements') ?? [];
                    break;
                }

                Log::warning("Endpoint {$endpoint} retornou status {$response->status()} para {$cidade->nome}. Tentando próximo espelho...");
            } catch (\Throwable $e) {
                Log::warning("Falha de conexão com {$endpoint} para {$cidade->nome}: ".$e->getMessage());
            }
        }

        // Extrai a lista de nomes únicos retornados pela API
        $bairrosApi = [];
        foreach ($elements as $element) {
            $nome = trim($element['tags']['name'] ?? '');
            if ($nome !== '' && ! in_array($nome, $bairrosApi, true)) {
                $bairrosApi[] = $nome;
            }
        }

        // Obtém os bairros que já existem no banco para esta cidade (para comparação case-insensitive)
        $bairrosExistentes = $cidade->bairros()->pluck('nome')->toArray();
        $bairrosExistentesLower = array_map('mb_strtolower', $bairrosExistentes);

        // Filtra e prepara apenas os bairros que AINDA NÃO existem no banco
        $novosBairros = [];
        $now = now();

        foreach ($bairrosApi as $nomeBairro) {
            $nomeLower = mb_strtolower($nomeBairro);
            if (! in_array($nomeLower, $bairrosExistentesLower, true)) {
                $novosBairros[] = [
                    'cidade_id' => $cidade->id,
                    'nome' => $nomeBairro,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $bairrosExistentesLower[] = $nomeLower;
            }
        }

        // Insere no banco apenas os bairros inéditos
        if (! empty($novosBairros)) {
            Bairro::insert($novosBairros);
        }

        // Se nenhum bairro existe no banco nem veio da API, cria 'Centro' como padrão
        if (empty($bairrosApi) && ! $cidade->bairros()->exists()) {
            Bairro::firstOrCreate([
                'cidade_id' => $cidade->id,
                'nome' => 'Centro',
            ]);
        }
    }

    /**
     * Retorna as coordenadas e informações de todas as estações cadastradas.
     */
    public function coordenadas(): JsonResponse
    {
        $user = Auth::user();
        $driver = DB::connection()->getDriverName();
        $query = Estacao::with(['bairro.cidade.estado', 'estacaoOrigem']);

        if ($user && $user->cidade_id) {
            $query->whereHas('bairro.cidade', function ($q) use ($user) {
                $q->where('id', $user->cidade_id);
            });
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $query->select('estacoes.*')
                ->selectRaw('ST_AsText(coordenadas) as coordenadas_wkt')
                ->selectRaw('ST_X(coordenadas) as st_longitude')
                ->selectRaw('ST_Y(coordenadas) as st_latitude');
        }

        $estacoes = $query->get()->map(function (Estacao $estacao) {
            $lat = $estacao->latitude;
            $lng = $estacao->longitude;

            return [
                'id' => $estacao->private_id,
                'public_id' => $estacao->public_id,
                'mac_address' => $estacao->mac_address,
                'tipo_estacao' => $estacao->tipo_estacao,
                'estacao_origem_id' => $estacao->estacao_origem_id,
                'matriz_pai_id' => $estacao->matriz_pai_id,
                'origem_latitude' => $estacao->estacaoOrigem?->latitude !== null ? (float) $estacao->estacaoOrigem->latitude : null,
                'origem_longitude' => $estacao->estacaoOrigem?->longitude !== null ? (float) $estacao->estacaoOrigem->longitude : null,
                'latitude' => $lat !== null ? (float) $lat : null,
                'longitude' => $lng !== null ? (float) $lng : null,
                'bairro' => $estacao->bairro_nome ?? $estacao->bairro?->nome,
                'cidade' => $estacao->cidade_nome ?? $estacao->bairro?->cidade?->nome,
                'uf' => $estacao->estado_uf ?? $estacao->bairro?->cidade?->estado?->uf,
                'endereco' => $estacao->endereco,
            ];
        });

        return response()->json($estacoes);
    }

    /**
     * Busca o endereço correspondente a uma coordenada geográfica (Reverse Geocoding) via Nominatim,
     * e cadastra o bairro no banco de dados para a cidade informada caso ainda não exista.
     */
    public function reverse(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'cidade_id' => ['nullable', 'exists:cidades,id'],
        ]);

        $lat = (float) $validated['lat'];
        $lng = (float) $validated['lng'];
        $cidadeId = isset($validated['cidade_id']) ? (int) $validated['cidade_id'] : null;

        // Busca síncrona via Nominatim / cache
        $detalhes = GeocodingService::obterDetalhesEndereco($lat, $lng, sync: true);

        // Resolve Estado e Cidade
        $estadoModel = null;
        $cidadeModel = null;
        if (! empty($detalhes['estado_uf'])) {
            $estadoModel = Estado::where('uf', strtoupper(trim((string) $detalhes['estado_uf'])))->first();
        }

        if ($cidadeId) {
            $cidadeModel = Cidade::with('estado')->find($cidadeId);
            if ($cidadeModel && ! $estadoModel) {
                $estadoModel = $cidadeModel->estado;
            }
        } elseif (! empty($detalhes['cidade'])) {
            $cidadeQuery = Cidade::where('nome', 'LIKE', trim((string) $detalhes['cidade']));
            if ($estadoModel) {
                $cidadeQuery->where('estado_id', $estadoModel->id);
            }
            $cidadeModel = $cidadeQuery->first();
            $cidadeId = $cidadeModel?->id;
            if ($cidadeModel && ! $estadoModel) {
                $estadoModel = $cidadeModel->estado;
            }
        }

        // Se encontrou o bairro via Nominatim, garante cadastro no banco se ainda não estiver cadastrado
        $bairroModel = null;
        $bairroNome = ! empty($detalhes['bairro']) ? trim((string) $detalhes['bairro']) : null;

        if ($bairroNome && $cidadeId) {
            $bairroModel = Bairro::firstOrCreate([
                'cidade_id' => $cidadeId,
                'nome' => $bairroNome,
            ]);
        }

        return response()->json([
            'endereco' => $detalhes['endereco_formatado'] ?? null,
            'logradouro' => $detalhes['logradouro'] ?? null,
            'numero' => $detalhes['numero'] ?? null,
            'bairro' => $bairroNome ?? ($bairroModel?->nome),
            'bairro_id' => $bairroModel?->id,
            'cidade' => $cidadeModel?->nome ?? $detalhes['cidade'] ?? null,
            'cidade_id' => $cidadeId,
            'estado_id' => $estadoModel?->id,
            'estado_nome' => $estadoModel?->nome,
            'estado_uf' => $estadoModel?->uf ?? $detalhes['estado_uf'] ?? null,
            'cep' => $detalhes['cep'] ?? null,
            'endereco_completo' => $detalhes['endereco_completo'] ?? null,
            'latitude' => $lat,
            'longitude' => $lng,
        ]);
    }
}
