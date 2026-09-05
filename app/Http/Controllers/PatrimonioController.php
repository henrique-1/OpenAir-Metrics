<?php

namespace App\Http\Controllers;

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
        $statusFiltro = $request->input('status');
        $busca = trim((string) $request->input('busca'));

        $query = Patrimonio::with(['estacao', 'criador'])->latest('created_at');

        if ($statusFiltro && in_array($statusFiltro, ['Disponível', 'Alocado', 'Instalado', 'Manutenção', 'Descartado'])) {
            $query->where('status', $statusFiltro);
        }

        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                $q->where('mac_address', 'like', "%{$busca}%")
                    ->orWhere('numero_patrimonio', 'like', "%{$busca}%")
                    ->orWhere('observacoes', 'like', "%{$busca}%");
            });
        }

        $patrimonios = $query->paginate(15)->withQueryString();

        // Contadores gerais para os cards de estatísticas
        $total = Patrimonio::count();
        $disponiveis = Patrimonio::where('status', 'Disponível')->count();
        $instalados = Patrimonio::where('status', 'Instalado')->count();
        $manutencao = Patrimonio::where('status', 'Manutenção')->count();

        return view('patrimonios.index', [
            'patrimonios' => $patrimonios,
            'total' => $total,
            'disponiveis' => $disponiveis,
            'instalados' => $instalados,
            'manutencao' => $manutencao,
            'statusFiltro' => $statusFiltro,
            'busca' => $busca,
        ]);
    }

    /**
     * Exibe o formulário de cadastro de patrimônio.
     */
    public function create(): View
    {
        return view('patrimonios.create');
    }

    /**
     * Salva um único item de patrimônio.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mac_address' => [
                'required',
                'string',
                'regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
                'unique:patrimonios,mac_address',
                'unique:estacoes,mac_address',
            ],
            'numero_patrimonio' => ['nullable', 'string', 'max:50'],
            'tipo_sugerido' => ['required', 'in:Indefinido,Estação Matriz,Estação Satélite'],
            'status' => ['required', 'in:Disponível,Alocado,Instalado,Manutenção,Descartado'],
            'data_aquisicao' => ['nullable', 'date'],
            'observacoes' => ['nullable', 'string', 'max:1000'],
        ], [
            'mac_address.required' => 'O endereço MAC é obrigatório.',
            'mac_address.regex' => 'O endereço MAC deve estar no formato AA:BB:CC:DD:EE:FF.',
            'mac_address.unique' => 'Este endereço MAC já está cadastrado no sistema.',
            'tipo_sugerido.required' => 'Selecione o tipo sugerido do equipamento.',
            'status.required' => 'Selecione o status inicial do patrimônio.',
        ]);

        $validated['mac_address'] = strtoupper(trim($validated['mac_address']));
        $validated['created_by'] = Auth::id();

        Patrimonio::create($validated);

        return redirect()
            ->route('patrimonios.index')
            ->with('success', 'Item de patrimônio cadastrado com sucesso!');
    }

    /**
     * Processa o cadastro em lote de múltiplos MAC Addresses.
     */
    public function storeBatch(Request $request): RedirectResponse
    {
        $request->validate([
            'mac_addresses_batch' => ['required', 'string'],
            'tipo_sugerido' => ['required', 'in:Indefinido,Estação Matriz,Estação Satélite'],
            'data_aquisicao' => ['nullable', 'date'],
        ], [
            'mac_addresses_batch.required' => 'Informe a lista de endereços MAC.',
        ]);

        $linhas = explode("\n", $request->input('mac_addresses_batch'));
        $tipo = $request->input('tipo_sugerido', 'Indefinido');
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

            Patrimonio::create([
                'mac_address' => $macLimpo,
                'tipo_sugerido' => $tipo,
                'status' => 'Disponível',
                'data_aquisicao' => $dataAquisicao,
                'created_by' => $userId,
            ]);

            $cadastrados++;
        }

        $mensagem = "{$cadastrados} placa(s) cadastrada(s) com sucesso no patrimônio.";
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
        $disponiveis = Patrimonio::where('status', 'Disponível')
            ->orderBy('mac_address')
            ->get(['private_id', 'public_id', 'mac_address', 'numero_patrimonio', 'tipo_sugerido']);

        return response()->json($disponiveis);
    }
}
