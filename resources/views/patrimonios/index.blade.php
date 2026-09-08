<x-layouts.app title="Gestão de Patrimônio - OpenAir Metrics">
    <div class="flex flex-col md:flex-row h-full w-full bg-athens-gray-50 dark:bg-athens-gray-950 transition-colors duration-200">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="patrimonios" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto min-h-0 p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Alertas de Feedback -->
                @if (session('success'))
                    <div class="bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-5 py-4 rounded-xl flex items-center justify-between shadow-sm animate-fade-in">
                        <div class="flex items-center gap-3">
                            <div class="p-1 bg-emerald-500 text-white rounded-full">
                                <x-heroicon-o-check class="w-4 h-4" />
                            </div>
                            <p class="text-sm font-medium">{{ session('success') }}</p>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-800 dark:hover:text-emerald-200 transition cursor-pointer">
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-cinnabar-50 dark:bg-cinnabar-950/50 border border-cinnabar-200 dark:border-cinnabar-800 text-cinnabar-800 dark:text-cinnabar-200 px-5 py-4 rounded-xl flex items-center justify-between shadow-sm animate-fade-in">
                        <div class="flex items-center gap-3">
                            <div class="p-1 bg-cinnabar-500 text-white rounded-full">
                                <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                            </div>
                            <p class="text-sm font-medium">{{ session('error') }}</p>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-cinnabar-600 dark:text-cinnabar-400 hover:text-cinnabar-800 dark:hover:text-cinnabar-200 transition cursor-pointer">
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>
                    </div>
                @endif

                <!-- Cabeçalho da Página com Ação Primária -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-blue-dianne-950 dark:text-white tracking-tight">Gestão de Patrimônio</h1>
                        <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mt-1">Estoque e controle de placas e sensores adquiridos (MAC Addresses).</p>
                    </div>

                    <!-- Botão de Ação Primária -->
                    <a href="{{ route('patrimonios.create') }}" class="inline-flex items-center justify-center gap-2 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold py-2.5 px-5 rounded-lg transition-all shadow-sm hover:shadow active:scale-98 text-sm cursor-pointer">
                        <x-heroicon-o-plus class="w-5 h-5" />
                        Cadastrar Equipamentos
                    </a>
                </div>

                <!-- Cards de Resumo Rápido -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-5 flex items-center gap-4 transition-colors">
                        <div class="p-3 bg-blue-dianne-50 dark:bg-blue-dianne-950/50 rounded-lg text-blue-dianne-600 dark:text-blue-dianne-400">
                            <x-heroicon-o-server-stack class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400 uppercase tracking-wide">Total em Estoque</p>
                            <p class="text-2xl font-bold text-blue-dianne-950 dark:text-white">{{ $total }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-5 flex items-center gap-4 transition-colors">
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/50 rounded-lg text-emerald-600 dark:text-emerald-400">
                            <x-heroicon-o-check-circle class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400 uppercase tracking-wide">Disponíveis p/ Instalar</p>
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $disponiveis }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-5 flex items-center gap-4 transition-colors">
                        <div class="p-3 bg-dodger-blue-50 dark:bg-dodger-blue-950/50 rounded-lg text-dodger-blue-600 dark:text-dodger-blue-400">
                            <x-heroicon-o-signal class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400 uppercase tracking-wide">Instaladas em Campo</p>
                            <p class="text-2xl font-bold text-dodger-blue-600 dark:text-dodger-blue-400">{{ $instalados }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-5 flex items-center gap-4 transition-colors">
                        <div class="p-3 bg-tahiti-gold-50 dark:bg-tahiti-gold-950/50 rounded-lg text-tahiti-gold-600 dark:text-tahiti-gold-400">
                            <x-heroicon-o-wrench-screwdriver class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400 uppercase tracking-wide">Em Manutenção</p>
                            <p class="text-2xl font-bold text-tahiti-gold-600 dark:text-tahiti-gold-400">{{ $manutencao }}</p>
                        </div>
                    </div>
                </div>

                <!-- Filtros e Barra de Busca -->
                <div class="bg-white dark:bg-athens-gray-900 p-4 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 transition-colors">
                    <form method="GET" action="{{ route('patrimonios.index') }}" class="flex flex-col sm:flex-row flex-wrap items-center gap-3 w-full">
                        <div class="relative flex-1 min-w-[240px] w-full">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-athens-gray-400" />
                            <input type="text" name="busca" value="{{ $busca }}" placeholder="Buscar por MAC Address, Patrimônio..." class="w-full pl-10 pr-4 py-2 border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg text-xs focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                        </div>

                        <div class="w-full sm:w-auto">
                            <select name="status" onchange="this.form.submit()" class="w-full sm:w-auto border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 rounded-lg px-3 py-2 text-xs text-athens-gray-700 dark:text-athens-gray-200 focus:ring-2 focus:ring-blue-dianne-500">
                                <option value="">Todos os Status</option>
                                <option value="Disponível" @selected(($statusFiltro ?? '') === 'Disponível')>Disponível</option>
                                <option value="Instalado" @selected(($statusFiltro ?? '') === 'Instalado' || ($statusFiltro ?? '') === 'Instalada')>Instalada</option>
                                <option value="Alocado" @selected(($statusFiltro ?? '') === 'Alocado')>Alocado</option>
                                <option value="Manutenção" @selected(($statusFiltro ?? '') === 'Manutenção')>Manutenção</option>
                                <option value="Descartado" @selected(($statusFiltro ?? '') === 'Descartado')>Descartado</option>
                            </select>
                        </div>

                        <div class="w-full sm:w-auto flex items-center gap-1.5">
                            <select name="sort" onchange="this.form.submit()" class="w-full sm:w-auto border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 rounded-lg px-3 py-2 text-xs text-athens-gray-700 dark:text-athens-gray-200 focus:ring-2 focus:ring-blue-dianne-500">
                                <option value="created_at" @selected(($sort ?? '') === 'created_at')>Mais Recentes</option>
                                <option value="mac_address" @selected(($sort ?? '') === 'mac_address')>MAC Address</option>
                                <option value="numero_patrimonio" @selected(($sort ?? '') === 'numero_patrimonio')>Nº Patrimônio</option>
                                <option value="status" @selected(($sort ?? '') === 'status')>Status</option>
                                <option value="data_aquisicao" @selected(($sort ?? '') === 'data_aquisicao')>Data Aquisição</option>
                            </select>

                            <input type="hidden" name="direction" value="{{ ($direction ?? 'desc') === 'asc' ? 'asc' : 'desc' }}">
                            <button 
                                type="button" 
                                onclick="const dir = this.form.querySelector('input[name=direction]'); dir.value = dir.value === 'asc' ? 'desc' : 'asc'; this.form.submit();"
                                title="Alternar Ordem (Crescente / Decrescente)"
                                class="p-2 border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-600 dark:text-athens-gray-300 rounded-lg hover:bg-athens-gray-50 dark:hover:bg-athens-gray-700 transition"
                            >
                                @if(($direction ?? 'desc') === 'desc')
                                    <x-heroicon-o-bars-arrow-down class="w-4 h-4 text-blue-dianne-600 dark:text-blue-dianne-400" />
                                @else
                                    <x-heroicon-o-bars-arrow-up class="w-4 h-4 text-blue-dianne-600 dark:text-blue-dianne-400" />
                                @endif
                            </button>
                        </div>

                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <button type="submit" class="px-3.5 py-2 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                                Filtrar
                            </button>
                            @if ($busca || $statusFiltro || ($sort ?? 'created_at') !== 'created_at' || ($direction ?? 'desc') !== 'desc')
                                <a href="{{ route('patrimonios.index') }}" class="px-3 py-2 bg-athens-gray-100 hover:bg-athens-gray-200 dark:bg-athens-gray-800 dark:hover:bg-athens-gray-700 text-athens-gray-700 dark:text-athens-gray-300 rounded-lg text-xs font-medium transition">
                                    Limpar
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Tabela de Patrimônios -->
                <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 overflow-hidden transition-colors">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-athens-gray-700 dark:text-athens-gray-300">
                            <thead class="bg-athens-gray-50 dark:bg-athens-gray-800/60 border-b border-athens-gray-200 dark:border-athens-gray-800 text-xs font-semibold uppercase text-athens-gray-500 dark:text-athens-gray-400 tracking-wider">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5">
                                        <a href="{{ route('patrimonios.index', array_merge(request()->query(), ['sort' => 'mac_address', 'direction' => (($sort ?? '') === 'mac_address' && ($direction ?? '') === 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-white transition">
                                            MAC Address
                                            @if(($sort ?? '') === 'mac_address')
                                                <span class="text-blue-dianne-600 dark:text-blue-dianne-400">{{ ($direction ?? '') === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3.5">
                                        <a href="{{ route('patrimonios.index', array_merge(request()->query(), ['sort' => 'numero_patrimonio', 'direction' => (($sort ?? '') === 'numero_patrimonio' && ($direction ?? '') === 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-white transition">
                                            Nº Patrimônio
                                            @if(($sort ?? '') === 'numero_patrimonio')
                                                <span class="text-blue-dianne-600 dark:text-blue-dianne-400">{{ ($direction ?? '') === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3.5">
                                        <a href="{{ route('patrimonios.index', array_merge(request()->query(), ['sort' => 'status', 'direction' => (($sort ?? '') === 'status' && ($direction ?? '') === 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-white transition">
                                            Status
                                            @if(($sort ?? '') === 'status')
                                                <span class="text-blue-dianne-600 dark:text-blue-dianne-400">{{ ($direction ?? '') === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3.5">Estação / Localidade</th>
                                    <th scope="col" class="px-6 py-3.5">
                                        <a href="{{ route('patrimonios.index', array_merge(request()->query(), ['sort' => 'data_aquisicao', 'direction' => (($sort ?? '') === 'data_aquisicao' && ($direction ?? '') === 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-white transition">
                                            Data Aquisição
                                            @if(($sort ?? '') === 'data_aquisicao')
                                                <span class="text-blue-dianne-600 dark:text-blue-dianne-400">{{ ($direction ?? '') === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3.5 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-athens-gray-200 dark:divide-athens-gray-800">
                                @forelse ($patrimonios as $item)
                                    <tr class="hover:bg-athens-gray-50/60 dark:hover:bg-athens-gray-800/50 transition">
                                        <!-- MAC Address -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="font-mono font-bold text-blue-dianne-950 dark:text-blue-dianne-200 bg-blue-dianne-50 dark:bg-blue-dianne-950/60 px-2.5 py-1 rounded text-xs border border-blue-dianne-200 dark:border-blue-dianne-800">
                                                {{ $item->mac_address }}
                                            </span>
                                        </td>

                                        <!-- Número de Patrimônio -->
                                        <td class="px-6 py-4 whitespace-nowrap text-athens-gray-600 dark:text-athens-gray-400 font-medium">
                                            {{ $item->numero_patrimonio ?? '-' }}
                                        </td>

                                        <!-- Status -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($item->status === 'Disponível')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    Disponível
                                                </span>
                                            @elseif ($item->status === 'Instalado' || $item->status === 'Instalada')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-dodger-blue-50 dark:bg-dodger-blue-950/50 text-dodger-blue-700 dark:text-dodger-blue-300 border border-dodger-blue-200 dark:border-dodger-blue-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-dodger-blue-500"></span>
                                                    Instalada
                                                </span>
                                            @elseif ($item->status === 'Alocado')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-dodger-blue-50 dark:bg-dodger-blue-950/50 text-dodger-blue-700 dark:text-dodger-blue-300 border border-dodger-blue-200 dark:border-dodger-blue-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-dodger-blue-500"></span>
                                                    Alocado
                                                </span>
                                            @elseif ($item->status === 'Manutenção')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-tahiti-gold-50 dark:bg-tahiti-gold-950/50 text-tahiti-gold-700 dark:text-tahiti-gold-300 border border-tahiti-gold-200 dark:border-tahiti-gold-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-tahiti-gold-500"></span>
                                                    Manutenção
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-athens-gray-100 dark:bg-athens-gray-800 text-athens-gray-600 dark:text-athens-gray-300 border border-athens-gray-300 dark:border-athens-gray-700">
                                                    {{ $item->status }}
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Estação Vinculada -->
                                        <td class="px-6 py-4">
                                            @if ($item->estacao)
                                                <div class="text-xs">
                                                    <span class="font-semibold text-blue-dianne-900 dark:text-white block">{{ $item->estacao->tipo_estacao }}</span>
                                                    <span class="text-athens-gray-600 dark:text-athens-gray-400">{{ $item->estacao->bairro_nome ?? $item->estacao->bairro?->nome }}</span>
                                                </div>
                                            @else
                                                <span class="text-xs text-athens-gray-400 dark:text-athens-gray-500 italic">Não vinculado</span>
                                            @endif
                                        </td>

                                        <!-- Data de Aquisição -->
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-athens-gray-600 dark:text-athens-gray-400">
                                            {{ $item->data_aquisicao ? $item->data_aquisicao->format('d/m/Y') : '-' }}
                                        </td>

                                        <!-- Ações -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                            @if (! $item->estacao)
                                                <form action="{{ route('patrimonios.destroy', $item->private_id) }}" method="POST" onsubmit="return confirm('Deseja realmente remover este patrimônio?')" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-cinnabar-600 dark:text-cinnabar-400 hover:text-cinnabar-800 dark:hover:text-cinnabar-200 font-semibold transition cursor-pointer p-1 hover:bg-cinnabar-50 dark:hover:bg-cinnabar-950/50 rounded">
                                                        <x-heroicon-o-trash class="w-4 h-4" />
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-athens-gray-300 dark:text-athens-gray-600">
                                                    <x-heroicon-o-lock-closed class="w-4 h-4 inline" />
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-athens-gray-400 dark:text-athens-gray-500 bg-white dark:bg-athens-gray-900">
                                            <x-heroicon-o-server-stack class="w-12 h-12 mx-auto mb-3 text-athens-gray-300 dark:text-athens-gray-600" />
                                            <p class="font-medium text-base text-athens-gray-600 dark:text-athens-gray-300">Nenhum equipamento de patrimônio cadastrado.</p>
                                            <p class="text-xs mt-1">Cadastre as placas compradas para que os instaladores possam vinculá-las em campo.</p>
                                            <a href="{{ route('patrimonios.create') }}" class="inline-flex items-center gap-1.5 mt-4 text-xs font-semibold bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white py-2 px-4 rounded-lg transition shadow-sm">
                                                <x-heroicon-o-plus class="w-4 h-4" />
                                                Cadastrar Primeiro Equipamento
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    @if ($patrimonios->hasPages())
                        <div class="px-6 py-4 border-t border-athens-gray-200 dark:border-athens-gray-800 bg-athens-gray-50 dark:bg-athens-gray-900">
                            {{ $patrimonios->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </main>
    </div>
</x-layouts.app>

