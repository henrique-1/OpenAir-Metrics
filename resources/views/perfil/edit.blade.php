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
            });
        </script>
    @endpush
</x-layouts.app>
