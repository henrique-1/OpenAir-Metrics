<x-layouts.app title="OpenAir Metrics">
    @push('styles')
        <style>
            html, body { overflow: hidden; }
            
            #map-wrapper {
                position: relative;
                width: 100vw;
                height: 100vh;
            }
            #air-quality-map {
                height: 100%;
                width: 100%;
                z-index: 10;
            }
            .layer-option input[type="radio"]:checked + div {
                border-color: var(--color-blue-dianne-500);
                background-color: var(--color-blue-dianne-50);
            }
            .layer-option input[type="radio"]:checked + div .font-medium {
                color: var(--color-blue-dianne-900);
            }

            /* Garante que o canvas do WebGL Heatmap nunca capture ou bloqueie eventos de clique no mapa */
            canvas[id^="webgl-leaflet-"],
            .leaflet-pane > canvas,
            .leaflet-overlay-pane canvas {
                pointer-events: none !important;
            }
        </style>
    @endpush

    <div id="map-wrapper">
        {{-- Container do Mapa --}}
        <div id="air-quality-map"></div>

        {{-- Painel Lateral de Controle --}}
        <div class="absolute top-20 right-4 z-[1000] w-72 bg-white/95 backdrop-blur-md shadow-2xl rounded-xl border border-athens-gray-200 overflow-hidden flex flex-col transition-all">
            <div class="bg-blue-dianne-950 text-white p-4 shadow-sm">
                <h2 class="font-bold text-lg leading-tight tracking-tight">Camadas de Dados</h2>
                <p class="text-xs text-blue-dianne-100 mt-1 opacity-80">Selecione o parâmetro para visualização</p>
            </div>
            
            <div class="p-4 flex flex-col gap-3">
                <label class="layer-option cursor-pointer">
                    <input type="radio" name="mapLayer" value="iqa" class="hidden" checked>
                    <div class="p-3 border border-athens-gray-200 rounded-lg hover:bg-athens-gray-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <x-heroicon-o-globe-americas class="w-6 h-6 rounded-full text-emerald-500 shadow-sm" />
                            <span class="font-medium text-athens-gray-800 text-sm">Qualidade do Ar (IQA)</span>
                        </div>
                    </div>
                </label>
                <label class="layer-option cursor-pointer">
                    <input type="radio" name="mapLayer" value="temperatura" class="hidden">
                    <div class="p-3 border border-athens-gray-200 rounded-lg hover:bg-athens-gray-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <x-heroicon-o-sun class="w-6 h-6 rounded-full text-tahiti-gold-500 shadow-sm" />
                            <span class="font-medium text-athens-gray-800 text-sm">Temperatura</span>
                        </div>
                    </div>
                </label>
                <label class="layer-option cursor-pointer">
                    <input type="radio" name="mapLayer" value="umidade" class="hidden">
                    <div class="p-3 border border-athens-gray-200 rounded-lg hover:bg-athens-gray-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <x-heroicon-o-cloud class="w-6 h-6 rounded-full text-dodger-blue-400 shadow-sm" />
                            <span class="font-medium text-athens-gray-800 text-sm">Umidade Relativa</span>
                        </div>
                    </div>
                </label>
                <label class="layer-option cursor-pointer">
                    <input type="radio" name="mapLayer" value="pm" class="hidden">
                    <div class="p-3 border border-athens-gray-200 rounded-lg hover:bg-athens-gray-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <x-heroicon-o-shield-exclamation class="w-6 h-6 rounded-full text-spindle-500 shadow-sm" />
                            <span class="font-medium text-athens-gray-800 text-sm">Material Particulado</span>
                        </div>
                    </div>
                </label>
                <label class="layer-option cursor-pointer">
                    <input type="radio" name="mapLayer" value="co2" class="hidden">
                    <div class="p-3 border border-athens-gray-200 rounded-lg hover:bg-athens-gray-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <x-heroicon-o-building-office-2 class="w-6 h-6 rounded-full text-athens-gray-700 shadow-sm" />
                            <span class="font-medium text-athens-gray-800 text-sm">Dióxido de Carbono (CO₂)</span>
                        </div>
                    </div>
                </label>
            </div>
        </div>

        {{-- Legenda Georreferenciada --}}
        <div id="map-legend" class="absolute bottom-8 left-4 z-[1000] h-8 w-88 rounded-full shadow-md border border-white/20 flex items-center transition-all duration-300 overflow-hidden">
            <div id="legend-gradient" class="absolute inset-0 opacity-100"></div>
            <div class="relative z-10 flex w-full h-full items-center">
                <span id="legend-unit" class="text-sm font-bold text-white tracking-wider ml-4 mr-2 drop-shadow-md"></span>
                <div id="legend-values" class="relative flex-1 h-full w-full"></div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Função global anexada à janela para permitir o fechamento do Pin via HTML onClick
            window.removeClickMarker = function() {
                if (window.clickMarker) {
                    window.leafletMap.removeLayer(window.clickMarker);
                    window.clickMarker = null;
                }
            };

            // Definição das escalas e paradas de cores unificadas para cada camada
            const layerColorStops = {
                'iqa': [
                    { val: 0, color: [46, 204, 113] },     // Boa (#2ecc71)
                    { val: 48, color: [46, 204, 113] },
                    { val: 52, color: [241, 196, 15] },    // Moderada (#f1c40f)
                    { val: 98, color: [241, 196, 15] },
                    { val: 102, color: [230, 126, 34] },   // Insalubre (#e67e22)
                    { val: 148, color: [230, 126, 34] },
                    { val: 152, color: [231, 76, 60] },    // Perigoso (#e74c3c)
                    { val: 198, color: [231, 76, 60] },
                    { val: 202, color: [142, 68, 173] },   // Péssima (#8e44ad)
                    { val: 400, color: [67, 17, 12] }      // Extrema (#43110c)
                ],
                'temperatura': [
                    { val: -20, color: [80, 131, 167] },   // Azul frio (#5083a7)
                    { val: -10, color: [222, 250, 233] },  // Azul gelo esbranquiçado (#defae9)
                    { val: 0, color: [139, 234, 179] },    // Verde água (#8beab3)
                    { val: 10, color: [46, 204, 113] },    // Verde suave (#2ecc71)
                    { val: 20, color: [241, 196, 15] },    // Amarelo (#f1c40f)
                    { val: 30, color: [230, 126, 34] },    // Laranja (#e67e22)
                    { val: 40, color: [231, 76, 60] }      // Vermelho quente (#e74c3c)
                ],
                'umidade': [
                    { val: 20, color: [231, 76, 60] },     // Vermelho seco (#e74c3c)
                    { val: 35, color: [230, 126, 34] },    // Laranja atenção (#e67e22)
                    { val: 50, color: [241, 196, 15] },    // Amarelo moderado (#f1c40f)
                    { val: 70, color: [46, 204, 113] },    // Verde ideal (#2ecc71)
                    { val: 85, color: [77, 163, 255] },    // Azul claro (#4da3ff)
                    { val: 100, color: [21, 38, 86] }      // Azul marinho úmido (#152656)
                ],
                'pm': [
                    { val: 0, color: [21, 38, 86] },       // Azul escuro (#152656)
                    { val: 10, color: [77, 163, 255] },    // Azul claro (#4da3ff)
                    { val: 25, color: [218, 235, 255] },   // Branco azulado (#daebff)
                    { val: 50, color: [241, 196, 15] },    // Amarelo moderado (#f1c40f)
                    { val: 100, color: [230, 126, 34] },   // Laranja insalubre (#e67e22)
                    { val: 300, color: [231, 76, 60] },    // Vermelho crítico (#e74c3c)
                    { val: 1000, color: [67, 17, 12] }     // Marrom extremo (#43110c)
                ],
                'co2': [
                    { val: 0, color: [7, 44, 24] },        // Verde escuro (#072c18)
                    { val: 200, color: [46, 204, 113] },   // Verde puro (#2ecc71)
                    { val: 400, color: [222, 250, 233] },  // Verde claro (#defae9)
                    { val: 600, color: [241, 196, 15] },   // Amarelo (#f1c40f)
                    { val: 800, color: [230, 126, 34] },   // Laranja (#e67e22)
                    { val: 1000, color: [231, 76, 60] },   // Vermelho (#e74c3c)
                    { val: 1200, color: [67, 17, 12] }     // Marrom escuro (#43110c)
                ]
            };

            function interpolateColor(val, stops) {
                if (val <= stops[0].val) return stops[0].color;
                if (val >= stops[stops.length - 1].val) return stops[stops.length - 1].color;

                for (let i = 0; i < stops.length - 1; i++) {
                    const s1 = stops[i];
                    const s2 = stops[i + 1];
                    if (val >= s1.val && val <= s2.val) {
                        const t = (val - s1.val) / (s2.val - s1.val);
                        return [
                            Math.round(s1.color[0] + t * (s2.color[0] - s1.color[0])),
                            Math.round(s1.color[1] + t * (s2.color[1] - s1.color[1])),
                            Math.round(s1.color[2] + t * (s2.color[2] - s1.color[2]))
                        ];
                    }
                }
                return stops[0].color;
            }

            function getColorForValue(val, layerKey) {
                const stops = layerColorStops[layerKey];
                if (!stops) return [46, 204, 113];
                return interpolateColor(val, stops);
            }

            // Raio de busca adaptativo com base no nível de zoom:
            // - Zoom <= 13 (visão macroscópica): 320m (mancha contida e precisa nos limites urbanos)
            // - Zoom 14 (visão de bairros): 380m
            // - Zoom 15 (visão de avenidas/corredores): 450m
            // - Zoom >= 16 (visão microscópica de quarteirões): 500m (fusão contínua e suave sem "bolas")
            function getAdaptiveSearchRadius(zoom, baseRadius = 320) {
                if (zoom >= 16) return 500;
                if (zoom === 15) return 450;
                if (zoom === 14) return 380;
                return baseRadius;
            }

            // Camada de Superfície Contínua IDW em Canvas nativo para o Leaflet
            // Elimina o blending aditivo e saturações falsas, garantindo 100% de paridade com o popup e legenda
            function definirIdwSurfaceLayer() {
                if (L.IdwSurfaceLayer) return;

                L.IdwSurfaceLayer = L.Layer.extend({
                    options: {
                        cellSize: 4,
                        searchRadius: 320,
                        opacity: 0.75
                    },

                    initialize: function (options) {
                        L.setOptions(this, options);
                        this._data = [];
                        this._colorFn = null;
                        this._bounds = null;
                        this._redrawFrame = null;
                    },

                    onAdd: function (map) {
                        this._map = map;
                        if (!this._canvas) {
                            this._initCanvas();
                        }
                        map.getPanes().overlayPane.appendChild(this._canvas);
                        map.on('moveend zoomend resize', this._redraw, this);
                        if (map.options.zoomAnimation && L.Browser.any3d) {
                            map.on('zoomanim', this._animateZoom, this);
                        }
                        this._redraw();
                    },

                    onRemove: function (map) {
                        if (this._redrawFrame) {
                            cancelAnimationFrame(this._redrawFrame);
                            this._redrawFrame = null;
                        }
                        if (this._canvas && this._canvas.parentNode) {
                            this._canvas.parentNode.removeChild(this._canvas);
                        }
                        map.off('moveend zoomend resize', this._redraw, this);
                        if (map.options.zoomAnimation && L.Browser.any3d) {
                            map.off('zoomanim', this._animateZoom, this);
                        }
                    },

                    setData: function (data, colorFn) {
                        this._data = data || [];
                        if (colorFn) this._colorFn = colorFn;
                        this._redraw();
                    },

                    _initCanvas: function () {
                        this._canvas = L.DomUtil.create('canvas', 'leaflet-idw-surface-layer leaflet-zoom-animated');
                        this._canvas.style.position = 'absolute';
                        this._canvas.style.pointerEvents = 'none';
                        this._ctx = this._canvas.getContext('2d');
                    },

                    _animateZoom: function (e) {
                        if (!this._canvas || !this._bounds) return;
                        const scale = this._map.getZoomScale(e.zoom);
                        const offset = this._map._latLngBoundsToNewLayerBounds(this._bounds, e.zoom, e.center).min;
                        L.DomUtil.setTransform(this._canvas, offset, scale);
                    },

                    _redraw: function () {
                        if (this._redrawFrame) {
                            cancelAnimationFrame(this._redrawFrame);
                        }
                        this._redrawFrame = requestAnimationFrame(() => {
                            this._render();
                            this._redrawFrame = null;
                        });
                    },

                    _render: function () {
                        if (!this._map || !this._canvas || !this._ctx) return;
                        if (!this._data || this._data.length === 0 || !this._colorFn) {
                            this._ctx.clearRect(0, 0, this._canvas.width, this._canvas.height);
                            return;
                        }

                        const map = this._map;
                        const size = map.getSize();
                        if (size.x === 0 || size.y === 0) return;

                        this._bounds = map.getBounds();

                        const topLeft = map.containerPointToLayerPoint([0, 0]);
                        L.DomUtil.setPosition(this._canvas, topLeft);

                        if (this._canvas.width !== size.x || this._canvas.height !== size.y) {
                            this._canvas.width = size.x;
                            this._canvas.height = size.y;
                        } else {
                            this._ctx.clearRect(0, 0, size.x, size.y);
                        }

                        const centerLatLng = map.getCenter();
                        const currentZoom = map.getZoom();
                        const baseRadiusMeters = this.options.searchRadius || 320;
                        const searchRadiusMeters = getAdaptiveSearchRadius(currentZoom, baseRadiusMeters);
                        const latOffset = (searchRadiusMeters / 111320);
                        const p1 = map.latLngToContainerPoint(centerLatLng);
                        const p2 = map.latLngToContainerPoint(L.latLng(centerLatLng.lat + latOffset, centerLatLng.lng));
                        const radiusPixels = Math.max(Math.abs(p1.y - p2.y), 15);

                        let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
                        const points = [];
                        for (let i = 0; i < this._data.length; i++) {
                            const pt = this._data[i];
                            if (pt.lat == null || pt.lng == null || pt.value == null || isNaN(pt.value)) continue;
                            const ptContainer = map.latLngToContainerPoint([pt.lat, pt.lng]);
                            if (ptContainer.x >= -radiusPixels && ptContainer.x <= size.x + radiusPixels &&
                                ptContainer.y >= -radiusPixels && ptContainer.y <= size.y + radiusPixels) {
                                points.push({
                                    x: ptContainer.x,
                                    y: ptContainer.y,
                                    val: Number(pt.value)
                                });
                                if (ptContainer.x < minX) minX = ptContainer.x;
                                if (ptContainer.x > maxX) maxX = ptContainer.x;
                                if (ptContainer.y < minY) minY = ptContainer.y;
                                if (ptContainer.y > maxY) maxY = ptContainer.y;
                            }
                        }

                        if (points.length === 0) {
                            this._ctx.clearRect(0, 0, size.x, size.y);
                            return;
                        }

                        // Resolução dinâmica adaptada ao nível de zoom para manter altíssimo FPS
                        const baseCellSize = this.options.cellSize || 4;
                        const cellSize = currentZoom >= 16 ? Math.max(baseCellSize, 6) : (currentZoom >= 14 ? Math.max(baseCellSize, 5) : baseCellSize);

                        const gridW = Math.ceil(size.x / cellSize);
                        const gridH = Math.ceil(size.y / cellSize);

                        if (!this._offCanvas) {
                            this._offCanvas = document.createElement('canvas');
                        }
                        if (this._offCanvas.width !== gridW || this._offCanvas.height !== gridH) {
                            this._offCanvas.width = gridW;
                            this._offCanvas.height = gridH;
                        }
                        const offCtx = this._offCanvas.getContext('2d');
                        const imgData = offCtx.createImageData(gridW, gridH);
                        const data32 = new Uint32Array(imgData.data.buffer);

                        const r2 = radiusPixels * radiusPixels;
                        const invRadius = 1 / radiusPixels;
                        const baseOpacity = this.options.opacity || 0.75;
                        const colorFn = this._colorFn;

                        const startGx = Math.max(0, Math.floor((minX - radiusPixels) / cellSize));
                        const endGx = Math.min(gridW - 1, Math.ceil((maxX + radiusPixels) / cellSize));
                        const startGy = Math.max(0, Math.floor((minY - radiusPixels) / cellSize));
                        const endGy = Math.min(gridH - 1, Math.ceil((maxY + radiusPixels) / cellSize));

                        for (let gy = startGy; gy <= endGy; gy++) {
                            const py = gy * cellSize + cellSize / 2;
                            const rowOffset = gy * gridW;

                            for (let gx = startGx; gx <= endGx; gx++) {
                                const px = gx * cellSize + cellSize / 2;

                                let num = 0;
                                let den = 0;
                                let minD2 = Infinity;

                                for (let p = 0; p < points.length; p++) {
                                    const pt = points[p];
                                    const dx = px - pt.x;
                                    if (dx > radiusPixels || dx < -radiusPixels) continue;
                                    const dy = py - pt.y;
                                    if (dy > radiusPixels || dy < -radiusPixels) continue;

                                    const d2 = dx * dx + dy * dy;
                                    if (d2 < r2) {
                                        const dist = Math.sqrt(d2);
                                        const u = dist * invRadius; // Distância normalizada no intervalo [0, 1)
                                        const oneMinusU = 1.0 - u;

                                        // Método de Shepard Modificado com suporte compacto:
                                        // O peso atinge exatamente 0 na borda do raio com derivada zero (C1 suave),
                                        // eliminando 100% dos arcos circulares e cortes bruscos entre sensores vizinhos.
                                        const w = (oneMinusU * oneMinusU) / Math.max(u, 0.005);
                                        num += pt.val * w;
                                        den += w;

                                        if (d2 < minD2) {
                                            minD2 = d2;
                                        }
                                    }
                                }

                                if (den > 0) {
                                    const interpolatedVal = num / den;
                                    const rgb = colorFn(interpolatedVal);

                                    const distRatio = Math.min(1.0, Math.sqrt(minD2) * invRadius);
                                    // Transição suave de opacidade (Hermite Smoothstep) apenas nas bordas externas do cluster.
                                    // Abaixo de 55% do raio (zona de sobreposição entre sensores a até 200m), a opacidade permanece sólida (sem afundar no meio).
                                    const edgeFade = distRatio > 0.55 ? Math.max(0, 1.0 - Math.pow((distRatio - 0.55) / 0.45, 2)) : 1.0;
                                    const alpha = Math.max(0, Math.min(255, Math.round(baseOpacity * edgeFade * 255)));

                                    data32[rowOffset + gx] = (alpha << 24) | (rgb[2] << 16) | (rgb[1] << 8) | rgb[0];
                                }
                            }
                        }

                        offCtx.putImageData(imgData, 0, 0);

                        this._ctx.clearRect(0, 0, size.x, size.y);
                        this._ctx.imageSmoothingEnabled = true;
                        this._ctx.imageSmoothingQuality = 'high';
                        this._ctx.drawImage(this._offCanvas, 0, 0, gridW, gridH, 0, 0, size.x, size.y);
                    }
                });

                L.idwSurfaceLayer = function (options) {
                    return new L.IdwSurfaceLayer(options);
                };
            }

            function inicializarMapa() {
                if (typeof L === 'undefined') {
                    setTimeout(inicializarMapa, 50);
                    return;
                }

                definirIdwSurfaceLayer();

                const fallbackLat = {{ $centroMapa['lat'] ?? -21.967194 }};
                const fallbackLng = {{ $centroMapa['lng'] ?? -46.812740 }};
                const initialZoom = {{ $centroMapa['zoom'] ?? 13 }};

                const map = L.map('air-quality-map', { zoomControl: false }).setView([fallbackLat, fallbackLng], initialZoom);
                window.leafletMap = map; 
                window.clickMarker = null;
                let currentLayerKey = 'iqa';
                let currentHeatmapLayer = null;
                const searchRadiusMeters = 320;

                L.tileLayer('https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', {
                    maxZoom: 16, minZoom: 3,
                    attribution: '&copy; <a href="https://openstreetmap.org/copyright">OpenStreetMap contributors</a>, Tiles style by Humanitarian OpenStreetMap Team hosted by OpenStreetMap France'
                }).addTo(map);

                // Dicionário das Camadas e Dados
                const mapLayersData = {
                    'iqa': {
                        name: 'Qualidade do Ar (IQA)',
                        icon: `<x-heroicon-o-globe-americas class="w-3.5 h-3.5" />`,
                        type: 'segments',
                        unit: 'IQA',
                        searchRadius: searchRadiusMeters,
                        segments: [
                            { text: 'Boa', color: '#2ecc71' }, 
                            { text: 'Moderada', color: '#f1c40f' }, 
                            { text: 'Insalubre', color: '#e67e22' }, 
                            { text: 'Perigoso', color: '#e74c3c' },
                            { text: 'Péssima', color: '#8e44ad' },
                            { text: 'Extrema', color: '#43110c' }
                        ],
                        data: { 
                            data: @json($dadosIqa ?? [])
                        }
                    },
                    'temperatura': {
                        name: 'Temperatura',
                        icon: `<x-heroicon-o-sun class="w-3.5 h-3.5" />`,
                        type: 'continuous',
                        unit: '°C',
                        searchRadius: searchRadiusMeters,
                        legend: [
                            { val: -20, pos: 10 }, { val: -10, pos: 25 }, { val: 0,   pos: 40 },
                            { val: 10,  pos: 55 }, { val: 20,  pos: 70 }, { val: 30,  pos: 85 }, { val: 40,  pos: 95 }
                        ],
                        data: { 
                            data: @json($dadosTemperatura ?? [])
                        }
                    },
                    'umidade': {
                        name: 'Umidade Relativa',
                        icon: `<x-heroicon-o-cloud class="w-3.5 h-3.5" />`,
                        type: 'continuous',
                        unit: '%',
                        searchRadius: searchRadiusMeters,
                        legend: [
                            { val: 20, pos: 15 }, { val: 35, pos: 35 }, { val: 50, pos: 55 },
                            { val: 70, pos: 75 }, { val: 100, pos: 95 }
                        ],
                        data: { 
                            data: @json($dadosUmidade ?? [])
                        }
                    },
                    'pm': {
                        name: 'Material Particulado',
                        icon: `<x-heroicon-o-shield-exclamation class="w-3.5 h-3.5" />`,
                        type: 'continuous',
                        unit: 'µg/m³',
                        searchRadius: searchRadiusMeters,
                        legend: [
                            { val: 0,    pos: 12 }, { val: 10,   pos: 28 }, { val: 25,   pos: 46 },
                            { val: 50,   pos: 65 }, { val: 100,  pos: 82 }, { val: 500,  pos: 95 }
                        ],
                        data: { 
                            data: @json($dadosPm ?? [])
                        }
                    },
                    'co2': {
                        name: 'Dióxido de Carbono (CO₂)',
                        icon: `<x-heroicon-o-building-office-2 class="w-3.5 h-3.5" />`,
                        type: 'continuous',
                        unit: 'ppm',
                        searchRadius: searchRadiusMeters,
                        legend: [
                            { val: 0,    pos: 12 }, { val: 200,  pos: 28 }, { val: 400,  pos: 46 },
                            { val: 600,  pos: 64 }, { val: 800,  pos: 80 }, { val: 1200, pos: 95 }
                        ],
                        data: { 
                            data: @json($dadosCo2 ?? [])
                        }
                    }
                };

                // Lógica de Ponderação IDW (Interpolação de valores entre os Sensores)
                function getInterpolatedData(lat, lng, layerKey) {
                    const layerInfo = mapLayersData[layerKey];
                    if (!layerInfo || !layerInfo.data || !layerInfo.data.data) return null;
                    const dataPoints = layerInfo.data.data;
                    
                    const currentZoom = (window.leafletMap && typeof window.leafletMap.getZoom === 'function') 
                        ? window.leafletMap.getZoom() 
                        : 14;
                    const baseRadius = layerInfo.searchRadius || searchRadiusMeters || 320;
                    const searchRadius = getAdaptiveSearchRadius(currentZoom, baseRadius);

                    let numerator = 0;
                    let denominator = 0;
                    let nearestPt = null;
                    let minDistance = Infinity;
                    
                    // Converte a posição do clique para o objeto nativo do Leaflet
                    const clickLatLng = L.latLng(lat, lng);

                    dataPoints.forEach(pt => {
                        const ptLatLng = L.latLng(pt.lat, pt.lng);
                        const dist = ptLatLng.distanceTo(clickLatLng);

                        // Só faz a interpolação se o clique estiver DENTRO do raio de alcance do sensor
                        // Usa exatamente a mesma fórmula Shepard Modificado da renderização visual
                        if (dist < searchRadius) {
                            const u = dist / searchRadius;
                            const oneMinusU = 1.0 - u;
                            const weight = (oneMinusU * oneMinusU) / Math.max(u, 0.005);
                            numerator += pt.value * weight;
                            denominator += weight;

                            if (dist < minDistance) {
                                minDistance = dist;
                                nearestPt = pt;
                            }
                        }
                    });

                    // Se nenhum sensor estiver no alcance do raio visual, retorna null (oculta o PIN)
                    if (denominator === 0) return null;
                    return {
                        value: Math.round(numerator / denominator),
                        dataHora: nearestPt ? (nearestPt.data_hora || null) : null
                    };
                }

                function getInterpolatedValue(lat, lng, layerKey) {
                    const res = getInterpolatedData(lat, lng, layerKey);
                    return res ? res.value : null;
                }

                // Função auxiliar para definir o texto e a cor com base no valor da camada
                function getStatusInfo(val, layerKey) {
                    if (layerKey === 'iqa') {
                        if (val <= 50) return { text: 'Boa', color: '#2ecc71' };
                        if (val <= 100) return { text: 'Moderada', color: '#f1c40f' };
                        if (val <= 150) return { text: 'Insalubre', color: '#e67e22' };
                        if (val <= 200) return { text: 'Perigoso', color: '#e74c3c' };
                        if (val <= 400) return { text: 'Péssima', color: '#8e44ad' };
                        return { text: 'Extrema', color: '#43110c' };
                        
                    }
                    const rgb = getColorForValue(val, layerKey);
                    return { text: mapLayersData[layerKey].name, color: `rgb(${rgb[0]}, ${rgb[1]}, ${rgb[2]})` };
                }

                // Renderiza o marcador no estilo visual OpenAir Metrics
                function updateClickMarker(latlng) {
                    const interpResult = getInterpolatedData(latlng.lat, latlng.lng, currentLayerKey);
                    
                    if (interpResult === null) {
                        removeClickMarker();
                        return; 
                    }

                    const val = interpResult.value;
                    const dataHora = interpResult.dataHora;

                    // Puxa as informações do Dicionário
                    const layerInfo = mapLayersData[currentLayerKey];
                    const unit = layerInfo.unit || '';

                    const textValue = `${val} ${unit}`.trim();
                    const layerName = layerInfo.name;
                    const layerIcon = layerInfo.icon;

                    // Puxa as regras dinâmicas de status para preencher os dados
                    const statusInfo = getStatusInfo(val, currentLayerKey);
                    const statusText = statusInfo.text;
                    const badgeColor = statusInfo.color;

                    // Lógica condicional: Verifica se a camada ativa é a de Qualidade do Ar
                    const isIqa = currentLayerKey === 'iqa';

                    const statusHtml = isIqa ? `
                        <!-- Legenda (Status ex: "Bom", "Moderado") -->
                        <div class="text-[13px] text-[#cccccc] mt-1.5 mb-0.5 tracking-wide">
                            ${statusText}
                        </div>
                    ` : '';

                    const dataHoraHtml = dataHora ? `
                        <!-- Data e Hora da Leitura -->
                        <div class="text-[11px] text-[#cccccc] flex items-center gap-1.5 mt-1.5 font-normal tracking-normal border-t border-white/10 pt-1">
                            <x-heroicon-o-clock class="w-3.5 h-3.5 text-[#b0b2b5] shrink-0" />
                            <span class="whitespace-nowrap">${dataHora}</span>
                        </div>
                    ` : '';

                    const badgeHtml = isIqa ? `
                        <!-- Botão circular (Canto Inferior Direito) - Cor Dinâmica -->
                        <div class="absolute -right-1 -bottom-2 w-8 h-8 rounded-full flex items-center justify-center border-[3.5px] border-[#4a4b4d] shadow-sm z-20" style="background-color: ${badgeColor};">
                            <x-heroicon-o-chevron-down class="w-4 h-4 text-white stroke-[3]" />
                        </div>
                    ` : '';

                    // HTML reestruturado para usar as classes utilitárias nativas do Tailwind
                    const markerHtml = `
                        <div class="pointer-events-auto relative z-50">
                            <!-- Ponto geográfico -->
                            <div class="absolute w-3 h-3 bg-white border-2 border-blue-dianne-900 rounded-full shadow-md z-20 -top-1.5 -left-1.5"></div>

                            <!-- Linha Vertical -->
                            <div class="absolute w-0.5 h-10 bg-blue-dianne-900 shadow-sm z-10 bottom-0 -left-px"></div>

                            <!-- Container da Caixa -->
                            <div class="absolute flex flex-col items-center bottom-10 left-0 -translate-x-1/2">
                                
                                <!-- Caixa de Informação Cinza Escuro -->
                                <div class="relative bg-[#4a4b4d] text-white pl-3.5 pr-8 py-2 rounded-r-[24px] rounded-tl-lg rounded-bl-sm shadow-[0_5px_15px_rgba(0,0,0,0.35)] min-w-[155px] border border-white/10">
                                    
                                    <!-- Valor e Unidade (Adiciona pb-1 se não tiver legenda nem data para manter as proporções) -->
                                    <div class="text-[24px] font-semibold leading-none tracking-tight flex items-baseline gap-1.5 pt-1 ${!isIqa && !dataHora ? 'pb-1' : ''}">
                                        ${val} <span class="text-[16px] font-normal">${unit}</span>
                                    </div>
                                    
                                    <!-- Exibe apenas no IQA -->
                                    ${statusHtml}

                                    <!-- Data e Hora da Leitura -->
                                    ${dataHoraHtml}
                                    
                                    <!-- Botão Fechar (X superior) -->
                                    <button onclick="removeClickMarker()" class="absolute -top-2.5 -right-2 bg-[#5c5d5f] rounded-full w-[26px] h-[26px] flex items-center justify-center border-2 border-[#4a4b4d] shadow hover:bg-athens-gray-400 transition cursor-pointer z-30">
                                        <x-heroicon-o-x-mark class="w-4 h-4 text-white stroke-2" />
                                    </button>
                                    
                                    <!-- Exibe apenas no IQA -->
                                    ${badgeHtml}
                                </div>

                            </div>
                        </div>
                    `;

                    const customIcon = L.divIcon({
                        className: 'bg-transparent',
                        html: markerHtml,
                        iconSize: [0, 0],
                        iconAnchor: [0, 0] 
                    });

                    if (window.clickMarker) {
                        window.clickMarker.setLatLng(latlng);
                        window.clickMarker.setIcon(customIcon);
                    } else {
                        window.clickMarker = L.marker(latlng, { icon: customIcon }).addTo(map);
                    }
                }

                // Dispara a criação/movimentação do PIN ao clicar no mapa
                map.on('click', function(e) {
                    updateClickMarker(e.latlng);
                });

                // Função de montagem dinâmica da legenda
                function updateLegend(layerKey) {
                    const layerInfo = mapLayersData[layerKey];
                    const gradientDiv = document.getElementById('legend-gradient');
                    const valuesContainer = document.getElementById('legend-values');
                    const unitSpan = document.getElementById('legend-unit');
                    
                    valuesContainer.innerHTML = '';
                    
                    if (layerInfo.type === 'segments') {
                        unitSpan.style.display = 'none';
                        gradientDiv.style.background = 'transparent';
                        valuesContainer.className = 'flex w-full h-full';
                        
                        layerInfo.segments.forEach((segment) => {
                            const block = document.createElement('div');
                            block.className = 'flex-1 flex items-center justify-center h-full border-r border-white/20 last:border-r-0';
                            block.style.backgroundColor = segment.color;
                            
                            const textSpan = document.createElement('span');
                            textSpan.innerText = segment.text;
                            textSpan.className = 'text-sm text-white font-medium drop-shadow-md tracking-wide';
                            
                            block.appendChild(textSpan);
                            valuesContainer.appendChild(block);
                        });
                    } else {
                        unitSpan.style.display = 'block';
                        unitSpan.innerText = layerInfo.unit;
                        valuesContainer.className = 'relative flex-1 h-full';
                        
                        const legendData = layerInfo.legend;
                        const cssStops = legendData.map(item => {
                            const rgb = getColorForValue(item.val, layerKey);
                            return `rgb(${rgb[0]}, ${rgb[1]}, ${rgb[2]}) ${item.pos}%`;
                        });
                        
                        const firstRgb = getColorForValue(legendData[0].val, layerKey);
                        const lastRgb = getColorForValue(legendData[legendData.length - 1].val, layerKey);
                        const gradientString = `rgb(${firstRgb[0]}, ${firstRgb[1]}, ${firstRgb[2]}) 0%, ${cssStops.join(', ')}, rgb(${lastRgb[0]}, ${lastRgb[1]}, ${lastRgb[2]}) 100%`;
                        
                        gradientDiv.style.backgroundColor = 'transparent';
                        gradientDiv.style.backgroundImage = `linear-gradient(to right, ${gradientString})`;
                        
                        layerInfo.legend.forEach(item => {
                            const span = document.createElement('span');
                            span.innerText = item.val;
                            span.className = 'absolute top-1/2 -translate-y-1/2 -translate-x-1/2 text-sm font-medium text-white drop-shadow-lg whitespace-nowrap';
                            span.style.left = `${item.pos}%`;
                            valuesContainer.appendChild(span);
                        });
                    }
                }

                function renderLayer(layerKey) {
                    currentLayerKey = layerKey;
                    
                    if (currentHeatmapLayer) {
                        map.removeLayer(currentHeatmapLayer);
                    }
                    
                    const layerInfo = mapLayersData[layerKey];

                    currentHeatmapLayer = L.idwSurfaceLayer({
                        cellSize: 4,
                        searchRadius: layerInfo.searchRadius || searchRadiusMeters || 320,
                        opacity: 0.75
                    });

                    currentHeatmapLayer.setData(layerInfo.data.data, (val) => getColorForValue(val, layerKey));
                    map.addLayer(currentHeatmapLayer);
                    
                    updateLegend(layerKey);
                    
                    if (window.clickMarker) {
                        updateClickMarker(window.clickMarker.getLatLng());
                    }
                }

                // Iniciar com a primeira camada
                renderLayer('iqa');

                const radios = document.querySelectorAll('input[name="mapLayer"]');
                radios.forEach(radio => {
                    radio.addEventListener('change', (e) => renderLayer(e.target.value));
                });

                let frameAtualizacaoPendente = false;

                // Atualiza suavemente a camada e o PIN aberto sem destruir/recriar camadas nem travar cliques
                function atualizarHeatmapEMarcador() {
                    if (currentHeatmapLayer && mapLayersData[currentLayerKey]) {
                        const layerInfo = mapLayersData[currentLayerKey];
                        currentHeatmapLayer.setData(layerInfo.data.data, (val) => getColorForValue(val, currentLayerKey));
                    }

                    if (window.clickMarker) {
                        updateClickMarker(window.clickMarker.getLatLng());
                    }
                }

                function agendarAtualizacaoVisual() {
                    if (!frameAtualizacaoPendente) {
                        frameAtualizacaoPendente = true;
                        requestAnimationFrame(() => {
                            atualizarHeatmapEMarcador();
                            frameAtualizacaoPendente = false;
                        });
                    }
                }

                // Atualização em Tempo Real via WebSockets (Laravel Reverb + Echo)
                function processarNovaMedicao(dados) {
                    if (!dados) return;

                    const lat = dados.lat != null ? parseFloat(dados.lat) : null;
                    const lng = dados.lng != null ? parseFloat(dados.lng) : null;
                    const dataHora = dados.data_hora;

                    const metricasMap = {
                        'iqa': dados.iqa,
                        'temperatura': dados.temperatura,
                        'umidade': dados.umidade,
                        'pm': dados.poeira,
                        'co2': dados.co2
                    };

                    // Atualiza os pontos de dados em memória para cada camada do mapa
                    Object.keys(metricasMap).forEach(key => {
                        if (!mapLayersData[key]) return;
                        const dataArr = mapLayersData[key].data.data;
                        const val = metricasMap[key];
                        if (val == null) return;

                        // 1. Procura primeiro por estacao_id (mais confiável e preciso)
                        let idx = -1;
                        if (dados.estacao_id) {
                            idx = dataArr.findIndex(pt => pt.estacao_id && pt.estacao_id === dados.estacao_id);
                        }

                        // 2. Se não encontrou por ID, busca por proximidade geográfica das coordenadas
                        if (idx < 0 && lat != null && lng != null) {
                            idx = dataArr.findIndex(pt => Math.abs(pt.lat - lat) < 0.0001 && Math.abs(pt.lng - lng) < 0.0001);
                        }

                        if (idx >= 0) {
                            dataArr[idx].value = val;
                            dataArr[idx].data_hora = dataHora;
                            if (dados.estacao_id && !dataArr[idx].estacao_id) {
                                dataArr[idx].estacao_id = dados.estacao_id;
                            }
                            if (lat != null && lng != null) {
                                dataArr[idx].lat = lat;
                                dataArr[idx].lng = lng;
                            }
                        } else if (lat != null && lng != null) {
                            dataArr.push({
                                estacao_id: dados.estacao_id || null,
                                lat: lat,
                                lng: lng,
                                value: val,
                                data_hora: dataHora
                            });
                        }
                    });

                    // Atualiza a visualização sem recriar contextos WebGL nem travar cliques no mapa
                    agendarAtualizacaoVisual();
                }

                // Inicialização resiliente da escuta do canal público 'medicoes'
                function inicializarEcho() {
                    if (window.Echo) {
                        window.Echo.channel('medicoes')
                            .listen('.NovaMedicaoRecebida', processarNovaMedicao)
                            .listen('NovaMedicaoRecebida', processarNovaMedicao);
                    } else {
                        setTimeout(inicializarEcho, 250);
                    }
                }
                inicializarEcho();
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', inicializarMapa);
            } else {
                inicializarMapa();
            }
        </script>
    @endpush
</x-layouts.app>