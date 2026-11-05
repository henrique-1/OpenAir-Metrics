<?php

use App\Http\Controllers\PasswordResetLinkController;
use App\Models\User;
use App\Notifications\RedefinirSenhaNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\NotPwnedVerifier;

uses(RefreshDatabase::class);

const SENHA_FORTE = 'cavalo correto bateria grampo';

/**
 * Gera um token de redefinição válido para o usuário informado.
 */
function gerarTokenDeRedefinicao(User $user): string
{
    return Password::broker()->createToken($user);
}

test('login exibe o link de recuperação de senha', function () {
    $this->get(route('login'))
        ->assertSuccessful()
        ->assertSee('Não lembra a senha?')
        ->assertSee('Recupere aqui')
        ->assertSee(route('password.request'), false);
});

test('tela de recuperação pré-preenche o e-mail vindo do login', function () {
    $this->get(route('password.request', ['email' => 'tecnico@openair.com']))
        ->assertSuccessful()
        ->assertSee('Enviar e-mail de recuperação')
        ->assertSee('value="tecnico@openair.com"', false);
});

test('tela de recuperação abre com e-mail vazio quando não informado', function () {
    $this->get(route('password.request'))
        ->assertSuccessful()
        ->assertSee('value=""', false);
});

test('usuário ativo recebe o e-mail de recuperação', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status', PasswordResetLinkController::MENSAGEM_LINK_ENVIADO);

    Notification::assertSentTo($user, RedefinirSenhaNotification::class);
    $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
});

test('e-mail de recuperação é enfileirado e contém o link de redefinição em português', function () {
    $user = User::factory()->create();
    $notificacao = new RedefinirSenhaNotification('token-de-teste');

    $mensagem = $notificacao->toMail($user);

    expect($notificacao)->toBeInstanceOf(ShouldQueue::class)
        ->and($mensagem->subject)->toBe('Recuperação de senha — OpenAir Metrics')
        ->and($mensagem->actionText)->toBe('Redefinir senha')
        ->and($mensagem->actionUrl)->toBe(route('password.reset', ['token' => 'token-de-teste', 'email' => $user->email]));
});

test('e-mail inexistente recebe a mesma resposta genérica e nenhuma notificação', function () {
    Notification::fake();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'naoexiste@openair.com'])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', PasswordResetLinkController::MENSAGEM_LINK_ENVIADO);

    Notification::assertNothingSent();
});

test('usuário inativo não recebe o e-mail de recuperação', function () {
    Notification::fake();
    $user = User::factory()->create(['ativo' => false]);

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email])
        ->assertSessionHas('status', PasswordResetLinkController::MENSAGEM_LINK_ENVIADO);

    Notification::assertNothingSent();
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
});

test('solicitação de recuperação exige e-mail válido', function () {
    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'invalido'])
        ->assertSessionHasErrors(['email' => 'Informe um endereço de e-mail válido.']);
});

test('solicitações de recuperação são limitadas a 5 por minuto', function () {
    Notification::fake();

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('password.email'), ['email' => 'alguem@openair.com'])->assertRedirect();
    }

    $this->post(route('password.email'), ['email' => 'alguem@openair.com'])->assertTooManyRequests();
});

test('tela de redefinição carrega com token e e-mail', function () {
    $this->get(route('password.reset', ['token' => 'abc123', 'email' => 'tecnico@openair.com']))
        ->assertSuccessful()
        ->assertSee('Redefinir senha')
        ->assertSee('value="abc123"', false)
        ->assertSee('value="tecnico@openair.com"', false);
});

test('usuário redefine a senha com token válido e consegue logar', function () {
    $user = User::factory()->create();
    $rememberTokenAntigo = $user->remember_token;
    $token = gerarTokenDeRedefinicao($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => SENHA_FORTE,
        'password_confirmation' => SENHA_FORTE,
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('status', 'Senha redefinida com sucesso! Faça login com a nova senha.');

    $user->refresh();
    expect(Hash::check(SENHA_FORTE, $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe($rememberTokenAntigo);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => SENHA_FORTE]);
    $this->assertAuthenticatedAs($user);
});

test('redefinição encerra as sessões do usuário quando o driver de sessão é banco de dados', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    $outroUsuario = User::factory()->create();

    DB::table('sessions')->insert([
        ['id' => 'sessao-usuario', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp],
        ['id' => 'sessao-outro', 'user_id' => $outroUsuario->id, 'payload' => '', 'last_activity' => now()->timestamp],
    ]);

    $this->post(route('password.update'), [
        'token' => gerarTokenDeRedefinicao($user),
        'email' => $user->email,
        'password' => SENHA_FORTE,
        'password_confirmation' => SENHA_FORTE,
    ])->assertRedirect(route('login'));

    $this->assertDatabaseMissing('sessions', ['id' => 'sessao-usuario']);
    $this->assertDatabaseHas('sessions', ['id' => 'sessao-outro']);
});

