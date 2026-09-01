<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Busca os detalhes estruturados de endereço para uma coordenada geográfica via Nominatim.
     *
     * @param  float  $lat  Latitude
     * @param  float  $lng  Longitude
     * @return array{
     *     logradouro: ?string,
     *     numero: ?string,
     *     bairro: ?string,
     *     cidade: ?string,
     *     estado_uf: ?string,
     *     cep: ?string,
     *     endereco_completo: ?string,
     *     endereco_formatado: ?string
     * }|null
     */
    public static function obterDetalhesEndereco(float $lat, float $lng): ?array
    {
        $roundLat = round($lat, 6);
        $roundLng = round($lng, 6);
        $cacheKey = "geocoding_details_{$roundLat}_{$roundLng}";

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($roundLat, $roundLng) {
            try {
                $url = 'https://nominatim.openstreetmap.org/reverse';
                $response = Http::timeout(8)
                    ->withUserAgent('OpenAir_Metrics/1.0')
                    ->get($url, [
                        'format' => 'json',
                        'lat' => $roundLat,
                        'lon' => $roundLng,
                        'zoom' => 18, // Aproximação nível rua/prédio
                        'addressdetails' => 1,
                    ]);

                if ($response->failed()) {
                    Log::warning("Falha na API Nominatim Reverse Geocoding para ({$roundLat}, {$roundLng}): ".$response->status());

                    return null;
                }

                $data = $response->json();
                $address = $data['address'] ?? [];

                // 1. Logradouro (rua, avenida, etc.)
                $logradouro = $address['road']
                    ?? $address['pedestrian']
                    ?? $address['street']
                    ?? $address['footway']
                    ?? $address['avenue']
                    ?? $address['path']
                    ?? null;

                // 2. Número
                $numero = $address['house_number'] ?? null;

                // 3. Bairro
                $bairro = $address['suburb']
                    ?? $address['neighbourhood']
                    ?? $address['city_district']
                    ?? $address['quarter']
                    ?? $address['residential']
                    ?? null;

                // 4. Cidade
                $cidade = $address['city']
                    ?? $address['town']
                    ?? $address['municipality']
                    ?? $address['village']
                    ?? $address['county']
                    ?? null;

                // 5. Estado / UF
                $estadoUf = null;
                if (! empty($address['ISO3166-2-lvl4'])) {
                    $estadoUf = str_replace('BR-', '', (string) $address['ISO3166-2-lvl4']);
                } elseif (! empty($address['state_code'])) {
                    $estadoUf = $address['state_code'];
                } elseif (! empty($address['state'])) {
                    $estadoUf = $address['state'];
                }

                // 6. CEP
                $cep = $address['postcode'] ?? null;

                // 7. Endereço Completo (display_name)
                $enderecoCompleto = $data['display_name'] ?? null;

                // 8. Endereço Formatado (Rua + Número)
                $enderecoFormatado = $logradouro
                    ? ($numero ? "{$logradouro}, {$numero}" : $logradouro)
                    : ($data['name'] ?? ($enderecoCompleto ? explode(',', $enderecoCompleto)[0] : null));

                return [
                    'logradouro' => $logradouro,
                    'numero' => $numero,
                    'bairro' => $bairro,
                    'cidade' => $cidade,
                    'estado_uf' => $estadoUf,
                    'cep' => $cep,
                    'endereco_completo' => $enderecoCompleto,
                    'endereco_formatado' => $enderecoFormatado,
                ];
            } catch (\Throwable $e) {
                Log::warning("Erro ao executar busca de endereço reverso para ({$roundLat}, {$roundLng}): ".$e->getMessage());

                return null;
            }
        });
    }

    /**
     * Helper simplificado para retornar apenas a string do endereço formatado.
     */
    public static function buscarEndereco(float $lat, float $lng): ?string
    {
        $detalhes = self::obterDetalhesEndereco($lat, $lng);

        return $detalhes['endereco_formatado'] ?? null;
    }
}
