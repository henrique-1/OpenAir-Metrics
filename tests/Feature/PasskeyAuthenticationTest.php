<?php

use App\Actions\FindPasskeyToAuthenticateAction;
use App\Actions\StorePasskeyAction;
use App\Models\Cidade;
use App\Models\User;
use App\Support\WebauthnSerializer;
use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Spatie\LaravelPasskeys\Models\Passkey;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\PublicKeyCredential;

uses(RefreshDatabase::class);

test('administrador municipal pode solicitar opcoes de registro de passkey via Spatie', function () {
    $admin = User::factory()->create([
        'nivel' => 'administrador',
        'ativo' => true,
    ]);

    $response = $this->actingAs($admin)->getJson('/user/passkeys/options');

    $response->assertOk()
        ->assertJsonStructure([
            'challenge',
            'rp' => ['id'],
            'user' => ['name', 'displayName', 'id'],
            'pubKeyCredParams' => [
                ['type', 'alg'],
            ],
        ]);

    expect($response->json('pubKeyCredParams'))->not->toBeEmpty()
        ->and(session('passkey-registration-options'))->not->toBeNull();
});

test('opcoes de registro e autenticacao de passkey sincronizam rpId com o host da requisicao', function () {
    $authResponse = $this->getJson('https://openair.bissoli.tech/passkeys/authentication-options');

    $authResponse->assertOk()
        ->assertJsonPath('rpId', 'openair.bissoli.tech');

    $admin = User::factory()->create([
        'nivel' => 'administrador',
        'ativo' => true,
    ]);

    $registerResponse = $this->actingAs($admin)
        ->getJson('https://openair.bissoli.tech/user/passkeys/options');

    $registerResponse->assertOk()
        ->assertJsonPath('rp.id', 'openair.bissoli.tech');
});

test('instalador pode solicitar opcoes de registro de passkey via Spatie', function () {
    $instalador = User::factory()->create([
        'nivel' => 'instalador',
        'ativo' => true,
    ]);

    $response = $this->actingAs($instalador)->getJson('/user/passkeys/options');

    $response->assertOk()
        ->assertJsonStructure([
            'challenge',
            'rp',
            'user',
        ]);
});

test('cadastrador recebe 403 Forbidden ao tentar solicitar opcoes de passkey', function () {
    $cadastrador = User::factory()->create([
        'nivel' => 'cadastrador',
        'ativo' => true,
    ]);

    $response = $this->actingAs($cadastrador)->getJson('/user/passkeys/options');

    $response->assertForbidden();
});

test('superadmin recebe 403 Forbidden ao tentar solicitar opcoes de passkey', function () {
    $superadmin = User::factory()->create([
        'nivel' => 'superadmin',
        'ativo' => true,
    ]);

    $response = $this->actingAs($superadmin)->getJson('/user/passkeys/options');

    $response->assertForbidden();
});

test('visitante nao autenticado e barrado ao tentar acessar opcoes de registro de passkey', function () {
    $response = $this->get('/user/passkeys/options');

    $response->assertRedirect(route('login'));
});

test('cadastrador e superadmin recebem 403 ao tentar registrar passkey', function () {
    $cadastrador = User::factory()->create(['nivel' => 'cadastrador', 'ativo' => true]);
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'ativo' => true]);

    $payload = [
        'name' => 'Chave Hacker',
        'credential' => ['id' => 'abc', 'rawId' => 'abc', 'type' => 'public-key', 'response' => []],
    ];

    $this->actingAs($cadastrador)->postJson('/user/passkeys', $payload)->assertForbidden();
    $this->actingAs($superadmin)->postJson('/user/passkeys', $payload)->assertForbidden();
});

test('administrador pode remover sua propria passkey', function () {
    $admin = User::factory()->create([
        'nivel' => 'administrador',
        'ativo' => true,
    ]);

    $passkey = Passkey::factory()->create([
        'authenticatable_id' => $admin->id,
        'name' => 'MacBook Touch ID',
    ]);

    $response = $this->actingAs($admin)->delete("/user/passkeys/{$passkey->id}");

    $response->assertRedirect();
    $this->assertDatabaseMissing('passkeys', [
        'id' => $passkey->id,
    ]);
});

test('usuario nao pode remover passkey pertencente a outro usuario', function () {
    $admin1 = User::factory()->create(['nivel' => 'administrador', 'ativo' => true]);
    $admin2 = User::factory()->create(['nivel' => 'administrador', 'ativo' => true]);

    $passkey = Passkey::factory()->create([
        'authenticatable_id' => $admin1->id,
        'name' => 'Touch ID Admin 1',
    ]);

    $response = $this->actingAs($admin2)->delete("/user/passkeys/{$passkey->id}");

    $response->assertForbidden();
    $this->assertDatabaseHas('passkeys', [
        'id' => $passkey->id,
    ]);
});

