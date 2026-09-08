<?php

namespace App\Http\Requests;

use App\Models\Bairro;
use App\Models\Estacao;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreEstacaoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepara os dados para validação.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('mac_address')) {
            $this->merge([
                'mac_address' => strtoupper(trim((string) $this->input('mac_address'))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mac_address' => [
                'required',
                'string',
                'regex:/^([0-9A-F]{2}[:-]){5}([0-9A-F]{2})$/',
                'unique:estacoes,mac_address',
            ],
            'tipo_estacao' => [
                'required',
                'in:Estação Matriz,Estação Satélite',
            ],
            'estacao_origem_id' => [
                'nullable',
                'integer',
                'exists:estacoes,private_id',
            ],
            'bairro_id' => [
                'required',
                'integer',
                'exists:bairros,id',
            ],
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
        ];
    }

    /**
     * Validações adicionais pós-regras básicas.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $user = $this->user();
                $bairroId = (int) $this->input('bairro_id');

                // Validação de Jurisdição Municipal
                if ($user && $user->cidade_id) {
                    $bairro = Bairro::find($bairroId);
                    if (! $bairro || $bairro->cidade_id !== $user->cidade_id) {
                        $validator->errors()->add(
                            'bairro_id',
                            'Você só tem permissão para cadastrar estações na cidade de '.($user->cidade?->nome ?? 'sua jurisdição municipal').'.'
                        );

                        return;
                    }
                }

                $tipo = $this->input('tipo_estacao');
                $lat = (float) $this->input('latitude');
                $lng = (float) $this->input('longitude');
                $origemId = $this->input('estacao_origem_id');

                if ($tipo === 'Estação Satélite') {
                    if ($origemId) {
                        $origem = Estacao::find($origemId);
                        if ($origem && $origem->latitude !== null && $origem->longitude !== null) {
                            $dist = Estacao::calcularDistanciaHaversine($lat, $lng, (float) $origem->latitude, (float) $origem->longitude);
                            if ($dist > 200.0) {
                                $validator->errors()->add(
                                    'latitude',
                                    'A Estação Satélite deve estar a no máximo 200 metros da estação de origem selecionada. Distância atual: '.round($dist, 1).' metros.'
                                );

                                return;
                            }
                        }
                    }

                    $menorDistancia = Estacao::menorDistanciaAte($lat, $lng);

                    if ($menorDistancia === null) {
                        $validator->errors()->add(
                            'tipo_estacao',
                            'Não é possível cadastrar uma Estação Satélite sem antes ter ao menos uma estação cadastrada no sistema.'
                        );
                    } elseif ($menorDistancia > 200.0) {
                        $validator->errors()->add(
                            'latitude',
                            'Estações do tipo Satélite devem estar localizadas a no máximo 200 metros de uma estação já cadastrada. Distância atual: '.round($menorDistancia, 1).' metros.'
                        );
                    }
                }
            },
        ];
    }

    /**
     * Mensagens de validação customizadas em português.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mac_address.required' => 'O endereço MAC da placa é obrigatório.',
            'mac_address.regex' => 'O endereço MAC deve estar no formato válido (ex: AA:BB:CC:DD:EE:FF ou AA-BB-CC-DD-EE-FF).',
            'mac_address.unique' => 'Este endereço MAC já está cadastrado em outra estação.',
            'tipo_estacao.required' => 'O tipo de estação é obrigatório.',
            'tipo_estacao.in' => 'O tipo de estação selecionado é inválido.',
            'bairro_id.required' => 'A seleção do bairro é obrigatória.',
            'bairro_id.exists' => 'O bairro selecionado não existe.',
            'latitude.required' => 'Por favor, selecione a localização da estação no mapa.',
            'latitude.numeric' => 'A latitude informada deve ser um número válido.',
            'latitude.between' => 'A latitude deve estar entre -90 e 90 graus.',
            'longitude.required' => 'Por favor, selecione a localização da estação no mapa.',
            'longitude.numeric' => 'A longitude informada deve ser um número válido.',
            'longitude.between' => 'A longitude deve estar entre -180 e 180 graus.',
        ];
    }
}
