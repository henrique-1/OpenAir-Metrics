<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompletarCadastroRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CompletarCadastroController extends Controller
{
    /**
     * Exibe o formulário para o novo usuário definir sua senha e endereço.
     */
    public function create(Request $request, string $token): View
    {
        return view('auth.complete-registration', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /**
     * Processa a definição de senha e o preenchimento opcional do endereço.
     */
    public function store(CompletarCadastroRequest $request): RedirectResponse
    {
        $status = Password::reset(
            [
                'token' => $request->validated('token'),
                'email' => $request->validated('email'),
                'password' => $request->validated('password'),
                'password_confirmation' => $request->validated('password_confirmation'),
                'ativo' => true,
            ],
            function (User $user, string $password) use ($request): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'email_verified_at' => now(),
                    'ativo' => true,
                    'cep' => $request->validated('cep'),
                    'logradouro' => $request->validated('logradouro'),
                    'numero' => $request->validated('numero'),
                    'complemento' => $request->validated('complemento'),
                    'bairro' => $request->validated('bairro'),
                    'estado' => $request->validated('estado'),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Cadastro ativado com sucesso! Faça login com a sua nova senha.');
        }

        return back()
            ->withInput($request->only('email', 'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'estado'))
            ->withErrors(['email' => 'Este link de ativação é inválido ou expirou. Você pode solicitar um novo link na tela de recuperação de senha.']);
    }
}
