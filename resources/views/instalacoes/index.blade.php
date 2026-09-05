<x-layouts.app title="Ordens de Instalação - OpenAir Metrics">
    <div class="flex h-full w-full bg-athens-gray-50">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="instalacoes" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Alertas -->
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

                <!-- Cabeçalho -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-blue-dianne-950 tracking-tight">Ordens de Instalação de Estações</h1>
                        <p class="text-sm text-athens-gray-600 mt-1">Roteiros sequenciais para instalação e vinculação física de MAC Address em campo.</p>
                    </div>

                    <a href="{{ route('estacoes.planejar') }}" class="inline-flex items-center justify-center gap-2 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold py-2.5 px-5 rounded-lg transition-all shadow-sm hover:shadow text-sm cursor-pointer">
                        <x-heroicon-o-plus class="w-5 h-5" />
                        Planejar Nova Malha
                    </a>
                </div>

                <!-- Tabela de Malhas e Ordens de Instalação -->
                <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-athens-gray-700">
                            <thead class="bg-athens-gray-50 border-b border-athens-gray-200 text-xs font-semibold uppercase text-athens-gray-500 tracking-wider">
                                <tr>
                                    <th scope="col" class="px-6 py-3.5">Estação Matriz (Cluster)</th>
                                    <th scope="col" class="px-6 py-3.5">Localização / Bairro</th>
                                    <th scope="col" class="px-6 py-3.5">Total de Sensores</th>
                                    <th scope="col" class="px-6 py-3.5">Status da Matriz</th>
                                    <th scope="col" class="px-6 py-3.5">Progresso da Instalação</th>
                                    <th scope="col" class="px-6 py-3.5 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-athens-gray-200">
                                @forelse ($matrizes as $m)
                                    @php
                                        $totalEstacoesCluster = $m->total_satelites + 1;
                                        $instaladasCluster = ($m->status_instalacao === 'Instalada' ? 1 : 0) + $m->instaladas_satelites;
                                        $porcentagem = $totalEstacoesCluster > 0 ? round(($instaladasCluster / $totalEstacoesCluster) * 100) : 0;
                                        $completa = $porcentagem === 100;
                                    @endphp
                                    <tr class="hover:bg-athens-gray-50/60 transition">
                                        <!-- Identificação da Matriz -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <div class="p-2 bg-dodger-blue-50 text-dodger-blue-600 rounded-lg">
                                                    <x-heroicon-o-cpu-chip class="w-5 h-5" />
                                                </div>
                                                <div>
                                                    <span class="font-bold text-blue-dianne-950 block">Matriz #{{ $m->ordem_instalacao ?? 1 }}</span>
                                                    @if ($m->mac_address)
                                                        <span class="font-mono text-xs text-athens-gray-500">{{ $m->mac_address }}</span>
                                                    @else
                                                        <span class="text-xs text-amber-600 font-medium">MAC Pendente</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Localidade -->
                                        <td class="px-6 py-4">
                                            <p class="font-medium text-blue-dianne-900">{{ $m->bairro_nome ?? $m->bairro?->nome }}</p>
                                            <p class="text-xs text-athens-gray-500">{{ $m->cidade_nome ?? $m->bairro?->cidade?->nome }} - {{ $m->estado_uf ?? $m->bairro?->cidade?->estado?->uf }}</p>
                                        </td>

                                        <!-- Total de Sensores -->
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-athens-gray-700">
                                            {{ $totalEstacoesCluster }} estações (1 Matriz + {{ $m->total_satelites }} Satélites)
                                        </td>

                                        <!-- Status da Matriz -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if ($m->status_instalacao === 'Instalada')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                    Instalada
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                                    Pendente
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Barra de Progresso -->
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="w-44 space-y-1.5">
                                                <div class="flex justify-between text-xs font-semibold">
                                                    <span class="{{ $completa ? 'text-emerald-700' : 'text-blue-dianne-900' }}">{{ $instaladasCluster }} de {{ $totalEstacoesCluster }} instaladas</span>
                                                    <span class="text-athens-gray-500">{{ $porcentagem }}%</span>
                                                </div>
                                                <div class="w-full bg-athens-gray-200 rounded-full h-2 overflow-hidden">
                                                    <div class="h-2 rounded-full transition-all duration-500 {{ $completa ? 'bg-emerald-500' : 'bg-blue-dianne-600' }}" style="width: {{ $porcentagem }}%"></div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Ação -->
                                        <td class="px-6 py-4 whitespace-nowrap text-right">
                                            <a href="{{ route('instalacoes.show', $m->public_id) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-dianne-600 hover:text-blue-dianne-800 bg-blue-dianne-50 hover:bg-blue-dianne-100 px-3.5 py-2 rounded-lg transition">
                                                <x-heroicon-o-clipboard-document-check class="w-4 h-4" />
                                                Abrir Roteiro
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-athens-gray-400">
                                            <x-heroicon-o-clipboard-document-list class="w-12 h-12 mx-auto mb-3 text-athens-gray-300" />
                                            <p class="font-medium text-base text-athens-gray-600">Nenhuma ordem de instalação gerada.</p>
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
                        <div class="px-6 py-4 border-t border-athens-gray-200 bg-athens-gray-50">
                            {{ $matrizes->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </main>
    </div>
</x-layouts.app>

