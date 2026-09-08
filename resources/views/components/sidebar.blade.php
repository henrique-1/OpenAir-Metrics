@props(['active' => 'dashboard'])

<!-- Barra Superior Mobile (Exibida apenas em telas menores que md) -->
<div class="md:hidden w-full bg-white/95 dark:bg-athens-gray-900/95 backdrop-blur-md border-b border-athens-gray-200 dark:border-athens-gray-800 px-4 py-3 flex items-center justify-between z-30 shrink-0">
    <div class="flex items-center gap-3">
        <!-- Botão Abrir Menu Mobile -->
        <button 
            type="button" 
            onclick="abrirSidebarMobile()" 
            aria-label="Abrir menu de navegação"
            class="p-2 rounded-lg text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 transition cursor-pointer"
        >
            <x-heroicon-o-bars-3 class="w-6 h-6" />
        </button>

        <a href="/" class="flex items-center gap-2">
            <img src="{{ asset('images/3.png') }}" alt="Logo OpenAir Metrics" class="h-8 w-auto drop-shadow-xs dark:hidden">
            <img src="{{ asset('images/3 - dark.png') }}" alt="Logo OpenAir Metrics" class="h-8 w-auto drop-shadow-xs hidden dark:block">
        </a>
    </div>
    
    <div class="flex items-center gap-2">
        <!-- Botão Alternar Tema Mobile -->
        <button 
            type="button" 
            onclick="toggleTheme()" 
            aria-label="Alternar modo claro e escuro"
            class="p-2 rounded-lg text-athens-gray-700 dark:text-athens-gray-200 bg-athens-gray-50 dark:bg-athens-gray-800 border border-athens-gray-200 dark:border-athens-gray-700 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-700 transition cursor-pointer shadow-2xs"
        >
            <x-heroicon-o-sun class="w-4 h-4 text-tahiti-gold-500 hidden dark:inline" />
            <x-heroicon-o-moon class="w-4 h-4 text-dodger-blue-600 inline dark:hidden" />
        </button>

        <a href="{{ route('perfil.edit') }}" class="p-1.5 rounded-lg text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 transition" title="Meu Perfil">
            <x-heroicon-o-user-circle class="w-7 h-7 text-blue-dianne-600 dark:text-blue-dianne-400" />
        </a>
    </div>
</div>

