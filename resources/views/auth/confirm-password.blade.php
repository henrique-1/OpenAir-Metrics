<x-layouts.app title="Confirmar Senha - OpenAir Metrics">
    <div class="flex items-center justify-center min-h-screen bg-athens-gray-100 dark:bg-athens-gray-950 relative w-full h-full">

        <!-- Cartão de Confirmação de Senha -->
        <div class="w-full max-w-md bg-white/95 dark:bg-athens-gray-900/95 backdrop-blur-md shadow-2xl rounded-xl border border-athens-gray-200 dark:border-athens-gray-800 overflow-hidden z-10 mx-4">

            <!-- Cabeçalho do Cartão -->
            <div class="bg-blue-dianne-950 text-white p-6 shadow-sm flex flex-col items-center">
                <img src="{{ asset('images/3.png') }}" alt="Logo OpenAir Metrics" class="h-14 w-auto mb-4 drop-shadow-md dark:hidden">
                <img src="{{ asset('images/3 - dark.png') }}" alt="Logo OpenAir Metrics" class="h-14 w-auto mb-4 drop-shadow-md hidden dark:block">
                <h2 class="font-bold text-xl leading-tight tracking-tight">Confirmação de Segurança</h2>
                <p class="text-sm text-blue-dianne-100 mt-1 opacity-80 text-center">
                    Por favor, confirme sua senha antes de prosseguir com esta ação sensível
                </p>
            </div>

            <!-- Formulário -->
            <div class="p-6">
                @if ($errors->any())
                    <div class="mb-4 p-3 rounded-lg bg-cinnabar-50 dark:bg-cinnabar-900/30 border border-cinnabar-200 dark:border-cinnabar-800 text-sm text-cinnabar-800 dark:text-cinnabar-200">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-4">
                    @csrf

                    <div>
                        <label for="password" class="block text-sm font-medium text-athens-gray-800 dark:text-athens-gray-200 mb-1">
                            Sua Senha Atual
                        </label>
                        <input type="password" id="password" name="password" required autofocus autocomplete="current-password"
                            class="w-full px-4 py-2.5 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 outline-none transition-all text-athens-gray-900 dark:text-athens-gray-100 bg-white dark:bg-athens-gray-800 placeholder-athens-gray-400 dark:placeholder-athens-gray-500"
                            placeholder="••••••••">
                    </div>

                    <!-- Botão de Submit -->
                    <button type="submit"
                        class="mt-2 w-full bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors shadow-md flex justify-center items-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-dianne-500">
                        Confirmar Senha
                        <x-heroicon-o-check class="w-5 h-5" />
                    </button>
                </form>
            </div>

            <!-- Rodapé / Voltar -->
            <div class="p-4 border-t border-athens-gray-200 dark:border-athens-gray-800 bg-athens-gray-50 dark:bg-athens-gray-800/60 text-center">
                <a href="{{ url()->previous() }}" class="text-sm text-athens-gray-600 dark:text-athens-gray-400 hover:text-athens-gray-900 dark:hover:text-athens-gray-200 font-medium transition flex items-center justify-center gap-1.5">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Voltar
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