test('token inválido é rejeitado', function () {
    $user = User::factory()->create();

    $this->from(route('password.reset', ['token' => 'invalido']))
        ->post(route('password.update'), [
            'token' => 'invalido',
            'email' => $user->email,
            'password' => SENHA_FORTE,
            'password_confirmation' => SENHA_FORTE,
        ])
        ->assertSessionHasErrors(['email' => 'Este link de recuperação é inválido ou expirou. Solicite um novo.']);

    expect(Hash::check(SENHA_FORTE, $user->refresh()->password))->toBeFalse();
});

test('token expirado é rejeitado', function () {
    $user = User::factory()->create();
    $token = gerarTokenDeRedefinicao($user);

    $this->travel(31)->minutes();

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => SENHA_FORTE,
        'password_confirmation' => SENHA_FORTE,
    ])->assertSessionHasErrors('email');

    expect(Hash::check(SENHA_FORTE, $user->refresh()->password))->toBeFalse();
});

test('usuário inativo não consegue redefinir a senha', function () {
    $user = User::factory()->create();
    $token = gerarTokenDeRedefinicao($user);
    $user->update(['ativo' => false]);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => SENHA_FORTE,
        'password_confirmation' => SENHA_FORTE,
    ])->assertSessionHasErrors('email');

    expect(Hash::check(SENHA_FORTE, $user->refresh()->password))->toBeFalse();
});

test('confirmação de senha deve conferir', function () {
    $user = User::factory()->create();

    $this->post(route('password.update'), [
        'token' => gerarTokenDeRedefinicao($user),
        'email' => $user->email,
        'password' => SENHA_FORTE,
        'password_confirmation' => 'outra frase totalmente diferente',
    ])->assertSessionHasErrors(['password' => 'A confirmação da senha não confere.']);
});

test('política NIST rejeita senhas fora do intervalo de 15 a 64 caracteres', function (string $senha, string $mensagem) {
    $user = User::factory()->create();

    $this->post(route('password.update'), [
        'token' => gerarTokenDeRedefinicao($user),
        'email' => $user->email,
        'password' => $senha,
        'password_confirmation' => $senha,
    ])->assertSessionHasErrors(['password' => $mensagem]);
})->with([
    '14 caracteres' => [str_repeat('a', 14), 'A senha deve ter pelo menos 15 caracteres.'],
    '65 caracteres' => [str_repeat('a', 65), 'A senha não pode ter mais de 64 caracteres.'],
]);

test('política NIST aceita senhas sem regras de composição', function (string $senha) {
    $user = User::factory()->create();

    $this->post(route('password.update'), [
        'token' => gerarTokenDeRedefinicao($user),
        'email' => $user->email,
        'password' => $senha,
        'password_confirmation' => $senha,
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login'));

    expect(Hash::check($senha, $user->refresh()->password))->toBeTrue();
})->with([
    'exatamente 15 minúsculas' => ['abcdefghijklmno'],
    'exatamente 64 caracteres' => [str_repeat('b', 64)],
    'frase com espaços' => [SENHA_FORTE],
    'frase com acentos' => ['pão de queijo com café coado'],
]);

test('política NIST bloqueia senhas presentes em vazamentos conhecidos', function () {
    $senhaVazada = 'senha vazada em algum lugar';
    $hash = strtoupper(sha1($senhaVazada));

    $this->app->instance(UncompromisedVerifier::class, new NotPwnedVerifier(app(HttpFactory::class)));
    Http::fake([
        'api.pwnedpasswords.com/range/'.substr($hash, 0, 5) => Http::response(substr($hash, 5).':42'),
    ]);

    $user = User::factory()->create();

    $this->post(route('password.update'), [
        'token' => gerarTokenDeRedefinicao($user),
        'email' => $user->email,
        'password' => $senhaVazada,
        'password_confirmation' => $senhaVazada,
    ])->assertSessionHasErrors([
        'password' => 'Esta senha apareceu em um vazamento de dados conhecido. Por segurança, escolha outra.',
    ]);

    expect(Hash::check($senhaVazada, $user->refresh()->password))->toBeFalse();
});
