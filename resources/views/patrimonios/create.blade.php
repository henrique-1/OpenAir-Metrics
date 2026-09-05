<x-layouts.app title="Cadastrar Patrimônio - OpenAir Metrics">
    <div class="flex h-full w-full bg-athens-gray-50">
        <!-- Sidebar de Navegação -->
        <x-sidebar active="patrimonios" />

        <!-- Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto p-6 lg:p-8">
            <div class="max-w-4xl mx-auto space-y-6">

                <!-- Breadcrumb e Voltar -->
                <div class="flex items-center gap-2 text-sm text-athens-gray-500">
                    <a href="{{ route('patrimonios.index') }}" class="hover:text-blue-dianne-600 transition">Patrimônio</a>
                    <x-heroicon-o-chevron-right class="w-4 h-4" />
                    <span class="text-blue-dianne-950 font-medium">Novo Cadastro</span>
                </div>

                <!-- Cabeçalho -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-blue-dianne-950 tracking-tight">Cadastrar Equipamentos no Patrimônio</h1>
                        <p class="text-sm text-athens-gray-600 mt-1">Registre os sensores e placas adquiridos para ficarem disponíveis para os instaladores em campo.</p>
                    </div>
                </div>

                <!-- Seletor de Modo (Unitário vs Lote) -->
                <div class="bg-white p-2 rounded-xl shadow-sm border border-athens-gray-200 flex gap-2">
                    <button type="button" id="tab-unitario-btn" onclick="alternarAba('unitario')" class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all cursor-pointer bg-blue-dianne-600 text-white shadow-sm">
                        <x-heroicon-o-cpu-chip class="w-4 h-4" />
                        Cadastro Individual
                    </button>
                    <button type="button" id="tab-lote-btn" onclick="alternarAba('lote')" class="flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all cursor-pointer bg-transparent text-athens-gray-600 hover:text-blue-dianne-900 hover:bg-athens-gray-100">
                        <x-heroicon-o-queue-list class="w-4 h-4" />
                        Importação em Massa / Lote
                    </button>
                </div>

                <!-- Formulário 1: Cadastro Individual -->
                <div id="painel-unitario" class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-6">
                    <form action="{{ route('patrimonios.store') }}" method="POST" class="space-y-5">
                        @csrf

                        <!-- MAC Address -->
                        <div>
                            <label for="mac_address" class="block text-sm font-semibold text-blue-dianne-950 mb-1.5">
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
                                class="w-full px-4 py-2.5 font-mono text-sm border @error('mac_address') border-cinnabar-500 ring-1 ring-cinnabar-500 @else border-athens-gray-300 @enderror rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                            >
                            @error('mac_address')
                                <p class="text-xs text-cinnabar-600 mt-1 font-medium">{{ $message }}</p>
                            @else
                                <p class="text-xs text-athens-gray-500 mt-1">Formato hexadecimal de 12 dígitos com dois pontos (ex: AA:BB:CC:DD:EE:FF).</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Número de Patrimônio / Etiqueta -->
                            <div>
                                <label for="numero_patrimonio" class="block text-sm font-semibold text-blue-dianne-950 mb-1.5">
                                    Nº de Patrimônio / Tombo
                                </label>
                                <input 
                                    type="text" 
                                    id="numero_patrimonio" 
                                    name="numero_patrimonio" 
                                    value="{{ old('numero_patrimonio') }}" 
                                    placeholder="Ex: PAT-2026-001" 
                                    class="w-full px-4 py-2.5 text-sm border border-athens-gray-300 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                            </div>

                            <!-- Tipo Sugerido -->
                            <div>
                                <label for="tipo_sugerido" class="block text-sm font-semibold text-blue-dianne-950 mb-1.5">
                                    Tipo Sugerido <span class="text-cinnabar-500">*</span>
                                </label>
                                <select 
                                    id="tipo_sugerido" 
                                    name="tipo_sugerido" 
                                    required
                                    class="w-full px-4 py-2.5 text-sm border border-athens-gray-300 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                                    <option value="Indefinido" {{ old('tipo_sugerido') === 'Indefinido' ? 'selected' : '' }}>Indefinido / Qualquer Estação</option>
                                    <option value="Estação Matriz" {{ old('tipo_sugerido') === 'Estação Matriz' ? 'selected' : '' }}>Estação Matriz</option>
                                    <option value="Estação Satélite" {{ old('tipo_sugerido') === 'Estação Satélite' ? 'selected' : '' }}>Estação Satélite</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Status Inicial -->
                            <div>
                                <label for="status" class="block text-sm font-semibold text-blue-dianne-950 mb-1.5">
                                    Status Inicial <span class="text-cinnabar-500">*</span>
                                </label>
                                <select 
                                    id="status" 
                                    name="status" 
                                    required
                                    class="w-full px-4 py-2.5 text-sm border border-athens-gray-300 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                                    <option value="Disponível" {{ old('status', 'Disponível') === 'Disponível' ? 'selected' : '' }}>Disponível para Instalação</option>
                                    <option value="Alocado" {{ old('status') === 'Alocado' ? 'selected' : '' }}>Alocado para Equipe</option>
                                    <option value="Manutenção" {{ old('status') === 'Manutenção' ? 'selected' : '' }}>Em Manutenção / Testes</option>
                                </select>
                            </div>

                            <!-- Data de Aquisição -->
                            <div>
                                <label for="data_aquisicao" class="block text-sm font-semibold text-blue-dianne-950 mb-1.5">
                                    Data de Aquisição
                                </label>
                                <input 
                                    type="date" 
                                    id="data_aquisicao" 
                                    name="data_aquisicao" 
                                    value="{{ old('data_aquisicao', date('Y-m-d')) }}" 
                                    class="w-full px-4 py-2.5 text-sm border border-athens-gray-300 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                            </div>
                        </div>

                        <!-- Observações -->
                        <div>
                            <label for="observacoes" class="block text-sm font-semibold text-blue-dianne-950 mb-1.5">
                                Observações Adicionais
                            </label>
                            <textarea 
                                id="observacoes" 
                                name="observacoes" 
                                rows="2" 
                                placeholder="Notas sobre lote de compra, fornecedor, revisão de hardware..."
                                class="w-full px-4 py-2.5 text-sm border border-athens-gray-300 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                            >{{ old('observacoes') }}</textarea>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-athens-gray-200">
                            <a href="{{ route('patrimonios.index') }}" class="px-5 py-2.5 text-sm font-semibold text-athens-gray-700 hover:bg-athens-gray-100 rounded-lg transition">
                                Cancelar
                            </a>
                            <button type="submit" class="bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-semibold py-2.5 px-6 rounded-lg transition-all shadow-sm hover:shadow text-sm cursor-pointer">
                                Cadastrar no Estoque
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Formulário 2: Importação em Lote -->
                <div id="painel-lote" class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-6 hidden">
                    <form action="{{ route('patrimonios.store-batch') }}" method="POST" class="space-y-5">
                        @csrf

                        <div class="bg-dodger-blue-50 border border-dodger-blue-200 p-4 rounded-lg flex items-start gap-3">
                            <x-heroicon-o-information-circle class="w-5 h-5 text-dodger-blue-600 shrink-0 mt-0.5" />
                            <div class="text-xs text-dodger-blue-900 space-y-1">
                                <p class="font-bold">Como funciona a importação em massa:</p>
                                <p>Cole abaixo uma lista de endereços MAC (um por linha). O sistema irá formatar, validar e descartar duplicados automaticamente.</p>
                            </div>
                        </div>

                        <!-- Lista de MACs -->
                        <div>
                            <label for="mac_addresses_batch" class="block text-sm font-semibold text-blue-dianne-950 mb-1.5">
                                Lista de MAC Addresses (1 por linha) <span class="text-cinnabar-500">*</span>
                            </label>
                            <textarea 
                                id="mac_addresses_batch" 
                                name="mac_addresses_batch" 
                                rows="8" 
                                required
                                placeholder="AA:BB:CC:DD:EE:01&#10;AA:BB:CC:DD:EE:02&#10;AA-BB-CC-DD-EE-03&#10;AABBCCDDEE04"
                                class="w-full font-mono text-xs px-4 py-3 border border-athens-gray-300 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                            >{{ old('mac_addresses_batch') }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <!-- Tipo Sugerido -->
                            <div>
                                <label for="tipo_sugerido_batch" class="block text-sm font-semibold text-blue-dianne-950 mb-1.5">
                                    Tipo Sugerido do Lote <span class="text-cinnabar-500">*</span>
                                </label>
                                <select 
                                    id="tipo_sugerido_batch" 
                                    name="tipo_sugerido" 
                                    required
                                    class="w-full px-4 py-2.5 text-sm border border-athens-gray-300 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                                    <option value="Indefinido">Indefinido / Misto</option>
                                    <option value="Estação Matriz">Estação Matriz</option>
                                    <option value="Estação Satélite">Estação Satélite</option>
                                </select>
                            </div>

                            <!-- Data de Aquisição -->
                            <div>
                                <label for="data_aquisicao_batch" class="block text-sm font-semibold text-blue-dianne-950 mb-1.5">
                                    Data de Aquisição do Lote
                                </label>
                                <input 
                                    type="date" 
                                    id="data_aquisicao_batch" 
                                    name="data_aquisicao" 
                                    value="{{ date('Y-m-d') }}" 
                                    class="w-full px-4 py-2.5 text-sm border border-athens-gray-300 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition"
                                >
                            </div>
                        </div>

                        <!-- Botões de Ação -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-athens-gray-200">
                            <a href="{{ route('patrimonios.index') }}" class="px-5 py-2.5 text-sm font-semibold text-athens-gray-700 hover:bg-athens-gray-100 rounded-lg transition">
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
                btnLote.className = 'flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all cursor-pointer bg-transparent text-athens-gray-600 hover:text-blue-dianne-900 hover:bg-athens-gray-100';
                painelUnitario.classList.remove('hidden');
                painelLote.classList.add('hidden');
            } else {
                btnLote.className = 'flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all cursor-pointer bg-blue-dianne-600 text-white shadow-sm';
                btnUnitario.className = 'flex-1 flex items-center justify-center gap-2 py-2.5 px-4 rounded-lg font-semibold text-sm transition-all cursor-pointer bg-transparent text-athens-gray-600 hover:text-blue-dianne-900 hover:bg-athens-gray-100';
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

