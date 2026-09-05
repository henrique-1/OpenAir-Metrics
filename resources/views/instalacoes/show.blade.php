<x-layouts.app title="Ordem de Instalação #{{ $matriz->ordem_instalacao ?? 1 }} - OpenAir Metrics">
    <div class="flex h-full w-full bg-athens-gray-50">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="instalacoes" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto p-6 lg:p-8">
            <div class="max-w-5xl mx-auto space-y-6">

                <!-- Breadcrumb e Voltar -->
                <div class="flex items-center gap-2 text-sm text-athens-gray-500">
                    <a href="{{ route('instalacoes.index') }}" class="hover:text-blue-dianne-600 transition">Ordens de Instalação</a>
                    <x-heroicon-o-chevron-right class="w-4 h-4" />
                    <span class="text-blue-dianne-950 font-medium">Cluster {{ $matriz->bairro_nome ?? $matriz->bairro?->nome }}</span>
                </div>

                <!-- Cabeçalho da Ordem de Instalação -->
                <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-2xl font-bold text-blue-dianne-950 tracking-tight">Roteiro de Instalação em Campo</h1>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-dianne-50 text-blue-dianne-700 border border-blue-dianne-200">
                                {{ $estacoes->count() }} Estações na Malha
                            </span>
                        </div>
                        <p class="text-sm text-athens-gray-600 mt-1">
                            Região: <strong>{{ $matriz->bairro_nome ?? $matriz->bairro?->nome }}</strong> ({{ $matriz->cidade_nome ?? $matriz->bairro?->cidade?->nome }} - {{ $matriz->estado_uf ?? $matriz->bairro?->cidade?->estado?->uf }}).
                            Siga a ordem estrita dos passos para garantir o emparelhamento da rede.
                        </p>
                    </div>

                    <a href="{{ route('instalacoes.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-athens-gray-700 bg-athens-gray-100 hover:bg-athens-gray-200 px-4 py-2.5 rounded-lg transition shrink-0">
                        <x-heroicon-o-arrow-left class="w-4 h-4" />
                        Voltar para a Lista
                    </a>
                </div>

                <!-- Lista Passo a Passo Sequencial -->
                <div class="space-y-4">
                    @php
                        $podeInstalarProxima = true;
                    @endphp

                    @foreach ($estacoes as $index => $estacao)
                        @php
                            $isInstalada = $estacao->status_instalacao === 'Instalada' && ! empty($estacao->mac_address);
                            $isMatriz = $estacao->tipo_estacao === 'Estação Matriz';
                            $bloqueada = ! $isInstalada && ! $podeInstalarProxima;
                            
                            if (! $isInstalada) {
                                // A partir da primeira não instalada, as seguintes ficam bloqueadas
                                $podeInstalarProxima = false;
                            }
                        @endphp

                        <div id="card-estacao-{{ $estacao->public_id }}" class="bg-white rounded-xl shadow-sm border {{ $isInstalada ? 'border-emerald-200 bg-emerald-50/20' : ($bloqueada ? 'border-athens-gray-200 opacity-70 bg-athens-gray-50/60' : 'border-blue-dianne-300 ring-2 ring-blue-dianne-100') }} p-6 transition-all">
                            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">

                                <!-- Identificação e Número do Passo -->
                                <div class="flex items-start gap-3.5">
                                    <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center font-bold text-base {{ $isInstalada ? 'bg-emerald-600 text-white' : ($isMatriz ? 'bg-blue-dianne-600 text-white' : 'bg-purple-600 text-white') }} shadow-sm">
                                        @if ($isInstalada)
                                            <x-heroicon-o-check class="w-6 h-6 stroke-[2.5]" />
                                        @else
                                            #{{ $estacao->ordem_instalacao ?? ($index + 1) }}
                                        @endif
                                    </div>

                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h2 class="text-base font-bold text-blue-dianne-950">
                                                Passo #{{ $estacao->ordem_instalacao ?? ($index + 1) }}: {{ $estacao->tipo_estacao }}
                                            </h2>

                                            @if ($isInstalada)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                                    Ativa e Instalada
                                                </span>
                                            @elseif ($bloqueada)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-athens-gray-200 text-athens-gray-600">
                                                    <x-heroicon-o-lock-closed class="w-3.5 h-3.5" />
                                                    Aguardando Estação Anterior
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300 animate-pulse">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                                    Próxima a Instalar
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Endereço e Detalhes de Localização -->
                                        <p class="text-sm font-semibold text-blue-dianne-900 flex items-center gap-1.5">
                                            <x-heroicon-o-map-pin class="w-4 h-4 text-cinnabar-500 shrink-0" />
                                            {{ $estacao->endereco_completo ?: ($estacao->logradouro ? $estacao->logradouro . ', ' . ($estacao->numero ?? 'S/N') . ' - ' . $estacao->bairro_nome : 'Localização georreferenciada') }}
                                        </p>

                                        <p class="text-xs text-athens-gray-500 font-mono">
                                            Lat: {{ number_format($estacao->latitude, 5) }}, Lng: {{ number_format($estacao->longitude, 5) }}
                                            @if (! $isMatriz && $estacao->distancia_origem_metros)
                                                • <span class="text-indigo-600 font-sans font-medium">Conexão a {{ round($estacao->distancia_origem_metros) }}m da estação anterior</span>
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
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $mapsQuery }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-xs font-bold text-dodger-blue-700 bg-dodger-blue-50 hover:bg-dodger-blue-100 border border-dodger-blue-200 px-3 py-2 rounded-lg transition shadow-sm">
                                        <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
                                        Abrir no GPS / Waze
                                    </a>
                                </div>
                            </div>

                            <!-- Área de Ação do Instalador / Vinculação de Patrimônio ou MAC -->
                            <div class="mt-4 pt-4 border-t border-athens-gray-200/80">
                                @if ($isInstalada)
                                    <!-- Informações da Instalação Concluída -->
                                    <div class="flex flex-wrap items-center justify-between gap-3 bg-emerald-50/80 border border-emerald-200 p-3.5 rounded-lg text-xs text-emerald-900">
                                        <div class="flex flex-wrap items-center gap-3">
                                            @if ($estacao->patrimonio?->numero_patrimonio)
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-bold">Patrimônio:</span>
                                                    <span class="font-semibold bg-white px-2 py-0.5 rounded border border-emerald-300 text-emerald-900">
                                                        #{{ $estacao->patrimonio->numero_patrimonio }}
                                                    </span>
                                                </div>
                                            @endif
                                            @if ($estacao->mac_address)
                                                <div class="flex items-center gap-1.5">
                                                    <span class="font-bold">MAC Address:</span>
                                                    <span class="font-mono font-bold bg-white px-2.5 py-1 rounded border border-emerald-300 text-emerald-800 text-sm">
                                                        {{ $estacao->mac_address }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-athens-gray-600">
                                            Instalado em <strong>{{ $estacao->data_instalacao?->format('d/m/Y \à\s H:i') ?? 'Hoje' }}</strong>
                                            @if ($estacao->instalador)
                                                por <strong>{{ $estacao->instalador->name }}</strong>
                                            @endif
                                        </div>
                                    </div>
                                @elseif ($bloqueada)
                                    <div class="bg-athens-gray-100 border border-athens-gray-200 p-3.5 rounded-lg text-xs text-athens-gray-500 flex items-center gap-2">
                                        <x-heroicon-o-lock-closed class="w-4 h-4 shrink-0 text-athens-gray-400" />
                                        <span>Instale e ative primeiro a estação anterior da malha para liberar a vinculação desta.</span>
                                    </div>
                                @else
                                    <!-- Formulário Interativo de Ativação (Patrimônio ou MAC) -->
                                    <div class="bg-blue-dianne-50/60 border border-blue-dianne-200 p-4 rounded-xl space-y-3">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-xs font-bold text-blue-dianne-950 uppercase tracking-wide flex items-center gap-1.5">
                                                    <x-heroicon-o-qr-code class="w-4 h-4 text-blue-dianne-600" />
                                                    Registrar Estação:
                                                </span>
                                                <!-- Seletor de Modo: Patrimônio ou MAC -->
                                                <div class="inline-flex p-0.5 bg-athens-gray-200/80 rounded-lg text-xs font-semibold">
                                                    <button type="button" onclick="setModoVinculacao('{{ $estacao->public_id }}', 'patrimonio')" id="tab-patrimonio-{{ $estacao->public_id }}" class="px-2.5 py-1 rounded-md transition bg-white text-blue-dianne-950 shadow-xs cursor-pointer">
                                                        Por Patrimônio
                                                    </button>
                                                    <button type="button" onclick="setModoVinculacao('{{ $estacao->public_id }}', 'mac')" id="tab-mac-{{ $estacao->public_id }}" class="px-2.5 py-1 rounded-md transition text-athens-gray-600 hover:text-blue-dianne-950 cursor-pointer">
                                                        Por MAC Address
                                                    </button>
                                                </div>
                                            </div>
                                            <span class="text-[11px] text-athens-gray-500">Informe o tombamento/patrimônio ou endereço MAC</span>
                                        </div>

                                        <!-- Container Modo Patrimônio -->
                                        <div id="container-patrimonio-{{ $estacao->public_id }}" class="space-y-2">
                                            <div class="flex flex-col sm:flex-row gap-2.5">
                                                <div class="relative flex-1">
                                                    <input 
                                                        type="text" 
                                                        id="patrimonio-input-{{ $estacao->public_id }}" 
                                                        placeholder="Digite o Nº do Patrimônio (ex: PAT-00123)" 
                                                        class="w-full text-sm font-semibold px-3.5 py-2.5 bg-white border border-athens-gray-300 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                                    >
                                                </div>

                                                @if ($patrimoniosDisponiveis->isNotEmpty())
                                                    <select onchange="selecionarPatrimonioEstoque('{{ $estacao->public_id }}', this)" id="select-estoque-{{ $estacao->public_id }}" class="text-xs border border-athens-gray-300 rounded-lg px-2.5 py-2.5 bg-white text-athens-gray-700 max-w-[220px]">
                                                        <option value="">Escolher do Estoque</option>
                                                        @foreach ($patrimoniosDisponiveis as $pat)
                                                            <option value="{{ $pat->public_id }}" data-numero="{{ $pat->numero_patrimonio }}" data-mac="{{ $pat->mac_address }}">
                                                                {{ $pat->numero_patrimonio ? 'Pat: ' . $pat->numero_patrimonio : 'MAC: ' . $pat->mac_address }} ({{ $pat->tipo_sugerido ?: 'Disponível' }})
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
                                                        class="w-full font-mono text-sm font-bold uppercase px-3.5 py-2.5 bg-white border border-athens-gray-300 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
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

                                        <p id="msg-erro-{{ $estacao->public_id }}" class="text-xs text-cinnabar-600 font-medium hidden"></p>
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

                tabMac.classList.add('bg-white', 'text-blue-dianne-950', 'shadow-xs');
                tabMac.classList.remove('text-athens-gray-600');

                tabPatrimonio.classList.remove('bg-white', 'text-blue-dianne-950', 'shadow-xs');
                tabPatrimonio.classList.add('text-athens-gray-600');
            } else {
                containerPatrimonio.classList.remove('hidden');
                containerMac.classList.add('hidden');

                tabPatrimonio.classList.add('bg-white', 'text-blue-dianne-950', 'shadow-xs');
                tabPatrimonio.classList.remove('text-athens-gray-600');

                tabMac.classList.remove('bg-white', 'text-blue-dianne-950', 'shadow-xs');
                tabMac.classList.add('text-athens-gray-600');
            }
        }

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
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="animate-spin inline-block mr-1">🔄</span> Ativando...';
            }

            fetch(`/api/instalacoes/${publicId}/vincular-mac`, {
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
                        btn.innerHTML = '<x-heroicon-o-check-circle class="w-4 h-4 inline mr-1" /> Ativar Estação';
                    }
                    msgErro.textContent = body.message || 'Erro ao registrar a estação.';
                    msgErro.classList.remove('hidden');
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<x-heroicon-o-check-circle class="w-4 h-4 inline mr-1" /> Ativar Estação';
                }
                msgErro.textContent = 'Erro de comunicação com o servidor. Tente novamente.';
                msgErro.classList.remove('hidden');
            });
        }
    </script>
    @endpush
</x-layouts.app>

