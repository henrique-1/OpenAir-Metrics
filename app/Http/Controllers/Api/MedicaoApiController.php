<?php

namespace App\Http\Controllers\Api;

use App\Events\NovaMedicaoRecebida;
use App\Http\Controllers\Controller;
use App\Models\Estacao;
use App\Models\Medicao;
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
            'patrimonio' => ['required_without:estacao_id', 'nullable', 'string', 'max:50'],
            'estacao_id' => ['required_without:patrimonio', 'nullable', 'string', 'max:50'],
            'temperatura' => ['required', 'numeric', 'between:-50,100'],
            'umidade' => ['required', 'numeric', 'between:0,100'],
            'co2' => ['required', 'integer', 'min:0', 'max:50000'],
            'poeira' => ['required', 'numeric', 'min:0', 'max:5000'],
        ]);

        $query = Estacao::query()->withCoordinates()->with('patrimonio');

        if (! empty($validated['patrimonio'])) {
            $patrimonioValor = trim((string) $validated['patrimonio']);
            $query->whereHas('patrimonio', function ($q) use ($patrimonioValor) {
                $q->where('numero_patrimonio', $patrimonioValor)
                    ->orWhere('public_id', $patrimonioValor);
            });
        } elseif (! empty($validated['estacao_id'])) {
            $estacaoId = trim((string) $validated['estacao_id']);
            $query->where('public_id', $estacaoId);
        }

        $estacao = $query->first();

        if (! $estacao) {
            return response()->json([
                'success' => false,
                'message' => 'Estação não encontrada com o identificador ou patrimônio informado.',
            ], 404);
        }

        // Validação de autenticidade da telemetria (Chave de API / Segredo do Dispositivo)
        $sensorKey = $request->header('X-Sensor-Key');
        $expectedKey = config('services.telemetry.key') ?: env('TELEMETRY_API_KEY');
        if ($expectedKey && (! $sensorKey || ! hash_equals((string) $expectedKey, (string) $sensorKey))) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo não autorizado. Chave de sensor inválida ou ausente.',
            ], 401);
        }

        if ($estacao->status_instalacao !== 'Instalada') {
            return response()->json([
                'success' => false,
                'message' => 'A estação encontrada ainda não foi ativada em campo (status atual: '.$estacao->status_instalacao.').',
            ], 422);
        }

        $iqa = Medicao::calcularIqa((float) $validated['poeira'], (int) $validated['co2']);

        $medicao = Medicao::create([
            'estacao_id' => $estacao->private_id,
            'temperatura' => (float) $validated['temperatura'],
            'umidade' => (float) $validated['umidade'],
            'co2' => (int) $validated['co2'],
            'poeira' => (float) $validated['poeira'],
            'iqa' => $iqa,
            'data_hora' => now(),
        ]);

        $medicao->setRelation('estacao', $estacao);

        try {
            NovaMedicaoRecebida::dispatch($medicao);
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Medição registrada com sucesso.',
            'data' => [
                'id' => $medicao->public_id,
                'estacao_id' => $estacao->public_id,
                'patrimonio' => $estacao->patrimonio?->numero_patrimonio,
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
