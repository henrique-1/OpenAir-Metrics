<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    /**
     * Lista os usuários municipais da cidade do administrador logado com busca, filtros e ordenação.
     */
    public function index(Request $request): View
    {
        $currentUser = $request->user();
        if (! $currentUser->isAdministrador()) {
            abort(403, 'Acesso restrito a administradores.');
        }

        $query = User::with('cidade.estado');

        if ($currentUser->cidade_id) {
            $query->where('cidade_id', $currentUser->cidade_id);
        }

        // Filtro de busca textual (nome ou email)
        $busca = trim((string) $request->input('busca'));
        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                $q->where('name', 'like', "%{$busca}%")
                    ->orWhere('email', 'like', "%{$busca}%");
            });
        }

        // Filtro por nível de acesso
        $nivel = $request->input('nivel');
        if ($nivel && in_array($nivel, ['administrador', 'cadastrador', 'instalador'], true)) {
            $query->where('nivel', $nivel);
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
            'filtros' => [
                'busca' => $busca,
                'nivel' => $nivel,
                'status' => $status,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Exibe o formulário de cadastro de novo usuário para o município.
     */
    public function create(Request $request): View
    {
        $currentUser = $request->user();
        if (! $currentUser->isAdministrador()) {
            abort(403, 'Acesso restrito a administradores.');
        }

        $currentUser->load('cidade.estado');

        return view('usuarios.create', [
            'currentUser' => $currentUser,
            'cidade' => $currentUser->cidade,
        ]);
    }

    /**
     * Salva o novo usuário e executa a regra de transição de gestão se for um novo administrador.
     */
    public function store(Request $request): RedirectResponse
    {
        $currentUser = $request->user();
        if (! $currentUser->isAdministrador()) {
            abort(403, 'Acesso restrito a administradores.');
        }

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

        if ($validated['nivel'] === 'administrador') {
            DB::transaction(function () use ($validated, $currentUser) {
                User::create($validated);
                // O administrador original perde acesso ao sistema e tem sua conta desabilitada (transição de gestão)
                $currentUser->update(['ativo' => false]);
            });

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('info', 'Novo administrador municipal cadastrado com sucesso. Conforme a regra de transição da prefeitura, sua conta de administrador anterior foi transferida e desativada.');
        }

        User::create($validated);

        return redirect()->route('usuarios.index')->with('success', 'Usuário municipal cadastrado com sucesso!');
    }

    /**
     * Alterna o status (ativo / desativado) de um usuário municipal.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();
        if (! $currentUser->isAdministrador()) {
            abort(403, 'Acesso restrito a administradores.');
        }

        if ($currentUser->cidade_id && $user->cidade_id !== $currentUser->cidade_id) {
            abort(403, 'Acesso restrito à sua jurisdição municipal.');
        }

        // Regra de segurança: o administrador logado não pode desativar a própria conta
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Você não pode desativar sua própria conta de administrador.');
        }

        $novoStatus = ! $user->ativo;
        $user->update(['ativo' => $novoStatus]);

        $mensagem = $novoStatus
            ? "O usuário {$user->name} foi reativado com sucesso!"
            : "O usuário {$user->name} foi desativado com sucesso!";

        return back()->with('success', $mensagem);
    }

    /**
     * Exibe o formulário de edição de um usuário municipal.
     */
    public function edit(Request $request, User $user): View
    {
        $currentUser = $request->user();
        if (! $currentUser->isAdministrador()) {
            abort(403, 'Acesso restrito a administradores.');
        }

        if ($currentUser->cidade_id && $user->cidade_id !== $currentUser->cidade_id) {
            abort(403, 'Acesso restrito à sua jurisdição municipal.');
        }

        $user->load('cidade.estado');

        return view('usuarios.edit', [
            'usuario' => $user,
            'currentUser' => $currentUser,
            'cidade' => $user->cidade ?? $currentUser->cidade,
        ]);
    }

    /**
     * Atualiza os dados de um usuário municipal.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();
        if (! $currentUser->isAdministrador()) {
            abort(403, 'Acesso restrito a administradores.');
        }

        if ($currentUser->cidade_id && $user->cidade_id !== $currentUser->cidade_id) {
            abort(403, 'Acesso restrito à sua jurisdição municipal.');
        }

        $isSelf = ($user->id === $currentUser->id);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'nivel' => ['required', 'in:administrador,cadastrador,instalador'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'cep' => ['nullable', 'string', 'max:9'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];

        // Regra estrita: Nenhum usuário pode trocar o próprio e-mail
        if (! $isSelf) {
            $rules['email'] = ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)];
        }

        $validated = $request->validate($rules);

        // Se estiver editando a si mesmo, preserva o e-mail original intocado
        if ($isSelf) {
            $validated['email'] = $user->email;
        }

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        // Se promover outro usuário a administrador municipal, aciona a regra de sucessão
        if ($validated['nivel'] === 'administrador' && ! $isSelf) {
            DB::transaction(function () use ($user, $validated, $currentUser) {
                $user->update($validated);
                $currentUser->update(['ativo' => false]);
            });

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('info', "Administração municipal transferida para {$user->name}. Sua conta anterior foi desativada.");
        }

        $user->update($validated);

        return redirect()->route('usuarios.index')->with('success', "Dados do usuário {$user->name} atualizados com sucesso!");
    }
}
