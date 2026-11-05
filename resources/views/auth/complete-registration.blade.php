<x-layouts.app title="Completar Cadastro - OpenAir Metrics">
    <div class="flex items-center justify-center min-h-screen bg-athens-gray-100 dark:bg-athens-gray-950 relative w-full h-full py-12">

        <!-- Cartão de Conclusão de Cadastro -->
        <div class="w-full max-w-lg bg-white/95 dark:bg-athens-gray-900/95 backdrop-blur-md shadow-2xl rounded-xl border border-athens-gray-200 dark:border-athens-gray-800 overflow-hidden z-10 mx-4">

            <!-- Cabeçalho do Cartão -->
            <div class="bg-athens-gray-50 dark:bg-athens-gray-900 border-b border-athens-gray-200 dark:border-athens-gray-800 p-6 flex flex-col items-center">
                <img src="{{ asset('images/3.png') }}" alt="Logo OpenAir Metrics" class="h-14 w-auto mb-4 drop-shadow-sm dark:hidden">
                <img src="{{ asset('images/3 - dark.png') }}" alt="Logo OpenAir Metrics" class="h-14 w-auto mb-4 drop-shadow-sm hidden dark:block">
                <h2 class="font-bold text-xl text-athens-gray-900 dark:text-white leading-tight tracking-tight">Ativação de Conta</h2>
                <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mt-1 text-center">Defina sua senha de acesso e informe seu endereço para concluir o cadastro</p>
            </div>

            <!-- Formulário -->
            <div class="p-6">
                <form method="POST" action="{{ route('cadastro.salvar') }}" class="flex flex-col gap-5">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <!-- E-mail -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-athens-gray-800 dark:text-athens-gray-200 mb-1">E-mail Cadastrado</label>
                        <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required readonly
                            class="w-full px-4 py-2 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg bg-athens-gray-100 dark:bg-athens-gray-800/60 text-athens-gray-700 dark:text-athens-gray-300 cursor-not-allowed">
                        @error('email')
                            <span class="text-cinnabar-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Senha -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-athens-gray-800 dark:text-athens-gray-200 mb-1">
                            Senha de Acesso <span class="text-cinnabar-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" id="password" name="password" required autofocus autocomplete="new-password" minlength="15" maxlength="64"
                                aria-describedby="password-ajuda"
                                class="w-full pl-4 pr-11 py-2 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 outline-none transition-all text-athens-gray-900 dark:text-athens-gray-100 bg-white dark:bg-athens-gray-800 placeholder-athens-gray-400 dark:placeholder-athens-gray-500"
                                placeholder="Mínimo 15 caracteres">
                            <button type="button" data-alternar-senha="password" aria-label="Mostrar senha" aria-pressed="false"
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-athens-gray-500 hover:text-athens-gray-800 dark:hover:text-athens-gray-200 transition">
                                <x-heroicon-o-eye class="w-5 h-5" data-icone="mostrar" />
                                <x-heroicon-o-eye-slash class="w-5 h-5 hidden" data-icone="ocultar" />
                            </button>
                        </div>
                        <div id="password-ajuda" class="flex justify-between gap-2 mt-1 text-xs text-athens-gray-500 dark:text-athens-gray-400">
                            <span>Use de 15 a 64 caracteres. Uma frase memorável é segura e fácil de lembrar.</span>
                            <span id="password-contador" aria-live="polite" class="shrink-0 tabular-nums">0/64</span>
                        </div>
                        @error('password')
                            <span class="text-cinnabar-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Confirmar Senha -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-athens-gray-800 dark:text-athens-gray-200 mb-1">
                            Confirmar Senha <span class="text-cinnabar-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" minlength="15" maxlength="64"
                                class="w-full pl-4 pr-11 py-2 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 outline-none transition-all text-athens-gray-900 dark:text-athens-gray-100 bg-white dark:bg-athens-gray-800 placeholder-athens-gray-400 dark:placeholder-athens-gray-500"
                                placeholder="Repita a nova senha">
                            <button type="button" data-alternar-senha="password_confirmation" aria-label="Mostrar senha" aria-pressed="false"
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-athens-gray-500 hover:text-athens-gray-800 dark:hover:text-athens-gray-200 transition">
                                <x-heroicon-o-eye class="w-5 h-5" data-icone="mostrar" />
                                <x-heroicon-o-eye-slash class="w-5 h-5 hidden" data-icone="ocultar" />
                            </button>
                        </div>
                    </div>

                    <!-- Seção de Endereço (Opcional) -->
                    <div class="pt-2 border-t border-athens-gray-200 dark:border-athens-gray-800">
                        <h3 class="text-sm font-bold text-blue-dianne-950 dark:text-white mb-3">Endereço Cadastral (Opcional)</h3>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <label for="cep" class="block text-xs font-semibold text-athens-gray-700 dark:text-athens-gray-300 mb-1">CEP</label>
                                <input type="text" id="cep" name="cep" value="{{ old('cep') }}" placeholder="00000-000" maxlength="9"
                                    class="w-full px-3 py-2 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>

                            <div class="sm:col-span-2">
                                <label for="logradouro" class="block text-xs font-semibold text-athens-gray-700 dark:text-athens-gray-300 mb-1">Logradouro (Rua, Av.)</label>
                                <input type="text" id="logradouro" name="logradouro" value="{{ old('logradouro') }}" placeholder="Rua das Flores" maxlength="255"
                                    class="w-full px-3 py-2 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>

                            <div>
                                <label for="numero" class="block text-xs font-semibold text-athens-gray-700 dark:text-athens-gray-300 mb-1">Número</label>
                                <input type="text" id="numero" name="numero" value="{{ old('numero') }}" placeholder="123" maxlength="20"
                                    class="w-full px-3 py-2 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>

                            <div>
                                <label for="complemento" class="block text-xs font-semibold text-athens-gray-700 dark:text-athens-gray-300 mb-1">Complemento</label>
                                <input type="text" id="complemento" name="complemento" value="{{ old('complemento') }}" placeholder="Apto 42" maxlength="255"
                                    class="w-full px-3 py-2 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>

                            <div>
                                <label for="bairro" class="block text-xs font-semibold text-athens-gray-700 dark:text-athens-gray-300 mb-1">Bairro</label>
                                <input type="text" id="bairro" name="bairro" value="{{ old('bairro') }}" placeholder="Centro" maxlength="255"
                                    class="w-full px-3 py-2 text-sm bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>

                            <div class="sm:col-span-3">
                                <label for="estado" class="block text-xs font-semibold text-athens-gray-700 dark:text-athens-gray-300 mb-1">Estado (UF)</label>
                                <input type="text" id="estado" name="estado" value="{{ old('estado') }}" placeholder="SP" maxlength="2"
                                    class="w-24 px-3 py-2 text-sm uppercase bg-white dark:bg-athens-gray-800 text-athens-gray-900 dark:text-athens-gray-100 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 transition">
                            </div>
                        </div>
                    </div>

                    <!-- Botão de Submit -->
                    <button type="submit"
                        class="mt-2 w-full bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors shadow-md flex justify-center items-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-dianne-500">
                        Ativar Conta e Entrar
                        <x-heroicon-o-check-circle class="w-5 h-5" />
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
            // Botões "mostrar/ocultar senha".
            document.querySelectorAll('[data-alternar-senha]').forEach((botao) => {
                botao.addEventListener('click', () => {
                    const campo = document.getElementById(botao.dataset.alternarSenha);
                    const exibir = campo.type === 'password';

                    campo.type = exibir ? 'text' : 'password';
                    botao.setAttribute('aria-pressed', String(exibir));
                    botao.setAttribute('aria-label', exibir ? 'Ocultar senha' : 'Mostrar senha');
                    botao.querySelector('[data-icone="mostrar"]').classList.toggle('hidden', exibir);
                    botao.querySelector('[data-icone="ocultar"]').classList.toggle('hidden', ! exibir);
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
