<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurarPoliticaDeSenha();
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
