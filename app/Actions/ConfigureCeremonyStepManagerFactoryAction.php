<?php

namespace App\Actions;

use App\Support\WebauthnSerializer;
use Spatie\LaravelPasskeys\Actions\ConfigureCeremonyStepManagerFactoryAction as SpatieConfigureCeremonyStepManagerFactoryAction;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;

class ConfigureCeremonyStepManagerFactoryAction extends SpatieConfigureCeremonyStepManagerFactoryAction
{
    public function execute(): CeremonyStepManagerFactory
    {
        $csmFactory = new CeremonyStepManagerFactory;

        $csmFactory->setAttestationStatementSupportManager(
            WebauthnSerializer::createAttestationStatementSupportManager()
        );

        $csmFactory->setAlgorithmManager(
            WebauthnSerializer::createAlgorithmManager()
        );

        return $csmFactory;
    }
}
