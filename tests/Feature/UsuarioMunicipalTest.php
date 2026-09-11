<?php

use App\Models\Cidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('nao administradores nao podem acessar a gestao de usuarios municipais', function () {
    $cadastrador = User::factory()->create(['nivel' => 'cadastrador']);
    $instalador = User::factory()->create(['nivel' => 'instalador']);

    $this->actingAs($cadastrador)->get(route('usuarios.index'))->assertStatus(403);
    $this->actingAs($cadastrador)->get(route('usuarios.create'))->assertStatus(403);
    $this->actingAs($cadastrador)->post(route('usuarios.store'), [])->assertStatus(403);

    $this->actingAs($instalador)->get(route('usuarios.index'))->assertStatus(403);
    $this->actingAs($instalador)->get(route('usuarios.create'))->assertStatus(403);
    $this->actingAs($instalador)->post(route('usuarios.store'), [])->assertStatus(403);
});

test('administrador pode listar apenas usuarios de seu municipio', function () {
    $cidade1 = Cidade::factory()->create(['nome' => 'Cidade Alpha']);
    $cidade2 = Cidade::factory()->create(['nome' => 'Cidade Beta']);

    $admin1 = User::factory()->create([
        'nivel' => 'administrador',
        'cidade_id' => $cidade1->id,
    ]);

    $userAlpha = User::factory()->create([
        'name' => 'Tecnico Alpha',
        'cidade_id' => $cidade1->id,
    ]);

    $userBeta = User::factory()->create([
        'name' => 'Tecnico Beta',
        'cidade_id' => $cidade2->id,
    ]);

    $response = $this->actingAs($admin1)->get(route('usuarios.index'));

    $response->assertOk();
    $response->assertSee('Tecnico Alpha');
    $response->assertDontSee('Tecnico Beta');
});

test('administrador pode cadastrar cadastrador ou instalador para o municipio', function () {
    $cidade = Cidade::factory()->create(['nome' => 'Campinas']);
    $admin = User::factory()->create([
        'nivel' => 'administrador',
        'cidade_id' => $cidade->id,
    ]);

    $payload = [
        'name' => 'Novo Instalador Municipal',
        'email' => 'instalador@campinas.sp.gov.br',
        'password' => 'senhaSegura123',
        'password_confirmation' => 'senhaSegura123',
        'nivel' => 'instalador',
    ];

    $response = $this->actingAs($admin)->post(route('usuarios.store'), $payload);

    $response->assertRedirect(route('usuarios.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'email' => 'instalador@campinas.sp.gov.br',
        'nivel' => 'instalador',
        'cidade_id' => $cidade->id,
        'ativo' => true,
    ]);
});

test('administrador municipal pode cadastrar outro administrador sob sucessao desativando a propria conta', function () {
    $cidade = Cidade::factory()->create(['nome' => 'São Paulo']);
    $adminOriginal = User::factory()->create([
        'name' => 'Gestor Municipal',
        'email' => 'gestor@prefeitura.sp.gov.br',
        'nivel' => 'administrador',
        'cidade_id' => $cidade->id,
        'ativo' => true,
    ]);

    $payloadNovoAdmin = [
        'name' => 'Prefeito Novo',
        'email' => 'novo.titular@prefeitura.sp.gov.br',
        'password' => 'senhaNova12345',
        'password_confirmation' => 'senhaNova12345',
        'nivel' => 'administrador',
    ];

    $response = $this->actingAs($adminOriginal)->post(route('usuarios.store'), $payloadNovoAdmin);

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('info');

    $this->assertDatabaseHas('users', [
        'email' => 'novo.titular@prefeitura.sp.gov.br',
        'nivel' => 'administrador',
        'cidade_id' => $cidade->id,
        'ativo' => true,
    ]);

    $adminOriginal->refresh();
    expect($adminOriginal->ativo)->toBeFalse();
    $this->assertGuest();
});

test('administrador pode filtrar e ordenar usuarios na listagem', function () {
    $cidade = Cidade::factory()->create();
    $admin = User::factory()->create([
        'name' => 'Ana Administradora',
        'email' => 'ana@cidade.sp.gov.br',
        'nivel' => 'administrador',
        'cidade_id' => $cidade->id,
        'ativo' => true,
    ]);

    $tecnico = User::factory()->create([
        'name' => 'Bruno Planejador',
        'email' => 'bruno@cidade.sp.gov.br',
        'nivel' => 'cadastrador',
        'cidade_id' => $cidade->id,
        'ativo' => true,
    ]);

    $instaladorInativo = User::factory()->create([
        'name' => 'Carlos Instalador',
        'email' => 'carlos@cidade.sp.gov.br',
        'nivel' => 'instalador',
        'cidade_id' => $cidade->id,
        'ativo' => false,
    ]);

    // Busca textual
    $responseBusca = $this->actingAs($admin)->get(route('usuarios.index', ['busca' => 'Bruno']));
    $responseBusca->assertOk();
    $responseBusca->assertSee('Bruno Planejador');
    $responseBusca->assertDontSee('Carlos Instalador');

    // Filtro por nível
    $responseNivel = $this->actingAs($admin)->get(route('usuarios.index', ['nivel' => 'instalador']));
    $responseNivel->assertOk();
    $responseNivel->assertSee('Carlos Instalador');
    $responseNivel->assertDontSee('Bruno Planejador');

    // Filtro por status inativo
    $responseStatus = $this->actingAs($admin)->get(route('usuarios.index', ['status' => 'inativo']));
    $responseStatus->assertOk();
    $responseStatus->assertSee('Carlos Instalador');
    $responseStatus->assertDontSee('Bruno Planejador');
});