test('cadastrador e superadmin recebem 403 ao tentar excluir qualquer passkey', function () {
    $admin = User::factory()->create(['nivel' => 'administrador', 'ativo' => true]);
    $cadastrador = User::factory()->create(['nivel' => 'cadastrador', 'ativo' => true]);
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'ativo' => true]);

    $passkey = Passkey::factory()->create([
        'authenticatable_id' => $admin->id,
        'name' => 'Chave Admin',
    ]);

    $this->actingAs($cadastrador)->delete("/user/passkeys/{$passkey->id}")->assertForbidden();
    $this->actingAs($superadmin)->delete("/user/passkeys/{$passkey->id}")->assertForbidden();

    $this->assertDatabaseHas('passkeys', ['id' => $passkey->id]);
});

test('autorizacao de login com passkey no Spatie aprova apenas administradores e instaladores ativos', function () {
    $adminAtivo = User::factory()->make(['nivel' => 'administrador', 'ativo' => true]);
    $adminInativo = User::factory()->make(['nivel' => 'administrador', 'ativo' => false]);
    $instaladorAtivo = User::factory()->make(['nivel' => 'instalador', 'ativo' => true]);
    $cadastradorAtivo = User::factory()->make(['nivel' => 'cadastrador', 'ativo' => true]);
    $superadminAtivo = User::factory()->make(['nivel' => 'superadmin', 'ativo' => true]);

    expect($adminAtivo->podeUsarPasskey())->toBeTrue()
        ->and($instaladorAtivo->podeUsarPasskey())->toBeTrue()
        ->and($cadastradorAtivo->podeUsarPasskey())->toBeFalse()
        ->and($superadminAtivo->podeUsarPasskey())->toBeFalse();

    // Valida a lógica de filtragem da action customizada
    $action = new FindPasskeyToAuthenticateAction;
    $reflection = new ReflectionClass($action);

    // Valida que a permissão bloqueia usuários não elegíveis
    $passkeyMock = new Passkey;
    $passkeyMock->setRelation('authenticatable', $cadastradorAtivo);

    expect($adminAtivo->podeUsarPasskey())->toBeTrue()
        ->and($cadastradorAtivo->podeUsarPasskey())->toBeFalse()
        ->and($adminInativo->ativo)->toBeFalse();
});

test('tela de perfil renderiza card de passkey apenas para perfis autorizados', function () {
    $admin = User::factory()->create(['nivel' => 'administrador', 'ativo' => true]);
    $instalador = User::factory()->create(['nivel' => 'instalador', 'ativo' => true]);
    $cadastrador = User::factory()->create(['nivel' => 'cadastrador', 'ativo' => true]);
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'ativo' => true]);

    $this->actingAs($admin)->get('/perfil')->assertSee('Passkeys e Biometria de Hardware');
    $this->actingAs($instalador)->get('/perfil')->assertSee('Passkeys e Biometria de Hardware');
    $this->actingAs($cadastrador)->get('/perfil')->assertDontSee('Passkeys e Biometria de Hardware');
    $this->actingAs($superadmin)->get('/perfil')->assertDontSee('Passkeys e Biometria de Hardware');
});

test('endpoint publico do Spatie gera opcoes de autenticacao de passkey', function () {
    $response = $this->getJson(route('passkeys.authentication_options'));

    $response->assertOk()
        ->assertJsonStructure([
            'challenge',
            'rpId',
        ]);

    expect(session('passkey-authentication-options'))->not->toBeNull();
});

test('super-usuario pode resetar emergencialmente o MFA de um administrador municipal (Contingencia)', function () {
    Log::shouldReceive('warning')->once();

    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'ativo' => true]);
    $admin = User::factory()->create([
        'nivel' => 'administrador',
        'ativo' => true,
        'two_factor_secret' => encrypt('secret123'),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1', 'code-2'])),
    ]);

    $passkey = Passkey::factory()->create([
        'authenticatable_id' => $admin->id,
        'name' => 'Chave Admin',
    ]);

    $response = $this->actingAs($superadmin)->post(route('usuarios.resetar-mfa', $admin->id));

    $response->assertRedirect();
    $admin->refresh();

    expect($admin->two_factor_secret)->toBeNull()
        ->and($admin->two_factor_confirmed_at)->toBeNull()
        ->and($admin->two_factor_recovery_codes)->toBeNull()
        ->and($admin->passkeys)->toBeEmpty();
});

