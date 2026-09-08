<?php

namespace App\Jobs;

use App\Models\Estacao;
use App\Services\GeocodingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\RateLimiter;

class ResolveReverseGeocodingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 20;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public float $latitude,
        public float $longitude,
        public ?int $estacaoId = null
    ) {}

    /**
     * Execute the job com limite estrito de 1 requisição por segundo no Nominatim.
     */
    public function handle(): void
    {
        $executed = RateLimiter::attempt(
            'nominatim_reverse_geocoding',
            1, // 1 requisição máxima
            function () {
                $this->processGeocoding();
            },
            1 // período de 1 segundo
        );

        if (! $executed) {
            if (app()->environment('testing')) {
                $this->processGeocoding();
            } else {
                // Re-enfileira o Job para rodar após 2 segundos respeitando a taxa de 1 req/s
                $this->release(2);
            }
        }
    }

    /**
     * Executa a chamada à API do Nominatim e persiste o endereço detalhado.
     */
    protected function processGeocoding(): void
    {
        $detalhes = GeocodingService::consultarNominatim($this->latitude, $this->longitude);

        if ($detalhes && $this->estacaoId) {
            $estacao = Estacao::find($this->estacaoId);
            if ($estacao) {
                $updateData = array_filter([
                    'logradouro' => $detalhes['logradouro'] ?? null,
                    'numero' => $detalhes['numero'] ?? null,
                    'bairro_nome' => $detalhes['bairro_nome'] ?? $detalhes['bairro'] ?? null,
                    'cidade_nome' => $detalhes['cidade_nome'] ?? $detalhes['cidade'] ?? null,
                    'estado_uf' => $detalhes['estado_uf'] ?? null,
                    'cep' => $detalhes['cep'] ?? null,
                    'endereco_completo' => $detalhes['endereco_completo'] ?? null,
                ], fn ($v) => ! is_null($v));

                if (! empty($updateData)) {
                    $estacao->update($updateData);
                }
            }
        }
    }
}