test('administrador pode desativar e reativar usuario da sua equipe', function () {
    $cidade = Cidade::factory()->create();
    $admin = User::factory()->create([
        'nivel' => 'administrador',
        'cidade_id' => $cidade->id,
    ]);

    $usuario = User::factory()->create([
        'cidade_id' => $cidade->id,
        'ativo' => true,
    ]);

    // Desativar
    $responseDesativar = $this->actingAs($admin)->patch(route('usuarios.toggle-status', $usuario->id));
    $responseDesativar->assertRedirect();
    $responseDesativar->assertSessionHas('success');

    $usuario->refresh();
    expect($usuario->ativo)->toBeFalse();

    // Reativar
    $responseReativar = $this->actingAs($admin)->patch(route('usuarios.toggle-status', $usuario->id));
    $responseReativar->assertRedirect();
    $responseReativar->assertSessionHas('success');

    $usuario->refresh();
    expect($usuario->ativo)->toBeTrue();
});

test('administrador nao pode desativar a si proprio', function () {
    $cidade = Cidade::factory()->create();
    $admin = User::factory()->create([
        'nivel' => 'administrador',
        'cidade_id' => $cidade->id,
        'ativo' => true,
    ]);

    $response = $this->actingAs($admin)->patch(route('usuarios.toggle-status', $admin->id));
    $response->assertRedirect();
    $response->assertSessionHas('error');

    $admin->refresh();
    expect($admin->ativo)->toBeTrue();
});

test('administrador nao pode desativar usuario de outro municipio', function () {
    $cidade1 = Cidade::factory()->create();
    $cidade2 = Cidade::factory()->create();

    $admin = User::factory()->create([
        'nivel' => 'administrador',
        'cidade_id' => $cidade1->id,
    ]);

    $outroUsuario = User::factory()->create([
        'cidade_id' => $cidade2->id,
        'ativo' => true,
    ]);

    $this->actingAs($admin)->patch(route('usuarios.toggle-status', $outroUsuario->id))
        ->assertStatus(403);

    $outroUsuario->refresh();
    expect($outroUsuario->ativo)->toBeTrue();
});

test('administrador pode editar usuario e nenhum usuario pode trocar o proprio email', function () {
    $cidade = Cidade::factory()->create();
    $admin = User::factory()->create([
        'email' => 'admin.original@cidade.sp.gov.br',
        'nivel' => 'administrador',
        'cidade_id' => $cidade->id,
    ]);

    $outroUsuario = User::factory()->create([
        'name' => 'Nome Antigo',
        'email' => 'tecnico.antigo@cidade.sp.gov.br',
        'nivel' => 'cadastrador',
        'cidade_id' => $cidade->id,
    ]);

    // Tela de edição
    $this->actingAs($admin)->get(route('usuarios.edit', $outroUsuario->id))->assertOk();

    // Administrador edita outro usuário e pode atualizar o email do outro usuário
    $responseUpdateOutro = $this->actingAs($admin)->put(route('usuarios.update', $outroUsuario->id), [
        'name' => 'Nome Atualizado',
        'email' => 'tecnico.novo@cidade.sp.gov.br',
        'nivel' => 'instalador',
    ]);
    $responseUpdateOutro->assertRedirect(route('usuarios.index'));

    $outroUsuario->refresh();
    expect($outroUsuario->name)->toBe('Nome Atualizado');
    expect($outroUsuario->email)->toBe('tecnico.novo@cidade.sp.gov.br');
    expect($outroUsuario->nivel)->toBe('instalador');

    // Ao editar a si mesmo, o próprio email NÃO pode ser alterado
    $responseUpdateSelf = $this->actingAs($admin)->put(route('usuarios.update', $admin->id), [
        'name' => 'Admin Nome Novo',
        'email' => 'tentativa.troca@cidade.sp.gov.br',
        'nivel' => 'administrador',
    ]);
    $responseUpdateSelf->assertRedirect(route('usuarios.index'));

    $admin->refresh();
    expect($admin->name)->toBe('Admin Nome Novo');
    expect($admin->email)->toBe('admin.original@cidade.sp.gov.br'); // Permanece inalterado!
});

test('telas de cadastro e edicao de usuario renderizam cards interativos de nivel de acesso', function () {
    $cidade = Cidade::factory()->create();
    $admin = User::factory()->create([
        'nivel' => 'administrador',
        'cidade_id' => $cidade->id,
    ]);

    $usuario = User::factory()->create([
        'nivel' => 'cadastrador',
        'cidade_id' => $cidade->id,
    ]);

    // Tela de cadastro
    $responseCreate = $this->actingAs($admin)->get(route('usuarios.create'));
    $responseCreate->assertOk();
    $responseCreate->assertSee('Planejador Técnico');
    $responseCreate->assertSee('Instalador');
    $responseCreate->assertSee('radio-administrador');
    $responseCreate->assertSee('radio-cadastrador');
    $responseCreate->assertSee('radio-instalador');

    // Tela de edição
    $responseEdit = $this->actingAs($admin)->get(route('usuarios.edit', $usuario->id));
    $responseEdit->assertOk();
    $responseEdit->assertSee('Planejador Técnico');
    $responseEdit->assertSee('Instalador');
    $responseEdit->assertDontSee('radio-administrador');
    $responseEdit->assertSee('radio-cadastrador');
    $responseEdit->assertSee('radio-instalador');
});
