<x-layouts.app title="Minhas Estações - OpenAir Metrics">
    <div class="flex flex-col md:flex-row h-full w-full bg-athens-gray-50 dark:bg-athens-gray-950 transition-colors duration-200">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="estacoes" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto min-h-0 p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Alerta de Sucesso -->
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

                <!-- Alerta de Erro -->
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
                        <h1 class="text-2xl font-bold text-blue-dianne-950 dark:text-white tracking-tight">Minhas Estações</h1>
                        <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mt-1">Gerencie a sua rede de sensores IoT (Estações Matrizes e Satélites).</p>
                    </div>

                    <!-- Botões de Ação Primária -->
                    @if(!auth()->user()?->isInstalador())
                    <div class="flex flex-wrap items-center gap-2.5">
                        <a href="{{ route('estacoes.planejar') }}" class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-4 rounded-lg transition-all shadow-sm hover:shadow active:scale-98 text-xs sm:text-sm cursor-pointer">
                            <x-heroicon-o-sparkles class="w-4 h-4" />
                            Planejar Malha (Automático)
                        </a>
                        <a href="{{ route('estacoes.create') }}" class="inline-flex items-center justify-center gap-2 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold py-2.5 px-4 rounded-lg transition-all shadow-sm hover:shadow active:scale-98 text-xs sm:text-sm cursor-pointer">
                            <x-heroicon-o-plus class="w-4 h-4" />
                            Cadastrar Nova Estação
                        </a>
                    </div>
                    @endif
                </div>

                <!-- Cards de Resumo Rápido -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-5 flex items-center gap-4 transition-colors">
                        <div class="p-3 bg-blue-dianne-50 dark:bg-blue-dianne-950/50 rounded-lg text-blue-dianne-600 dark:text-blue-dianne-400">
                            <x-heroicon-o-signal class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400 uppercase tracking-wide">Total de Estações</p>
                            <p class="text-2xl font-bold text-blue-dianne-950 dark:text-white">{{ $estacoes->count() }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-5 flex items-center gap-4 transition-colors">
                        <div class="p-3 bg-dodger-blue-50 dark:bg-dodger-blue-950/50 rounded-lg text-dodger-blue-600 dark:text-dodger-blue-400">
                            <x-heroicon-o-cpu-chip class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400 uppercase tracking-wide">Estações Matrizes</p>
                            <p class="text-2xl font-bold text-blue-dianne-950 dark:text-white">{{ $estacoes->where('tipo_estacao', 'Estação Matriz')->count() }}</p>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-5 flex items-center gap-4 transition-colors">
                        <div class="p-3 bg-spindle-50 dark:bg-spindle-950/50 rounded-lg text-spindle-600 dark:text-spindle-400">
                            <x-heroicon-o-radio class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400 uppercase tracking-wide">Estações Satélites</p>
                            <p class="text-2xl font-bold text-blue-dianne-950 dark:text-white">{{ $estacoes->where('tipo_estacao', 'Estação Satélite')->count() }}</p>
                        </div>
                    </div>
                </div>

                <!-- Barra de Busca, Filtros e Ordenação -->
                <div class="bg-white dark:bg-athens-gray-900 p-4 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 transition-colors">
                    <form method="GET" action="{{ route('estacoes.index') }}" class="flex flex-col sm:flex-row flex-wrap items-center gap-3">
                        <!-- Busca Textual -->
                        <div class="relative flex-1 min-w-[220px] w-full">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-athens-gray-400" />
                            <input 
                                type="text" 
                                name="busca" 
                                value="{{ $busca ?? '' }}" 
                                placeholder="Buscar por MAC, Patrimônio, Logradouro, Bairro..." 
                                class="w-full pl-10 pr-4 py-2 border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg text-xs focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                            >
                        </div>

                        <!-- Filtro Tipo -->
                        <div class="w-full sm:w-auto">
                            <select name="tipo" onchange="this.form.submit()" class="w-full sm:w-auto border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 rounded-lg px-3 py-2 text-xs text-athens-gray-700 dark:text-athens-gray-200 focus:ring-2 focus:ring-blue-dianne-500">
                                <option value="">Todos os Tipos</option>
                                <option value="Estação Matriz" @selected(($tipo ?? '') === 'Estação Matriz')>Estação Matriz</option>
                                <option value="Estação Satélite" @selected(($tipo ?? '') === 'Estação Satélite')>Estação Satélite</option>
                            </select>
                        </div>

                        <!-- Filtro Status -->
                        <div class="w-full sm:w-auto">
                            <select name="status" onchange="this.form.submit()" class="w-full sm:w-auto border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 rounded-lg px-3 py-2 text-xs text-athens-gray-700 dark:text-athens-gray-200 focus:ring-2 focus:ring-blue-dianne-500">
                                <option value="">Todos os Status</option>
                                <option value="instalada" @selected(($status ?? '') === 'instalada')>Instalada</option>
                                <option value="pendente" @selected(($status ?? '') === 'pendente')>Pendente de Instalação</option>
                                <option value="substituicao" @selected(($status ?? '') === 'substituicao')>Substituição Solicitada</option>
                            </select>
                        </div>

                        <!-- Ordenação -->
                        <div class="w-full sm:w-auto flex items-center gap-1.5">
                            <select name="sort" onchange="this.form.submit()" class="w-full sm:w-auto border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 rounded-lg px-3 py-2 text-xs text-athens-gray-700 dark:text-athens-gray-200 focus:ring-2 focus:ring-blue-dianne-500">
                                <option value="created_at" @selected(($sort ?? '') === 'created_at')>Mais Recentes</option>
                                <option value="identificacao" @selected(($sort ?? '') === 'identificacao')>MAC / Identificação</option>
                                <option value="tipo" @selected(($sort ?? '') === 'tipo')>Tipo de Estação</option>
                                <option value="localidade" @selected(($sort ?? '') === 'localidade')>Logradouro</option>
                                <option value="vida_util" @selected(($sort ?? '') === 'vida_util')>Vida Útil</option>
                                <option value="instalacao" @selected(($sort ?? '') === 'instalacao')>Data de Instalação</option>
                            </select>

                            <input type="hidden" name="direction" value="{{ ($direction ?? 'desc') === 'asc' ? 'asc' : 'desc' }}">
                            <button 
                                type="button" 
                                onclick="this.form.direction.value = this.form.direction.value === 'asc' ? 'desc' : 'asc'; this.form.submit();"
                                title="Alternar ordem crescente/decrescente"
                                class="p-2 border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-700 dark:text-athens-gray-200 rounded-lg hover:bg-athens-gray-50 dark:hover:bg-athens-gray-700 transition cursor-pointer"
                            >
                                @if(($direction ?? 'desc') === 'asc')
                                    <x-heroicon-o-bars-arrow-up class="w-4 h-4 text-blue-dianne-600 dark:text-blue-dianne-400" />
                                @else
                                    <x-heroicon-o-bars-arrow-down class="w-4 h-4 text-blue-dianne-600 dark:text-blue-dianne-400" />
                                @endif
                            </button>
                        </div>

                        <button type="submit" class="w-full sm:w-auto bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold px-4 py-2 rounded-lg text-xs transition cursor-pointer">
                            Filtrar
                        </button>

                        @if(!empty($busca) || !empty($tipo) || !empty($status) || ($sort ?? '') !== 'created_at')
                            <a href="{{ route('estacoes.index') }}" class="text-xs text-cinnabar-600 dark:text-cinnabar-400 hover:underline font-medium">
                                Limpar
                            </a>
                        @endif
                    </form>
                </div>

                <!-- Tabela de Listagem de Estações -->
                <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 overflow-hidden transition-colors">
                    <div class="px-6 py-5 border-b border-athens-gray-200 dark:border-athens-gray-800 flex justify-between items-center bg-athens-gray-50/50 dark:bg-athens-gray-900/50">
                        <h3 class="text-base font-bold text-blue-dianne-950 dark:text-white">Estações Cadastradas</h3>
                        <span class="text-xs text-athens-gray-500 dark:text-athens-gray-400 font-medium">{{ $estacoes->count() }} {{ $estacoes->count() === 1 ? 'estação listada' : 'estações listadas' }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-athens-gray-50 dark:bg-athens-gray-800/60 border-b border-athens-gray-200 dark:border-athens-gray-800 text-xs uppercase tracking-wider text-athens-gray-600 dark:text-athens-gray-300">
                                    <th class="px-6 py-3.5 font-bold">
                                        <a href="{{ route('estacoes.index', array_merge(request()->all(), ['sort' => 'identificacao', 'direction' => ($sort ?? '') === 'identificacao' && ($direction ?? '') === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-blue-dianne-400 transition">
                                            Identificação / Patrimônio
                                            @if(($sort ?? '') === 'identificacao')
                                                <span>{{ ($direction ?? '') === 'asc' ? '▲' : '▼' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-6 py-3.5 font-bold">
                                        <a href="{{ route('estacoes.index', array_merge(request()->all(), ['sort' => 'tipo', 'direction' => ($sort ?? '') === 'tipo' && ($direction ?? '') === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-blue-dianne-400 transition">
                                            Tipo
                                            @if(($sort ?? '') === 'tipo')
                                                <span>{{ ($direction ?? '') === 'asc' ? '▲' : '▼' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-6 py-3.5 font-bold">
                                        <a href="{{ route('estacoes.index', array_merge(request()->all(), ['sort' => 'localidade', 'direction' => ($sort ?? '') === 'localidade' && ($direction ?? '') === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-blue-dianne-400 transition">
                                            Localidade (IBGE)
                                            @if(($sort ?? '') === 'localidade')
                                                <span>{{ ($direction ?? '') === 'asc' ? '▲' : '▼' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-6 py-3.5 font-bold">
                                        <a href="{{ route('estacoes.index', array_merge(request()->all(), ['sort' => 'vida_util', 'direction' => ($sort ?? '') === 'vida_util' && ($direction ?? '') === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-blue-dianne-400 transition">
                                            Vida Útil (Sensores)
                                            @if(($sort ?? '') === 'vida_util')
                                                <span>{{ ($direction ?? '') === 'asc' ? '▲' : '▼' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-6 py-3.5 font-bold">
                                        <a href="{{ route('estacoes.index', array_merge(request()->all(), ['sort' => 'instalacao', 'direction' => ($sort ?? '') === 'instalacao' && ($direction ?? '') === 'asc' ? 'desc' : 'asc'])) }}" class="inline-flex items-center gap-1 hover:text-blue-dianne-600 dark:hover:text-blue-dianne-400 transition">
                                            Instalação
                                            @if(($sort ?? '') === 'instalacao')
                                                <span>{{ ($direction ?? '') === 'asc' ? '▲' : '▼' }}</span>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-6 py-3.5 font-bold text-center">Substituição</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-athens-gray-200 dark:divide-athens-gray-800 text-sm">
                                @forelse ($estacoes as $estacao)
                                    @php
                                        $vida = $estacao->calcularVidaUtil();
                                        $identificadorExibicao = $estacao->patrimonio?->numero_patrimonio 
                                            ? $estacao->patrimonio->numero_patrimonio 
                                            : ($estacao->mac_address ? $estacao->mac_address : '#' . $estacao->private_id);
                                    @endphp
                                    <tr class="hover:bg-athens-gray-50 dark:hover:bg-athens-gray-800/50 transition-colors">
                                        <!-- Identificação, Patrimônio e MAC -->
                                        <td class="px-6 py-4">
                                            @if($estacao->patrimonio?->numero_patrimonio)
                                                <div class="font-mono text-xs font-bold text-blue-dianne-950 dark:text-blue-dianne-200 bg-blue-dianne-50 dark:bg-blue-dianne-950/60 px-2 py-0.5 rounded border border-blue-dianne-200 dark:border-blue-dianne-800 inline-block mb-1">
                                                    {{ $estacao->patrimonio->numero_patrimonio }}
                                                </div>
                                            @endif
                                            <div class="flex items-center gap-2">
                                                <span class="font-mono text-xs font-semibold text-athens-gray-700 dark:text-athens-gray-300 bg-athens-gray-100 dark:bg-athens-gray-800 px-2 py-0.5 rounded border border-athens-gray-300 dark:border-athens-gray-700">
                                                    MAC: {{ $estacao->mac_address ?? 'Pendente de Instalação' }}
                                                </span>
                                            </div>
                                            <span class="text-[10px] text-athens-gray-400 font-mono block mt-1" title="UUID Público">{{ $estacao->public_id }}</span>
                                        </td>

                                        <!-- Tipo de Estação -->
                                        <td class="px-6 py-4">
                                            @if ($estacao->tipo_estacao === 'Estação Matriz')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-dodger-blue-50 dark:bg-dodger-blue-950/50 text-dodger-blue-700 dark:text-dodger-blue-300 border border-dodger-blue-200 dark:border-dodger-blue-800">
                                                    <x-heroicon-o-cpu-chip class="w-3.5 h-3.5 text-dodger-blue-600 dark:text-dodger-blue-400" />
                                                    Estação Matriz
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-spindle-50 dark:bg-spindle-950/50 text-spindle-700 dark:text-spindle-300 border border-spindle-200 dark:border-spindle-800">
                                                    <x-heroicon-o-radio class="w-3.5 h-3.5 text-spindle-600 dark:text-spindle-400" />
                                                    Estação Satélite
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Localidade e Endereço Estruturado -->
                                        <td class="px-6 py-4">
                                            @if ($estacao->logradouro)
                                                <p class="font-bold text-sm text-blue-dianne-950 dark:text-white flex items-center gap-1.5">
                                                    <x-heroicon-o-map-pin class="w-4 h-4 text-blue-dianne-600 dark:text-blue-dianne-400 shrink-0" />
                                                    <span>{{ $estacao->logradouro }}{{ $estacao->numero ? ', ' . $estacao->numero : '' }}</span>
                                                </p>
                                                <p class="text-xs text-athens-gray-600 dark:text-athens-gray-400 mt-0.5 ml-5.5">
                                                    {{ $estacao->bairro_nome ?? $estacao->bairro?->nome }}, {{ $estacao->cidade_nome ?? $estacao->bairro?->cidade?->nome }} - {{ $estacao->estado_uf ?? $estacao->bairro?->cidade?->estado?->uf }}
                                                </p>
                                                @if ($estacao->cep)
                                                    <p class="text-[11px] text-athens-gray-500 dark:text-athens-gray-400 font-mono mt-0.5 ml-5.5">
                                                        CEP: {{ $estacao->cep }}
                                                    </p>
                                                @endif
                                            @elseif ($estacao->bairro)
                                                <p class="font-medium text-blue-dianne-950 dark:text-white">{{ $estacao->bairro->nome }}</p>
                                                <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400">{{ $estacao->bairro->cidade?->nome }} - {{ $estacao->bairro->cidade?->estado?->uf }}</p>
                                            @else
                                                <span class="text-xs text-athens-gray-400">Não informado</span>
                                            @endif
                                        </td>

                                        <!-- Vida Útil dos Sensores (Base técnica 5 anos) -->
                                        <td class="px-6 py-4">
                                            @if($vida['instalada'])
                                                <div class="space-y-1.5 min-w-[150px]">
                                                    <div class="flex items-center justify-between text-xs">
                                                        <span class="font-bold text-blue-dianne-950 dark:text-white">{{ $vida['porcentagem_restante'] }}% vida útil</span>
                                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $vida['badge_class'] }}">
                                                            {{ $vida['status'] }}
                                                        </span>
                                                    </div>
                                                    <div class="w-full bg-athens-gray-200 dark:bg-athens-gray-700 rounded-full h-1.5 overflow-hidden">
                                                        <div class="h-1.5 rounded-full transition-all duration-500 @if($vida['dias_restantes'] <= 0) bg-cinnabar-600 @elseif($vida['dias_restantes'] <= 180) bg-tahiti-gold-500 @else bg-emerald-500 @endif" style="width: {{ $vida['porcentagem_restante'] }}%"></div>
                                                    </div>
                                                    <div class="flex items-center justify-between text-[10px] text-athens-gray-500 dark:text-athens-gray-400">
                                                        <span>Nominal: 5 anos</span>
                                                        <span>{{ $vida['dias_restantes'] > 0 ? $vida['dias_restantes'] . ' dias rest.' : 'Expirada' }}</span>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-xs text-athens-gray-600 dark:text-athens-gray-300 bg-athens-gray-50 dark:bg-athens-gray-800 border border-athens-gray-200 dark:border-athens-gray-700 px-2.5 py-1 rounded-full">
                                                    <x-heroicon-o-clock class="w-3.5 h-3.5 text-athens-gray-400" />
                                                    Não Instalada
                                                </span>
                                            @endif
                                        </td>

                                        <!-- Data de Instalação / Cadastro -->
                                        <td class="px-6 py-4 text-xs text-athens-gray-600 dark:text-athens-gray-400">
                                            @if($estacao->data_instalacao)
                                                <span class="font-semibold text-emerald-700 dark:text-emerald-400 block">Instalada em:</span>
                                                <span>{{ $estacao->data_instalacao->format('d/m/Y') }}</span>
                                            @else
                                                <span class="text-athens-gray-400">Pendente de instalação</span>
                                            @endif
                                        </td>

                                        <!-- Substituição / Ações -->
                                        <td class="px-6 py-4 text-center">
                                            @if ($estacao->solicitacao_substituicao)
                                                <div class="inline-flex flex-col items-center">
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-tahiti-gold-100 dark:bg-tahiti-gold-950/60 text-tahiti-gold-900 dark:text-tahiti-gold-300 border border-tahiti-gold-300 dark:border-tahiti-gold-700" title="{{ $estacao->motivo_substituicao }}">
                                                        <x-heroicon-o-arrow-path class="w-3.5 h-3.5 text-tahiti-gold-700 dark:text-tahiti-gold-400" />
                                                        Substituição Solicitada
                                                    </span>
                                                    @if($estacao->solicitacao_substituicao_em)
                                                        <span class="text-[10px] text-athens-gray-500 dark:text-athens-gray-400 mt-0.5">
                                                              em {{ $estacao->solicitacao_substituicao_em->format('d/m/Y') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @elseif ($vida['instalada'] && Auth::user() && (Auth::user()->isAdministrador() || Auth::user()->isCadastrador() || Auth::user()->isPlanejador()))
                                                <button 
                                                    type="button" 
                                                    onclick="abrirModalSubstituicao('{{ $estacao->public_id }}', '{{ $identificadorExibicao }}', {{ $vida['porcentagem_restante'] }})"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-tahiti-gold-700 dark:text-tahiti-gold-300 bg-tahiti-gold-50 dark:bg-tahiti-gold-950/50 hover:bg-tahiti-gold-100 dark:hover:bg-tahiti-gold-900/50 border border-tahiti-gold-300 dark:border-tahiti-gold-700 rounded-lg transition shadow-xs cursor-pointer"
                                                >
                                                    <x-heroicon-o-arrow-path class="w-3.5 h-3.5 text-tahiti-gold-600 dark:text-tahiti-gold-400" />
                                                    Solicitar Substituição
                                                </button>
                                            @else
                                                <span class="text-xs text-athens-gray-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center bg-white dark:bg-athens-gray-900">
                                            <div class="max-w-sm mx-auto flex flex-col items-center">
                                                <div class="p-4 bg-athens-gray-100 dark:bg-athens-gray-800 rounded-full text-athens-gray-400 mb-3">
                                                    <x-heroicon-o-signal-slash class="w-10 h-10" />
                                                </div>
                                                <h4 class="text-base font-bold text-blue-dianne-950 dark:text-white mb-1">Nenhuma estação encontrada</h4>
                                                <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mb-5">Nenhum registro corresponde aos filtros selecionados.</p>
                                                 @if(!auth()->user()?->isInstalador())
                                                 <a href="{{ route('estacoes.create') }}" class="inline-flex items-center gap-2 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white text-xs font-semibold py-2 px-4 rounded-lg transition-colors shadow-sm">
                                                     <x-heroicon-o-plus class="w-4 h-4" />
                                                     Cadastrar Nova Estação
                                                 </a>
                                                 @endif
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

    <!-- Modal de Confirmação de Substituição de Sensores -->
    <div id="modal-substituicao" class="fixed inset-0 z-50 bg-blue-dianne-950/40 backdrop-blur-xs flex items-center justify-center p-4 hidden">
        <div class="bg-white dark:bg-athens-gray-900 rounded-2xl shadow-xl border border-athens-gray-200 dark:border-athens-gray-800 max-w-md w-full p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-athens-gray-100 dark:border-athens-gray-800">
                <h3 class="text-base font-bold text-blue-dianne-950 dark:text-white flex items-center gap-2">
                    <x-heroicon-o-arrow-path class="w-5 h-5 text-tahiti-gold-600 dark:text-tahiti-gold-400" />
                    Solicitar Substituição de Sensores
                </h3>
                <button type="button" onclick="fecharModalSubstituicao()" class="text-athens-gray-400 hover:text-athens-gray-600 dark:hover:text-athens-gray-300 cursor-pointer">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>

            <p class="text-xs text-athens-gray-600 dark:text-athens-gray-300 leading-relaxed">
                Você está solicitando a substituição dos sensores da estação <strong id="modal-estacao-identificador" class="text-blue-dianne-950 dark:text-white font-mono font-bold"></strong>. 
                Isso sinalizará os instaladores e abrirá uma ordem técnica para troca física do hardware.
            </p>

            <form id="form-substituicao" method="POST" action="" class="space-y-4">
                @csrf
                <div>
                    <label for="motivo_substituicao" id="motivo-substituicao-label" class="block text-xs font-bold text-blue-dianne-950 dark:text-athens-gray-200 uppercase tracking-wider mb-1">
                        Motivo da Solicitação
                    </label>
                    <textarea 
                        name="motivo_substituicao" 
                        id="motivo_substituicao" 
                        rows="3" 
                        placeholder="Ex: Ciclo de vida nominal atingido, descalibração ou leitura inconsistente..."
                        class="w-full text-xs p-3 border border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="fecharModalSubstituicao()" class="px-4 py-2 text-xs font-semibold text-athens-gray-600 dark:text-athens-gray-300 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 rounded-lg transition cursor-pointer">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-tahiti-gold-600 hover:bg-tahiti-gold-700 text-white font-semibold text-xs rounded-lg shadow-sm transition flex items-center gap-1.5 cursor-pointer">
                        <x-heroicon-o-check class="w-4 h-4" />
                        Confirmar Troca
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function abrirModalSubstituicao(publicId, identificador, porcentagemVida) {
            const modal = document.getElementById('modal-substituicao');
            const form = document.getElementById('form-substituicao');
            const identSpan = document.getElementById('modal-estacao-identificador');
            const motivoTextarea = document.getElementById('motivo_substituicao');
            const motivoLabel = document.getElementById('motivo-substituicao-label');

            identSpan.textContent = identificador;
            form.action = `/estacoes/${publicId}/solicitar-substituicao`;

            if (porcentagemVida > 20) {
                motivoTextarea.required = true;
                motivoTextarea.value = '';
                motivoTextarea.placeholder = 'Descreva detalhadamente o motivo da solicitação preventiva (obrigatório quando vida útil > 20%)...';
                motivoLabel.innerHTML = 'Motivo da Solicitação <span class="text-cinnabar-600 dark:text-cinnabar-400 font-bold">* (Obrigatório - vida útil > 20%)</span>';
            } else {
                motivoTextarea.required = false;
                motivoTextarea.value = 'Fim da vida útil da estação';
                motivoLabel.innerHTML = 'Motivo da Solicitação <span class="text-emerald-600 dark:text-emerald-400 font-bold">(Preenchido automaticamente)</span>';
            }

            modal.classList.remove('hidden');
        }

        function fecharModalSubstituicao() {
            const modal = document.getElementById('modal-substituicao');
            modal.classList.add('hidden');
        }
    </script>
    @endpush
</x-layouts.app>

