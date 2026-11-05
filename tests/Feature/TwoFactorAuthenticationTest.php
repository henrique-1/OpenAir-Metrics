<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

test('usuario autenticado pode habilitar autenticacao em duas etapas (2FA)', function () {
    $user = User::factory()->create([
        'nivel' => 'administrador',
        'ativo' => true,
    ]);

    $response = $this->actingAs($user)->post('/user/two-factor-authentication');

    $response->assertRedirect();
    $user->refresh();

    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

test('usuario nao pode confirmar 2FA com codigo invalido', function () {
    $user = User::factory()->create([
        'nivel' => 'administrador',
        'ativo' => true,
    ]);

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $user->refresh();

    $response = $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', [
        'code' => '000000',
    ]);

    $response->assertSessionHasErrors('code', null, 'confirmTwoFactorAuthentication');
    $user->refresh();

    expect($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();
});

test('usuario confirma 2FA com sucesso utilizando codigo TOTP valido', function () {
    $user = User::factory()->create([
        'nivel' => 'administrador',
        'ativo' => true,
    ]);

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $user->refresh();

    $google2fa = app(Google2FA::class);
    $validCode = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

    $response = $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', [
        'code' => $validCode,
    ]);

    $response->assertRedirect();
    $user->refresh();

    expect($user->two_factor_confirmed_at)->not->toBeNull()
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeTrue()
        ->and($user->recoveryCodes())->toHaveCount(8);
});

test('login redireciona para desafio de 2FA quando o usuario tem 2FA confirmado', function () {
    $user = User::factory()->create([
        'email' => 'admin.mfa@prefeitura.sp.gov.br',
        'password' => bcrypt('SenhaForte123456!'),
        'nivel' => 'administrador',
        'ativo' => true,
    ]);

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $user->refresh();
    $validCode = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));
    $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', ['code' => $validCode]);
    $user->refresh();

    auth()->logout();

    $response = $this->post(route('login.store'), [
        'email' => 'admin.mfa@prefeitura.sp.gov.br',
        'password' => 'SenhaForte123456!',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
    expect(session('login.id'))->toBe($user->id);
});

test('usuario autentica com sucesso no desafio de 2FA com codigo TOTP valido', function () {
    $secret = app(TwoFactorAuthenticationProvider::class)->generateSecretKey();

    $user = User::factory()->create([
        'nivel' => 'administrador',
        'ativo' => true,
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1', 'code-2'])),
    ]);

    $validCode = app(Google2FA::class)->getCurrentOtp($secret);

    $response = $this->withSession(['login.id' => $user->id])
        ->post('/two-factor-challenge', [
            'code' => $validCode,
        ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('desafio 2FA redireciona instalador para rota de instalacoes', function () {
    $secret = app(TwoFactorAuthenticationProvider::class)->generateSecretKey();

    $user = User::factory()->create([
        'nivel' => 'instalador',
        'ativo' => true,
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => encrypt(json_encode(['code-1', 'code-2'])),
    ]);

    $validCode = app(Google2FA::class)->getCurrentOtp($secret);

    $response = $this->withSession(['login.id' => $user->id])
        ->post('/two-factor-challenge', [
            'code' => $validCode,
        ]);

    $response->assertRedirect(route('instalacoes.index'));
    $this->assertAuthenticatedAs($user);
});

test('desafio 2FA rejeita codigo invalido mantendo usuario nao autenticado', function () {
    $secret = app(TwoFactorAuthenticationProvider::class)->generateSecretKey();

    $user = User::factory()->create([
        'nivel' => 'administrador',
        'ativo' => true,
        'two_factor_secret' => encrypt($secret),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->withSession(['login.id' => $user->id])
        ->post('/two-factor-challenge', [
            'code' => '999999',
        ]);

    $response->assertSessionHasErrors('code');
    $this->assertGuest();
});

test('usuario pode se autenticar utilizando codigo de recuperacao de backup e o codigo e consumido', function () {
    $user = User::factory()->create([
        'nivel' => 'administrador',
        'ativo' => true,
    ]);

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $user->refresh();
    $validCode = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));
    $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', ['code' => $validCode]);
    $user->refresh();

    $recoveryCodes = $user->recoveryCodes();
    $recoveryCodeToUse = $recoveryCodes[0];

    auth()->logout();

    $response = $this->withSession(['login.id' => $user->id])
        ->post('/two-factor-challenge', [
            'recovery_code' => $recoveryCodeToUse,
        ]);

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->recoveryCodes())->not->toContain($recoveryCodeToUse)
        ->and($user->recoveryCodes())->toHaveCount(8);
});

test('usuario autenticado pode regenerar codigos de recuperacao', function () {
    $user = User::factory()->create([
        'nivel' => 'administrador',
        'ativo' => true,
    ]);

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $user->refresh();
    $validCode = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));
    $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', ['code' => $validCode]);
    $user->refresh();

    $oldCodes = $user->recoveryCodes();

    $response = $this->actingAs($user)->post('/user/two-factor-recovery-codes');
    $response->assertRedirect();

    $user->refresh();
    $newCodes = $user->recoveryCodes();

    expect($newCodes)->toHaveCount(8)
        ->and($newCodes)->not->toEqual($oldCodes);
});

test('usuario autenticado pode desativar o 2FA', function () {
    $user = User::factory()->create([
        'nivel' => 'administrador',
        'ativo' => true,
    ]);

    $this->actingAs($user)->post('/user/two-factor-authentication');
    $user->refresh();
    $validCode = app(Google2FA::class)->getCurrentOtp(decrypt($user->two_factor_secret));
    $this->actingAs($user)->post('/user/confirmed-two-factor-authentication', ['code' => $validCode]);
    $user->refresh();

    expect($user->hasEnabledTwoFactorAuthentication())->toBeTrue();

    $response = $this->actingAs($user)->delete('/user/two-factor-authentication');
    $response->assertRedirect();

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->hasEnabledTwoFactorAuthentication())->toBeFalse();
});
