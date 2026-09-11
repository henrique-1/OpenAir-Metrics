<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEstacaoRequest;
use App\Models\Bairro;
use App\Models\Estacao;
use App\Models\Estado;
use App\Models\Patrimonio;
use App\Services\GeocodingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EstacaoController extends Controller
{
    /**
     * Exibe a listagem de "Minhas Estações" do usuário autenticado / município.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        if ($user?->isSuperAdmin()) {
            abort(403, 'O super-usuário só pode gerenciar administradores.');
        }

        $query = Estacao::with(['bairro.cidade.estado', 'patrimonio', 'solicitanteSubstituicao']);

        if ($user && $user->cidade_id) {
            $query->whereHas('bairro', function ($cityQuery) use ($user) {
                $cityQuery->where('cidade_id', $user->cidade_id);
            });
        } elseif ($user) {
            $query->where('created_by', $user->id);
        }

        // Filtros
        $busca = trim((string) $request->input('busca'));
        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                $q->where('mac_address', 'like', "%{$busca}%")
                    ->orWhere('logradouro', 'like', "%{$busca}%")
                    ->orWhere('bairro_nome', 'like', "%{$busca}%")
                    ->orWhere('cidade_nome', 'like', "%{$busca}%")
                    ->orWhereHas('patrimonio', function ($pQ) use ($busca) {
                        $pQ->where('numero_patrimonio', 'like', "%{$busca}%");
                    })
                    ->orWhereHas('bairro', function ($bQ) use ($busca) {
                        $bQ->where('nome', 'like', "%{$busca}%");
                    });
            });
        }

        $tipo = $request->input('tipo');
        if ($tipo && in_array($tipo, ['Estação Matriz', 'Estação Satélite'])) {
            $query->where('tipo_estacao', $tipo);
        }

        $status = $request->input('status');
        if ($status === 'instalada') {
            $query->whereNotNull('data_instalacao');
        } elseif ($status === 'pendente') {
            $query->whereNull('data_instalacao');
        } elseif ($status === 'substituicao') {
            $query->where('solicitacao_substituicao', true);
        }

        // Ordenação
        $sort = $request->input('sort', 'created_at');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        switch ($sort) {
            case 'identificacao':
                $query->orderBy('mac_address', $direction);
                break;
            case 'tipo':
                $query->orderBy('tipo_estacao', $direction);
                break;
            case 'localidade':
                $query->orderBy('logradouro', $direction);
                break;
            case 'instalacao':
                $query->orderBy('data_instalacao', $direction);
                break;
            default:
                $query->orderBy('created_at', $direction);
                break;
        }

        $estacoes = $query->get();

        if ($sort === 'vida_util') {
            $estacoes = $estacoes->sortBy(function ($estacao) {
                $vida = $estacao->calcularVidaUtil();

                return $vida['porcentagem_restante'] ?? 0;
            }, SORT_REGULAR, $direction === 'desc')->values();
        }

        return view('estacoes.index', [
            'estacoes' => $estacoes,
            'user' => $user,
            'busca' => $busca,
            'tipo' => $tipo,
            'status' => $status,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Exibe o formulário de cadastro de nova estação.
     */
    public function create(): View
    {
        $user = Auth::user();
        if (! $user?->isPlanejadorTecnico() && ! $user?->isAdministrador()) {
            abort(403, 'Acesso restrito ao Planejador Técnico.');
        }

        if ($user && $user->cidade_id) {
            $user->load('cidade.estado');
        }

        $cidade = $user?->cidade;
        $estados = Estado::orderBy('nome')->get();

        $query = Estacao::withCoordinates()->with(['bairro.cidade.estado', 'estacaoOrigem']);
        if ($cidade) {
            $query->whereHas('bairro.cidade', fn($q) => $q->where('id', $cidade->id));
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

        return view('estacoes.create', [
            'estados' => $estados,
            'estacoesExistentes' => $estacoesExistentes,
            'cidade' => $cidade,
            'user' => $user,
        ]);
    }

    /**
     * Salva uma nova estação no banco de dados com resolução de endereço e substituição de bairro.
     */
    public function store(StoreEstacaoRequest $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user?->isPlanejadorTecnico() && ! $user?->isAdministrador()) {
            abort(403, 'Acesso restrito ao Planejador Técnico.');
        }

        $data = $request->validated();
        $data['created_by'] = $user?->id;

        // Validação de Jurisdição Municipal
        if ($user && $user->cidade_id) {
            $bairroSelecionado = Bairro::findOrFail($data['bairro_id']);
            if ($bairroSelecionado->cidade_id !== $user->cidade_id) {
                abort(403, 'Você só tem permissão para cadastrar estações dentro da sua jurisdição municipal.');
            }
        }

        // 1. Obtém os detalhes de endereço via OpenStreetMap Nominatim a partir das coordenadas
        $lat = (float) $data['latitude'];
        $lng = (float) $data['longitude'];
        $detalhes = GeocodingService::obterDetalhesEndereco($lat, $lng);

        if ($detalhes) {
            $data['logradouro'] = $detalhes['logradouro'];
            $data['numero'] = $detalhes['numero'];
            $data['bairro_nome'] = $detalhes['bairro'];
            $data['cidade_nome'] = $detalhes['cidade'];
            $data['estado_uf'] = $detalhes['estado_uf'];
            $data['cep'] = $detalhes['cep'];
            $data['endereco_completo'] = $detalhes['endereco_completo'];

            // 2. Substituição do Bairro: Associa a estação ao bairro real obtido da geolocalização reversa
            if (! empty($detalhes['bairro'])) {
                $bairroSelecionado = Bairro::find($data['bairro_id']);
                $cidadeId = ($user && $user->cidade_id) ? $user->cidade_id : $bairroSelecionado?->cidade_id;

                if ($cidadeId) {
                    $bairroObtido = Bairro::firstOrCreate([
                        'cidade_id' => $cidadeId,
                        'nome' => trim($detalhes['bairro']),
                    ]);

                    $data['bairro_id'] = $bairroObtido->id;
                }
            }
        }

        // 3. Resolução de topologia de rede para Satélites
        if ($data['tipo_estacao'] === 'Estação Satélite') {
            $origemId = $data['estacao_origem_id'] ?? null;
            $estacaoOrigem = $origemId ? Estacao::find($origemId) : null;

            if (! $estacaoOrigem) {
                // Encontra a estação mais próxima no banco para vincular
                $todasEstacoes = Estacao::withCoordinates()->get();
                $menorDist = null;
                $maisProxima = null;

                foreach ($todasEstacoes as $est) {
                    if ($est->latitude !== null && $est->longitude !== null) {
                        $dist = Estacao::calcularDistanciaHaversine($lat, $lng, (float) $est->latitude, (float) $est->longitude);
                        if ($dist <= 200.0 && ($menorDist === null || $dist < $menorDist)) {
                            $menorDist = $dist;
                            $maisProxima = $est;
                        }
                    }
                }

                $estacaoOrigem = $maisProxima;
            }

            if ($estacaoOrigem) {
                $data['estacao_origem_id'] = $estacaoOrigem->private_id;
                $data['matriz_pai_id'] = $estacaoOrigem->matriz_pai_id ?: $estacaoOrigem->private_id;
                if (! isset($data['distancia_origem_metros']) && $estacaoOrigem->latitude !== null && $estacaoOrigem->longitude !== null) {
                    $data['distancia_origem_metros'] = Estacao::calcularDistanciaHaversine($lat, $lng, (float) $estacaoOrigem->latitude, (float) $estacaoOrigem->longitude);
                }
            }
        }

        $estacao = Estacao::create($data);

        // Se for Matriz, define matriz_pai_id como sendo ela mesma
        if ($estacao->tipo_estacao === 'Estação Matriz' && empty($estacao->matriz_pai_id)) {
            $estacao->update(['matriz_pai_id' => $estacao->private_id]);
        }

        // Se o MAC Address pertencer a um item em estoque no patrimônio, vincula e atualiza seu status para Instalada
        if (! empty($estacao->mac_address)) {
            $patrimonio = Patrimonio::where('mac_address', $estacao->mac_address)->first();
            if ($patrimonio) {
                $estacao->update(['patrimonio_id' => $patrimonio->private_id]);
                $patrimonio->update(['status' => 'Instalada']);
            }
        }

        return redirect()
            ->route('estacoes.index')
            ->with('success', 'Estação cadastrada com sucesso!');
    }

    /**
     * Registra uma solicitação de substituição preventiva ou corretiva dos sensores da estação.
     */
    public function solicitarSubstituicao(Request $request, string $public_id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isAdministrador() && ! $user->isCadastrador() && ! $user->isPlanejador())) {
            abort(403, 'Apenas Administradores e Planejadores Técnicos podem solicitar a substituição de sensores.');
        }

        $estacao = Estacao::where('public_id', $public_id)->firstOrFail();

        // Jurisdição municipal: se o usuário possui cidade vinculada, só pode solicitar para estações de seu município
        if ($user->cidade_id && $estacao->bairro && $estacao->bairro->cidade_id !== $user->cidade_id) {
            abort(403, 'Você só pode solicitar substituição de sensores para estações dentro do seu município.');
        }

        // Apenas estações já instaladas podem ter substituição solicitada
        if (! $estacao->data_instalacao) {
            return redirect()
                ->route('estacoes.index')
                ->with('error', 'A substituição de sensores só pode ser solicitada para estações já instaladas em campo.');
        }

        $vida = $estacao->calcularVidaUtil();
        $vidaMaiorQueVinte = ($vida['porcentagem_restante'] ?? 0) > 20;

        $validated = $request->validate([
            'motivo_substituicao' => $vidaMaiorQueVinte
                ? ['required', 'string', 'min:3', 'max:1000']
                : ['nullable', 'string', 'max:1000'],
        ], [
            'motivo_substituicao.required' => 'O motivo da substituição é obrigatório quando a vida útil dos sensores for maior que 20%.',
        ]);

        $motivoFinal = $validated['motivo_substituicao'] ?? null;
        if (empty($motivoFinal)) {
            $motivoFinal = 'Fim da vida útil da estação';
        }

        $estacao->update([
            'solicitacao_substituicao' => true,
            'solicitacao_substituicao_em' => now(),
            'motivo_substituicao' => $motivoFinal,
            'solicitado_por' => $user->id,
        ]);

        $identificador = $estacao->patrimonio?->numero_patrimonio
            ? "Patrimônio #{$estacao->patrimonio->numero_patrimonio}"
            : ($estacao->mac_address ? "MAC {$estacao->mac_address}" : "Estação #{$estacao->private_id}");

        return redirect()
            ->route('estacoes.index')
            ->with('success', "Solicitação de substituição dos sensores registrada com sucesso para a {$identificador}!");
    }

    /**
     * Efetiva a substituição do sensor de uma estação:
     * O patrimônio anterior é marcado como 'Descartado', o novo patrimônio passa para 'Instalada',
     * a estação é atualizada e a pendência de substituição é finalizada.
     */
    public function substituirSensor(Request $request, string $public_id): RedirectResponse
    {
        $user = Auth::user();
        if (! $user || (! $user->isPlanejadorTecnico() && ! $user->isInstalador() && ! $user->isAdministrador())) {
            abort(403, 'Acesso não autorizado para substituir sensores.');
        }

        $estacao = Estacao::where('public_id', $public_id)->firstOrFail();

        if ($user->cidade_id && $estacao->bairro && $estacao->bairro->cidade_id !== $user->cidade_id) {
            abort(403, 'Você só pode substituir sensores de estações do seu município.');
        }

        $validated = $request->validate([
            'patrimonio_id' => ['required', 'exists:patrimonios,private_id'],
        ], [
            'patrimonio_id.required' => 'Selecione o novo equipamento de patrimônio.',
        ]);

        $novoPatrimonio = Patrimonio::findOrFail($validated['patrimonio_id']);

        if ($novoPatrimonio->status !== 'Disponível') {
            return redirect()
                ->route('estacoes.index')
                ->with('error', 'O equipamento selecionado não está com status Disponível.');
        }

        DB::beginTransaction();

        try {
            // Regra: assim que o sensor for substituído, o status do patrimônio deve ser alterado para descartado
            if ($estacao->patrimonio_id && (int) $estacao->patrimonio_id !== (int) $novoPatrimonio->private_id) {
                $antigoPatrimonio = Patrimonio::find($estacao->patrimonio_id);
                if ($antigoPatrimonio) {
                    $antigoPatrimonio->update(['status' => 'Descartado']);
                }
            }

            // Atualiza o novo patrimônio para 'Instalada'
            $novoPatrimonio->update(['status' => 'Instalada']);

            // Atualiza a estação com o novo patrimônio e finaliza a pendência de substituição
            $estacao->update([
                'patrimonio_id' => $novoPatrimonio->private_id,
                'mac_address' => $novoPatrimonio->mac_address ?: $estacao->mac_address,
                'status_instalacao' => 'Instalada',
                'data_instalacao' => now(),
                'solicitacao_substituicao' => false,
                'solicitacao_substituicao_em' => null,
                'motivo_substituicao' => null,
                'solicitado_por' => null,
            ]);

            DB::commit();

            return redirect()
                ->route('estacoes.index')
                ->with('success', "Sensor da estação substituído com sucesso! O patrimônio anterior foi marcado como Descartado e o novo equipamento #{$novoPatrimonio->numero_patrimonio} está ativo.");
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('estacoes.index')
                ->with('error', 'Erro ao substituir sensor: ' . $e->getMessage());
        }
    }
}
