<x-layouts.app title="Cadastrar Nova Estação - OpenAir Metrics">
    <div class="flex h-full w-full bg-athens-gray-50">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="estacoes" />

        <!-- Conteúdo Principal com Scroll Vertical -->
        <main class="flex-1 overflow-y-auto p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Breadcrumbs e Cabeçalho -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 text-xs font-medium text-athens-gray-500 mb-1">
                            <a href="{{ route('estacoes.index') }}" class="hover:text-blue-dianne-700 transition">Minhas Estações</a>
                            <span>/</span>
                            <span class="text-blue-dianne-950 font-semibold">Nova Estação</span>
                        </div>
                        <h1 class="text-2xl font-bold text-blue-dianne-950 tracking-tight">Cadastro de Nova Estação</h1>
                        <p class="text-sm text-athens-gray-600 mt-0.5">Preencha os dados da placa IoT e selecione a localização no mapa.</p>
                    </div>

                    <a href="{{ route('estacoes.index') }}" class="inline-flex items-center gap-2 bg-white text-athens-gray-700 hover:bg-athens-gray-100 px-4 py-2.5 rounded-lg font-semibold text-sm border border-athens-gray-200 shadow-sm transition">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Voltar para Listagem
                    </a>
                </div>

                <!-- Exibição de Erros Globais de Validação -->
                @if ($errors->any())
                    <div class="bg-cinnabar-50 border border-cinnabar-200 text-cinnabar-800 px-5 py-4 rounded-xl shadow-sm space-y-1">
                        <div class="flex items-center gap-2 font-bold text-sm text-cinnabar-900">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-cinnabar-600" />
                            Atenção: Por favor, corrija os erros abaixo antes de salvar:
                        </div>
                        <ul class="list-disc list-inside text-xs space-y-1 text-cinnabar-700 ml-2">
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
                        <form method="POST" action="{{ route('estacoes.store') }}" id="form-estacao" class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-6 space-y-6">
                            @csrf

                            <!-- Seção 1: Dados da Placa e Hardware -->
                            <div>
                                <h3 class="text-sm font-bold text-blue-dianne-950 uppercase tracking-wider mb-4 pb-2 border-b border-athens-gray-100 flex items-center gap-2">
                                    <x-heroicon-o-cpu-chip class="w-4 h-4 text-blue-dianne-600" />
                                    Dados do Dispositivo
                                </h3>

                                <div class="space-y-4">
                                    <!-- MAC Address -->
                                    <div>
                                        <label for="mac_address" class="block text-xs font-bold text-blue-dianne-950 uppercase tracking-wider mb-1">
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
                                            class="w-full px-3.5 py-2.5 text-sm font-mono uppercase bg-athens-gray-50 border {{ $errors->has('mac_address') ? 'border-cinnabar-400 focus:ring-cinnabar-400' : 'border-athens-gray-300 focus:border-blue-dianne-500 focus:ring-blue-dianne-500' }} rounded-lg focus:outline-none focus:ring-2 focus:bg-white transition"
                                        >
                                        @error('mac_address')
                                            <p class="text-xs text-cinnabar-600 mt-1 font-medium">{{ $message }}</p>
                                        @enderror
                                        <p class="text-[11px] text-athens-gray-500 mt-1">Identificador físico único do hardware ESP32 / Arduino.</p>
                                    </div>

                                    <!-- Tipo de Estação -->
                                    <div>
                                        <label for="tipo_estacao" class="block text-xs font-bold text-blue-dianne-950 uppercase tracking-wider mb-1">
                                            Tipo de Estação <span class="text-cinnabar-500">*</span>
                                        </label>
                                        <select 
                                            name="tipo_estacao" 
                                            id="tipo_estacao" 
                                            required
                                            class="w-full px-3.5 py-2.5 text-sm bg-athens-gray-50 border {{ $errors->has('tipo_estacao') ? 'border-cinnabar-400' : 'border-athens-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-dianne-500 focus:bg-white transition"
                                        >
                                            <option value="Estação Matriz" {{ old('tipo_estacao') === 'Estação Matriz' ? 'selected' : '' }}>Estação Matriz (Ponto Livre)</option>
                                            <option value="Estação Satélite" {{ old('tipo_estacao') === 'Estação Satélite' ? 'selected' : '' }}>Estação Satélite (Raio máx. 200m)</option>
                                        </select>
                                        @error('tipo_estacao')
                                            <p class="text-xs text-cinnabar-600 mt-1 font-medium">{{ $message }}</p>
                                        @enderror

                                        <!-- Card Informativo do Tipo Selecionado -->
                                        <div id="tipo-info-matriz" class="mt-3 p-3 bg-dodger-blue-50 border border-dodger-blue-200 rounded-lg text-xs text-dodger-blue-900 flex items-start gap-2.5">
                                            <x-heroicon-o-information-circle class="w-4 h-4 text-dodger-blue-600 shrink-0 mt-0.5" />
                                            <div>
                                                <span class="font-bold">Estação Matriz:</span> Pode ser posicionada livremente em qualquer coordenada geográfica sem restrição de proximidade.
                                            </div>
                                        </div>

                                        <div id="tipo-info-satelite" class="mt-3 p-3 bg-purple-50 border border-purple-200 rounded-lg text-xs text-purple-900 flex items-start gap-2.5 hidden">
                                            <x-heroicon-o-radio class="w-4 h-4 text-purple-600 shrink-0 mt-0.5" />
                                            <div>
                                                <span class="font-bold">Estação Satélite:</span> Deve obrigatoriamente estar a uma distância máxima de <strong class="text-purple-950 underline">200 metros</strong> de qualquer estação já existente.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Seção 2: Localidade IBGE (Cascata) -->
                            <div>
                                <h3 class="text-sm font-bold text-blue-dianne-950 uppercase tracking-wider mb-4 pb-2 border-b border-athens-gray-100 flex items-center gap-2">
                                    <x-heroicon-o-map-pin class="w-4 h-4 text-blue-dianne-600" />
                                    Localidade (IBGE)
                                </h3>

                                <div class="space-y-4">
                                    <!-- Estado -->
                                    <div>
                                        <label for="select_estado" class="block text-xs font-bold text-blue-dianne-950 uppercase tracking-wider mb-1">
                                            Estado (UF) <span class="text-cinnabar-500">*</span>
                                        </label>
                                        <select 
                                            id="select_estado" 
                                            class="w-full px-3.5 py-2.5 text-sm bg-athens-gray-50 border border-athens-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-dianne-500 focus:bg-white transition"
                                        >
                                            <option value="">Selecione o Estado...</option>
                                            @foreach ($estados as $estado)
                                                <option value="{{ $estado->id }}">{{ $estado->nome }} ({{ $estado->uf }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Cidade -->
                                    <div>
                                        <label for="select_cidade" class="block text-xs font-bold text-blue-dianne-950 uppercase tracking-wider mb-1">
                                            Cidade <span class="text-cinnabar-500">*</span>
                                        </label>
                                        <select 
                                            id="select_cidade" 
                                            disabled
                                            class="w-full px-3.5 py-2.5 text-sm bg-athens-gray-100 border border-athens-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-dianne-500 focus:bg-white transition disabled:opacity-60 disabled:cursor-not-allowed"
                                        >
                                            <option value="">Selecione o Estado primeiro...</option>
                                        </select>
                                    </div>

                                    <!-- Bairro -->
                                    <div>
                                        <label for="bairro_id" class="block text-xs font-bold text-blue-dianne-950 uppercase tracking-wider mb-1">
                                            Bairro <span class="text-cinnabar-500">*</span>
                                        </label>
                                        <select 
                                            name="bairro_id" 
                                            id="bairro_id" 
                                            required 
                                            disabled
                                            class="w-full px-3.5 py-2.5 text-sm bg-athens-gray-100 border {{ $errors->has('bairro_id') ? 'border-cinnabar-400' : 'border-athens-gray-300' }} rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-dianne-500 focus:bg-white transition disabled:opacity-60 disabled:cursor-not-allowed"
                                        >
                                            <option value="">Selecione a Cidade primeiro...</option>
                                        </select>
                                        @error('bairro_id')
                                            <p class="text-xs text-cinnabar-600 mt-1 font-medium">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <!-- Seção 3: Coordenadas do Mapa (Inputs Ocultos + Visualizador) -->
                            <div>
                                <h3 class="text-sm font-bold text-blue-dianne-950 uppercase tracking-wider mb-4 pb-2 border-b border-athens-gray-100 flex items-center gap-2">
                                    <x-heroicon-o-globe-americas class="w-4 h-4 text-blue-dianne-600" />
                                    Posição Geográfica
                                </h3>

                                <!-- Inputs Ocultos -->
                                <input type="hidden" name="latitude" id="input_latitude" value="{{ old('latitude') }}">
                                <input type="hidden" name="longitude" id="input_longitude" value="{{ old('longitude') }}">

                                <!-- Visualizador de Coordenadas Selecionadas -->
                                <div id="coordenadas-display" class="p-3.5 rounded-lg border {{ old('latitude') ? 'bg-emerald-50 border-emerald-200' : 'bg-athens-gray-50 border-athens-gray-200' }} transition-colors">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs font-bold text-athens-gray-600 uppercase tracking-wider">Ponto Selecionado:</span>
                                        <span id="coords-status-badge" class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ old('latitude') ? 'bg-emerald-200 text-emerald-800' : 'bg-athens-gray-200 text-athens-gray-600' }}">
                                            {{ old('latitude') ? 'Definido' : 'Pendente' }}
                                        </span>
                                    </div>
                                    <div class="mt-2 text-xs font-mono text-blue-dianne-950" id="coords-text">
                                        @if (old('latitude') && old('longitude'))
                                            Lat: {{ old('latitude') }} | Lng: {{ old('longitude') }}
                                        @else
                                            <span class="text-athens-gray-400 italic">Clique no mapa ao lado para marcar a coordenada exata.</span>
                                        @endif
                                    </div>
                                </div>
                                @error('latitude')
                                    <p class="text-xs text-cinnabar-600 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                                @error('longitude')
                                    <p class="text-xs text-cinnabar-600 mt-1 font-medium">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Botões de Ação do Formulário -->
                            <div class="pt-4 border-t border-athens-gray-200 flex items-center justify-end gap-3">
                                <a href="{{ route('estacoes.index') }}" class="px-4 py-2.5 text-sm font-semibold text-athens-gray-600 hover:bg-athens-gray-100 rounded-lg transition">
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
                        <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 overflow-hidden flex flex-col flex-1 min-h-[550px]">
                            
                            <!-- Cabeçalho do Mapa -->
                            <div class="px-5 py-3.5 border-b border-athens-gray-200 bg-athens-gray-50/70 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-map class="w-5 h-5 text-blue-dianne-600" />
                                    <div>
                                        <h3 class="text-sm font-bold text-blue-dianne-950">Mapa de Implantação</h3>
                                        <p class="text-[11px] text-athens-gray-500">Clique no mapa para posicionar a estação IoT.</p>
                                    </div>
                                </div>

                                <!-- Legenda Rápida -->
                                <div class="flex items-center gap-3 text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3 h-3 rounded-full bg-dodger-blue-500 inline-block"></span>
                                        <span class="text-athens-gray-600">Matriz Existente</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <span class="w-3 h-3 rounded-full bg-purple-500 inline-block"></span>
                                        <span class="text-athens-gray-600">Satélite Existente</span>
                                    </div>
                                    <div id="legenda-raio" class="flex items-center gap-1.5 hidden">
                                        <span class="w-3 h-3 rounded-full border-2 border-dashed border-dodger-blue-500 bg-dodger-blue-200/50 inline-block"></span>
                                        <span class="text-blue-dianne-900 font-semibold">Raio 200m</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Alerta Dinâmico Flutuante do Mapa -->
                            <div id="map-feedback" class="mx-4 mt-3 p-3 rounded-lg text-xs font-medium hidden transition-all duration-300"></div>

                            <!-- Container do Mapa Leaflet -->
                            <div class="flex-1 w-full relative min-h-[480px]">
                                <div id="map-cadastro" class="absolute inset-0 z-0 w-full h-full rounded-b-xl"></div>
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
            // Elementos do DOM
            const selectEstado = document.getElementById('select_estado');
            const selectCidade = document.getElementById('select_cidade');
            const selectBairro = document.getElementById('bairro_id');
            const selectTipo = document.getElementById('tipo_estacao');
            const inputLat = document.getElementById('input_latitude');
            const inputLng = document.getElementById('input_longitude');
            const coordsDisplay = document.getElementById('coordenadas-display');
            const coordsText = document.getElementById('coords-text');
            const coordsBadge = document.getElementById('coords-status-badge');
            const tipoInfoMatriz = document.getElementById('tipo-info-matriz');
            const tipoInfoSatelite = document.getElementById('tipo-info-satelite');
            const legendaRaio = document.getElementById('legenda-raio');
            const mapFeedback = document.getElementById('map-feedback');

            // --- 1. Cascata de Localidades (Estado -> Cidade -> Bairro) ---
            selectEstado.addEventListener('change', function () {
                const estadoId = this.value;
                selectCidade.innerHTML = '<option value="">Carregando cidades...</option>';
                selectCidade.disabled = true;
                selectBairro.innerHTML = '<option value="">Selecione a Cidade primeiro...</option>';
                selectBairro.disabled = true;

                if (!estadoId) {
                    selectCidade.innerHTML = '<option value="">Selecione o Estado primeiro...</option>';
                    return;
                }

                fetch('/api/estados/' + estadoId + '/cidades')
                    .then(res => res.json())
                    .then(cidades => {
                        selectCidade.innerHTML = '<option value="">Selecione a Cidade...</option>';
                        cidades.forEach(c => {
                            selectCidade.innerHTML += `<option value="${c.id}">${c.nome}</option>`;
                        });
                        selectCidade.disabled = false;
                    })
                    .catch(err => {
                        console.error('Erro ao carregar cidades:', err);
                        selectCidade.innerHTML = '<option value="">Erro ao carregar cidades</option>';
                    });
            });

            // Cache de Geocodificação em memória (Município + UF -> Coordenadas/Bounds)
            const geocodeCache = new Map();
            let geocodeAbortController = null;

            function focarMapaNoMunicipio(cidadeNome, estadoUf) {
                if (!cidadeNome) return;

                const queryKey = `${cidadeNome}, ${estadoUf || ''}`.trim();

                // Se já temos em cache
                if (geocodeCache.has(queryKey)) {
                    const data = geocodeCache.get(queryKey);
                    aplicarFocoNoMapa(data, 14); // Zoom 14 para Cidade
                    return;
                }

                // Cancela busca anterior em andamento se houver
                if (geocodeAbortController) {
                    geocodeAbortController.abort();
                }
                geocodeAbortController = new AbortController();

                showMapFeedback('info', `Localizando <strong>${cidadeNome}</strong> no mapa...`);

                const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(queryKey + ', Brasil')}&format=json&limit=1`;

                fetch(url, {
                    signal: geocodeAbortController.signal,
                    headers: {
                        'Accept-Language': 'pt-BR,pt;q=0.9,en;q=0.8'
                    }
                })
                .then(res => res.json())
                .then(results => {
                    if (results && results.length > 0) {
                        const item = results[0];
                        geocodeCache.set(queryKey, item);
                        aplicarFocoNoMapa(item, 14); // Zoom 14 para Cidade
                    } else {
                        // Fallback: se não encontrou com UF, tenta só o nome da cidade
                        return fetch(`https://nominatim.openstreetmap.org/search?city=${encodeURIComponent(cidadeNome)}&country=Brazil&format=json&limit=1`, {
                            signal: geocodeAbortController.signal
                        })
                        .then(res => res.json())
                        .then(fallbackResults => {
                            if (fallbackResults && fallbackResults.length > 0) {
                                const item = fallbackResults[0];
                                geocodeCache.set(queryKey, item);
                                aplicarFocoNoMapa(item, 14); // Zoom 14 para Cidade
                            }
                        });
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

                // Se já temos em cache
                if (geocodeCache.has(queryKey)) {
                    const data = geocodeCache.get(queryKey);
                    aplicarFocoNoMapa(data, 16); // Zoom 16 para Bairro
                    return;
                }

                showMapFeedback('info', `Aproximando no bairro <strong>${bairroNome}</strong>...`);

                const url = `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(queryKey + ', Brasil')}&format=json&limit=1`;

                fetch(url, {
                    headers: {
                        'Accept-Language': 'pt-BR,pt;q=0.9,en;q=0.8'
                    }
                })
                .then(res => res.json())
                .then(results => {
                    if (results && results.length > 0) {
                        const item = results[0];
                        geocodeCache.set(queryKey, item);
                        aplicarFocoNoMapa(item, 16); // Zoom 16 para Bairro
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
                    map.flyTo([lat, lon], targetZoom, {
                        duration: 1.5
                    });
                } else if (geoItem.boundingbox && geoItem.boundingbox.length === 4) {
                    const south = parseFloat(geoItem.boundingbox[0]);
                    const north = parseFloat(geoItem.boundingbox[1]);
                    const west = parseFloat(geoItem.boundingbox[2]);
                    const east = parseFloat(geoItem.boundingbox[3]);
                    const centerLat = (south + north) / 2;
                    const centerLon = (west + east) / 2;

                    map.flyTo([centerLat, centerLon], targetZoom, {
                        duration: 1.5
                    });
                }

                // Restaura o aviso contextual do modo após a transição
                setTimeout(() => {
                    if (selectTipo.value === 'Estação Satélite') {
                        if (existingStations.length === 0) {
                            showMapFeedback('error', '<strong>Atenção:</strong> Não existem estações cadastradas. A primeira estação do sistema deve ser obrigatoriamente uma <strong>Estação Matriz</strong>.');
                        } else {
                            showMapFeedback('info', '<strong>Modo Satélite Ativo:</strong> Clique apenas dentro de um dos <strong>círculos azuis de 200 metros</strong> ao redor das estações existentes.');
                        }
                    } else {
                        hideMapFeedback();
                    }
                }, 1600);
            }

            selectCidade.addEventListener('change', function () {
                const cidadeId = this.value;
                selectBairro.innerHTML = '<option value="">Buscando e cadastrando bairros no OpenStreetMap...</option>';
                selectBairro.disabled = true;

                if (!cidadeId) {
                    selectBairro.innerHTML = '<option value="">Selecione a Cidade primeiro...</option>';
                    return;
                }

                // Obtém o nome da cidade e UF do estado selecionado
                const cidadeNome = this.options[this.selectedIndex] ? this.options[this.selectedIndex].text : '';
                const estadoText = selectEstado.options[selectEstado.selectedIndex] ? selectEstado.options[selectEstado.selectedIndex].text : '';
                const ufMatch = estadoText.match(/\(([A-Z]{2})\)/);
                const estadoUf = ufMatch ? ufMatch[1] : '';

                // Centraliza e foca o mapa no município selecionado (Zoom 14)
                focarMapaNoMunicipio(cidadeNome, estadoUf);

                fetch('/api/cidades/' + cidadeId + '/bairros')
                    .then(res => res.json())
                    .then(bairros => {
                        if (!bairros || bairros.length === 0) {
                            selectBairro.innerHTML = '<option value="">Nenhum bairro encontrado</option>';
                            return;
                        }
                        selectBairro.innerHTML = '<option value="">Selecione o Bairro...</option>';
                        bairros.forEach(b => {
                            selectBairro.innerHTML += `<option value="${b.id}">${b.nome}</option>`;
                        });
                        selectBairro.disabled = false;
                    })
                    .catch(err => {
                        console.error('Erro ao carregar bairros:', err);
                        selectBairro.innerHTML = '<option value="">Erro ao carregar bairros</option>';
                    });
            });

            selectBairro.addEventListener('change', function () {
                const bairroId = this.value;
                if (!bairroId) return;

                const bairroNome = this.options[this.selectedIndex] ? this.options[this.selectedIndex].text : '';
                const cidadeNome = selectCidade.options[selectCidade.selectedIndex] ? selectCidade.options[selectCidade.selectedIndex].text : '';
                const estadoText = selectEstado.options[selectEstado.selectedIndex] ? selectEstado.options[selectEstado.selectedIndex].text : '';
                const ufMatch = estadoText.match(/\(([A-Z]{2})\)/);
                const estadoUf = ufMatch ? ufMatch[1] : '';

                // Aproxima o mapa no bairro selecionado com Zoom 16
                focarMapaNoBairro(bairroNome, cidadeNome, estadoUf);
            });

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

            // --- 2. Leaflet Map & Lógica Geoespacial dos 200m ---
            const map = L.map('map-cadastro', {
                zoomControl: true,
                attributionControl: true
            }).setView([-23.55052, -46.633308], 16); // Padrão São Paulo

            L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap contributors</a>, Tiles style by Humanitarian OpenStreetMap Team hosted by OpenStreetMap France',
                maxZoom: 19,
                minZoom: 3
            }).addTo(map);

            let existingStations = [];
            const existingMarkersLayer = L.layerGroup().addTo(map);
            const circles200mLayer = L.layerGroup();
            let newStationMarker = null;

            // Função para exibir mensagem de feedback do mapa
            function showMapFeedback(type, message) {
                mapFeedback.classList.remove('hidden', 'bg-emerald-50', 'text-emerald-800', 'border-emerald-200', 'bg-cinnabar-50', 'text-cinnabar-800', 'border-cinnabar-200', 'bg-purple-50', 'text-purple-800', 'border-purple-200');
                
                if (type === 'success') {
                    mapFeedback.classList.add('bg-emerald-50', 'text-emerald-800', 'border', 'border-emerald-200');
                } else if (type === 'error') {
                    mapFeedback.classList.add('bg-cinnabar-50', 'text-cinnabar-800', 'border', 'border-cinnabar-200');
                } else {
                    mapFeedback.classList.add('bg-purple-50', 'text-purple-800', 'border', 'border-purple-200');
                }
                mapFeedback.innerHTML = message;
            }

            function hideMapFeedback() {
                mapFeedback.classList.add('hidden');
            }

            // Carrega as coordenadas das estações já cadastradas e seus raios de 200m
            fetch('/api/estacoes/coordenadas')
                .then(res => res.json())
                .then(estacoes => {
                    existingStations = (estacoes || []).map(e => ({
                        ...e,
                        latitude: parseFloat(e.latitude),
                        longitude: parseFloat(e.longitude)
                    })).filter(e => !isNaN(e.latitude) && !isNaN(e.longitude));

                    const bounds = L.latLngBounds([]);

                    existingStations.forEach(est => {
                        const isMatriz = est.tipo_estacao === 'Estação Matriz';
                        const markerColor = isMatriz ? '#1f68f1' : '#8b5cf6';
                        const markerRadius = isMatriz ? 9 : 7;

                        // Marcador no mapa para todas as estações existentes
                        const circleMarker = L.circleMarker([est.latitude, est.longitude], {
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
                                <div style="margin-bottom: 4px;">${badgeTipo}</div>
                                <span style="font-family: monospace; font-size: 11px; color: #334155;">MAC: ${est.mac_address || 'N/A'}</span><br>
                                <span style="color: #475569;">${est.bairro || ''} ${est.cidade ? ' - ' + est.cidade : ''}</span><br>
                                ${est.endereco ? '<span style="color: #1f68f1; font-weight: 500; display: block; margin-top: 2px;">📍 ' + est.endereco + '</span>' : ''}
                                ${isMatriz ? '<span style="color: #0284c7; font-weight: 600; font-size: 11px; display: block; margin-top: 4px;">🔵 Raio de 200m para novas Satélites</span>' : ''}
                                <small style="color: #94a3b8; display: block; margin-top: 2px;">Lat: ${est.latitude.toFixed(5)}, Lng: ${est.longitude.toFixed(5)}</small>
                            </div>
                        `);

                        existingMarkersLayer.addLayer(circleMarker);
                        bounds.extend([est.latitude, est.longitude]);

                        // Círculo de 200m de raio gerado exclusivamente a partir de cada ESTAÇÃO MATRIZ
                        if (isMatriz) {
                            const radiusCircle = L.circle([est.latitude, est.longitude], {
                                radius: 200, // 200 metros
                                color: '#1f68f1',
                                weight: 2,
                                dashArray: '5, 5',
                                fillColor: '#3588fc',
                                fillOpacity: 0.18,
                                interactive: true
                            });

                            radiusCircle.bindTooltip(`Raio de 200m da Estação Matriz (Área permitida para novas Satélites)`, {
                                sticky: true,
                                direction: 'top'
                            });

                            circles200mLayer.addLayer(radiusCircle);
                        }
                    });

                    // Ajusta o zoom do mapa para abranger todas as estações se houver dados
                    if (existingStations.length > 0) {
                        map.fitBounds(bounds, { padding: [50, 50], maxZoom: 16 });
                    }

                    if (selectTipo.value === 'Estação Satélite') {
                        ativarModoSatelite();
                    } else {
                        ativarModoMatriz();
                    }
                })
                .catch(err => {
                    console.error('Erro ao buscar coordenadas das estações:', err);
                });

            // Atualiza o estado visual baseado no Tipo de Estação
            function ativarModoSatelite() {
                tipoInfoMatriz.classList.add('hidden');
                tipoInfoSatelite.classList.remove('hidden');
                
                const matrizes = existingStations.filter(st => st.tipo_estacao === 'Estação Matriz');

                if (matrizes.length === 0) {
                    showMapFeedback('error', '<strong>Atenção:</strong> Não existem <strong>Estações Matriz</strong> cadastradas. A primeira estação do sistema deve ser obrigatoriamente uma <strong>Estação Matriz</strong>.');
                } else {
                    showMapFeedback('info', '<strong>Modo Satélite Ativo:</strong> Posicione a Estação Satélite dentro de um dos <strong>círculos azuis com raio de 200 metros</strong> ao redor de uma Estação Matriz.');
                }

                if (!map.hasLayer(circles200mLayer)) {
                    map.addLayer(circles200mLayer);
                }

                // Se já havia um marcador posicionado, revalida se ele está a 200m de uma Matriz
                if (newStationMarker) {
                    const latLng = newStationMarker.getLatLng();
                    const validacao = validarDistancia200m(latLng.lat, latLng.lng);
                    if (!validacao.valido) {
                        removerMarcadorNovo();
                        showMapFeedback('error', `A posição selecionada estava fora do raio de 200m de uma Estação Matriz (${validacao.menorDistancia ? Math.round(validacao.menorDistancia) + 'm' : ''}). O ponto foi resetado.`);
                    }
                }
            }

            function ativarModoMatriz() {
                tipoInfoMatriz.classList.remove('hidden');
                tipoInfoSatelite.classList.add('hidden');

                if (map.hasLayer(circles200mLayer)) {
                    map.removeLayer(circles200mLayer);
                }

                showMapFeedback('info', '<strong>Modo Matriz Ativo:</strong> Você pode posicionar uma <strong>Estação Matriz</strong> em qualquer ponto livre do mapa.');
            }

            selectTipo.addEventListener('change', function () {
                if (this.value === 'Estação Satélite') {
                    ativarModoSatelite();
                } else {
                    ativarModoMatriz();
                }
            });

            // Validação de Proximidade (Regra dos 200m em relação a Estações Matriz)
            function validarDistancia200m(lat, lng) {
                const matrizStations = existingStations.filter(st => st.tipo_estacao === 'Estação Matriz');

                if (matrizStations.length === 0) {
                    return { valido: false, menorDistancia: null, semMatriz: true };
                }

                const clickPoint = L.latLng(lat, lng);
                let menorDistancia = Infinity;
                let matrizMaisProxima = null;

                matrizStations.forEach(st => {
                    const stPoint = L.latLng(st.latitude, st.longitude);
                    const dist = clickPoint.distanceTo(stPoint); // Distância esférica em metros pelo Leaflet
                    if (dist < menorDistancia) {
                        menorDistancia = dist;
                        matrizMaisProxima = st;
                    }
                });

                return {
                    valido: menorDistancia <= 200.0,
                    menorDistancia: menorDistancia,
                    matrizMaisProxima: matrizMaisProxima,
                    semMatriz: false
                };
            }

            function definirCoordenadasFormulario(lat, lng, feedbackMsg = null) {
                const latFormat = lat.toFixed(6);
                const lngFormat = lng.toFixed(6);

                inputLat.value = latFormat;
                inputLng.value = lngFormat;

                coordsDisplay.classList.remove('bg-athens-gray-50', 'border-athens-gray-200');
                coordsDisplay.classList.add('bg-emerald-50', 'border-emerald-200');

                coordsBadge.classList.remove('bg-athens-gray-200', 'text-athens-gray-600');
                coordsBadge.classList.add('bg-emerald-200', 'text-emerald-800');
                coordsBadge.textContent = 'Definido';

                coordsText.innerHTML = `<span class="font-bold text-emerald-900">Lat:</span> ${latFormat} | <span class="font-bold text-emerald-900">Lng:</span> ${lngFormat}`;

                if (feedbackMsg) {
                    showMapFeedback('success', feedbackMsg);
                }
            }

            function removerMarcadorNovo() {
                if (newStationMarker) {
                    map.removeLayer(newStationMarker);
                    newStationMarker = null;
                }
                inputLat.value = '';
                inputLng.value = '';

                coordsDisplay.classList.add('bg-athens-gray-50', 'border-athens-gray-200');
                coordsDisplay.classList.remove('bg-emerald-50', 'border-emerald-200');

                coordsBadge.classList.add('bg-athens-gray-200', 'text-athens-gray-600');
                coordsBadge.classList.remove('bg-emerald-200', 'text-emerald-800');
                coordsBadge.textContent = 'Não Definido';

                coordsText.innerHTML = 'Clique no mapa para definir a posição do sensor';
            }

            // Ícone personalizado para o novo ponto selecionado (Verde vibrante com pulso)
            const newPinIcon = L.divIcon({
                className: 'custom-pin-pulse',
                html: '<div style="background-color: #2ecc71; width: 16px; height: 16px; border-radius: 50%; border: 3px solid #ffffff; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
                iconSize: [16, 16],
                iconAnchor: [8, 8]
            });

            function buscarEnderecoReverso(lat, lng, callback) {
                fetch(`/api/geocoding/reverse?lat=${lat}&lng=${lng}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data) {
                            // Se a geolocalização encontrou o bairro, sincroniza automaticamente no formulário
                            if (data.bairro && selectBairro) {
                                let optionFound = false;
                                for (let i = 0; i < selectBairro.options.length; i++) {
                                    if (selectBairro.options[i].text.toLowerCase().trim() === data.bairro.toLowerCase().trim()) {
                                        selectBairro.selectedIndex = i;
                                        optionFound = true;
                                        break;
                                    }
                                }
                                // Se o bairro ainda não estava listado, adiciona como opção detectada
                                if (!optionFound && data.bairro.trim() !== '') {
                                    const currentVal = selectBairro.value || selectBairro.options[1]?.value || '';
                                    if (currentVal) {
                                        const newOpt = new Option(data.bairro + ' (Detectado pelo mapa)', currentVal, true, true);
                                        selectBairro.add(newOpt);
                                        selectBairro.disabled = false;
                                    }
                                }
                            }

                            if (data.endereco) {
                                callback(data.endereco);
                            }
                        }
                    })
                    .catch(err => console.warn('Falha no reverse geocoding:', err));
            }

            // Evento de Clique no Mapa
            map.on('click', function (e) {
                const clickedLat = e.latlng.lat;
                const clickedLng = e.latlng.lng;
                const tipoAtual = selectTipo.value;

                if (tipoAtual === 'Estação Matriz') {
                    // Clique Livre para Matriz
                    if (!newStationMarker) {
                        newStationMarker = L.marker([clickedLat, clickedLng], { icon: newPinIcon }).addTo(map);
                    } else {
                        newStationMarker.setLatLng([clickedLat, clickedLng]);
                    }

                    newStationMarker.bindPopup(`<b>Nova Estação Matriz</b><br><span id="popup-endereco-novo" style="color: #1f68f1; font-size: 11px;">📍 Identificando endereço...</span><br><small style="color: #7b96b6;">Lat: ${clickedLat.toFixed(5)}, Lng: ${clickedLng.toFixed(5)}</small>`).openPopup();
                    definirCoordenadasFormulario(clickedLat, clickedLng, 'Localização da <strong>Estação Matriz</strong> fixada com sucesso!');

                    buscarEnderecoReverso(clickedLat, clickedLng, function (endereco) {
                        const el = document.getElementById('popup-endereco-novo');
                        if (el) el.innerHTML = `📍 ${endereco}`;
                        showMapFeedback('success', `Localização fixada: <strong>${endereco}</strong>`);
                    });
                } else {
                    // Validação Estrita dos 200m exclusivamente em relação a Estações Matriz
                    const resultado = validarDistancia200m(clickedLat, clickedLng);

                    if (resultado.semMatriz) {
                        showMapFeedback('error', '<strong>Bloqueado:</strong> Não há nenhuma <strong>Estação Matriz</strong> cadastrada para conectar esta Satélite. Cadastre primeiro uma Estação Matriz.');
                        return;
                    }

                    if (resultado.valido) {
                        // Válido: dentro de 200m de uma Matriz
                        if (!newStationMarker) {
                            newStationMarker = L.marker([clickedLat, clickedLng], { icon: newPinIcon }).addTo(map);
                        } else {
                            newStationMarker.setLatLng([clickedLat, clickedLng]);
                        }

                        const distFormat = Math.round(resultado.menorDistancia);
                        newStationMarker.bindPopup(`<b>Nova Estação Satélite</b><br><span id="popup-endereco-novo" style="color: #1f68f1; font-size: 11px;">📍 Identificando endereço...</span><br><small style="color: #7b96b6;">A ${distFormat}m da Estação Matriz mais próxima</small>`).openPopup();
                        definirCoordenadasFormulario(clickedLat, clickedLng, `Localização permitida! Distância de <strong>${distFormat} metros</strong> da Estação Matriz mais próxima (máximo: 200m).`);

                        buscarEnderecoReverso(clickedLat, clickedLng, function (endereco) {
                            const el = document.getElementById('popup-endereco-novo');
                            if (el) el.innerHTML = `📍 ${endereco}`;
                            showMapFeedback('success', `Localização permitida (a ${distFormat}m da Matriz): <strong>${endereco}</strong>`);
                        });
                    } else {
                        // Inválido: fora do raio de 200m de qualquer Matriz
                        const distFormat = Math.round(resultado.menorDistancia);
                        showMapFeedback('error', `<strong>Posição Inválida:</strong> O ponto selecionado está a <strong>${distFormat} metros</strong> da Estação Matriz mais próxima. Estações Satélites devem estar a no máximo <strong>200 metros de uma Estação Matriz</strong>.`);
                        
                        // Desenha um marcador temporário vermelho de alerta
                        const tempAlertMarker = L.circleMarker([clickedLat, clickedLng], {
                            radius: 10,
                            fillColor: '#e74c3c',
                            color: '#ffffff',
                            weight: 2,
                            fillOpacity: 0.8
                        }).addTo(map);

                        setTimeout(() => map.removeLayer(tempAlertMarker), 2500);
                    }
                }
            });

            // Se o formulário recarregou com coordenadas antigas (ex: pós erro de validação)
            if (inputLat.value && inputLng.value) {
                const oldLat = parseFloat(inputLat.value);
                const oldLng = parseFloat(inputLng.value);
                if (!isNaN(oldLat) && !isNaN(oldLng)) {
                    newStationMarker = L.marker([oldLat, oldLng], { icon: newPinIcon }).addTo(map);
                    map.setView([oldLat, oldLng], 15);
                }
            }
        });
    </script>
    @endpush
</x-layouts.app>

