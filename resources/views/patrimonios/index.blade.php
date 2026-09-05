<x-layouts.app title="Gestão de Patrimônio - OpenAir Metrics">
    <div class="flex h-full w-full bg-athens-gray-50">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="patrimonios" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Alertas de Feedback -->
                @if (session('success'))
                    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-xl flex items-center justify-between shadow-sm animate-fade-in">
                        <div class="flex items-center gap-3">
                            <div class="p-1 bg-emerald-500 text-white rounded-full">
                                <x-heroicon-o-check class="w-4 h-4" />
                            </div>
                            <p class="text-sm font-medium">{{ session('success') }}</p>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 transition cursor-pointer">
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-cinnabar-50 border border-cinnabar-200 text-cinnabar-800 px-5 py-4 rounded-xl flex items-center justify-between shadow-sm animate-fade-in">
                        <div class="flex items-center gap-3">
                            <div class="p-1 bg-cinnabar-500 text-white rounded-full">
                                <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                            </div>
                            <p class="text-sm font-medium">{{ session('error') }}</p>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-cinnabar-600 hover:text-cinnabar-800 transition cursor-pointer">
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>
                    </div>
                @endif

                <!-- Cabeçalho da Página com Ação Primária -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-blue-dianne-950 tracking-tight">Gestão de Patrimônio</h1>
                        <p class="text-sm text-athens-gray-600 mt-1">Estoque e controle de placas e sensores adquiridos (MAC Addresses).</p>
                    </div>

                    <!-- Botão de Ação Primária -->
                    <a href="{{ route('patrimonios.create') }}" class="inline-flex items-center justify-center gap-2 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold py-2.5 px-5 rounded-lg transition-all shadow-sm hover:shadow active:scale-98 text-sm cursor-pointer">
                        <x-heroicon-o-plus class="w-5 h-5" />
                        Cadastrar Equipamentos
                    </a>
                </div>

                <!-- Cards de Resumo Rápido -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 flex items-center gap-4">
                        <div class="p-3 bg-blue-dianne-50 rounded-lg text-blue-dianne-600">
                            <x-heroicon-o-server-stack class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 uppercase tracking-wide">Total em Estoque</p>
                            <p class="text-2xl font-bold text-blue-dianne-950">{{ $total }}</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 flex items-center gap-4">
                        <div class="p-3 bg-emerald-50 rounded-lg text-emerald-600">
                            <x-heroicon-o-check-circle class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 uppercase tracking-wide">Disponíveis p/ Instalar</p>
                            <p class="text-2xl font-bold text-emerald-600">{{ $disponiveis }}</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 flex items-center gap-4">
                        <div class="p-3 bg-dodger-blue-50 rounded-lg text-dodger-blue-600">
                            <x-heroicon-o-signal class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 uppercase tracking-wide">Instalados em Campo</p>
                            <p class="text-2xl font-bold text-dodger-blue-600">{{ $instalados }}</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 flex items-center gap-4">
                        <div class="p-3 bg-amber-50 rounded-lg text-amber-600">
                            <x-heroicon-o-wrench-screwdriver class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 uppercase tracking-wide">Em Manutenção</p>
                            <p class="text-2xl font-bold text-amber-600">{{ $manutencao }}</p>
                        </div>
                    </div>
                </div>

                <!-- Filtros e Barra de Busca -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-athens-gray-200 flex flex-col md:flex-row gap-3 items-center justify-between">
                    <form method="GET" action="{{ route('patrimonios.index') }}" class="flex flex-wrap items-center gap-3 w-full">
                        <div class="relative flex-1 min-w-[240px]">
                            <x-heroicon-o-magnifying-glass class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-athens-gray-400" />
                            <input type="text" name="busca" value="{{ $busca }}" placeholder="Buscar por MAC Address, Patrimônio..." class="w-full pl-10 pr-4 py-2 border border-athens-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                        </div>

                        <select name="status" onchange="this.form.submit()" class="border border-athens-gray-300 rounded-lg px-3 py-2 text-sm text-athens-gray-700 focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500">
                            <option value="">Todos os Status</option>
                            <option value="Disponível" {{ $statusFiltro === 'Disponível' ? 'selected' : '' }}>Disponível</option>
                            <option value="Instalado" {{ $statusFiltro === 'Instalado' ? 'selected' : '' }}>Instalado</option>
                            <option value="Alocado" {{ $statusFiltro === 'Alocado' ? 'selected' : '' }}>Alocado</option>
                            <option value="Manutenção" {{ $statusFiltro === 'Manutenção' ? 'selected' : '' }}>Manutenção</option>
                            <option value="Descartado" {{ $statusFiltro === 'Descartado' ? 'selected' : '' }}>Descartado</option>
                        </select>

                        <button type="submit" class="bg-athens-gray-100 hover:bg-athens-gray-200 text-athens-gray-700 text-sm font-semibold px-4 py-2 rounded-lg transition cursor-pointer">
                            Filtrar
                        </button>

                        @if ($busca || $statusFiltro)
                            <a href="{{ route('patrimonios.index') }}" class="text-xs text-cinnabar-600 hover:underline font-medium ml-1">
                                Limpar Filtros
                            </a>
                        @endif
                    </form>
                </div>

                <!-- Tabela de Patrimônios -->
                <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-athens-gray-700">
                            <thead class="bg-athens-gray-50 border-b border-athens-gray-200 text-xs font-semibold uppercase text-athens-gray-500 tracking-wider">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5">MAC Address</th>
                                    <th scope="col" class="px-6 py-3.5">Nº Patrimônio</th>
                                    <th scope="col" class="px-6 py-3.5">Tipo Sugerido</th>
                                    <th scope="col" class="px-6 py-3.5">Status</th>
                                    <th scope="col" class="px-6 py-3.5">Estação / Localidade</th>
                                    <th scope="col" class="px-6 py-3.5">Data Aquisição</th>
                                    <th scope="col" class="px-6 py-3.5 text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-athens-gray-200">
                                @forelse ($patrimonios as $item)
                                    <tr class="hover:bg-athens-gray-50/60 transition">
                                        <!-- MAC Address -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="font-mono font-bold text-blue-dianne-950 bg-blue-dianne-50 px-2.5 py-1 rounded text-xs border border-blue-dianne-200">
                                                {{ $item->mac_address }}
                                            </span>
                                        </td>

                                        <!-- Número de Patrimônio -->
                                        <td class="px-6 py-4 whitespace-nowrap text-athens-gray-600 font-medium">
                                            {{ $item->numero_patrimonio ?? '-' }}
                                        </td>

                                        <!-- Tipo Sugerido -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($item->tipo_sugerido === 'Estação Matriz')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-dodger-blue-50 text-dodger-blue-700 border border-dodger-blue-200">
                                                    <x-heroicon-o-cpu-chip class="w-3.5 h-3.5 text-dodger-blue-600" />
                                                    Matriz
                                                </span>
                                            @elseif ($item->tipo_sugerido === 'Estação Satélite')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                                    <x-heroicon-o-radio class="w-3.5 h-3.5 text-purple-600" />
                                                    Satélite
                                                </span>
                                            @else
                                                <span class="text-xs text-athens-gray-400">Qualquer tipo</span>
                                            @endif
                                        </td>

                                        <!-- Status -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($item->status === 'Disponível')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    Disponível
                                                </span>
                                            @elseif ($item->status === 'Instalado')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-dodger-blue-50 text-dodger-blue-700 border border-dodger-blue-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-dodger-blue-500"></span>
                                                    Instalado
                                                </span>
                                            @elseif ($item->status === 'Alocado')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                                    Alocado
                                                </span>
                                            @elseif ($item->status === 'Manutenção')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                    Manutenção
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-athens-gray-100 text-athens-gray-600 border border-athens-gray-300">
                                                    {{ $item->status }}
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Estação Vinculada -->
                                        <td class="px-6 py-4">
                                            @if ($item->estacao)
                                                <div class="text-xs">
                                                    <span class="font-semibold text-blue-dianne-900 block">{{ $item->estacao->tipo_estacao }}</span>
                                                    <span class="text-athens-gray-600">{{ $item->estacao->bairro_nome ?? $item->estacao->bairro?->nome }}</span>
                                                </div>
                                            @else
                                                <span class="text-xs text-athens-gray-400 italic">Não vinculado</span>
                                            @endif
                                        </td>

                                        <!-- Data de Aquisição -->
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-athens-gray-600">
                                            {{ $item->data_aquisicao ? $item->data_aquisicao->format('d/m/Y') : '-' }}
                                        </td>

                                        <!-- Ações -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-xs">
                                            @if (! $item->estacao)
                                                <form action="{{ route('patrimonios.destroy', $item->private_id) }}" method="POST" onsubmit="return confirm('Deseja realmente remover este patrimônio?')" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-cinnabar-600 hover:text-cinnabar-800 font-semibold transition cursor-pointer p-1 hover:bg-cinnabar-50 rounded">
                                                        <x-heroicon-o-trash class="w-4 h-4" />
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-athens-gray-300">
                                                    <x-heroicon-o-lock-closed class="w-4 h-4 inline" />
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-athens-gray-400">
                                            <x-heroicon-o-server-stack class="w-12 h-12 mx-auto mb-3 text-athens-gray-300" />
                                            <p class="font-medium text-base text-athens-gray-600">Nenhum equipamento de patrimônio cadastrado.</p>
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
                        <div class="px-6 py-4 border-t border-athens-gray-200 bg-athens-gray-50">
                            {{ $patrimonios->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </main>
    </div>
</x-layouts.app>

