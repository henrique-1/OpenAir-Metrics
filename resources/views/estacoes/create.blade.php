<x-layouts.app title="Cadastrar Nova Estação - OpenAir Metrics">
    <div class="flex flex-col md:flex-row h-full w-full bg-athens-gray-50 dark:bg-athens-gray-950 transition-colors duration-200">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="estacoes" />

        <!-- Conteúdo Principal com Scroll Vertical -->
        <main class="flex-1 overflow-y-auto min-h-0 p-4 sm:p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Breadcrumbs e Cabeçalho -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-medium text-athens-gray-500 dark:text-athens-gray-400 mb-1">
                            <a href="{{ route('estacoes.index') }}" class="hover:text-blue-dianne-700 dark:hover:text-blue-dianne-400 transition">Minhas Estações</a>
                            <span>/</span>
                            <span class="text-blue-dianne-950 dark:text-white font-semibold">Nova Estação</span>
                        </div>
                        <h1 class="text-2xl font-bold text-blue-dianne-950 dark:text-white tracking-tight">Cadastro de Nova Estação</h1>
                        <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mt-0.5">Preencha os dados da placa IoT e selecione a localização no mapa.</p>
                    </div>

                    <a href="{{ route('estacoes.index') }}" class="inline-flex items-center gap-2 bg-white dark:bg-athens-gray-800 text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-700 px-4 py-2.5 rounded-lg font-semibold text-sm border border-athens-gray-200 dark:border-athens-gray-700 shadow-sm transition">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Voltar para Listagem
                    </a>
                </div>

                <!-- Banner de Jurisdição Municipal Ativa -->
                @if (isset($cidade) && $cidade)
                    <div class="bg-spindle-50 dark:bg-spindle-950/40 border border-spindle-200 dark:border-spindle-800 text-spindle-900 dark:text-spindle-200 px-5 py-3.5 rounded-xl shadow-sm flex items-start gap-3 text-xs">
                        <x-heroicon-o-shield-check class="w-5 h-5 text-blue-dianne-600 dark:text-blue-dianne-400 shrink-0 mt-0.5" />
                        <div>
                            <span class="font-bold text-blue-dianne-950 dark:text-white">Jurisdição Municipal Ativa:</span>
                            Sua conta tem atuação restrita ao município de <strong>{{ $cidade->nome }} ({{ $cidade->estado?->uf }})</strong>. O cadastro de estações e a visualização do mapa estão limitados a esta localidade.
                        </div>
                    </div>
                @endif

                <!-- Exibição de Erros Globais de Validação -->
                @if ($errors->any())
                    <div class="bg-cinnabar-50 dark:bg-cinnabar-950/50 border border-cinnabar-200 dark:border-cinnabar-800 text-cinnabar-800 dark:text-cinnabar-200 px-5 py-4 rounded-xl shadow-sm space-y-1">
                        <div class="flex items-center gap-2 font-bold text-sm text-cinnabar-900 dark:text-cinnabar-100">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-cinnabar-600 dark:text-cinnabar-400" />
                            Atenção: Por favor, corrija os erros abaixo antes de salvar:
                        </div>
                        <ul class="list-disc list-inside text-xs space-y-1 text-cinnabar-700 dark:text-cinnabar-300 ml-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Grid Principal: Formulário + Mapa -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- Coluna da Esquerda: Formulário de Cadastro -->
                    <div class="lg:col-span-5 space-y-6">
                        <form method="POST" action="{{ route('estacoes.store') }}" id="form-estacao" class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-6 space-y-6">
                            @csrf
                            <input type="hidden" name="estacao_origem_id" id="input_estacao_origem_id" value="{{ old('estacao_origem_id') }}">

                            <!-- Seção 1: Dados da Placa e Hardware -->
                            <div>
                                <h3 class="text-sm font-bold text-blue-dianne-950 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-athens-gray-100 dark:border-athens-gray-800 flex items-center gap-2">
                                    <x-heroicon-o-cpu-chip class="w-4 h-4 text-blue-dianne-600 dark:text-blue-dianne-400" />
                                    Dados do Dispositivo
                                </h3>

                                <div class="space-y-4">
                                    <!-- MAC Address -->
                                    <div>
                                        <label for="mac_address" class="block text-xs font-bold text-blue-dianne-950 dark:text-athens-gray-200 uppercase tracking-wider mb-1">
                                            Endereço MAC da Placa <span class="text-cinnabar-500">*</span>
                                        </label>
                                        <input 
                                            type="text" 
                                            name="mac_address" 
                                            id="mac_address" 
                                            value="{{ old('mac_address') }}" 
                                            placeholder="AA:BB:CC:DD:EE:FF" 
                                            maxlength="17" 
                                            required
                                            class="w-full px-3.5 py-2.5 text-sm font-mono uppercase bg-athens-gray-50 dark:bg-athens-gray-800 border {{ $errors->has('mac_address') ? 'border-cinnabar-400 focus:ring-cinnabar-400' : 'border-athens-gray-300 dark:border-athens-gray-700 focus:border-blue-dianne-500 focus:ring-blue-dianne-500' }} text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:bg-white dark:focus:bg-athens-gray-800 transition"
                                        >
                                        @error('mac_address')
                                            <p class="text-xs text-cinnabar-600 dark:text-cinnabar-400 mt-1 font-medium">{{ $message }}</p>
                                        @enderror
                                        <p class="text-[11px] text-athens-gray-500 dark:text-athens-gray-400 mt-1">Identificador físico único do hardware ESP32 / Arduino.</p>
                                    </div>

                                    <!-- Tipo de Estação -->
                                    <div>
                                        <label for="tipo_estacao" class="block text-xs font-bold text-blue-dianne-950 dark:text-athens-gray-200 uppercase tracking-wider mb-1">
                                            Tipo de Estação <span class="text-cinnabar-500">*</span>
                                        </label>
                                        <select 
                                            name="tipo_estacao" 
                                            id="tipo_estacao" 
                                            required
                                            class="w-full px-3.5 py-2.5 text-sm bg-athens-gray-50 dark:bg-athens-gray-800 border {{ $errors->has('tipo_estacao') ? 'border-cinnabar-400' : 'border-athens-gray-300 dark:border-athens-gray-700' }} text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-dianne-500 focus:bg-white dark:focus:bg-athens-gray-800 transition"
                                        >
                                            <option value="Estação Matriz" {{ old('tipo_estacao') === 'Estação Matriz' ? 'selected' : '' }}>Estação Matriz (Ponto Livre)</option>
                                            <option value="Estação Satélite" {{ old('tipo_estacao') === 'Estação Satélite' ? 'selected' : '' }}>Estação Satélite (Raio máx. 200m)</option>
                                        </select>
                                        @error('tipo_estacao')
                                            <p class="text-xs text-cinnabar-600 dark:text-cinnabar-400 mt-1 font-medium">{{ $message }}</p>
                                        @enderror

                                        <!-- Card Informativo do Tipo Selecionado -->
                                        <div id="tipo-info-matriz" class="mt-3 p-3 bg-dodger-blue-50 dark:bg-dodger-blue-950/40 border border-dodger-blue-200 dark:border-dodger-blue-800 rounded-lg text-xs text-dodger-blue-900 dark:text-dodger-blue-200 flex items-start gap-2.5">
                                            <x-heroicon-o-information-circle class="w-4 h-4 text-dodger-blue-600 dark:text-dodger-blue-400 shrink-0 mt-0.5" />
                                            <div>
                                                <span class="font-bold">Estação Matriz:</span> Pode ser posicionada livremente em qualquer coordenada geográfica sem restrição de proximidade, sendo automaticamente grudada à via pública mais próxima (Snap to Road).
                                            </div>
                                        </div>

                                        <div id="tipo-info-satelite" class="mt-3 p-3 bg-spindle-50 dark:bg-spindle-950/40 border border-spindle-200 dark:border-spindle-800 rounded-lg text-xs text-spindle-900 dark:text-spindle-200 flex items-start gap-2.5 hidden">
                                            <x-heroicon-o-radio class="w-4 h-4 text-spindle-600 dark:text-spindle-400 shrink-0 mt-0.5" />
                                            <div>
                                                <span class="font-bold">Estação Satélite:</span> Deve obrigatoriamente estar a uma distância máxima de <strong class="text-spindle-950 dark:text-white underline">200 metros</strong> de qualquer estação já existente, sendo automaticamente grudada à via pública mais próxima (Snap to Road).
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção 2: Localidade IBGE (Cascata) -->
                            <div>
                                <h3 class="text-sm font-bold text-blue-dianne-950 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-athens-gray-100 dark:border-athens-gray-800 flex items-center gap-2">
                                    <x-heroicon-o-map-pin class="w-4 h-4 text-blue-dianne-600 dark:text-blue-dianne-400" />
                                    Localidade (IBGE)
                                </h3>

                                <div class="space-y-4">
                                    <!-- Estado -->
                                    <div>
                                        <label for="select_estado" class="block text-xs font-bold text-blue-dianne-950 dark:text-athens-gray-200 uppercase tracking-wider mb-1">
                                            Estado (UF) <span class="text-cinnabar-500">*</span>
                                        </label>
                                        <select 
                                            id="select_estado" 
                                            @if (isset($cidade) && $cidade) disabled @endif
                                            class="w-full px-3.5 py-2.5 text-sm bg-athens-gray-50 dark:bg-athens-gray-800 border border-athens-gray-300 dark:border-athens-gray-700 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-dianne-500 focus:bg-white dark:focus:bg-athens-gray-800 transition disabled:opacity-60 disabled:cursor-not-allowed"
                                        >
                                            <option value="">Selecione o Estado...</option>
                                            @foreach ($estados as $estado)
                                                <option value="{{ $estado->id }}" {{ ((isset($cidade) && $cidade && $cidade->estado_id == $estado->id) || old('select_estado') == $estado->id) ? 'selected' : '' }}>
                                                    {{ $estado->nome }} ({{ $estado->uf }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Cidade -->
                                    <div>
                                        <label for="select_cidade" class="block text-xs font-bold text-blue-dianne-950 dark:text-athens-gray-200 uppercase tracking-wider mb-1">
                                            Cidade <span class="text-cinnabar-500">*</span>
                                        </label>
                                        <select 
                                            id="select_cidade" 
                                            @if (isset($cidade) && $cidade) disabled @else disabled @endif
                                            class="w-full px-3.5 py-2.5 text-sm bg-athens-gray-100 dark:bg-athens-gray-800/60 border border-athens-gray-300 dark:border-athens-gray-700 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-dianne-500 focus:bg-white dark:focus:bg-athens-gray-800 transition disabled:opacity-60 disabled:cursor-not-allowed"
                                        >
                                            @if (isset($cidade) && $cidade)
                                                <option value="{{ $cidade->id }}" selected>{{ $cidade->nome }}</option>
                                            @else
                                                <option value="">Selecione o Estado primeiro...</option>
                                            @endif
                                        </select>
                                    </div>

                                    <!-- Bairro -->
                                    <div>
                                        <label for="bairro_id" class="block text-xs font-bold text-blue-dianne-950 dark:text-athens-gray-200 uppercase tracking-wider mb-1">
                                            Bairro <span class="text-cinnabar-500">*</span>
                                        </label>
                                        <select 
                                            name="bairro_id" 
                                            id="bairro_id" 
                                            required 
                                            disabled
                                            class="w-full px-3.5 py-2.5 text-sm bg-athens-gray-100 dark:bg-athens-gray-800/60 border {{ $errors->has('bairro_id') ? 'border-cinnabar-400' : 'border-athens-gray-300 dark:border-athens-gray-700' }} text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-dianne-500 focus:bg-white dark:focus:bg-athens-gray-800 transition disabled:opacity-60 disabled:cursor-not-allowed"
                                        >
                                            <option value="">{{ isset($cidade) && $cidade ? 'Carregando bairros...' : 'Selecione a Cidade primeiro...' }}</option>
                                        </select>
                                        @error('bairro_id')
                                            <p class="text-xs text-cinnabar-600 dark:text-cinnabar-400 mt-1 font-medium">{{ $message }}</p>
                                        @enderror
                                        <p class="text-[11px] text-athens-gray-500 dark:text-athens-gray-400 mt-1">Ao clicar no mapa, o bairro é detectado e selecionado automaticamente.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção 3: Coordenadas do Mapa (Inputs Ocultos + Visualizador) -->
                            <div>
                                <h3 class="text-sm font-bold text-blue-dianne-950 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-athens-gray-100 dark:border-athens-gray-800 flex items-center gap-2">
                                    <x-heroicon-o-globe-americas class="w-4 h-4 text-blue-dianne-600 dark:text-blue-dianne-400" />
                                    Posição Geográfica
                                </h3>

                                <!-- Inputs Ocultos -->
                                <input type="hidden" name="latitude" id="input_latitude" value="{{ old('latitude') }}">
                                <input type="hidden" name="longitude" id="input_longitude" value="{{ old('longitude') }}">

                                <!-- Visualizador de Coordenadas Selecionadas -->
                                <div id="coordenadas-display" class="p-3.5 rounded-lg border {{ old('latitude') ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800' : 'bg-athens-gray-50 dark:bg-athens-gray-800/60 border-athens-gray-200 dark:border-athens-gray-700' }} transition-colors">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-athens-gray-600 dark:text-athens-gray-300 uppercase tracking-wider">Ponto Selecionado:</span>
                                        <span id="coords-status-badge" class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ old('latitude') ? 'bg-emerald-200 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200' : 'bg-athens-gray-200 dark:bg-athens-gray-700 text-athens-gray-600 dark:text-athens-gray-300' }}">
                                            {{ old('latitude') ? 'Definido' : 'Pendente' }}
                                        </span>
                                    </div>
                                    <div class="mt-2 text-xs font-mono text-blue-dianne-950 dark:text-white" id="coords-text">
                                        @if (old('latitude') && old('longitude'))
                                            Lat: {{ old('latitude') }} | Lng: {{ old('longitude') }}
                                        @else
                                            <span class="text-athens-gray-400 dark:text-athens-gray-500 italic">Clique no mapa ao lado para marcar a coordenada exata.</span>
                                        @endif
                                    </div>

                                    <!-- Linha de Conexão Ativa -->
                                    <div id="conexao-info" class="mt-2.5 pt-2 border-t border-athens-gray-200 dark:border-athens-gray-700/60 text-xs text-blue-dianne-950 dark:text-white flex items-center gap-2 hidden">
                                        <x-heroicon-o-arrows-pointing-in class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                        <span id="conexao-info-text"></span>
                                    </div>
                                </div>
                                @error('latitude')
                                    <p class="text-xs text-cinnabar-600 dark:text-cinnabar-400 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                                @error('longitude')
                                    <p class="text-xs text-cinnabar-600 dark:text-cinnabar-400 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Botões de Ação do Formulário -->
                            <div class="pt-4 border-t border-athens-gray-200 dark:border-athens-gray-800 flex items-center justify-end gap-3">
                                <a href="{{ route('estacoes.index') }}" class="px-4 py-2.5 text-sm font-semibold text-athens-gray-600 dark:text-athens-gray-400 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 rounded-lg transition">
                                    Cancelar
                                </a>
                                <button type="submit" id="btn-submit-estacao" class="px-6 py-2.5 text-sm font-bold text-white bg-blue-dianne-600 hover:bg-blue-dianne-700 active:scale-98 rounded-lg shadow-sm hover:shadow transition-all flex items-center gap-2 cursor-pointer">
                                    <x-heroicon-o-check class="w-4 h-4" />
                                    Salvar Estação
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Coluna da Direita: Mapa Interativo com Validação de Raio -->
                    <div class="lg:col-span-7 flex flex-col space-y-3">
                        <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 overflow-hidden flex flex-col flex-1 min-h-[550px]">
                            
                            <!-- Cabeçalho do Mapa -->
                            <div class="px-5 py-3.5 border-b border-athens-gray-200 dark:border-athens-gray-800 bg-athens-gray-50/70 dark:bg-athens-gray-800/80 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-map class="w-5 h-5 text-blue-dianne-600 dark:text-blue-dianne-400" />
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-sm font-bold text-blue-dianne-950 dark:text-white">Mapa de Implantação</h3>
                                            <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 px-2 py-0.5 rounded border border-emerald-200 dark:border-emerald-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Snap to Road Ativo
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-athens-gray-500 dark:text-athens-gray-400">Clique ou arraste o pino no mapa para posicionar a estação IoT (gruda à rua mais próxima).</p>
                                    </div>
                                </div>

                                <!-- Legenda Rápida -->
                                <div class="flex items-center gap-3 text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3 h-3 rounded-full bg-dodger-blue-500 inline-block"></span>
                                        <span class="text-athens-gray-600 dark:text-athens-gray-400">Matriz</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3 h-3 rounded-full bg-purple-500 inline-block"></span>
                                        <span class="text-athens-gray-600 dark:text-athens-gray-400">Satélite</span>
                                    </div>
                                    <div id="legenda-raio" class="flex items-center gap-1.5 hidden">
                                        <span class="w-3 h-3 rounded-full border-2 border-dashed border-dodger-blue-500 bg-dodger-blue-200/50 inline-block"></span>
                                        <span class="text-blue-dianne-900 dark:text-blue-dianne-300 font-semibold">Raio 200m</span>
                                    </div>
                                    <div id="legenda-conexao" class="flex items-center gap-1.5 hidden">
                                        <span class="w-4 h-0.5 border-t-2 border-dashed border-emerald-500 inline-block"></span>
                                        <span class="text-emerald-700 dark:text-emerald-400 font-semibold">Conexão</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Alerta Dinâmico Flutuante do Mapa -->
                            <div id="map-feedback" class="mx-4 mt-3 p-3 rounded-lg text-xs font-medium hidden transition-all duration-300"></div>

                            <!-- Container do Mapa Leaflet -->
                            <div class="flex-1 w-full relative min-h-[480px]">
                                <div id="map-cadastro" class="absolute inset-0 z-0 w-full h-full rounded-b-xl bg-athens-gray-100 dark:bg-athens-gray-950"></div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

    @push('styles')
    <style>
        .custom-pin-pulse {
            position: relative;
            cursor: grab;
            transition: transform 0.15s ease;
        }
        .custom-pin-pulse:hover {
            transform: scale(1.15);
        }
        .leaflet-dragging .custom-pin-pulse {
            cursor: grabbing !important;
        }
        .custom-pin-pulse::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 24px;
            height: 24px;
            margin-top: -12px;
            margin-left: -12px;
            border-radius: 50%;
            background-color: rgba(46, 204, 113, 0.4);
            animation: pinPulse 2s infinite ease-out;
            pointer-events: none;
        }
        @keyframes pinPulse {
            0% { transform: scale(0.5); opacity: 1; }
            100% { transform: scale(2.2); opacity: 0; }
        }
    </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Jurisdição Municipal se vinculada ao usuário
            const fixedCidade = {!! json_encode((isset($cidade) && $cidade) ? ['id' => $cidade->id, 'nome' => $cidade->nome, 'uf' => $cidade->estado?->uf ?? '', 'estado_id' => $cidade->estado_id] : null) !!};

            // Elementos do DOM
            const formEstacao = document.getElementById('form-estacao');
            const selectEstado = document.getElementById('select_estado');
            const selectCidade = document.getElementById('select_cidade');
            const selectBairro = document.getElementById('bairro_id');
            const selectTipo = document.getElementById('tipo_estacao');
            const inputLat = document.getElementById('input_latitude');
            const inputLng = document.getElementById('input_longitude');
            const inputEstacaoOrigemId = document.getElementById('input_estacao_origem_id');
            const coordsDisplay = document.getElementById('coordenadas-display');
            const coordsText = document.getElementById('coords-text');
            const coordsBadge = document.getElementById('coords-status-badge');
            const conexaoInfo = document.getElementById('conexao-info');
            const conexaoInfoText = document.getElementById('conexao-info-text');
            const tipoInfoMatriz = document.getElementById('tipo-info-matriz');
            const tipoInfoSatelite = document.getElementById('tipo-info-satelite');
            const legendaRaio = document.getElementById('legenda-raio');
            const legendaConexao = document.getElementById('legenda-conexao');
            const mapFeedback = document.getElementById('map-feedback');

            // Garante que o campo de bairro não fique desabilitado na hora do submit do form
            formEstacao.addEventListener('submit', function () {
                if (selectBairro) {
                    selectBairro.disabled = false;
                }
            });

            // --- 1. Cascata de Localidades & Carregadores ---
            function carregarCidades(estadoId, callback) {
                if (!estadoId) return;
                selectCidade.innerHTML = '<option value="">Carregando cidades...</option>';
                selectCidade.disabled = true;

                fetch('/localidades/estados/' + estadoId + '/cidades')
                    .then(res => res.json())
                    .then(cidades => {
                        selectCidade.innerHTML = '<option value="">Selecione a Cidade...</option>';
                        (cidades || []).forEach(c => {
                            selectCidade.innerHTML += `<option value="${c.id}">${c.nome}</option>`;
                        });
                        selectCidade.disabled = false;
                        if (typeof callback === 'function') callback();
                    })
                    .catch(err => {
                        console.error('Erro ao carregar cidades:', err);
                        selectCidade.innerHTML = '<option value="">Erro ao carregar cidades</option>';
                    });
            }

            function carregarBairros(cidadeId, selectedBairroId = null, callback = null) {
                if (!cidadeId) return;
                selectBairro.innerHTML = '<option value="">Buscando bairros...</option>';
                selectBairro.disabled = true;

                fetch('/localidades/cidades/' + cidadeId + '/bairros')
                    .then(res => res.json())
                    .then(bairros => {
                        if (!bairros || bairros.length === 0) {
                            selectBairro.innerHTML = '<option value="">Nenhum bairro cadastrado</option>';
                            selectBairro.disabled = false;
                            if (typeof callback === 'function') callback();
                            return;
                        }
                        selectBairro.innerHTML = '<option value="">Selecione o Bairro...</option>';
                        let found = false;
                        bairros.forEach(b => {
                            const opt = new Option(b.nome, b.id);
                            if (selectedBairroId && b.id == selectedBairroId) {
                                opt.selected = true;
                                found = true;
                            }
                            selectBairro.add(opt);
                        });
                        selectBairro.disabled = false;
                        if (typeof callback === 'function') callback();
                    })
                    .catch(err => {
                        console.error('Erro ao carregar bairros:', err);
                        selectBairro.innerHTML = '<option value="">Erro ao carregar bairros</option>';
                    });
            }

            function sincronizarBairro(bairroId, bairroNome) {
                if (!selectBairro) return;

                if (bairroId) {
                    let found = false;
                    for (let i = 0; i < selectBairro.options.length; i++) {
                        if (selectBairro.options[i].value == bairroId) {
                            selectBairro.selectedIndex = i;
                            found = true;
                            break;
                        }
                    }
                    if (!found && bairroNome) {
                        const opt = new Option(bairroNome + ' (Detectado pelo mapa)', bairroId, true, true);
                        selectBairro.add(opt);
                    }
                    selectBairro.disabled = false;
                } else if (bairroNome) {
                    let found = false;
                    for (let i = 0; i < selectBairro.options.length; i++) {
                        if (selectBairro.options[i].text.toLowerCase().trim() === bairroNome.toLowerCase().trim()) {
                            selectBairro.selectedIndex = i;
                            found = true;
                            break;
                        }
                    }
                    if (!found) {
                        const currentVal = selectBairro.value || (selectBairro.options[1] ? selectBairro.options[1].value : '');
                        if (currentVal) {
                            const opt = new Option(bairroNome + ' (Detectado pelo mapa)', currentVal, true, true);
                            selectBairro.add(opt);
                        }
                    }
                    selectBairro.disabled = false;
                }
            }

            // Eventos manuais de seleção quando não restrito por jurisdição
            if (!fixedCidade) {
                selectEstado.addEventListener('change', function () {
                    const estadoId = this.value;
                    selectBairro.innerHTML = '<option value="">Selecione a Cidade primeiro...</option>';
                    selectBairro.disabled = true;

                    if (!estadoId) {
                        selectCidade.innerHTML = '<option value="">Selecione o Estado primeiro...</option>';
                        selectCidade.disabled = true;
                        return;
                    }
                    carregarCidades(estadoId);
                });

                selectCidade.addEventListener('change', function () {
                    const cidadeId = this.value;
                    if (!cidadeId) {
                        selectBairro.innerHTML = '<option value="">Selecione a Cidade primeiro...</option>';
                        selectBairro.disabled = true;
                        return;
                    }

                    const cidadeNome = this.options[this.selectedIndex] ? this.options[this.selectedIndex].text : '';
                    const estadoText = selectEstado.options[selectEstado.selectedIndex] ? selectEstado.options[selectEstado.selectedIndex].text : '';
                    const ufMatch = estadoText.match(/\(([A-Z]{2})\)/);
                    const estadoUf = ufMatch ? ufMatch[1] : '';

                    focarMapaNoMunicipio(cidadeNome, estadoUf);
                    carregarBairros(cidadeId);
                });
            } else {
                // Se já possui jurisdição fixa, carrega os bairros da cidade imediatamente
                const initialBairroId = {{ old('bairro_id') ? (int) old('bairro_id') : 'null' }};
                carregarBairros(fixedCidade.id, initialBairroId);
            }

            selectBairro.addEventListener('change', function () {
                const bairroId = this.value;
                if (!bairroId) return;

                const bairroNome = this.options[this.selectedIndex] ? this.options[this.selectedIndex].text : '';
                const cidadeNome = fixedCidade ? fixedCidade.nome : (selectCidade.options[selectCidade.selectedIndex] ? selectCidade.options[selectCidade.selectedIndex].text : '');
                const estadoUf = fixedCidade ? fixedCidade.uf : (() => {
                    const estadoText = selectEstado.options[selectEstado.selectedIndex] ? selectEstado.options[selectEstado.selectedIndex].text : '';
                    const ufMatch = estadoText.match(/\(([A-Z]{2})\)/);
                    return ufMatch ? ufMatch[1] : '';
                })();

                focarMapaNoBairro(bairroNome, cidadeNome, estadoUf);
            });

            // Cache de Geocodificação em memória
            const geocodeCache = new Map();
            let geocodeAbortController = null;

            function focarMapaNoMunicipio(cidadeNome, estadoUf) {
                if (!cidadeNome) return;
                const queryKey = `${cidadeNome}, ${estadoUf || ''}`.trim();

                if (geocodeCache.has(queryKey)) {
                    aplicarFocoNoMapa(geocodeCache.get(queryKey), 14);
                    return;
                }

                if (geocodeAbortController) {
                    geocodeAbortController.abort();
                }
                geocodeAbortController = new AbortController();

                showMapFeedback('info', `Localizando <strong>${cidadeNome}</strong> no mapa...`);
                const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(queryKey + ', Brasil')}&format=json&limit=1`;

                fetch(url, {
                    signal: geocodeAbortController.signal,
                    headers: { 'Accept-Language': 'pt-BR,pt;q=0.9,en;q=0.8' }
                })
                .then(res => res.json())
                .then(results => {
                    if (results && results.length > 0) {
                        const item = results[0];
                        geocodeCache.set(queryKey, item);
                        aplicarFocoNoMapa(item, 14);
                    }
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        console.warn('Não foi possível geolocalizar o município:', err);
                    }
                });
            }

            function focarMapaNoBairro(bairroNome, cidadeNome, estadoUf) {
                if (!bairroNome || bairroNome === 'Centro' || bairroNome.startsWith('Selecione') || bairroNome.startsWith('Nenhum')) return;
                const queryKey = `${bairroNome}, ${cidadeNome}, ${estadoUf || ''}`.trim();

                if (geocodeCache.has(queryKey)) {
                    aplicarFocoNoMapa(geocodeCache.get(queryKey), 16);
                    return;
                }

                showMapFeedback('info', `Aproximando no bairro <strong>${bairroNome}</strong>...`);
                const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(queryKey + ', Brasil')}&format=json&limit=1`;

                fetch(url, {
                    headers: { 'Accept-Language': 'pt-BR,pt;q=0.9,en;q=0.8' }
                })
                .then(res => res.json())
                .then(results => {
                    if (results && results.length > 0) {
                        const item = results[0];
                        geocodeCache.set(queryKey, item);
                        aplicarFocoNoMapa(item, 16);
                    }
                })
                .catch(err => {
                    console.warn('Não foi possível geolocalizar o bairro:', err);
                });
            }

            function aplicarFocoNoMapa(geoItem, targetZoom = 14) {
                if (!geoItem) return;
                const lat = parseFloat(geoItem.lat);
                const lon = parseFloat(geoItem.lon);

                if (!isNaN(lat) && !isNaN(lon)) {
                    map.flyTo([lat, lon], targetZoom, { duration: 1.5 });
                } else if (geoItem.boundingbox && geoItem.boundingbox.length === 4) {
                    const centerLat = (parseFloat(geoItem.boundingbox[0]) + parseFloat(geoItem.boundingbox[1])) / 2;
                    const centerLon = (parseFloat(geoItem.boundingbox[2]) + parseFloat(geoItem.boundingbox[3])) / 2;
                    map.flyTo([centerLat, centerLon], targetZoom, { duration: 1.5 });
                }

                setTimeout(() => {
                    if (selectTipo.value === 'Estação Satélite') {
                        if (existingStations.length === 0) {
                            showMapFeedback('error', '<strong>Atenção:</strong> Não existem estações cadastradas. A primeira estação deve ser obrigatoriamente uma <strong>Estação Matriz</strong>.');
                        } else {
                            showMapFeedback('info', '<strong>Modo Satélite Ativo:</strong> Clique dentro do <strong>raio de 200 metros</strong> de qualquer estação já cadastrada.');
                        }
                    } else {
                        hideMapFeedback();
                    }
                }, 1600);
            }

            // Formatação do MAC Address
            const inputMac = document.getElementById('mac_address');
            inputMac.addEventListener('input', function (e) {
                let v = e.target.value.replace(/[^A-Fa-f0-9]/g, '').toUpperCase();
                let formatted = '';
                for (let i = 0; i < v.length && i < 12; i++) {
                    if (i > 0 && i % 2 === 0) formatted += ':';
                    formatted += v[i];
                }
                e.target.value = formatted;
            });

            // --- 2. Leaflet Map & Camadas Geoespaciais ---
            const map = L.map('map-cadastro', {
                zoomControl: true,
                attributionControl: true
            }).setView([-23.55052, -46.633308], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap contributors</a>, Tiles style by Humanitarian OpenStreetMap Team hosted by OpenStreetMap France',
                maxZoom: 19,
                minZoom: 3
            }).addTo(map);

            let existingStations = [];
            const existingMarkersLayer = L.layerGroup().addTo(map);
            const existingLinesLayer = L.layerGroup().addTo(map);
            const circles200mLayer = L.layerGroup();
            let newStationMarker = null;
            let dynamicConnectionLine = null;

            function showMapFeedback(type, message) {
                mapFeedback.classList.remove('hidden', 'bg-emerald-50', 'dark:bg-emerald-950/50', 'text-emerald-800', 'dark:text-emerald-200', 'border-emerald-200', 'dark:border-emerald-800', 'bg-cinnabar-50', 'dark:bg-cinnabar-950/50', 'text-cinnabar-800', 'dark:text-cinnabar-200', 'border-cinnabar-200', 'dark:border-cinnabar-800', 'bg-spindle-50', 'dark:bg-spindle-950/40', 'text-spindle-800', 'dark:text-spindle-200', 'border-spindle-200', 'dark:border-spindle-800');
                
                if (type === 'success') {
                    mapFeedback.classList.add('bg-emerald-50', 'dark:bg-emerald-950/50', 'text-emerald-800', 'dark:text-emerald-200', 'border', 'border-emerald-200', 'dark:border-emerald-800');
                } else if (type === 'error') {
                    mapFeedback.classList.add('bg-cinnabar-50', 'dark:bg-cinnabar-950/50', 'text-cinnabar-800', 'dark:text-cinnabar-200', 'border', 'border-cinnabar-200', 'dark:border-cinnabar-800');
                } else {
                    mapFeedback.classList.add('bg-spindle-50', 'dark:bg-spindle-950/40', 'text-spindle-800', 'dark:text-spindle-200', 'border', 'border-spindle-200', 'dark:border-spindle-800');
                }
                mapFeedback.innerHTML = message;
            }

            function hideMapFeedback() {
                mapFeedback.classList.add('hidden');
            }

            // Inicializa as estações já cadastradas no mapa com seus círculos de raio 200m e linhas de conexão
            function inicializarEstacoesExistentes(estacoes) {
                existingMarkersLayer.clearLayers();
                existingLinesLayer.clearLayers();
                circles200mLayer.clearLayers();

                existingStations = (estacoes || []).map(e => ({
                    ...e,
                    id: e.id || e.private_id,
                    latitude: parseFloat(e.latitude),
                    longitude: parseFloat(e.longitude),
                    origem_latitude: e.origem_latitude !== null ? parseFloat(e.origem_latitude) : null,
                    origem_longitude: e.origem_longitude !== null ? parseFloat(e.origem_longitude) : null,
                })).filter(e => !isNaN(e.latitude) && !isNaN(e.longitude));

                const stationsById = {};
                existingStations.forEach(s => {
                    stationsById[s.id] = s;
                });

                const bounds = L.latLngBounds([]);

                existingStations.forEach(est => {
                    const isMatriz = est.tipo_estacao === 'Estação Matriz';
                    const markerColor = isMatriz ? '#1f68f1' : '#8b5cf6';
                    const markerRadius = isMatriz ? 8 : 6;
                    const latLng = [est.latitude, est.longitude];

                    // 1. Marcador no mapa para todas as estações existentes
                    const circleMarker = L.circleMarker(latLng, {
                        radius: markerRadius,
                        fillColor: markerColor,
                        color: '#ffffff',
                        weight: 2,
                        opacity: 1,
                        fillOpacity: 0.95
                    });

                    const badgeTipo = isMatriz
                        ? '<span style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 10px;">ESTAÇÃO MATRIZ</span>'
                        : '<span style="background: #f3e8ff; color: #7e22ce; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-size: 10px;">ESTAÇÃO SATÉLITE</span>';

                    circleMarker.bindPopup(`
                        <div style="font-family: sans-serif; font-size: 12px; line-height: 1.45; min-width: 190px;">
                            <div style="margin-bottom: 4px;">${badgeTipo} <span style="font-weight: bold; color: #334155;">#${est.id}</span></div>
                            <span style="font-family: monospace; font-size: 11px; color: #334155;">MAC: ${est.mac_address || 'N/A'}</span><br>
                            <span style="color: #475569;">${est.bairro || ''} ${est.cidade ? ' - ' + est.cidade : ''}</span><br>
                            ${est.endereco ? '<span style="color: #1f68f1; font-weight: 500; display: block; margin-top: 2px;">📍 ' + est.endereco + '</span>' : ''}
                            ${est.estacao_origem_id ? '<span style="color: #64748b; font-size: 11px; display: block; margin-top: 2px;">Conectada à Estação #' + est.estacao_origem_id + '</span>' : ''}
                            <span style="color: ${isMatriz ? '#0284c7' : '#7e22ce'}; font-weight: 600; font-size: 11px; display: block; margin-top: 4px;">Raio de 200m para novas conexões</span>
                            <small style="color: #94a3b8; display: block; margin-top: 2px;">Lat: ${est.latitude.toFixed(5)}, Lng: ${est.longitude.toFixed(5)}</small>
                        </div>
                    `);

                    existingMarkersLayer.addLayer(circleMarker);
                    bounds.extend(latLng);

                    // 2. Círculo de 200m de raio para TODAS as estações cadastradas (Matriz e Satélite)
                    const radiusCircle = L.circle(latLng, {
                        radius: 200,
                        color: isMatriz ? '#1f68f1' : '#8b5cf6',
                        weight: 1.5,
                        dashArray: '5, 5',
                        fillColor: isMatriz ? '#3588fc' : '#a78bfa',
                        fillOpacity: 0.14,
                        interactive: false
                    });
                    circles200mLayer.addLayer(radiusCircle);

                    // 3. Linha traçando onde a estação já cadastrada está se conectando
                    let parentLatLng = null;
                    if (est.origem_latitude !== null && est.origem_longitude !== null && !isNaN(est.origem_latitude) && !isNaN(est.origem_longitude)) {
                        parentLatLng = [est.origem_latitude, est.origem_longitude];
                    } else if (est.estacao_origem_id && stationsById[est.estacao_origem_id]) {
                        parentLatLng = [stationsById[est.estacao_origem_id].latitude, stationsById[est.estacao_origem_id].longitude];
                    } else if (est.matriz_pai_id && stationsById[est.matriz_pai_id] && stationsById[est.matriz_pai_id].id !== est.id) {
                        parentLatLng = [stationsById[est.matriz_pai_id].latitude, stationsById[est.matriz_pai_id].longitude];
                    }

                    if (parentLatLng) {
                        const connLine = L.polyline([parentLatLng, latLng], {
                            color: '#64748b',
                            weight: 2,
                            dashArray: '4, 4',
                            opacity: 0.75
                        });
                        connLine.bindTooltip(`Conexão: Estação #${est.id} ➔ Origem #${est.estacao_origem_id || est.matriz_pai_id}`, { sticky: true });
                        existingLinesLayer.addLayer(connLine);
                    }
                });

                // Centralização inteligente do mapa
                if (bounds.isValid()) {
                    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 });
                } else if (fixedCidade) {
                    focarMapaNoMunicipio(fixedCidade.nome, fixedCidade.uf);
                }

                if (selectTipo.value === 'Estação Satélite') {
                    ativarModoSatelite();
                } else {
                    ativarModoMatriz();
                }
            }

            // Inicialização das estações pré-carregadas pelo backend
            const preloadedStations = @json($estacoesExistentes ?? []);
            if (Array.isArray(preloadedStations) && preloadedStations.length > 0) {
                inicializarEstacoesExistentes(preloadedStations);
            } else {
                fetch('{{ route('estacoes.coordenadas') }}')
                    .then(res => res.json())
                    .then(estacoes => inicializarEstacoesExistentes(estacoes))
                    .catch(err => console.error('Erro ao buscar coordenadas das estações:', err));
            }

            // Alternância entre Estação Matriz e Estação Satélite
            function ativarModoSatelite() {
                tipoInfoMatriz.classList.add('hidden');
                tipoInfoSatelite.classList.remove('hidden');
                legendaRaio.classList.remove('hidden');
                legendaConexao.classList.remove('hidden');

                if (existingStations.length === 0) {
                    showMapFeedback('error', '<strong>Atenção:</strong> Não existem estações cadastradas. A primeira estação do sistema deve ser obrigatoriamente uma <strong>Estação Matriz</strong>.');
                } else {
                    showMapFeedback('info', '<strong>Modo Satélite Ativo:</strong> Clique ou arraste a no máximo <strong>200 metros</strong> de qualquer estação existente. A estação será grudada à rua mais próxima (Snap to Road).');
                }

                if (!map.hasLayer(circles200mLayer)) {
                    map.addLayer(circles200mLayer);
                }

                if (newStationMarker) {
                    const latLng = newStationMarker.getLatLng();
                    const validacao = validarDistancia200m(latLng.lat, latLng.lng);
                    if (validacao.valido && validacao.estacaoMaisProxima) {
                        atualizarLinhaConexaoSatelite(latLng.lat, latLng.lng, validacao.estacaoMaisProxima);
                    } else {
                        removerMarcadorNovo();
                        showMapFeedback('error', `A posição selecionada estava fora do raio de 200m de qualquer estação cadastrada (${validacao.menorDistancia ? Math.round(validacao.menorDistancia) + 'm' : ''}). O ponto foi resetado.`);
                    }
                }
            }

            function ativarModoMatriz() {
                tipoInfoMatriz.classList.remove('hidden');
                tipoInfoSatelite.classList.add('hidden');
                legendaRaio.classList.add('hidden');
                legendaConexao.classList.add('hidden');

                if (map.hasLayer(circles200mLayer)) {
                    map.removeLayer(circles200mLayer);
                }

                removerLinhaConexaoSatelite();
                showMapFeedback('info', '<strong>Modo Matriz Ativo:</strong> Posicione a <strong>Estação Matriz</strong> em qualquer ponto. A estação será grudada à rua mais próxima (Snap to Road).');
            }

            selectTipo.addEventListener('change', function () {
                if (this.value === 'Estação Satélite') {
                    ativarModoSatelite();
                } else {
                    ativarModoMatriz();
                }
            });

            // Validação de Proximidade dos 200m em relação a QUALQUER estação existente
            function validarDistancia200m(lat, lng) {
                if (existingStations.length === 0) {
                    return { valido: false, menorDistancia: null, estacaoMaisProxima: null, semEstacoes: true };
                }

                const clickPoint = L.latLng(lat, lng);
                let menorDistancia = Infinity;
                let estacaoMaisProxima = null;

                existingStations.forEach(st => {
                    const stPoint = L.latLng(st.latitude, st.longitude);
                    const dist = clickPoint.distanceTo(stPoint);
                    if (dist < menorDistancia) {
                        menorDistancia = dist;
                        estacaoMaisProxima = st;
                    }
                });

                return {
                    valido: menorDistancia <= 200.0,
                    menorDistancia: menorDistancia,
                    estacaoMaisProxima: estacaoMaisProxima,
                    semEstacoes: false
                };
            }

            // Atualização da Linha Dinâmica de Conexão da nova Satélite
            function atualizarLinhaConexaoSatelite(lat, lng, estMaisProxima) {
                if (!estMaisProxima) {
                    removerLinhaConexaoSatelite();
                    return;
                }

                const startPoint = [lat, lng];
                const targetPoint = [estMaisProxima.latitude, estMaisProxima.longitude];

                if (!dynamicConnectionLine) {
                    dynamicConnectionLine = L.polyline([startPoint, targetPoint], {
                        color: '#10b981',
                        weight: 3,
                        dashArray: '6, 6',
                        opacity: 0.95
                    }).addTo(map);
                } else {
                    dynamicConnectionLine.setLatLngs([startPoint, targetPoint]);
                }

                inputEstacaoOrigemId.value = estMaisProxima.id;

                const dist = Math.round(L.latLng(lat, lng).distanceTo(L.latLng(estMaisProxima.latitude, estMaisProxima.longitude)));
                const targetNome = `Estação #${estMaisProxima.id} (${estMaisProxima.tipo_estacao})`;

                conexaoInfoText.innerHTML = `Ligando a <strong>${targetNome}</strong> a <strong>${dist}m</strong> de distância.`;
                conexaoInfo.classList.remove('hidden');
            }

            function removerLinhaConexaoSatelite() {
                if (dynamicConnectionLine) {
                    map.removeLayer(dynamicConnectionLine);
                    dynamicConnectionLine = null;
                }
                inputEstacaoOrigemId.value = '';
                conexaoInfo.classList.add('hidden');
            }

            function definirCoordenadasFormulario(lat, lng, feedbackMsg = null) {
                const latFormat = lat.toFixed(6);
                const lngFormat = lng.toFixed(6);

                inputLat.value = latFormat;
                inputLng.value = lngFormat;

                coordsDisplay.classList.remove('bg-athens-gray-50', 'dark:bg-athens-gray-800/60', 'border-athens-gray-200', 'dark:border-athens-gray-700');
                coordsDisplay.classList.add('bg-emerald-50', 'dark:bg-emerald-950/40', 'border-emerald-200', 'dark:border-emerald-800');

                coordsBadge.classList.remove('bg-athens-gray-200', 'dark:bg-athens-gray-700', 'text-athens-gray-600', 'dark:text-athens-gray-300');
                coordsBadge.classList.add('bg-emerald-200', 'dark:bg-emerald-900', 'text-emerald-800', 'dark:text-emerald-200');
                coordsBadge.textContent = 'Definido';

                coordsText.innerHTML = `<span class="font-bold text-emerald-900 dark:text-emerald-300">Lat:</span> ${latFormat} | <span class="font-bold text-emerald-900 dark:text-emerald-300">Lng:</span> ${lngFormat}`;

                if (feedbackMsg) {
                    showMapFeedback('success', feedbackMsg);
                }
            }

            function removerMarcadorNovo() {
                if (newStationMarker) {
                    map.removeLayer(newStationMarker);
                    newStationMarker = null;
                }
                removerLinhaConexaoSatelite();
                inputLat.value = '';
                inputLng.value = '';

                coordsDisplay.classList.add('bg-athens-gray-50', 'dark:bg-athens-gray-800/60', 'border-athens-gray-200', 'dark:border-athens-gray-700');
                coordsDisplay.classList.remove('bg-emerald-50', 'dark:bg-emerald-950/40', 'border-emerald-200', 'dark:border-emerald-800');

                coordsBadge.classList.add('bg-athens-gray-200', 'dark:bg-athens-gray-700', 'text-athens-gray-600', 'dark:text-athens-gray-300');
                coordsBadge.classList.remove('bg-emerald-200', 'dark:bg-emerald-900', 'text-emerald-800', 'dark:text-emerald-200');
                coordsBadge.textContent = 'Pendente';

                coordsText.innerHTML = '<span class="text-athens-gray-400 dark:text-athens-gray-500 italic">Clique no mapa ao lado para marcar a coordenada exata.</span>';
            }

            const newPinIcon = L.divIcon({
                className: 'custom-pin-pulse',
                html: '<div style="background-color: #2ecc71; width: 16px; height: 16px; border-radius: 50%; border: 3px solid #ffffff; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            });

            // Executa chamada AJAX ao Snap to Road nativo no backend
            function executarSnapToRoad(lat, lng, origemLat, origemLng, callbackSucesso, callbackErro) {
                const payload = {
                    latitude: lat,
                    longitude: lng,
                    max_distancia: 200.0,
                };
                if (origemLat !== null && origemLng !== null && !isNaN(origemLat) && !isNaN(origemLng)) {
                    payload.origem_latitude = origemLat;
                    payload.origem_longitude = origemLng;
                }

                fetch('{{ route('estacoes.snap-to-road') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('SnapToRoad falhou ou fora do alcance.');
                    }
                    return res.json();
                })
                .then(data => {
                    if (!data || !data.snapped) {
                        throw new Error('Nenhuma via pública viável encontrada.');
                    }
                    callbackSucesso(data);
                })
                .catch(err => {
                    if (typeof callbackErro === 'function') {
                        callbackErro(err);
                    }
                });
            }

            // Posiciona a estação (Matriz ou Satélite) grudando no leito viário mais próximo (Snap to Road)
            function posicionarEstacaoComSnap(lat, lng, isDrag = false, dragStartLatLng = null) {
                const tipoAtual = selectTipo.value;

                if (tipoAtual === 'Estação Matriz') {
                    showMapFeedback('info', '<span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-blue-dianne-500 animate-ping"></span> Encaixando <strong>Estação Matriz</strong> na via pública mais próxima (Snap to Road)...</span>');

                    executarSnapToRoad(lat, lng, null, null, function (data) {
                        const snappedLat = data.latitude;
                        const snappedLng = data.longitude;

                        if (!newStationMarker) {
                            newStationMarker = L.marker([snappedLat, snappedLng], { icon: newPinIcon, draggable: true }).addTo(map);
                            vincularEventosDragMarcador(newStationMarker);
                        } else {
                            newStationMarker.setLatLng([snappedLat, snappedLng]);
                        }

                        removerLinhaConexaoSatelite();
                        const ruaNome = data.nome_rua || 'Via Pública';
                        newStationMarker.bindPopup(`<b>Nova Estação Matriz</b><br><span id="popup-endereco-novo" style="color: #1f68f1; font-size: 11px;">📍 ${data.endereco_completo || ruaNome}</span><br><small style="color: #7b96b6;">Lat: ${snappedLat.toFixed(5)}, Lng: ${snappedLng.toFixed(5)}</small>`).openPopup();

                        definirCoordenadasFormulario(snappedLat, snappedLng, `Estação Matriz grudada na rua <strong>${ruaNome}</strong> (Snap to Road)!`);

                        buscarEnderecoReverso(snappedLat, snappedLng, function (endereco) {
                            const el = document.getElementById('popup-endereco-novo');
                            if (el) el.innerHTML = `📍 ${endereco}`;
                            showMapFeedback('success', `Estação Matriz fixada na rua <strong>${endereco}</strong> (Snap to Road)`);
                        });
                    }, function (err) {
                        console.warn('Falha no SnapToRoad da Matriz:', err);
                        if (isDrag && dragStartLatLng) {
                            newStationMarker.setLatLng(dragStartLatLng);
                            showMapFeedback('error', '<strong>Snap to Road:</strong> Nenhuma via pública encontrada próxima ao ponto solto. Posição revertida.');
                        } else {
                            showMapFeedback('error', '<strong>Snap to Road:</strong> Nenhuma via pública encontrada próxima ao local clicado. Posicione a estação sobre ou junto a uma rua.');
                            const tempAlertMarker = L.circleMarker([lat, lng], {
                                radius: 10,
                                fillColor: '#e74c3c',
                                color: '#ffffff',
                                weight: 2,
                                fillOpacity: 0.8
                            }).addTo(map);
                            setTimeout(() => map.removeLayer(tempAlertMarker), 2500);
                        }
                    });
                } else {
                    // Estação Satélite
                    const resultado = validarDistancia200m(lat, lng);

                    if (resultado.semEstacoes) {
                        showMapFeedback('error', '<strong>Bloqueado:</strong> Não há nenhuma estação cadastrada para conectar esta Satélite. Cadastre primeiro uma Estação Matriz.');
                        return;
                    }

                    if (resultado.menorDistancia > 300.0) {
                        const distFormat = Math.round(resultado.menorDistancia);
                        const nomePai = `Estação #${resultado.estacaoMaisProxima.id}`;

                        if (isDrag && dragStartLatLng) {
                            newStationMarker.setLatLng(dragStartLatLng);
                            const valOrig = validarDistancia200m(dragStartLatLng.lat, dragStartLatLng.lng);
                            if (valOrig.estacaoMaisProxima) {
                                atualizarLinhaConexaoSatelite(dragStartLatLng.lat, dragStartLatLng.lng, valOrig.estacaoMaisProxima);
                            }
                            showMapFeedback('error', `<strong>Fora do Alcance:</strong> O sensor foi solto a <strong>${distFormat} metros</strong> de qualquer estação existente (máx. 200m). Posição revertida.`);
                        } else {
                            showMapFeedback('error', `<strong>Posição Inválida:</strong> O ponto selecionado está a <strong>${distFormat} metros</strong> da estação mais próxima (${nomePai}). Estações Satélites devem estar a no máximo <strong>200 metros de uma estação já existente</strong>.`);
                            const tempAlertMarker = L.circleMarker([lat, lng], {
                                radius: 10,
                                fillColor: '#e74c3c',
                                color: '#ffffff',
                                weight: 2,
                                fillOpacity: 0.8
                            }).addTo(map);
                            setTimeout(() => map.removeLayer(tempAlertMarker), 2500);
                        }
                        return;
                    }

                    showMapFeedback('info', `<span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span> Encaixando <strong>Estação Satélite</strong> na via pública a ≤ 200m da Estação #${resultado.estacaoMaisProxima.id} (Snap to Road)...</span>`);

                    executarSnapToRoad(lat, lng, resultado.estacaoMaisProxima.latitude, resultado.estacaoMaisProxima.longitude, function (data) {
                        const snappedLat = data.latitude;
                        const snappedLng = data.longitude;

                        if (!newStationMarker) {
                            newStationMarker = L.marker([snappedLat, snappedLng], { icon: newPinIcon, draggable: true }).addTo(map);
                            vincularEventosDragMarcador(newStationMarker);
                        } else {
                            newStationMarker.setLatLng([snappedLat, snappedLng]);
                        }

                        const distFormat = Math.round(data.distancia_origem);
                        const nomePai = `Estação #${resultado.estacaoMaisProxima.id} (${resultado.estacaoMaisProxima.tipo_estacao})`;
                        const ruaNome = data.nome_rua || 'Via Pública';

                        newStationMarker.bindPopup(`<b>Nova Estação Satélite</b><br><span id="popup-endereco-novo" style="color: #1f68f1; font-size: 11px;">📍 ${data.endereco_completo || ruaNome}</span><br><small style="color: #10b981; font-weight: 600;">Conectando a ${nomePai} a ${distFormat}m</small><br><small style="color: #7b96b6;">Lat: ${snappedLat.toFixed(5)}, Lng: ${snappedLng.toFixed(5)}</small>`).openPopup();

                        definirCoordenadasFormulario(snappedLat, snappedLng, `Estação Satélite grudada na rua <strong>${ruaNome}</strong> a <strong>${distFormat}m</strong> de ${nomePai} (Snap to Road)!`);
                        atualizarLinhaConexaoSatelite(snappedLat, snappedLng, resultado.estacaoMaisProxima);

                        buscarEnderecoReverso(snappedLat, snappedLng, function (endereco) {
                            const el = document.getElementById('popup-endereco-novo');
                            if (el) el.innerHTML = `📍 ${endereco}`;
                            showMapFeedback('success', `Estação Satélite fixada na rua <strong>${endereco}</strong> (conectada a ${nomePai} a ${distFormat}m)`);
                        });
                    }, function (err) {
                        console.warn('Falha no SnapToRoad da Satélite:', err);
                        const nomePai = `Estação #${resultado.estacaoMaisProxima.id}`;

                        if (isDrag && dragStartLatLng) {
                            newStationMarker.setLatLng(dragStartLatLng);
                            const valOrig = validarDistancia200m(dragStartLatLng.lat, dragStartLatLng.lng);
                            if (valOrig.estacaoMaisProxima) {
                                atualizarLinhaConexaoSatelite(dragStartLatLng.lat, dragStartLatLng.lng, valOrig.estacaoMaisProxima);
                            }
                            showMapFeedback('error', `<strong>Snap to Road:</strong> Nenhuma via pública válida encontrada a ≤ 200m da ${nomePai}. Posição revertida.`);
                        } else {
                            showMapFeedback('error', `<strong>Posição Inválida:</strong> Snap to Road não encontrou via pública válida a ≤ 200m da ${nomePai}. Clique mais próximo.`);
                            const tempAlertMarker = L.circleMarker([lat, lng], {
                                radius: 10,
                                fillColor: '#e74c3c',
                                color: '#ffffff',
                                weight: 2,
                                fillOpacity: 0.8
                            }).addTo(map);
                            setTimeout(() => map.removeLayer(tempAlertMarker), 2500);
                        }
                    });
                }
            }

            function vincularEventosDragMarcador(marker) {
                let dragStartLatLng = null;

                marker.on('dragstart', function (ev) {
                    dragStartLatLng = ev.target.getLatLng();
                });

                marker.on('drag', function (ev) {
                    const pos = ev.target.getLatLng();
                    if (selectTipo.value === 'Estação Satélite') {
                        const val = validarDistancia200m(pos.lat, pos.lng);
                        if (val.estacaoMaisProxima) {
                            atualizarLinhaConexaoSatelite(pos.lat, pos.lng, val.estacaoMaisProxima);
                            if (val.valido) {
                                dynamicConnectionLine.setStyle({ color: '#10b981' });
                            } else {
                                dynamicConnectionLine.setStyle({ color: '#ef4444' });
                            }
                        }
                    }
                });

                marker.on('dragend', function (ev) {
                    const pos = ev.target.getLatLng();
                    posicionarEstacaoComSnap(pos.lat, pos.lng, true, dragStartLatLng);
                });
            }

            // Busca Reversa com auto-seleção de Estado, Cidade e Bairro
            function buscarEnderecoReverso(lat, lng, callback) {
                const cidParam = fixedCidade ? `&cidade_id=${fixedCidade.id}` : (selectCidade.value ? `&cidade_id=${selectCidade.value}` : '');
                fetch(`/geocoding/reverse?lat=${lat}&lng=${lng}${cidParam}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data) return;

                        // Validação de Jurisdição Municipal
                        if (fixedCidade && data.cidade_id && data.cidade_id !== fixedCidade.id) {
                            showMapFeedback('error', `<strong>Fora da Jurisdição:</strong> O ponto clicado pertence ao município <strong>${data.cidade || 'outro'}</strong>. Sua conta só pode cadastrar em <strong>${fixedCidade.nome} (${fixedCidade.uf})</strong>.`);
                            removerMarcadorNovo();
                            return;
                        }

                        // Sincronização automática em cascata se não tiver selecionado
                        if (!fixedCidade) {
                            if (data.estado_id && (!selectEstado.value || selectEstado.value != data.estado_id)) {
                                selectEstado.value = data.estado_id;
                                carregarCidades(data.estado_id, function () {
                                    if (data.cidade_id) {
                                        selectCidade.value = data.cidade_id;
                                        carregarBairros(data.cidade_id, data.bairro_id, function () {
                                            sincronizarBairro(data.bairro_id, data.bairro);
                                        });
                                    }
                                });
                            } else if (data.cidade_id && (!selectCidade.value || selectCidade.value != data.cidade_id)) {
                                selectCidade.value = data.cidade_id;
                                carregarBairros(data.cidade_id, data.bairro_id, function () {
                                    sincronizarBairro(data.bairro_id, data.bairro);
                                });
                            } else if (data.bairro_id || data.bairro) {
                                sincronizarBairro(data.bairro_id, data.bairro);
                            }
                        } else {
                            sincronizarBairro(data.bairro_id, data.bairro);
                        }

                        if (data.endereco) {
                            if (typeof callback === 'function') {
                                callback(data.endereco);
                            } else {
                                const el = document.getElementById('popup-endereco-novo');
                                if (el) el.innerHTML = `📍 ${data.endereco}`;
                            }
                        }
                    })
                    .catch(err => console.warn('Falha no reverse geocoding:', err));
            }

            // Evento de Clique no Mapa
            map.on('click', function (e) {
                const clickedLat = e.latlng.lat;
                const clickedLng = e.latlng.lng;
                posicionarEstacaoComSnap(clickedLat, clickedLng, false, null);
            });

            // Restaura marcador se houver coordenadas antigas
            if (inputLat.value && inputLng.value) {
                const oldLat = parseFloat(inputLat.value);
                const oldLng = parseFloat(inputLng.value);
                if (!isNaN(oldLat) && !isNaN(oldLng)) {
                    newStationMarker = L.marker([oldLat, oldLng], { icon: newPinIcon, draggable: true }).addTo(map);
                    vincularEventosDragMarcador(newStationMarker);
                    map.setView([oldLat, oldLng], 15);
                    if (selectTipo.value === 'Estação Satélite') {
                        const val = validarDistancia200m(oldLat, oldLng);
                        if (val.valido && val.estacaoMaisProxima) {
                            atualizarLinhaConexaoSatelite(oldLat, oldLng, val.estacaoMaisProxima);
                        }
                    }
                }
            }
        });
    </script>
    @endpush
</x-layouts.app>

