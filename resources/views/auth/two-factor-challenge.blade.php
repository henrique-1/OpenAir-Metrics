<x-layouts.app title="Autenticação em Duas Etapas - OpenAir Metrics">
    <div class="flex items-center justify-center min-h-screen bg-athens-gray-100 dark:bg-athens-gray-950 relative w-full h-full">

        <!-- Cartão de Desafio 2FA -->
        <div class="w-full max-w-md bg-white/95 dark:bg-athens-gray-900/95 backdrop-blur-md shadow-2xl rounded-xl border border-athens-gray-200 dark:border-athens-gray-800 overflow-hidden z-10 mx-4">

            <!-- Cabeçalho do Cartão -->
            <div class="bg-blue-dianne-950 text-white p-6 shadow-sm flex flex-col items-center">
                <img src="{{ asset('images/3.png') }}" alt="Logo OpenAir Metrics" class="h-14 w-auto mb-4 drop-shadow-md dark:hidden">
                <img src="{{ asset('images/3 - dark.png') }}" alt="Logo OpenAir Metrics" class="h-14 w-auto mb-4 drop-shadow-md hidden dark:block">
                <h2 class="font-bold text-xl leading-tight tracking-tight">Autenticação em Duas Etapas</h2>
                <p id="challenge-descricao" class="text-sm text-blue-dianne-100 mt-1 opacity-80 text-center">
                    Insira o código de 6 dígitos gerado pelo seu aplicativo autenticador
                </p>
            </div>

            <!-- Formulário -->
            <div class="p-6">
                @if ($errors->any())
                    <div class="mb-4 p-3 rounded-lg bg-cinnabar-50 dark:bg-cinnabar-900/30 border border-cinnabar-200 dark:border-cinnabar-800 text-sm text-cinnabar-800 dark:text-cinnabar-200">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('two-factor.login') }}" class="flex flex-col gap-4">
                    @csrf

                    <!-- Modo 1: Código TOTP (Padrão) -->
                    <div id="modo-totp">
                        <label for="code" class="block text-sm font-medium text-athens-gray-800 dark:text-athens-gray-200 mb-1">
                            Código de Autenticação
                        </label>
                        <input type="text" id="code" name="code" inputmode="numeric" autofocus autocomplete="one-time-code" maxlength="7"
                            class="w-full px-4 py-2.5 text-center text-lg tracking-widest font-mono border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 outline-none transition-all text-athens-gray-900 dark:text-athens-gray-100 bg-white dark:bg-athens-gray-800 placeholder-athens-gray-400 dark:placeholder-athens-gray-500"
                            placeholder="000 000">
                    </div>

                    <!-- Modo 2: Código de Recuperação (Backup) -->
                    <div id="modo-recovery" class="hidden">
                        <label for="recovery_code" class="block text-sm font-medium text-athens-gray-800 dark:text-athens-gray-200 mb-1">
                            Código de Recuperação (Backup)
                        </label>
                        <input type="text" id="recovery_code" name="recovery_code" autocomplete="one-time-code"
                            class="w-full px-4 py-2.5 font-mono text-sm border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 outline-none transition-all text-athens-gray-900 dark:text-athens-gray-100 bg-white dark:bg-athens-gray-800 placeholder-athens-gray-400 dark:placeholder-athens-gray-500"
                            placeholder="xxxx-xxxx-xxxx">
                    </div>

                    <!-- Alternar modo -->
                    <div class="text-right">
                        <button type="button" id="btn-alternar-modo" class="text-xs text-blue-dianne-600 dark:text-blue-dianne-400 hover:text-blue-dianne-800 dark:hover:text-blue-dianne-300 font-medium transition underline-offset-2 hover:underline">
                            Usar um código de recuperação
                        </button>
                    </div>

                    <!-- Botão de Submit -->
                    <button type="submit"
                        class="mt-2 w-full bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors shadow-md flex justify-center items-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-dianne-500">
                        Confirmar Acesso
                        <x-heroicon-o-shield-check class="w-5 h-5" />
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

    @push('scripts')
        <script>
            const btnAlternar = document.getElementById('btn-alternar-modo');
            const modoTotp = document.getElementById('modo-totp');
            const modoRecovery = document.getElementById('modo-recovery');
            const inputCode = document.getElementById('code');
            const inputRecovery = document.getElementById('recovery_code');
            const descricao = document.getElementById('challenge-descricao');

            let usandoRecovery = false;

            btnAlternar?.addEventListener('click', () => {
                usandoRecovery = !usandoRecovery;

                modoTotp.classList.toggle('hidden', usandoRecovery);
                modoRecovery.classList.toggle('hidden', !usandoRecovery);

                if (usandoRecovery) {
                    btnAlternar.textContent = 'Usar código do aplicativo autenticador';
                    descricao.textContent = 'Insira um dos seus códigos de recuperação descartáveis de backup';
                    inputCode.value = '';
                    inputRecovery.focus();
                } else {
                    btnAlternar.textContent = 'Usar um código de recuperação';
                    descricao.textContent = 'Insira o código de 6 dígitos gerado pelo seu aplicativo autenticador';
                    inputRecovery.value = '';
                    inputCode.focus();
                }
            });
        </script>
    @endpush
</x-layouts.app>
