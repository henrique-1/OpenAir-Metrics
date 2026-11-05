<?php

namespace App\Http\Requests;

use App\Providers\AppServiceProvider;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * A política de senha segue a NIST SP 800-63B Rev. 4 e é definida em
     * {@see AppServiceProvider::boot()} via Password::defaults().
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
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
            'token.required' => 'Link de recuperação inválido. Solicite um novo.',
            'email.required' => 'Informe o e-mail cadastrado.',
            'email.email' => 'Informe um endereço de e-mail válido.',
            'password.required' => 'Informe a nova senha.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'password.min' => 'A senha deve ter pelo menos :min caracteres.',
            'password.max' => 'A senha não pode ter mais de :max caracteres.',
            'password.password.uncompromised' => 'Esta senha apareceu em um vazamento de dados conhecido. Por segurança, escolha outra.',
        ];
    }
}
