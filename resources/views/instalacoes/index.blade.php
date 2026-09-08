<x-layouts.app title="Ordens de Instalação - OpenAir Metrics">
    <div class="flex flex-col md:flex-row h-full w-full bg-athens-gray-50 dark:bg-athens-gray-950 transition-colors duration-200">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="instalacoes" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto min-h-0 p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Alertas -->
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

                <!-- Cabeçalho -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-blue-dianne-950 dark:text-white tracking-tight">Ordens de Instalação de Estações</h1>
                        <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mt-1">Roteiros sequenciais para instalação e vinculação física de MAC Address em campo.</p>
                    </div>

                    <a href="{{ route('estacoes.planejar') }}" class="inline-flex items-center justify-center gap-2 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold py-2.5 px-5 rounded-lg transition-all shadow-sm hover:shadow text-sm cursor-pointer">
                        <x-heroicon-o-plus class="w-5 h-5" />
                        Planejar Nova Malha
                    </a>
                </div>

                <!-- Barra de Busca, Filtros e Ordenação -->
                <div class="bg-white dark:bg-athens-gray-900 p-4 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 transition-colors">
                    <form method="GET" action="{{ route('instalacoes.index') }}" class="flex flex-col sm:flex-row flex-wrap items-center gap-3 w-full">
                        <div class="relative flex-1 min-w-[240px] w-full">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-athens-gray-400" />
                            <input type="text" name="busca" value="{{ $busca ?? '' }}" placeholder="Buscar por MAC Address, Bairro, Logradouro..." class="w-full pl-10 pr-4 py-2 border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg text-xs focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                        </div>

                        <div class="w-full sm:w-auto">
                            <select name="status" onchange="this.form.submit()" class="w-full sm:w-auto border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 rounded-lg px-3 py-2 text-xs text-athens-gray-700 dark:text-athens-gray-200 focus:ring-2 focus:ring-blue-dianne-500">
                                <option value="">Todos os Status</option>
                                <option value="Instalada" @selected(($status ?? '') === 'Instalada')>Instaladas</option>
                                <option value="Pendente" @selected(($status ?? '') === 'Pendente')>Pendentes</option>
                            </select>
                        </div>

                        <div class="w-full sm:w-auto flex items-center gap-1.5">
                            <select name="sort" onchange="this.form.submit()" class="w-full sm:w-auto border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 rounded-lg px-3 py-2 text-xs text-athens-gray-700 dark:text-athens-gray-200 focus:ring-2 focus:ring-blue-dianne-500">
                                <option value="created_at" @selected(($sort ?? '') === 'created_at')>Mais Recentes</option>
                                <option value="ordem_instalacao" @selected(($sort ?? '') === 'ordem_instalacao')>Ordem Matriz</option>
                                <option value="status_instalacao" @selected(($sort ?? '') === 'status_instalacao')>Status da Matriz</option>
                                <option value="mac_address" @selected(($sort ?? '') === 'mac_address')>MAC Address</option>
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
                            @if (!empty($busca) || !empty($status) || ($sort ?? 'created_at') !== 'created_at' || ($direction ?? 'desc') !== 'desc')
                                <a href="{{ route('instalacoes.index') }}" class="px-3 py-2 bg-athens-gray-100 hover:bg-athens-gray-200 dark:bg-athens-gray-800 dark:hover:bg-athens-gray-700 text-athens-gray-700 dark:text-athens-gray-300 rounded-lg text-xs font-medium transition">
                                    Limpar
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <!-- Tabela de Malhas e Ordens de Instalação -->
                <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 overflow-hidden transition-colors">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-athens-gray-700 dark:text-athens-gray-300">
                            <thead class="bg-athens-gray-50 dark:bg-athens-gray-800/60 border-b border-athens-gray-200 dark:border-athens-gray-800 text-xs font-semibold uppercase text-athens-gray-500 dark:text-athens-gray-400 tracking-wider">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5">
                                        <a href="{{ route('instalacoes.index', array_merge(request()->query(), ['sort' => 'ordem_instalacao', 'direction' => (($sort ?? '') === 'ordem_instalacao' && ($direction ?? '') === 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-white transition">
                                            Estação Matriz (Cluster)
                                            @if(($sort ?? '') === 'ordem_instalacao')
                                                <span class="text-blue-dianne-600 dark:text-blue-dianne-400">{{ ($direction ?? '') === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3.5">Localização / Bairro</th>
                                    <th scope="col" class="px-6 py-3.5">Total de Sensores</th>
                                    <th scope="col" class="px-6 py-3.5">
                                        <a href="{{ route('instalacoes.index', array_merge(request()->query(), ['sort' => 'status_instalacao', 'direction' => (($sort ?? '') === 'status_instalacao' && ($direction ?? '') === 'asc') ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-white transition">
                                            Status da Matriz
                                            @if(($sort ?? '') === 'status_instalacao')
                                                <span class="text-blue-dianne-600 dark:text-blue-dianne-400">{{ ($direction ?? '') === 'asc' ? '↑' : '↓' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th scope="col" class="px-6 py-3.5">Progresso da Instalação</th>
                                    <th scope="col" class="px-6 py-3.5 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-athens-gray-200 dark:divide-athens-gray-800">
                                @forelse ($matrizes as $m)
                                    @php
                                        $totalEstacoesCluster = $m->total_satelites + 1;
                                        $instaladasCluster = ($m->status_instalacao === 'Instalada' ? 1 : 0) + $m->instaladas_satelites;
                                        $porcentagem = $totalEstacoesCluster > 0 ? round(($instaladasCluster / $totalEstacoesCluster) * 100) : 0;
                                        $completa = $porcentagem === 100;
                                    @endphp
                                    <tr class="hover:bg-athens-gray-50/60 dark:hover:bg-athens-gray-800/50 transition">
                                        <!-- Identificação da Matriz -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="p-2 bg-dodger-blue-50 dark:bg-dodger-blue-950/50 text-dodger-blue-600 dark:text-dodger-blue-400 rounded-lg">
                                                    <x-heroicon-o-cpu-chip class="w-5 h-5" />
                                                </div>
                                                <div>
                                                    <span class="font-bold text-blue-dianne-950 dark:text-white block">Matriz #{{ $m->ordem_instalacao ?? 1 }}</span>
                                                    @if ($m->mac_address)
                                                        <span class="font-mono text-xs text-athens-gray-500 dark:text-athens-gray-400">{{ $m->mac_address }}</span>
                                                    @else
                                                        <span class="text-xs text-tahiti-gold-600 dark:text-tahiti-gold-400 font-medium">MAC: Pendente de Instalação</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Localidade -->
                                        <td class="px-6 py-4">
                                            <p class="font-medium text-blue-dianne-900 dark:text-white">{{ $m->bairro_nome ?? $m->bairro?->nome }}</p>
                                            <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400">{{ $m->cidade_nome ?? $m->bairro?->cidade?->nome }} - {{ $m->estado_uf ?? $m->bairro?->cidade?->estado?->uf }}</p>
                                        </td>

                                        <!-- Total de Sensores -->
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-athens-gray-700 dark:text-athens-gray-300">
                                            {{ $totalEstacoesCluster }} estações (1 Matriz + {{ $m->total_satelites }} Satélites)
                                        </td>

                                        <!-- Status da Matriz -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($m->status_instalacao === 'Instalada')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    Instalada
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-tahiti-gold-50 dark:bg-tahiti-gold-950/50 text-tahiti-gold-700 dark:text-tahiti-gold-300 border border-tahiti-gold-200 dark:border-tahiti-gold-800">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-tahiti-gold-500"></span>
                                                    Pendente
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Barra de Progresso -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="w-44 space-y-1.5">
                                                <div class="flex justify-between text-xs font-semibold">
                                                    <span class="{{ $completa ? 'text-emerald-700 dark:text-emerald-400' : 'text-blue-dianne-900 dark:text-white' }}">{{ $instaladasCluster }} de {{ $totalEstacoesCluster }} instaladas</span>
                                                    <span class="text-athens-gray-500 dark:text-athens-gray-400">{{ $porcentagem }}%</span>
                                                </div>
                                                <div class="w-full bg-athens-gray-200 dark:bg-athens-gray-700 rounded-full h-2 overflow-hidden">
                                                    <div class="h-2 rounded-full transition-all duration-500 {{ $completa ? 'bg-emerald-500' : 'bg-blue-dianne-600' }}" style="width: {{ $porcentagem }}%"></div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Ação -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <a href="{{ route('instalacoes.show', $m->public_id) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-dianne-600 dark:text-blue-dianne-300 hover:text-blue-dianne-800 dark:hover:text-blue-dianne-200 bg-blue-dianne-50 dark:bg-blue-dianne-950/60 hover:bg-blue-dianne-100 dark:hover:bg-blue-dianne-900/60 border border-transparent dark:border-blue-dianne-800 px-3.5 py-2 rounded-lg transition">
                                                <x-heroicon-o-clipboard-document-check class="w-4 h-4" />
                                                Abrir Roteiro
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-athens-gray-400 dark:text-athens-gray-500 bg-white dark:bg-athens-gray-900">
                                            <x-heroicon-o-clipboard-document-list class="w-12 h-12 mx-auto mb-3 text-athens-gray-300 dark:text-athens-gray-600" />
                                            <p class="font-medium text-base text-athens-gray-600 dark:text-athens-gray-300">Nenhuma ordem de instalação gerada.</p>
                                            <p class="text-xs mt-1">Utilize o Planejador de Malha para posicionar a Matriz e gerar as ordens automaticamente.</p>
                                            <a href="{{ route('estacoes.planejar') }}" class="inline-flex items-center gap-1.5 mt-4 text-xs font-semibold bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white py-2 px-4 rounded-lg transition shadow-sm">
                                                <x-heroicon-o-plus class="w-4 h-4" />
                                                Planejar Primeira Malha
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($matrizes->hasPages())
                        <div class="px-6 py-4 border-t border-athens-gray-200 dark:border-athens-gray-800 bg-athens-gray-50 dark:bg-athens-gray-900">
                            {{ $matrizes->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </main>
    </div>
</x-layouts.app>

