<?php

namespace App\Providers;

use App\Actions\ConfigureCeremonyStepManagerFactoryAction;
use App\Actions\StorePasskeyAction;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\LaravelPasskeys\Models\Passkey;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \Spatie\LaravelPasskeys\Actions\StorePasskeyAction::class,
            StorePasskeyAction::class
        );

        $this->app->bind(
            \Spatie\LaravelPasskeys\Actions\ConfigureCeremonyStepManagerFactoryAction::class,
            ConfigureCeremonyStepManagerFactoryAction::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurarPoliticaDeSenha();
        $this->configurarGates();
        $this->configurarPasskeys();
    }

    /**
     * Define os Gates de autorização da aplicação.
     */
    private function configurarGates(): void
    {
        Gate::define('manage-passkeys', fn (User $user): bool => $user->podeUsarPasskey());
    }

    /**
     * Configura o modelo de Passkeys utilizado pela aplicação (Spatie).
     */
    private function configurarPasskeys(): void
    {
        Route::model('passkey', Passkey::class);
    }

    /**
     * Política de senha conforme NIST SP 800-63B Rev. 4 (jul/2025).
     *
     * - Mínimo de 15 caracteres (senha como único fator de autenticação) e até 64 caracteres.
     * - Sem regras de composição (maiúsculas, números ou símbolos não são exigidos).
     * - Bloqueio de senhas presentes em vazamentos conhecidos (Have I Been Pwned, via k-anonymity).
     * - Sem expiração periódica: a troca ocorre apenas por iniciativa do usuário ou comprometimento.
     */
    private function configurarPoliticaDeSenha(): void
    {
        Password::defaults(fn (): Password => Password::min(15)->max(64)->uncompromised());
    }
}
