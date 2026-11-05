<x-layouts.app title="Login - OpenAir Metrics">
    <div class="flex items-center justify-center min-h-screen bg-athens-gray-100 dark:bg-athens-gray-950 relative w-full h-full">
        
        <!-- Cartão de Login -->
        <div class="w-full max-w-md bg-white/95 dark:bg-athens-gray-900/95 backdrop-blur-md shadow-2xl rounded-xl border border-athens-gray-200 dark:border-athens-gray-800 overflow-hidden z-10 mx-4">
            
            <!-- Cabeçalho do Cartão -->
            <div class="bg-athens-gray-50 dark:bg-athens-gray-900 border-b border-athens-gray-200 dark:border-athens-gray-800 p-6 flex flex-col items-center">
                <img src="{{ asset('images/3.png') }}" alt="Logo OpenAir Metrics" class="h-14 w-auto mb-4 drop-shadow-sm dark:hidden">
                <img src="{{ asset('images/3 - dark.png') }}" alt="Logo OpenAir Metrics" class="h-14 w-auto mb-4 drop-shadow-sm hidden dark:block">
                <h2 class="font-bold text-xl text-athens-gray-900 dark:text-white leading-tight tracking-tight">Acesso ao Sistema</h2>
                <p class="text-sm text-athens-gray-600 dark:text-athens-gray-400 mt-1">Insira suas credenciais para continuar</p>
            </div>

            <!-- Formulário -->
            <div class="p-6">
                @if (session('status'))
                    <div role="status" class="mb-4 p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-800 text-sm text-emerald-800 dark:text-emerald-200">
                        {{ session('status') }}
                    </div>
                @endif

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
                        <div class="relative">
                            <input type="password" id="password" name="password" required
                                class="w-full pl-4 pr-11 py-2 border border-athens-gray-300 dark:border-athens-gray-700 rounded-lg focus:ring-2 focus:ring-blue-dianne-500 focus:border-blue-dianne-500 outline-none transition-all text-athens-gray-900 dark:text-athens-gray-100 bg-white dark:bg-athens-gray-800 placeholder-athens-gray-400 dark:placeholder-athens-gray-500"
                                placeholder="••••••••">
                            <button type="button" data-alternar-senha="password" aria-label="Mostrar senha" aria-pressed="false"
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-athens-gray-500 hover:text-athens-gray-800 dark:hover:text-athens-gray-200 transition">
                                <x-heroicon-o-eye class="w-5 h-5" data-icone="mostrar" />
                                <x-heroicon-o-eye-slash class="w-5 h-5 hidden" data-icone="ocultar" />
                            </button>
                        </div>
                        @error('password')
                            <span class="text-cinnabar-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Lembre-me -->
                    <div class="flex items-center justify-between mt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="rounded border-athens-gray-300 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-blue-dianne-600 focus:ring-blue-dianne-500 w-4 h-4 transition">
                            <span class="text-sm text-athens-gray-700 dark:text-athens-gray-300">Lembrar de mim</span>
                        </label>
                    </div>

                    <!-- Botão de Submit -->
                    <button type="submit"
                        class="mt-4 w-full bg-blue-dianne-600 hover:bg-blue-dianne-700 text-white font-bold py-2.5 px-4 rounded-lg transition-colors shadow-md flex justify-center items-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-dianne-500">
                        Entrar
                        <x-heroicon-o-arrow-right class="w-4 h-4" />
                    </button>

                    <!-- Divisor -->
                    <div id="passkey-divider" class="relative flex items-center justify-center my-1 hidden">
                        <div class="border-t border-athens-gray-200 dark:border-athens-gray-800 w-full"></div>
                        <span class="bg-white dark:bg-athens-gray-900 px-3 text-xs text-athens-gray-400 uppercase font-semibold">ou</span>
                        <div class="border-t border-athens-gray-200 dark:border-athens-gray-800 w-full"></div>
                    </div>

                    <!-- Botão Passkey / Biometria (para Administradores e Instaladores) -->
                    <button type="button" id="btn-login-passkey"
                        class="hidden w-full bg-athens-gray-100 hover:bg-athens-gray-200 dark:bg-athens-gray-800 dark:hover:bg-athens-gray-700/80 text-athens-gray-800 dark:text-athens-gray-200 font-semibold py-2.5 px-4 rounded-lg transition-colors border border-athens-gray-300 dark:border-athens-gray-700 flex justify-center items-center gap-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-dianne-500 cursor-pointer">
                        <x-heroicon-o-finger-print class="w-5 h-5 text-blue-dianne-600 dark:text-blue-dianne-400" />
                        <span>Entrar com Passkey / Biometria</span>
                    </button>
                    <p id="passkey-error" class="hidden text-cinnabar-500 text-xs text-center mt-0.5"></p>
                    @if(session('authenticatePasskey::message'))
                        <p class="text-cinnabar-500 text-xs text-center mt-0.5">
                            {{ session('authenticatePasskey::message') }}
                        </p>
                    @endif

                    <form id="passkey-login-form" method="POST" action="{{ route('passkeys.login') }}" class="hidden">
                        @csrf
                        <input type="hidden" name="start_authentication_response" id="passkey-start-auth-response">
                        <input type="hidden" name="remember" id="passkey-remember">
                    </form>

                    <!-- Recuperação de senha -->
                    @if (Route::has('password.request'))
                        <p class="text-sm text-center text-athens-gray-600 dark:text-athens-gray-400">
                            Não lembra a senha?
                            <a href="{{ route('password.request') }}" id="link-recuperar-senha" class="text-blue-dianne-600 dark:text-blue-dianne-400 hover:text-blue-dianne-800 dark:hover:text-blue-dianne-300 font-medium underline-offset-2 hover:underline transition">Recupere aqui</a>
                        </p>
                    @endif
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

    @push('scripts')
        <script>
            // Alternar visualização da senha digitada (NIST SP 800-63B)
            document.querySelectorAll('[data-alternar-senha]').forEach((botao) => {
                botao.addEventListener('click', () => {
                    const campo = document.getElementById(botao.dataset.alternarSenha);
                    const exibir = campo.type === 'password';

                    campo.type = exibir ? 'text' : 'password';
                    botao.setAttribute('aria-pressed', String(exibir));
                    botao.setAttribute('aria-label', exibir ? 'Ocultar senha' : 'Mostrar senha');
                    botao.querySelector('[data-icone="mostrar"]').classList.toggle('hidden', exibir);
                    botao.querySelector('[data-icone="ocultar"]').classList.toggle('hidden', !exibir);
                });
            });

            // Leva o e-mail já digitado no login para a tela de recuperação de senha.
            document.getElementById('link-recuperar-senha')?.addEventListener('click', (event) => {
                const email = document.getElementById('email')?.value.trim();

                if (! email) {
                    return;
                }

                event.preventDefault();
                const url = new URL(event.currentTarget.href);
                url.searchParams.set('email', email);
                window.location.href = url.toString();
            });

            // Autenticação biométrica via Passkey (WebAuthn)
            const btnPasskey = document.getElementById('btn-login-passkey');
            const passkeyDivider = document.getElementById('passkey-divider');
            const passkeyError = document.getElementById('passkey-error');

            if (window.PublicKeyCredential && btnPasskey && passkeyDivider) {
                btnPasskey.classList.remove('hidden');
                passkeyDivider.classList.remove('hidden');

                btnPasskey.addEventListener('click', async () => {
                    passkeyError.classList.add('hidden');
                    passkeyError.textContent = '';
                    btnPasskey.disabled = true;

                    try {
                        const optRes = await fetch('{{ route('passkeys.authentication_options') }}', {
                            headers: { 'Accept': 'application/json' }
                        });

                        if (! optRes.ok) {
                            throw new Error('Não foi possível iniciar o login com Passkey.');
                        }

                        const options = await optRes.json();

                        const base64UrlToBuffer = (base64url) => {
                            const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
                            const padded = base64.padEnd(base64.length + (4 - base64.length % 4) % 4, '=');
                            const binary = atob(padded);
                            const bytes = new Uint8Array(binary.length);
                            for (let i = 0; i < binary.length; i++) {
                                bytes[i] = binary.charCodeAt(i);
                            }
                            return bytes.buffer;
                        };

                        const bufferToBase64Url = (buffer) => {
                            const bytes = new Uint8Array(buffer);
                            let binary = '';
                            for (let i = 0; i < bytes.byteLength; i++) {
                                binary += String.fromCharCode(bytes[i]);
                            }
                            return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
                        };

                        const requestOptions = {
                            ...options,
                            challenge: base64UrlToBuffer(options.challenge),
                            allowCredentials: (options.allowCredentials || []).map(c => ({
                                ...c,
                                id: base64UrlToBuffer(c.id)
                            }))
                        };

                        const assertion = await navigator.credentials.get({ publicKey: requestOptions });

                        if (! assertion) {
                            throw new Error('Nenhuma credencial foi fornecida.');
                        }

                        const credentialPayload = assertion.toJSON ? assertion.toJSON() : {
                            id: assertion.id,
                            rawId: bufferToBase64Url(assertion.rawId),
                            type: assertion.type,
                            response: {
                                clientDataJSON: bufferToBase64Url(assertion.response.clientDataJSON),
                                authenticatorData: bufferToBase64Url(assertion.response.authenticatorData),
                                signature: bufferToBase64Url(assertion.response.signature),
                                userHandle: assertion.response.userHandle ? bufferToBase64Url(assertion.response.userHandle) : null
                            }
                        };

                        const form = document.getElementById('passkey-login-form');
                        document.getElementById('passkey-start-auth-response').value = JSON.stringify(credentialPayload);
                        document.getElementById('passkey-remember').value = document.querySelector('input[name="remember"]')?.checked ? '1' : '0';
                        form.submit();

                    } catch (err) {
                        console.error('Erro no login com Passkey:', err);
                        passkeyError.textContent = err.name === 'NotAllowedError'
                            ? 'Operação biométrica cancelada ou expirada.'
                            : (err.message || 'Falha ao autenticar com Passkey.');
                        passkeyError.classList.remove('hidden');
                        btnPasskey.disabled = false;
                    }
                    } finally {
                        btnPasskey.disabled = false;
                    }
                });
            }
        </script>
    @endpush
</x-layouts.app>