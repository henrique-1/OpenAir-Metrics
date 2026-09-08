<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estacao;
use App\Models\Medicao;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicaoApiController extends Controller
{
    /**
     * Recebe telemetria e dados de sensores enviados por uma estação em campo.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mac_address' => ['required_without:estacao_id', 'nullable', 'string', 'max:50'],
            'estacao_id' => ['required_without:mac_address', 'nullable', 'string', 'max:50'],
            'temperatura' => ['required', 'numeric', 'between:-50,100'],
            'umidade' => ['required', 'numeric', 'between:0,100'],
            'co2' => ['required', 'integer', 'min:0', 'max:50000'],
            'poeira' => ['required', 'numeric', 'min:0', 'max:5000'],
            'data_hora' => ['nullable', 'date'],
        ]);

        $query = Estacao::query();

        if (! empty($validated['mac_address'])) {
            $mac = strtoupper(trim((string) $validated['mac_address']));
            // Normaliza caso seja enviado sem dois pontos (ex: AABBCCDDEEFF -> AA:BB:CC:DD:EE:FF)
            if (strlen($mac) === 12 && ! str_contains($mac, ':')) {
                $mac = implode(':', str_split($mac, 2));
            }
            $query->where('mac_address', $mac);
        } elseif (! empty($validated['estacao_id'])) {
            $estacaoId = trim((string) $validated['estacao_id']);
            $query->where('public_id', $estacaoId);
        }

        $estacao = $query->first();

        if (! $estacao) {
            return response()->json([
                'success' => false,
                'message' => 'Estação não encontrada com o identificador ou MAC Address informado.',
            ], 404);
        }

        if ($estacao->status_instalacao !== 'Instalada') {
            return response()->json([
                'success' => false,
                'message' => 'A estação encontrada ainda não foi ativada em campo (status atual: '.$estacao->status_instalacao.').',
            ], 422);
        }

        $dataHora = ! empty($validated['data_hora'])
            ? Carbon::parse($validated['data_hora'])
            : now();

        $medicao = Medicao::create([
            'estacao_id' => $estacao->private_id,
            'temperatura' => (float) $validated['temperatura'],
            'umidade' => (float) $validated['umidade'],
            'co2' => (int) $validated['co2'],
            'poeira' => (float) $validated['poeira'],
            'data_hora' => $dataHora,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Medição registrada com sucesso.',
            'data' => [
                'id' => $medicao->public_id,
                'estacao_id' => $estacao->public_id,
                'mac_address' => $estacao->mac_address,
                'temperatura' => (float) $medicao->temperatura,
                'umidade' => (float) $medicao->umidade,
                'co2' => (int) $medicao->co2,
                'poeira' => (float) $medicao->poeira,
                'iqa' => $medicao->iqa,
                'data_hora' => $medicao->data_hora->toIso8601String(),
            ],
        ], 201);
    }
}
