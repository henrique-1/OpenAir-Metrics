<x-layouts.app title="Dashboard - OpenAir Metrics">
    <!-- Container principal responsivo -->
    <div class="flex flex-col md:flex-row h-full w-full bg-athens-gray-50 dark:bg-athens-gray-950 transition-colors duration-200">        
        <!-- Barra de Navegação Lateral (Sidebar) -->
        <x-sidebar active="dashboard" />

        <!-- Área de Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto min-h-0 p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                
                <!-- Título da Página -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-blue-dianne-950 dark:text-white tracking-tight">Painel Analítico de Monitoramento</h1>
                        <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mt-1">Acompanhe as métricas e médias históricas de qualidade do ar por cidade ou bairro.</p>
                    </div>
                    @if(!auth()->user()?->isInstalador())
                        <a href="{{ route('estacoes.create') }}" class="inline-flex items-center gap-2 bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white text-sm font-semibold py-2 px-4 rounded-lg transition-colors shadow-sm self-start sm:self-auto">
                            <x-heroicon-o-plus class="w-4 h-4" />
                            Nova Estação
                        </a>
                    @endif
                </div>

                <!-- Grid de Métricas Gerais da Rede -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- Sensores Ativos -->
                    <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-5 flex items-center gap-4 transition-colors">
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-950/50 rounded-lg text-emerald-500">
                            <x-heroicon-o-cpu-chip class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400 uppercase tracking-wide">Estações Ativas</p>
                            <p class="text-2xl font-bold text-blue-dianne-950 dark:text-white">{{ $totalEstacoes }}</p>
                        </div>
                    </div>

                    <!-- Leituras -->
                    <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-5 flex items-center gap-4 transition-colors">
                        <div class="p-3 bg-dodger-blue-50 dark:bg-dodger-blue-950/50 rounded-lg text-dodger-blue-500">
                            <x-heroicon-o-chart-bar class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400 uppercase tracking-wide">Total de Leituras</p>
                            <p class="text-2xl font-bold text-blue-dianne-950 dark:text-white">{{ number_format($totalLeituras, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    
                    <!-- Alertas IQA -->
                    <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-5 flex items-center gap-4 transition-colors">
                        <div class="p-3 bg-ebony-clay-50 dark:bg-ebony-clay-950/50 rounded-lg text-ebony-clay-600 dark:text-ebony-clay-400">
                            <x-heroicon-o-globe-americas class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400 uppercase tracking-wide">Alertas Qualidade (IQA)</p>
                            <p class="text-2xl font-bold text-blue-dianne-950 dark:text-white">{{ $alertasIqa }}</p>
                        </div>
                    </div>

                    <!-- Alertas de PM -->
                    <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-5 flex items-center gap-4 transition-colors">
                        <div class="p-3 bg-cinnabar-50 dark:bg-cinnabar-950/50 rounded-lg text-cinnabar-500">
                            <x-heroicon-o-shield-exclamation class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400 uppercase tracking-wide">Alertas Particulado</p>
                            <p class="text-2xl font-bold text-blue-dianne-950 dark:text-white">{{ $alertasPm }}</p>
                        </div>
                    </div>
                </div>

                <!-- Painel Principal de Gráficos Analíticos -->
                <div class="bg-white dark:bg-athens-gray-900 rounded-2xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 overflow-hidden transition-colors">
                    
                    <!-- Header do Painel com Filtros de Agrupamento -->
                    <div class="p-6 border-b border-athens-gray-200 dark:border-athens-gray-800 bg-white dark:bg-athens-gray-900">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            
                            <!-- Controles de Localidade (Cidade vs Bairro) -->
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-xs font-bold uppercase tracking-wider text-athens-gray-500 dark:text-athens-gray-400">Agrupar por:</span>
                                
                                <!-- Toggle Cidade / Bairro -->
                                <div class="inline-flex p-1 bg-athens-gray-100 dark:bg-athens-gray-800 rounded-lg border border-athens-gray-200 dark:border-athens-gray-700 text-xs font-semibold">
                                    <button type="button" 
                                        id="btn-tipo-cidade" 
                                        data-tipo="cidade"
                                        data-active-classes="bg-white dark:bg-athens-gray-900 text-blue-dianne-950 dark:text-white shadow-sm font-bold"
                                        data-inactive-classes="text-athens-gray-600 dark:text-athens-gray-400 hover:text-blue-dianne-950 dark:hover:text-white font-normal"
                                        class="btn-tipo px-3 py-1.5 rounded-md transition-all bg-white dark:bg-athens-gray-900 text-blue-dianne-950 dark:text-white shadow-sm font-bold cursor-pointer">
                                        Cidade
                                    </button>
                                    <button type="button" 
                                        id="btn-tipo-bairro" 
                                        data-tipo="bairro"
                                        data-active-classes="bg-white dark:bg-athens-gray-900 text-blue-dianne-950 dark:text-white shadow-sm font-bold"
                                        data-inactive-classes="text-athens-gray-600 dark:text-athens-gray-400 hover:text-blue-dianne-950 dark:hover:text-white font-normal"
                                        class="btn-tipo px-3 py-1.5 rounded-md transition-all text-athens-gray-600 dark:text-athens-gray-400 hover:text-blue-dianne-950 dark:hover:text-white font-normal cursor-pointer">
                                        Bairro
                                    </button>
                                </div>

                                <!-- Select de Cidade -->
                                <div id="container-select-cidade" class="min-w-[220px]">
                                    <select id="select-cidade" class="w-full text-xs font-medium bg-athens-gray-50 dark:bg-athens-gray-800 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg px-3 py-2 text-blue-dianne-950 dark:text-white focus:ring-2 focus:ring-blue-dianne-500 focus:outline-none">
                                        @forelse ($cidades as $cidade)
                                            <option value="{{ $cidade->id }}">{{ $cidade->nome }} - {{ $cidade->estado?->uf }}</option>
                                        @empty
                                            <option value="">Nenhuma cidade com estações</option>
                                        @endforelse
                                    </select>
                                </div>

                                <!-- Select de Bairro (Oculto inicialmente) -->
                                <div id="container-select-bairro" class="min-w-[240px] hidden">
                                    <select id="select-bairro" class="w-full text-xs font-medium bg-athens-gray-50 dark:bg-athens-gray-800 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg px-3 py-2 text-blue-dianne-950 dark:text-white focus:ring-2 focus:ring-blue-dianne-500 focus:outline-none">
                                        @forelse ($bairros as $bairro)
                                            <option value="{{ $bairro->id }}">{{ $bairro->nome }} ({{ $bairro->cidade?->nome }} - {{ $bairro->cidade?->estado?->uf }})</option>
                                        @empty
                                            <option value="">Nenhum bairro com estações</option>
                                        @endforelse
                                    </select>
                                </div>
                            </div>

                            <!-- Filtro de Período -->
                            <div class="flex items-center gap-2 self-start lg:self-auto">
                                <span class="text-xs font-bold uppercase tracking-wider text-athens-gray-500 dark:text-athens-gray-400">Período:</span>
                                <div class="inline-flex p-1 bg-athens-gray-100 dark:bg-athens-gray-800 rounded-lg border border-athens-gray-200 dark:border-athens-gray-700 text-xs font-medium">
                                    <button type="button" 
                                        data-periodo="24h" 
                                        data-active-classes="bg-white dark:bg-athens-gray-900 text-blue-dianne-950 dark:text-white font-bold shadow-sm"
                                        data-inactive-classes="text-athens-gray-600 dark:text-athens-gray-400 hover:text-blue-dianne-950 dark:hover:text-white font-normal"
                                        class="btn-periodo px-2.5 py-1 rounded-md transition-all bg-white dark:bg-athens-gray-900 text-blue-dianne-950 dark:text-white font-bold shadow-sm cursor-pointer">24 Horas</button>
                                    <button type="button" 
                                        data-periodo="7d" 
                                        data-active-classes="bg-white dark:bg-athens-gray-900 text-blue-dianne-950 dark:text-white font-bold shadow-sm"
                                        data-inactive-classes="text-athens-gray-600 dark:text-athens-gray-400 hover:text-blue-dianne-950 dark:hover:text-white font-normal"
                                        class="btn-periodo px-2.5 py-1 rounded-md transition-all text-athens-gray-600 dark:text-athens-gray-400 hover:text-blue-dianne-950 dark:hover:text-white font-normal cursor-pointer">7 Dias</button>
                                    <button type="button" 
                                        data-periodo="30d" 
                                        data-active-classes="bg-white dark:bg-athens-gray-900 text-blue-dianne-950 dark:text-white font-bold shadow-sm"
                                        data-inactive-classes="text-athens-gray-600 dark:text-athens-gray-400 hover:text-blue-dianne-950 dark:hover:text-white font-normal"
                                        class="btn-periodo px-2.5 py-1 rounded-md transition-all text-athens-gray-600 dark:text-athens-gray-400 hover:text-blue-dianne-950 dark:hover:text-white font-normal cursor-pointer">30 Dias</button>
                                </div>
                            </div>
                        </div>

                        <!-- Abas de Seleção de Métricas (Qualidade do Ar, Temp, Umidade, PM, CO2) -->
                        <div class="mt-6 flex flex-wrap gap-2 border-t border-athens-gray-100 dark:border-athens-gray-800 pt-5">
                            <!-- Qualidade do Ar (IQA) -->
                            <button type="button" 
                                data-metrica="qualidade_ar" 
                                data-active-classes="border-emerald-500 dark:border-emerald-500 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-200 shadow-sm font-bold active"
                                data-inactive-classes="border-athens-gray-200 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-50 dark:hover:bg-athens-gray-700 font-semibold"
                                class="btn-metrica flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs transition-all border border-emerald-500 dark:border-emerald-500 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-200 shadow-sm font-bold active cursor-pointer">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                Qualidade do Ar (IQA)
                            </button>

                            <!-- Temperatura -->
                            <button type="button" 
                                data-metrica="temperatura" 
                                data-active-classes="border-tahiti-gold-500 dark:border-tahiti-gold-500 bg-tahiti-gold-50 dark:bg-tahiti-gold-950/60 text-tahiti-gold-900 dark:text-tahiti-gold-200 shadow-sm font-bold active"
                                data-inactive-classes="border-athens-gray-200 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-50 dark:hover:bg-athens-gray-700 font-semibold"
                                class="btn-metrica flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs transition-all border border-athens-gray-200 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-50 dark:hover:bg-athens-gray-700 font-semibold cursor-pointer">
                                <span class="w-2.5 h-2.5 rounded-full bg-tahiti-gold-500"></span>
                                Temperatura (°C)
                            </button>

                            <!-- Umidade Relativa -->
                            <button type="button" 
                                data-metrica="umidade" 
                                data-active-classes="border-dodger-blue-500 dark:border-dodger-blue-500 bg-dodger-blue-50 dark:bg-dodger-blue-950/60 text-dodger-blue-900 dark:text-dodger-blue-200 shadow-sm font-bold active"
                                data-inactive-classes="border-athens-gray-200 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-50 dark:hover:bg-athens-gray-700 font-semibold"
                                class="btn-metrica flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs transition-all border border-athens-gray-200 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-50 dark:hover:bg-athens-gray-700 font-semibold cursor-pointer">
                                <span class="w-2.5 h-2.5 rounded-full bg-dodger-blue-500"></span>
                                Umidade Relativa (%)
                            </button>

                            <!-- Material Particulado -->
                            <button type="button" 
                                data-metrica="poeira" 
                                data-active-classes="border-cinnabar-500 dark:border-cinnabar-500 bg-cinnabar-50 dark:bg-cinnabar-950/60 text-cinnabar-900 dark:text-cinnabar-200 shadow-sm font-bold active"
                                data-inactive-classes="border-athens-gray-200 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-50 dark:hover:bg-athens-gray-700 font-semibold"
                                class="btn-metrica flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs transition-all border border-athens-gray-200 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-50 dark:hover:bg-athens-gray-700 font-semibold cursor-pointer">
                                <span class="w-2.5 h-2.5 rounded-full bg-cinnabar-500"></span>
                                Material Particulado (µg/m³)
                            </button>

                            <!-- CO2 -->
                            <button type="button" 
                                data-metrica="co2" 
                                data-active-classes="border-athens-gray-500 dark:border-athens-gray-400 bg-athens-gray-100 dark:bg-athens-gray-800/90 text-athens-gray-900 dark:text-athens-gray-100 shadow-sm font-bold active"
                                data-inactive-classes="border-athens-gray-200 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-50 dark:hover:bg-athens-gray-700 font-semibold"
                                class="btn-metrica flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs transition-all border border-athens-gray-200 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-50 dark:hover:bg-athens-gray-700 font-semibold cursor-pointer">
                                <span class="w-2.5 h-2.5 rounded-full bg-athens-gray-700 dark:bg-athens-gray-400"></span>
                                Dióxido de Carbono (CO₂)
                            </button>
                        </div>
                    </div>

                    <!-- Cards de Estatísticas em Tempo Real (Média, Máximo, Mínimo e Amostras) -->
                    <div class="p-6 bg-athens-gray-50/50 dark:bg-athens-gray-950/50 border-b border-athens-gray-200 dark:border-athens-gray-800 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        
                        <!-- Média -->
                        <div class="bg-white dark:bg-athens-gray-900 p-4 rounded-xl border border-athens-gray-200 dark:border-athens-gray-800 shadow-2xs">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-athens-gray-500 dark:text-athens-gray-400">Média Calculada</span>
                                <span id="stat-classificacao" class="px-2 py-0.5 text-[11px] font-bold rounded-full border bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800">
                                    Normal
                                </span>
                            </div>
                            <div class="mt-2 flex items-baseline gap-1.5">
                                <span id="stat-media" class="text-2xl font-black text-blue-dianne-950 dark:text-white">--</span>
                                <span id="stat-unidade-media" class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400">IQA</span>
                            </div>
                            <p id="stat-desc-media" class="text-[11px] text-athens-gray-500 dark:text-athens-gray-400 mt-1">Média ponderada do período selecionado</p>
                        </div>

                        <!-- Valor Máximo -->
                        <div class="bg-white dark:bg-athens-gray-900 p-4 rounded-xl border border-athens-gray-200 dark:border-athens-gray-800 shadow-2xs">
                            <div class="flex items-center justify-between text-cinnabar-600">
                                <span class="text-xs font-bold uppercase tracking-wider text-athens-gray-500 dark:text-athens-gray-400">Valor Máximo</span>
                                <x-heroicon-m-arrow-trending-up class="w-4 h-4 text-cinnabar-500" />
                            </div>
                            <div class="mt-2 flex items-baseline gap-1.5">
                                <span id="stat-maximo" class="text-2xl font-black text-cinnabar-600 dark:text-cinnabar-400">--</span>
                                <span id="stat-unidade-max" class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400">IQA</span>
                            </div>
                            <p id="stat-desc-max" class="text-[11px] text-athens-gray-500 dark:text-athens-gray-400 mt-1">Pico máximo registrado no período selecionado</p>
                        </div>

                        <!-- Valor Mínimo -->
                        <div class="bg-white dark:bg-athens-gray-900 p-4 rounded-xl border border-athens-gray-200 dark:border-athens-gray-800 shadow-2xs">
                            <div class="flex items-center justify-between text-dodger-blue-600">
                                <span class="text-xs font-bold uppercase tracking-wider text-athens-gray-500 dark:text-athens-gray-400">Valor Mínimo</span>
                                <x-heroicon-m-arrow-trending-down class="w-4 h-4 text-dodger-blue-500" />
                            </div>
                            <div class="mt-2 flex items-baseline gap-1.5">
                                <span id="stat-minimo" class="text-2xl font-black text-dodger-blue-600 dark:text-dodger-blue-400">--</span>
                                <span id="stat-unidade-min" class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400">IQA</span>
                            </div>
                            <p id="stat-desc-min" class="text-[11px] text-athens-gray-500 dark:text-athens-gray-400 mt-1">Ponto mínimo observado no período selecionado</p>
                        </div>

                        <!-- Estações Agregadas e Amostras -->
                        <div class="bg-white dark:bg-athens-gray-900 p-4 rounded-xl border border-athens-gray-200 dark:border-athens-gray-800 shadow-2xs">
                            <div class="flex items-center justify-between text-athens-gray-500 dark:text-athens-gray-400">
                                <span class="text-xs font-bold uppercase tracking-wider">Amostras & Estações</span>
                                <x-heroicon-o-signal class="w-4 h-4 text-emerald-500" />
                            </div>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span id="stat-amostras" class="text-2xl font-black text-blue-dianne-950 dark:text-white">--</span>
                                <span class="text-xs font-medium text-athens-gray-500 dark:text-athens-gray-400">leituras</span>
                            </div>
                            <p id="stat-estacoes-ativas" class="text-[11px] text-athens-gray-500 dark:text-athens-gray-400 mt-1">Agregando estações ativas</p>
                        </div>
                    </div>

                    <!-- Área do Gráfico de Linhas -->
                    <div class="p-6 relative">
                        <!-- Loading Overlay -->
                        <div id="chart-loading" class="absolute inset-0 bg-white/75 dark:bg-athens-gray-950/75 backdrop-blur-2xs flex items-center justify-center z-10 hidden">
                            <div class="flex items-center gap-3 px-4 py-2.5 bg-white dark:bg-athens-gray-800 border border-athens-gray-200 dark:border-athens-gray-700 rounded-xl shadow-lg">
                                <svg class="animate-spin h-5 w-5 text-blue-dianne-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-xs font-bold text-blue-dianne-950 dark:text-white">Calculando médias da localidade...</span>
                            </div>
                        </div>

                        <!-- Canvas do Chart.js -->
                        <div class="w-full h-84 relative">
                            <canvas id="dashboard-chart"></canvas>
                        </div>

                        <!-- Estado Vazio (Sem Dados) -->
                        <div id="chart-empty" class="hidden flex flex-col items-center justify-center py-16 text-center">
                            <x-heroicon-o-chart-bar-square class="w-12 h-12 text-athens-gray-300 dark:text-athens-gray-600 mb-3" />
                            <h4 class="text-sm font-bold text-blue-dianne-950 dark:text-white">Nenhuma leitura encontrada</h4>
                            <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mt-1 max-w-sm">Não há telemetrias registradas para esta localidade no período selecionado.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                let chartInstance = null;
                let currentTipo = 'cidade';
                let currentMetrica = 'qualidade_ar';
                let currentPeriodo = '24h';

                const btnTipoCidade = document.getElementById('btn-tipo-cidade');
                const btnTipoBairro = document.getElementById('btn-tipo-bairro');
                const containerCidade = document.getElementById('container-select-cidade');
                const containerBairro = document.getElementById('container-select-bairro');
                const selectCidade = document.getElementById('select-cidade');
                const selectBairro = document.getElementById('select-bairro');
                const chartLoading = document.getElementById('chart-loading');
                const chartCanvas = document.getElementById('dashboard-chart');
                const chartEmpty = document.getElementById('chart-empty');

                // Elementos dos Cards de Estatística
                const statMedia = document.getElementById('stat-media');
                const statMaximo = document.getElementById('stat-maximo');
                const statMinimo = document.getElementById('stat-minimo');
                const statAmostras = document.getElementById('stat-amostras');
                const statUnidadeMedia = document.getElementById('stat-unidade-media');
                const statUnidadeMax = document.getElementById('stat-unidade-max');
                const statUnidadeMin = document.getElementById('stat-unidade-min');
                const statClassificacao = document.getElementById('stat-classificacao');
                const statEstacoesAtivas = document.getElementById('stat-estacoes-ativas');

                // Função auxiliar para alternar classes ativas/inativas dos botões (suporta Light e Dark Mode)
                function aplicarEstadoBotao(elemento, ativo) {
                    const activeClasses = (elemento.dataset.activeClasses || '').split(' ').filter(Boolean);
                    const inactiveClasses = (elemento.dataset.inactiveClasses || '').split(' ').filter(Boolean);

                    if (ativo) {
                        if (inactiveClasses.length) elemento.classList.remove(...inactiveClasses);
                        if (activeClasses.length) elemento.classList.add(...activeClasses);
                    } else {
                        if (activeClasses.length) elemento.classList.remove(...activeClasses);
                        if (inactiveClasses.length) elemento.classList.add(...inactiveClasses);
                    }
                }

                function atualizarBotoesTipo() {
                    document.querySelectorAll('.btn-tipo').forEach(btn => {
                        aplicarEstadoBotao(btn, btn.dataset.tipo === currentTipo);
                    });
                }

                function atualizarBotoesPeriodo() {
                    document.querySelectorAll('.btn-periodo').forEach(btn => {
                        aplicarEstadoBotao(btn, btn.dataset.periodo === currentPeriodo);
                    });
                }

                function atualizarBotoesMetrica() {
                    document.querySelectorAll('.btn-metrica').forEach(btn => {
                        aplicarEstadoBotao(btn, btn.dataset.metrica === currentMetrica);
                    });
                }

                // Alternância de Agrupamento: Cidade vs Bairro
                btnTipoCidade.addEventListener('click', function () {
                    currentTipo = 'cidade';
                    atualizarBotoesTipo();
                    containerCidade.classList.remove('hidden');
                    containerBairro.classList.add('hidden');
                    carregarDadosGrafico();
                });

                btnTipoBairro.addEventListener('click', function () {
                    currentTipo = 'bairro';
                    atualizarBotoesTipo();
                    containerBairro.classList.remove('hidden');
                    containerCidade.classList.add('hidden');
                    carregarDadosGrafico();
                });

                // Seleção de Cidade ou Bairro
                selectCidade.addEventListener('change', carregarDadosGrafico);
                selectBairro.addEventListener('change', carregarDadosGrafico);

                // Seleção de Período (24h, 7d, 30d)
                document.querySelectorAll('.btn-periodo').forEach(btn => {
                    btn.addEventListener('click', function () {
                        currentPeriodo = this.dataset.periodo;
                        atualizarBotoesPeriodo();
                        carregarDadosGrafico();
                    });
                });

                // Seleção de Métricas (Qualidade do Ar, Temp, Umidade, PM, CO2)
                document.querySelectorAll('.btn-metrica').forEach(btn => {
                    btn.addEventListener('click', function () {
                        currentMetrica = this.dataset.metrica;
                        atualizarBotoesMetrica();
                        carregarDadosGrafico();
                    });
                });

                // Função de Carregamento AJAX dos Dados do Gráfico
                function carregarDadosGrafico(silent = false) {
                    const localidadeId = currentTipo === 'cidade' ? selectCidade.value : selectBairro.value;

                    if (!silent) {
                        chartLoading.classList.remove('hidden');
                    }

                    const url = new URL('{{ route('dashboard.graficos') }}', window.location.origin);
                    url.searchParams.append('tipo_agrupamento', currentTipo);
                    if (localidadeId) {
                        url.searchParams.append('localidade_id', localidadeId);
                    }
                    url.searchParams.append('metrica', currentMetrica);
                    url.searchParams.append('periodo', currentPeriodo);

                    fetch(url)
                        .then(res => res.json())
                        .then(data => {
                            chartLoading.classList.add('hidden');
                            atualizarCardsEstatisticos(data);
                            renderizarGrafico(data);
                        })
                        .catch(err => {
                            chartLoading.classList.add('hidden');
                            console.error('Erro ao buscar dados do gráfico:', err);
                        });
                }

                // Formata número respeitando inteiros para Qualidade do Ar e CO2, e 2 casas com vírgula para os demais
                function formatarValorMetrica(val, metrica) {
                    if (val === null || val === undefined || isNaN(val)) return '--';
                    const isInteiro = (metrica === 'qualidade_ar' || metrica === 'co2');
                    if (isInteiro) {
                        return Math.round(val).toLocaleString('pt-BR');
                    }
                    return Number(val).toLocaleString('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }

                // Atualização dos Cards de Média, Máximo e Mínimo
                function atualizarCardsEstatisticos(data) {
                    statMedia.textContent = formatarValorMetrica(data.media, data.metrica);
                    statMaximo.textContent = formatarValorMetrica(data.maximo, data.metrica);
                    statMinimo.textContent = formatarValorMetrica(data.minimo, data.metrica);
                    statAmostras.textContent = Number(data.total_leituras).toLocaleString('pt-BR');

                    statUnidadeMedia.textContent = data.unidade;
                    statUnidadeMax.textContent = data.unidade;
                    statUnidadeMin.textContent = data.unidade;

                    statClassificacao.textContent = data.status_classificacao;
                    statClassificacao.className = `px-2 py-0.5 text-[11px] font-bold rounded-full border ${data.status_cor}`;

                    statEstacoesAtivas.textContent = `${data.estacoes_ativas} estação(ões) ativa(s) em ${data.localidade}`;

                    // Atualiza a legenda descritiva dos blocos com o período selecionado
                    const descPeriodo = data.periodo_rotulo || (currentPeriodo === '7d' ? 'nos últimos 7 dias' : (currentPeriodo === '30d' ? 'nos últimos 30 dias' : 'nas últimas 24 horas'));
                    const statDescMedia = document.getElementById('stat-desc-media');
                    const statDescMax = document.getElementById('stat-desc-max');
                    const statDescMin = document.getElementById('stat-desc-min');

                    if (statDescMedia) statDescMedia.textContent = `Média ponderada ${descPeriodo}`;
                    if (statDescMax) statDescMax.textContent = `Pico máximo registrado ${descPeriodo}`;
                    if (statDescMin) statDescMin.textContent = `Ponto mínimo observado ${descPeriodo}`;
                }

                let ultimoDadoCarregado = null;

                // Criação dos datasets de Média, Máximo e Mínimo
                function gerarDatasetsGrafico(data, isDark) {
                    const corMedia = data.cor;
                    const corMaximo = data.metrica === 'poeira' ? '#dc2626' : '#ef4444';
                    const corMinimo = data.metrica === 'umidade' ? '#0284c7' : '#3b82f6';
                    const isMuitosPontos = data.labels && data.labels.length > 30;

                    return [
                        {
                            label: `Média (${data.unidade})`,
                            data: data.valores || [],
                            borderColor: corMedia,
                            backgroundColor: corMedia,
                            borderWidth: 2.5,
                            borderDash: [],
                            fill: false,
                            tension: 0.35,
                            pointBackgroundColor: isDark ? '#111827' : '#ffffff',
                            pointBorderColor: corMedia,
                            pointBorderWidth: 2,
                            pointRadius: isMuitosPontos ? 2 : 4,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: corMedia,
                            pointHoverBorderColor: isDark ? '#111827' : '#ffffff',
                            pointHoverBorderWidth: 2,
                        },
                        {
                            label: `Máximo (${data.unidade})`,
                            data: data.maximos || [],
                            borderColor: corMaximo,
                            backgroundColor: corMaximo,
                            borderWidth: 1.8,
                            borderDash: [5, 4],
                            fill: false,
                            tension: 0.35,
                            pointBackgroundColor: isDark ? '#111827' : '#ffffff',
                            pointBorderColor: corMaximo,
                            pointBorderWidth: 1.5,
                            pointRadius: isMuitosPontos ? 1.5 : 3,
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: corMaximo,
                            pointHoverBorderColor: isDark ? '#111827' : '#ffffff',
                            pointHoverBorderWidth: 2,
                        },
                        {
                            label: `Mínimo (${data.unidade})`,
                            data: data.minimos || [],
                            borderColor: corMinimo,
                            backgroundColor: corMinimo,
                            borderWidth: 1.8,
                            borderDash: [5, 4],
                            fill: false,
                            tension: 0.35,
                            pointBackgroundColor: isDark ? '#111827' : '#ffffff',
                            pointBorderColor: corMinimo,
                            pointBorderWidth: 1.5,
                            pointRadius: isMuitosPontos ? 1.5 : 3,
                            pointHoverRadius: 5,
                            pointHoverBackgroundColor: corMinimo,
                            pointHoverBorderColor: isDark ? '#111827' : '#ffffff',
                            pointHoverBorderWidth: 2,
                        }
                    ];
                }

                // Renderização do Gráfico de Linhas com Chart.js
                function renderizarGrafico(data) {
                    ultimoDadoCarregado = data;

                    if (!data.labels || data.labels.length === 0) {
                        chartCanvas.classList.add('hidden');
                        chartEmpty.classList.remove('hidden');
                        if (chartInstance) {
                            chartInstance.destroy();
                            chartInstance = null;
                        }
                        return;
                    }

                    chartCanvas.classList.remove('hidden');
                    chartEmpty.classList.add('hidden');

                    const ctx = chartCanvas.getContext('2d');
                    const isDark = document.documentElement.classList.contains('dark');
                    const gridColor = isDark ? '#233246' : '#edf1f5';
                    const tickColor = isDark ? '#99afc7' : '#7b96b6';

                    const isInteiro = (data.metrica === 'qualidade_ar' || data.metrica === 'co2');
                    const datasets = gerarDatasetsGrafico(data, isDark);

                    const yAxisTicksCallback = function (val) {
                        if (isInteiro) {
                            if (Number.isInteger(Number(val))) {
                                return `${Number(val).toLocaleString('pt-BR')} ${data.unidade}`;
                            }
                            return '';
                        }
                        const num = Number(val);
                        const formatado = num.toLocaleString('pt-BR', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                        return `${formatado} ${data.unidade}`;
                    };

                    const tooltipLabelCallback = function (context) {
                        const val = context.parsed.y;
                        if (val === null || val === undefined) return '';
                        let formatado;
                        if (isInteiro) {
                            formatado = Math.round(val).toLocaleString('pt-BR');
                        } else {
                            formatado = Number(val).toLocaleString('pt-BR', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                        const datasetLabel = context.dataset.label || data.nome_metrica;
                        return `${datasetLabel}: ${formatado} ${data.unidade}`;
                    };

                    if (chartInstance) {
                        // Atualização in-place suave sem destruir o canvas (evita o reset visual do gráfico)
                        chartInstance.data.labels = data.labels;
                        chartInstance.data.datasets = datasets;
                        
                        chartInstance.options.plugins.legend.labels.color = tickColor;
                        chartInstance.options.scales.x.ticks.color = tickColor;
                        chartInstance.options.scales.y.ticks.color = tickColor;
                        chartInstance.options.scales.y.grid.color = gridColor;
                        chartInstance.options.scales.y.ticks.precision = isInteiro ? 0 : 2;
                        chartInstance.options.scales.y.ticks.callback = yAxisTicksCallback;
                        chartInstance.options.plugins.tooltip.callbacks.label = tooltipLabelCallback;

                        chartInstance.update();
                        return;
                    }

                    chartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: datasets
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index',
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    align: 'end',
                                    labels: {
                                        boxWidth: 10,
                                        boxHeight: 10,
                                        usePointStyle: true,
                                        font: { size: 11, weight: '600' },
                                        color: tickColor,
                                        padding: 12,
                                    }
                                },
                                tooltip: {
                                    backgroundColor: isDark ? 'rgba(4, 57, 72, 0.95)' : 'rgba(15, 76, 92, 0.95)',
                                    titleFont: { size: 12, weight: 'bold' },
                                    bodyFont: { size: 12 },
                                    padding: 10,
                                    cornerRadius: 8,
                                    displayColors: true,
                                    callbacks: {
                                        label: tooltipLabelCallback
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    grid: {
                                        display: false,
                                    },
                                    ticks: {
                                        font: { size: 11 },
                                        color: tickColor,
                                        maxRotation: 0,
                                        autoSkip: true,
                                        maxTicksLimit: 12
                                    }
                                },
                                y: {
                                    grid: {
                                        color: gridColor,
                                    },
                                    ticks: {
                                        font: { size: 11 },
                                        color: tickColor,
                                        precision: isInteiro ? 0 : 2,
                                        callback: yAxisTicksCallback
                                    }
                                }
                            }
                        }
                    });
                }

                window.addEventListener('themechanged', function () {
                    atualizarBotoesTipo();
                    atualizarBotoesPeriodo();
                    atualizarBotoesMetrica();
                    if (chartInstance) {
                        chartInstance.destroy();
                        chartInstance = null;
                    }
                    if (ultimoDadoCarregado) {
                        renderizarGrafico(ultimoDadoCarregado);
                    }
                });

                let timerAtualizacaoDashboard = null;

                // Atualização em Tempo Real dos Gráficos e Métricas via WebSockets (Laravel Reverb + Echo)
                function processarNovaMedicaoDashboard(dados) {
                    if (!dados) return;

                    // Se estiver agrupando por cidade, verifica se pertence à cidade selecionada
                    if (currentTipo === 'cidade') {
                        const cidadeSelecionada = selectCidade.value;
                        if (cidadeSelecionada && dados.cidade_id && String(dados.cidade_id) !== String(cidadeSelecionada)) {
                            return;
                        }
                    }

                    // Se estiver agrupando por bairro, verifica se pertence ao bairro selecionado
                    if (currentTipo === 'bairro') {
                        const bairroSelecionado = selectBairro.value;
                        if (bairroSelecionado && dados.bairro_id && String(dados.bairro_id) !== String(bairroSelecionado)) {
                            return;
                        }
                    }

                    // Aplica debounce para agrupar rajadas de medições em uma única requisição ao servidor
                    if (timerAtualizacaoDashboard) {
                        clearTimeout(timerAtualizacaoDashboard);
                    }
                    timerAtualizacaoDashboard = setTimeout(() => {
                        carregarDadosGrafico(true);
                    }, 350);
                }

                // Inicialização resiliente da escuta do canal público 'medicoes'
                function inicializarEchoDashboard() {
                    if (window.Echo) {
                        window.Echo.channel('medicoes')
                            .listen('.NovaMedicaoRecebida', processarNovaMedicaoDashboard)
                            .listen('NovaMedicaoRecebida', processarNovaMedicaoDashboard);
                    } else {
                        setTimeout(inicializarEchoDashboard, 250);
                    }
                }
                inicializarEchoDashboard();

                // Carregamento inicial do gráfico ao abrir a tela
                carregarDadosGrafico();
            });
        </script>
    @endpush
</x-layouts.app>