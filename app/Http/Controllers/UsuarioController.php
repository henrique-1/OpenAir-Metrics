<?php

namespace App\Http\Controllers;

use App\Models\Cidade;
use App\Models\Estado;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    /**
     * Lista os usuários municipais da cidade do administrador ou todos os administradores se for super-usuário.
     */
    public function index(Request $request): View
    {
        $currentUser = $request->user();
        if (! $currentUser->isSuperAdmin() && ! $currentUser->isAdministrador()) {
            abort(403, 'Acesso restrito a administradores.');
        }

        $query = User::with('cidade.estado');

        $cidades = null;
        if ($currentUser->isSuperAdmin()) {
            // Super-usuário gerencia administradores municipais em todo o sistema
            $query->where('nivel', 'administrador');

            if ($request->filled('cidade_id')) {
                $query->where('cidade_id', $request->input('cidade_id'));
            }

            $cidades = Cidade::with('estado')->orderBy('nome')->get();
        } else {
            // Administrador municipal gerencia apenas os usuários da sua jurisdição (Planejador Técnico e Instalador)
            $query->where('cidade_id', $currentUser->cidade_id)
                ->whereIn('nivel', ['cadastrador', 'instalador']);

            // Filtro por nível de acesso para admin municipal
            $nivel = $request->input('nivel');
            if ($nivel && in_array($nivel, ['cadastrador', 'instalador'], true)) {
                $query->where('nivel', $nivel);
            }
        }

        // Filtro de busca textual (nome ou email)
        $busca = trim((string) $request->input('busca'));
        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                $q->where('name', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%");
            });
        }

        // Filtro por status
        $status = $request->input('status');
        if ($status === 'ativo') {
            $query->where('ativo', true);
        } elseif ($status === 'inativo') {
            $query->where('ativo', false);
        }

        // Ordenação
        $sort = (string) $request->input('sort', 'name');
        $direction = strtolower((string) $request->input('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $allowedSorts = ['name', 'email', 'nivel', 'ativo', 'created_at'];

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $usuarios = $query->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'currentUser' => $currentUser,
            'cidades' => $cidades,
            'filtros' => [
                'busca' => $busca,
                'nivel' => $request->input('nivel'),
                'status' => $status,
                'cidade_id' => $request->input('cidade_id'),
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Exibe o formulário de cadastro de novo usuário.
     */
    public function create(Request $request): View
    {
        $currentUser = $request->user();
        if (! $currentUser->isSuperAdmin() && ! $currentUser->isAdministrador()) {
            abort(403, 'Acesso restrito a administradores.');
        }

        $currentUser->load('cidade.estado');

        $estados = null;
        $cidades = null;
        $selectedEstadoId = null;
        if ($currentUser->isSuperAdmin()) {
            $estados = Estado::orderBy('nome')->get();
            $selectedEstadoId = old('estado_id');
            if (! $selectedEstadoId && old('cidade_id')) {
                $cidadeOld = Cidade::find(old('cidade_id'));
                $selectedEstadoId = $cidadeOld?->estado_id;
            }
            $cidades = $selectedEstadoId ? Cidade::where('estado_id', $selectedEstadoId)->orderBy('nome')->get() : collect();
        }

        return view('usuarios.create', [
            'currentUser' => $currentUser,
            'cidade' => $currentUser->cidade,
            'estados' => $estados,
            'cidades' => $cidades,
            'selectedEstadoId' => $selectedEstadoId,
        ]);
    }

    /**
     * Salva o novo usuário conforme o nível do usuário logado.
     */
    public function store(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        if (! $currentUser->isSuperAdmin() && ! $currentUser->isAdministrador()) {
            abort(403, 'Acesso restrito a administradores.');
        }

        if ($currentUser->isSuperAdmin()) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'estado_id' => ['nullable', 'exists:estados,id'],
                'cidade_id' => [
                    'required',
                    'exists:cidades,id',
                    Rule::exists('cidades', 'id')->when(
                        $request->filled('estado_id'),
                        fn ($q) => $q->where('estado_id', $request->input('estado_id'))
                    ),
                ],
                'logradouro' => ['nullable', 'string', 'max:255'],
                'numero' => ['nullable', 'string', 'max:20'],
                'complemento' => ['nullable', 'string', 'max:255'],
                'bairro' => ['nullable', 'string', 'max:255'],
                'estado' => ['nullable', 'string', 'max:2'],
                'cep' => ['nullable', 'string', 'max:9'],
            ]);

            $validated['nivel'] = 'administrador';
            $validated['ativo'] = true;
            unset($validated['estado_id']);

            User::create($validated);

            return redirect()->route('usuarios.index')->with('success', 'Administrador municipal cadastrado com sucesso!');
        }

        // Administrador municipal: cadastra técnicos, instaladores ou um sucessor administrador
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'nivel' => ['required', 'in:administrador,cadastrador,instalador'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:2'],
            'cep' => ['nullable', 'string', 'max:9'],
        ]);

        $validated['cidade_id'] = $currentUser->cidade_id;
        $validated['ativo'] = true;

        User::create($validated);

        if ($validated['nivel'] === 'administrador') {
            $currentUser->update(['ativo' => false]);
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('info', 'Novo administrador municipal cadastrado com sucesso. Conforme a regra de sucessão administrativa municipal, sua conta foi desativada e você foi desconectado.');
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuário cadastrado com sucesso!');
    }

    /**
     * Alterna o status (ativo/inativo) de um usuário.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();
        if (! $currentUser->isSuperAdmin() && ! $currentUser->isAdministrador()) {
            abort(403, 'Acesso restrito a administradores.');
        }

        if ($currentUser->isSuperAdmin()) {
            if ($user->id === $currentUser->id || $user->isSuperAdmin()) {
                return back()->with('error', 'Você não pode desativar seu próprio acesso.');
            }
            if (! $user->isAdministrador()) {
                abort(403, 'Super-usuário só pode gerenciar administradores municipais.');
            }
        } else {
            if ($user->id === $currentUser->id) {
                return back()->with('error', 'Você não pode desativar sua própria conta.');
            }
            if ($user->cidade_id !== $currentUser->cidade_id || ! in_array($user->nivel, ['cadastrador', 'instalador'], true)) {
                abort(403, 'Acesso restrito a usuários sob sua jurisdição municipal.');
            }
        }

        $novoStatus = ! $user->ativo;
        $user->update(['ativo' => $novoStatus]);

        $mensagem = $novoStatus
            ? "O usuário {$user->name} foi reativado com sucesso!"
            : "O usuário {$user->name} foi desativado com sucesso!";

        return back()->with('success', $mensagem);
    }

    /**
     * Exibe o formulário de edição de um usuário.
     */
    public function edit(Request $request, User $user): View
    {
        $currentUser = $request->user();
        if (! $currentUser->isSuperAdmin() && ! $currentUser->isAdministrador()) {
            abort(403, 'Acesso restrito a administradores.');
        }

        $estados = null;
        $cidades = null;
        $selectedEstadoId = null;
        if ($currentUser->isSuperAdmin()) {
            if (! $user->isAdministrador()) {
                abort(403, 'Super-usuário só pode gerenciar administradores municipais.');
            }
            $estados = Estado::orderBy('nome')->get();
            $selectedEstadoId = old('estado_id', $user->cidade?->estado_id);
            $cidades = $selectedEstadoId ? Cidade::where('estado_id', $selectedEstadoId)->orderBy('nome')->get() : collect();
        } else {
            if ($user->cidade_id !== $currentUser->cidade_id) {
                abort(403, 'Acesso restrito a usuários sob sua jurisdição municipal.');
            }
            if ($user->id !== $currentUser->id && ! in_array($user->nivel, ['cadastrador', 'instalador'], true)) {
                abort(403, 'Acesso restrito a usuários sob sua jurisdição municipal.');
            }
        }

        $user->load('cidade.estado');

        return view('usuarios.edit', [
            'usuario' => $user,
            'currentUser' => $currentUser,
            'cidade' => $user->cidade ?? $currentUser->cidade,
            'estados' => $estados,
            'cidades' => $cidades,
            'selectedEstadoId' => $selectedEstadoId,
        ]);
    }

    /**
     * Atualiza os dados de um usuário.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();
        if (! $currentUser->isSuperAdmin() && ! $currentUser->isAdministrador()) {
            abort(403, 'Acesso restrito a administradores.');
        }

        if ($currentUser->isSuperAdmin()) {
            if (! $user->isAdministrador()) {
                abort(403, 'Super-usuário só pode gerenciar administradores municipais.');
            }

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'estado_id' => ['nullable', 'exists:estados,id'],
                'cidade_id' => [
                    'required',
                    'exists:cidades,id',
                    Rule::exists('cidades', 'id')->when(
                        $request->filled('estado_id'),
                        fn ($q) => $q->where('estado_id', $request->input('estado_id'))
                    ),
                ],
                'logradouro' => ['nullable', 'string', 'max:255'],
                'numero' => ['nullable', 'string', 'max:20'],
                'complemento' => ['nullable', 'string', 'max:255'],
                'bairro' => ['nullable', 'string', 'max:255'],
                'cep' => ['nullable', 'string', 'max:9'],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);

            unset($validated['estado_id']);

            if (empty($validated['password'])) {
                unset($validated['password']);
            }

            $user->update($validated);

            return redirect()->route('usuarios.index')->with('success', "Administrador {$user->name} atualizado com sucesso!");
        }

        // Administrador municipal
        if ($user->cidade_id !== $currentUser->cidade_id) {
            abort(403, 'Acesso restrito a usuários sob sua jurisdição municipal.');
        }

        $isSelf = ($user->id === $currentUser->id);

        if (! $isSelf && ! in_array($user->nivel, ['cadastrador', 'instalador'], true)) {
            abort(403, 'Acesso restrito a usuários sob sua jurisdição municipal.');
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'cep' => ['nullable', 'string', 'max:9'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        if ($isSelf) {
            $validated = $request->validate($rules);
        } else {
            $rules['email'] = ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)];
            $rules['nivel'] = ['required', 'in:cadastrador,instalador'];
            $validated = $request->validate($rules);
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('usuarios.index')->with('success', "Dados do usuário {$user->name} atualizados com sucesso!");
    }
}
