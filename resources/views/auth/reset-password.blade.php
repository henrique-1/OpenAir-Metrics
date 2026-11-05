<x-layouts.app title="Redefinir senha - OpenAir Metrics">
    <div class="flex items-center justify-center min-h-screen bg-athens-gray-100 dark:bg-athens-gray-950 relative w-full h-full">

        <!-- Cartão de Redefinição -->
        <div class="w-full max-w-md bg-white/95 dark:bg-athens-gray-900/95 backdrop-blur-md shadow-2xl rounded-xl border border-athens-gray-200 dark:border-athens-gray-800 overflow-hidden z-10 mx-4">

            <!-- Cabeçalho do Cartão -->
            <div class="bg-athens-gray-50 dark:bg-athens-gray-900 border-b border-athens-gray-200 dark:border-athens-gray-800 p-6 flex flex-col items-center">
                <img src="{{ asset('images/3.png') }}" alt="Logo OpenAir Metrics" class="h-14 w-auto mb-4 drop-shadow-sm dark:hidden">
                <img src="{{ asset('images/3 - dark.png') }}" alt="Logo OpenAir Metrics" class="h-14 w-auto mb-4 drop-shadow-sm hidden dark:block">
                <h2 class="font-bold text-xl text-athens-gray-900 dark:text-white leading-tight tracking-tight">Redefinir senha</h2>
                <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mt-1">Crie uma nova senha para a sua conta</p>
            </div>

            <!-- Formulário -->
            <div class="p-6">
                <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <!-- E-mail -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-athens-gray-800 dark:text-athens-gray-200 mb-1">E-mail</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username" maxlength="255"
                            class="w-full px-4 py-2 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 outline-none transition-all text-athens-gray-900 dark:text-athens-gray-100 bg-white dark:bg-athens-gray-800 placeholder-athens-gray-400 dark:placeholder-athens-gray-500"
                            placeholder="seu@email.com">
                        @error('email')
                            <span class="text-cinnabar-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Nova senha -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-athens-gray-800 dark:text-athens-gray-200 mb-1">Nova senha</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required autofocus autocomplete="new-password" minlength="15" maxlength="64"
                                aria-describedby="password-ajuda"
                                class="w-full pl-4 pr-11 py-2 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 outline-none transition-all text-athens-gray-900 dark:text-athens-gray-100 bg-white dark:bg-athens-gray-800 placeholder-athens-gray-400 dark:placeholder-athens-gray-500"
                                placeholder="Mínimo 15 caracteres">
                            <button type="button" data-alternar-senha="password" aria-label="Mostrar senha" aria-pressed="false"
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-athens-gray-500 hover:text-athens-gray-800 dark:hover:text-athens-gray-200 transition cursor-pointer z-10">
                                <x-heroicon-o-eye class="w-5 h-5" data-icone="mostrar" />
                                <x-heroicon-o-eye-slash class="w-5 h-5 hidden" data-icone="ocultar" />
                            </button>
                        </div>
                        <div id="password-ajuda" class="flex justify-between gap-2 mt-1 text-xs text-athens-gray-500 dark:text-athens-gray-400">
                            <span>Use de 15 a 64 caracteres. Uma frase com várias palavras é fácil de lembrar e segura.</span>
                            <span id="password-contador" aria-live="polite" class="shrink-0 tabular-nums">0/64</span>
                        </div>
                        @error('password')
                            <span class="text-cinnabar-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Confirmar nova senha -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-athens-gray-800 dark:text-athens-gray-200 mb-1">Confirmar nova senha</label>
                        <div class="relative">
                            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" minlength="15" maxlength="64"
                                class="w-full pl-4 pr-11 py-2 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 outline-none transition-all text-athens-gray-900 dark:text-athens-gray-100 bg-white dark:bg-athens-gray-800 placeholder-athens-gray-400 dark:placeholder-athens-gray-500"
                                placeholder="Repita a nova senha">
                            <button type="button" data-alternar-senha="password_confirmation" aria-label="Mostrar senha" aria-pressed="false"
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-athens-gray-500 hover:text-athens-gray-800 dark:hover:text-athens-gray-200 transition cursor-pointer z-10">
                                <x-heroicon-o-eye class="w-5 h-5" data-icone="mostrar" />
                                <x-heroicon-o-eye-slash class="w-5 h-5 hidden" data-icone="ocultar" />
                            </button>
                        </div>
                    </div>

                    <!-- Botão de Submit -->
                    <button type="submit"
                        class="mt-2 w-full bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors shadow-md flex justify-center items-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-dianne-500">
                        Redefinir senha
                        <x-heroicon-o-key class="w-4 h-4" />
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
            // Botões "mostrar/ocultar senha" (NIST SP 800-63B recomenda oferecer essa opção).
            document.querySelectorAll('[data-alternar-senha]').forEach((botao) => {
                botao.addEventListener('click', () => {
                    const campo = document.getElementById(botao.dataset.alternarSenha);
                    if (! campo) return;

                    const exibir = campo.type === 'password';
                    campo.type = exibir ? 'text' : 'password';

                    botao.setAttribute('aria-pressed', String(exibir));
                    botao.setAttribute('aria-label', exibir ? 'Ocultar senha' : 'Mostrar senha');
                    botao.querySelector('[data-icone="mostrar"]')?.classList.toggle('hidden', exibir);
                    botao.querySelector('[data-icone="ocultar"]')?.classList.toggle('hidden', ! exibir);
                });
            });

            // Contador de caracteres da nova senha.
            const campoSenha = document.getElementById('password');
            const contadorSenha = document.getElementById('password-contador');
            campoSenha?.addEventListener('input', () => {
                contadorSenha.textContent = `${[...campoSenha.value].length}/64`;
            });
        </script>
    @endpush
</x-layouts.app>
