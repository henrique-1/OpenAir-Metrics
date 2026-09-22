<?php

namespace App\Http\Controllers;

use App\Models\Estacao;
use App\Models\Patrimonio;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InstalacaoController extends Controller
{
    /**
     * Lista todas as ordens de instalação / malhas planejadas no sistema.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        if ($user?->isSuperAdmin()) {
            abort(403, 'O super-usuário só pode gerenciar administradores.');
        }

        $busca = trim((string) $request->input('busca'));
        $status = $request->input('status');
        $sort = (string) $request->input('sort', 'created_at');
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['created_at', 'ordem_instalacao', 'status_instalacao', 'mac_address'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        // Agrupa as estações pelas Estações Matrizes
        $query = Estacao::where('tipo_estacao', 'Estação Matriz')
            ->with(['bairro.cidade.estado', 'patrimonio'])
            ->withCount([
                'satelitesMalha as total_satelites',
                'satelitesMalha as instaladas_satelites' => function ($q) {
                    $q->where('status_instalacao', 'Instalada')
                        ->where('solicitacao_substituicao', false);
                },
                'satelitesMalha as substituicoes_pendentes' => function ($q) {
                    $q->where('solicitacao_substituicao', true);
                },
            ]);

        // Jurisdição municipal: se o usuário tiver cidade vinculada, restringe à sua jurisdição
        $user = Auth::user();
        if ($user && $user->cidade_id) {
            $query->whereHas('bairro', function ($bairroQuery) use ($user) {
                $bairroQuery->where('cidade_id', $user->cidade_id);
            });
        }

        // Filtro de busca
        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                $q->where('mac_address', 'like', "%{$busca}%")
                    ->orWhere('logradouro', 'like', "%{$busca}%")
                    ->orWhere('bairro_nome', 'like', "%{$busca}%")
                    ->orWhere('cidade_nome', 'like', "%{$busca}%")
                    ->orWhereHas('bairro', function ($bQ) use ($busca) {
                        $bQ->where('nome', 'like', "%{$busca}%");
                    });
            });
        }

        // Filtro por status
        if ($status === 'Instalada') {
            $query->where('status_instalacao', 'Instalada');
        } elseif ($status === 'Pendente') {
            $query->where(function ($q) {
                $q->where('status_instalacao', '!=', 'Instalada')
                    ->orWhereNull('status_instalacao');
            });
        }

        $matrizes = $query->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return view('instalacoes.index', [
            'matrizes' => $matrizes,
            'busca' => $busca,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Exibe o roteiro detalhado de instalação de uma malha específica.
     */
    public function show(string $public_id): View
    {
        $user = Auth::user();
        if ($user?->isSuperAdmin()) {
            abort(403, 'O super-usuário só pode gerenciar administradores.');
        }

        $matriz = Estacao::withCoordinates()->where('public_id', $public_id)->firstOrFail();

        if ($user && $user->cidade_id && $matriz->bairro?->cidade_id && $matriz->bairro->cidade_id !== $user->cidade_id) {
            abort(403, 'Acesso restrito ao município da sua jurisdição.');
        }

        // Busca todas as estações desta malha e reordena em cascata topológica
        $estacoes = Estacao::withCoordinates()
            ->where(function ($q) use ($matriz) {
                $q->where('private_id', $matriz->private_id)
                    ->orWhere('matriz_pai_id', $matriz->private_id);
            })
            ->with(['bairro.cidade.estado', 'patrimonio', 'estacaoOrigem', 'instalador'])
            ->orderByRaw('COALESCE(ordem_instalacao, 1) ASC')
            ->get();

        $estacoes = Estacao::ordenarEmCascata($estacoes);

        $patrimoniosQuery = Patrimonio::where('status', 'Disponível');
        if ($user && $user->cidade_id) {
            $patrimoniosQuery->where('cidade_id', $user->cidade_id);
        }

        $patrimoniosDisponiveis = $patrimoniosQuery
            ->orderBy('numero_patrimonio')
            ->orderBy('mac_address')
            ->get(['private_id', 'public_id', 'mac_address', 'numero_patrimonio']);

        return view('instalacoes.show', [
            'matriz' => $matriz,
            'estacoes' => $estacoes,
            'patrimoniosDisponiveis' => $patrimoniosDisponiveis,
        ]);
    }

    /**
     * Registra o Patrimônio ou MAC Address e finaliza a instalação de uma estação em campo.
     */
    public function vincularMac(Request $request, string $public_id): JsonResponse
    {
        $user = Auth::user();
        if (! $user?->isInstalador()) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso não autorizado. Apenas técnicos com perfil de Instalador podem registrar estações em campo.',
            ], 403);
        }

        $estacao = Estacao::where('public_id', $public_id)->firstOrFail();

        // Jurisdição municipal: se o instalador possui cidade vinculada, bloqueia tentativa em estação de outro município
        if ($user->cidade_id && $estacao->bairro?->cidade_id && $estacao->bairro->cidade_id !== $user->cidade_id) {
            return response()->json([
                'success' => false,
                'message' => 'Acesso negado. A estação pertence a outro município fora da sua jurisdição.',
            ], 403);
        }

        $request->validate([
            'mac_address' => ['nullable', 'string'],
            'numero_patrimonio' => ['nullable', 'string'],
            'identificador' => ['nullable', 'string'],
            'patrimonio_id' => ['nullable', 'string'],
        ]);

        $macInformado = trim($request->input('mac_address') ?? '');
        $patrimonioInformado = trim($request->input('numero_patrimonio') ?? '');
        $identificador = trim($request->input('identificador') ?? '');
        $patrimonioPublicId = trim($request->input('patrimonio_id') ?? '');

        // Se identificador genérico foi passado, detecta se é MAC ou Patrimônio
        if (! $macInformado && ! $patrimonioInformado && $identificador) {
            if (preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $identificador)) {
                $macInformado = $identificador;
            } else {
                $patrimonioInformado = $identificador;
            }
        }

        if (empty($macInformado) && empty($patrimonioInformado) && empty($patrimonioPublicId)) {
            return response()->json([
                'success' => false,
                'message' => 'Por favor, informe o Número de Patrimônio ou o MAC Address da placa instalada.',
            ], 422);
        }

        if (! empty($macInformado)) {
            $macInformado = strtoupper(str_replace('-', ':', $macInformado));
            if (! preg_match('/^([0-9A-Fa-f]{2}:){5}([0-9A-Fa-f]{2})$/', $macInformado)) {
                return response()->json([
                    'success' => false,
                    'message' => 'O formato do MAC Address deve ser AA:BB:CC:DD:EE:FF.',
                ], 422);
            }
        }

        // 1. Validação de Regra de Ordem: Se for Satélite, a estação de origem / Matriz precisa estar instalada
        if ($estacao->tipo_estacao === 'Estação Satélite' && $estacao->estacao_origem_id) {
            $origem = Estacao::find($estacao->estacao_origem_id);
            if ($origem && $origem->status_instalacao !== 'Instalada') {
                return response()->json([
                    'success' => false,
                    'message' => "Atenção: A estação anterior (#{$origem->ordem_instalacao} - {$origem->tipo_estacao}) deve ser instalada e ativada antes desta.",
                ], 422);
            }
        }

        // 2. Busca ou cria o item no Patrimônio
        $patrimonio = null;
        if (! empty($patrimonioPublicId)) {
            $patrimonio = Patrimonio::where('public_id', $patrimonioPublicId)->first();
        }
        if (! $patrimonio && ! empty($patrimonioInformado)) {
            $patrimonio = Patrimonio::where('numero_patrimonio', $patrimonioInformado)->first();
        }
        if (! $patrimonio && ! empty($macInformado)) {
            $patrimonio = Patrimonio::where('mac_address', $macInformado)->first();
        }

        if ($user->cidade_id && $patrimonio && $patrimonio->cidade_id && $patrimonio->cidade_id !== $user->cidade_id) {
            return response()->json([
                'success' => false,
                'message' => 'Este equipamento pertence ao patrimônio de outro município fora da sua jurisdição.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            if (! $patrimonio) {
                // Se não existir no estoque, cria novo registro automaticamente
                $patrimonio = Patrimonio::create([
                    'cidade_id' => $estacao->bairro?->cidade_id ?? Auth::user()?->cidade_id,
                    'numero_patrimonio' => $patrimonioInformado ?: null,
                    'mac_address' => $macInformado ?: null,
                    'status' => 'Instalada',
                    'data_aquisicao' => now()->toDateString(),
                    'observacoes' => 'Cadastrado automaticamente durante a instalação em campo.',
                    'created_by' => Auth::id(),
                ]);
            } else {
                // Se já estiver vinculado a outra estação ativa
                if ($patrimonio->estacao && $patrimonio->estacao->private_id !== $estacao->private_id) {
                    DB::rollBack();

                    $identificadorExibicao = $patrimonio->numero_patrimonio ? "Patrimônio #{$patrimonio->numero_patrimonio}" : "MAC {$patrimonio->mac_address}";

                    return response()->json([
                        'success' => false,
                        'message' => "Este item ({$identificadorExibicao}) já está instalado na estação #{$patrimonio->estacao->ordem_instalacao} ({$patrimonio->estacao->endereco_completo}).",
                    ], 422);
                }

                $updates = ['status' => 'Instalada'];
                if ($macInformado && empty($patrimonio->mac_address)) {
                    $updates['mac_address'] = $macInformado;
                }
                if ($patrimonioInformado && empty($patrimonio->numero_patrimonio)) {
                    $updates['numero_patrimonio'] = $patrimonioInformado;
                }
                $patrimonio->update($updates);
            }

            // Regra: Assim que o sensor for substituído, o status do patrimônio anterior deve ser alterado para descartado
            $patrimonioAntigo = null;
            if ($estacao->patrimonio_id && (int) $estacao->patrimonio_id !== (int) $patrimonio->private_id) {
                $patrimonioAntigo = Patrimonio::find($estacao->patrimonio_id);
            } elseif (! $estacao->patrimonio_id && $estacao->mac_address) {
                $patrimonioAntigo = Patrimonio::where('mac_address', $estacao->mac_address)->first();
            }

            if ($patrimonioAntigo && (int) $patrimonioAntigo->private_id !== (int) $patrimonio->private_id) {
                $patrimonioAntigo->update(['status' => 'Descartado']);
            }

            // Define o MAC que será gravado na estação
            $macFinal = $macInformado ?: $patrimonio->mac_address;

            // 3. Atualiza a Estação com o MAC Address, status Instalada e finaliza qualquer solicitação de substituição pendente
            $estacao->update([
                'mac_address' => $macFinal,
                'patrimonio_id' => $patrimonio->private_id,
                'status_instalacao' => 'Instalada',
                'data_instalacao' => now(),
                'instalado_por' => Auth::id(),
                'solicitacao_substituicao' => false,
                'solicitacao_substituicao_em' => null,
                'motivo_substituicao' => null,
                'solicitado_por' => null,
            ]);

            DB::commit();

            $identificadorSucesso = $patrimonio->numero_patrimonio
                ? "Patrimônio #{$patrimonio->numero_patrimonio}".($macFinal ? " (MAC: {$macFinal})" : '')
                : "MAC {$macFinal}";

            return response()->json([
                'success' => true,
                'message' => "Estação #{$estacao->ordem_instalacao} ({$estacao->tipo_estacao}) ativada com sucesso com {$identificadorSucesso}!",
                'estacao' => [
                    'public_id' => $estacao->public_id,
                    'mac_address' => $estacao->mac_address,
                    'numero_patrimonio' => $patrimonio->numero_patrimonio,
                    'status_instalacao' => $estacao->status_instalacao,
                    'data_instalacao' => $estacao->data_instalacao ? Carbon::parse($estacao->data_instalacao)->format('d/m/Y H:i') : null,
                    'instalador_nome' => Auth::user()->name,
                ],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao registrar instalação: '.$e->getMessage(),
            ], 500);
        }
    }
}
