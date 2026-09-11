<x-layouts.app title="{{ $currentUser->isSuperAdmin() ? 'Editar Administrador' : 'Editar Usuário Municipal' }} - OpenAir Metrics">
    <div class="flex flex-col md:flex-row h-full w-full bg-athens-gray-50 dark:bg-athens-gray-950 transition-colors duration-200">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="usuarios" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto min-h-0 p-4 sm:p-6 lg:p-8">
            <div class="max-w-4xl mx-auto space-y-6">

                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 text-sm text-athens-gray-500 dark:text-athens-gray-400">
                    <a href="{{ route('usuarios.index') }}" class="hover:text-blue-dianne-600 dark:hover:text-blue-dianne-400 transition">
                        {{ $currentUser->isSuperAdmin() ? 'Administradores' : 'Usuários Municipais' }}
                    </a>
                    <x-heroicon-o-chevron-right class="w-4 h-4" />
                    <span class="text-blue-dianne-950 dark:text-white font-medium">Editar {{ $currentUser->isSuperAdmin() ? 'Administrador' : 'Usuário' }}</span>
                </div>

                <!-- Erros -->
                @if ($errors->any())
                    <div class="bg-cinnabar-50 dark:bg-cinnabar-950/40 border border-cinnabar-200 dark:border-cinnabar-800 text-cinnabar-800 dark:text-cinnabar-200 px-4 py-3 rounded-xl">
                        <div class="flex items-center gap-2 font-semibold text-sm mb-1">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-cinnabar-600 dark:text-cinnabar-400" />
                            Por favor, corrija os erros abaixo:
                        </div>
                        <ul class="list-disc list-inside text-xs space-y-0.5 ml-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Cabeçalho -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-blue-dianne-950 dark:text-white tracking-tight">
                            {{ $currentUser->isSuperAdmin() ? 'Editar Administrador: ' : 'Editar Usuário: ' }}{{ $usuario->name }}
                        </h1>
                        <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mt-1">
                            @if($currentUser->isSuperAdmin())
                                Atualize os dados do administrador e sua jurisdição municipal.
                            @else
                                Atualize os dados e credenciais do membro da equipe municipal de 
                                <strong class="text-blue-dianne-950 dark:text-white">{{ $cidade->nome ?? 'Sua Cidade' }} ({{ $cidade->estado->uf ?? '' }})</strong>.
                            @endif
                        </p>
                    </div>
                    <div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold
                            @if($usuario->ativo) bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-800
                            @else bg-cinnabar-100 dark:bg-cinnabar-950/70 text-cinnabar-800 dark:text-cinnabar-200 border border-cinnabar-300 dark:border-cinnabar-800 @endif">
                            <span class="w-1.5 h-1.5 rounded-full {{ $usuario->ativo ? 'bg-emerald-500' : 'bg-cinnabar-500' }}"></span>
                            {{ $usuario->ativo ? 'Usuário Ativo' : 'Usuário Desativado' }}
                        </span>
                    </div>
                </div>

                <!-- Formulário -->
                <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-6">
                    <form action="{{ route('usuarios.update', $usuario->id) }}" method="POST" class="space-y-6" id="form-editar-usuario">
                        @csrf
                        @method('PUT')

                        <div>
                            <h2 class="text-lg font-bold text-blue-dianne-950 dark:text-white border-b border-athens-gray-100 dark:border-athens-gray-800 pb-2">Função e Acesso</h2>
                        </div>

                        @if($currentUser->isSuperAdmin())
                            <div class="bg-blue-dianne-50 dark:bg-blue-dianne-950/40 border border-blue-dianne-200 dark:border-blue-dianne-800 rounded-xl p-4 flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-blue-dianne-600 text-white">
                                    <x-heroicon-o-shield-check class="w-6 h-6" />
                                </div>
                                <div>
                                    <h4 class="font-bold text-sm text-blue-dianne-950 dark:text-white">Perfil: Administrador Municipal</h4>
                                    <p class="text-xs text-athens-gray-600 dark:text-athens-gray-400 mt-0.5">
                                        Gestor municipal responsável pela rede de estações e equipe da sua cidade.
                                    </p>
                                </div>
                            </div>
                        @else
                            <!-- Seleção de Nível e Permissões para Administrador Municipal -->
                            <div class="space-y-3">
                                <label class="block text-sm font-semibold text-blue-dianne-950 dark:text-white">
                                    Selecione o Nível de Acesso e Permissões <span class="text-cinnabar-500">*</span>
                                </label>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="grupo-niveis">
                                    <!-- Opção: Planejador Técnico -->
                                    <div 
                                        id="card-nivel-cadastrador"
                                        onclick="selecionarNivel('cadastrador')"
                                        class="nivel-card relative flex flex-col justify-between p-4 rounded-xl border-2 transition-all cursor-pointer select-none"
                                    >
                                        <input 
                                            type="radio" 
                                            name="nivel" 
                                            id="radio-cadastrador"
                                            value="cadastrador" 
                                            class="sr-only" 
                                            @checked(old('nivel', $usuario->nivel) === 'cadastrador')
                                        >
                                        <div>
                                            <div class="flex items-center justify-between gap-2 mb-2">
                                                <div class="flex items-center gap-2">
                                                    <div class="p-1.5 rounded-lg bg-blue-dianne-100 dark:bg-blue-dianne-900/60 text-blue-dianne-700 dark:text-blue-dianne-300">
                                                        <x-heroicon-o-pencil-square class="w-5 h-5" />
                                                    </div>
                                                    <span class="text-sm font-bold text-blue-dianne-950 dark:text-white">Planejador Técnico</span>
                                                </div>
                                                <div id="check-cadastrador" class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors">
                                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <p class="text-xs text-athens-gray-600 dark:text-athens-gray-400 leading-relaxed">
                                                Cadastra patrimônios físicos, solicita substituição de sensores e planeja malhas viárias.
                                            </p>
                                        </div>
                                        <div class="mt-3 pt-2.5 border-t border-athens-gray-200/60 dark:border-athens-gray-800/80 flex items-center gap-1.5 text-[11px] font-medium text-blue-dianne-600 dark:text-blue-dianne-400">
                                            <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
                                            <span>Gestão de Patrimônio & Malhas</span>
                                        </div>
                                    </div>

                                    <!-- Opção: Instalador de Campo -->
                                    <div 
                                        id="card-nivel-instalador"
                                        onclick="selecionarNivel('instalador')"
                                        class="nivel-card relative flex flex-col justify-between p-4 rounded-xl border-2 transition-all cursor-pointer select-none"
                                    >
                                        <input 
                                            type="radio" 
                                            name="nivel" 
                                            id="radio-instalador"
                                            value="instalador" 
                                            class="sr-only" 
                                            @checked(old('nivel', $usuario->nivel) === 'instalador')
                                        >
                                        <div>
                                            <div class="flex items-center justify-between gap-2 mb-2">
                                                <div class="flex items-center gap-2">
                                                    <div class="p-1.5 rounded-lg bg-tahiti-gold-100 dark:bg-tahiti-gold-900/60 text-tahiti-gold-700 dark:text-tahiti-gold-300">
                                                        <x-heroicon-o-wrench-screwdriver class="w-5 h-5" />
                                                    </div>
                                                    <span class="text-sm font-bold text-blue-dianne-950 dark:text-white">Instalador</span>
                                                </div>
                                                <div id="check-instalador" class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors">
                                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </div>
                                            </div>
                                            <p class="text-xs text-athens-gray-600 dark:text-athens-gray-400 leading-relaxed">
                                                Acessa o roteiro em campo, localiza os pontos geográficos e vincula o MAC/Patrimônio físico à estação.
                                            </p>
                                        </div>
                                        <div class="mt-3 pt-2.5 border-t border-athens-gray-200/60 dark:border-athens-gray-800/80 flex items-center gap-1.5 text-[11px] font-medium text-tahiti-gold-600 dark:text-tahiti-gold-400">
                                            <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
                                            <span>Ativação & Roteiro em Campo</span>
                                        </div>
                                    </div>
                                </div>
                                @error('nivel')
                                    <p class="text-xs text-cinnabar-600 dark:text-cinnabar-400 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
                            <!-- Jurisdição Municipal -->
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">
                                    Jurisdição Municipal <span class="text-cinnabar-500">*</span>
                                </label>
                                @if($currentUser->isSuperAdmin())
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <!-- Estado -->
                                        <div>
                                            <label for="estado_id" class="block text-xs font-semibold text-athens-gray-700 dark:text-athens-gray-300 mb-1">
                                                Estado (UF) <span class="text-cinnabar-500">*</span>
                                            </label>
                                            <select name="estado_id" id="estado_id" required class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border @error('estado_id') border-cinnabar-500 ring-1 ring-cinnabar-500 @else border-athens-gray-300 dark:border-athens-gray-700 @enderror rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                                                <option value="">Selecione o estado...</option>
                                                @if(isset($estados) && $estados->isNotEmpty())
                                                    @foreach($estados as $est)
                                                        <option value="{{ $est->id }}" @selected(old('estado_id', $selectedEstadoId ?? '') == $est->id)>
                                                            {{ $est->nome }} ({{ $est->uf }})
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error('estado_id')
                                                <p class="text-xs text-cinnabar-600 dark:text-cinnabar-400 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <!-- Município -->
                                        <div>
                                            <label for="cidade_id" class="block text-xs font-semibold text-athens-gray-700 dark:text-athens-gray-300 mb-1">
                                                Município <span class="text-cinnabar-500">*</span>
                                            </label>
                                            <select name="cidade_id" id="cidade_id" required @disabled(!old('estado_id', $selectedEstadoId ?? '')) class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border @error('cidade_id') border-cinnabar-500 ring-1 ring-cinnabar-500 @else border-athens-gray-300 dark:border-athens-gray-700 @enderror rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition disabled:opacity-60 disabled:cursor-not-allowed">
                                                <option value="">{{ old('estado_id', $selectedEstadoId ?? '') ? 'Selecione o município...' : 'Selecione o estado primeiro...' }}</option>
                                                @if(isset($cidades) && $cidades->isNotEmpty())
                                                    @foreach($cidades as $c)
                                                        <option value="{{ $c->id }}" @selected(old('cidade_id', $usuario->cidade_id) == $c->id)>
                                                            {{ $c->nome }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error('cidade_id')
                                                <p class="text-xs text-cinnabar-600 dark:text-cinnabar-400 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                    <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mt-1.5">
                                        Selecione o estado para carregar os municípios correspondentes para a jurisdição do administrador.
                                    </p>
                                @else
                                    <input 
                                        type="text" 
                                        readonly 
                                        disabled
                                        value="{{ $cidade->nome ?? 'Cidade Não Definida' }} - {{ $cidade->estado->uf ?? '' }}" 
                                        class="w-full px-4 py-2.5 text-sm bg-athens-gray-100 dark:bg-athens-gray-800/60 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg text-athens-gray-700 dark:text-athens-gray-300 cursor-not-allowed"
                                    >
                                    <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mt-1">Vinculado à cidade de atuação.</p>
                                @endif
                            </div>

                            <!-- Nome -->
                            <div class="sm:col-span-2">
                                <label for="name" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">
                                    Nome Completo <span class="text-cinnabar-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name" 
                                    value="{{ old('name', $usuario->name) }}" 
                                    required
                                    placeholder="Ex: João da Silva"
                                    class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 placeholder:text-athens-gray-400 dark:placeholder:text-athens-gray-500 border @error('name') border-cinnabar-500 ring-1 ring-cinnabar-500 @else border-athens-gray-300 dark:border-athens-gray-700 @enderror rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                            </div>

                            <!-- Email -->
                            <div class="sm:col-span-2">
                                <label for="email" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">
                                    Endereço de Email <span class="text-cinnabar-500">*</span>
                                </label>
                                @if($usuario->id === $currentUser->id)
                                    <div class="relative">
                                        <input 
                                            type="email" 
                                            id="email" 
                                            value="{{ $usuario->email }}" 
                                            disabled
                                            class="w-full px-4 py-2.5 text-sm bg-athens-gray-100 dark:bg-athens-gray-800/60 text-athens-gray-600 dark:text-athens-gray-400 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg cursor-not-allowed select-none pr-10"
                                        >
                                        <div class="absolute right-3 top-1/2 -translate-y-1/2 text-athens-gray-400 dark:text-athens-gray-500 flex items-center gap-1 text-xs">
                                            <x-heroicon-o-lock-closed class="w-4 h-4" />
                                            <span>Inalterável</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mt-1.5">
                                        Por diretriz de integridade e segurança, nenhum usuário pode trocar o próprio e-mail cadastrado.
                                    </p>
                                @else
                                    <input 
                                        type="email" 
                                        id="email" 
                                        name="email" 
                                        value="{{ old('email', $usuario->email) }}" 
                                        required
                                        placeholder="usuario@prefeitura.gov.br"
                                        class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 placeholder:text-athens-gray-400 dark:placeholder:text-athens-gray-500 border @error('email') border-cinnabar-500 ring-1 ring-cinnabar-500 @else border-athens-gray-300 dark:border-athens-gray-700 @enderror rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                    >
                                    <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mt-1">Como administrador, você pode gerenciar o e-mail de acesso deste servidor.</p>
                                @endif
                            </div>

                            <!-- Senha -->
                            <div>
                                <label for="password" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">
                                    Nova Senha <span class="text-xs font-normal text-athens-gray-500">(opcional)</span>
                                </label>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    minlength="8"
                                    placeholder="Deixe em branco para manter a atual"
                                    class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 placeholder:text-athens-gray-400 dark:placeholder:text-athens-gray-500 border @error('password') border-cinnabar-500 ring-1 ring-cinnabar-500 @else border-athens-gray-300 dark:border-athens-gray-700 @enderror rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                            </div>

                            <!-- Confirmar Senha -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">
                                    Confirmar Nova Senha
                                </label>
                                <input 
                                    type="password" 
                                    id="password_confirmation" 
                                    name="password_confirmation" 
                                    minlength="8"
                                    placeholder="Repita a nova senha"
                                    class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 placeholder:text-athens-gray-400 dark:placeholder:text-athens-gray-500 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                            </div>
                        </div>

                        <div class="pt-4">
                            <h2 class="text-lg font-bold text-blue-dianne-950 dark:text-white border-b border-athens-gray-100 dark:border-athens-gray-800 pb-2">Endereço (Opcional)</h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="cep" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white">CEP</label>
                                    <span id="cep-status" class="text-xs font-medium text-athens-gray-500 dark:text-athens-gray-400 hidden"></span>
                                </div>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        id="cep" 
                                        name="cep" 
                                        value="{{ old('cep', $usuario->cep) }}" 
                                        placeholder="00000-000" 
                                        maxlength="9" 
                                        class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 placeholder:text-athens-gray-400 dark:placeholder:text-athens-gray-500 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition pr-10"
                                    >
                                    <div id="cep-loading" class="absolute right-3 top-1/2 -translate-y-1/2 hidden pointer-events-none">
                                        <svg class="animate-spin h-4 w-4 text-blue-dianne-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="logradouro" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">Logradouro</label>
                                <input type="text" id="logradouro" name="logradouro" value="{{ old('logradouro', $usuario->logradouro) }}" class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>

                            <div>
                                <label for="numero" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">Número</label>
                                <input type="text" id="numero" name="numero" value="{{ old('numero', $usuario->numero) }}" class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>

                            <div>
                                <label for="complemento" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">Complemento</label>
                                <input type="text" id="complemento" name="complemento" value="{{ old('complemento', $usuario->complemento) }}" class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>

                            <div>
                                <label for="bairro" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">Bairro</label>
                                <input type="text" id="bairro" name="bairro" value="{{ old('bairro', $usuario->bairro) }}" class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>
                        </div>

                        <!-- Botões -->
                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-athens-gray-100 dark:border-athens-gray-800">
                            <a href="{{ route('usuarios.index') }}" class="px-5 py-2.5 text-sm font-semibold text-athens-gray-600 dark:text-athens-gray-400 hover:text-blue-dianne-950 dark:hover:text-white hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 rounded-lg transition">
                                Cancelar
                            </a>
                            <button type="submit" id="btn-submit" class="px-6 py-2.5 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold text-sm rounded-lg shadow-sm transition flex items-center gap-2">
                                <x-heroicon-o-check class="w-4 h-4" />
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </main>
    </div>

    <script>
        // Auto-preenchimento CEP
        const cepInput = document.getElementById('cep');
        const cepLoading = document.getElementById('cep-loading');
        const cepStatus = document.getElementById('cep-status');

        if (cepInput) {
            cepInput.addEventListener('input', function(e) {
                let v = e.target.value.replace(/\D/g, '');
                if (v.length > 5) {
                    v = v.substring(0, 5) + '-' + v.substring(5, 8);
                }
                e.target.value = v;

                const cleanCep = v.replace(/\D/g, '');
                if (cleanCep.length === 8) {
                    buscarCep(cleanCep);
                }
            });
        }

        async function buscarCep(cep) {
            if (!cepLoading || !cepStatus) return;
            cepLoading.classList.remove('hidden');
            cepStatus.classList.add('hidden');

            try {
                const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
                const data = await response.json();

                if (!data.erro) {
                    if (data.logradouro) document.getElementById('logradouro').value = data.logradouro;
                    if (data.bairro) document.getElementById('bairro').value = data.bairro;
                    document.getElementById('numero').focus();

                    cepStatus.textContent = 'CEP encontrado!';
                    cepStatus.className = 'text-xs font-medium text-emerald-600 dark:text-emerald-400';
                    cepStatus.classList.remove('hidden');
                } else {
                    cepStatus.textContent = 'CEP não encontrado';
                    cepStatus.className = 'text-xs font-medium text-cinnabar-500';
                    cepStatus.classList.remove('hidden');
                }
            } catch (err) {
                cepStatus.textContent = 'Erro ao consultar CEP';
                cepStatus.className = 'text-xs font-medium text-cinnabar-500';
                cepStatus.classList.remove('hidden');
            } finally {
                cepLoading.classList.add('hidden');
            }
        }

        @if(! $currentUser->isSuperAdmin())
        function selecionarNivel(nivel) {
            const radio = document.getElementById('radio-' + nivel);
            if (radio) {
                radio.checked = true;
            }

            const niveis = ['cadastrador', 'instalador'];
            
            niveis.forEach(n => {
                const card = document.getElementById('card-nivel-' + n);
                const check = document.getElementById('check-' + n);
                if (!card || !check) return;

                const checkIcon = check.querySelector('svg');

                if (n === nivel) {
                    if (n === 'cadastrador') {
                        card.className = 'nivel-card relative flex flex-col justify-between p-4 rounded-xl border-2 transition-all cursor-pointer select-none border-blue-dianne-600 dark:border-blue-dianne-400 bg-blue-dianne-50/70 dark:bg-blue-dianne-950/50 shadow-sm ring-2 ring-blue-dianne-500/20';
                        check.className = 'w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors bg-blue-dianne-600 border-blue-dianne-600';
                    } else if (n === 'instalador') {
                        card.className = 'nivel-card relative flex flex-col justify-between p-4 rounded-xl border-2 transition-all cursor-pointer select-none border-tahiti-gold-500 dark:border-tahiti-gold-400 bg-tahiti-gold-50/70 dark:bg-tahiti-gold-950/50 shadow-sm ring-2 ring-tahiti-gold-500/20';
                        check.className = 'w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors bg-tahiti-gold-500 border-tahiti-gold-500';
                    }
                    if (checkIcon) checkIcon.classList.remove('opacity-0');
                } else {
                    card.className = 'nivel-card relative flex flex-col justify-between p-4 rounded-xl border-2 transition-all cursor-pointer select-none border-athens-gray-200 dark:border-athens-gray-800 bg-white dark:bg-athens-gray-900 hover:border-athens-gray-300 dark:hover:border-athens-gray-700';
                    check.className = 'w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors border-athens-gray-300 dark:border-athens-gray-700 bg-transparent';
                    if (checkIcon) checkIcon.classList.add('opacity-0');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const radioChecked = document.querySelector('input[name="nivel"]:checked');
            const nivelInicial = radioChecked ? radioChecked.value : '{{ old('nivel', $usuario->nivel) }}';
            selecionarNivel(nivelInicial);
        });
        @endif

        @if($currentUser->isSuperAdmin())
        document.addEventListener('DOMContentLoaded', () => {
            const estadoSelect = document.getElementById('estado_id');
            const cidadeSelect = document.getElementById('cidade_id');

            if (!estadoSelect || !cidadeSelect) return;

            estadoSelect.addEventListener('change', function () {
                const estadoId = this.value;
                cidadeSelect.innerHTML = '';

                if (!estadoId) {
                    cidadeSelect.disabled = true;
                    cidadeSelect.innerHTML = '<option value="">Selecione o estado primeiro...</option>';
                    return;
                }

                cidadeSelect.disabled = true;
                cidadeSelect.innerHTML = '<option value="">Carregando municípios...</option>';

                fetch(`/localidades/estados/${estadoId}/cidades`)
                    .then(response => {
                        if (!response.ok) throw new Error('Falha na resposta da rede');
                        return response.json();
                    })
                    .then(cidades => {
                        cidadeSelect.innerHTML = '<option value="">Selecione o município...</option>';
                        if (Array.isArray(cidades) && cidades.length > 0) {
                            cidades.forEach(c => {
                                const opt = document.createElement('option');
                                opt.value = c.id;
                                opt.textContent = c.nome;
                                cidadeSelect.appendChild(opt);
                            });
                            cidadeSelect.disabled = false;
                        } else {
                            cidadeSelect.innerHTML = '<option value="">Nenhum município cadastrado</option>';
                            cidadeSelect.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao carregar cidades:', error);
                        cidadeSelect.innerHTML = '<option value="">Erro ao carregar municípios</option>';
                        cidadeSelect.disabled = false;
                    });
            });
        });
        @endif
    </script>
</x-layouts.app>
