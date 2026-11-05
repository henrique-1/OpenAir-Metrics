<x-layouts.app title="Recuperar senha - OpenAir Metrics">
    <div class="flex items-center justify-center min-h-screen bg-athens-gray-100 dark:bg-athens-gray-950 relative w-full h-full">

        <!-- Cartão de Recuperação -->
        <div class="w-full max-w-md bg-white/95 dark:bg-athens-gray-900/95 backdrop-blur-md shadow-2xl rounded-xl border border-athens-gray-200 dark:border-athens-gray-800 overflow-hidden z-10 mx-4">

            <!-- Cabeçalho do Cartão -->
            <div class="bg-athens-gray-50 dark:bg-athens-gray-900 border-b border-athens-gray-200 dark:border-athens-gray-800 p-6 flex flex-col items-center">
                <img src="{{ asset('images/3.png') }}" alt="Logo OpenAir Metrics" class="h-14 w-auto mb-4 drop-shadow-sm dark:hidden">
                <img src="{{ asset('images/3 - dark.png') }}" alt="Logo OpenAir Metrics" class="h-14 w-auto mb-4 drop-shadow-sm hidden dark:block">
                <h2 class="font-bold text-xl text-athens-gray-900 dark:text-white leading-tight tracking-tight">Recuperar senha</h2>
                <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mt-1 text-center">Informe seu e-mail e enviaremos um link para você criar uma nova senha</p>
            </div>

            <!-- Formulário -->
            <div class="p-6">
                @if (session('status'))
                    <div role="status" class="mb-4 p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-sm text-emerald-800 dark:text-emerald-200">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-4">
                    @csrf

                    <!-- E-mail -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-athens-gray-800 dark:text-athens-gray-200 mb-1">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autofocus autocomplete="email" maxlength="255"
                            class="w-full px-4 py-2 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 outline-none transition-all text-athens-gray-900 dark:text-athens-gray-100 bg-white dark:bg-athens-gray-800 placeholder-athens-gray-400 dark:placeholder-athens-gray-500"
                            placeholder="seu@email.com">
                        @error('email')
                            <span class="text-cinnabar-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Botão de Submit -->
                    <button type="submit"
                        class="mt-2 w-full bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors shadow-md flex justify-center items-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-dianne-500">
                        Enviar e-mail de recuperação
                        <x-heroicon-o-envelope class="w-4 h-4" />
                    </button>
                </form>
            </div>

            <!-- Rodapé / Voltar -->
            <div class="p-4 border-t border-athens-gray-200 dark:border-athens-gray-800 bg-athens-gray-50 dark:bg-athens-gray-800/60 text-center">
                <a href="{{ route('login') }}" class="text-sm text-athens-gray-600 dark:text-athens-gray-400 hover:text-athens-gray-900 dark:hover:text-athens-gray-200 font-medium transition flex items-center justify-center gap-1.5">
                    <x-heroicon-o-arrow-left class="w-4 h-4" />
                    Voltar para o login
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
