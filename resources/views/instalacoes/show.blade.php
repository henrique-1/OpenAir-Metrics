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
                            $temSubstituicaoPendente = (bool) $estacao->solicitacao_substituicao;
                            $isInstalada = $estacao->status_instalacao === 'Instalada' && ! empty($estacao->mac_address) && ! $temSubstituicaoPendente;
                            $isMatriz = $estacao->tipo_estacao === 'Estação Matriz';
                            $origem = $isMatriz ? null : $estacao->estacaoOrigem;
                            $origemInstalada = $isMatriz || ($origem && $origem->status_instalacao === 'Instalada' && ! empty($origem->mac_address));
                            
                            // Uma estação pendente de primeira instalação fica bloqueada se a sua estação de origem ainda não foi instalada
                            // Estações com substituição pendente já estão liberadas diretamente para execução
                            $bloqueada = ! $isInstalada && ! $origemInstalada && ! $temSubstituicaoPendente;

                            $isProxima = false;
                            if (! $isInstalada && ($origemInstalada || $temSubstituicaoPendente) && ! $primeiraPendenteDefinida) {
                                $isProxima = true;
                                $primeiraPendenteDefinida = true;
                            }
                        @endphp

                        <div id="card-estacao-{{ $estacao->public_id }}" class="rounded-xl shadow-sm border {{ $temSubstituicaoPendente ? 'border-amber-300 dark:border-amber-700 bg-amber-50/20 dark:bg-amber-950/20 ring-2 ring-amber-200/50 dark:ring-amber-950/60' : ($isInstalada ? 'border-emerald-200 dark:border-emerald-800 bg-emerald-50/20 dark:bg-emerald-950/20' : ($bloqueada ? 'border-athens-gray-200 dark:border-athens-gray-800 opacity-70 bg-athens-gray-50/60 dark:bg-athens-gray-900/40' : ($isProxima ? 'bg-white dark:bg-athens-gray-900 border-blue-dianne-300 dark:border-blue-dianne-700 ring-2 ring-blue-dianne-100 dark:ring-blue-dianne-950/60' : 'bg-white dark:bg-athens-gray-900 border-dodger-blue-300 dark:border-dodger-blue-700'))) }} p-6 transition-all">
                            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">

                                <!-- Identificação e Número do Passo -->
                                <div class="flex items-start gap-3.5">
                                    <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center font-bold text-base {{ $temSubstituicaoPendente ? 'bg-amber-500 text-white' : ($isInstalada ? 'bg-emerald-600 text-white' : ($isMatriz ? 'bg-blue-dianne-600 text-white' : 'bg-spindle-600 text-white')) }} shadow-sm">
                                        @if ($temSubstituicaoPendente)
                                            <x-heroicon-o-arrow-path class="w-6 h-6 stroke-[2.5]" />
                                        @elseif ($isInstalada)
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

                                            @if ($temSubstituicaoPendente)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-200 border border-amber-300 dark:border-amber-700 animate-pulse">
                                                    <x-heroicon-o-arrow-path class="w-3.5 h-3.5" />
                                                    Ordem de Substituição Aberta
                                                </span>
                                            @elseif ($isInstalada)
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
                                    <!-- Formulário de Ativação / Substituição via Dropdown de Patrimônio/MAC -->
                                    <div class="bg-blue-dianne-50/60 dark:bg-blue-dianne-950/40 border border-blue-dianne-200 dark:border-blue-dianne-800 p-4 rounded-xl space-y-3">
                                        @if ($temSubstituicaoPendente)
                                            <div class="p-3 rounded-lg bg-amber-100/70 dark:bg-amber-950/60 border border-amber-300 dark:border-amber-700 text-xs text-amber-900 dark:text-amber-200 flex flex-col gap-1">
                                                <div class="flex items-center gap-1.5 font-bold text-amber-900 dark:text-amber-100">
                                                    <x-heroicon-o-arrow-path class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                                                    Ordem de Substituição de Sensor em Aberto
                                                </div>
                                                <p class="text-amber-800 dark:text-amber-300">
                                                    Equipamento anterior: <strong>{{ $estacao->patrimonio?->numero_patrimonio ? 'Patrimônio #' . $estacao->patrimonio->numero_patrimonio : 'MAC ' . ($estacao->mac_address ?? 'S/N') }}</strong>. Ao vincular a nova placa, este item anterior será automaticamente marcado como <strong>Descartado</strong>.
                                                </p>
                                                @if($estacao->motivo_substituicao)
                                                    <p class="text-[11px] text-amber-700 dark:text-amber-400 italic mt-0.5">Motivo: {{ $estacao->motivo_substituicao }}</p>
                                                @endif
                                            </div>
                                        @endif

                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                            <span class="text-xs font-bold text-blue-dianne-950 dark:text-white uppercase tracking-wide flex items-center gap-1.5">
                                                <x-heroicon-o-qr-code class="w-4 h-4 text-blue-dianne-600 dark:text-blue-dianne-400" />
                                                {{ $temSubstituicaoPendente ? 'Executar Substituição:' : 'Ativação em Campo:' }}
                                            </span>
                                            <span class="text-[11px] text-athens-gray-500 dark:text-athens-gray-400">
                                                {{ $temSubstituicaoPendente ? 'Selecione o novo equipamento para substituir o sensor antigo' : 'Selecione o equipamento disponível no estoque municipal' }}
                                            </span>
                                        </div>

                                        <div class="space-y-2">
                                            <div class="flex flex-col sm:flex-row gap-2.5">
                                                <div class="relative flex-1">
                                                    <select 
                                                        id="select-patrimonio-{{ $estacao->public_id }}" 
                                                        class="w-full text-xs sm:text-sm font-semibold px-3.5 py-2.5 bg-white dark:bg-athens-gray-800 border border-athens-gray-300 dark:border-athens-gray-700 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition cursor-pointer"
                                                        @disabled($patrimoniosDisponiveis->isEmpty())
                                                    >
                                                        <option value="">{{ $temSubstituicaoPendente ? 'Selecione o novo equipamento (Patrimônio / MAC)...' : 'Selecione o equipamento (Patrimônio / MAC)...' }}</option>
                                                        @foreach ($patrimoniosDisponiveis as $pat)
                                                            <option value="{{ $pat->public_id }}">
                                                                Patrimônio: {{ $pat->numero_patrimonio ?? 'S/N' }}{{ $pat->mac_address ? ' — MAC: ' . $pat->mac_address : '' }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <button 
                                                    type="button" 
                                                    onclick="vincularEstacao('{{ $estacao->public_id }}')" 
                                                    id="btn-vincular-{{ $estacao->public_id }}"
                                                    @disabled($patrimoniosDisponiveis->isEmpty())
                                                    class="{{ $temSubstituicaoPendente ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-600 hover:bg-emerald-700' }} disabled:bg-athens-gray-300 dark:disabled:bg-athens-gray-700 disabled:cursor-not-allowed text-white font-bold px-5 py-2.5 rounded-lg text-xs transition shadow-sm hover:shadow active:scale-98 cursor-pointer flex items-center justify-center gap-1.5 shrink-0"
                                                >
                                                    @if($temSubstituicaoPendente)
                                                        <x-heroicon-o-arrow-path class="w-4 h-4" />
                                                        Concluir Substituição
                                                    @else
                                                        <x-heroicon-o-check-circle class="w-4 h-4" />
                                                        Ativar Estação
                                                    @endif
                                                </button>
                                            </div>

                                            @if ($patrimoniosDisponiveis->isEmpty())
                                                <p class="text-xs text-amber-600 dark:text-amber-400 font-medium">
                                                    Nenhum equipamento disponível no estoque municipal. Cadastre novas placas no módulo de Patrimônio para liberá-las para instalação.
                                                </p>
                                            @endif

                                            <p id="msg-erro-{{ $estacao->public_id }}" class="text-xs text-cinnabar-600 dark:text-cinnabar-400 font-medium hidden"></p>
                                        </div>
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
        // Envio assíncrono para vincular e ativar estação em campo via seleção do dropdown de Patrimônio
        function vincularEstacao(publicId) {
            const select = document.getElementById(`select-patrimonio-${publicId}`);
            const btn = document.getElementById(`btn-vincular-${publicId}`);
            const msgErro = document.getElementById(`msg-erro-${publicId}`);

            if (msgErro) {
                msgErro.classList.add('hidden');
                msgErro.textContent = '';
            }

            const selectedPatId = select ? select.value.trim() : '';

            if (!selectedPatId) {
                if (msgErro) {
                    msgErro.textContent = 'Por favor, selecione um equipamento da lista para ativar a estação.';
                    msgErro.classList.remove('hidden');
                }
                return;
            }

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
                body: JSON.stringify({
                    patrimonio_id: selectedPatId
                })
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
                    if (msgErro) {
                        msgErro.textContent = body.message || 'Erro ao registrar a estação.';
                        msgErro.classList.remove('hidden');
                    }
                }
            })
            .catch(err => {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalBtnHtml || 'Ativar Estação';
                }
                if (msgErro) {
                    msgErro.textContent = 'Erro de comunicação com o servidor. Tente novamente.';
                    msgErro.classList.remove('hidden');
                }
            });
        }
        window.vincularEstacao = vincularEstacao;
    </script>
    @endpush
</x-layouts.app>

