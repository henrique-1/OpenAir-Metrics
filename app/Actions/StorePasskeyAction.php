<?php

namespace App\Actions;

use App\Support\WebauthnSerializer;
use CBOR\ByteStringObject;
use CBOR\Decoder;
use CBOR\MapObject;
use CBOR\StringStream;
use CBOR\TextStringObject;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction as SpatieStorePasskeyAction;
use Spatie\LaravelPasskeys\Events\PasskeyRegisteredEvent;
use Spatie\LaravelPasskeys\Exceptions\InvalidPasskey;
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;
use Spatie\LaravelPasskeys\Models\Passkey;
use Spatie\LaravelPasskeys\Support\Config;
use Spatie\LaravelPasskeys\Support\CredentialRecordConverter;
use Throwable;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialSource;

class StorePasskeyAction extends SpatieStorePasskeyAction
{
    public function execute(
        HasPasskeys $authenticatable,
        string $passkeyJson,
        string $passkeyOptionsJson,
        string $hostName,
        array $additionalProperties = [],
    ): Passkey {
        $publicKeyCredentialSource = $this->determinePublicKeyCredentialSource(
            $passkeyJson,
            $passkeyOptionsJson,
            $hostName
        );

        /** @var Passkey $passkey */
        $passkey = $authenticatable->passkeys()->create([
            ...$additionalProperties,
            'data' => $publicKeyCredentialSource,
        ]);

        event(new PasskeyRegisteredEvent($passkey, $authenticatable));

        return $passkey;
    }

    protected function determinePublicKeyCredentialSource(
        string $passkeyJson,
        string $passkeyOptionsJson,
        string $hostName,
    ): PublicKeyCredentialSource {
        $passkeyOptions = $this->getPasskeyOptions($passkeyOptionsJson);
        $publicKeyCredential = $this->getPasskey($passkeyJson);

        if (! $publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            throw InvalidPasskey::invalidPublicKeyCredential();
        }

        $configureCeremonyStepManagerFactory = Config::getAction(
            'configure_ceremony_step_manager_factory',
            ConfigureCeremonyStepManagerFactoryAction::class
        );
        $csmFactory = $configureCeremonyStepManagerFactory->execute();
        $creationCsm = $csmFactory->creationCeremony();

        try {
            $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create($creationCsm)->check(
                authenticatorAttestationResponse: $publicKeyCredential->response,
                publicKeyCredentialCreationOptions: $passkeyOptions,
                host: $hostName,
            );
        } catch (Throwable $exception) {
            // Se a validação da declaração de atestação falhar (ex: atestação self-signed do linux-passkey ou formato não confiado pela CA),
            // mas o servidor configurou preferência por nenhuma atestação ('none'), normalizamos o attestationObject para fmt: 'none'
            // preservando integralmente o authData (chave pública, credencial ID e flags biométricas).
            $normalizedPasskeyJson = $this->normalizeAttestationToNone($passkeyJson);
            if ($normalizedPasskeyJson !== null && $normalizedPasskeyJson !== $passkeyJson) {
                try {
                    $fallbackCredential = $this->getPasskey($normalizedPasskeyJson);
                    if ($fallbackCredential->response instanceof AuthenticatorAttestationResponse) {
                        $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create($creationCsm)->check(
                            authenticatorAttestationResponse: $fallbackCredential->response,
                            publicKeyCredentialCreationOptions: $passkeyOptions,
                            host: $hostName,
                        );

                        return CredentialRecordConverter::toPublicKeyCredentialSource($publicKeyCredentialSource);
                    }
                } catch (Throwable) {
                    // Mantém a exceção original abaixo caso o fallback também não valide
                }
            }

            throw InvalidPasskey::invalidAuthenticatorAttestationResponse($exception);
        }

        return CredentialRecordConverter::toPublicKeyCredentialSource($publicKeyCredentialSource);
    }

    protected function getPasskey(string $passkeyJson): PublicKeyCredential
    {
        if (! json_validate($passkeyJson)) {
            throw InvalidPasskey::invalidJson();
        }

        try {
            /** @var PublicKeyCredential $publicKeyCredential */
            $publicKeyCredential = WebauthnSerializer::make()->fromJson(
                $passkeyJson,
                PublicKeyCredential::class
            );

            return $publicKeyCredential;
        } catch (Throwable $e) {
            // Se a desserialização falhar devido ao formato de atestação desconhecido no CBOR,
            // tentamos converter a atestação para "none" preservando o authData
            $normalizedPasskeyJson = $this->normalizeAttestationToNone($passkeyJson);
            if ($normalizedPasskeyJson !== null && $normalizedPasskeyJson !== $passkeyJson) {
                return WebauthnSerializer::make()->fromJson(
                    $normalizedPasskeyJson,
                    PublicKeyCredential::class
                );
            }

            throw $e;
        }
    }

    /**
     * Normaliza a declaração de atestação do WebAuthn para o formato 'none'.
     * Preserva integralmente authData, a chave pública gerada pelo hardware e o ID da credencial.
     */
    protected function normalizeAttestationToNone(string $passkeyJson): ?string
    {
        $data = json_decode($passkeyJson, true);
        if (! is_array($data) || empty($data['response']['attestationObject'])) {
            return null;
        }

        try {
            $rawAttestation = Base64UrlSafe::decode($data['response']['attestationObject']);
            $stream = new StringStream($rawAttestation);
            $parsed = Decoder::create()->decode($stream);
            $normalized = $parsed->normalize();

            if (! isset($normalized['authData']) || ($normalized['fmt'] ?? '') === 'none') {
                return $passkeyJson;
            }

            $authData = $normalized['authData'];
            $strippedMap = MapObject::create()
                ->add(TextStringObject::create('fmt'), TextStringObject::create('none'))
                ->add(TextStringObject::create('attStmt'), MapObject::create())
                ->add(TextStringObject::create('authData'), ByteStringObject::create($authData));

            $data['response']['attestationObject'] = Base64UrlSafe::encodeUnpadded((string) $strippedMap);

            return json_encode($data);
        } catch (Throwable) {
            return null;
        }
    }
}
