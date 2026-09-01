@props(['active' => 'dashboard'])

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
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active === 'dashboard' ? 'bg-blue-dianne-50 text-blue-dianne-950 font-bold border border-blue-dianne-200 shadow-sm' : 'text-athens-gray-600 hover:bg-athens-gray-100 hover:text-blue-dianne-900 font-medium' }}">
            <x-heroicon-o-chart-pie class="w-5 h-5 {{ $active === 'dashboard' ? 'text-blue-dianne-600' : 'text-athens-gray-500' }}" />
            Visão Geral
        </a>
        <a href="{{ route('estacoes.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active === 'estacoes' ? 'bg-blue-dianne-50 text-blue-dianne-950 font-bold border border-blue-dianne-200 shadow-sm' : 'text-athens-gray-600 hover:bg-athens-gray-100 hover:text-blue-dianne-900 font-medium' }}">
            <x-heroicon-o-signal class="w-5 h-5 {{ $active === 'estacoes' ? 'text-blue-dianne-600' : 'text-athens-gray-500' }}" />
            Minhas Estações
        </a>
        <a href="#" class="flex items-center gap-3 text-athens-gray-600 hover:bg-athens-gray-100 hover:text-blue-dianne-900 px-3 py-2.5 rounded-lg font-medium transition-colors">
            <x-heroicon-o-bell-alert class="w-5 h-5 text-athens-gray-500" />
            Gerenciar Alertas
        </a>
    </nav>

    <!-- Rodapé da Sidebar com Perfil e Logout -->
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
            <button type="submit" class="w-full flex items-center justify-center gap-2 bg-white text-cinnabar-600 hover:bg-cinnabar-50 hover:text-cinnabar-700 px-3 py-2.5 rounded-lg font-bold transition-colors border border-athens-gray-200 shadow-sm cursor-pointer">
                <x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5" />
                Sair do Sistema
            </button>
        </form>
    </div>
</aside>
