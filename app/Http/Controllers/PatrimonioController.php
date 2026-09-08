<?php

namespace App\Http\Controllers;

use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Patrimonio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PatrimonioController extends Controller
{
    /**
     * Exibe a listagem de equipamentos/patrimônios em estoque.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $statusFiltro = $request->input('status');
        $busca = trim((string) $request->input('busca'));
        $sort = (string) $request->input('sort', 'created_at');
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['mac_address', 'numero_patrimonio', 'status', 'data_aquisicao', 'created_at'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        $query = Patrimonio::with(['estacao', 'criador']);

        // Jurisdição municipal: restringe aos equipamentos da cidade do usuário autenticado
        if ($user && $user->cidade_id) {
            $query->where('cidade_id', $user->cidade_id);
        }

        if ($statusFiltro && in_array($statusFiltro, ['Disponível', 'Alocado', 'Instalado', 'Instalada', 'Manutenção', 'Descartado'])) {
            if ($statusFiltro === 'Instalado' || $statusFiltro === 'Instalada') {
                $query->whereIn('status', ['Instalado', 'Instalada']);
            } else {
                $query->where('status', $statusFiltro);
            }
        }

        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                $q->where('mac_address', 'like', "%{$busca}%")
                    ->orWhere('numero_patrimonio', 'like', "%{$busca}%")
                    ->orWhere('observacoes', 'like', "%{$busca}%");
            });
        }

        $patrimonios = $query->orderBy($sort, $direction)->paginate(15)->withQueryString();

        // Contadores gerais para os cards de estatísticas escopados por jurisdição
        $baseQuery = Patrimonio::query();
        if ($user && $user->cidade_id) {
            $baseQuery->where('cidade_id', $user->cidade_id);
        }

        $total = (clone $baseQuery)->count();
        $disponiveis = (clone $baseQuery)->where('status', 'Disponível')->count();
        $instalados = (clone $baseQuery)->whereIn('status', ['Instalado', 'Instalada'])->count();
        $manutencao = (clone $baseQuery)->where('status', 'Manutenção')->count();

        return view('patrimonios.index', [
            'patrimonios' => $patrimonios,
            'total' => $total,
            'disponiveis' => $disponiveis,
            'instalados' => $instalados,
            'manutencao' => $manutencao,
            'statusFiltro' => $statusFiltro,
            'busca' => $busca,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Exibe o formulário de cadastro de patrimônio.
     */
    public function create(): View
    {
        $user = Auth::user();
        if ($user && $user->cidade_id) {
            $cidades = Cidade::where('id', $user->cidade_id)->with('estado')->get();
        } else {
            $cidades = Cidade::with('estado')->orderBy('nome')->get();
        }

        return view('patrimonios.create', [
            'cidades' => $cidades,
            'cidadeUsuario' => $user?->cidade,
        ]);
    }

    /**
     * Salva um único item de patrimônio com código gerado automaticamente: OAir-Estacao-<IdCidade>-<Num>.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Jurisdição municipal: se o usuário possui cidade vinculada, bloqueia tentativa de cadastro em outro município
        if ($user && $user->cidade_id && $request->filled('cidade_id') && (int) $user->cidade_id !== (int) $request->input('cidade_id')) {
            abort(403, 'Você só tem permissão para cadastrar equipamentos dentro do seu município de jurisdição.');
        }

        $validated = $request->validate([
            'cidade_id' => ['required', 'exists:cidades,id'],
            'mac_address' => [
                'required',
                'string',
                'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
                'unique:patrimonios,mac_address',
                'unique:estacoes,mac_address',
            ],
            'data_aquisicao' => ['nullable', 'date'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ], [
            'cidade_id.required' => 'Selecione o município do patrimônio.',
            'mac_address.required' => 'O endereço MAC é obrigatório.',
            'mac_address.regex' => 'O endereço MAC deve estar no formato AA:BB:CC:DD:EE:FF.',
            'mac_address.unique' => 'Este endereço MAC já está cadastrado no sistema.',
        ]);

        if ($user && $user->cidade_id) {
            $validated['cidade_id'] = $user->cidade_id;
        }

        $validated['mac_address'] = strtoupper(trim($validated['mac_address']));
        $validated['status'] = 'Disponível';
        $validated['created_by'] = Auth::id();
        $validated['numero_patrimonio'] = Patrimonio::gerarProximoCodigo((int) $validated['cidade_id']);

        Patrimonio::create($validated);

        return redirect()
            ->route('patrimonios.index')
            ->with('success', "Item cadastrado com sucesso sob o patrimônio {$validated['numero_patrimonio']}!");
    }

    /**
     * Processa o cadastro em lote de múltiplos MAC Addresses gerando os códigos de patrimônio sequenciais.
     */
    public function storeBatch(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Jurisdição municipal: bloqueia tentativa de cadastrar lote para outro município
        if ($user && $user->cidade_id && $request->filled('cidade_id') && (int) $user->cidade_id !== (int) $request->input('cidade_id')) {
            abort(403, 'Você só tem permissão para cadastrar lotes de patrimônio dentro do seu município de jurisdição.');
        }

        $request->validate([
            'cidade_id' => ['required', 'exists:cidades,id'],
            'mac_addresses_batch' => ['required', 'string'],
            'data_aquisicao' => ['nullable', 'date'],
        ], [
            'cidade_id.required' => 'Selecione o município para o lote de patrimônio.',
            'mac_addresses_batch.required' => 'Informe a lista de endereços MAC.',
        ]);

        $cidadeId = ($user && $user->cidade_id) ? (int) $user->cidade_id : (int) $request->input('cidade_id');
        $linhas = explode("\n", $request->input('mac_addresses_batch'));
        $dataAquisicao = $request->input('data_aquisicao');
        $userId = Auth::id();

        $cadastrados = 0;
        $duplicados = 0;
        $invalidos = 0;

        foreach ($linhas as $linha) {
            $macLimpo = strtoupper(trim($linha));

            if (empty($macLimpo)) {
                continue;
            }

            // Normaliza formato com hífens ou sem separadores se necessário
            if (preg_match('/^[0-9A-F]{12}$/', $macLimpo)) {
                $macLimpo = implode(':', str_split($macLimpo, 2));
            } elseif (preg_match('/^([0-9A-F]{2}-){5}[0-9A-F]{2}$/', $macLimpo)) {
                $macLimpo = str_replace('-', ':', $macLimpo);
            }

            if (! preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $macLimpo)) {
                $invalidos++;

                continue;
            }

            // Verifica se já existe em patrimonios ou estacoes
            if (Patrimonio::where('mac_address', $macLimpo)->exists() || Estacao::where('mac_address', $macLimpo)->exists()) {
                $duplicados++;

                continue;
            }

            $numeroPatrimonio = Patrimonio::gerarProximoCodigo($cidadeId);

            Patrimonio::create([
                'cidade_id' => $cidadeId,
                'mac_address' => $macLimpo,
                'numero_patrimonio' => $numeroPatrimonio,
                'status' => 'Disponível',
                'data_aquisicao' => $dataAquisicao,
                'observacoes' => 'Cadastrado em lote.',
                'created_by' => $userId,
            ]);

            $cadastrados++;
        }

        $mensagem = "{$cadastrados} placa(s) cadastrada(s) com códigos sequenciais no patrimônio.";
        if ($duplicados > 0) {
            $mensagem .= " ({$duplicados} MACs já existentes ignorados)";
        }
        if ($invalidos > 0) {
            $mensagem .= " ({$invalidos} formatos inválidos descartados)";
        }

        return redirect()
            ->route('patrimonios.index')
            ->with($cadastrados > 0 ? 'success' : 'error', $mensagem);
    }

    /**
     * Remove um item do patrimônio caso não esteja em uso ativo.
     */
    public function destroy(Patrimonio $patrimonio): RedirectResponse
    {
        $user = Auth::user();
        if ($user && $user->cidade_id && $patrimonio->cidade_id && (int) $patrimonio->cidade_id !== (int) $user->cidade_id) {
            abort(403, 'Acesso restrito ao município da sua jurisdição.');
        }

        if ($patrimonio->estacao()->exists()) {
            return redirect()
                ->route('patrimonios.index')
                ->with('error', 'Não é possível excluir um patrimônio que está vinculado a uma estação ativa.');
        }

        $patrimonio->delete();

        return redirect()
            ->route('patrimonios.index')
            ->with('success', 'Patrimônio removido com sucesso!');
    }

    /**
     * Retorna lista de patrimônios com status 'Disponível' para preenchimento rápido em APIs.
     */
    public function apiDisponiveis(): JsonResponse
    {
        $user = Auth::user();
        $query = Patrimonio::where('status', 'Disponível');

        if ($user && $user->cidade_id) {
            $query->where('cidade_id', $user->cidade_id);
        }

        $disponiveis = $query
            ->orderBy('mac_address')
            ->get(['private_id', 'public_id', 'mac_address', 'numero_patrimonio']);

        return response()->json($disponiveis);
    }
}
