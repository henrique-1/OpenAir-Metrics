<?php

namespace App\Http\Controllers;

use App\Models\Estacao;
use App\Models\Medicao;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Exibe o mapa público com dados em tempo real dos sensores persistidos no banco.
     */
    public function index(): View
    {
        $driver = DB::connection()->getDriverName();
        $query = Estacao::query()
            ->whereHas('medicoes')
            ->with(['ultimaMedicao']);

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $query->select('estacoes.*')
                ->selectRaw('ST_AsText(coordenadas) as coordenadas_wkt')
                ->selectRaw('ST_X(coordenadas) as st_longitude')
                ->selectRaw('ST_Y(coordenadas) as st_latitude');
        }

        $estacoes = $query->get();

        $dadosIqa = [];
        $dadosTemperatura = [];
        $dadosUmidade = [];
        $dadosPm = [];
        $dadosCo2 = [];

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
            'centroMapa'
        ));
    }
}
