<?php

namespace App\Http\Controllers;

use App\Models\Cidade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PerfilController extends Controller
{
    /**
     * Exibe o formulário de edição do perfil do usuário autenticado.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $user->load('cidade.estado');
        $cidades = Cidade::with('estado')->orderBy('nome')->get();

        return view('perfil.edit', [
            'user' => $user,
            'cidades' => $cidades,
        ]);
    }

    /**
     * Atualiza os dados cadastrais e endereço do usuário.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:255'],
            'bairro' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:2'],
            'cep' => ['nullable', 'string', 'max:9'],
        ];

        // Se o usuário ainda não tiver cidade vinculada e for permitido selecionar
        if (! $user->cidade_id) {
            $rules['cidade_id'] = ['nullable', 'exists:cidades,id'];
        }

        $validated = $request->validate($rules);

        // Se o usuário já tinha cidade fixa, não permite alteração de cidade_id por esta tela
        if ($user->cidade_id) {
            unset($validated['cidade_id']);
        }

        $user->update($validated);

        return redirect()->route('perfil.edit')->with('success', 'Perfil atualizado com sucesso!');
    }
}
