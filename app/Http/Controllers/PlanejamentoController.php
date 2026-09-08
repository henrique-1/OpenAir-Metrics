<?php

namespace App\Http\Controllers;

use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Estado;
use App\Services\PlanejamentoMalhaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PlanejamentoController extends Controller
{
    public function __construct(
        protected PlanejamentoMalhaService $planejamentoService
    ) {}

    /**
     * Exibe a interface do Planejador Interativo de Malha.
     */
    public function create(): View
    {
        $user = Auth::user();
        if ($user && $user->cidade_id) {
            $user->load('cidade.estado');
        }

        $cidade = $user?->cidade;
        $estados = Estado::orderBy('nome')->get();

        $query = Estacao::withCoordinates()->with(['bairro.cidade.estado', 'estacaoOrigem']);
        if ($cidade) {
            $query->whereHas('bairro.cidade', fn ($q) => $q->where('id', $cidade->id));
        }

        $estacoesExistentes = $query->get()
            ->map(function (Estacao $estacao) {
                return [
                    'id' => $estacao->private_id,
                    'public_id' => $estacao->public_id,
                    'mac_address' => $estacao->mac_address,
                    'tipo_estacao' => $estacao->tipo_estacao,
                    'estacao_origem_id' => $estacao->estacao_origem_id,
                    'matriz_pai_id' => $estacao->matriz_pai_id,
                    'origem_latitude' => $estacao->estacaoOrigem?->latitude !== null ? (float) $estacao->estacaoOrigem->latitude : null,
                    'origem_longitude' => $estacao->estacaoOrigem?->longitude !== null ? (float) $estacao->estacaoOrigem->longitude : null,
                    'latitude' => $estacao->latitude !== null ? (float) $estacao->latitude : null,
                    'longitude' => $estacao->longitude !== null ? (float) $estacao->longitude : null,
                    'bairro' => $estacao->bairro_nome ?? $estacao->bairro?->nome,
                    'cidade' => $estacao->cidade_nome ?? $estacao->bairro?->cidade?->nome,
                    'uf' => $estacao->estado_uf ?? $estacao->bairro?->cidade?->estado?->uf,
                    'endereco' => $estacao->endereco,
                ];
            });

        return view('estacoes.planejar', [
            'estados' => $estados,
            'estacoesExistentes' => $estacoesExistentes,
            'cidade' => $cidade,
            'user' => $user,
        ]);
    }

    /**
     * Endpoint da API para cálculo em tempo real das posições das satélites a <= 200m.
     */
    public function calcular(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'quantidade_satelites' => ['required', 'integer', 'min:1', 'max:20'],
            'cidade_id' => ['nullable', 'integer'],
        ]);

        $user = Auth::user();
        $lat = (float) $request->input('latitude');
        $lng = (float) $request->input('longitude');
        $quantidade = (int) $request->input('quantidade_satelites');

        if ($user && $user->cidade_id) {
            $cidadeId = $user->cidade_id;
        } else {
            $cidadeId = $request->input('cidade_id') ? (int) $request->input('cidade_id') : null;
        }

        $malha = $this->planejamentoService->calcularMalha($lat, $lng, $quantidade, $cidadeId);

        return response()->json($malha);
    }

    /**
     * Endpoint da API para ajuste e encaixe fino de uma estação sobre a via pública mais próxima (Snap to Road).
     */
    public function snapToRoad(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'origem_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'origem_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'max_distancia' => ['nullable', 'numeric', 'min:10', 'max:500'],
            'estacoes_existentes' => ['nullable', 'array'],
        ]);

        $res = $this->planejamentoService->snapPontoNaVia(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            isset($validated['origem_latitude']) ? (float) $validated['origem_latitude'] : null,
            isset($validated['origem_longitude']) ? (float) $validated['origem_longitude'] : null,
            isset($validated['max_distancia']) ? (float) $validated['max_distancia'] : 200.0,
            $validated['estacoes_existentes'] ?? []
        );

        if (! $res) {
            return response()->json([
                'snapped' => false,
                'message' => 'Nenhuma via pública encontrada dentro do alcance permitido.',
            ], 422);
        }

        return response()->json($res);
    }

    /**
     * Salva a malha planejada no banco de dados com status 'Planejada' e ordens de instalação.
     */
    public function salvar(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user && $user->cidade_id && $request->filled('cidade_id') && (int) $user->cidade_id !== (int) $request->input('cidade_id')) {
            abort(403, 'Você só tem permissão para planejar e salvar malhas na sua cidade de jurisdição municipal.');
        }

        if (is_string($request->input('matriz'))) {
            $decodedMatriz = json_decode($request->input('matriz'), true);
            if (is_array($decodedMatriz)) {
                $request->merge(['matriz' => $decodedMatriz]);
            }
        }

        if (is_string($request->input('satelites'))) {
            $decodedSatelites = json_decode($request->input('satelites'), true);
            if (is_array($decodedSatelites)) {
                $request->merge(['satelites' => $decodedSatelites]);
            }
        }

        $request->validate([
            'cidade_id' => ['required', 'exists:cidades,id'],
            'matriz' => ['required', 'array'],
            'matriz.latitude' => ['required', 'numeric'],
            'matriz.longitude' => ['required', 'numeric'],
            'satelites' => ['required', 'array', 'min:1'],
            'satelites.*.latitude' => ['required', 'numeric'],
            'satelites.*.longitude' => ['required', 'numeric'],
        ]);

        $user = Auth::user();
        $userId = $user?->id;
        $cidadeId = (int) $request->input('cidade_id');

        if ($user && $user->cidade_id && (int) $user->cidade_id !== $cidadeId) {
            abort(403, 'Você só tem permissão para planejar e salvar malhas na sua cidade de jurisdição municipal.');
        }

        $cidade = Cidade::with('estado')->findOrFail($cidadeId);

        $matrizData = $request->input('matriz');
        $satelitesData = $request->input('satelites');

        DB::beginTransaction();

        try {
            // 1. Resolve / cria o bairro da Matriz
            $bairroMatrizNome = $matrizData['bairro_nome'] ?: 'Centro';
            $bairroMatriz = Bairro::firstOrCreate(
                ['cidade_id' => $cidadeId, 'nome' => $bairroMatrizNome]
            );

            // 2. Salva a Estação Matriz planejada
            $matriz = Estacao::create([
                'tipo_estacao' => 'Estação Matriz',
                'status_instalacao' => 'Planejada',
                'ordem_instalacao' => 1,
                'bairro_id' => $bairroMatriz->id,
                'logradouro' => $matrizData['logradouro'] ?? null,
                'numero' => $matrizData['numero'] ?? null,
                'bairro_nome' => $bairroMatrizNome,
                'cidade_nome' => $cidade->nome,
                'estado_uf' => $cidade->estado?->uf ?? 'SP',
                'cep' => $matrizData['cep'] ?? null,
                'endereco_completo' => $matrizData['endereco_completo'] ?? null,
                'latitude' => $matrizData['latitude'],
                'longitude' => $matrizData['longitude'],
                'distancia_origem_metros' => 0,
                'created_by' => $userId,
            ]);

            // Define ela como a matriz raiz de si mesma
            $matriz->update(['matriz_pai_id' => $matriz->private_id]);

            // Mapeamento de instâncias para definir relacionamentos de árvore (estacao_origem_id)
            $estacoesCriadas = [
                0 => $matriz, // Índice 0 = Matriz
            ];

            // 3. Salva cada Estação Satélite planejada (1ª passagem: criação dos registros)
            foreach ($satelitesData as $index => $satData) {
                $bairroSatNome = $satData['bairro_nome'] ?: $bairroMatrizNome;
                $bairroSat = Bairro::firstOrCreate(
                    ['cidade_id' => $cidadeId, 'nome' => $bairroSatNome]
                );

                $satelite = Estacao::create([
                    'tipo_estacao' => 'Estação Satélite',
                    'status_instalacao' => 'Planejada',
                    'ordem_instalacao' => $satData['ordem_instalacao'] ?? ($index + 2),
                    'matriz_pai_id' => $matriz->private_id,
                    'estacao_origem_id' => $matriz->private_id, // valor temporário seguro
                    'distancia_origem_metros' => $satData['distancia_origem_metros'] ?? 180.0,
                    'bairro_id' => $bairroSat->id,
                    'logradouro' => $satData['logradouro'] ?? null,
                    'numero' => $satData['numero'] ?? null,
                    'bairro_nome' => $bairroSatNome,
                    'cidade_nome' => $cidade->nome,
                    'estado_uf' => $cidade->estado?->uf ?? 'SP',
                    'cep' => $satData['cep'] ?? null,
                    'endereco_completo' => $satData['endereco_completo'] ?? null,
                    'latitude' => $satData['latitude'],
                    'longitude' => $satData['longitude'],
                    'created_by' => $userId,
                ]);

                $estacoesCriadas[$index + 1] = $satelite;
            }

            // 2ª passagem: vinculação precisa da estacao_origem_id na árvore
            foreach ($satelitesData as $index => $satData) {
                $origemIndice = isset($satData['origem_indice']) ? (int) $satData['origem_indice'] : 0;
                $estacaoOrigem = $estacoesCriadas[$origemIndice] ?? $matriz;

                $estacoesCriadas[$index + 1]->update([
                    'estacao_origem_id' => $estacaoOrigem->private_id,
                ]);
            }

            // 3ª passagem: ordenação em cascata da malha para normalizar a ordem sequencial de instalação
            $todasEstacoes = Estacao::where('private_id', $matriz->private_id)
                ->orWhere('matriz_pai_id', $matriz->private_id)
                ->get();
            Estacao::ordenarEmCascata($todasEstacoes);

            DB::commit();

            return redirect()
                ->route('instalacoes.show', $matriz->public_id)
                ->with('success', 'Malha com 1 Matriz e '.count($satelitesData).' Satélites planejada com sucesso! A ordem de instalação foi gerada.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('error', 'Erro ao salvar planejamento da malha: '.$e->getMessage())
                ->withInput();
        }
    }
}
