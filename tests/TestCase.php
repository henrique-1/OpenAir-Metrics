<?php

namespace Tests;

use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->desabilitarVerificacaoDeSenhasVazadas();
    }

    /**
     * Evita chamadas reais à API Have I Been Pwned durante os testes.
     *
     * Testes que precisam validar o bloqueio de senhas vazadas devem registrar
     * o verificador real e simular a resposta HTTP com Http::fake().
     */
    protected function desabilitarVerificacaoDeSenhasVazadas(): void
    {
        $this->app->instance(UncompromisedVerifier::class, new class implements UncompromisedVerifier
        {
            /**
             * @param  array{value: string, threshold: int}  $data
             */
            public function verify($data): bool
            {
                return true;
            }
        });
    }
}
