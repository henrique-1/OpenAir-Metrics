<?php

namespace App\Jobs;

use App\Models\Estacao;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $roundLat = round($this->latitude, 6);
        $roundLng = round($this->longitude, 6);
        $cacheKey = "geocoding_details_{$roundLat}_{$roundLng}";

        try {
            $url = 'https://nominatim.openstreetmap.org/reverse';
            $response = Http::timeout(5)
                ->withUserAgent('OpenAir_Metrics/1.0 (contato.henrique.bissoli@gmail.com)')
                ->get($url, [
                    'format' => 'json',
                    'lat' => $roundLat,
                    'lon' => $roundLng,
                    'zoom' => 18,
                    'addressdetails' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $address = $data['address'] ?? [];

                $logradouro = $address['road']
                    ?? $address['pedestrian']
                    ?? $address['street']
                    ?? $address['footway']
                    ?? $address['avenue']
                    ?? $address['path']
                    ?? null;

                $numero = $address['house_number'] ?? null;

                $bairro = $address['suburb']
                    ?? $address['neighbourhood']
                    ?? $address['city_district']
                    ?? $address['quarter']
                    ?? $address['residential']
                    ?? null;

                $cidade = $address['city']
                    ?? $address['town']
                    ?? $address['municipality']
                    ?? $address['village']
                    ?? $address['county']
                    ?? null;

                $estadoUf = null;
                if (! empty($address['ISO3166-2-lvl4'])) {
                    $estadoUf = str_replace('BR-', '', (string) $address['ISO3166-2-lvl4']);
                } elseif (! empty($address['state_code'])) {
                    $estadoUf = $address['state_code'];
                } elseif (! empty($address['state'])) {
                    $estadoUf = $address['state'];
                }

                $cep = $address['postcode'] ?? null;
                $enderecoCompleto = $data['display_name'] ?? null;
                $enderecoFormatado = $logradouro
                    ? ($numero ? "{$logradouro}, {$numero}" : $logradouro)
                    : ($data['name'] ?? ($enderecoCompleto ? explode(',', $enderecoCompleto)[0] : null));

                $detalhes = [
                    'logradouro' => $logradouro,
                    'numero' => $numero,
                    'bairro' => $bairro,
                    'bairro_nome' => $bairro,
                    'cidade' => $cidade,
                    'cidade_nome' => $cidade,
                    'estado_uf' => $estadoUf,
                    'cep' => $cep,
                    'endereco_completo' => $enderecoCompleto,
                    'endereco_formatado' => $enderecoFormatado,
                ];

                Cache::put($cacheKey, $detalhes, now()->addDays(30));

                if ($this->estacaoId) {
                    $estacao = Estacao::find($this->estacaoId);
                    if ($estacao) {
                        $updateData = array_filter([
                            'logradouro' => $logradouro,
                            'numero' => $numero,
                            'bairro_nome' => $bairro,
                            'cidade_nome' => $cidade,
                            'estado_uf' => $estadoUf,
                            'cep' => $cep,
                            'endereco_completo' => $enderecoCompleto ?: ($logradouro ? "{$logradouro} - {$bairro}, {$cidade}" : null),
                        ], fn ($v) => ! is_null($v));

                        if (! empty($updateData)) {
                            $estacao->update($updateData);
                        }
                    }
                }
            } else {
                Log::warning("Falha na resposta do Nominatim para ({$roundLat}, {$roundLng}): ".$response->status());
            }
        } catch (\Throwable $e) {
            Log::warning("Exceção ao resolver geocodificação reversa para ({$roundLat}, {$roundLng}): ".$e->getMessage());
        }
    }
}
