<x-layouts.app title="Ordem de Instalação #{{ $matriz->ordem_instalacao ?? 1 }} - OpenAir Metrics">
    <div class="flex flex-col md:flex-row h-full w-full bg-athens-gray-50 dark:bg-athens-gray-950 transition-colors duration-200">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="instalacoes" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto min-h-0 p-4 sm:p-6 lg:p-8">
            <div class="max-w-5xl mx-auto space-y-6">

                <!-- Breadcrumb e Voltar -->
                <div class="flex items-center gap-2 text-sm text-athens-gray-500 dark:text-athens-gray-400">
                    <a href="{{ route('instalacoes.index') }}" class="hover:text-blue-dianne-600 dark:hover:text-blue-dianne-400 transition">Ordens de Instalação</a>
                    <x-heroicon-o-chevron-right class="w-4 h-4" />
                    <span class="text-blue-dianne-950 dark:text-white font-medium">Cluster {{ $matriz->bairro_nome ?? $matriz->bairro?->nome }}</span>
                </div>

                <!-- Cabeçalho da Ordem de Instalação -->
                <div class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-2xl font-bold text-blue-dianne-950 dark:text-white tracking-tight">Roteiro de Instalação em Campo</h1>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-dianne-50 dark:bg-blue-dianne-950/50 text-blue-dianne-700 dark:text-blue-dianne-300 border border-blue-dianne-200 dark:border-blue-dianne-800">
                                {{ $estacoes->count() }} Estações na Malha
                            </span>
                        </div>
                        <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mt-1">
                            Região: <strong>{{ $matriz->bairro_nome ?? $matriz->bairro?->nome }}</strong> ({{ $matriz->cidade_nome ?? $matriz->bairro?->cidade?->nome }} - {{ $matriz->estado_uf ?? $matriz->bairro?->cidade?->estado?->uf }}).
                            Siga a ordem estrita dos passos para garantir o emparelhamento da rede.
                        </p>
                    </div>

                    <a href="{{ route('instalacoes.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-athens-gray-700 dark:text-athens-gray-200 bg-athens-gray-100 dark:bg-athens-gray-800 hover:bg-athens-gray-200 dark:hover:bg-athens-gray-700 px-4 py-2.5 rounded-lg transition shrink-0">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Voltar para a Lista
                    </a>
                </div>

                <!-- Lista Passo a Passo Sequencial -->
                <div class="space-y-4">
                    @php
                        $primeiraPendenteDefinida = false;
                    @endphp

                    @foreach ($estacoes as $index => $estacao)
                        @php
                            $isInstalada = $estacao->status_instalacao === 'Instalada' && ! empty($estacao->mac_address);
                            $isMatriz = $estacao->tipo_estacao === 'Estação Matriz';
                            $origem = $isMatriz ? null : $estacao->estacaoOrigem;
                            $origemInstalada = $isMatriz || ($origem && $origem->status_instalacao === 'Instalada' && ! empty($origem->mac_address));
                            
                            // Uma estação pendente fica bloqueada se a sua estação de origem ainda não foi instalada
                            $bloqueada = ! $isInstalada && ! $origemInstalada;

                            $isProxima = false;
                            if (! $isInstalada && $origemInstalada && ! $primeiraPendenteDefinida) {
                                $isProxima = true;
                                $primeiraPendenteDefinida = true;
                            }
                        @endphp

                        <div id="card-estacao-{{ $estacao->public_id }}" class="rounded-xl shadow-sm border {{ $isInstalada ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/20 dark:bg-emerald-950/20' : ($bloqueada ? 'border-athens-gray-200 dark:border-athens-gray-800 opacity-70 bg-athens-gray-50/60 dark:bg-athens-gray-900/40' : ($isProxima ? 'bg-white dark:bg-athens-gray-900 border-blue-dianne-300 dark:border-blue-dianne-700 ring-2 ring-blue-dianne-100 dark:ring-blue-dianne-950/60' : 'bg-white dark:bg-athens-gray-900 border-dodger-blue-300 dark:border-dodger-blue-700')) }} p-6 transition-all">
                            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">

                                <!-- Identificação e Número do Passo -->
                                <div class="flex items-start gap-3.5">
                                    <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center font-bold text-base {{ $isInstalada ? 'bg-emerald-600 text-white' : ($isMatriz ? 'bg-blue-dianne-600 text-white' : 'bg-spindle-600 text-white') }} shadow-sm">
                                        @if ($isInstalada)
                                            <x-heroicon-o-check class="w-6 h-6 stroke-[2.5]" />
                                        @else
                                            #{{ $estacao->ordem_instalacao ?? ($index + 1) }}
                                        @endif
                                    </div>

                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h2 class="text-base font-bold text-blue-dianne-950 dark:text-white">
                                                Passo #{{ $estacao->ordem_instalacao ?? ($index + 1) }}: {{ $estacao->tipo_estacao }}
                                            </h2>

                                            @if ($isInstalada)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-200 border border-emerald-300 dark:border-emerald-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                    Ativa e Instalada
                                                </span>
                                            @elseif ($bloqueada)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-athens-gray-200 dark:bg-athens-gray-700 text-athens-gray-600 dark:text-athens-gray-300">
                                                    <x-heroicon-o-lock-closed class="w-3.5 h-3.5" />
                                                    Aguardando Estação Anterior (#{{ $origem->ordem_instalacao ?? 1 }})
                                                </span>
                                            @elseif ($isProxima)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-tahiti-gold-100 dark:bg-tahiti-gold-950/60 text-tahiti-gold-800 dark:text-tahiti-gold-200 border border-tahiti-gold-300 dark:border-tahiti-gold-700 animate-pulse">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-tahiti-gold-600"></span>
                                                    Próxima a Instalar
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-dodger-blue-50 dark:bg-dodger-blue-950/60 text-dodger-blue-700 dark:text-dodger-blue-300 border border-dodger-blue-300 dark:border-dodger-blue-700">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-dodger-blue-500"></span>
                                                    Liberada para Instalação
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Endereço e Detalhes de Localização -->
                                        <p class="text-sm font-semibold text-blue-dianne-900 dark:text-blue-dianne-200 flex items-center gap-1.5">
                                            <x-heroicon-o-map-pin class="w-4 h-4 text-cinnabar-500 shrink-0" />
                                            {{ $estacao->endereco_completo ?: ($estacao->logradouro ? $estacao->logradouro . ', ' . ($estacao->numero ?? 'S/N') . ' - ' . $estacao->bairro_nome : 'Localização georreferenciada') }}
                                        </p>

                                        <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 font-mono">
                                            Lat: {{ number_format($estacao->latitude, 5) }}, Lng: {{ number_format($estacao->longitude, 5) }}
                                            @if (! $isMatriz && $origem)
                                                • <span class="text-dodger-blue-600 dark:text-dodger-blue-400 font-sans font-medium">Conexão a {{ round($estacao->distancia_origem_metros ?? 180) }}m da Estação #{{ $origem->ordem_instalacao ?? 1 }} ({{ $origem->tipo_estacao }})</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <!-- Botão de Navegação GPS -->
                                <div class="shrink-0 flex items-center gap-2">
                                    @php
                                        $mapsQuery = ($estacao->latitude && $estacao->longitude) 
                                            ? "{$estacao->latitude},{$estacao->longitude}" 
                                            : urlencode($estacao->endereco_completo ?: ($estacao->logradouro ? $estacao->logradouro . ', ' . ($estacao->numero ?? 'S/N') . ' - ' . $estacao->bairro_nome : ''));
                                    @endphp
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $mapsQuery }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-xs font-bold text-dodger-blue-700 dark:text-dodger-blue-300 bg-dodger-blue-50 dark:bg-dodger-blue-950/50 hover:bg-dodger-blue-100 dark:hover:bg-dodger-blue-900/50 border border-dodger-blue-200 dark:border-dodger-blue-800 px-3 py-2 rounded-lg transition shadow-sm">
                                        <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                                        Abrir no GPS / Waze
                                    </a>
                                </div>
                            </div>

                            <!-- Área de Ação do Instalador / Vinculação de Patrimônio ou MAC -->
                            <div class="mt-4 pt-4 border-t border-athens-gray-200/80 dark:border-athens-gray-800">
                                @if ($isInstalada)
                                    <!-- Informações da Instalação Concluída -->
                                    <div class="flex flex-wrap items-center justify-between gap-3 bg-emerald-50/80 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 p-3.5 rounded-lg text-xs text-emerald-900 dark:text-emerald-200">
                                        <div class="flex flex-wrap items-center gap-3">
                                            @if ($estacao->patrimonio?->numero_patrimonio)
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-bold">Patrimônio:</span>
                                                    <span class="font-semibold bg-white dark:bg-athens-gray-800 px-2 py-0.5 rounded border border-emerald-300 dark:border-emerald-700 text-emerald-900 dark:text-emerald-200">
                                                        #{{ $estacao->patrimonio->numero_patrimonio }}
                                                    </span>
                                                </div>
                                            @endif
                                            @if ($estacao->mac_address)
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-bold">MAC Address:</span>
                                                    <span class="font-mono font-bold bg-white dark:bg-athens-gray-800 px-2.5 py-1 rounded border border-emerald-300 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200 text-sm">
                                                        {{ $estacao->mac_address }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-athens-gray-600 dark:text-athens-gray-400">
                                            Instalado em <strong>{{ $estacao->data_instalacao ? \Carbon\Carbon::parse($estacao->data_instalacao)->format('d/m/Y \à\s H:i') : 'Hoje' }}</strong>
                                            @if ($estacao->instalador)
                                                por <strong>{{ $estacao->instalador->name }}</strong>
                                            @endif
                                        </div>
                                    </div>
                                @elseif ($bloqueada)
                                    <div class="bg-athens-gray-100 dark:bg-athens-gray-800/60 border border-athens-gray-200 dark:border-athens-gray-700 p-3.5 rounded-lg text-xs text-athens-gray-500 dark:text-athens-gray-400 flex items-center gap-2">
                                        <x-heroicon-o-lock-closed class="w-4 h-4 shrink-0 text-athens-gray-400" />
                                        <span>Instale e ative primeiro a estação anterior <strong>(#{{ $origem->ordem_instalacao ?? 1 }} - {{ $origem->tipo_estacao ?? 'Estação Matriz' }})</strong> para liberar a vinculação desta.</span>
                                    </div>
                                @elseif (! Auth::user()?->isInstalador())
                                    <div class="bg-tahiti-gold-50 dark:bg-tahiti-gold-950/40 border border-tahiti-gold-200 dark:border-tahiti-gold-800 p-3.5 rounded-lg text-xs text-tahiti-gold-900 dark:text-tahiti-gold-200 flex items-center gap-2">
                                        <x-heroicon-o-shield-exclamation class="w-4 h-4 shrink-0 text-tahiti-gold-600 dark:text-tahiti-gold-400" />
                                        <span>Estação pendente de instalação em campo. Apenas técnicos com perfil de <strong>Instalador</strong> podem vincular o patrimônio e concluir a ativação.</span>
                                    </div>
                                @else
                                    <!-- Formulário Interativo de Ativação (Patrimônio ou MAC) -->
                                    <div class="bg-blue-dianne-50/60 dark:bg-blue-dianne-950/40 border border-blue-dianne-200 dark:border-blue-dianne-800 p-4 rounded-xl space-y-3">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-xs font-bold text-blue-dianne-950 dark:text-white uppercase tracking-wide flex items-center gap-1.5">
                                                    <x-heroicon-o-qr-code class="w-4 h-4 text-blue-dianne-600 dark:text-blue-dianne-400" />
                                                    Registrar Estação:
                                                </span>
                                                <!-- Seletor de Modo: Patrimônio ou MAC -->
                                                <div class="inline-flex p-0.5 bg-athens-gray-200/80 dark:bg-athens-gray-800 rounded-lg text-xs font-semibold">
                                                    <button type="button" onclick="setModoVinculacao('{{ $estacao->public_id }}', 'patrimonio')" id="tab-patrimonio-{{ $estacao->public_id }}" class="px-2.5 py-1 rounded-md transition bg-white dark:bg-athens-gray-700 text-blue-dianne-950 dark:text-white shadow-xs cursor-pointer">
                                                        Por Patrimônio
                                                    </button>
                                                    <button type="button" onclick="setModoVinculacao('{{ $estacao->public_id }}', 'mac')" id="tab-mac-{{ $estacao->public_id }}" class="px-2.5 py-1 rounded-md transition text-athens-gray-600 dark:text-athens-gray-400 hover:text-blue-dianne-950 dark:hover:text-white cursor-pointer">
                                                        Por MAC Address
                                                    </button>
                                                </div>
                                            </div>
                                            <span class="text-[11px] text-athens-gray-500 dark:text-athens-gray-400">Informe o tombamento/patrimônio ou endereço MAC</span>
                                        </div>

                                        <!-- Container Modo Patrimônio -->
                                        <div id="container-patrimonio-{{ $estacao->public_id }}" class="space-y-2">
                                            <div class="flex flex-col sm:flex-row gap-2.5">
                                                <div class="relative flex-1">
                                                    <input 
                                                        type="text" 
                                                        id="patrimonio-input-{{ $estacao->public_id }}" 
                                                        placeholder="Digite o Nº do Patrimônio (ex: PAT-00123)" 
                                                        class="w-full text-sm font-semibold px-3.5 py-2.5 bg-white dark:bg-athens-gray-800 border border-athens-gray-300 dark:border-athens-gray-700 text-athens-gray-900 dark:text-athens-gray-100 placeholder:text-athens-gray-400 dark:placeholder:text-athens-gray-500 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                                    >
                                                </div>

                                                @if ($patrimoniosDisponiveis->isNotEmpty())
                                                    <select onchange="selecionarPatrimonioEstoque('{{ $estacao->public_id }}', this)" id="select-estoque-{{ $estacao->public_id }}" class="text-xs border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg px-2.5 py-2.5 bg-white dark:bg-athens-gray-800 text-athens-gray-700 dark:text-athens-gray-200 max-w-[220px]">
                                                        <option value="">Escolher do Estoque</option>
                                                        @foreach ($patrimoniosDisponiveis as $pat)
                                                            <option value="{{ $pat->public_id }}" data-numero="{{ $pat->numero_patrimonio }}" data-mac="{{ $pat->mac_address }}">
                                                                {{ $pat->numero_patrimonio ? 'Pat: ' . $pat->numero_patrimonio . ($pat->mac_address ? ' - ' . $pat->mac_address : '') : 'MAC: ' . $pat->mac_address }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @endif

                                                <button 
                                                    type="button" 
                                                    onclick="vincularEstacao('{{ $estacao->public_id }}')" 
                                                    id="btn-vincular-{{ $estacao->public_id }}"
                                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-lg text-xs transition shadow-sm hover:shadow active:scale-98 cursor-pointer flex items-center justify-center gap-1.5 shrink-0"
                                                >
                                                    <x-heroicon-o-check-circle class="w-4 h-4" />
                                                    Ativar Estação
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Container Modo MAC Address -->
                                        <div id="container-mac-{{ $estacao->public_id }}" class="space-y-2 hidden">
                                            <div class="flex flex-col sm:flex-row gap-2.5">
                                                <div class="relative flex-1">
                                                    <input 
                                                        type="text" 
                                                        id="mac-input-{{ $estacao->public_id }}" 
                                                        placeholder="AA:BB:CC:DD:EE:FF" 
                                                        maxlength="17" 
                                                        class="w-full font-mono text-sm font-bold uppercase px-3.5 py-2.5 bg-white dark:bg-athens-gray-800 border border-athens-gray-300 dark:border-athens-gray-700 text-athens-gray-900 dark:text-athens-gray-100 placeholder:text-athens-gray-400 dark:placeholder:text-athens-gray-500 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                                    >
                                                </div>

                                                <button 
                                                    type="button" 
                                                    onclick="vincularEstacao('{{ $estacao->public_id }}')" 
                                                    id="btn-vincular-mac-{{ $estacao->public_id }}"
                                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-5 py-2.5 rounded-lg text-xs transition shadow-sm hover:shadow active:scale-98 cursor-pointer flex items-center justify-center gap-1.5 shrink-0"
                                                >
                                                    <x-heroicon-o-check-circle class="w-4 h-4" />
                                                    Ativar Estação
                                                </button>
                                            </div>
                                        </div>

                                        <p id="msg-erro-{{ $estacao->public_id }}" class="text-xs text-cinnabar-600 dark:text-cinnabar-400 font-medium hidden"></p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </main>
    </div>

    @push('scripts')
    <script>
        // Estado do modo de ativação ('patrimonio' ou 'mac')
        const modoAtivoPorEstacao = {};

        function setModoVinculacao(publicId, modo) {
            modoAtivoPorEstacao[publicId] = modo;
            const containerPatrimonio = document.getElementById(`container-patrimonio-${publicId}`);
            const containerMac = document.getElementById(`container-mac-${publicId}`);
            const tabPatrimonio = document.getElementById(`tab-patrimonio-${publicId}`);
            const tabMac = document.getElementById(`tab-mac-${publicId}`);
            const msgErro = document.getElementById(`msg-erro-${publicId}`);

            if (msgErro) msgErro.classList.add('hidden');

            if (modo === 'mac') {
                containerPatrimonio.classList.add('hidden');
                containerMac.classList.remove('hidden');

                tabMac.classList.add('bg-white', 'dark:bg-athens-gray-700', 'text-blue-dianne-950', 'dark:text-white', 'shadow-xs');
                tabMac.classList.remove('text-athens-gray-600', 'dark:text-athens-gray-400');

                tabPatrimonio.classList.remove('bg-white', 'dark:bg-athens-gray-700', 'text-blue-dianne-950', 'dark:text-white', 'shadow-xs');
                tabPatrimonio.classList.add('text-athens-gray-600', 'dark:text-athens-gray-400');
            } else {
                containerPatrimonio.classList.remove('hidden');
                containerMac.classList.add('hidden');

                tabPatrimonio.classList.add('bg-white', 'dark:bg-athens-gray-700', 'text-blue-dianne-950', 'dark:text-white', 'shadow-xs');
                tabPatrimonio.classList.remove('text-athens-gray-600', 'dark:text-athens-gray-400');

                tabMac.classList.remove('bg-white', 'dark:bg-athens-gray-700', 'text-blue-dianne-950', 'dark:text-white', 'shadow-xs');
                tabMac.classList.add('text-athens-gray-600', 'dark:text-athens-gray-400');
            }
        }
        window.setModoVinculacao = setModoVinculacao;

        function selecionarPatrimonioEstoque(publicId, selectEl) {
            const opt = selectEl.options[selectEl.selectedIndex];
            const inputPatrimonio = document.getElementById(`patrimonio-input-${publicId}`);
            const inputMac = document.getElementById(`mac-input-${publicId}`);

            if (!opt || !opt.value) {
                return;
            }

            const numPat = opt.getAttribute('data-numero');
            const mac = opt.getAttribute('data-mac');

            if (numPat && inputPatrimonio) {
                inputPatrimonio.value = numPat;
            } else if (mac && inputPatrimonio) {
                inputPatrimonio.value = mac;
            }

            if (mac && inputMac) {
                inputMac.value = mac;
            }
        }
        window.selecionarPatrimonioEstoque = selecionarPatrimonioEstoque;

        // Formatação com máscara automática do MAC Address (XX:XX:XX:XX:XX:XX)
        document.querySelectorAll('input[id^="mac-input-"]').forEach(input => {
            input.addEventListener('input', function(e) {
                let v = e.target.value.replace(/[^a-fA-F0-9]/g, '').toUpperCase();
                let formatted = '';
                for (let i = 0; i < v.length && i < 12; i++) {
                    if (i > 0 && i % 2 === 0) formatted += ':';
                    formatted += v[i];
                }
                e.target.value = formatted;
            });
        });

        // Envio assíncrono para vincular e ativar estação em campo
        function vincularEstacao(publicId) {
            const modo = modoAtivoPorEstacao[publicId] || 'patrimonio';
            const inputPatrimonio = document.getElementById(`patrimonio-input-${publicId}`);
            const inputMac = document.getElementById(`mac-input-${publicId}`);
            const selectEstoque = document.getElementById(`select-estoque-${publicId}`);
            const btn = document.getElementById(modo === 'mac' ? `btn-vincular-mac-${publicId}` : `btn-vincular-${publicId}`) || document.getElementById(`btn-vincular-${publicId}`);
            const msgErro = document.getElementById(`msg-erro-${publicId}`);

            const payload = {};

            if (modo === 'mac') {
                const mac = inputMac ? inputMac.value.trim() : '';
                if (!mac || mac.length < 17) {
                    msgErro.textContent = 'Por favor, informe um endereço MAC válido no formato AA:BB:CC:DD:EE:FF.';
                    msgErro.classList.remove('hidden');
                    return;
                }
                payload.mac_address = mac;
            } else {
                const patVal = inputPatrimonio ? inputPatrimonio.value.trim() : '';
                const selectedPatId = selectEstoque ? selectEstoque.value : '';

                if (!patVal && !selectedPatId) {
                    msgErro.textContent = 'Por favor, digite o Número de Patrimônio ou escolha um item do estoque.';
                    msgErro.classList.remove('hidden');
                    return;
                }

                if (selectedPatId) {
                    payload.patrimonio_id = selectedPatId;
                }
                if (patVal) {
                    payload.numero_patrimonio = patVal;
                }
            }

            msgErro.classList.add('hidden');
            let originalBtnHtml = '';
            if (btn) {
                originalBtnHtml = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="animate-spin inline-block mr-1">🔄</span> Ativando...';
            }

            fetch(`/instalacoes/${publicId}/vincular-mac`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(({ status, body }) => {
                if (status >= 200 && status < 300 && body.success) {
                    window.location.reload();
                } else {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = originalBtnHtml || 'Ativar Estação';
                    }
                    msgErro.textContent = body.message || 'Erro ao registrar a estação.';
                    msgErro.classList.remove('hidden');
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml || 'Ativar Estação';
                }
                msgErro.textContent = 'Erro de comunicação com o servidor. Tente novamente.';
                msgErro.classList.remove('hidden');
            });
        }
        window.vincularEstacao = vincularEstacao;
    </script>
    @endpush
</x-layouts.app>

