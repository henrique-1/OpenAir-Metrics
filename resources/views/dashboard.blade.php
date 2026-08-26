<x-layouts.app title="Dashboard - OpenAir Metrics">
    <!-- Container que ocupa o espaço livre abaixo do Header absoluto (pt-20) -->
    <div class="flex h-full w-full bg-athens-gray-50">        
        <!-- Barra de Navegação Lateral (Sidebar) -->
        <aside class="w-64 bg-white/95 backdrop-blur-md shadow-[4px_0_24px_rgba(0,0,0,0.02)] border-r border-athens-gray-200 flex flex-col hidden md:flex z-10 h-full">
            
            <!-- Logotipo embutido na Sidebar -->
            <div class="p-6 border-b border-athens-gray-200 flex items-center justify-center bg-white/50">
                <a href="/" class="transition hover:opacity-80 block">
                    <img src="{{ asset('images/3.png') }}" alt="Logo OpenAir Metrics" class="h-12 w-auto drop-shadow-sm">
                </a>
            </div>

            <div class="p-5 pb-2">
                <h2 class="text-xs font-bold text-athens-gray-500 uppercase tracking-wider">Menu Principal</h2>
            </div>
            
            <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
                <a href="{{ url('/') }}" class="flex items-center gap-3 text-athens-gray-600 hover:bg-athens-gray-100 hover:text-blue-dianne-900 px-3 py-2.5 rounded-lg font-medium transition-colors">
                    <x-heroicon-o-map class="w-5 h-5" />
                    Voltar para o Mapa
                </a>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 bg-blue-dianne-50 text-blue-dianne-950 px-3 py-2.5 rounded-lg font-bold transition-colors border border-blue-dianne-200 shadow-sm">
                    <x-heroicon-o-chart-pie class="w-5 h-5 text-blue-dianne-600" />
                    Visão Geral
                </a>
                <a href="#" class="flex items-center gap-3 text-athens-gray-600 hover:bg-athens-gray-100 hover:text-blue-dianne-900 px-3 py-2.5 rounded-lg font-medium transition-colors">
                    <x-heroicon-o-signal class="w-5 h-5" />
                    Meus Sensores
                </a>
                <a href="#" class="flex items-center gap-3 text-athens-gray-600 hover:bg-athens-gray-100 hover:text-blue-dianne-900 px-3 py-2.5 rounded-lg font-medium transition-colors">
                    <x-heroicon-o-bell-alert class="w-5 h-5" />
                    Gerenciar Alertas
                </a>
            </nav>

            <!-- NOVO: Rodapé da Sidebar com Perfil e Logout -->
            <div class="p-4 border-t border-athens-gray-200 bg-athens-gray-50 mt-auto">
                
                <!-- Informações do Usuário -->
                <div class="flex items-center gap-3 px-2 mb-4">
                    <x-heroicon-o-user-circle class="w-9 h-9 text-blue-dianne-600" />
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-blue-dianne-950 truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-athens-gray-500 truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>

                <!-- Botão de Sair -->
                <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 bg-white text-cinnabar-600 hover:bg-cinnabar-50 hover:text-cinnabar-700 px-3 py-2.5 rounded-lg font-bold transition-colors border border-athens-gray-200 shadow-sm">
                        <x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5" />
                        Sair do Sistema
                    </button>
                </form>
                
            </div>
        </aside>

        <!-- Área de Conteúdo Principal -->
        <main class="flex-1 overflow-y-auto p-6 lg:p-8">
            <div class="max-w-7xl mx-auto space-y-8">
                
                <!-- Título da Página -->
                <div>
                    <h1 class="text-2xl font-bold text-blue-dianne-950 tracking-tight">Visão Geral</h1>
                    <p class="text-sm text-athens-gray-600 mt-1">Acompanhe as métricas e alertas da sua rede IoT de qualidade do ar.</p>
                </div>

                <!-- Grid de Métricas (Responsivo) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    <!-- Sensores Ativos -->
                    <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 flex items-center gap-4">
                        <div class="p-3 bg-emerald-50 rounded-lg text-emerald-500">
                            <x-heroicon-o-cpu-chip class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 uppercase tracking-wide">Sensores Ativos</p>
                            <p class="text-2xl font-bold text-blue-dianne-950">{{ $sensores->count() }}</p>
                        </div>
                    </div>

                    <!-- Leituras -->
                    <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 flex items-center gap-4">
                        <div class="p-3 bg-dodger-blue-50 rounded-lg text-dodger-blue-500">
                            <x-heroicon-o-chart-bar class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 uppercase tracking-wide">Leituras Registradas</p>
                            <p class="text-2xl font-bold text-blue-dianne-950">12.432</p>
                        </div>
                    </div>
                    
                    <!-- Alertas IQA (Qualidade Geral) -->
                    <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 flex items-center gap-4">
                        <div class="p-3 bg-purple-50 rounded-lg text-purple-500">
                            <x-heroicon-o-globe-americas class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 uppercase tracking-wide">Alertas Qualidade</p>
                            <p class="text-2xl font-bold text-blue-dianne-950">2</p>
                        </div>
                    </div>

                    <!-- Alertas de Temperatura -->
                    <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 flex items-center gap-4">
                        <div class="p-3 bg-tahiti-gold-50 rounded-lg text-tahiti-gold-500">
                            <x-heroicon-o-sun class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 uppercase tracking-wide">Alertas Temp.</p>
                            <p class="text-2xl font-bold text-blue-dianne-950">1</p>
                        </div>
                    </div>

                    <!-- Alertas de Umidade -->
                    <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 flex items-center gap-4">
                        <div class="p-3 bg-blue-50 rounded-lg text-blue-500">
                            <x-heroicon-o-cloud class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 uppercase tracking-wide">Alertas Umidade</p>
                            <p class="text-2xl font-bold text-blue-dianne-950">0</p>
                        </div>
                    </div>

                    <!-- Alertas de CO2 -->
                    <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 flex items-center gap-4">
                        <div class="p-3 bg-stone-100 rounded-lg text-stone-600">
                            <x-heroicon-o-building-office-2 class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 uppercase tracking-wide">Alertas CO₂</p>
                            <p class="text-2xl font-bold text-blue-dianne-950">3</p>
                        </div>
                    </div>

                    <!-- Alertas de PM -->
                    <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 p-5 flex items-center gap-4 sm:col-span-2 lg:col-span-2">
                        <div class="p-3 bg-cinnabar-50 rounded-lg text-cinnabar-500">
                            <x-heroicon-o-shield-exclamation class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-xs font-bold text-athens-gray-500 uppercase tracking-wide">Alertas de Material Particulado</p>
                            <p class="text-2xl font-bold text-blue-dianne-950">5</p>
                        </div>
                    </div>
                </div>

                <!-- Tabela de Gerenciamento que você já corrigiu -->
                <div class="bg-white rounded-xl shadow-sm border border-athens-gray-200 overflow-hidden">
                    <div class="px-6 py-5 border-b border-athens-gray-200 flex justify-between items-center bg-athens-gray-50/50">
                        <h3 class="text-lg font-bold text-blue-dianne-950">Sensores Cadastrados</h3>
                        <button class="bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white text-sm font-semibold py-2 px-4 rounded-lg transition-colors shadow-sm flex items-center gap-2">
                            <x-heroicon-o-plus class="w-4 h-4" />
                            Novo Sensor
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-athens-gray-50 border-b border-athens-gray-200 text-xs uppercase tracking-wider text-athens-gray-500">
                                    <th class="px-6 py-3 font-bold">ID Público (UUID)</th>
                                    <th class="px-6 py-3 font-bold">Localização</th>
                                    <th class="px-6 py-3 font-bold">Última Leitura</th>
                                    <th class="px-6 py-3 font-bold text-center">Status</th>
                                    <th class="px-6 py-3 font-bold text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-athens-gray-200 text-sm">
                                @forelse ($sensores as $sensor)
                                    <tr class="hover:bg-athens-gray-50 transition-colors">
                                        <td class="px-6 py-4 font-mono text-xs text-athens-gray-700">{{ $sensor->public_id }}</td>
                                        <td class="px-6 py-4 text-blue-dianne-950 font-medium">Lat: {{ $sensor->latitude }} | Lng: {{ $sensor->longitude }}</td>
                                        <td class="px-6 py-4 text-athens-gray-600">{{ $sensor->created_at->diffForHumans() }}</td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Ativo
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <button class="text-blue-dianne-600 hover:text-blue-dianne-800 font-medium transition">Detalhes</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-10 text-center text-athens-gray-500 bg-white">
                                            <x-heroicon-o-signal-slash class="w-10 h-10 mx-auto text-athens-gray-300 mb-3" />
                                            Nenhum sensor IoT cadastrado no momento.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-layouts.app>