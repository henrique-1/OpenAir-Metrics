<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CompletarCadastroRequest extends FormRequest
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
        if ($this->has('email')) {
            $this->merge([
                'email' => trim((string) $this->input('email')),
            ]);
        }
        if ($this->has('estado')) {
            $this->merge([
                'estado' => strtoupper(trim((string) $this->input('estado'))),
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
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255', 'exists:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'cep' => ['nullable', 'string', 'max:9'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:2'],
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
            'token.required' => 'Link de ativação inválido. Solicite um novo link.',
            'email.required' => 'Informe o e-mail cadastrado.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'email.exists' => 'Não encontramos nenhuma conta com o e-mail informado.',
            'password.required' => 'Defina sua senha de acesso.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
            'password.max' => 'A senha não pode ter mais de :max caracteres.',
            'password.password.uncompromised' => 'Esta senha apareceu em um vazamento de dados conhecido. Por segurança, escolha outra.',
            'cep.max' => 'O CEP deve ter no máximo 9 caracteres.',
            'estado.max' => 'A UF deve ter 2 caracteres.',
        ];
    }
}
