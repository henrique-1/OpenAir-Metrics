<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Contracts\PasskeyLoginResponse as PasskeyLoginResponseContract;
use Laravel\Passkeys\Passkey;
use Laravel\Passkeys\Passkeys;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Customização de resposta pós-login com Passkey
        $this->app->singleton(PasskeyLoginResponseContract::class, function () {
            return new class implements PasskeyLoginResponseContract
            {
                public function toResponse($request)
                {
                    $user = Auth::user();
                    $target = route('dashboard');

                    if ($user?->isSuperAdmin()) {
                        $target = route('usuarios.index');
                    } elseif ($user?->isInstalador()) {
                        $target = route('instalacoes.index');
                    }

                    if ($request->wantsJson()) {
                        return new JsonResponse([
                            'redirect' => redirect()->intended($target)->getTargetUrl(),
                        ], 200);
                    }

                    return redirect()->intended($target);
                }
            };
        });

        // Customização de resposta pós-desafio TOTP 2FA
        $this->app->singleton(TwoFactorLoginResponseContract::class, function () {
            return new class implements TwoFactorLoginResponseContract
            {
                public function toResponse($request)
                {
                    $user = Auth::user();
                    $target = route('dashboard');

                    if ($user?->isSuperAdmin()) {
                        $target = route('usuarios.index');
                    } elseif ($user?->isInstalador()) {
                        $target = route('instalacoes.index');
                    }

                    return redirect()->intended($target);
                }
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::twoFactorChallengeView(fn () => view('auth.two-factor-challenge'));
        Fortify::confirmPasswordView(fn () => view('auth.confirm-password'));

        // Gate que restringe o gerenciamento de Passkeys exclusivamente a Administradores Municipais e Instaladores
        Gate::define('manage-passkeys', function (User $user): bool {
            return $user->podeUsarPasskey();
        });

        // Autorização no login com Passkey: usuário deve estar ativo e possuir papel autorizado
        Passkeys::authorizeLoginUsing(function (Request $request, User $user, Passkey $passkey): bool {
            return (bool) ($user->ativo && $user->podeUsarPasskey());
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });
    }
}
