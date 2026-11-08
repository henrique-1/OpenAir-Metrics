<x-layouts.app title="Meu Perfil - OpenAir Metrics">
    <div class="flex flex-col md:flex-row h-full w-full bg-athens-gray-50 dark:bg-athens-gray-950 transition-colors duration-200">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="perfil" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto min-h-0 p-4 sm:p-6 lg:p-8">
            <div class="max-w-4xl mx-auto space-y-6">

                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 text-sm text-athens-gray-500 dark:text-athens-gray-400">
                    <span class="hover:text-blue-dianne-600 transition">Configurações</span>
                    <x-heroicon-o-chevron-right class="w-4 h-4" />
                    <span class="text-blue-dianne-950 dark:text-white font-medium">Meu Perfil</span>
                </div>

                <!-- Alertas de Sucesso / Erro -->
                @if (session('success'))
                    <div class="bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 rounded-xl flex items-center gap-3">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
                        <p class="text-sm font-medium">{{ session('success') }}</p>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="bg-cinnabar-50 dark:bg-cinnabar-950/50 border border-cinnabar-200 dark:border-cinnabar-800 text-cinnabar-800 dark:text-cinnabar-200 px-4 py-3 rounded-xl space-y-1">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-cinnabar-600 dark:text-cinnabar-400 flex-shrink-0" />
                            <p class="text-sm font-bold">Corrija os erros abaixo:</p>
                        </div>
                        <ul class="list-disc list-inside text-xs space-y-0.5 ml-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Card de Informações de Nível de Acesso -->
                <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-6 transition-colors">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-athens-gray-100 dark:border-athens-gray-800">
                        <div class="flex items-center gap-4">
                            <div class="w-14 h-14 rounded-full bg-blue-dianne-100 dark:bg-blue-dianne-950 text-blue-dianne-700 dark:text-blue-dianne-300 flex items-center justify-center font-bold text-xl uppercase shadow-inner">
                                {{ substr($user->name, 0, 2) }}
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-blue-dianne-950 dark:text-white">{{ $user->name }}</h1>
                                <p class="text-sm text-athens-gray-500 dark:text-athens-gray-400">{{ $user->email }}</p>
                            </div>
                        </div>

                        <div class="flex flex-col sm:items-end">
                            <span class="text-xs font-semibold text-athens-gray-500 dark:text-athens-gray-400 uppercase tracking-wider mb-1">Nível no Sistema</span>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold 
                                @if($user->isSuperAdmin()) bg-purple-100 dark:bg-purple-950/70 text-purple-800 dark:text-purple-200 border border-purple-200 dark:border-purple-800
                                @elseif($user->isAdministrador()) bg-ebony-clay-100 dark:bg-ebony-clay-950/70 text-ebony-clay-800 dark:text-ebony-clay-200 border border-ebony-clay-200 dark:border-ebony-clay-800
                                @elseif($user->isInstalador()) bg-tahiti-gold-100 dark:bg-tahiti-gold-950/70 text-tahiti-gold-800 dark:text-tahiti-gold-200 border border-tahiti-gold-200 dark:border-tahiti-gold-800
                                @else bg-blue-dianne-100 dark:bg-blue-dianne-950/70 text-blue-dianne-800 dark:text-blue-dianne-200 border border-blue-dianne-200 dark:border-blue-dianne-800 @endif">
                                <span class="w-2 h-2 rounded-full 
                                    @if($user->isSuperAdmin()) bg-purple-600
                                    @elseif($user->isAdministrador()) bg-ebony-clay-600
                                    @elseif($user->isInstalador()) bg-tahiti-gold-600
                                    @else bg-blue-dianne-600 @endif"></span>
                                {{ $user->nivel_label }}
                            </span>
                            @if($user->cidade)
                                <span class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mt-1">
                                    Jurisdição: <strong class="text-blue-dianne-950 dark:text-white">{{ $user->cidade->nome }} ({{ $user->cidade->estado->uf ?? 'UF' }})</strong>
                                </span>
                            @elseif($user->isSuperAdmin())
                                <span class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mt-1">
                                    Acesso: <strong class="text-blue-dianne-950 dark:text-white">Global / Todas as Jurisdições</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Explicação das Funções -->
                    <div class="mt-6">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-athens-gray-500 dark:text-athens-gray-400 mb-3">Guia de Níveis e Permissões</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <!-- Administrador -->
                            <div class="p-3.5 rounded-lg border @if($user->isAdministrador()) border-ebony-clay-300 dark:border-ebony-clay-700 bg-ebony-clay-50/50 dark:bg-ebony-clay-950/50 @else border-athens-gray-200 dark:border-athens-gray-800 bg-athens-gray-50/50 dark:bg-athens-gray-800/50 @endif">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-heroicon-o-shield-check class="w-4 h-4 text-ebony-clay-600 dark:text-ebony-clay-400" />
                                    <span class="text-sm font-bold text-blue-dianne-950 dark:text-white">Administrador</span>
                                </div>
                                <p class="text-xs text-athens-gray-600 dark:text-athens-gray-400">
                                    Cadastra e gerencia os usuários sob jurisdição municipal, além de gerar relatórios e configurar alertas.
                                </p>
                            </div>

                            <!-- Planejador Técnico -->
                            <div class="p-3.5 rounded-lg border @if($user->isCadastrador()) border-blue-dianne-300 dark:border-blue-dianne-700 bg-blue-dianne-50/50 dark:bg-blue-dianne-950/50 @else border-athens-gray-200 dark:border-athens-gray-800 bg-athens-gray-50/50 dark:bg-athens-gray-800/50 @endif">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-heroicon-o-pencil-square class="w-4 h-4 text-blue-dianne-600 dark:text-blue-dianne-400" />
                                    <span class="text-sm font-bold text-blue-dianne-950 dark:text-white">Planejador Técnico</span>
                                </div>
                                <p class="text-xs text-athens-gray-600 dark:text-athens-gray-400">
                                    Cadastro e gestão de patrimônio físico (marca como descartado), solicita substituição de sensores e planeja malhas.
                                </p>
                            </div>

                            <!-- Instalador -->
                            <div class="p-3.5 rounded-lg border @if($user->isInstalador()) border-tahiti-gold-300 dark:border-tahiti-gold-700 bg-tahiti-gold-50/50 dark:bg-tahiti-gold-950/50 @else border-athens-gray-200 dark:border-athens-gray-800 bg-athens-gray-50/50 dark:bg-athens-gray-800/50 @endif">
                                <div class="flex items-center gap-2 mb-1">
                                    <x-heroicon-o-wrench-screwdriver class="w-4 h-4 text-tahiti-gold-600 dark:text-tahiti-gold-400" />
                                    <span class="text-sm font-bold text-blue-dianne-950 dark:text-white">Instalador</span>
                                </div>
                                <p class="text-xs text-athens-gray-600 dark:text-athens-gray-400">
                                    Acessa o roteiro de instalação, localiza pontos geográficos e vincula o MAC/Patrimônio à estação.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulário de Edição -->
                <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-6 transition-colors">
                    <form action="{{ route('perfil.update') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <h2 class="text-lg font-bold text-blue-dianne-950 dark:text-white border-b border-athens-gray-100 dark:border-athens-gray-800 pb-2">Dados Pessoais</h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Nome -->
                            <div class="sm:col-span-2">
                                <label for="name" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                    Nome Completo <span class="text-cinnabar-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="name" 
                                    name="name" 
                                    value="{{ old('name', $user->name) }}" 
                                    required
                                    class="w-full px-4 py-2.5 text-sm border @error('name') border-cinnabar-500 ring-1 ring-cinnabar-500 @else border-athens-gray-300 dark:border-athens-gray-700 @enderror bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                                @error('name')
                                    <p class="text-xs text-cinnabar-600 dark:text-cinnabar-400 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Email Institucional (Fixo - Não pode ser alterado) -->
                            <div class="sm:col-span-2">
                                <label for="email" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                    Email Institucional (Identificação do Usuário)
                                </label>
                                <div class="relative">
                                    <input 
                                        type="email" 
                                        id="email" 
                                        value="{{ $user->email }}" 
                                        disabled
                                        class="w-full px-4 py-2.5 text-sm border border-athens-gray-300 dark:border-athens-gray-700 bg-athens-gray-100 dark:bg-athens-gray-800/60 text-athens-gray-600 dark:text-athens-gray-400 rounded-lg cursor-not-allowed select-none"
                                    >
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2 text-athens-gray-400 dark:text-athens-gray-500 flex items-center gap-1 text-xs">
                                        <x-heroicon-o-lock-closed class="w-4 h-4" />
                                        <span>Fixo</span>
                                    </div>
                                </div>
                                <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mt-1.5">
                                    O e-mail é a chave de identificação do usuário no município e não pode ser alterado.
                                </p>
                            </div>
                        </div>

                        <div class="pt-4">
                            <h2 class="text-lg font-bold text-blue-dianne-950 dark:text-white border-b border-athens-gray-100 dark:border-athens-gray-800 pb-2">Endereço</h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                            <!-- CEP -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label for="cep" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200">
                                        CEP
                                    </label>
                                    <span id="cep-status" class="text-xs font-medium text-athens-gray-500 dark:text-athens-gray-400 hidden"></span>
                                </div>
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        id="cep" 
                                        name="cep" 
                                        value="{{ old('cep', $user->cep) }}" 
                                        placeholder="00000-000"
                                        maxlength="9"
                                        class="w-full px-4 py-2.5 text-sm border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition pr-10"
                                    >
                                    <div id="cep-loading" class="absolute right-3 top-1/2 -translate-y-1/2 hidden pointer-events-none">
                                        <svg class="animate-spin h-4 w-4 text-blue-dianne-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p id="cep-feedback" class="text-xs mt-1 hidden"></p>
                                @error('cep')
                                    <p class="text-xs text-cinnabar-600 dark:text-cinnabar-400 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Logradouro -->
                            <div class="sm:col-span-2">
                                <label for="logradouro" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                    Logradouro (Rua, Av.)
                                </label>
                                <input 
                                    type="text" 
                                    id="logradouro" 
                                    name="logradouro" 
                                    value="{{ old('logradouro', $user->logradouro) }}" 
                                    class="w-full px-4 py-2.5 text-sm border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                            </div>

                            <!-- Número -->
                            <div>
                                <label for="numero" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                    Número
                                </label>
                                <input 
                                    type="text" 
                                    id="numero" 
                                    name="numero" 
                                    value="{{ old('numero', $user->numero) }}" 
                                    class="w-full px-4 py-2.5 text-sm border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                            </div>

                            <!-- Complemento -->
                            <div>
                                <label for="complemento" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                    Complemento
                                </label>
                                <input 
                                    type="text" 
                                    id="complemento" 
                                    name="complemento" 
                                    value="{{ old('complemento', $user->complemento) }}" 
                                    placeholder="Apto, Sala, Bloco"
                                    class="w-full px-4 py-2.5 text-sm border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                            </div>

                            <!-- Bairro -->
                            <div>
                                <label for="bairro" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                    Bairro
                                </label>
                                <input 
                                    type="text" 
                                    id="bairro" 
                                    name="bairro" 
                                    value="{{ old('bairro', $user->bairro) }}" 
                                    class="w-full px-4 py-2.5 text-sm border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                            </div>

                            <!-- Cidade / Município -->
                            <div class="sm:col-span-2">
                                <label for="cidade_id" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                    Cidade / Município
                                </label>
                                @if($user->cidade_id)
                                    <input 
                                        type="text" 
                                        readonly 
                                        disabled
                                        value="{{ $user->cidade->nome ?? '' }} - {{ $user->cidade->estado->uf ?? '' }}" 
                                        class="w-full px-4 py-2.5 text-sm bg-athens-gray-100 dark:bg-athens-gray-800/60 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg text-athens-gray-700 dark:text-athens-gray-400 cursor-not-allowed"
                                    >
                                    <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mt-1">A jurisdição municipal é vinculada e gerenciada pela administração.</p>
                                @else
                                    <select 
                                        id="cidade_id" 
                                        name="cidade_id" 
                                        class="w-full px-4 py-2.5 text-sm border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                    >
                                        <option value="">Selecione uma cidade...</option>
                                        @foreach($cidades as $cid)
                                            <option value="{{ $cid->id }}" @selected(old('cidade_id', $user->cidade_id) == $cid->id)>
                                                {{ $cid->nome }} ({{ $cid->estado->uf ?? 'UF' }})
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <!-- Estado UF -->
                            <div>
                                <label for="estado" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                    UF (Estado)
                                </label>
                                <input 
                                    type="text" 
                                    id="estado" 
                                    name="estado" 
                                    value="{{ old('estado', $user->estado ?? $user->cidade?->estado?->uf) }}" 
                                    maxlength="2"
                                    placeholder="Ex: SP"
                                    class="w-full uppercase px-4 py-2.5 text-sm border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                            </div>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-athens-gray-100 dark:border-athens-gray-800">
                            <a href="{{ route('dashboard') }}" class="px-5 py-2.5 text-sm font-semibold text-athens-gray-600 dark:text-athens-gray-400 hover:text-blue-dianne-950 dark:hover:text-white hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 rounded-lg transition">
                                Voltar
                            </a>
                            <button type="submit" class="px-6 py-2.5 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold text-sm rounded-lg shadow-sm transition flex items-center gap-2 cursor-pointer">
                                <x-heroicon-o-check class="w-4 h-4" />
                                Salvar Alterações
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Card: Autenticação em Duas Etapas (TOTP via Google Authenticator / Authy) -->
                <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-6 transition-colors">
                    @if ($user->podeUsarPasskey() && ! $user->hasEnabledTwoFactorAuthentication() && $user->passkeys->isEmpty())
                        <div class="mb-5 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-sm text-amber-900 dark:text-amber-200 flex items-start gap-3">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                            <div>
                                <p class="font-bold">Recomendação de Segurança (NIST SP 800-63B)</p>
                                <p class="text-xs text-amber-800 dark:text-amber-300 mt-0.5">
                                    Por possuir perfil com privilégios de gestão municipal ou intervenção técnica (<strong>{{ $user->nivel_label }}</strong>), recomendamos fortemente a ativação do <strong>2FA via Authenticator</strong> ou o registro de uma <strong>Passkey</strong> biométrica.
                                </p>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-between pb-4 border-b border-athens-gray-100 dark:border-athens-gray-800">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 rounded-lg bg-blue-dianne-50 dark:bg-blue-dianne-950/60 text-blue-dianne-600 dark:text-blue-dianne-400">
                                <x-heroicon-o-device-phone-mobile class="w-6 h-6" />
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-blue-dianne-950 dark:text-white">Autenticação em Duas Etapas (TOTP)</h2>
                                <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400">Proteja sua conta exigindo um código de 6 dígitos gerado no Google Authenticator ou Authy a cada login.</p>
                            </div>
                        </div>

                        <div>
                            @if ($user->hasEnabledTwoFactorAuthentication())
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Ativo
                                </span>
                            @elseif ($user->two_factor_secret)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-tahiti-gold-100 dark:bg-tahiti-gold-950/70 text-tahiti-gold-800 dark:text-tahiti-gold-200 border border-tahiti-gold-200 dark:border-tahiti-gold-800">
                                    Aguardando Confirmação
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-athens-gray-100 dark:bg-athens-gray-800 text-athens-gray-600 dark:text-athens-gray-400 border border-athens-gray-200 dark:border-athens-gray-700">
                                    Desativado
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4">
                        @if (! $user->two_factor_secret)
                            {{-- 2FA Desativado --}}
                            <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mb-4">
                                Ao habilitar o 2FA, você precisará informar um código temporário gerado pelo seu smartphone sempre que acessar sua conta no OpenAir Metrics.
                            </p>
                            <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                                @csrf
                                <button type="submit" class="px-5 py-2.5 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold text-sm rounded-lg shadow-sm transition flex items-center gap-2 cursor-pointer">
                                    <x-heroicon-o-shield-check class="w-4 h-4" />
                                    Habilitar Autenticação em Duas Etapas
                                </button>
                            </form>
                        @elseif (! $user->two_factor_confirmed_at)
                            {{-- 2FA Habilitado mas aguardando confirmação do código --}}
                            <div class="space-y-4">
                                <div class="p-4 rounded-xl bg-blue-dianne-50 dark:bg-blue-dianne-950/40 border border-blue-dianne-200 dark:border-blue-dianne-800 text-sm text-blue-dianne-900 dark:text-blue-dianne-200">
                                    <p class="font-semibold mb-1">Escaneie o QR Code no seu aplicativo autenticador</p>
                                    <p class="text-xs text-blue-dianne-800 dark:text-blue-dianne-300">Abra o Google Authenticator ou Authy, selecione a opção de ler código QR e aponte a câmera para a imagem abaixo:</p>
                                </div>

                                <div class="flex flex-col sm:flex-row items-center gap-6 p-4 bg-white dark:bg-athens-gray-950 rounded-xl border border-athens-gray-200 dark:border-athens-gray-800">
                                    <div class="p-3 bg-white rounded-lg shadow-sm">
                                        {!! $user->twoFactorQrCodeSvg() !!}
                                    </div>
                                    <div class="space-y-2 text-xs text-athens-gray-600 dark:text-athens-gray-400">
                                        <p class="font-medium text-athens-gray-800 dark:text-athens-gray-200">Não consegue ler o QR Code?</p>
                                        <p>Insira a chave de configuração manual no seu app:</p>
                                        <code class="px-2 py-1 bg-athens-gray-100 dark:bg-athens-gray-800 rounded font-mono text-blue-dianne-600 dark:text-blue-dianne-400 select-all block text-xs break-all">
                                            {{ decrypt($user->two_factor_secret) }}
                                        </code>
                                    </div>
                                </div>

                                <form method="POST" action="{{ url('/user/confirmed-two-factor-authentication') }}" class="flex flex-col sm:flex-row items-end gap-3 pt-2">
                                    @csrf
                                    <div class="w-full sm:w-64">
                                        <label for="code" class="block text-xs font-semibold text-athens-gray-700 dark:text-athens-gray-300 mb-1">
                                            Código de 6 dígitos gerado no app <span class="text-cinnabar-500">*</span>
                                        </label>
                                        <input type="text" id="code" name="code" inputmode="numeric" autofocus placeholder="000 000" maxlength="7" required
                                            class="w-full px-4 py-2 text-center font-mono tracking-widest text-base border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 focus:ring-2 focus:ring-blue-dianne-500 outline-none">
                                    </div>
                                    <button type="submit" class="w-full sm:w-auto px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm rounded-lg transition flex items-center justify-center gap-2 cursor-pointer">
                                        <x-heroicon-o-check class="w-4 h-4" />
                                        Confirmar e Ativar 2FA
                                    </button>
                                </form>

                                <form method="POST" action="{{ url('/user/two-factor-authentication') }}" class="pt-1">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-cinnabar-600 dark:text-cinnabar-400 hover:underline">
                                        Cancelar configuração
                                    </button>
                                </form>
                            </div>
                        @else
                            {{-- 2FA Ativo e Confirmado --}}
                            <div class="space-y-4">
                                <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400">
                                    Sua conta está protegida com autenticação em duas etapas. Se você perder o acesso ao seu dispositivo autenticador, poderá utilizar um dos seus códigos de recuperação abaixo.
                                </p>

                                <div class="p-4 rounded-xl bg-athens-gray-50 dark:bg-athens-gray-950 border border-athens-gray-200 dark:border-athens-gray-800 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-blue-dianne-950 dark:text-white uppercase tracking-wider">Códigos de Recuperação (Backup)</span>
                                        <form method="POST" action="{{ url('/user/two-factor-recovery-codes') }}">
                                            @csrf
                                            <button type="submit" class="text-xs text-blue-dianne-600 dark:text-blue-dianne-400 hover:underline flex items-center gap-1 cursor-pointer">
                                                <x-heroicon-o-arrow-path class="w-3.5 h-3.5" />
                                                Gerar novos códigos
                                            </button>
                                        </form>
                                    </div>
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2">
                                        @foreach ($user->recoveryCodes() as $recoveryCode)
                                            <code class="px-2.5 py-1.5 text-center text-xs font-mono bg-white dark:bg-athens-gray-900 border border-athens-gray-200 dark:border-athens-gray-800 rounded text-athens-gray-800 dark:text-athens-gray-200 select-all">
                                                {{ $recoveryCode }}
                                            </code>
                                        @endforeach
                                    </div>
                                    <p class="text-[11px] text-athens-gray-500 dark:text-athens-gray-400 mt-1">Guarde esses códigos em um local seguro. Cada código só pode ser usado uma única vez.</p>
                                </div>

                                <form method="POST" action="{{ url('/user/two-factor-authentication') }}" class="pt-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Tem certeza que deseja desativar a autenticação em duas etapas? Sua conta ficará menos protegida.');"
                                        class="px-4 py-2 border border-cinnabar-300 dark:border-cinnabar-700 text-cinnabar-600 dark:text-cinnabar-400 hover:bg-cinnabar-50 dark:hover:bg-cinnabar-950/40 text-xs font-semibold rounded-lg transition flex items-center gap-1.5 cursor-pointer">
                                        <x-heroicon-o-lock-open class="w-4 h-4" />
                                        Desativar 2FA
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Card: Passkeys e Biometria (Exclusivo para Administradores e Instaladores) --}}
                @if ($user->podeUsarPasskey())
                    <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-6 transition-colors">
                        <div class="flex items-center justify-between pb-4 border-b border-athens-gray-100 dark:border-athens-gray-800">
                            <div class="flex items-center gap-3">
                                <div class="p-2.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                                    <x-heroicon-o-finger-print class="w-6 h-6" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-lg font-bold text-blue-dianne-950 dark:text-white">Passkeys e Biometria de Hardware</h2>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-dianne-100 dark:bg-blue-dianne-950 text-blue-dianne-800 dark:text-blue-dianne-200 border border-blue-dianne-200 dark:border-blue-dianne-800">
                                            NIST AAL3
                                        </span>
                                    </div>
                                    <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400">Disponível para {{ $user->nivel_label }}: acesse o sistema instantaneamente com Touch ID, Face ID, Windows Hello ou chave física FIDO2 (YubiKey).</p>
                                </div>
                            </div>

                            <button type="button" id="btn-cadastrar-passkey"
                                class="px-4 py-2 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold text-xs rounded-lg shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                                <x-heroicon-o-plus class="w-4 h-4" />
                                Adicionar Passkey
                            </button>
                        </div>

                        <div id="passkey-feedback" class="hidden mt-3 p-3 rounded-lg text-xs"></div>

                        <div class="mt-4">
                            @if ($user->passkeys->isEmpty())
                                <p class="text-sm text-athens-gray-500 dark:text-athens-gray-400 text-center py-6">
                                    Nenhuma Passkey cadastrada ainda. Clique em "Adicionar Passkey" para registrar a biometria deste dispositivo.
                                </p>
                            @else
                                <div class="divide-y divide-athens-gray-100 dark:divide-athens-gray-800">
                                    @foreach ($user->passkeys as $passkey)
                                        <div class="py-3 flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="p-2 rounded-lg bg-athens-gray-100 dark:bg-athens-gray-800 text-athens-gray-600 dark:text-athens-gray-400">
                                                    <x-heroicon-o-key class="w-5 h-5" />
                                                </div>
                                                <div>
                                                    <p class="text-sm font-semibold text-blue-dianne-950 dark:text-white">{{ $passkey->name }}</p>
                                                    <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400">
                                                        Criada em {{ $passkey->created_at?->format('d/m/Y H:i') }}
                                                        @if ($passkey->last_used_at)
                                                            • Último uso: {{ $passkey->last_used_at->format('d/m/Y H:i') }}
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>

                                            <form method="POST" action="{{ route('passkey.destroy', $passkey) }}" onsubmit="return confirm('Tem certeza que deseja remover esta Passkey?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-cinnabar-600 dark:text-cinnabar-400 hover:text-cinnabar-800 p-1.5 hover:bg-cinnabar-50 dark:hover:bg-cinnabar-950/40 rounded transition cursor-pointer" title="Excluir Passkey">
                                                    <x-heroicon-o-trash class="w-4 h-4" />
                                                </button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </main>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const cepInput = document.getElementById('cep');
                const logradouroInput = document.getElementById('logradouro');
                const numeroInput = document.getElementById('numero');
                const complementoInput = document.getElementById('complemento');
                const bairroInput = document.getElementById('bairro');
                const estadoInput = document.getElementById('estado');
                const cidadeSelect = document.getElementById('cidade_id');
                const cepLoading = document.getElementById('cep-loading');
                const cepFeedback = document.getElementById('cep-feedback');

                let ultimoCepBuscado = '';

                function formatarCep(valor) {
                    const limpo = valor.replace(/\D/g, '').slice(0, 8);
                    if (limpo.length > 5) {
                        return limpo.slice(0, 5) + '-' + limpo.slice(5);
                    }
                    return limpo;
                }

                function mostrarFeedback(mensagem, tipo = 'info') {
                    if (!cepFeedback) return;
                    cepFeedback.classList.remove('hidden', 'text-cinnabar-600', 'text-emerald-600', 'text-athens-gray-500');
                    if (tipo === 'erro') {
                        cepFeedback.classList.add('text-cinnabar-600');
                    } else if (tipo === 'sucesso') {
                        cepFeedback.classList.add('text-emerald-600');
                    } else {
                        cepFeedback.classList.add('text-athens-gray-500');
                    }
                    cepFeedback.textContent = mensagem;
                }

                function limparFeedback() {
                    if (!cepFeedback) return;
                    cepFeedback.textContent = '';
                    cepFeedback.classList.add('hidden');
                }

                async function consultarViaCep(cep) {
                    const cepLimpo = cep.replace(/\D/g, '');
                    if (cepLimpo.length !== 8) {
                        return;
                    }

                    if (cepLimpo === ultimoCepBuscado) {
                        return;
                    }

                    ultimoCepBuscado = cepLimpo;
                    limparFeedback();
                    if (cepLoading) cepLoading.classList.remove('hidden');

                    try {
                        const response = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
                        if (!response.ok) {
                            throw new Error('Falha na requisição ao ViaCEP');
                        }

                        const data = await response.json();

                        if (data.erro === true || data.erro === 'true') {
                            mostrarFeedback('CEP não encontrado.', 'erro');
                            return;
                        }

                        // Preenchimento automático dos dados do logradouro e bairro
                        if (logradouroInput && data.logradouro) {
                            logradouroInput.value = data.logradouro;
                        }
                        if (bairroInput && data.bairro) {
                            bairroInput.value = data.bairro;
                        }
                        if (complementoInput && data.complemento && !complementoInput.value) {
                            complementoInput.value = data.complemento;
                        }
                        if (estadoInput && data.uf) {
                            estadoInput.value = data.uf.toUpperCase();
                        }

                        // Se o usuário não possuir cidade fixa e o select de cidade existir
                        if (cidadeSelect && data.localidade) {
                            const locNormalizada = data.localidade.trim().toLowerCase();
                            const ufNormalizada = (data.uf || '').trim().toLowerCase();
                            for (let i = 0; i < cidadeSelect.options.length; i++) {
                                const opt = cidadeSelect.options[i];
                                const optText = opt.textContent.toLowerCase();
                                if (optText.includes(locNormalizada) && (!ufNormalizada || optText.includes(ufNormalizada))) {
                                    cidadeSelect.selectedIndex = i;
                                    break;
                                }
                            }
                        }

                        mostrarFeedback(`${data.localidade || ''} - ${data.uf || ''}`, 'sucesso');

                        // Posiciona o foco no número para continuar o preenchimento
                        if (numeroInput) {
                            numeroInput.focus();
                        }

                    } catch (error) {
                        console.error('Erro ao consultar ViaCEP:', error);
                        mostrarFeedback('Não foi possível autocompletar pelo CEP. Preencha os campos manualmente.', 'info');
                    } finally {
                        if (cepLoading) cepLoading.classList.add('hidden');
                    }
                }

                if (cepInput) {
                    cepInput.addEventListener('input', function () {
                        this.value = formatarCep(this.value);
                        const digitos = this.value.replace(/\D/g, '');
                        if (digitos.length === 8) {
                            consultarViaCep(digitos);
                        } else if (digitos.length < 8) {
                            limparFeedback();
                        }
                    });

                    cepInput.addEventListener('blur', function () {
                        const digitos = this.value.replace(/\D/g, '');
                        if (digitos.length === 8) {
                            consultarViaCep(digitos);
                        }
                    });
                }

                @if ($user->podeUsarPasskey())
                const btnCadastrarPasskey = document.getElementById('btn-cadastrar-passkey');
                const passkeyFeedback = document.getElementById('passkey-feedback');

                function exibirPasskeyFeedback(mensagem, tipo = 'info') {
                    if (! passkeyFeedback) return;
                    passkeyFeedback.classList.remove('hidden', 'bg-cinnabar-50', 'text-cinnabar-700', 'border-cinnabar-200', 'dark:bg-cinnabar-950/40', 'dark:text-cinnabar-300', 'bg-emerald-50', 'text-emerald-700', 'border-emerald-200', 'dark:bg-emerald-950/40', 'dark:text-emerald-300', 'border');
                    if (tipo === 'erro') {
                        passkeyFeedback.classList.add('bg-cinnabar-50', 'text-cinnabar-700', 'border', 'border-cinnabar-200', 'dark:bg-cinnabar-950/40', 'dark:text-cinnabar-300');
                    } else if (tipo === 'sucesso') {
                        passkeyFeedback.classList.add('bg-emerald-50', 'text-emerald-700', 'border', 'border-emerald-200', 'dark:bg-emerald-950/40', 'dark:text-emerald-300');
                    } else {
                        passkeyFeedback.classList.add('bg-athens-gray-100', 'text-athens-gray-700', 'dark:bg-athens-gray-800', 'dark:text-athens-gray-200');
                    }
                    passkeyFeedback.textContent = mensagem;
                }

                function base64UrlToBuffer(base64url) {
                    const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
                    const padded = base64.padEnd(base64.length + (4 - base64.length % 4) % 4, '=');
                    const binary = atob(padded);
                    const bytes = new Uint8Array(binary.length);
                    for (let i = 0; i < binary.length; i++) {
                        bytes[i] = binary.charCodeAt(i);
                    }
                    return bytes.buffer;
                }

                function bufferToBase64Url(buffer) {
                    const bytes = new Uint8Array(buffer);
                    let binary = '';
                    for (let i = 0; i < bytes.byteLength; i++) {
                        binary += String.fromCharCode(bytes[i]);
                    }
                    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
                }

                if (btnCadastrarPasskey) {
                    btnCadastrarPasskey.addEventListener('click', async function () {
                        if (! window.PublicKeyCredential) {
                            exibirPasskeyFeedback('Seu navegador não oferece suporte a autenticação por Passkey / WebAuthn.', 'erro');
                            return;
                        }

                        const nomeChave = prompt('Identificação da Passkey (ex: MacBook Touch ID, Windows Hello, YubiKey):', 'Meu Dispositivo');
                        if (! nomeChave || ! nomeChave.trim()) {
                            return;
                        }

                        btnCadastrarPasskey.disabled = true;
                        exibirPasskeyFeedback('Aguardando autorização no seu dispositivo...', 'info');

                        try {
                            const optRes = await fetch('{{ route('passkey.registration-options') }}', {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            });

                            if (! optRes.ok) {
                                throw new Error('Não foi possível obter opções de registro.');
                            }

                            const optData = await optRes.json();
                            const creationOptions = optData.options ?? optData.publicKey ?? optData;

                            if (! creationOptions || ! creationOptions.challenge || ! creationOptions.user) {
                                throw new Error('Opções de registro de Passkey inválidas retornadas pelo servidor.');
                            }

                            if (! Array.isArray(creationOptions.pubKeyCredParams) || creationOptions.pubKeyCredParams.length === 0) {
                                creationOptions.pubKeyCredParams = [
                                    { type: 'public-key', alg: -7 },
                                    { type: 'public-key', alg: -257 }
                                ];
                            }

                            creationOptions.challenge = base64UrlToBuffer(creationOptions.challenge);
                            creationOptions.user.id = base64UrlToBuffer(creationOptions.user.id);
                            if (creationOptions.excludeCredentials) {
                                creationOptions.excludeCredentials = creationOptions.excludeCredentials.map(cred => ({
                                    ...cred,
                                    id: base64UrlToBuffer(cred.id)
                                }));
                            }

                            const credential = await navigator.credentials.create({ publicKey: creationOptions });
                            if (! credential) {
                                throw new Error('Criação da credencial cancelada.');
                            }

                            const credentialPayload = credential.toJSON ? credential.toJSON() : {
                                id: credential.id,
                                rawId: bufferToBase64Url(credential.rawId),
                                type: credential.type,
                                response: {
                                    clientDataJSON: bufferToBase64Url(credential.response.clientDataJSON),
                                    attestationObject: bufferToBase64Url(credential.response.attestationObject),
                                    transports: credential.response.getTransports ? credential.response.getTransports() : []
                                }
                            };

                            const saveRes = await fetch('{{ route('passkey.store') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    name: nomeChave.trim(),
                                    credential: credentialPayload
                                })
                            });

                            const saveData = await saveRes.json();
                            if (! saveRes.ok) {
                                throw new Error(saveData.message || 'Falha ao salvar Passkey.');
                            }

                            exibirPasskeyFeedback('Passkey cadastrada com sucesso! Recarregando página...', 'sucesso');
                            setTimeout(() => window.location.reload(), 1000);

                        } catch (err) {
                            console.error('Erro ao cadastrar Passkey:', err);
                            exibirPasskeyFeedback(
                                err.name === 'NotAllowedError'
                                    ? 'Operação cancelada ou expirada pelo usuário.'
                                    : (err.message || 'Erro inesperado ao registrar Passkey.'),
                                'erro'
                            );
                        } finally {
                            btnCadastrarPasskey.disabled = false;
                        }
                    });
                }
                @endif
            });
        </script>
    @endpush
</x-layouts.app>
