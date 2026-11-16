<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Garante que usuários com conta desativada (ativo = false) tenham a sessão encerrada imediatamente.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->ativo) {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sua conta foi desativada.',
                ], 403);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Sua conta foi desativada pelo administrador.',
            ]);
        }

        return $next($request);
    }
}
