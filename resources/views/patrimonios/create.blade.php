<x-layouts.app title="Cadastrar Patrimônio - OpenAir Metrics">
    <div class="flex flex-col md:flex-row h-full w-full bg-athens-gray-50 dark:bg-athens-gray-950 transition-colors duration-200">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="patrimonios" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto min-h-0 p-4 sm:p-6 lg:p-8">
            <div class="max-w-4xl mx-auto space-y-6">

                <!-- Breadcrumb e Voltar -->
                <div class="flex items-center gap-2 text-sm text-athens-gray-500 dark:text-athens-gray-400">
                    <a href="{{ route('patrimonios.index') }}" class="hover:text-blue-dianne-600 dark:hover:text-blue-dianne-400 transition">Patrimônio</a>
                    <x-heroicon-o-chevron-right class="w-4 h-4" />
                    <span class="text-blue-dianne-950 dark:text-white font-medium">Novo Cadastro</span>
                </div>

                <!-- Cabeçalho -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-blue-dianne-950 dark:text-white tracking-tight">Cadastrar Equipamentos no Patrimônio</h1>
                        <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mt-1">Registre os sensores e placas adquiridos para ficarem disponíveis para os instaladores em campo.</p>
                    </div>
                </div>

                <!-- Seletor de Modo (Unitário vs Lote) -->
                <div class="bg-white dark:bg-athens-gray-900 p-2 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 flex gap-2">
                    <button type="button" id="tab-unitario-btn" onclick="alternarAba('unitario')" class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all cursor-pointer bg-blue-dianne-600 text-white shadow-sm">
                        <x-heroicon-o-cpu-chip class="w-4 h-4" />
                        Cadastro Individual
                    </button>
                    <button type="button" id="tab-lote-btn" onclick="alternarAba('lote')" class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all cursor-pointer bg-transparent text-athens-gray-600 dark:text-athens-gray-300 hover:text-blue-dianne-900 dark:hover:text-white hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800">
                        <x-heroicon-o-queue-list class="w-4 h-4" />
                        Importação em Massa / Lote
                    </button>
                </div>

                <!-- Formulário 1: Cadastro Individual -->
                <div id="painel-unitario" class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-6">
                    <form action="{{ route('patrimonios.store') }}" method="POST" class="space-y-5">
                        @csrf

                        <div class="bg-blue-dianne-50 dark:bg-blue-dianne-950/40 border border-blue-dianne-200 dark:border-blue-dianne-800 p-4 rounded-lg flex items-start gap-3">
                            <x-heroicon-o-information-circle class="w-5 h-5 text-blue-dianne-600 dark:text-blue-dianne-400 shrink-0 mt-0.5" />
                            <div class="text-xs text-blue-dianne-900 dark:text-blue-dianne-200 space-y-1">
                                <p class="font-bold">Regra de Estoque e Instalação:</p>
                                <p>O equipamento é cadastrado com status inicial <strong class="text-emerald-700 dark:text-emerald-300">Disponível</strong> para instalação. Assim que for vinculado e ativado na Ordem de Instalação, seu status será alterado automaticamente para <strong class="text-dodger-blue-700 dark:text-dodger-blue-300">Instalada</strong>.</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- MAC Address -->
                            <div>
                                <label for="mac_address" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                    Endereço MAC da Placa <span class="text-cinnabar-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="mac_address" 
                                    name="mac_address" 
                                    value="{{ old('mac_address') }}" 
                                    placeholder="AA:BB:CC:DD:EE:FF" 
                                    maxlength="17"
                                    required
                                    class="w-full px-4 py-2.5 font-mono text-sm bg-white dark:bg-athens-gray-800 border @error('mac_address') border-cinnabar-500 ring-1 ring-cinnabar-500 @else border-athens-gray-300 dark:border-athens-gray-700 @enderror text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                                @error('mac_address')
                                    <p class="text-xs text-cinnabar-600 dark:text-cinnabar-400 mt-1 font-medium">{{ $message }}</p>
                                @else
                                    <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mt-1">Formato hexadecimal com dois pontos (ex: AA:BB:CC:DD:EE:FF).</p>
                                @enderror
                            </div>

                            <!-- Município e Código Automático -->
                            <div>
                                @if (isset($cidadeUsuario) && $cidadeUsuario)
                                    <div class="mb-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg p-3 text-xs text-emerald-900 dark:text-emerald-200 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-o-building-office-2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                            <span>Jurisdição Municipal: <strong>{{ $cidadeUsuario->nome }} ({{ $cidadeUsuario->estado->uf ?? '' }})</strong></span>
                                        </div>
                                        <span class="text-[10px] bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 font-bold px-2 py-0.5 rounded">Fixa</span>
                                    </div>
                                    <input type="hidden" name="cidade_id" value="{{ $cidadeUsuario->id }}">
                                @endif

                                <label for="cidade_id" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                    Município <span class="text-cinnabar-500">*</span>
                                </label>
                                <select 
                                    id="cidade_id" 
                                    @if (!isset($cidadeUsuario) || !$cidadeUsuario) name="cidade_id" @endif
                                    @disabled(isset($cidadeUsuario) && $cidadeUsuario)
                                    required
                                    class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 border @error('cidade_id') border-cinnabar-500 ring-1 ring-cinnabar-500 @else border-athens-gray-300 dark:border-athens-gray-700 @enderror text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    @if (!isset($cidadeUsuario) || !$cidadeUsuario)
                                        <option value="">Selecione o município...</option>
                                    @endif
                                    @foreach($cidades as $cid)
                                        <option value="{{ $cid->id }}" @selected(old('cidade_id', $cidadeUsuario?->id) == $cid->id)>
                                            {{ $cid->nome }} ({{ $cid->estado->uf ?? 'UF' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('cidade_id')
                                    <p class="text-xs text-cinnabar-600 dark:text-cinnabar-400 mt-1 font-medium">{{ $message }}</p>
                                @else
                                    <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mt-1">Código gerado automaticamente: <strong class="font-mono text-blue-dianne-800 dark:text-blue-dianne-300">OAir-Estacao-&lt;IdCidade&gt;-&lt;Num&gt;</strong></p>
                                @enderror
                            </div>
                        </div>

                        <!-- Data de Aquisição -->
                        <div>
                            <label for="data_aquisicao" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                Data de Aquisição
                            </label>
                            <input 
                                type="date" 
                                id="data_aquisicao" 
                                name="data_aquisicao" 
                                value="{{ old('data_aquisicao', date('Y-m-d')) }}" 
                                class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 border border-athens-gray-300 dark:border-athens-gray-700 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                            >
                            <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mt-1">Data de aquisição ou entrada do equipamento no estoque (padrão: hoje).</p>
                        </div>

                        <!-- Observações -->
                        <div>
                            <label for="observacoes" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                Observações Adicionais
                            </label>
                            <textarea 
                                id="observacoes" 
                                name="observacoes" 
                                rows="2" 
                                placeholder="Notas sobre lote de compra, fornecedor, revisão de hardware..."
                                class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 border border-athens-gray-300 dark:border-athens-gray-700 text-athens-gray-900 dark:text-athens-gray-100 placeholder:text-athens-gray-400 dark:placeholder:text-athens-gray-500 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                            >{{ old('observacoes') }}</textarea>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-athens-gray-200 dark:border-athens-gray-800">
                            <a href="{{ route('patrimonios.index') }}" class="px-5 py-2.5 text-sm font-semibold text-athens-gray-700 dark:text-athens-gray-300 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 rounded-lg transition">
                                Cancelar
                            </a>
                            <button type="submit" class="bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold py-2.5 px-6 rounded-lg transition-all shadow-sm hover:shadow text-sm cursor-pointer">
                                Cadastrar no Estoque
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Formulário 2: Importação em Lote -->
                <div id="painel-lote" class="bg-white dark:bg-athens-gray-900 rounded-xl shadow-sm border border-athens-gray-200 dark:border-athens-gray-800 p-6 hidden">
                    <form action="{{ route('patrimonios.store-batch') }}" method="POST" class="space-y-5">
                        @csrf

                        <div class="bg-dodger-blue-50 dark:bg-dodger-blue-950/40 border border-dodger-blue-200 dark:border-dodger-blue-800 p-4 rounded-lg flex items-start gap-3">
                            <x-heroicon-o-information-circle class="w-5 h-5 text-dodger-blue-600 dark:text-dodger-blue-400 shrink-0 mt-0.5" />
                            <div class="text-xs text-dodger-blue-900 dark:text-dodger-blue-200 space-y-1">
                                <p class="font-bold">Como funciona a importação em massa:</p>
                                <p>Cole abaixo uma lista de endereços MAC (um por linha). O sistema irá formatar, validar e descartar duplicados automaticamente.</p>
                            </div>
                        </div>

                        <!-- Município do Lote -->
                        <div>
                            @if (isset($cidadeUsuario) && $cidadeUsuario)
                                <div class="mb-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-lg p-3 text-xs text-emerald-900 dark:text-emerald-200 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <x-heroicon-o-building-office-2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                        <span>Jurisdição Municipal: <strong>{{ $cidadeUsuario->nome }} ({{ $cidadeUsuario->estado->uf ?? '' }})</strong></span>
                                    </div>
                                    <span class="text-[10px] bg-emerald-100 dark:bg-emerald-900 text-emerald-800 dark:text-emerald-200 font-bold px-2 py-0.5 rounded">Fixa</span>
                                </div>
                                <input type="hidden" name="cidade_id" value="{{ $cidadeUsuario->id }}">
                            @endif

                            <label for="cidade_id_batch" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                Município de Destino do Lote <span class="text-cinnabar-500">*</span>
                            </label>
                            <select 
                                id="cidade_id_batch" 
                                @if (!isset($cidadeUsuario) || !$cidadeUsuario) name="cidade_id" @endif
                                @disabled(isset($cidadeUsuario) && $cidadeUsuario)
                                required
                                class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 border border-athens-gray-300 dark:border-athens-gray-700 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                @if (!isset($cidadeUsuario) || !$cidadeUsuario)
                                    <option value="">Selecione o município...</option>
                                @endif
                                @foreach($cidades as $cid)
                                    <option value="{{ $cid->id }}" @selected(old('cidade_id', $cidadeUsuario?->id) == $cid->id)>
                                        {{ $cid->nome }} ({{ $cid->estado->uf ?? 'UF' }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 mt-1">Cada placa receberá um código sequencial no padrão <strong class="font-mono text-blue-dianne-800 dark:text-blue-dianne-300">OAir-Estacao-&lt;IdCidade&gt;-&lt;Num&gt;</strong>.</p>
                        </div>

                        <!-- Lista de MACs -->
                        <div>
                            <label for="mac_addresses_batch" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                Lista de MAC Addresses (1 por linha) <span class="text-cinnabar-500">*</span>
                            </label>
                            <textarea 
                                id="mac_addresses_batch" 
                                name="mac_addresses_batch" 
                                rows="8" 
                                required
                                placeholder="AA:BB:CC:DD:EE:01&#10;AA:BB:CC:DD:EE:02&#10;AA-BB-CC-DD-EE-03&#10;AABBCCDDEE04"
                                class="w-full font-mono text-xs px-4 py-3 bg-white dark:bg-athens-gray-800 border border-athens-gray-300 dark:border-athens-gray-700 text-athens-gray-900 dark:text-athens-gray-100 placeholder:text-athens-gray-400 dark:placeholder:text-athens-gray-500 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                            >{{ old('mac_addresses_batch') }}</textarea>
                        </div>

                        <!-- Data de Aquisição -->
                        <div>
                            <label for="data_aquisicao_batch" class="block text-sm font-semibold text-blue-dianne-950 dark:text-athens-gray-200 mb-1.5">
                                Data de Aquisição do Lote
                            </label>
                            <input 
                                type="date" 
                                id="data_aquisicao_batch" 
                                name="data_aquisicao" 
                                value="{{ date('Y-m-d') }}" 
                                class="w-full px-4 py-2.5 text-sm bg-white dark:bg-athens-gray-800 border border-athens-gray-300 dark:border-athens-gray-700 text-athens-gray-900 dark:text-athens-gray-100 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                            >
                        </div>

                        <!-- Botões de Ação -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-athens-gray-200 dark:border-athens-gray-800">
                            <a href="{{ route('patrimonios.index') }}" class="px-5 py-2.5 text-sm font-semibold text-athens-gray-700 dark:text-athens-gray-300 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 rounded-lg transition">
                                Cancelar
                            </a>
                            <button type="submit" class="bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold py-2.5 px-6 rounded-lg transition-all shadow-sm hover:shadow text-sm cursor-pointer">
                                Importar Lote de Placas
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </main>
    </div>

    @push('scripts')
    <script>
        function alternarAba(aba) {
            const btnUnitario = document.getElementById('tab-unitario-btn');
            const btnLote = document.getElementById('tab-lote-btn');
            const painelUnitario = document.getElementById('painel-unitario');
            const painelLote = document.getElementById('painel-lote');

            if (aba === 'unitario') {
                btnUnitario.className = 'flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all cursor-pointer bg-blue-dianne-600 text-white shadow-sm';
                btnLote.className = 'flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all cursor-pointer bg-transparent text-athens-gray-600 dark:text-athens-gray-300 hover:text-blue-dianne-900 dark:hover:text-white hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800';
                painelUnitario.classList.remove('hidden');
                painelLote.classList.add('hidden');
            } else {
                btnLote.className = 'flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all cursor-pointer bg-blue-dianne-600 text-white shadow-sm';
                btnUnitario.className = 'flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all cursor-pointer bg-transparent text-athens-gray-600 dark:text-athens-gray-300 hover:text-blue-dianne-900 dark:hover:text-white hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800';
                painelLote.classList.remove('hidden');
                painelUnitario.classList.add('hidden');
            }
        }

        // Máscara automática de MAC Address
        const inputMac = document.getElementById('mac_address');
        if (inputMac) {
            inputMac.addEventListener('input', function (e) {
                let v = e.target.value.replace(/[^a-fA-F0-9]/g, '').toUpperCase();
                let formatted = '';
                for (let i = 0; i < v.length && i < 12; i++) {
                    if (i > 0 && i % 2 === 0) formatted += ':';
                    formatted += v[i];
                }
                e.target.value = formatted;
            });
        }
    </script>
    @endpush
</x-layouts.app>