<!-- Drawer / Menu Lateral Mobile Off-Canvas -->
<div id="mobile-sidebar-drawer" class="fixed inset-0 z-50 md:hidden hidden">
    <!-- Backdrop com fade -->
    <div class="fixed inset-0 bg-blue-dianne-950/60 backdrop-blur-xs transition-opacity" onclick="fecharSidebarMobile()"></div>

    <!-- Painel Deslizante -->
    <div class="fixed inset-y-0 left-0 w-72 max-w-[85vw] bg-white dark:bg-athens-gray-900 shadow-2xl flex flex-col z-10 transition-transform">
        <!-- Topo do Drawer -->
        <div class="p-4 border-b border-athens-gray-200 dark:border-athens-gray-800 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <img src="{{ asset('images/3.png') }}" alt="Logo OpenAir Metrics" class="h-9 w-auto drop-shadow-xs dark:hidden">
                <img src="{{ asset('images/3 - dark.png') }}" alt="Logo OpenAir Metrics" class="h-9 w-auto drop-shadow-xs hidden dark:block">
            </a>
            <button type="button" onclick="fecharSidebarMobile()" aria-label="Fechar menu" class="p-2 rounded-lg text-athens-gray-500 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 cursor-pointer">
                <x-heroicon-o-x-mark class="w-6 h-6" />
            </button>
        </div>

        <!-- Links de Navegação Mobile -->
        <div class="p-4 pb-2">
            <h2 class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400 uppercase tracking-wider">Menu Principal</h2>
        </div>

        <nav class="flex-1 p-3 space-y-1.5 overflow-y-auto">
            <a href="{{ url('/') }}" class="flex items-center gap-3 text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 px-3 py-2.5 rounded-lg font-medium transition-colors">
                <x-heroicon-o-map class="w-5 h-5 text-blue-dianne-600 dark:text-blue-dianne-400" />
                Voltar para o Mapa
            </a>
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active === 'dashboard' ? 'bg-blue-dianne-50 dark:bg-blue-dianne-950/80 text-blue-dianne-950 dark:text-blue-dianne-300 font-bold border border-blue-dianne-200 dark:border-blue-dianne-800 shadow-sm' : 'text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 font-medium' }}">
                <x-heroicon-o-chart-pie class="w-5 h-5 {{ $active === 'dashboard' ? 'text-blue-dianne-600 dark:text-blue-dianne-400' : 'text-athens-gray-500 dark:text-athens-gray-400' }}" />
                Visão Geral
            </a>
            <a href="{{ route('estacoes.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active === 'estacoes' ? 'bg-blue-dianne-50 dark:bg-blue-dianne-950/80 text-blue-dianne-950 dark:text-blue-dianne-300 font-bold border border-blue-dianne-200 dark:border-blue-dianne-800 shadow-sm' : 'text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 font-medium' }}">
                <x-heroicon-o-signal class="w-5 h-5 {{ $active === 'estacoes' ? 'text-blue-dianne-600 dark:text-blue-dianne-400' : 'text-athens-gray-500 dark:text-athens-gray-400' }}" />
                Minhas Estações
            </a>
            <a href="{{ route('patrimonios.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active === 'patrimonios' ? 'bg-blue-dianne-50 dark:bg-blue-dianne-950/80 text-blue-dianne-950 dark:text-blue-dianne-300 font-bold border border-blue-dianne-200 dark:border-blue-dianne-800 shadow-sm' : 'text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 font-medium' }}">
                <x-heroicon-o-server-stack class="w-5 h-5 {{ $active === 'patrimonios' ? 'text-blue-dianne-600 dark:text-blue-dianne-400' : 'text-athens-gray-500 dark:text-athens-gray-400' }}" />
                Patrimônio
            </a>
            <a href="{{ route('instalacoes.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active === 'instalacoes' ? 'bg-blue-dianne-50 dark:bg-blue-dianne-950/80 text-blue-dianne-950 dark:text-blue-dianne-300 font-bold border border-blue-dianne-200 dark:border-blue-dianne-800 shadow-sm' : 'text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 font-medium' }}">
                <x-heroicon-o-clipboard-document-check class="w-5 h-5 {{ $active === 'instalacoes' ? 'text-blue-dianne-600 dark:text-blue-dianne-400' : 'text-athens-gray-500 dark:text-athens-gray-400' }}" />
                Ordens de Instalação
            </a>

            @if(Auth::user() && Auth::user()->isAdministrador())
                <a href="{{ route('usuarios.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active === 'usuarios' ? 'bg-blue-dianne-50 dark:bg-blue-dianne-950/80 text-blue-dianne-950 dark:text-blue-dianne-300 font-bold border border-blue-dianne-200 dark:border-blue-dianne-800 shadow-sm' : 'text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 font-medium' }}">
                    <x-heroicon-o-users class="w-5 h-5 {{ $active === 'usuarios' ? 'text-blue-dianne-600 dark:text-blue-dianne-400' : 'text-athens-gray-500 dark:text-athens-gray-400' }}" />
                    Usuários Municipais
                </a>
            @endif

            <a href="{{ route('perfil.edit') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active === 'perfil' ? 'bg-blue-dianne-50 dark:bg-blue-dianne-950/80 text-blue-dianne-950 dark:text-blue-dianne-300 font-bold border border-blue-dianne-200 dark:border-blue-dianne-800 shadow-sm' : 'text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 font-medium' }}">
                <x-heroicon-o-user class="w-5 h-5 {{ $active === 'perfil' ? 'text-blue-dianne-600 dark:text-blue-dianne-400' : 'text-athens-gray-500 dark:text-athens-gray-400' }}" />
                Meu Perfil
            </a>
        </nav>

        <!-- Rodapé do Drawer -->
        <div class="p-4 border-t border-athens-gray-200 dark:border-athens-gray-800 bg-athens-gray-50 dark:bg-athens-gray-900/80 space-y-3">
            @if(Auth::user())
                <div class="flex items-center gap-3">
                    <x-heroicon-o-user-circle class="w-9 h-9 text-blue-dianne-600 dark:text-blue-dianne-400 shrink-0" />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-bold text-blue-dianne-950 dark:text-white truncate">{{ Auth::user()->name }}</p>
                        <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 truncate">{{ Auth::user()->nivel_label }}</p>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 bg-white dark:bg-athens-gray-800 text-cinnabar-600 dark:text-cinnabar-400 hover:bg-cinnabar-50 dark:hover:bg-cinnabar-950/40 hover:text-cinnabar-700 px-3 py-2 rounded-lg text-xs font-bold transition-colors border border-athens-gray-200 dark:border-athens-gray-700 shadow-xs cursor-pointer">
                    <x-heroicon-o-arrow-left-on-rectangle class="w-4 h-4" />
                    Sair do Sistema
                </button>
            </form>
        </div>
    </div>
</div>

