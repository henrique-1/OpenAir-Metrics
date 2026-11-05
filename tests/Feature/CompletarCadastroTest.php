<?php

use App\Models\User;
use App\Notifications\ConviteCadastroNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\NotPwnedVerifier;

uses(RefreshDatabase::class);

const SENHA_CADASTRO_FORTE = 'minha nova senha de acesso segura';

test('email de convite e ativacao e enfileirado e contem o link com validade', function () {
    $user = User::factory()->create([
        'nivel' => 'instalador',
        'email_verified_at' => null,
    ]);
    $notificacao = new ConviteCadastroNotification('token-ativacao-123');

    $mensagem = $notificacao->toMail($user);

    expect($notificacao)->toBeInstanceOf(ShouldQueue::class)
        ->and($mensagem->subject)->toBe('Ativação de conta — OpenAir Metrics')
        ->and($mensagem->actionText)->toBe('Completar cadastro e ativar conta')
        ->and($mensagem->actionUrl)->toBe(route('cadastro.completar', ['token' => 'token-ativacao-123', 'email' => $user->email]));
});

test('tela de completar cadastro carrega com token e email preenchido', function () {
    $this->get(route('cadastro.completar', ['token' => 'token-abc', 'email' => 'novo@prefeitura.sp.gov.br']))
        ->assertSuccessful()
        ->assertSee('Ativação de Conta')
        ->assertSee('value="token-abc"', false)
        ->assertSee('value="novo@prefeitura.sp.gov.br"', false);
});

test('novo usuario define sua senha e endereco opcional com sucesso', function () {
    $user = User::factory()->create([
        'email' => 'colaborador@prefeitura.sp.gov.br',
        'email_verified_at' => null,
    ]);
    $token = Password::broker()->createToken($user);

    $response = $this->post(route('cadastro.salvar'), [
        'token' => $token,
        'email' => $user->email,
        'password' => SENHA_CADASTRO_FORTE,
        'password_confirmation' => SENHA_CADASTRO_FORTE,
        'cep' => '13010-000',
        'logradouro' => 'Avenida Francisco Glicério',
        'numero' => '1000',
        'complemento' => 'Sala 12',
        'bairro' => 'Centro',
        'estado' => 'SP',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status', 'Cadastro ativado com sucesso! Faça login com a sua nova senha.');

    $user->refresh();
    expect(Hash::check(SENHA_CADASTRO_FORTE, $user->password))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->cep)->toBe('13010-000')
        ->and($user->logradouro)->toBe('Avenida Francisco Glicério')
        ->and($user->numero)->toBe('1000')
        ->and($user->bairro)->toBe('Centro')
        ->and($user->estado)->toBe('SP');

    // Consegue efetuar login com as novas credenciais
    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => SENHA_CADASTRO_FORTE,
    ]);
    $this->assertAuthenticatedAs($user);
});

test('completar cadastro rejeita senha menor que 15 caracteres (NIST)', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $response = $this->post(route('cadastro.salvar'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'senhaCurta1234',
        'password_confirmation' => 'senhaCurta1234',
    ]);

    $response->assertSessionHasErrors(['password' => 'A senha deve ter pelo menos 15 caracteres.']);
    expect(Hash::check('senhaCurta1234', $user->refresh()->password))->toBeFalse();
});

test('completar cadastro bloqueia senhas vazadas conhecidas', function () {
    $senhaVazada = 'senha vazada em lista publica';
    $hash = strtoupper(sha1($senhaVazada));

    $this->app->instance(UncompromisedVerifier::class, new NotPwnedVerifier(app(HttpFactory::class)));
    Http::fake([
        'api.pwnedpasswords.com/range/'.substr($hash, 0, 5) => Http::response(substr($hash, 5).':99'),
    ]);

    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $response = $this->post(route('cadastro.salvar'), [
        'token' => $token,
        'email' => $user->email,
        'password' => $senhaVazada,
        'password_confirmation' => $senhaVazada,
    ]);

    $response->assertSessionHasErrors(['password' => 'Esta senha apareceu em um vazamento de dados conhecido. Por segurança, escolha outra.']);
    expect(Hash::check($senhaVazada, $user->refresh()->password))->toBeFalse();
});

test('token de ativacao expirado (30 min) e rejeitado', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $this->travel(31)->minutes();

    $response = $this->post(route('cadastro.salvar'), [
        'token' => $token,
        'email' => $user->email,
        'password' => SENHA_CADASTRO_FORTE,
        'password_confirmation' => SENHA_CADASTRO_FORTE,
    ]);

    $response->assertSessionHasErrors('email');
    expect(Hash::check(SENHA_CADASTRO_FORTE, $user->refresh()->password))->toBeFalse();
});

test('token de ativacao invalido e rejeitado', function () {
    $user = User::factory()->create();

    $response = $this->post(route('cadastro.salvar'), [
        'token' => 'token-falso',
        'email' => $user->email,
        'password' => SENHA_CADASTRO_FORTE,
        'password_confirmation' => SENHA_CADASTRO_FORTE,
    ]);

    $response->assertSessionHasErrors('email');
    expect(Hash::check(SENHA_CADASTRO_FORTE, $user->refresh()->password))->toBeFalse();
});
