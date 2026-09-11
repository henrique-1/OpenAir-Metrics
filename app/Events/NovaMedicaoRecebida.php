<?php

namespace App\Events;

use App\Models\Estacao;
use App\Models\Medicao;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NovaMedicaoRecebida implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Cria uma nova instância do evento.
     */
    public function __construct(public Medicao $medicao) {}

    /**
     * O canal no qual o evento deve ser transmitido.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('medicoes'),
        ];
    }

    /**
     * O nome do evento a ser transmitido para o cliente.
     */
    public function broadcastAs(): string
    {
        return 'NovaMedicaoRecebida';
    }

    /**
     * Os dados que devem ser transmitidos com o evento.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $estacao = $this->medicao->estacao;
        if ($estacao && ($estacao->latitude === null || $estacao->longitude === null)) {
            $estacao = Estacao::withCoordinates()->find($estacao->getKey()) ?? $estacao;
        }

        $lat = $estacao?->latitude;
        $lng = $estacao?->longitude;

        $dataHoraObj = $this->medicao->data_hora ?? $this->medicao->created_at;
        $dataHoraFormatada = $dataHoraObj ? Carbon::parse($dataHoraObj)->format('d/m/Y H:i') : now()->format('d/m/Y H:i');

        $bairroId = $estacao?->bairro_id;
        $cidadeId = $estacao?->bairro?->cidade_id;

        return [
            'estacao_id' => $estacao?->public_id,
            'patrimonio' => $estacao?->patrimonio?->numero_patrimonio,
            'bairro_id' => $bairroId,
            'cidade_id' => $cidadeId,
            'lat' => $lat !== null ? (float) $lat : null,
            'lng' => $lng !== null ? (float) $lng : null,
            'iqa' => (int) $this->medicao->iqa,
            'temperatura' => (float) $this->medicao->temperatura,
            'umidade' => (float) $this->medicao->umidade,
            'poeira' => (float) $this->medicao->poeira,
            'co2' => (int) $this->medicao->co2,
            'data_hora' => $dataHoraFormatada,
        ];
    }
}