<aside class="w-64 bg-white/95 dark:bg-athens-gray-900/95 backdrop-blur-md shadow-[4px_0_24px_rgba(0,0,0,0.02)] border-r border-athens-gray-200 dark:border-athens-gray-800 flex flex-col hidden md:flex z-10 md:sticky md:top-0 md:h-screen shrink-0 transition-colors duration-200">
    <!-- Logotipo embutido na Sidebar -->
    <div class="p-6 border-b border-athens-gray-200 dark:border-athens-gray-800 flex items-center justify-center bg-white/50 dark:bg-athens-gray-900/50">
        <a href="/" class="transition hover:opacity-80 block">
            <img src="{{ asset('images/3.png') }}" alt="Logo OpenAir Metrics" class="h-12 w-auto drop-shadow-sm dark:hidden">
            <img src="{{ asset('images/3 - dark.png') }}" alt="Logo OpenAir Metrics" class="h-12 w-auto drop-shadow-sm hidden dark:block">
        </a>
    </div>

    <div class="p-5 pb-2">
        <h2 class="text-xs font-bold text-athens-gray-500 dark:text-athens-gray-400 uppercase tracking-wider">Menu Principal</h2>
    </div>
    
    <nav class="flex-1 p-4 space-y-2 overflow-y-auto">
        <a href="{{ url('/') }}" class="flex items-center gap-3 text-athens-gray-600 dark:text-athens-gray-300 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 hover:text-blue-dianne-900 dark:hover:text-white px-3 py-2.5 rounded-lg font-medium transition-colors">
            <x-heroicon-o-map class="w-5 h-5" />
            Voltar para o Mapa
        </a>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active === 'dashboard' ? 'bg-blue-dianne-50 dark:bg-blue-dianne-950/80 text-blue-dianne-950 dark:text-blue-dianne-300 font-bold border border-blue-dianne-200 dark:border-blue-dianne-800 shadow-sm' : 'text-athens-gray-600 dark:text-athens-gray-300 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 hover:text-blue-dianne-900 dark:hover:text-white font-medium' }}">
            <x-heroicon-o-chart-pie class="w-5 h-5 {{ $active === 'dashboard' ? 'text-blue-dianne-600 dark:text-blue-dianne-400' : 'text-athens-gray-500 dark:text-athens-gray-400' }}" />
            Visão Geral
        </a>
        <a href="{{ route('estacoes.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active === 'estacoes' ? 'bg-blue-dianne-50 dark:bg-blue-dianne-950/80 text-blue-dianne-950 dark:text-blue-dianne-300 font-bold border border-blue-dianne-200 dark:border-blue-dianne-800 shadow-sm' : 'text-athens-gray-600 dark:text-athens-gray-300 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 hover:text-blue-dianne-900 dark:hover:text-white font-medium' }}">
            <x-heroicon-o-signal class="w-5 h-5 {{ $active === 'estacoes' ? 'text-blue-dianne-600 dark:text-blue-dianne-400' : 'text-athens-gray-500 dark:text-athens-gray-400' }}" />
            Minhas Estações
        </a>
        <a href="{{ route('patrimonios.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active === 'patrimonios' ? 'bg-blue-dianne-50 dark:bg-blue-dianne-950/80 text-blue-dianne-950 dark:text-blue-dianne-300 font-bold border border-blue-dianne-200 dark:border-blue-dianne-800 shadow-sm' : 'text-athens-gray-600 dark:text-athens-gray-300 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 hover:text-blue-dianne-900 dark:hover:text-white font-medium' }}">
            <x-heroicon-o-server-stack class="w-5 h-5 {{ $active === 'patrimonios' ? 'text-blue-dianne-600 dark:text-blue-dianne-400' : 'text-athens-gray-500 dark:text-athens-gray-400' }}" />
            Patrimônio
        </a>
        <a href="{{ route('instalacoes.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active === 'instalacoes' ? 'bg-blue-dianne-50 dark:bg-blue-dianne-950/80 text-blue-dianne-950 dark:text-blue-dianne-300 font-bold border border-blue-dianne-200 dark:border-blue-dianne-800 shadow-sm' : 'text-athens-gray-600 dark:text-athens-gray-300 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 hover:text-blue-dianne-900 dark:hover:text-white font-medium' }}">
            <x-heroicon-o-clipboard-document-check class="w-5 h-5 {{ $active === 'instalacoes' ? 'text-blue-dianne-600 dark:text-blue-dianne-400' : 'text-athens-gray-500 dark:text-athens-gray-400' }}" />
            Ordens de Instalação
        </a>

        @if(Auth::user() && Auth::user()->isAdministrador())
            <a href="{{ route('usuarios.index') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active === 'usuarios' ? 'bg-blue-dianne-50 dark:bg-blue-dianne-950/80 text-blue-dianne-950 dark:text-blue-dianne-300 font-bold border border-blue-dianne-200 dark:border-blue-dianne-800 shadow-sm' : 'text-athens-gray-600 dark:text-athens-gray-300 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 hover:text-blue-dianne-900 dark:hover:text-white font-medium' }}">
                <x-heroicon-o-users class="w-5 h-5 {{ $active === 'usuarios' ? 'text-blue-dianne-600 dark:text-blue-dianne-400' : 'text-athens-gray-500 dark:text-athens-gray-400' }}" />
                Usuários Municipais
            </a>
        @endif

        <a href="{{ route('perfil.edit') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ $active === 'perfil' ? 'bg-blue-dianne-50 dark:bg-blue-dianne-950/80 text-blue-dianne-950 dark:text-blue-dianne-300 font-bold border border-blue-dianne-200 dark:border-blue-dianne-800 shadow-sm' : 'text-athens-gray-600 dark:text-athens-gray-300 hover:bg-athens-gray-100 dark:hover:bg-athens-gray-800 hover:text-blue-dianne-900 dark:hover:text-white font-medium' }}">
            <x-heroicon-o-user class="w-5 h-5 {{ $active === 'perfil' ? 'text-blue-dianne-600 dark:text-blue-dianne-400' : 'text-athens-gray-500 dark:text-athens-gray-400' }}" />
            Meu Perfil
        </a>
    </nav>

    <!-- Rodapé da Sidebar com Seletor de Tema, Perfil e Logout -->
    <div class="p-4 border-t border-athens-gray-200 dark:border-athens-gray-800 bg-athens-gray-50 dark:bg-athens-gray-900 mt-auto space-y-3">
        <!-- Botão Seletor de Modo Claro / Modo Escuro -->
        <button 
            type="button" 
            onclick="toggleTheme()" 
            id="theme-toggle-btn"
            aria-label="Alternar modo claro e escuro"
            class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-xs font-semibold text-athens-gray-700 dark:text-athens-gray-200 bg-white dark:bg-athens-gray-800 border border-athens-gray-200 dark:border-athens-gray-700 shadow-xs hover:bg-athens-gray-100 dark:hover:bg-athens-gray-700 transition cursor-pointer"
        >
            <span class="flex items-center gap-2">
                <x-heroicon-o-sun id="theme-icon-sun" class="w-4 h-4 text-tahiti-gold-500 hidden dark:inline" />
                <x-heroicon-o-moon id="theme-icon-moon" class="w-4 h-4 text-dodger-blue-600 inline dark:hidden" />
                <span id="theme-toggle-label">Modo Claro</span>
            </span>
            <span class="text-[10px] font-medium tracking-wide uppercase px-1.5 py-0.5 rounded bg-athens-gray-100 dark:bg-athens-gray-700 text-athens-gray-600 dark:text-athens-gray-300">
                Tema
            </span>
        </button>

        <!-- Informações do Usuário -->
        <a href="{{ route('perfil.edit') }}" class="flex items-center gap-3 px-2 hover:bg-athens-gray-100/80 dark:hover:bg-athens-gray-800 p-1.5 rounded-lg transition group">
            <x-heroicon-o-user-circle class="w-9 h-9 text-blue-dianne-600 dark:text-blue-dianne-400 group-hover:text-blue-dianne-700 transition" />
            <div class="flex-1 min-w-0">
                <p class="text-sm font-bold text-blue-dianne-950 dark:text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-xs text-athens-gray-500 dark:text-athens-gray-400 truncate">{{ Auth::user()->nivel_label }}</p>
            </div>
        </a>

        <!-- Botão de Sair -->
        <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
            @csrf
            <button type="submit" class="w-full flex items-center justify-center gap-2 bg-white dark:bg-athens-gray-800 text-cinnabar-600 dark:text-cinnabar-400 hover:bg-cinnabar-50 dark:hover:bg-cinnabar-950/40 hover:text-cinnabar-700 px-3 py-2.5 rounded-lg font-bold transition-colors border border-athens-gray-200 dark:border-athens-gray-700 shadow-sm cursor-pointer">
                <x-heroicon-o-arrow-left-on-rectangle class="w-5 h-5" />
                Sair do Sistema
            </button>
        </form>
    </div>
</aside>

<script>
    function updateThemeUI() {
        const isDark = document.documentElement.classList.contains('dark');
        const labels = document.querySelectorAll('#theme-toggle-label');
        labels.forEach(label => {
            label.textContent = isDark ? 'Modo Escuro' : 'Modo Claro';
        });
    }

    function toggleTheme() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateThemeUI();
        window.dispatchEvent(new CustomEvent('themechanged', { detail: { isDark } }));
    }

    function abrirSidebarMobile() {
        const drawer = document.getElementById('mobile-sidebar-drawer');
        if (drawer) drawer.classList.remove('hidden');
    }

    function fecharSidebarMobile() {
        const drawer = document.getElementById('mobile-sidebar-drawer');
        if (drawer) drawer.classList.add('hidden');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            fecharSidebarMobile();
        }
    });

    document.addEventListener('DOMContentLoaded', updateThemeUI);
</script>
