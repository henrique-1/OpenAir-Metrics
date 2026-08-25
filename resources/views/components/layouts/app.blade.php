<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <title>{{ $title ?? config('app.name', 'OpenAir Metrics') }}</title>
        <link rel="icon" type="image/x-icon" href="{{ asset('images/icons/OpenAir_Metrics.ico') }}">
        
        @fonts
        
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        
        @stack('styles')
    </head>
    
    {{-- Travamos a tela em 100% de largura e altura, sem scroll --}}
    <body class="font-sans antialiased h-screen w-screen overflow-hidden bg-athens-gray-50">
        
        {{-- Header Flutuante e Transparente --}}
        <header class="absolute top-0 left-0 w-full z-[2000] pointer-events-none p-4">
            <div class="relative flex items-center w-full h-12">
                
                {{-- Logo Centralizado Absolutamente --}}
                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 pointer-events-auto">
                    <a href="/" class="transition hover:opacity-80 block">
                        {{-- O drop-shadow garante leitura caso o mapa de calor fique escuro atrás do logo --}}
                        <img src="{{ asset('images/3.png') }}" alt="Logo OpenAir Metrics" class="h-16 w-auto drop-shadow-md">
                    </a>
                </div>

                {{-- Links de Autenticação na Direita (com fundo desfocado para leitura) --}}
                @if (Route::has('login'))
                    <nav class="ml-auto flex items-center gap-4 text-sm font-medium pointer-events-auto bg-white/80 backdrop-blur-md px-5 py-2.5 rounded-full shadow-sm border border-white/20">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-blue-dianne-700 hover:text-blue-dianne-900 transition">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-athens-gray-700 hover:text-blue-dianne-700 transition">Log in</a>
                        @endauth
                    </nav>
                @endif
                
            </div>
        </header>

        {{-- O container principal agora ocupa 100% do body --}}
        <main class="h-full w-full">
            {{ $slot }}
        </main>

        @stack('scripts')
    </body>
</html>