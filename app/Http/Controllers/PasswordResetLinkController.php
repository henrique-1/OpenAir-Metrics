<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendPasswordResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Mensagem exibida independentemente de o e-mail existir, para não permitir enumeração de usuários.
     */
    public const MENSAGEM_LINK_ENVIADO = 'Se o e-mail informado estiver cadastrado e ativo, você receberá em instantes um link para redefinir sua senha.';

    /**
     * Exibe o formulário de solicitação do link de recuperação.
     *
     * O e-mail digitado na tela de login é recebido via query string para pré-preencher o campo.
     */
    public function create(Request $request): View
    {
        return view('auth.forgot-password', [
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /**
     * Envia o link de recuperação para usuários ativos.
     */
    public function store(SendPasswordResetLinkRequest $request): RedirectResponse
    {
        Password::sendResetLink([
            'email' => $request->validated('email'),
            'ativo' => true,
        ]);

        return back()
            ->withInput($request->only('email'))
            ->with('status', self::MENSAGEM_LINK_ENVIADO);
    }
}
