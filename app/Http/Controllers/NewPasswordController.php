<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Exibe o formulário de definição da nova senha.
     */
    public function create(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /**
     * Valida o token e grava a nova senha.
     */
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            [
                ...$request->only('email', 'password', 'password_confirmation', 'token'),
                'ativo' => true,
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $this->encerrarSessoesDoUsuario($user);

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Senha redefinida com sucesso! Faça login com a nova senha.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Este link de recuperação é inválido ou expirou. Solicite um novo.']);
    }

    /**
     * Encerra as sessões abertas do usuário em outros dispositivos (somente com driver de sessão em banco).
     */
    private function encerrarSessoesDoUsuario(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $user->getAuthIdentifier())
            ->delete();
    }
}
