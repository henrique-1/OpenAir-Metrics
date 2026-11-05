<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanManagePasskeys
{
    /**
     * Garante que apenas Administradores Municipais e Instaladores acessem rotas de gerenciamento de Passkeys.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('user/passkeys*')) {
            $user = $request->user();

            if ($user && ! $user->podeUsarPasskey()) {
                abort(403, 'Acesso restrito a Administradores Municipais e Instaladores.');
            }
        }

        return $next($request);
    }
}
