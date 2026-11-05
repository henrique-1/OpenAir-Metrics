<?php

namespace App\Actions;

use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction as SpatieFindPasskeyAction;
use Spatie\LaravelPasskeys\Models\Passkey;

class FindPasskeyToAuthenticateAction extends SpatieFindPasskeyAction
{
    /**
     * Localiza e valida a Passkey para autenticação, garantindo que o usuário
     * esteja ativo e possua permissão (Administrador Municipal ou Instalador).
     */
    public function execute(string $publicKeyCredentialJson, string $passkeyOptionsJson): ?Passkey
    {
        $passkey = parent::execute($publicKeyCredentialJson, $passkeyOptionsJson);

        if (! $passkey) {
            return null;
        }

        $user = $passkey->authenticatable;

        if (! $user || ! $user->ativo || ! $user->podeUsarPasskey()) {
            return null;
        }

        // Define a rota pretendida de redirecionamento com base no papel do usuário
        if ($user->isSuperAdmin()) {
            session(['passkeys.redirect' => route('usuarios.index')]);
        } elseif ($user->isInstalador()) {
            session(['passkeys.redirect' => route('instalacoes.index')]);
        } else {
            session(['passkeys.redirect' => route('dashboard')]);
        }

        return $passkey;
    }
}
