<x-layouts.app title="Minhas Estações - OpenAir Metrics">
    <div class="flex h-full w-full bg-athens-gray-50">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="estacoes" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Alerta de Sucesso -->
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

                <!-- Cabeçalho da Página com Ação Primária -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-blue-dianne-950 tracking-tight">Minhas Estações</h1>
                        <p class="text-sm text-athens-gray-600 mt-1">Gerencie a sua rede de sensores IoT (Estações Matrizes e Satélites).</p>
                    </div>

                    <!-- Botão de Ação Primária -->
                    <a href="{{ route('estacoes.create') }}" class="inline-flex items-center justify-center gap-2 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold py-2.5 px-5 rounded-lg transition-all shadow-sm hover:shadow active:scale-98 text-sm cursor-pointer">
                        <x-heroicon-o-plus class="w-5 h-5" />
                        Cadastrar Nova Estação
                    </a>
                </div>

                <!-- Cards de Resumo Rápido -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 flex items-center gap-4">
                        <div class="p-3 bg-blue-dianne-50 rounded-lg text-blue-dianne-600">
                            <x-heroicon-o-signal class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 uppercase tracking-wide">Total de Estações</p>
                            <p class="text-2xl font-bold text-blue-dianne-950">{{ $estacoes->count() }}</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 flex items-center gap-4">
                        <div class="p-3 bg-dodger-blue-50 rounded-lg text-dodger-blue-600">
                            <x-heroicon-o-cpu-chip class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 uppercase tracking-wide">Estações Matrizes</p>
                            <p class="text-2xl font-bold text-blue-dianne-950">{{ $estacoes->where('tipo_estacao', 'Estação Matriz')->count() }}</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 flex items-center gap-4">
                        <div class="p-3 bg-purple-50 rounded-lg text-purple-600">
                            <x-heroicon-o-radio class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 uppercase tracking-wide">Estações Satélites</p>
                            <p class="text-2xl font-bold text-blue-dianne-950">{{ $estacoes->where('tipo_estacao', 'Estação Satélite')->count() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tabela de Listagem de Estações -->
                <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-athens-gray-200 flex justify-between items-center bg-athens-gray-50/50">
                        <h3 class="text-base font-bold text-blue-dianne-950">Estações Cadastradas</h3>
                        <span class="text-xs text-athens-gray-500 font-medium">{{ $estacoes->count() }} {{ $estacoes->count() === 1 ? 'estação vinculada' : 'estações vinculadas' }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-athens-gray-50 border-b border-athens-gray-200 text-xs uppercase tracking-wider text-athens-gray-500">
                                    <th class="px-6 py-3.5 font-bold">Endereço MAC</th>
                                    <th class="px-6 py-3.5 font-bold">Tipo</th>
                                    <th class="px-6 py-3.5 font-bold">Localidade (IBGE)</th>
                                    <th class="px-6 py-3.5 font-bold">Coordenadas</th>
                                    <th class="px-6 py-3.5 font-bold">Cadastro</th>
                                    <th class="px-6 py-3.5 font-bold text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-athens-gray-200 text-sm">
                                @forelse ($estacoes as $estacao)
                                    <tr class="hover:bg-athens-gray-50 transition-colors">
                                        <!-- MAC Address e UUID -->
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono text-xs font-bold text-blue-dianne-950 bg-athens-gray-100 px-2 py-1 rounded border border-athens-gray-300">{{ $estacao->mac_address ?? 'N/A' }}</span>
                                            </div>
                                            <span class="text-[10px] text-athens-gray-400 font-mono block mt-0.5" title="UUID Público">{{ $estacao->public_id }}</span>
                                        </td>

                                        <!-- Tipo de Estação -->
                                        <td class="px-6 py-4">
                                            @if ($estacao->tipo_estacao === 'Estação Matriz')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-dodger-blue-50 text-dodger-blue-700 border border-dodger-blue-200">
                                                    <x-heroicon-o-cpu-chip class="w-3.5 h-3.5 text-dodger-blue-600" />
                                                    Estação Matriz
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                                    <x-heroicon-o-radio class="w-3.5 h-3.5 text-purple-600" />
                                                    Estação Satélite
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Localidade e Endereço Estruturado -->
                                        <td class="px-6 py-4">
                                            @if ($estacao->logradouro)
                                                <p class="font-bold text-sm text-blue-dianne-950 flex items-center gap-1.5">
                                                    <x-heroicon-o-map-pin class="w-4 h-4 text-blue-dianne-600 shrink-0" />
                                                    <span>{{ $estacao->logradouro }}{{ $estacao->numero ? ', ' . $estacao->numero : '' }}</span>
                                                </p>
                                                <p class="text-xs text-athens-gray-600 mt-0.5 ml-5.5">
                                                    {{ $estacao->bairro_nome ?? $estacao->bairro?->nome }}, {{ $estacao->cidade_nome ?? $estacao->bairro?->cidade?->nome }} - {{ $estacao->estado_uf ?? $estacao->bairro?->cidade?->estado?->uf }}
                                                </p>
                                                @if ($estacao->cep)
                                                    <p class="text-[11px] text-athens-gray-500 font-mono mt-0.5 ml-5.5">
                                                        CEP: {{ $estacao->cep }}
                                                    </p>
                                                @endif
                                            @elseif ($estacao->bairro)
                                                <p class="font-medium text-blue-dianne-950">{{ $estacao->bairro->nome }}</p>
                                                <p class="text-xs text-athens-gray-500">{{ $estacao->bairro->cidade?->nome }} - {{ $estacao->bairro->cidade?->estado?->uf }}</p>
                                                @if ($estacao->endereco)
                                                    <p class="text-xs text-blue-dianne-600 mt-1 flex items-center gap-1 font-medium">
                                                        <x-heroicon-o-map-pin class="w-3.5 h-3.5 text-blue-dianne-500 shrink-0" />
                                                        <span>{{ $estacao->endereco }}</span>
                                                    </p>
                                                @endif
                                            @else
                                                <span class="text-xs text-athens-gray-400">Não informado</span>
                                            @endif
                                        </td>

                                        <!-- Coordenadas -->
                                        <td class="px-6 py-4 font-mono text-xs text-athens-gray-600">
                                            @if ($estacao->latitude !== null && $estacao->longitude !== null)
                                                <div>Lat: <span class="font-medium text-blue-dianne-900">{{ number_format($estacao->latitude, 6) }}</span></div>
                                                <div>Lng: <span class="font-medium text-blue-dianne-900">{{ number_format($estacao->longitude, 6) }}</span></div>
                                            @else
                                                <span class="text-athens-gray-400">Sem coordenadas</span>
                                            @endif
                                        </td>

                                        <!-- Data de Cadastro -->
                                        <td class="px-6 py-4 text-xs text-athens-gray-600">
                                            {{ $estacao->created_at ? $estacao->created_at->format('d/m/Y H:i') : '-' }}
                                        </td>

                                        <!-- Status -->
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Ativo
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center bg-white">
                                            <div class="max-w-sm mx-auto flex flex-col items-center">
                                                <div class="p-4 bg-athens-gray-100 rounded-full text-athens-gray-400 mb-3">
                                                    <x-heroicon-o-signal-slash class="w-10 h-10" />
                                                </div>
                                                <h4 class="text-base font-bold text-blue-dianne-950 mb-1">Nenhuma estação cadastrada</h4>
                                                <p class="text-xs text-athens-gray-500 mb-5">Você ainda não cadastrou nenhuma estação de monitoramento no sistema.</p>
                                                <a href="{{ route('estacoes.create') }}" class="inline-flex items-center gap-2 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white text-xs font-semibold py-2 px-4 rounded-lg transition-colors shadow-sm">
                                                    <x-heroicon-o-plus class="w-4 h-4" />
                                                    Cadastrar Primeira Estação
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>
</x-layouts.app>

