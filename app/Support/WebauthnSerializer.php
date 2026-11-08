<?php

namespace App\Support;

use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES256K;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\ECDSA\ES512;
use Cose\Algorithm\Signature\EdDSA\Ed25519;
use Cose\Algorithm\Signature\RSA\PS256;
use Cose\Algorithm\Signature\RSA\PS384;
use Cose\Algorithm\Signature\RSA\PS512;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithm\Signature\RSA\RS384;
use Cose\Algorithm\Signature\RSA\RS512;
use ReflectionProperty;
use Spatie\LaravelPasskeys\Support\PublicKeyCredentialRpEntityDenormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;
use Webauthn\AttestationStatement\AndroidKeyAttestationStatementSupport;
use Webauthn\AttestationStatement\AppleAttestationStatementSupport;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\FidoU2FAttestationStatementSupport;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AttestationStatement\PackedAttestationStatementSupport;
use Webauthn\AttestationStatement\TPMAttestationStatementSupport;
use Webauthn\Denormalizer\WebauthnSerializerFactory;

class WebauthnSerializer
{
    public static function createAlgorithmManager(): Manager
    {
        return Manager::create()->add(
            ES256::create(),
            ES256K::create(),
            ES384::create(),
            ES512::create(),
            RS256::create(),
            RS384::create(),
            RS512::create(),
            PS256::create(),
            PS384::create(),
            PS512::create(),
            Ed25519::create(),
        );
    }

    public static function createAttestationStatementSupportManager(): AttestationStatementSupportManager
    {
        $algorithmManager = self::createAlgorithmManager();

        return AttestationStatementSupportManager::create([
            new NoneAttestationStatementSupport,
            PackedAttestationStatementSupport::create($algorithmManager),
            FidoU2FAttestationStatementSupport::create(),
            AndroidKeyAttestationStatementSupport::create(),
            AppleAttestationStatementSupport::create(),
            TPMAttestationStatementSupport::create(),
        ]);
    }

    public static function make(): self
    {
        $attestationStatementSupportManager = self::createAttestationStatementSupportManager();

        /** @var SymfonySerializer $webauthnSerializer */
        $webauthnSerializer = (new WebauthnSerializerFactory($attestationStatementSupportManager))->create();

        $normalizers = (new ReflectionProperty(SymfonySerializer::class, 'normalizers'))->getValue($webauthnSerializer);

        $symfonySerializer = new SymfonySerializer(
            [new PublicKeyCredentialRpEntityDenormalizer, ...$normalizers],
            [new JsonEncoder],
        );

        return new self($symfonySerializer);
    }

    public function __construct(
        protected SymfonySerializer $serializer,
    ) {}

    public function toJson(mixed $value): string
    {
        return $this->serializer->serialize(
            $value,
            'json',
            [
                AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
                JsonEncode::OPTIONS => JSON_THROW_ON_ERROR,
            ]
        );
    }

    /**
     * @param  class-string  $desiredClass
     */
    public function fromJson(string $value, string $desiredClass): mixed
    {
        return $this->serializer->deserialize($value, $desiredClass, 'json');
    }
}
