<x-layouts.app title="Login - OpenAir Metrics">
    <div class="flex items-center justify-center min-h-screen bg-athens-gray-100 dark:bg-athens-gray-950 relative w-full h-full">
        
        <!-- Cartão de Login -->
        <div class="w-full max-w-md bg-white/95 dark:bg-athens-gray-900/95 backdrop-blur-md shadow-2xl rounded-xl border border-athens-gray-200 dark:border-athens-gray-800 overflow-hidden z-10 mx-4">
            
            <!-- Cabeçalho do Cartão -->
            <div class="bg-blue-dianne-950 text-white p-6 shadow-sm flex flex-col items-center">
                <img src="{{ asset('images/3.png') }}" alt="Logo OpenAir Metrics" class="h-14 w-auto mb-4 drop-shadow-md dark:hidden">
                <img src="{{ asset('images/3 - dark.png') }}" alt="Logo OpenAir Metrics" class="h-14 w-auto mb-4 drop-shadow-md hidden dark:block">
                <h2 class="font-bold text-xl leading-tight tracking-tight">Acesso ao Sistema</h2>
                <p class="text-sm text-blue-dianne-100 mt-1 opacity-80">Insira suas credenciais para continuar</p>
            </div>

            <!-- Formulário -->
            <div class="p-6">
                <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
                    @csrf

                    <!-- E-mail -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-athens-gray-800 dark:text-athens-gray-200 mb-1">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full px-4 py-2 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 outline-none transition-all text-athens-gray-900 dark:text-athens-gray-100 bg-white dark:bg-athens-gray-800 placeholder-athens-gray-400 dark:placeholder-athens-gray-500"
                            placeholder="seu@email.com">
                        @error('email')
                            <span class="text-cinnabar-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Senha -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-athens-gray-800 dark:text-athens-gray-200 mb-1">Senha</label>
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-2 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 outline-none transition-all text-athens-gray-900 dark:text-athens-gray-100 bg-white dark:bg-athens-gray-800 placeholder-athens-gray-400 dark:placeholder-athens-gray-500"
                            placeholder="••••••••">
                        @error('password')
                            <span class="text-cinnabar-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Lembre-me & Recuperação -->
                    <div class="flex items-center justify-between mt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="rounded border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-blue-dianne-600 focus:ring-blue-dianne-500 w-4 h-4 transition">
                            <span class="text-sm text-athens-gray-700 dark:text-athens-gray-300">Lembrar de mim</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm text-blue-dianne-600 dark:text-blue-dianne-400 hover:text-blue-dianne-800 dark:hover:text-blue-dianne-300 font-medium transition">Esqueceu a senha?</a>
                        @endif
                    </div>

                    <!-- Botão de Submit -->
                    <button type="submit"
                        class="mt-4 w-full bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors shadow-md flex justify-center items-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-dianne-500">
                        Entrar
                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                    </button>
                </form>
            </div>
            
            <!-- Rodapé / Voltar -->
            <div class="p-4 border-t border-athens-gray-200 dark:border-athens-gray-800 bg-athens-gray-50 dark:bg-athens-gray-800/60 text-center">
                <a href="{{ url('/') }}" class="text-sm text-athens-gray-600 dark:text-athens-gray-400 hover:text-athens-gray-900 dark:hover:text-athens-gray-200 font-medium transition flex items-center justify-center gap-1.5">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Voltar para o Mapa
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>