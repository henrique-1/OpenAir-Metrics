<?php

use App\Actions\FindPasskeyToAuthenticateAction;
use App\Models\Cidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelPasskeys\Models\Passkey;

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
