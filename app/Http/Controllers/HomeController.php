<?php

namespace App\Http\Controllers;

use App\Models\Estacao;
use App\Models\Medicao;
use App\Services\AlertaAmbientalService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __construct(
        protected AlertaAmbientalService $alertaService
    ) {}

    /**
     * Exibe o mapa público com dados em tempo real dos sensores persistidos no banco.
     */
    public function index(): View
    {
        $estacoes = Estacao::query()
            ->withCoordinates()
            ->whereHas('medicoes')
            ->with(['ultimaMedicao', 'bairro.cidade.estado'])
            ->get();

        $dadosIqa = [];
        $dadosTemperatura = [];
        $dadosUmidade = [];
        $dadosPm = [];
        $dadosCo2 = [];
        $estacoesAlerta = [];

        $sumLat = 0.0;
        $sumLng = 0.0;
        $validPointsCount = 0;

        foreach ($estacoes as $estacao) {
            $medicao = $estacao->ultimaMedicao;
            $lat = $estacao->latitude;
            $lng = $estacao->longitude;

            if ($medicao && $lat !== null && $lng !== null) {
                $latFloat = (float) $lat;
                $lngFloat = (float) $lng;

                $sumLat += $latFloat;
                $sumLng += $lngFloat;
                $validPointsCount++;

                $iqaValor = $medicao->iqa ?? Medicao::calcularIqa((float) $medicao->poeira, (int) $medicao->co2);

                $dataHoraObj = $medicao->data_hora ?? $medicao->created_at;
                $dataHoraFormatada = $dataHoraObj ? Carbon::parse($dataHoraObj)->format('d/m/Y H:i') : null;

                // Avaliação dos alertas ambientais para a estação
                $resAlerta = $this->alertaService->avaliarMedicao($medicao);
                if ($resAlerta['tem_alerta']) {
                    $estacoesAlerta[] = [
                        'public_id' => $estacao->public_id,
                        'nome' => $estacao->logradouro ? "{$estacao->logradouro}, {$estacao->numero}" : "Estação {$estacao->mac_address}",
                        'bairro' => $estacao->bairro?->nome ?? $estacao->bairro_nome,
                        'cidade' => $estacao->bairro?->cidade?->nome ?? $estacao->cidade_nome,
                        'lat' => $latFloat,
                        'lng' => $lngFloat,
                        'alerta_maximo' => [
                            'nivel' => $resAlerta['alerta_maximo']['nivel']->value,
                            'peso' => $resAlerta['alerta_maximo']['nivel']->peso(),
                            'status' => $resAlerta['alerta_maximo']['status'],
                            'classe' => $resAlerta['alerta_maximo']['classe'],
                            'descricao' => $resAlerta['alerta_maximo']['descricao'],
                            'cor' => $resAlerta['alerta_maximo']['cor'],
                            'badge_class' => $resAlerta['alerta_maximo']['badge_class'],
                            'parametro' => $resAlerta['alerta_maximo']['parametro'] ?? 'Ambiente',
                            'valor' => $resAlerta['alerta_maximo']['valor'] ?? null,
                            'unidade' => $resAlerta['alerta_maximo']['unidade'] ?? '',
                        ],
                        'alertas_ativos' => array_values($resAlerta['alertas_ativos']),
                    ];
                }

                $dadosIqa[] = [
                    'estacao_id' => $estacao->public_id,
                    'lat' => $latFloat,
                    'lng' => $lngFloat,
                    'value' => (int) $iqaValor,
                    'data_hora' => $dataHoraFormatada,
                ];

                $dadosTemperatura[] = [
                    'estacao_id' => $estacao->public_id,
                    'lat' => $latFloat,
                    'lng' => $lngFloat,
                    'value' => (float) $medicao->temperatura,
                    'data_hora' => $dataHoraFormatada,
                ];

                $dadosUmidade[] = [
                    'estacao_id' => $estacao->public_id,
                    'lat' => $latFloat,
                    'lng' => $lngFloat,
                    'value' => (float) $medicao->umidade,
                    'data_hora' => $dataHoraFormatada,
                ];

                $dadosPm[] = [
                    'estacao_id' => $estacao->public_id,
                    'lat' => $latFloat,
                    'lng' => $lngFloat,
                    'value' => (float) $medicao->poeira,
                    'data_hora' => $dataHoraFormatada,
                ];

                $dadosCo2[] = [
                    'estacao_id' => $estacao->public_id,
                    'lat' => $latFloat,
                    'lng' => $lngFloat,
                    'value' => (int) $medicao->co2,
                    'data_hora' => $dataHoraFormatada,
                ];
            }
        }

        $centroMapa = [
            'lat' => $validPointsCount > 0 ? round($sumLat / $validPointsCount, 6) : -21.967194,
            'lng' => $validPointsCount > 0 ? round($sumLng / $validPointsCount, 6) : -46.812740,
            'zoom' => $validPointsCount > 0 ? 14 : 13,
        ];

        return view('home', compact(
            'dadosIqa',
            'dadosTemperatura',
            'dadosUmidade',
            'dadosPm',
            'dadosCo2',
            'centroMapa',
            'estacoesAlerta'
        ));
    }
}
