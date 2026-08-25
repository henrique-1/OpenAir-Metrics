<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Exibe a tela de login.
     */
    public function create(): View
    {
        // Retorna a view que criamos em resources/views/auth/login.blade.php
        return view('auth.login');
    }

    /**
     * Processa a tentativa de login.
     */
    public function store(Request $request)
    {
        // 1. Validação básica dos dados recebidos
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Lembre-me checkbox
        $remember = $request->boolean('remember');

        // 2. Tentativa de autenticação
        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            // Redireciona para o dashboard ou para a página que o usuário tentou acessar antes
            return redirect()->intended('dashboard');
        }

        // 3. Em caso de falha, retorna para a tela de login com erro
        return back()->withErrors([
            'email' => 'As credenciais informadas não correspondem aos nossos registros.',
        ])->onlyInput('email');
    }
}
