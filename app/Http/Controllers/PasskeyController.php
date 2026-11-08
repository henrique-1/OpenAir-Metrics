<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;
use Spatie\LaravelPasskeys\Models\Passkey;

class PasskeyController extends Controller
{
    /**
     * Gera e retorna as opções de registro de uma nova Passkey para o usuário autenticado.
     */
    public function options(Request $request, GeneratePasskeyRegisterOptionsAction $generateOptions): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->podeUsarPasskey(), 403, 'Acesso restrito a Administradores Municipais e Instaladores.');

        if ($request->getHost()) {
            config([
                'passkeys.relying_party.id' => $request->getHost(),
                'passkeys.relying_party_id' => $request->getHost(),
            ]);
        }

        $options = $generateOptions->execute($user, asJson: true);
        $optionsData = json_decode($options, true);

        if (empty($optionsData['pubKeyCredParams'])) {
            $optionsData['pubKeyCredParams'] = [
                ['type' => 'public-key', 'alg' => -7],   // ES256 (ECDSA w/ SHA-256)
                ['type' => 'public-key', 'alg' => -257], // RS256 (RSASSA-PKCS1-v1_5 w/ SHA-256)
            ];
            $options = json_encode($optionsData);
        }

        session(['passkey-registration-options' => $options]);

        return response()->json($optionsData);
    }

    /**
     * Valida a atestação e salva a nova credencial pública biométrica.
     */
    public function store(Request $request, StorePasskeyAction $storePasskey): JsonResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->podeUsarPasskey(), 403, 'Acesso restrito a Administradores Municipais e Instaladores.');

        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'credential' => ['required'],
        ]);

        $optionsJson = session()->pull('passkey-registration-options');
        abort_unless($optionsJson, 422, 'Sessão de registro de Passkey expirada. Tente novamente.');

        $credentialPayload = is_string($request->input('credential'))
            ? $request->input('credential')
            : json_encode($request->input('credential'));

        $passkey = $storePasskey->execute(
            authenticatable: $user,
            passkeyJson: $credentialPayload,
            passkeyOptionsJson: $optionsJson,
            hostName: $request->getHost(),
            additionalProperties: [
                'name' => $request->input('name'),
            ]
        );

        return response()->json([
            'message' => 'Passkey cadastrada com sucesso!',
            'passkey' => $passkey,
        ], 201);
    }

    /**
     * Exclui uma Passkey pertencente ao usuário autenticado.
     */
    public function destroy(Passkey $passkey): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && $user->podeUsarPasskey(), 403, 'Acesso restrito a Administradores Municipais e Instaladores.');
        abort_unless($passkey->authenticatable_id === $user->id, 403, 'Você não tem permissão para remover esta Passkey.');

        $passkey->delete();

        return back()->with('success', 'Passkey removida com sucesso.');
    }
}
