<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEstacaoRequest;
use App\Models\Bairro;
use App\Models\Estacao;
use App\Models\Estado;
use App\Services\GeocodingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EstacaoController extends Controller
{
    /**
     * Exibe a listagem de "Minhas Estações" do usuário autenticado.
     */
    public function index(): View
    {
        $estacoes = Estacao::with(['bairro.cidade.estado'])
            ->where('created_by', Auth::id())
            ->latest('created_at')
            ->get();

        return view('estacoes.index', [
            'estacoes' => $estacoes,
        ]);
    }

    /**
     * Exibe o formulário de cadastro de nova estação.
     */
    public function create(): View
    {
        $estados = Estado::orderBy('nome')->get();

        return view('estacoes.create', [
            'estados' => $estados,
        ]);
    }

    /**
     * Salva uma nova estação no banco de dados com resolução de endereço e substituição de bairro.
     */
    public function store(StoreEstacaoRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

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
                $cidadeId = $bairroSelecionado?->cidade_id;

                if ($cidadeId) {
                    $bairroObtido = Bairro::firstOrCreate([
                        'cidade_id' => $cidadeId,
                        'nome' => trim($detalhes['bairro']),
                    ]);

                    $data['bairro_id'] = $bairroObtido->id;
                }
            }
        }

        Estacao::create($data);

        return redirect()
            ->route('estacoes.index')
            ->with('success', 'Estação cadastrada com sucesso!');
    }
}
