<x-layouts.app title="Novo Usuário Municipal - OpenAir Metrics">
    <div class="flex flex-col md:flex-row h-full w-full bg-athens-gray-50 dark:bg-athens-gray-950 transition-colors duration-200">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="usuarios" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto min-h-0 p-4 sm:p-6 lg:p-8">
            <div class="max-w-4xl mx-auto space-y-6">

                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 text-sm text-athens-gray-500 dark:text-athens-gray-400">
                    <a href="{{ route('usuarios.index') }}" class="hover:text-blue-dianne-600 dark:hover:text-blue-dianne-400 transition">Usuários Municipais</a>
                    <x-heroicon-o-chevron-right class="w-4 h-4" />
                    <span class="text-blue-dianne-950 dark:text-white font-medium">Novo Cadastro</span>
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
                <div>
                    <h1 class="text-2xl font-bold text-blue-dianne-950 dark:text-white tracking-tight">Cadastrar Novo Usuário</h1>
                    <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mt-1">
                        Adicione um membro à equipe de monitoramento de 
                        <strong class="text-blue-dianne-950 dark:text-white">{{ $cidade->nome ?? 'Sua Cidade' }} ({{ $cidade->estado->uf ?? '' }})</strong>.
                    </p>
                </div>

                <!-- Formulário -->
                <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-6">
                    <form action="{{ route('usuarios.store') }}" method="POST" class="space-y-6" id="form-novo-usuario">
                        @csrf

                        <!-- Alerta Dinâmico para Novo Administrador -->
                        <div id="alerta-transicao-adm" class="hidden bg-tahiti-gold-50 dark:bg-tahiti-gold-950/40 border border-tahiti-gold-300 dark:border-tahiti-gold-800 rounded-xl p-4 text-tahiti-gold-900 dark:text-tahiti-gold-200">
                            <div class="flex items-start gap-3">
                                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-tahiti-gold-600 dark:text-tahiti-gold-400 flex-shrink-0 mt-0.5" />
                                <div>
                                    <h4 class="font-bold text-sm text-tahiti-gold-950 dark:text-tahiti-gold-100">Aviso Crítico: Transição de Titularidade Municipal</h4>
                                    <p class="text-xs text-tahiti-gold-800 dark:text-tahiti-gold-300 mt-1 leading-relaxed">
                                        Ao cadastrar outro <strong>Administrador</strong> para esta mesma cidade, seu próprio acesso ao sistema será <strong>imediatamente desabilitado</strong> e você será desconectado. Esta regra de segurança garante a sucessão do cargo na administração municipal.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-blue-dianne-950 dark:text-white border-b border-athens-gray-100 dark:border-athens-gray-800 pb-2">Função e Acesso</h2>
                        </div>

                        <!-- Seleção de Nível e Permissões (Estilo Guia de Níveis e Permissões) -->
                        <div class="space-y-3">
                            <label class="block text-sm font-semibold text-blue-dianne-950 dark:text-white">
                                Selecione o Nível de Acesso e Permissões <span class="text-cinnabar-500">*</span>
                            </label>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4" id="grupo-niveis">
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
                                        @checked(old('nivel', 'cadastrador') === 'cadastrador')
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
                                            Cadastra estações, patrimônios físicos, planeja malhas e rotas de instalação em campo.
                                        </p>
                                    </div>
                                    <div class="mt-3 pt-2.5 border-t border-athens-gray-200/60 dark:border-athens-gray-800/80 flex items-center gap-1.5 text-[11px] font-medium text-blue-dianne-600 dark:text-blue-dianne-400">
                                        <x-heroicon-o-check-circle class="w-3.5 h-3.5" />
                                        <span>Gestão Técnica & Malhas</span>
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
                                        @checked(old('nivel') === 'instalador')
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

                                <!-- Opção: Administrador Municipal -->
                                <div 
                                    id="card-nivel-administrador"
                                    onclick="selecionarNivel('administrador')"
                                    class="nivel-card relative flex flex-col justify-between p-4 rounded-xl border-2 transition-all cursor-pointer select-none"
                                >
                                    <input 
                                        type="radio" 
                                        name="nivel" 
                                        id="radio-administrador"
                                        value="administrador" 
                                        class="sr-only" 
                                        @checked(old('nivel') === 'administrador')
                                    >
                                    <div>
                                        <div class="flex items-center justify-between gap-2 mb-2">
                                            <div class="flex items-center gap-2">
                                                <div class="p-1.5 rounded-lg bg-ebony-clay-100 dark:bg-ebony-clay-800 text-ebony-clay-700 dark:text-ebony-clay-200">
                                                    <x-heroicon-o-shield-check class="w-5 h-5" />
                                                </div>
                                                <span class="text-sm font-bold text-blue-dianne-950 dark:text-white">Administrador</span>
                                            </div>
                                            <div id="check-administrador" class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors">
                                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        <p class="text-xs text-athens-gray-600 dark:text-athens-gray-400 leading-relaxed">
                                            Cadastra e gerencia novos usuários na prefeitura, além de solicitar substituição de sensores da cidade.
                                        </p>
                                    </div>
                                    <div class="mt-3 pt-2.5 border-t border-athens-gray-200/60 dark:border-athens-gray-800/80 flex items-center gap-1.5 text-[11px] font-medium text-cinnabar-600 dark:text-cinnabar-400">
                                        <x-heroicon-o-exclamation-triangle class="w-3.5 h-3.5" />
                                        <span>Sucessão / Titularidade Única</span>
                                    </div>
                                </div>
                            </div>
                            @error('nivel')
                                <p class="text-xs text-cinnabar-600 dark:text-cinnabar-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
                            <!-- Cidade Jurisdição (Fixa) -->
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">
                                    Jurisdição Municipal
                                </label>
                                <input 
                                    type="text" 
                                    readonly 
                                    disabled
                                    value="{{ $cidade->nome ?? 'Cidade Não Definida' }} - {{ $cidade->estado->uf ?? '' }}" 
                                    class="w-full px-4 py-2.5 text-sm bg-athens-gray-100 dark:bg-athens-gray-800/60 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg text-athens-gray-700 dark:text-athens-gray-300 cursor-not-allowed"
                                >
                                <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mt-1">Vinculado automaticamente à sua cidade de atuação.</p>
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
                                    value="{{ old('name') }}" 
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
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    value="{{ old('email') }}" 
                                    required
                                    placeholder="usuario@prefeitura.gov.br"
                                    class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 placeholder:text-athens-gray-400 dark:placeholder:text-athens-gray-500 border @error('email') border-cinnabar-500 ring-1 ring-cinnabar-500 @else border-athens-gray-300 dark:border-athens-gray-700 @enderror rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                            </div>

                            <!-- Senha -->
                            <div>
                                <label for="password" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">
                                    Senha Inicial <span class="text-cinnabar-500">*</span>
                                </label>
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    required
                                    minlength="8"
                                    placeholder="Mínimo 8 caracteres"
                                    class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 placeholder:text-athens-gray-400 dark:placeholder:text-athens-gray-500 border @error('password') border-cinnabar-500 ring-1 ring-cinnabar-500 @else border-athens-gray-300 dark:border-athens-gray-700 @enderror rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                            </div>

                            <!-- Confirmar Senha -->
                            <div>
                                <label for="password_confirmation" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">
                                    Confirmar Senha <span class="text-cinnabar-500">*</span>
                                </label>
                                <input 
                                    type="password" 
                                    id="password_confirmation" 
                                    name="password_confirmation" 
                                    required
                                    minlength="8"
                                    placeholder="Repita a senha"
                                    class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 placeholder:text-athens-gray-400 dark:placeholder:text-athens-gray-500 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                            </div>
                        </div>

                        <div class="pt-4">
                            <h2 class="text-lg font-bold text-blue-dianne-950 dark:text-white border-b border-athens-gray-100 dark:border-athens-gray-800 pb-2">Endereço (Opcional)</h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                            <div>
                                <label for="cep" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">CEP</label>
                                <input type="text" id="cep" name="cep" value="{{ old('cep') }}" placeholder="00000-000" maxlength="9" class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 placeholder:text-athens-gray-400 dark:placeholder:text-athens-gray-500 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="logradouro" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">Logradouro</label>
                                <input type="text" id="logradouro" name="logradouro" value="{{ old('logradouro') }}" class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>

                            <div>
                                <label for="numero" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">Número</label>
                                <input type="text" id="numero" name="numero" value="{{ old('numero') }}" class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>

                            <div>
                                <label for="complemento" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">Complemento</label>
                                <input type="text" id="complemento" name="complemento" value="{{ old('complemento') }}" class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>

                            <div>
                                <label for="bairro" class="block text-sm font-semibold text-blue-dianne-950 dark:text-white mb-1.5">Bairro</label>
                                <input type="text" id="bairro" name="bairro" value="{{ old('bairro') }}" class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>
                        </div>

                        <!-- Botões -->
                        <div class="flex items-center justify-end gap-3 pt-6 border-t border-athens-gray-100 dark:border-athens-gray-800">
                            <a href="{{ route('usuarios.index') }}" class="px-5 py-2.5 text-sm font-semibold text-athens-gray-600 dark:text-athens-gray-400 hover:text-blue-dianne-950 dark:hover:text-white hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 rounded-lg transition">
                                Cancelar
                            </a>
                            <button type="submit" id="btn-submit" class="px-6 py-2.5 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold text-sm rounded-lg shadow-sm transition flex items-center gap-2">
                                <x-heroicon-o-check class="w-4 h-4" />
                                Cadastrar Usuário
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </main>
    </div>

    <script>
        function selecionarNivel(nivel) {
            const radio = document.getElementById('radio-' + nivel);
            if (radio) {
                radio.checked = true;
            }

            const niveis = ['cadastrador', 'instalador', 'administrador'];
            
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
                    } else {
                        card.className = 'nivel-card relative flex flex-col justify-between p-4 rounded-xl border-2 transition-all cursor-pointer select-none border-tahiti-gold-600 dark:border-tahiti-gold-500 bg-tahiti-gold-50/80 dark:bg-tahiti-gold-950/60 shadow-sm ring-2 ring-tahiti-gold-500/30';
                        check.className = 'w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors bg-tahiti-gold-600 border-tahiti-gold-600';
                    }
                    if (checkIcon) checkIcon.classList.remove('opacity-0');
                } else {
                    card.className = 'nivel-card relative flex flex-col justify-between p-4 rounded-xl border-2 transition-all cursor-pointer select-none border-athens-gray-200 dark:border-athens-gray-800 bg-white dark:bg-athens-gray-900 hover:border-athens-gray-300 dark:hover:border-athens-gray-700';
                    check.className = 'w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors border-athens-gray-300 dark:border-athens-gray-700 bg-transparent';
                    if (checkIcon) checkIcon.classList.add('opacity-0');
                }
            });

            verificarNivel(nivel);
        }

        function verificarNivel(nivel) {
            const alerta = document.getElementById('alerta-transicao-adm');
            const btn = document.getElementById('btn-submit');

            if (nivel === 'administrador') {
                alerta.classList.remove('hidden');
                btn.classList.remove('bg-blue-dianne-600', 'hover:bg-blue-dianne-700');
                btn.classList.add('bg-tahiti-gold-600', 'hover:bg-tahiti-gold-700');
                btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg> Confirmar e Transferir Administração';
            } else if (nivel === 'instalador') {
                alerta.classList.add('hidden');
                btn.classList.add('bg-blue-dianne-600', 'hover:bg-blue-dianne-700');
                btn.classList.remove('bg-tahiti-gold-600', 'hover:bg-tahiti-gold-700');
                btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Cadastrar Usuário';
            } else {
                alerta.classList.add('hidden');
                btn.classList.add('bg-blue-dianne-600', 'hover:bg-blue-dianne-700');
                btn.classList.remove('bg-tahiti-gold-600', 'hover:bg-tahiti-gold-700');
                btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Cadastrar Usuário';
            }
        }

        // Executa na carga da página
        document.addEventListener('DOMContentLoaded', () => {
            const radioChecked = document.querySelector('input[name="nivel"]:checked');
            const nivelInicial = radioChecked ? radioChecked.value : 'cadastrador';
            selecionarNivel(nivelInicial);
        });
    </script>
</x-layouts.app>
