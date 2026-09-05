<x-layouts.app title="Planejador de Malha de Sensores - OpenAir Metrics">
    @push('styles')
        <style>
            .custom-pin-pulse {
                animation: pulse-animation 2s infinite;
            }
            @keyframes pulse-animation {
                0% { box-shadow: 0 0 0 0 rgba(31, 104, 241, 0.7); }
                70% { box-shadow: 0 0 0 12px rgba(31, 104, 241, 0); }
                100% { box-shadow: 0 0 0 0 rgba(31, 104, 241, 0); }
            }
            .custom-pin-matriz {
                cursor: grab;
                transition: transform 0.15s ease;
            }
            .custom-pin-matriz:hover {
                transform: scale(1.12);
            }
            .leaflet-dragging .custom-pin-matriz {
                cursor: grabbing !important;
            }
            .custom-pin-satelite {
                cursor: grab;
                transition: transform 0.15s ease;
            }
            .custom-pin-satelite:hover {
                transform: scale(1.12);
            }
            .leaflet-dragging .custom-pin-satelite {
                cursor: grabbing !important;
            }
        </style>
    @endpush

    <div class="flex h-full w-full bg-athens-gray-50">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="estacoes" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- Breadcrumb e Voltar -->
                <div class="flex items-center gap-2 text-sm text-athens-gray-500">
                    <a href="{{ route('estacoes.index') }}" class="hover:text-blue-dianne-600 transition">Minhas Estações</a>
                    <x-heroicon-o-chevron-right class="w-4 h-4" />
                    <span class="text-blue-dianne-950 font-medium">Planejador Automático de Malha</span>
                </div>

                <!-- Cabeçalho -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-blue-dianne-950 tracking-tight">Planejador de Malha de Sensores</h1>
                        <p class="text-sm text-athens-gray-600 mt-1">Posicione a Estação Matriz e o sistema calculará a posição ideal das Satélites ao longo das ruas (limite de 200m).</p>
                    </div>

                    <a href="{{ route('estacoes.create') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-dianne-600 bg-white border border-athens-gray-300 hover:bg-athens-gray-50 px-3.5 py-2 rounded-lg transition">
                        <x-heroicon-o-arrow-path class="w-4 h-4" />
                        Cadastro Manual Individual
                    </a>
                </div>

                <!-- Grid Principal: Configuração + Mapa + Resumo -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

                    <!-- Painel Esquerdo: Parâmetros e Resumo (5 cols) -->
                    <div class="lg:col-span-5 space-y-5">
                        <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 space-y-4">
                            <h2 class="text-base font-bold text-blue-dianne-950 flex items-center gap-2 border-b border-athens-gray-100 pb-3">
                                <x-heroicon-o-adjustments-horizontal class="w-5 h-5 text-blue-dianne-600" />
                                1. Configurar Parâmetros da Rede
                            </h2>

                            <!-- Estado e Cidade -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-athens-gray-600 uppercase mb-1">Estado</label>
                                    <select id="select-estado" class="w-full text-sm border border-athens-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-dianne-500">
                                        <option value="">Selecione o Estado</option>
                                        @foreach ($estados as $est)
                                            <option value="{{ $est->id }}" data-uf="{{ $est->uf }}">{{ $est->nome }} ({{ $est->uf }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-athens-gray-600 uppercase mb-1">Cidade</label>
                                    <select id="select-cidade" disabled class="w-full text-sm border border-athens-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-dianne-500 bg-athens-gray-50">
                                        <option value="">Selecione a Cidade</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Quantidade de Satélites Dinâmica -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <label for="qtd-satelites" class="block text-xs font-bold text-athens-gray-600 uppercase">
                                        Qtd. de Satélites:
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" id="input-qtd-number" min="1" max="15" value="3" class="w-16 text-center text-sm font-bold text-blue-dianne-700 border border-athens-gray-300 rounded-lg py-1 px-1.5 focus:ring-2 focus:ring-blue-dianne-500 bg-white">
                                        <span class="text-xs text-athens-gray-400 font-medium">(Total: <strong id="label-total" class="text-blue-dianne-950 font-bold">4 estações</strong>)</span>
                                    </div>
                                </div>
                                <input type="range" id="qtd-satelites" min="1" max="15" value="3" class="w-full accent-blue-dianne-600 cursor-pointer">
                            </div>

                            <!-- Card de Instrução de Ação -->
                            <div class="bg-blue-dianne-50 border border-blue-dianne-200 p-3.5 rounded-lg flex items-start gap-2.5">
                                <x-heroicon-o-cursor-arrow-rays class="w-5 h-5 text-blue-dianne-600 shrink-0 mt-0.5" />
                                <div class="text-xs text-blue-dianne-900 leading-relaxed">
                                    <p class="font-bold">Passo 2: Marcar a Matriz no Mapa</p>
                                    <p class="text-blue-dianne-800 mt-0.5">Clique no mapa para posicionar a <strong>Estação Matriz</strong>. A posição dela ficará fixa e as satélites serão calculadas ao longo das ruas. Para reposicionar a Matriz, <strong>arraste e solte</strong> o pino #1 ou clique em <strong>Remover Matriz</strong> para reposicionar do zero.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Card de Lista das Estações Calculadas -->
                        <div id="card-resultado-malha" class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 space-y-3 hidden">
                            <div class="flex items-center justify-between border-b border-athens-gray-100 pb-3">
                                <h3 class="text-sm font-bold text-blue-dianne-950 flex items-center gap-1.5">
                                    <x-heroicon-o-list-bullet class="w-4 h-4 text-emerald-600" />
                                    Malha Calculada (<span id="count-estacoes">0</span> estações)
                                </h3>
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        Todas a ≤ 200m
                                    </span>
                                    <button type="button" id="btn-remover-matriz-card" class="text-xs font-semibold text-rose-600 hover:text-rose-800 hover:underline cursor-pointer">
                                        Remover Matriz
                                    </button>
                                </div>
                            </div>

                            <!-- Lista de Estações em Scroll -->
                            <div id="lista-estacoes-calculadas" class="space-y-2.5 max-h-[320px] overflow-y-auto pr-1">
                                <!-- Preenchido dinamicamente via JS -->
                            </div>

                            <!-- Formulário de Confirmação e Submissão -->
                            <form id="form-salvar-malha" action="{{ route('estacoes.salvar-malha') }}" method="POST" class="pt-3 border-t border-athens-gray-100">
                                @csrf
                                <input type="hidden" name="cidade_id" id="form-cidade-id">
                                <input type="hidden" name="matriz" id="form-matriz-json">
                                <input type="hidden" name="satelites" id="form-satelites-json">

                                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 px-4 rounded-lg transition shadow-md hover:shadow-lg active:scale-98 text-sm cursor-pointer">
                                    <x-heroicon-o-check-circle class="w-5 h-5" />
                                    Confirmar e Gerar Ordem de Instalação
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Painel Direito: Mapa Interativo com Leaflet (7 cols) -->
                    <div class="lg:col-span-7">
                        <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 overflow-hidden flex flex-col h-[640px]">
                            <!-- Barra Superior do Mapa -->
                            <div class="px-4 py-3 bg-athens-gray-50 border-b border-athens-gray-200 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-blue-dianne-950">Legenda:</span>
                                    <span class="inline-flex items-center gap-1 text-dodger-blue-700 font-semibold"><span class="w-2.5 h-2.5 rounded-full bg-dodger-blue-600"></span> Matriz (Fixa / Arrastável)</span>
                                    <span class="inline-flex items-center gap-1 text-purple-700 font-semibold ml-2"><span class="w-2.5 h-2.5 rounded-full bg-purple-600"></span> Satélites (Arrastáveis)</span>
                                    <span class="inline-flex items-center gap-1 text-slate-600 font-semibold ml-2"><span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span> Instaladas</span>
                                    <span class="inline-flex items-center gap-1 text-slate-500 font-medium ml-2"><span class="w-3 h-0.5 bg-slate-400 border border-slate-400 border-dashed"></span> Raio Instaladas (Cinza)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span id="map-status" class="text-athens-gray-500 italic">Aguardando seleção da Matriz</span>
                                    <button type="button" id="btn-remover-matriz-topbar" class="hidden inline-flex items-center gap-1 text-xs font-semibold text-rose-600 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 border border-rose-200 px-2 py-0.5 rounded transition cursor-pointer">
                                        Remover Matriz
                                    </button>
                                </div>
                            </div>

                            <!-- Container do Mapa -->
                            <div id="map-planejador" class="flex-1 w-full bg-athens-gray-100 z-0"></div>
                        </div>
                    </div>

                </div>

            </div>
        </main>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                let map;
                let currentMalha = null;
                let plannedLayers = L.featureGroup();
                let existingStationsLayers = L.featureGroup();

            const selectEstado = document.getElementById('select-estado');
            const selectCidade = document.getElementById('select-cidade');
            const sliderQtd = document.getElementById('qtd-satelites');
            const inputNumberQtd = document.getElementById('input-qtd-number');
            const labelTotal = document.getElementById('label-total');
            const cardResultado = document.getElementById('card-resultado-malha');
            const listaEstacoesEl = document.getElementById('lista-estacoes-calculadas');
            const countEstacoesEl = document.getElementById('count-estacoes');
            const mapStatus = document.getElementById('map-status');
            const btnTopbar = document.getElementById('btn-remover-matriz-topbar');
            const btnCard = document.getElementById('btn-remover-matriz-card');
            let atualizarPopupMatrizGlobal = null;

            // Formulário Hidden Inputs
            const formCidadeId = document.getElementById('form-cidade-id');
            const formMatrizJson = document.getElementById('form-matriz-json');
            const formSatelitesJson = document.getElementById('form-satelites-json');

            function removerMatriz() {
                currentMalha = null;
                plannedLayers.clearLayers();
                cardResultado.classList.add('hidden');
                formMatrizJson.value = '';
                formSatelitesJson.value = '';
                atualizarPopupMatrizGlobal = null;
                if (btnTopbar) {
                    btnTopbar.classList.add('hidden');
                }
                mapStatus.innerHTML = '<span class="text-athens-gray-500 italic">Aguardando seleção da Matriz (clique no mapa para posicionar)</span>';
            }

            if (btnTopbar) {
                btnTopbar.addEventListener('click', removerMatriz);
            }
            if (btnCard) {
                btnCard.addEventListener('click', removerMatriz);
            }

            // Função para busca de endereço e bairro reverso (mesmo modelo da tela create.blade.php)
            function buscarBairroReverso(lat, lng, callback) {
                fetch(`/api/geocoding/reverse?lat=${lat}&lng=${lng}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data && callback) {
                            callback(data);
                        }
                    })
                    .catch(err => console.warn('Falha no reverse geocoding da Matriz:', err));
            }

            function setQuantidadeDinamica(novaQtd) {
                novaQtd = Math.max(1, Math.min(15, parseInt(novaQtd) || 1));
                sliderQtd.value = novaQtd;
                inputNumberQtd.value = novaQtd;
                labelTotal.textContent = (novaQtd + 1) + ' estações';

                if (currentMalha && currentMalha.matriz) {
                    recalcularMalha(currentMalha.matriz.latitude, currentMalha.matriz.longitude);
                }
            }

            sliderQtd.addEventListener('input', function() {
                setQuantidadeDinamica(this.value);
            });

            inputNumberQtd.addEventListener('change', function() {
                setQuantidadeDinamica(this.value);
            });

            document.querySelectorAll('.btn-preset-qtd').forEach(btn => {
                btn.addEventListener('click', function() {
                    const presetVal = parseInt(this.getAttribute('data-qtd'));
                    setQuantidadeDinamica(presetVal);
                });
            });

            // Inicializa o Mapa Leaflet
            map = L.map('map-planejador', {
                zoomControl: true,
                attributionControl: false
            }).setView([-23.55052, -46.633308], 16);
            L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                maxZoom: 19,
                minZoom: 3,
                attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap contributors</a>, Tiles style by Humanitarian OpenStreetMap Team hosted by OpenStreetMap France'
            }).addTo(map);

            map.addLayer(existingStationsLayers);
            map.addLayer(plannedLayers);

            // Carrega estações já existentes para contexto
            fetch('/api/estacoes/coordenadas')
                .then(r => r.json())
                .then(estacoes => {
                    (estacoes || []).forEach(est => {
                        if (est.latitude && est.longitude) {
                            const isM = est.tipo_estacao === 'Estação Matriz';
                            const latLng = [parseFloat(est.latitude), parseFloat(est.longitude)];

                            // Círculo de cobertura de 200m em cinza das estações já instaladas
                            const circleCoverage = L.circle(latLng, {
                                radius: 200,
                                color: '#94a3b8',
                                weight: 1.5,
                                dashArray: '5, 5',
                                fillColor: '#cbd5e1',
                                fillOpacity: 0.16
                            });
                            existingStationsLayers.addLayer(circleCoverage);

                            // Ponto central da estação já cadastrada
                            const marker = L.circleMarker(latLng, {
                                radius: isM ? 7 : 5,
                                fillColor: isM ? '#475569' : '#64748b',
                                color: '#ffffff',
                                weight: 1.5,
                                fillOpacity: 0.85
                            });
                            marker.bindPopup(`<strong>${est.tipo_estacao} (Já Cadastrada)</strong><br>${est.endereco || ''}<br><span style="color:#64748b; font-size:11px;">Raio de alcance: 200m (Cinza)</span>`);
                            existingStationsLayers.addLayer(marker);
                        }
                    });
                });

            // Carregamento em Cascata de Cidades
            selectEstado.addEventListener('change', function() {
                if (currentMalha) {
                    removerMatriz();
                }

                const uf = this.options[this.selectedIndex].getAttribute('data-uf');
                selectCidade.innerHTML = '<option value="">Carregando cidades...</option>';
                selectCidade.disabled = true;

                if (!uf) {
                    selectCidade.innerHTML = '<option value="">Selecione o Estado primeiro</option>';
                    return;
                }

                fetch(`/api/cidades/${uf}`)
                    .then(r => r.json())
                    .then(cidades => {
                        selectCidade.innerHTML = '<option value="">Selecione a Cidade</option>';
                        cidades.forEach(c => {
                            const opt = new Option(c.nome, c.id);
                            selectCidade.add(opt);
                        });
                        selectCidade.disabled = false;
                        selectCidade.classList.remove('bg-athens-gray-50');
                    });
            });

            // Foco na Cidade
            selectCidade.addEventListener('change', function() {
                if (currentMalha) {
                    removerMatriz();
                }

                const cidadeNome = this.options[this.selectedIndex]?.text;
                const uf = selectEstado.options[selectEstado.selectedIndex]?.getAttribute('data-uf');
                formCidadeId.value = this.value;

                if (cidadeNome && uf) {
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(cidadeNome + ', ' + uf + ', Brasil')}`)
                        .then(r => r.json())
                        .then(data => {
                            if (data && data[0]) {
                                map.flyTo([parseFloat(data[0].lat), parseFloat(data[0].lon)], 14, { duration: 1.5 });
                            }
                        });
                }
            });

            // Clique no Mapa para Definir a Matriz e Calcular a Malha
            map.on('click', function(e) {
                if (!selectCidade.value) {
                    alert('Por favor, selecione primeiro o Estado e a Cidade.');
                    return;
                }

                if (currentMalha && currentMalha.matriz) {
                    mapStatus.innerHTML = '<span class="text-amber-700 font-semibold">📍 Estação Matriz fixada. Arraste o pino #1 ou clique em "Remover Matriz" para alterar a posição.</span>';
                    return;
                }

                const lat = e.latlng.lat;
                const lng = e.latlng.lng;
                recalcularMalha(lat, lng);
            });

            function recalcularMalha(lat, lng) {
                mapStatus.textContent = 'Calculando posições viárias da malha...';

                const qtd = parseInt(sliderQtd.value);
                const cidadeId = selectCidade.value;

                fetch('/api/estacoes/calcular-malha', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        latitude: lat,
                        longitude: lng,
                        quantidade_satelites: qtd,
                        cidade_id: cidadeId
                    })
                })
                .then(r => {
                    if (!r.ok) {
                        throw new Error('Erro na resposta do servidor: ' + r.status);
                    }
                    return r.json();
                })
                .then(data => {
                    if (!data || !data.matriz) {
                        throw new Error('Formato de resposta inválido.');
                    }
                    currentMalha = data;
                    renderizarMalhaNoMapa(data);
                    renderizarResumoMalha(data);
                    mapStatus.textContent = 'Malha viária calculada com sucesso!';

                    // Obtenção automática do bairro da Matriz via geocoding reverso (mesmo modelo da tela create.blade.php)
                    const mLat = currentMalha.matriz.latitude;
                    const mLng = currentMalha.matriz.longitude;
                    buscarBairroReverso(mLat, mLng, function(geo) {
                        if (geo && currentMalha && currentMalha.matriz) {
                            if (geo.bairro) {
                                currentMalha.matriz.bairro_nome = geo.bairro;
                            }
                            if (geo.endereco_completo) {
                                currentMalha.matriz.endereco_completo = geo.endereco_completo;
                            }
                            if (geo.logradouro) {
                                currentMalha.matriz.logradouro = geo.logradouro;
                            }
                            if (geo.cep) {
                                currentMalha.matriz.cep = geo.cep;
                            }

                            // Propaga para satélites sem bairro definido
                            (currentMalha.satelites || []).forEach(s => {
                                if (!s.bairro_nome || s.bairro_nome === 'Centro') {
                                    s.bairro_nome = geo.bairro || s.bairro_nome;
                                }
                            });

                            renderizarResumoMalha(currentMalha);
                            if (typeof atualizarPopupMatrizGlobal === 'function') {
                                atualizarPopupMatrizGlobal();
                            }

                            if (geo.bairro) {
                                mapStatus.innerHTML = `<span class="text-emerald-700 font-semibold">✓ Malha viária calculada no bairro ${geo.bairro}!</span>`;
                            }
                        }
                    });
                })
                .catch(err => {
                    console.error('Erro ao calcular malha:', err);
                    mapStatus.textContent = 'Falha no cálculo da malha. Tente clicar em outro ponto.';
                });
            }

            function renderizarMalhaNoMapa(malha) {
                if (!malha || !malha.matriz) {
                    return;
                }

                plannedLayers.clearLayers();
                if (btnTopbar) {
                    btnTopbar.classList.remove('hidden');
                }

                const m = malha.matriz;
                const mLat = parseFloat(m.latitude);
                const mLng = parseFloat(m.longitude);

                if (isNaN(mLat) || isNaN(mLng)) {
                    console.error('Coordenadas inválidas para Matriz:', m);
                    return;
                }

                const bounds = L.latLngBounds([]);
                let matrizLatLng = L.latLng(mLat, mLng);
                bounds.extend(matrizLatLng);

                // Dicionários de camadas indexadas para manipulação dinâmica
                const circlesMap = {};
                const markersMap = {};
                const linesMap = {};
                let dragPreviewLine = null;

                // 1. Círculo de alcance da Matriz (200m)
                const circleMatriz = L.circle(matrizLatLng, {
                    radius: 200,
                    color: '#1f68f1',
                    weight: 2,
                    dashArray: '5, 5',
                    fillColor: '#3588fc',
                    fillOpacity: 0.16
                });
                plannedLayers.addLayer(circleMatriz);
                circlesMap[0] = circleMatriz;

                // Marcador da Matriz (Fixa / Arrastável - Raiz da rede)
                const markerMatriz = L.marker(matrizLatLng, {
                    draggable: true,
                    icon: L.divIcon({
                        className: 'custom-pin-matriz',
                        html: '<div style="background:#1f68f1; color:#fff; font-weight:bold; font-size:11px; width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2.5px solid #fff; box-shadow:0 3px 8px rgba(0,0,0,0.3);" title="Arraste para reposicionar a Matriz">#1</div>',
                        iconSize: [26, 26],
                        iconAnchor: [13, 13]
                    })
                });

                function atualizarPopupMatriz() {
                    const enderecoExibicao = m.endereco_completo || (m.bairro_nome ? `${m.bairro_nome} - ${m.cidade_nome || ''}` : 'Localização fixada');
                    markerMatriz.bindPopup(`
                        <div style="font-family:sans-serif; font-size:12px; line-height:1.4; min-width:180px;">
                            <strong style="color:#0369a1;">#1 - Estação Matriz (Fixa)</strong><br>
                            <span>📍 ${enderecoExibicao}</span><br>
                            <span style="color:#1f68f1; font-size:10px; font-weight:600;">✋ Arraste para reposicionar</span>
                            <button id="btn-remover-matriz-popup" type="button" style="margin-top: 8px; width: 100%; font-size: 11px; font-weight: 600; color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; padding: 4px 8px; border-radius: 6px; cursor: pointer;">
                                Remover Matriz
                            </button>
                        </div>
                    `);
                }
                atualizarPopupMatrizGlobal = atualizarPopupMatriz;
                atualizarPopupMatriz();

                markerMatriz.on('popupopen', function() {
                    const btn = document.getElementById('btn-remover-matriz-popup');
                    if (btn) {
                        btn.onclick = function() {
                            removerMatriz();
                        };
                    }
                });

                markerMatriz.on('dragstart', function() {
                    mapStatus.textContent = 'Movendo Estação Matriz...';
                });

                markerMatriz.on('drag', function(e) {
                    matrizLatLng = e.target.getLatLng();
                    circleMatriz.setLatLng(matrizLatLng);
                    atualizarLinhasConexao();
                });

                markerMatriz.on('dragend', function(e) {
                    const finalPos = e.target.getLatLng();
                    mapStatus.textContent = 'Recalculando malha viária para a nova posição da Matriz...';
                    recalcularMalha(finalPos.lat, finalPos.lng);
                });

                plannedLayers.addLayer(markerMatriz);
                markersMap[0] = markerMatriz;

                // Função auxiliar: detecta se candidateIdx é descendente de targetIdx na árvore (previne ciclos)
                function isDescendant(candidateIdx, targetIdx) {
                    if (candidateIdx === targetIdx) return true;
                    let curr = candidateIdx;
                    const visited = new Set();
                    while (curr !== 0 && curr !== null && curr !== undefined && !visited.has(curr)) {
                        visited.add(curr);
                        if (curr === targetIdx) {
                            return true;
                        }
                        const satObj = (malha.satelites || [])[curr - 1];
                        if (!satObj) break;
                        curr = satObj.origem_indice;
                    }
                    return false;
                }

                // Função auxiliar: localiza a estação mais próxima viável para ser pai (sem criar ciclos)
                function encontrarCandidatoPaiMaisProximo(pos, selfStationIdx) {
                    let best = {
                        stationIdx: 0,
                        ordem: 1,
                        latLng: matrizLatLng,
                        distancia: map.distance(pos, matrizLatLng)
                    };

                    (malha.satelites || []).forEach((s, sIdx) => {
                        const sStationIdx = sIdx + 1;
                        if (sStationIdx === selfStationIdx) return;
                        if (isDescendant(sStationIdx, selfStationIdx)) return;

                        const sLatLng = L.latLng(parseFloat(s.latitude), parseFloat(s.longitude));
                        const dist = map.distance(pos, sLatLng);
                        if (dist < best.distancia) {
                            best = {
                                stationIdx: sStationIdx,
                                ordem: s.ordem_instalacao,
                                latLng: sLatLng,
                                distancia: dist
                            };
                        }
                    });

                    return best;
                }

                // Função para atualizar as polylines no mapa com base no estado atual da malha
                function atualizarLinhasConexao() {
                    (malha.satelites || []).forEach((sat, sIdx) => {
                        const stationIdx = sIdx + 1;
                        const line = linesMap[stationIdx];
                        if (!line) return;

                        const satLatLng = L.latLng(parseFloat(sat.latitude), parseFloat(sat.longitude));
                        const pIdx = sat.origem_indice ?? 0;
                        let parentLatLng = matrizLatLng;

                        if (pIdx > 0 && malha.satelites[pIdx - 1]) {
                            const pSat = malha.satelites[pIdx - 1];
                            parentLatLng = L.latLng(parseFloat(pSat.latitude), parseFloat(pSat.longitude));
                        }

                        line.setLatLngs([parentLatLng, satLatLng]);
                        line.setStyle({
                            color: '#6366f1',
                            weight: 2,
                            dashArray: '6, 6',
                            opacity: 0.8
                        });
                    });
                }

                // 2. Renderiza as Satélites e configura o mecanismo de Drag-and-Drop
                (malha.satelites || []).forEach((sat, idx) => {
                    const sLat = parseFloat(sat.latitude);
                    const sLng = parseFloat(sat.longitude);

                    if (isNaN(sLat) || isNaN(sLng)) {
                        return;
                    }

                    const satLatLng = L.latLng(sLat, sLng);
                    bounds.extend(satLatLng);
                    const thisStationIdx = idx + 1;

                    // Círculo de 200m da Satélite
                    const circleSat = L.circle(satLatLng, {
                        radius: 200,
                        color: '#8b5cf6',
                        weight: 1.5,
                        dashArray: '4, 4',
                        fillColor: '#a855f7',
                        fillOpacity: 0.12
                    });
                    plannedLayers.addLayer(circleSat);
                    circlesMap[thisStationIdx] = circleSat;

                    // Linha conectando à estação de origem (árvore de comunicação)
                    const pIdx = sat.origem_indice ?? 0;
                    let parentLatLng = matrizLatLng;
                    if (pIdx > 0 && malha.satelites[pIdx - 1]) {
                        parentLatLng = L.latLng(parseFloat(malha.satelites[pIdx - 1].latitude), parseFloat(malha.satelites[pIdx - 1].longitude));
                    }

                    const polyline = L.polyline([parentLatLng, satLatLng], {
                        color: '#6366f1',
                        weight: 2,
                        dashArray: '6, 6',
                        opacity: 0.8
                    });
                    plannedLayers.addLayer(polyline);
                    linesMap[thisStationIdx] = polyline;

                    // Marcador da Satélite (Arrastável)
                    const markerSat = L.marker(satLatLng, {
                        draggable: true,
                        icon: L.divIcon({
                            className: 'custom-pin-satelite',
                            html: `<div style="background:#8b5cf6; color:#fff; font-weight:bold; font-size:11px; width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #fff; box-shadow:0 3px 8px rgba(0,0,0,0.3);" title="Arraste para reposicionar">#${sat.ordem_instalacao}</div>`,
                            iconSize: [24, 24],
                            iconAnchor: [12, 12]
                        })
                    });

                    function atualizarPopup(distancia) {
                        const distM = Math.round(distancia !== undefined ? distancia : sat.distancia_origem_metros);
                        markerSat.bindPopup(`
                            <div style="font-family:sans-serif; font-size:12px; line-height:1.4;">
                                <strong style="color:#7e22ce;">#${sat.ordem_instalacao} - Estação Satélite</strong><br>
                                <span>📍 ${sat.endereco_completo || 'Localização calculada'}</span><br>
                                <small style="color:#6366f1;">Distância da conexão: ${distM}m</small><br>
                                <span style="color:#8b5cf6; font-size:10px; font-weight:600;">✋ Arraste para reposicionar</span>
                            </div>
                        `);
                    }
                    atualizarPopup();

                    // Estado temporário durante o arraste
                    let startLatLng = null;
                    let startOrigem = null;

                    markerSat.on('dragstart', function(e) {
                        startLatLng = e.target.getLatLng();
                        startOrigem = sat.origem_indice ?? 0;

                        // Oculta a linha atual da estação em trânsito
                        if (linesMap[thisStationIdx]) {
                            linesMap[thisStationIdx].setStyle({ opacity: 0 });
                        }

                        // "as estações conectadas devem se desconectar momentaneamente dela"
                        (malha.satelites || []).forEach((childSat, cIdx) => {
                            if (childSat.origem_indice === thisStationIdx) {
                                const childStationIdx = cIdx + 1;
                                if (linesMap[childStationIdx]) {
                                    linesMap[childStationIdx].setStyle({
                                        color: '#94a3b8',
                                        weight: 1.5,
                                        dashArray: '3, 3',
                                        opacity: 0.25
                                    });
                                }
                            }
                        });

                        if (!dragPreviewLine) {
                            dragPreviewLine = L.polyline([], {
                                weight: 2.5,
                                dashArray: '4, 4'
                            }).addTo(plannedLayers);
                        }
                    });

                    markerSat.on('drag', function(e) {
                        const currentPos = e.target.getLatLng();

                        // O círculo de alcance segue o marcador
                        if (circlesMap[thisStationIdx]) {
                            circlesMap[thisStationIdx].setLatLng(currentPos);
                        }

                        // Busca o pai mais próximo sem gerar ciclos
                        const bestParent = encontrarCandidatoPaiMaisProximo(currentPos, thisStationIdx);
                        const dist = bestParent.distancia;

                        // Linha elástica dinâmica conectando à estação mais próxima
                        dragPreviewLine.setLatLngs([bestParent.latLng, currentPos]);

                        if (dist <= 200.0) {
                            dragPreviewLine.setStyle({
                                color: '#10b981',
                                weight: 3,
                                dashArray: '4, 4',
                                opacity: 0.95
                            });
                            mapStatus.innerHTML = `<span class="text-emerald-700 font-semibold">● Ligando à Estação #${bestParent.ordem} (${Math.round(dist)}m - Alcance Válido)</span>`;
                        } else {
                            dragPreviewLine.setStyle({
                                color: '#ef4444',
                                weight: 2.5,
                                dashArray: '6, 6',
                                opacity: 0.9
                            });
                            mapStatus.innerHTML = `<span class="text-rose-600 font-semibold">⚠ Fora do alcance (> 200m da estação mais próxima: #${bestParent.ordem} a ${Math.round(dist)}m)</span>`;
                        }
                    });

                    markerSat.on('dragend', function(e) {
                        const finalPos = e.target.getLatLng();
                        const bestParent = encontrarCandidatoPaiMaisProximo(finalPos, thisStationIdx);
                        const dist = bestParent.distancia;

                        if (dragPreviewLine) {
                            plannedLayers.removeLayer(dragPreviewLine);
                            dragPreviewLine = null;
                        }

                        // Validação preliminar de alcance máximo para evitar chamadas desnecessárias se solto muito longe
                        if (dist > 300.0) {
                            markerSat.setLatLng(startLatLng);
                            if (circlesMap[thisStationIdx]) {
                                circlesMap[thisStationIdx].setLatLng(startLatLng);
                            }
                            atualizarLinhasConexao();
                            mapStatus.innerHTML = `<span class="text-rose-600 font-bold">Posição inválida! A estação deve permanecer a no máximo 200m de outra estação (${Math.round(dist)}m detectado).</span>`;
                            return;
                        }

                        // Feedback visual de processamento do Snap to Road
                        mapStatus.innerHTML = `<span class="text-blue-dianne-600 font-semibold">Encaixando estação na via pública mais próxima (Snap to Road)...</span>`;

                        // Prepara a lista de outras estações existentes para detecção de colisão (Anti-Overlap)
                        const estacoesExistentes = [
                            { latitude: parseFloat(malha.matriz.latitude), longitude: parseFloat(malha.matriz.longitude) }
                        ];
                        (malha.satelites || []).forEach((s, sIdx) => {
                            if (sIdx + 1 !== thisStationIdx) {
                                estacoesExistentes.push({
                                    latitude: parseFloat(s.latitude),
                                    longitude: parseFloat(s.longitude)
                                });
                            }
                        });

                        // Executa o Snap to Road nativo no MariaDB via API
                        fetch('/api/estacoes/snap-to-road', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                latitude: finalPos.lat,
                                longitude: finalPos.lng,
                                origem_latitude: bestParent.latLng.lat,
                                origem_longitude: bestParent.latLng.lng,
                                max_distancia: 200.0,
                                estacoes_existentes: estacoesExistentes
                            })
                        })
                        .then(r => {
                            if (!r.ok) {
                                throw new Error('SnapToRoad falhou ou fora do alcance de 200m.');
                            }
                            return r.json();
                        })
                        .then(data => {
                            if (!data || !data.snapped) {
                                throw new Error('Nenhuma via viável encontrada.');
                            }

                            const snappedLatLng = L.latLng(data.latitude, data.longitude);

                            // Encaixa o marcador e o círculo exatamente no leito da via pública
                            markerSat.setLatLng(snappedLatLng);
                            if (circlesMap[thisStationIdx]) {
                                circlesMap[thisStationIdx].setLatLng(snappedLatLng);
                            }

                            // Posição válida na via: tratar reconexão e nós intermediários (A <- M <- B)
                            const parentAIdx = startOrigem;
                            const posA = parentAIdx === 0
                                ? matrizLatLng
                                : L.latLng(parseFloat(malha.satelites[parentAIdx - 1].latitude), parseFloat(malha.satelites[parentAIdx - 1].longitude));

                            const distToA = map.distance(snappedLatLng, posA);
                            const childrenB = (malha.satelites || []).filter(s => s.origem_indice === thisStationIdx);

                            const movedToAnotherParent = (bestParent.stationIdx !== parentAIdx);
                            const isOutOfA = (distToA > 200.0);

                            if (!movedToAnotherParent && !isOutOfA) {
                                // M movida para perto respeitando os 200m de A: atualiza a ligação com A
                                sat.origem_indice = parentAIdx;
                                sat.distancia_origem_metros = Math.round(distToA * 10) / 10;

                                childrenB.forEach(bSat => {
                                    const bPos = L.latLng(parseFloat(bSat.latitude), parseFloat(bSat.longitude));
                                    const distBtoM = map.distance(bPos, snappedLatLng);
                                    if (distBtoM <= 200.0) {
                                        bSat.origem_indice = thisStationIdx;
                                        bSat.distancia_origem_metros = Math.round(distBtoM * 10) / 10;
                                    } else {
                                        // Se M se afastou de B, B tenta assumir A
                                        const distBtoA = map.distance(bPos, posA);
                                        if (distBtoA <= 200.0) {
                                            bSat.origem_indice = parentAIdx;
                                            bSat.distancia_origem_metros = Math.round(distBtoA * 10) / 10;
                                        } else {
                                            const bStationIdx = malha.satelites.indexOf(bSat) + 1;
                                            const altParent = encontrarCandidatoPaiMaisProximo(bPos, bStationIdx);
                                            bSat.origem_indice = altParent.stationIdx;
                                            bSat.distancia_origem_metros = Math.round(altParent.distancia * 10) / 10;
                                        }
                                    }
                                });
                            } else {
                                // M removida desse meio: conecta à nova estação mais próxima
                                sat.origem_indice = bestParent.stationIdx;
                                sat.distancia_origem_metros = Math.round(data.distancia_origem * 10) / 10;

                                // "se ela for removida desse meio, a estação B deverá assumir o lugar"
                                childrenB.forEach(bSat => {
                                    const bPos = L.latLng(parseFloat(bSat.latitude), parseFloat(bSat.longitude));
                                    const distBtoA = map.distance(bPos, posA);
                                    if (distBtoA <= 200.0) {
                                        bSat.origem_indice = parentAIdx;
                                        bSat.distancia_origem_metros = Math.round(distBtoA * 10) / 10;
                                    } else {
                                        const bStationIdx = malha.satelites.indexOf(bSat) + 1;
                                        const altParent = encontrarCandidatoPaiMaisProximo(bPos, bStationIdx);
                                        bSat.origem_indice = altParent.stationIdx;
                                        bSat.distancia_origem_metros = Math.round(altParent.distancia * 10) / 10;
                                    }
                                });
                            }

                            // Atualiza coordenadas e dados de endereço da estação no objeto local
                            sat.latitude = data.latitude;
                            sat.longitude = data.longitude;
                            if (data.nome_rua) sat.logradouro = data.nome_rua;
                            if (data.endereco_completo) sat.endereco_completo = data.endereco_completo;
                            if (data.bairro) sat.bairro_nome = data.bairro;

                            atualizarLinhasConexao();
                            atualizarPopup();
                            renderizarResumoMalha(malha);

                            mapStatus.innerHTML = `<span class="text-emerald-700 font-semibold">✓ Estação #${sat.ordem_instalacao} fixada na rua ${data.nome_rua || 'local'} (${Math.round(sat.distancia_origem_metros)}m da Estação #${bestParent.ordem})!</span>`;
                        })
                        .catch(err => {
                            console.warn('SnapToRoad não encontrou via válida a <= 200m:', err);
                            // Reverte para a posição anterior
                            markerSat.setLatLng(startLatLng);
                            if (circlesMap[thisStationIdx]) {
                                circlesMap[thisStationIdx].setLatLng(startLatLng);
                            }
                            atualizarLinhasConexao();
                            mapStatus.innerHTML = `<span class="text-rose-600 font-bold">Snap to Road não encontrou via pública válida a ≤ 200m da Estação #${bestParent.ordem}. Posição revertida.</span>`;
                        });
                    });

                    plannedLayers.addLayer(markerSat);
                    markersMap[thisStationIdx] = markerSat;
                });

                if (bounds.isValid()) {
                    map.fitBounds(bounds, { padding: [40, 40], maxZoom: 17 });
                }
            }

            function renderizarResumoMalha(malha) {
                if (!malha || !malha.matriz) {
                    return;
                }

                cardResultado.classList.remove('hidden');
                listaEstacoesEl.innerHTML = '';

                // O ponto clicado pelo usuário é a Estação Matriz (#1), e os outros são as Satélites (#2..N)
                const todas = [malha.matriz, ...(malha.satelites || [])].filter(Boolean);
                countEstacoesEl.textContent = todas.length;

                todas.forEach(est => {
                    if (!est || !est.tipo_estacao) {
                        return;
                    }

                    const isM = est.tipo_estacao === 'Estação Matriz';
                    const item = document.createElement('div');
                    item.className = 'p-3 rounded-lg border text-xs flex items-start gap-2.5 ' + 
                        (isM ? 'bg-blue-50/70 border-blue-200' : 'bg-athens-gray-50 border-athens-gray-200');

                    const lat = typeof est.latitude === 'number' ? est.latitude : parseFloat(est.latitude);
                    const lng = typeof est.longitude === 'number' ? est.longitude : parseFloat(est.longitude);

                    let conexaoInfo = '';
                    if (!isM) {
                        const pIdx = est.origem_indice ?? 0;
                        const pOrdem = pIdx === 0 ? 1 : ((malha.satelites[pIdx - 1]?.ordem_instalacao) || (pIdx + 1));
                        conexaoInfo = `<span class="text-[10px] text-athens-gray-500 font-mono">Ligada a #${pOrdem} (${Math.round(est.distancia_origem_metros || 0)}m)</span>`;
                    }

                    item.innerHTML = `
                        <span class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center font-bold text-white text-[11px] ${isM ? 'bg-blue-dianne-600' : 'bg-purple-600'}">
                            #${est.ordem_instalacao || 1}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1">
                                <span class="font-bold ${isM ? 'text-blue-dianne-950' : 'text-purple-950'}">${est.tipo_estacao}</span>
                                ${conexaoInfo}
                            </div>
                            <p class="text-athens-gray-700 mt-0.5 truncate">${est.endereco_completo || (est.bairro_nome + ' - ' + est.cidade_nome)}</p>
                            <p class="text-[10px] text-athens-gray-400 font-mono mt-0.5">Lat: ${!isNaN(lat) ? lat.toFixed(5) : '--'}, Lng: ${!isNaN(lng) ? lng.toFixed(5) : '--'}</p>
                        </div>
                    `;
                    listaEstacoesEl.appendChild(item);
                });

                // Prepara os dados para submissão
                formMatrizJson.value = JSON.stringify(malha.matriz);
                formSatelitesJson.value = JSON.stringify(malha.satelites || []);
            }
        });
    </script>
@endpush
</x-layouts.app>

