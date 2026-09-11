<x-layouts.app title="Gestão de Usuários Municipais - OpenAir Metrics">
    <div class="flex flex-col md:flex-row h-full w-full bg-athens-gray-50 dark:bg-athens-gray-950 transition-colors duration-200">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="usuarios" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto min-h-0 p-4 sm:p-6 lg:p-8">
            <div class="max-w-6xl mx-auto space-y-6">

                <!-- Breadcrumb -->
                <div class="flex items-center gap-2 text-sm text-athens-gray-500 dark:text-athens-gray-400">
                    <span class="hover:text-blue-dianne-600 transition">Administração</span>
                    <x-heroicon-o-chevron-right class="w-4 h-4" />
                    <span class="text-blue-dianne-950 dark:text-white font-medium">
                        {{ $currentUser->isSuperAdmin() ? 'Administradores Municipais' : 'Usuários Municipais' }}
                    </span>
                </div>

                <!-- Alertas -->
                @if (session('success'))
                    <div class="bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-4 py-3 rounded-xl flex items-center gap-3">
                        <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
                        <p class="text-sm font-medium">{{ session('success') }}</p>
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-cinnabar-50 dark:bg-cinnabar-950/50 border border-cinnabar-200 dark:border-cinnabar-800 text-cinnabar-800 dark:text-cinnabar-200 px-4 py-3 rounded-xl flex items-center gap-3">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-cinnabar-600 dark:text-cinnabar-400 flex-shrink-0" />
                        <p class="text-sm font-medium">{{ session('error') }}</p>
                    </div>
                @endif

                @if (session('info'))
                    <div class="bg-blue-dianne-50 dark:bg-blue-dianne-950/50 border border-blue-dianne-200 dark:border-blue-dianne-800 text-blue-dianne-800 dark:text-blue-dianne-200 px-4 py-3 rounded-xl flex items-center gap-3">
                        <x-heroicon-o-information-circle class="w-5 h-5 text-blue-dianne-600 dark:text-blue-dianne-400 flex-shrink-0" />
                        <p class="text-sm font-medium">{{ session('info') }}</p>
                    </div>
                @endif

                <!-- Cabeçalho -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-blue-dianne-950 dark:text-white tracking-tight">
                            {{ $currentUser->isSuperAdmin() ? 'Gestão de Administradores' : 'Equipe Municipal' }}
                        </h1>
                        <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mt-1">
                            @if($currentUser->isSuperAdmin())
                                Administradores responsáveis pela gestão dos municípios no OpenAir Metrics.
                            @else
                                Usuários cadastrados para a gestão de sensores em 
                                <strong class="text-blue-dianne-950 dark:text-white">{{ $currentUser->cidade->nome ?? 'Sua Cidade' }} ({{ $currentUser->cidade->estado->uf ?? '' }})</strong>.
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('usuarios.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        <x-heroicon-o-user-plus class="w-4 h-4" />
                        {{ $currentUser->isSuperAdmin() ? 'Novo Administrador' : 'Novo Usuário' }}
                    </a>
                </div>

                <!-- Aviso de Perfil -->
                <div class="bg-blue-dianne-50 dark:bg-blue-dianne-950/50 border border-blue-dianne-200 dark:border-blue-dianne-800 rounded-xl p-4 flex items-start gap-3">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-dianne-600 dark:text-blue-dianne-400 flex-shrink-0 mt-0.5" />
                    <div class="text-xs text-blue-dianne-900 dark:text-blue-dianne-200 leading-relaxed">
                        @if($currentUser->isSuperAdmin())
                            <strong>Painel Central do Super-usuário:</strong> Você gerencia exclusivamente os Administradores Municipais cadastrados para cada município.
                        @else
                            <strong>Regra de Gestão Municipal:</strong> O Administrador Municipal cadastra e gerencia a equipe operacional (Planejadores Técnicos e Instaladores) sob sua jurisdição municipal.
                        @endif
                    </div>
                </div>

                <!-- Barra de Busca, Filtros e Ordenação -->
                <div class="bg-white dark:bg-athens-gray-900 p-4 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 transition-colors">
                    <form method="GET" action="{{ route('usuarios.index') }}" class="flex flex-col sm:flex-row flex-wrap items-center gap-3">
                        <!-- Busca Textual -->
                        <div class="relative flex-1 min-w-[220px] w-full">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-athens-gray-400" />
                            <input 
                                type="text" 
                                name="busca" 
                                value="{{ $filtros['busca'] ?? '' }}" 
                                placeholder="Buscar por Nome ou E-mail..." 
                                class="w-full pl-10 pr-4 py-2 border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg text-xs focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                            >
                        </div>

                        @if($currentUser->isSuperAdmin() && !empty($cidades))
                            <!-- Filtro Cidade para SuperAdmin -->
                            <div class="w-full sm:w-auto">
                                <select name="cidade_id" onchange="this.form.submit()" class="w-full sm:w-auto border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 rounded-lg px-3 py-2 text-xs text-athens-gray-700 dark:text-athens-gray-200 focus:ring-2 focus:ring-blue-dianne-500">
                                    <option value="">Todas as Cidades</option>
                                    @foreach($cidades as $c)
                                        <option value="{{ $c->id }}" @selected(($filtros['cidade_id'] ?? '') == $c->id)>{{ $c->nome }} ({{ $c->estado->uf ?? '' }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <!-- Filtro Nível para Administrador Municipal -->
                            <div class="w-full sm:w-auto">
                                <select name="nivel" onchange="this.form.submit()" class="w-full sm:w-auto border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 rounded-lg px-3 py-2 text-xs text-athens-gray-700 dark:text-athens-gray-200 focus:ring-2 focus:ring-blue-dianne-500">
                                    <option value="">Todos os Níveis</option>
                                    <option value="cadastrador" @selected(($filtros['nivel'] ?? '') === 'cadastrador')>Planejador Técnico</option>
                                    <option value="instalador" @selected(($filtros['nivel'] ?? '') === 'instalador')>Instalador</option>
                                </select>
                            </div>
                        @endif

                        <!-- Filtro Status -->
                        <div class="w-full sm:w-auto">
                            <select name="status" onchange="this.form.submit()" class="w-full sm:w-auto border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 rounded-lg px-3 py-2 text-xs text-athens-gray-700 dark:text-athens-gray-200 focus:ring-2 focus:ring-blue-dianne-500">
                                <option value="">Todos os Status</option>
                                <option value="ativo" @selected(($filtros['status'] ?? '') === 'ativo')>Ativos</option>
                                <option value="inativo" @selected(($filtros['status'] ?? '') === 'inativo')>Inativos</option>
                            </select>
                        </div>

                        <!-- Ordenação -->
                        <div class="w-full sm:w-auto flex items-center gap-1.5">
                            <select name="sort" onchange="this.form.submit()" class="w-full sm:w-auto border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 rounded-lg px-3 py-2 text-xs text-athens-gray-700 dark:text-athens-gray-200 focus:ring-2 focus:ring-blue-dianne-500">
                                <option value="name" @selected(($filtros['sort'] ?? '') === 'name')>Nome (A-Z)</option>
                                <option value="email" @selected(($filtros['sort'] ?? '') === 'email')>E-mail</option>
                                <option value="nivel" @selected(($filtros['sort'] ?? '') === 'nivel')>Nível de Acesso</option>
                                <option value="ativo" @selected(($filtros['sort'] ?? '') === 'ativo')>Status</option>
                                <option value="created_at" @selected(($filtros['sort'] ?? '') === 'created_at')>Data de Cadastro</option>
                            </select>

                            <input type="hidden" name="direction" value="{{ ($filtros['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc' }}">
                            <button 
                                type="button" 
                                onclick="const dir = this.form.querySelector('input[name=direction]'); dir.value = dir.value === 'asc' ? 'desc' : 'asc'; this.form.submit();"
                                title="Alternar Ordem (Crescente / Decrescente)"
                                class="p-2 border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-600 dark:text-athens-gray-300 rounded-lg hover:bg-athens-gray-50 dark:hover:bg-athens-gray-700 transition"
                            >
                                @if(($filtros['direction'] ?? 'asc') === 'desc')
                                    <x-heroicon-o-bars-arrow-down class="w-4 h-4 text-blue-dianne-600 dark:text-blue-dianne-400" />
                                @else
                                    <x-heroicon-o-bars-arrow-up class="w-4 h-4 text-blue-dianne-600 dark:text-blue-dianne-400" />
                                @endif
                            </button>
                        </div>

                        <!-- Botão Filtrar / Limpar -->
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <button type="submit" class="px-3.5 py-2 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                                Filtrar
                            </button>
                            @if(!empty($filtros['busca']) || !empty($filtros['nivel']) || !empty($filtros['status']) || !empty($filtros['cidade_id']) || ($filtros['sort'] ?? 'name') !== 'name' || ($filtros['direction'] ?? 'asc') !== 'asc')
                                <a href="{{ route('usuarios.index') }}" class="px-3 py-2 bg-athens-gray-100 hover:bg-athens-gray-200 dark:bg-athens-gray-800 dark:hover:bg-athens-gray-700 text-athens-gray-700 dark:text-athens-gray-300 rounded-lg text-xs font-medium transition">
                                    Limpar
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Tabela de Usuários -->
                <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 overflow-hidden transition-colors">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-athens-gray-200 dark:border-athens-gray-800 bg-athens-gray-50/75 dark:bg-athens-gray-800/60 text-xs font-semibold uppercase text-athens-gray-500 dark:text-athens-gray-400 tracking-wider">
                                    <th class="py-3.5 px-6">
                                        <a href="{{ route('usuarios.index', array_merge(request()->query(), ['sort' => 'name', 'direction' => (($filtros['sort'] ?? '') === 'name' && ($filtros['direction'] ?? '') === 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-white transition">
                                            Nome / Email
                                            @if(($filtros['sort'] ?? '') === 'name')
                                                <span class="text-blue-dianne-600 dark:text-blue-dianne-400">{{ ($filtros['direction'] ?? '') === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="py-3.5 px-6">
                                        <a href="{{ route('usuarios.index', array_merge(request()->query(), ['sort' => 'nivel', 'direction' => (($filtros['sort'] ?? '') === 'nivel' && ($filtros['direction'] ?? '') === 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-white transition">
                                            Nível de Acesso
                                            @if(($filtros['sort'] ?? '') === 'nivel')
                                                <span class="text-blue-dianne-600 dark:text-blue-dianne-400">{{ ($filtros['direction'] ?? '') === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="py-3.5 px-6">Cidade</th>
                                    <th class="py-3.5 px-6">
                                        <a href="{{ route('usuarios.index', array_merge(request()->query(), ['sort' => 'ativo', 'direction' => (($filtros['sort'] ?? '') === 'ativo' && ($filtros['direction'] ?? '') === 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-white transition">
                                            Status
                                            @if(($filtros['sort'] ?? '') === 'ativo')
                                                <span class="text-blue-dianne-600 dark:text-blue-dianne-400">{{ ($filtros['direction'] ?? '') === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="py-3.5 px-6">
                                        <a href="{{ route('usuarios.index', array_merge(request()->query(), ['sort' => 'created_at', 'direction' => (($filtros['sort'] ?? '') === 'created_at' && ($filtros['direction'] ?? '') === 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-white transition">
                                            Data de Cadastro
                                            @if(($filtros['sort'] ?? '') === 'created_at')
                                                <span class="text-blue-dianne-600 dark:text-blue-dianne-400">{{ ($filtros['direction'] ?? '') === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="py-3.5 px-6 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-athens-gray-100 dark:divide-athens-gray-800 text-sm">
                                @forelse($usuarios as $u)
                                    <tr class="hover:bg-athens-gray-50/50 dark:hover:bg-athens-gray-800/50 transition">
                                        <td class="py-4 px-6">
                                            <div class="font-semibold text-blue-dianne-950 dark:text-white flex items-center gap-2">
                                                {{ $u->name }}
                                                @if($u->id === $currentUser->id)
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-dianne-100 dark:bg-blue-dianne-950/80 text-blue-dianne-700 dark:text-blue-dianne-300">Você</span>
                                                @endif
                                            </div>
                                            <div class="text-xs text-athens-gray-500 dark:text-athens-gray-400">{{ $u->email }}</div>
                                        </td>
                                        <td class="py-4 px-6">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold
                                                @if($u->isAdministrador()) bg-ebony-clay-100 dark:bg-ebony-clay-950/70 text-ebony-clay-800 dark:text-ebony-clay-200
                                                @elseif($u->isInstalador()) bg-tahiti-gold-100 dark:bg-tahiti-gold-950/70 text-tahiti-gold-800 dark:text-tahiti-gold-200
                                                @else bg-blue-dianne-100 dark:bg-blue-dianne-950/70 text-blue-dianne-800 dark:text-blue-dianne-200 @endif">
                                                {{ $u->nivel_label }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-athens-gray-700 dark:text-athens-gray-300">
                                            {{ $u->cidade->nome ?? '-' }}
                                        </td>
                                        <td class="py-4 px-6">
                                            @if($u->ativo)
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    Ativo
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-cinnabar-700 dark:text-cinnabar-300 bg-cinnabar-50 dark:bg-cinnabar-950/50 px-2 py-0.5 rounded-full border border-cinnabar-200 dark:border-cinnabar-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-cinnabar-500"></span>
                                                    Inativo
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6 text-xs text-athens-gray-500 dark:text-athens-gray-400">
                                            {{ $u->created_at?->format('d/m/Y H:i') ?? '-' }}
                                        </td>
                                        <td class="py-4 px-6 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <!-- Botão Editar -->
                                                <a 
                                                    href="{{ route('usuarios.edit', $u->id) }}" 
                                                    title="Editar dados do usuário" 
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg text-blue-dianne-700 dark:text-blue-dianne-300 bg-blue-dianne-50 dark:bg-blue-dianne-950/50 hover:bg-blue-dianne-100 dark:hover:bg-blue-dianne-900 border border-blue-dianne-200 dark:border-blue-dianne-800 transition"
                                                >
                                                    <x-heroicon-o-pencil-square class="w-3.5 h-3.5" />
                                                    Editar
                                                </a>

                                                <!-- Botão Desativar / Reativar -->
                                                @if($u->id === $currentUser->id)
                                                    <span class="inline-flex items-center text-xs text-athens-gray-400 dark:text-athens-gray-600 px-2 py-1 cursor-not-allowed" title="Você não pode desativar seu próprio acesso">
                                                        -
                                                    </span>
                                                @else
                                                    @if($u->ativo)
                                                        <form method="POST" action="{{ route('usuarios.toggle-status', $u->id) }}" onsubmit="return confirm('Deseja realmente desativar o acesso de {{ $u->name }}? O usuário não conseguirá mais entrar no sistema.');">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button 
                                                                type="submit" 
                                                                title="Desativar acesso do usuário" 
                                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg text-cinnabar-700 dark:text-cinnabar-300 bg-cinnabar-50 dark:bg-cinnabar-950/50 hover:bg-cinnabar-100 dark:hover:bg-cinnabar-900 border border-cinnabar-200 dark:border-cinnabar-800 transition"
                                                            >
                                                                <x-heroicon-o-user-minus class="w-3.5 h-3.5" />
                                                                Desativar
                                                            </button>
                                                        </form>
                                                    @else
                                                        <form method="POST" action="{{ route('usuarios.toggle-status', $u->id) }}" onsubmit="return confirm('Deseja reativar o acesso de {{ $u->name }}?');">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button 
                                                                type="submit" 
                                                                title="Reativar acesso do usuário" 
                                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 hover:bg-emerald-100 dark:hover:bg-emerald-900 border border-emerald-200 dark:border-emerald-800 transition"
                                                            >
                                                                <x-heroicon-o-user-plus class="w-3.5 h-3.5" />
                                                                Reativar
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-athens-gray-500 dark:text-athens-gray-400">
                                            Nenhum usuário encontrado com os filtros selecionados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($usuarios->hasPages())
                        <div class="p-4 border-t border-athens-gray-200 dark:border-athens-gray-800 bg-athens-gray-50 dark:bg-athens-gray-900">
                            {{ $usuarios->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </main>
    </div>
</x-layouts.app>