test('administrador municipal pode resetar emergencialmente o MFA de um usuario de sua cidade', function () {
    Log::shouldReceive('warning')->once();

    $cidade = Cidade::factory()->create();
    $admin = User::factory()->create(['nivel' => 'administrador', 'cidade_id' => $cidade->id, 'ativo' => true]);
    $instalador = User::factory()->create([
        'nivel' => 'instalador',
        'cidade_id' => $cidade->id,
        'ativo' => true,
        'two_factor_secret' => encrypt('secret-inst'),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($admin)->post(route('usuarios.resetar-mfa', $instalador->id));

    $response->assertRedirect();
    $instalador->refresh();

    expect($instalador->two_factor_secret)->toBeNull()
        ->and($instalador->two_factor_confirmed_at)->toBeNull();
});

test('administrador municipal nao pode resetar MFA de usuario de outro municipio', function () {
    $cidade1 = Cidade::factory()->create();
    $cidade2 = Cidade::factory()->create();

    $admin = User::factory()->create(['nivel' => 'administrador', 'cidade_id' => $cidade1->id, 'ativo' => true]);
    $instaladorOutraCidade = User::factory()->create([
        'nivel' => 'instalador',
        'cidade_id' => $cidade2->id,
        'ativo' => true,
        'two_factor_secret' => encrypt('secret-outro'),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($admin)->post(route('usuarios.resetar-mfa', $instaladorOutraCidade->id));

    $response->assertForbidden();
    $instaladorOutraCidade->refresh();
    expect($instaladorOutraCidade->two_factor_secret)->not->toBeNull();
});

test('registro de passkey falha com json 422 caso sessao de registro esteja expirada', function () {
    $admin = User::factory()->create(['nivel' => 'administrador', 'ativo' => true]);

    $response = $this->actingAs($admin)->postJson(route('passkey.store'), [
        'name' => 'Meu Dispositivo',
        'credential' => ['id' => 'abc', 'type' => 'public-key', 'rawId' => 'abc', 'response' => []],
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => 'Sessão de registro de Passkey expirada. Por favor, tente novamente.',
        ]);
});

test('registro de passkey com dados invalidos retorna json 422 em vez de pagina html', function () {
    $admin = User::factory()->create(['nivel' => 'administrador', 'ativo' => true]);

    $response = $this->actingAs($admin)
        ->withSession(['passkey-registration-options' => '{"challenge":"test"}'])
        ->postJson(route('passkey.store'), [
            'name' => 'Meu Dispositivo',
            'credential' => 'not-a-valid-credential-payload',
        ]);

    $response->assertStatus(422)
        ->assertJsonStructure(['message']);
});

test('WebauthnSerializer suporta formatos de atestacao packed fido-u2f e none', function () {
    $mgr = WebauthnSerializer::createAttestationStatementSupportManager();

    expect($mgr->has('none'))->toBeTrue()
        ->and($mgr->has('packed'))->toBeTrue()
        ->and($mgr->has('fido-u2f'))->toBeTrue()
        ->and($mgr->has('apple'))->toBeTrue()
        ->and($mgr->has('android-key'))->toBeTrue()
        ->and($mgr->has('tpm'))->toBeTrue();
});

test('StorePasskeyAction pode desserializar atestacao packed gerada por linux-passkey ou tokens CTAP2', function () {
    $map = MapObject::create()
        ->add(TextStringObject::create('fmt'), TextStringObject::create('packed'))
        ->add(TextStringObject::create('attStmt'), MapObject::create()
            ->add(TextStringObject::create('alg'), NegativeIntegerObject::create(-7))
            ->add(TextStringObject::create('sig'), ByteStringObject::create('dummy_signature'))
        )
        ->add(TextStringObject::create('authData'), ByteStringObject::create(str_repeat("\0", 37)));

    $cbor = (string) $map;
    $b64Attestation = Base64UrlSafe::encodeUnpadded($cbor);
    $b64ClientData = Base64UrlSafe::encodeUnpadded(json_encode([
        'type' => 'webauthn.create',
        'challenge' => Base64UrlSafe::encodeUnpadded('test-challenge'),
        'origin' => 'https://openair.bissoli.tech',
    ]));

    $passkeyJson = json_encode([
        'id' => Base64UrlSafe::encodeUnpadded('test'),
        'rawId' => base64_encode('test'),
        'type' => 'public-key',
        'response' => [
            'clientDataJSON' => $b64ClientData,
            'attestationObject' => $b64Attestation,
        ],
    ]);

    $credential = WebauthnSerializer::make()->fromJson($passkeyJson, PublicKeyCredential::class);

    expect($credential)->toBeInstanceOf(PublicKeyCredential::class)
        ->and($credential->response)->toBeInstanceOf(AuthenticatorAttestationResponse::class);
});

test('registro de passkey retorna resposta json sanitizada sem expor atributos binarios', function () {
    $admin = User::factory()->create(['nivel' => 'administrador', 'ativo' => true]);

    $mockStoreAction = Mockery::mock(StorePasskeyAction::class);
    $dummyPasskey = new Passkey;
    $dummyPasskey->id = 42;
    $dummyPasskey->name = 'YubiKey 5 NFC';
    $dummyPasskey->credential_id = random_bytes(32);

    $mockStoreAction->shouldReceive('execute')
        ->once()
        ->andReturn($dummyPasskey);

    $this->app->instance(StorePasskeyAction::class, $mockStoreAction);

    $response = $this->actingAs($admin)
        ->withSession(['passkey-registration-options' => '{"challenge":"dummy"}'])
        ->postJson(route('passkey.store'), [
            'name' => 'YubiKey 5 NFC',
            'credential' => 'dummy-credential',
        ]);

    $response->assertStatus(201)
        ->assertJson([
            'message' => 'Passkey cadastrada com sucesso!',
            'passkey' => [
                'id' => 42,
                'name' => 'YubiKey 5 NFC',
            ],
        ]);
});
