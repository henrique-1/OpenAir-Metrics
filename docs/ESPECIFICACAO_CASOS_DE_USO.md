# Documento de Especificação de Casos de Uso — OpenAir-Metrics

---

## Catálogo de Atores Identificados

| Ator | Tipo | Descrição e Responsabilidades no Código |
| :--- | :--- | :--- |
| **Visitante** | Humano | Usuário não autenticado que acessa o mapa público de qualidade do ar ([`HomeController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/HomeController.php#L11-L117)) e a tela de autenticação ([`AuthController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/AuthController.php#L9-L73)). |
| **Super-Usuário (`superadmin`)** | Humano | Gestor global restrito exclusivamente ao gerenciamento de contas de Administradores Municipais ([`UsuarioController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L14-L361)). |
| **Administrador Municipal (`administrador`)** | Humano | Gestor de um município (`cidade_id`) com permissão sobre usuários locais, estações, planejamento de malha, patrimônio, instalações e dashboard. |
| **Planejador Técnico (`cadastrador` / `planejador`)** | Humano | Técnico responsável pelo cadastro individual de estações, planejamento de malha viária, gestão de estoque de patrimônio e solicitação/execução de substituição de sensores. |
| **Instalador (`instalador`)** | Humano | Técnico de campo responsável por consultar roteiros de instalação, vincular MAC/Patrimônio em campo ([`InstalacaoController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L14-L322)) e substituir sensores. |
| **Sensor IoT (Estação em Campo)** | Hardware Externo | Dispositivo físico embarcado que transmite leituras ambientais periódicas via HTTP `POST /api/medicoes` ([`MedicaoApiController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/Api/MedicaoApiController.php#L12-L113)). |
| **APIs OpenStreetMap (Nominatim / Overpass)** | Sistema Externo | Serviços externos de geocodificação reversa e extração de geometrias viárias e bairros ([`GeocodingService`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L13-L626), [`PlanejamentoMalhaService`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/PlanejamentoMalhaService.php#L12-L757), [`LocalidadeController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/LocalidadeController.php#L17-L295)). |
| **Sistema (Fila & Broadcasting Reverb)** | Sistema Interno | Subsistema de execução de jobs assíncronos ([`ResolveReverseGeocodingJob`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Jobs/ResolveReverseGeocodingJob.php#L14-L81)) e difusão WebSocket em tempo real ([`NovaMedicaoRecebida`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Events/NovaMedicaoRecebida.php#L14-L79)). |

---

## Especificação Detalhada dos Casos de Uso

### UC01 — Visualizar Mapa Público de Monitoramento em Tempo Real

- **Atores:** Visitante, Usuário Autenticado, Sistema (Broadcasting Reverb).
- **Resumo:** Exibe o mapa público interativo com a localização geográfica e os indicadores ambientais mais recentes (IQA, temperatura, umidade, $\text{PM}_{2.5}$ e $\text{CO}_2$) de todas as estações com medições registradas.
- **Pré-condições:** Nenhuma (acesso público na rota `GET /` em [web.php](file:///home/henrique_1/Documents/OpenAir-Metrics/routes/web.php#L15)).
- **Fluxo Principal (Caminho Feliz):**
  1. O Visitante acessa a página inicial do sistema (`GET /`).
  2. O Sistema ([`HomeController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/HomeController.php#L16-L116)) consulta todas as instâncias de [`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L19-L741) que possuem ao menos um registro em `medicoes`, carregando a relação `ultimaMedicao` e extraindo as coordenadas espaciais (`ST_AsText`, `ST_X`, `ST_Y`).
  3. O Sistema calcula ou recupera o IQA da última medição ([`Medicao::calcularIqa`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Medicao.php#L103-L122)) e formata os vetores de dados por camada (`dadosIqa`, `dadosTemperatura`, `dadosUmidade`, `dadosPm`, `dadosCo2`).
  4. O Sistema calcula o centro do mapa (`centroMapa`) através da média aritmética das latitudes e longitudes válidas com nível de zoom `14`.
  5. O Sistema renderiza a view `home` e mantém escuta ativa no canal WebSocket `medicoes` para atualização em tempo real.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Nenhuma estação com medições ou coordenadas válidas (`validPointsCount === 0`):** O Sistema define o ponto central padrão do mapa para as coordenadas `lat: -21.967194`, `lng: -46.812740` com nível de zoom `13` ([`HomeController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/HomeController.php#L102-L106)) e renderiza o mapa sem marcadores.
  - **A2 - Medição sem valor de IQA pré-calculado no banco (`$medicao->iqa === null`):** O Sistema calcula o IQA em tempo de execução via [`Medicao::calcularIqa`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Medicao.php#L103-L122) a partir de `poeira` e `co2` ([`HomeController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/HomeController.php#L55)).
  - **A3 - Banco de dados SQLite (sem funções espaciais nativas):** O Sistema ignora as funções `ST_X`/`ST_Y` do MySQL/MariaDB e extrai latitude e longitude pelo accessor [`Estacao::extractCoordinatesFromAttribute`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L304-L341).
- **Pós-condições:** O mapa público é exibido ao usuário com as leituras mais recentes das estações ativas.

---

### UC02 — Autenticar no Sistema (Login e Logout)

- **Atores:** Visitante (Login), Usuário Autenticado (Logout).
- **Resumo:** Realiza a autenticação de usuários ativos por e-mail e senha com redirecionamento contextual por perfil, bem como o encerramento seguro da sessão.
- **Pré-condições:** Para Login (`GET/POST /login`), o ator deve ser um visitante (`guest`). Para Logout (`POST /logout`), o ator deve estar autenticado (`auth`).
- **Fluxo Principal (Caminho Feliz):**
  1. O Visitante submete o formulário de login (`POST /login`) com `email`, `password` e a opção `remember`.
  2. O Sistema ([`AuthController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/AuthController.php#L23-L56)) valida o formato de e-mail e a presença da senha.
  3. O Sistema acrescenta a restrição obrigatória `ativo = true` às credenciais e verifica a autenticação (`Auth::attempt`).
  4. O Sistema regenera o ID da sessão (`$request->session()->regenerate()`) para prevenção de *Session Fixation*.
  5. O Sistema redireciona o usuário para o painel padrão (`route('dashboard')`) ou URL pretendida.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Redirecionamento de Super-Usuário:** Se o usuário autenticado possuir perfil `superadmin` ou `super_administrador` ([`User::isSuperAdmin`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/User.php#L65-L68)), o Sistema redireciona para `route('usuarios.index')` ([`AuthController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/AuthController.php#L40-L42)).
  - **A2 - Redirecionamento de Instalador:** Se o usuário autenticado possuir perfil `instalador` ([`User::isInstalador`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/User.php#L90-L93)), o Sistema redireciona para `route('instalacoes.index')` ([`AuthController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/AuthController.php#L44-L46)).
  - **A3 - Credenciais inválidas ou conta inativa (`ativo = false`):** O Sistema recusa o login, preserva apenas o campo `email` e retorna à tela anterior com a mensagem de erro informando que as credenciais não correspondem ou a conta está desativada ([`AuthController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/AuthController.php#L53-L55)).
  - **A4 - Limite de tentativas excedido (*Rate Limiting*):** Se o Visitante realizar mais de 5 submissões em 1 minuto (`throttle:5,1` em [web.php](file:///home/henrique_1/Documents/OpenAir-Metrics/routes/web.php#L25)), o Sistema bloqueia a requisição com status `HTTP 429 Too Many Requests`.
  - **A5 - Encerramento de Sessão (`POST /logout`):** O Usuário Autenticado solicita logout; o Sistema ([`AuthController::destroy`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/AuthController.php#L61-L72)) executa `Auth::logout()`, invalida a sessão, regenera o token CSRF e redireciona para `/`.
- **Pós-condições:** Sessão autenticada iniciada (no login) ou invalidada com token CSRF renovado (no logout).

---

### UC03 — Gerenciar Perfil Próprio

- **Atores:** Usuário Autenticado.
- **Resumo:** Permite ao usuário autenticado visualizar e atualizar seus dados cadastrais e endereço residencial/profissional.
- **Pré-condições:** O usuário deve estar autenticado no sistema (`middleware('auth')`).
- **Fluxo Principal (Caminho Feliz):**
  1. O Usuário Autenticado acessa a tela de perfil (`GET /perfil` em [`PerfilController::edit`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PerfilController.php#L15-L25)) e envia as alterações via `PUT /perfil`.
  2. O Sistema ([`PerfilController::update`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PerfilController.php#L30-L59)) valida `name` (obrigatório, máx. 255) e os campos opcionais de endereço (`logradouro`, `numero`, `complemento`, `bairro`, `estado` com máx. 2 caracteres, `cep` com máx. 9 caracteres).
  3. O Sistema atualiza o registro em [`User`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/User.php#L30-L105) e redireciona para `route('perfil.edit')` com mensagem de sucesso.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Usuário já possui município vinculado (`$user->cidade_id != null`):** O Sistema ignora e remove qualquer parâmetro `cidade_id` enviado na requisição (`unset($validated['cidade_id'])`), impedindo a troca de jurisdição pelo próprio usuário ([`PerfilController::update`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PerfilController.php#L52-L54)).
  - **A2 - Usuário ainda não possui município vinculado (`! $user->cidade_id`):** O Sistema habilita a regra `'cidade_id' => ['nullable', 'exists:cidades,id']` e permite a associação inicial ([`PerfilController::update`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PerfilController.php#L45-L47)).
  - **A3 - Falha de validação de campos:** O Sistema retorna erro de validação e redireciona de volta ao formulário mantendo os dados submetidos.
- **Pós-condições:** Dados cadastrais do usuário atualizados no banco de dados.

---

### UC04 — Gerenciar Usuários e Administradores Municipais

- **Atores:** Super-Usuário (`superadmin`), Administrador Municipal (`administrador`).
- **Resumo:** Permite listar, filtrar, cadastrar, editar e alternar o status ativo/inativo de contas de usuários, respeitando a hierarquia administrativa e a regra de sucessão municipal.
- **Pré-condições:** O usuário deve estar autenticado com perfil `superadmin` ou `administrador` ([`UsuarioController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L14-L361)).
- **Fluxo Principal (Caminho Feliz):**
  1. O Ator acessa a listagem paginada de usuários (`GET /usuarios` em [`UsuarioController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L19-L93)), podendo filtrar por busca textual (`name`, `email`), `status` (`ativo`/`inativo`), `cidade_id` (se `superadmin`) ou `nivel` (se `administrador`).
  2. O Ator acessa o formulário (`GET /usuarios/create`) e submete os dados de um novo usuário (`POST /usuarios` em [`UsuarioController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L132-L199)).
  3. O Sistema valida a unicidade do `email`, a confirmação de senha (`min:8`, `confirmed`) e os atributos de escopo:
     - Se `superadmin`: define `nivel = 'administrador'`, valida que `cidade_id` pertence ao `estado_id` selecionado e vincula ao município escolhido.
     - Se `administrador`: força `cidade_id = $currentUser->cidade_id` e valida `nivel` em `['administrador', 'cadastrador', 'instalador']`.
  4. O Sistema cria o registro em [`User`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/User.php#L30-L105) com `ativo = true` e redireciona para `route('usuarios.index')`.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Usuário sem permissão administrativa (`! isSuperAdmin() && ! isAdministrador()`):** O Sistema interrompe imediatamente a requisição com `HTTP 403 Forbidden` ("Acesso restrito a administradores.") em qualquer método de [`UsuarioController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L22-L24).
  - **A2 - Sucessão Administrativa Municipal (`administrador` cadastra outro `administrador`):** Ao criar um novo usuário com `nivel === 'administrador'`, o Sistema desativa a conta do administrador logado (`$currentUser->update(['ativo' => false])`), executa `Auth::logout()`, invalida a sessão, regenera o token CSRF e redireciona para `route('login')` com aviso de sucessão ([`UsuarioController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L189-L196)).
  - **A3 - Alternância de Status (`PATCH /usuarios/{user}/toggle-status`) — Tentativa de auto-desativação:** Se o Ator tentar alterar o status de sua própria conta (`$user->id === $currentUser->id`) ou de outro `superadmin`, o Sistema bloqueia a operação e retorna com mensagem de erro ([`UsuarioController::toggleStatus`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L212-L221)).
  - **A4 - Violação de escopo na edição ou ativação/desativação:**
    - Se o `superadmin` tentar editar ou alternar o status de um usuário que não seja `administrador`, o Sistema retorna `HTTP 403` ([`UsuarioController::toggleStatus`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L215-L217), [`UsuarioController::edit`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L251-L253)).
    - Se o `administrador` tentar editar ou alternar o status de um usuário de outra cidade (`$user->cidade_id !== $currentUser->cidade_id`) ou de nível fora de `['cadastrador', 'instalador']` (exceto si mesmo na edição), o Sistema retorna `HTTP 403` ([`UsuarioController::toggleStatus`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L222-L224), [`UsuarioController::update`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L325-L333)).
  - **A5 - Administrador editando a própria conta (`$isSelf === true` em [`UsuarioController::update`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L329-L351)):** O Sistema não permite que ele altere seu próprio `email` ou `nivel`, atualizando apenas nome, endereço e senha (se preenchida).
- **Pós-condições:** Conta de usuário criada, atualizada ou com status `ativo` alternado; em caso de sucessão municipal, o novo administrador assume e o anterior fica inativo.

---

### UC05 — Consultar Dashboard Analítico e Gráficos de Séries Temporais

- **Atores:** Administrador Municipal, Planejador Técnico, Instalador.
- **Resumo:** Apresenta indicadores consolidados de estações, contadores de alertas críticos e gráficos de séries temporais com granularidade adaptativa por localidade, métrica e período.
- **Pré-condições:** O usuário deve estar autenticado e **não** possuir perfil `superadmin`.
- **Fluxo Principal (Caminho Feliz):**
  1. O Ator acessa o painel (`GET /dashboard` em [`DashboardController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/DashboardController.php#L20-L66)).
  2. O Sistema filtra estações, medições, cidades e bairros pela jurisdição do usuário (`$user->cidade_id`), contabiliza os alertas (`poeira > 50`, `co2 > 1000`, `temperatura > 35` ou `< 10`, `umidade < 30` ou `> 85`) e exibe a view `dashboard`.
  3. O Ator solicita dados de séries temporais (`GET /dashboard/graficos` em [`DashboardController::dadosGrafico`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/DashboardController.php#L71-L275)) informando `tipo_agrupamento` (`cidade` ou `bairro`), `localidade_id`, `metrica` e `periodo` (`24h`, `7d`, `30d`, `todos`).
  4. O Sistema filtra as medições do período, determina o bucket temporal adaptativo ([`DashboardController::determinarBucketTemporal`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/DashboardController.php#L323-L386)) conforme a amplitude em minutos (`5s`, `15s`, `1 min`, `5 min`, `15 min`, `1h` ou `1 dia`), calcula média, máximo e mínimo por intervalo e classifica a média geral ([`DashboardController::obterClassificacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/DashboardController.php#L282-L316)).
  5. O Sistema retorna o payload JSON estruturado para renderização do gráfico.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Acesso por Super-Usuário (`$user?->isSuperAdmin()`):** O Sistema bloqueia o acesso com `HTTP 403 Forbidden` ("O super-usuário só pode gerenciar administradores.") tanto em `index` quanto em `dadosGrafico` ([`DashboardController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/DashboardController.php#L23-L25), [`DashboardController::dadosGrafico`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/DashboardController.php#L74-L76)).
  - **A2 - Nenhuma localidade informada (`! $localidadeId`):** O Sistema seleciona automaticamente a primeira cidade disponível com estações cadastradas dentro da jurisdição do usuário ([`DashboardController::dadosGrafico`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/DashboardController.php#L111-L122)).
  - **A3 - Ausência de medições no período (`$medicoes->isEmpty()`):** O Sistema retorna um JSON com vetores vazios (`labels: []`, `valores: []`), estatísticas zeradas (`media: 0.0`, `total_leituras: 0`) e `status_classificacao: 'Sem Dados'` ([`DashboardController::dadosGrafico`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/DashboardController.php#L182-L204)).
- **Pós-condições:** Indicadores e séries temporais exibidos de acordo com o recorte geográfico e temporal selecionado.

---

### UC06 — Consultar e Filtrar Estações de Monitoramento

- **Atores:** Administrador Municipal, Planejador Técnico, Instalador.
- **Resumo:** Lista todas as estações do município (ou criadas pelo usuário), exibindo status de instalação, pendências de substituição e cálculo de vida útil dos sensores.
- **Pré-condições:** O usuário deve estar autenticado e não ser `superadmin`.
- **Fluxo Principal (Caminho Feliz):**
  1. O Ator acessa `GET /estacoes` ([`EstacaoController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L22-L111)) informando parâmetros opcionais de `busca`, `tipo`, `status` (`instalada`, `pendente`, `substituicao`), `sort` e `direction`.
  2. O Sistema restringe a consulta ao `cidade_id` do usuário (ou `created_by` caso o usuário não tenha cidade associada).
  3. O Sistema aplica os filtros e ordenação, calcula o ciclo de vida útil nominal de 5 anos de cada estação ([`Estacao::calcularVidaUtil`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L465-L511)) e renderiza a view `estacoes.index`.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Acesso por Super-Usuário:** O Sistema aborta com `HTTP 403 Forbidden` ([`EstacaoController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L25-L27)).
  - **A2 - Ordenação por Vida Útil (`$sort === 'vida_util'`):** O Sistema realiza a ordenação em memória sobre a coleção carregada utilizando a chave calculada `porcentagem_restante` ([`EstacaoController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L94-L100)).
  - **A3 - Classificação de Criticidade da Vida Útil ([`Estacao::calcularVidaUtil`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L467-L498)):**
    - Se `data_instalacao` for nula: retorna status `'Não Instalada'` e `100.0%`.
    - Se `dias_restantes <= 0`: retorna status `'Vencida'` e `0%`.
    - Se `dias_restantes <= 180`: retorna status `'Crítica (< 6 meses)'`.
- **Pós-condições:** A listagem de estações e seus indicadores de saúde operacional são apresentados ao usuário.

---

### UC07 — Cadastrar Estação Individualmente

- **Atores:** Planejador Técnico, Administrador Municipal, APIs OpenStreetMap (Nominatim), Sistema (Fila).
- **Resumo:** Realiza o cadastro manual de uma Estação Matriz ou Estação Satélite no mapa, validando jurisdição, alcance máximo de 200m, geocodificação reversa e vínculo automático com o patrimônio.
- **Pré-condições:** O usuário deve estar autenticado com perfil `cadastrador`/`planejador` ou `administrador`.
- **Fluxo Principal (Caminho Feliz):**
  1. O Ator acessa o formulário (`GET /estacoes/create` em [`EstacaoController::create`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L116-L161)) e submete os dados da nova estação (`POST /estacoes` em [`EstacaoController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L166-L266)): `mac_address`, `tipo_estacao`, `estacao_origem_id` (opcional), `bairro_id`, `latitude` e `longitude`.
  2. O Sistema ([`StoreEstacaoRequest`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Requests/StoreEstacaoRequest.php#L11-L165)) normaliza o `mac_address` para maiúsculas, valida formato regex, unicidade em `estacoes`, limites geográficos, jurisdição municipal e regras espaciais.
  3. O Sistema consulta os detalhes de endereço via [`GeocodingService::obterDetalhesEndereco`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L442-L524) e substitui o `bairro_id` pelo bairro real retornado pela geocodificação reversa (`Bairro::firstOrCreate`).
  4. O Sistema resolve a topologia de rede (definindo `estacao_origem_id`, `matriz_pai_id` e `distancia_origem_metros`), persiste a [`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L19-L741) convertendo latitude/longitude em geometria `POINT` SRID 4326 e redireciona para `route('estacoes.index')`.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Usuário sem perfil de Planejador Técnico ou Administrador:** O Sistema aborta com `HTTP 403 Forbidden` ("Acesso restrito ao Planejador Técnico.") ([`EstacaoController::create`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L119-L121), [`EstacaoController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L169-L171)).
  - **A2 - Bairro fora da jurisdição municipal do usuário (`$bairro->cidade_id !== $user->cidade_id`):** O Sistema rejeita a requisição com erro de validação em `bairro_id` ([`StoreEstacaoRequest::after`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Requests/StoreEstacaoRequest.php#L91-L101)) ou `HTTP 403` ([`EstacaoController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L177-L182)).
  - **A3 - Cadastro de Satélite sem nenhuma estação prévia no banco (`$menorDistancia === null`):** O Sistema rejeita o cadastro com erro de validação em `tipo_estacao` ("Não é possível cadastrar uma Estação Satélite sem antes ter ao menos uma estação cadastrada no sistema.") ([`StoreEstacaoRequest::after`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Requests/StoreEstacaoRequest.php#L126-L130)).
  - **A4 - Estação Satélite a mais de 200 metros da estação de origem ou de qualquer estação existente (`$dist > 200.0`):** O Sistema rejeita o cadastro com erro de validação no campo `latitude`, informando a distância atual em metros ([`StoreEstacaoRequest::after`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Requests/StoreEstacaoRequest.php#L113-L136)).
  - **A5 - Estação Satélite submetida sem `estacao_origem_id` explícito:** O Sistema ([`EstacaoController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L219-L236)) busca automaticamente a estação já cadastrada mais próxima dentro do raio de $\le 200\text{m}$ e a vincula como origem.
  - **A6 - MAC Address corresponde a um item cadastrado em `patrimonios`:** O Sistema vincula automaticamente `patrimonio_id` à estação criada e atualiza o status do patrimônio para `'Instalada'` ([`EstacaoController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L255-L261)).
- **Pós-condições:** Nova estação persistida com geometria espacial, endereço resolvido, topologia vinculada e patrimônio atualizado (se existente).

---

### UC08 — Simular e Planejar Malha de Estações em Cascata Viária

- **Atores:** Planejador Técnico, Administrador Municipal, APIs OpenStreetMap (Overpass / Nominatim).
- **Resumo:** Calcula automaticamente a disposição ótima de 1 Estação Matriz e de 1 a 20 Estações Satélites sobre o leito de vias públicas urbanas em cascata multidirecional, permitindo também o ajuste fino individual (*Snap to Road*).
- **Pré-condições:** O usuário deve estar autenticado com perfil `cadastrador`/`planejador` ou `administrador`.
- **Fluxo Principal (Caminho Feliz):**
  1. O Ator acessa o planejador interativo (`GET /estacoes/planejar` em [`PlanejamentoController::create`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PlanejamentoController.php#L26-L71)), seleciona um ponto central e solicita o cálculo (`POST /estacoes/calcular-malha` em [`PlanejamentoController::calcular`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PlanejamentoController.php#L76-L103)) enviando `latitude`, `longitude`, `quantidade_satelites` (1 a 20) e `cidade_id`.
  2. O Sistema ([`PlanejamentoMalhaService::calcularMalha`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/PlanejamentoMalhaService.php#L28-L165)) consulta as geometrias viárias locais na tabela `malha_viaria` dentro do raio `(quantidadeSatelites * 200) + 200` metros via [`GeocodingService::buscarViasProximas`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L26-L104), excluindo rodovias (`motorway`, `trunk`, `motorway_link`, `trunk_link`).
  3. O Sistema encaixa a Matriz na rua mais próxima (*Snapping*), obtém seu endereço e bairro via [`GeocodingService::obterDetalhesEndereco`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L442-L524) (`sync: true`) e garante o registro do bairro (`Bairro::firstOrCreate`).
  4. O Sistema ([`PlanejamentoMalhaService::gerarMalhaEmCascataPelasRuas`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/PlanejamentoMalhaService.php#L255-L455)) discretiza os segmentos de rua e distribui as satélites em 2, 3 ou 4 ramos angulares cardeais, avançando em saltos de $100\text{m}$ a $198\text{m}$ que se afastam da Matriz e respeitam o distanciamento *Anti-Overlap* ($\ge 180\text{m}$ a $200\text{m}$ das demais estações).
  5. O Sistema retorna o JSON contendo o objeto `matriz` e o vetor `satelites` posicionados.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Usuário sem permissão (`! isPlanejadorTecnico() && ! isAdministrador()`):** O Sistema retorna `HTTP 403 Forbidden` ([`PlanejamentoController::calcular`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PlanejamentoController.php#L86-L88), [`PlanejamentoController::snapToRoad`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PlanejamentoController.php#L111-L113)).
  - **A2 - Região sem vias na tabela local `malha_viaria` (`empty($ways)`):** O Sistema ([`PlanejamentoMalhaService::obterViasDoOpenStreetMap`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/PlanejamentoMalhaService.php#L172-L218)) consulta os servidores Overpass com rotação de failover (`overpass.private.coffee`, `overpass-api.de`, `maps.mail.ru`) e armazena o resultado em cache por 24h.
  - **A3 - Indisponibilidade total de geometrias viárias (Local e Overpass vazios):** O Sistema executa o algoritmo geométrico de contingência [`PlanejamentoMalhaService::gerarCascataFallback`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/PlanejamentoMalhaService.php#L536-L589), projetando saltos geodésicos de $180\text{m}$ nos eixos cardeais (`0°`, `180°`, `90°`, `270°`).
  - **A4 - Sem ponto de rua estrito no setor angular ou colisão inevitável de raios:** O Sistema testa projeções deslocadas em $175\text{m}$ com variações angulares (`0°`, `±30°`, `±60°`, `±90°`); se todos os pontos colidirem (`tem_colisao === true`), seleciona o ponto que maximiza a distância para as outras estações (`menor_distancia_outras`) ([`PlanejamentoMalhaService::gerarMalhaEmCascataPelasRuas`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/PlanejamentoMalhaService.php#L348-L406), [`GeocodingService::snapToRoad`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L386-L391)).
  - **A5 - Ajuste manual de ponto no mapa (`POST /estacoes/snap-to-road`) sem via no alcance:** Se o Ator arrastar um marcador para uma área onde nenhuma via pública atenda à distância máxima da estação pai, [`PlanejamentoController::snapToRoad`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PlanejamentoController.php#L133-L138) retorna `HTTP 422 Unprocessable Entity` com `'snapped' => false`.
- **Pós-condições:** A topologia sugerida da malha é apresentada visualmente sobre as vias no mapa de planejamento.

---

### UC09 — Salvar Malha Planejada e Gerar Ordem de Instalação

- **Atores:** Planejador Técnico, Administrador Municipal.
- **Resumo:** Persiste em lote a Estação Matriz e as Estações Satélites planejadas com status `'Planejada'`, constrói a árvore de conexões e normaliza a ordem sequencial de instalação em cascata (DFS).
- **Pré-condições:** O usuário deve estar autenticado com perfil `cadastrador`/`planejador` ou `administrador` e possuir uma malha calculada.
- **Fluxo Principal (Caminho Feliz):**
  1. O Ator submete o planejamento (`POST /estacoes/salvar-malha` em [`PlanejamentoController::salvar`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PlanejamentoController.php#L146-L289)) contendo `cidade_id`, `matriz` e `satelites` (1 a 20 itens).
  2. O Sistema decodifica `matriz` e `satelites` (caso enviados como JSON string), valida os dados e verifica a jurisdição municipal do usuário.
  3. O Sistema inicia uma transação de banco de dados (`DB::beginTransaction()`), resolve os bairros (`Bairro::firstOrCreate`) e cria a `Estação Matriz` com `status_instalacao = 'Planejada'`, `ordem_instalacao = 1` e `matriz_pai_id` apontando para seu próprio `private_id`.
  4. O Sistema cria todas as `Estações Satélites` com `status_instalacao = 'Planejada'` e vincula na segunda passagem o `estacao_origem_id` exato conforme o `origem_indice` da árvore.
  5. O Sistema executa [`Estacao::ordenarEmCascata`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L647-L740) (travessia DFS a partir da Matriz) para numerar `ordem_instalacao` sequencialmente de `1` a `N`, confirma a transação (`DB::commit()`) e redireciona para o roteiro da ordem de instalação (`route('instalacoes.show', $matriz->public_id)`).
- **Fluxos Alternativos / Exceções:**
  - **A1 - Tentativa de salvar malha em município fora da jurisdição (`$user->cidade_id !== $cidadeId`):** O Sistema aborta com `HTTP 403 Forbidden` ([`PlanejamentoController::salvar`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PlanejamentoController.php#L153-L155)).
  - **A2 - Quantidade de satélites fora do intervalo `[1, 20]` ou coordenadas ausentes:** O Sistema rejeita a requisição com erro de validação ([`PlanejamentoController::salvar`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PlanejamentoController.php#L171-L179)).
  - **A3 - Exceção de banco de dados durante a gravação:** O bloco `catch (\Throwable $e)` executa `DB::rollBack()` e redireciona de volta com os dados preenchidos e a mensagem do erro ([`PlanejamentoController::salvar`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PlanejamentoController.php#L281-L288)).
- **Pós-condições:** Malha completa gravada com status `'Planejada'` e roteiro de instalação gerado.

---

### UC10 — Gerenciar Inventário de Patrimônio (Individual, Lote e Descarte)

- **Atores:** Planejador Técnico, Administrador Municipal, Instalador (apenas listagem/consulta).
- **Resumo:** Gerencia o ciclo de estoque das placas/sensores IoT do município, permitindo cadastro unitário ou em lote com geração automática de código patrimonial e baixa lógica para descarte.
- **Pré-condições:** O usuário deve estar autenticado (não `superadmin` para listagem; `cadastrador`/`planejador` ou `administrador` para criação e descarte).
- **Fluxo Principal (Caminho Feliz):**
  1. O Ator consulta o estoque (`GET /patrimonios` em [`PatrimonioController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PatrimonioController.php#L19-L83)) visualizando os contadores (`total`, `disponiveis`, `instalados`, `descartados`) restritos à sua jurisdição municipal.
  2. Para cadastro individual (`POST /patrimonios` em [`PatrimonioController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PatrimonioController.php#L110-L154)), o Planejador Técnico informa `cidade_id`, `mac_address`, `data_aquisicao` e `observacoes`.
  3. O Sistema valida o formato MAC e sua unicidade em `patrimonios` e `estacoes`, gera o código sequencial `OAir-Estacao-<cidade_id>-<XXXX>` via [`Patrimonio::gerarProximoCodigo`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Patrimonio.php#L89-L103) e persiste o item com `status = 'Disponível'`.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Cadastro em Lote (`POST /patrimonios/batch` em [`PatrimonioController::storeBatch`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PatrimonioController.php#L159-L242)):** O Ator envia múltiplas linhas de MACs. O Sistema normaliza formatos com hífens ou 12 hexadecimais contínuos (`implode(':', str_split($macLimpo, 2))`), ignora linhas vazias, contabiliza e descarta formatos inválidos (`$invalidos++`) e MACs já existentes em `patrimonios` ou `estacoes` (`$duplicados++`), cadastrando apenas os inéditos e exibindo um resumo detalhado. Se nenhum item válido for cadastrado (`$cadastrados === 0`), retorna alerta de erro.
  - **A2 - Descarte Lógico de Equipamento (`DELETE /patrimonios/{patrimonio}` em [`PatrimonioController::destroy`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PatrimonioController.php#L247-L263)):** O Ator solicita o descarte; o Sistema valida a jurisdição e atualiza `status = 'Descartado'` sem remover o registro físico da tabela.
  - **A3 - Consulta de Patrimônios Disponíveis via API (`GET /patrimonios/disponiveis` em [`PatrimonioController::apiDisponiveis`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PatrimonioController.php#L268-L284)):** Se o usuário autenticado não possuir `cidade_id` e não for `superadmin`, o Sistema retorna um array JSON vazio `[]`.
  - **A4 - Violação de perfil ou jurisdição municipal:** Tentativas de acesso por `superadmin` à listagem, por `instalador` às rotas de criação/descarte, ou envio de `cidade_id` divergente da jurisdição do usuário resultam em `HTTP 403 Forbidden`.
- **Pós-condições:** Equipamentos registrados em estoque com numeração sequencial padronizada ou atualizados para `'Descartado'`.

---

### UC11 — Consultar Ordens de Instalação e Roteiro em Cascata

- **Atores:** Instalador, Planejador Técnico, Administrador Municipal.
- **Resumo:** Lista as malhas planejadas agrupadas por Estação Matriz e exibe o roteiro detalhado passo a passo na ordem topológica de instalação em campo.
- **Pré-condições:** O usuário deve estar autenticado e não possuir perfil `superadmin`.
- **Fluxo Principal (Caminho Feliz):**
  1. O Ator acessa `GET /instalacoes` ([`InstalacaoController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L19-L92)), visualizando as Estações Matrizes paginadas com contagens de `total_satelites`, `instaladas_satelites` e `substituicoes_pendentes`.
  2. O Ator seleciona uma malha (`GET /instalacoes/{public_id}` em [`InstalacaoController::show`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L97-L137)).
  3. O Sistema carrega a Matriz e todas as suas Satélites (`matriz_pai_id`), executa [`Estacao::ordenarEmCascata`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L647-L740) para garantir a sequência DFS e carrega a lista de patrimônios com `status = 'Disponível'` do município.
  4. O Sistema exibe a view `instalacoes.show` com o mapa e o passo a passo de ativação.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Acesso por Super-Usuário ou fora da jurisdição municipal:** O Sistema aborta com `HTTP 403 Forbidden` ([`InstalacaoController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L22-L24), [`InstalacaoController::show`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L100-L108)).
  - **A2 - Estação Matriz não encontrada pelo `public_id`:** O método `firstOrFail()` retorna `HTTP 404 Not Found`.
- **Pós-condições:** O roteiro ordenado em cascata e os equipamentos disponíveis para vinculação são exibidos ao técnico.

---

### UC12 — Ativar Estação e Vincular Equipamento em Campo

- **Atores:** Instalador.
- **Resumo:** Registra a instalação física de uma estação em campo vinculando um Patrimônio ou MAC Address, validando a precedência hierárquica da malha e atualizando o status operacional.
- **Pré-condições:** O usuário deve estar autenticado estritamente com perfil `instalador` ([`User::isInstalador`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/User.php#L90-L93)).
- **Fluxo Principal (Caminho Feliz):**
  1. O Instalador envia uma requisição `POST /instalacoes/{public_id}/vincular-mac` ([`InstalacaoController::vincularMac`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L142-L321)) informando `patrimonio_id`, `numero_patrimonio`, `mac_address` ou um `identificador` genérico.
  2. O Sistema valida a jurisdição municipal, identifica se o valor informado é um MAC Address ou código de patrimônio e verifica se a estação de origem (`estacao_origem_id`) já está instalada.
  3. O Sistema inicia uma transação (`DB::beginTransaction()`), localiza o [`Patrimonio`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Patrimonio.php#L12-L128) e atualiza seu status para `'Instalada'`.
  4. O Sistema atualiza a [`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L19-L741) com `mac_address`, `patrimonio_id`, `status_instalacao = 'Instalada'`, `data_instalacao = now()`, `instalado_por = Auth::id()`, limpa eventuais flags de substituição pendente, confirma a transação e retorna JSON `200 OK` com `'success' => true`.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Ator não possui perfil `instalador` (`! $user?->isInstalador()`):** O Sistema retorna JSON com status `HTTP 403 Forbidden` ("Acesso não autorizado. Apenas técnicos com perfil de Instalador podem registrar estações em campo.") ([`InstalacaoController::vincularMac`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L145-L150)).
  - **A2 - Estação ou Patrimônio pertence a outro município:** O Sistema retorna JSON `HTTP 403` (para estação de outro município) ou `HTTP 422` (para equipamento de patrimônio de outro município) ([`InstalacaoController::vincularMac`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L155-L160), [L223-L228](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L223-L228)).
  - **A3 - Nenhum identificador enviado ou formato MAC inválido:** O Sistema retorna JSON `HTTP 422 Unprocessable Entity` exigindo o Número de Patrimônio ou MAC válido no padrão `AA:BB:CC:DD:EE:FF` ([`InstalacaoController::vincularMac`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L183-L198)).
  - **A4 - Quebra da ordem de precedência (`Estação Satélite` cuja `estacao_origem_id` ainda não está `'Instalada'`):** O Sistema bloqueia a ativação e retorna JSON `HTTP 422` informando que a estação anterior deve ser instalada e ativada antes desta ([`InstalacaoController::vincularMac`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L200-L209)).
  - **A5 - Equipamento informado não existe no estoque de Patrimônio (`! $patrimonio`):** O Sistema cria automaticamente um novo registro em [`Patrimonio`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Patrimonio.php#L12-L128) com `status = 'Instalada'`, `data_aquisicao = now()` e observação de cadastro automático em campo ([`InstalacaoController::vincularMac`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L233-L243)).
  - **A6 - Patrimônio já está instalado em outra estação (`$patrimonio->estacao->private_id !== $estacao->private_id`):** O Sistema executa `DB::rollBack()` e retorna JSON `HTTP 422` indicando em qual estação e endereço o equipamento já está alocado ([`InstalacaoController::vincularMac`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L246-L255)).
  - **A7 - Estação já possuía um patrimônio anterior diferente (Substituição em campo):** O Sistema altera automaticamente o status do patrimônio antigo para `'Descartado'` ([`InstalacaoController::vincularMac`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L268-L277)).
  - **A8 - Erro inesperado de persistência:** O bloco `catch` executa `DB::rollBack()` e retorna JSON com status `HTTP 500` ([`InstalacaoController::vincularMac`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L313-L320)).
- **Pós-condições:** Estação ativada em campo (`status_instalacao = 'Instalada'`), apta a receber telemetria IoT, e patrimônio vinculado.

---

### UC13 — Solicitar Substituição de Sensores de Estação

- **Atores:** Administrador Municipal, Planejador Técnico.
- **Resumo:** Registra uma ordem de manutenção/substituição preventiva ou corretiva para os sensores de uma estação instalada em campo.
- **Pré-condições:** O usuário deve estar autenticado com perfil `administrador` ou `cadastrador`/`planejador`, e a estação deve estar instalada (`data_instalacao != null`).
- **Fluxo Principal (Caminho Feliz):**
  1. O Ator solicita a substituição (`POST /estacoes/{public_id}/solicitar-substituicao` em [`EstacaoController::solicitarSubstituicao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L271-L322)) enviando o `motivo_substituicao`.
  2. O Sistema verifica a permissão do usuário, a jurisdição municipal e calcula o percentual restante de vida útil da estação ([`Estacao::calcularVidaUtil`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L465-L511)).
  3. O Sistema valida o `motivo_substituicao` e atualiza a [`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L19-L741) com `solicitacao_substituicao = true`, `solicitacao_substituicao_em = now()`, `motivo_substituicao` e `solicitado_por = $user->id`.
  4. O Sistema redireciona para `route('estacoes.index')` com mensagem de confirmação.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Usuário sem permissão (ex: `instalador` ou `superadmin`) ou fora da jurisdição:** O Sistema aborta com `HTTP 403 Forbidden` ([`EstacaoController::solicitarSubstituicao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L274-L283)).
  - **A2 - Estação ainda não instalada em campo (`! $estacao->data_instalacao`):** O Sistema impede a operação e redireciona para `route('estacoes.index')` com mensagem de erro ([`EstacaoController::solicitarSubstituicao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L286-L290)).
  - **A3 - Vida útil restante maior que 20% (`porcentagem_restante > 20`) e motivo ausente:** O Sistema torna o campo `motivo_substituicao` obrigatório (`required|string|min:3|max:1000`) e rejeita a requisição caso não seja preenchido ([`EstacaoController::solicitarSubstituicao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L293-L301)).
  - **A4 - Vida útil restante $\le 20\%$ e motivo em branco:** O Sistema aceita o campo vazio e preenche automaticamente `motivo_substituicao = 'Fim da vida útil da estação'` ([`EstacaoController::solicitarSubstituicao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L303-L306)).
- **Pós-condições:** A estação passa a exibir pendência ativa de substituição de sensores (`solicitacao_substituicao = true`).

---

### UC14 — Efetivar Substituição de Sensor de Estação

- **Atores:** Instalador, Planejador Técnico, Administrador Municipal.
- **Resumo:** Conclui a substituição do hardware de uma estação, alocando um novo equipamento disponível do patrimônio e descartando o equipamento anterior.
- **Pré-condições:** O usuário deve estar autenticado com perfil `instalador`, `cadastrador`/`planejador` ou `administrador`, e deve existir um item de [`Patrimonio`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Patrimonio.php#L12-L128) com status `'Disponível'`.
- **Fluxo Principal (Caminho Feliz):**
  1. O Ator submete o formulário de substituição (`POST /estacoes/{public_id}/substituir-sensor` em [`EstacaoController::substituirSensor`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L329-L400)) informando o `patrimonio_id` do novo equipamento.
  2. O Sistema valida a jurisdição da estação e do novo patrimônio e confirma que o novo patrimônio está com `status === 'Disponível'`.
  3. O Sistema inicia uma transação (`DB::beginTransaction()`), marca o patrimônio antigo como `'Descartado'` e atualiza o novo patrimônio para `'Instalada'`.
  4. O Sistema atualiza a [`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L19-L741) com o novo `patrimonio_id` e `mac_address`, renova `data_instalacao = now()`, redefine `solicitacao_substituicao = false` (limpando data, motivo e solicitante), confirma a transação e redireciona para `route('estacoes.index')`.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Acesso não autorizado ou estação de outro município:** O Sistema aborta com `HTTP 403 Forbidden` ([`EstacaoController::substituirSensor`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L331-L340)).
  - **A2 - Novo patrimônio pertence a outro município:** O Sistema redireciona para `route('estacoes.index')` com mensagem de erro de jurisdição ([`EstacaoController::substituirSensor`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L350-L354)).
  - **A3 - Novo patrimônio não está com status `'Disponível'`:** O Sistema impede a troca e redireciona com mensagem de erro ([`EstacaoController::substituirSensor`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L356-L360)).
  - **A4 - Falha transacional no banco de dados:** O bloco `catch` executa `DB::rollBack()` e redireciona com a descrição do erro ([`EstacaoController::substituirSensor`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L393-L399)).
- **Pós-condições:** Patrimônio antigo marcado como `'Descartado'`, novo patrimônio vinculado como `'Instalada'`, ciclo de vida útil da estação reiniciado e pendência de substituição encerrada.

---

### UC15 — Enviar e Processar Telemetria Ambiental (Sensor IoT)

- **Atores:** Sensor IoT (Estação em Campo), Sistema (Broadcasting Reverb).
- **Resumo:** Recebe os pacotes de telemetria enviados pelas estações em campo, valida a autenticidade e os limites físicos dos sensores, calcula o IQA, persiste a leitura e transmite o evento em tempo real.
- **Pré-condições:** A estação correspondente ao MAC Address deve estar cadastrada e com `status_instalacao === 'Instalada'`.
- **Fluxo Principal (Caminho Feliz):**
  1. O Sensor IoT envia uma requisição `POST /api/medicoes` ([`MedicaoApiController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/Api/MedicaoApiController.php#L17-L112)) com o cabeçalho `X-Sensor-Key` e o payload JSON contendo `mac_address`, `temperatura`, `umidade`, `co2` e `poeira`.
  2. O Sistema valida os intervalos de leitura (`temperatura: -50..100`, `umidade: 0..100`, `co2: 0..50000`, `poeira: 0..5000`) e verifica a chave `X-Sensor-Key` via `hash_equals()`.
  3. O Sistema normaliza o `mac_address` e localiza a [`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L19-L741) correspondente (buscando pelo MAC direto na estação ou através da relação `patrimonio`).
  4. O Sistema calcula o Índice de Qualidade do Ar via [`Medicao::calcularIqa`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Medicao.php#L103-L122) e cria o registro em [`Medicao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Medicao.php#L11-L150).
  5. O Sistema dispara imediatamente o evento [`NovaMedicaoRecebida`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Events/NovaMedicaoRecebida.php#L14-L79) (`ShouldBroadcastNow`) no canal `medicoes` e retorna resposta JSON com status `HTTP 201 Created`.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Payload inválido ou leituras fora das faixas físicas:** O Sistema retorna JSON com status `HTTP 422 Unprocessable Entity` (configurado globalmente para rotas `api/*` em [bootstrap/app.php](file:///home/henrique_1/Documents/OpenAir-Metrics/bootstrap/app.php#L19-L22)).
  - **A2 - Chave de API ausente ou inválida (`! hash_equals($expectedKey, $sensorKey)`):** Quando `TELEMETRY_API_KEY` está configurada e o cabeçalho `X-Sensor-Key` diverge ou está ausente, o Sistema recusa a requisição com status `HTTP 401 Unauthorized` ([`MedicaoApiController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/Api/MedicaoApiController.php#L32-L39)).
  - **A3 - Estação não encontrada para o MAC informado (`! $estacao`):** O Sistema retorna JSON com status `HTTP 404 Not Found` ("Estação não encontrada com o endereço MAC informado.") ([`MedicaoApiController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/Api/MedicaoApiController.php#L62-L67)).
  - **A4 - Estação ainda não ativada em campo (`$estacao->status_instalacao !== 'Instalada'`):** O Sistema rejeita a medição e retorna JSON com status `HTTP 422 Unprocessable Entity` informando o status atual da estação ([`MedicaoApiController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/Api/MedicaoApiController.php#L69-L74)).
  - **A5 - Limite de requisições excedido (*Rate Limiting*):** Se o limite de 60 requisições por minuto (`throttle:60,1` em [api.php](file:///home/henrique_1/Documents/OpenAir-Metrics/routes/api.php#L8)) for ultrapassado, o Sistema retorna `HTTP 429 Too Many Requests`.
  - **A6 - Falha no servidor de WebSockets (Reverb indisponível):** O bloco `try/catch` em [`MedicaoApiController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/Api/MedicaoApiController.php#L90-L94) captura a exceção de broadcast, registra no log via `report($e)` e garante o retorno `HTTP 201 Created` sem perder a medição já persistida no banco.
- **Pós-condições:** Medição e IQA gravados na tabela `medicoes` e clientes conectados ao canal WebSocket `medicoes` atualizados em tempo real.

---

### UC16 — Consultar Localidades e Resolver Geocodificação Reversa

- **Atores:** Usuário Autenticado, Sistema (Worker de Fila), APIs OpenStreetMap (Overpass / Nominatim).
- **Resumo:** Fornece listas hierárquicas de estados, cidades e bairros (com importação automática via Overpass) e resolve endereços a partir de coordenadas geográficas de forma síncrona ou assíncrona.
- **Pré-condições:** O usuário deve estar autenticado para chamadas HTTP às rotas de [`LocalidadeController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/LocalidadeController.php#L17-L295).
- **Fluxo Principal (Caminho Feliz):**
  1. O Ator consulta estados (`GET /localidades/estados`), cidades (`GET /localidades/estados/{estado}/cidades` ou `GET /localidades/cidades/{uf}`), bairros (`GET /localidades/cidades/{cidade}/bairros`) ou geocodificação reversa (`GET /geocoding/reverse` passando `lat`, `lng` e opcionalmente `cidade_id`).
  2. Na geocodificação reversa ([`LocalidadeController::reverse`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/LocalidadeController.php#L228-L294)), o Sistema invoca [`GeocodingService::obterDetalhesEndereco`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L442-L524), que verifica sequencialmente:
     - O cache de 30 dias (`geocoding_details_{lat}_{lng}`);
     - A existência de outra estação cadastrada num raio de $\le 10\text{m}$ no banco;
     - O nome do logradouro mais próximo ($\le 50\text{m}$) na tabela `malha_viaria`;
     - A API externa do OpenStreetMap Nominatim ([`GeocodingService::consultarNominatim`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L531-L615)).
  3. O Sistema associa ou cria automaticamente o bairro encontrado para a cidade (`Bairro::firstOrCreate`) e retorna o JSON com o endereço completo estruturado.
- **Fluxos Alternativos / Exceções:**
  - **A1 - Cidade consultada ainda não possui bairros cadastrados no banco (`$cidade->bairros()->doesntExist()`):** O Sistema ([`LocalidadeController::importarBairrosOverpass`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/LocalidadeController.php#L73-L175)) consulta os servidores Overpass com failover, filtra e insere em lote apenas os bairros inéditos (`Bairro::insert`).
  - **A2 - API Overpass não retorna nenhum bairro para a cidade (`empty($bairrosApi)`):** O Sistema cria automaticamente o bairro padrão `'Centro'` para a cidade (`Bairro::firstOrCreate`) ([`LocalidadeController::importarBairrosOverpass`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/LocalidadeController.php#L169-L174)).
  - **A3 - Estado/UF inexistente em `cidadesPorUf`:** O Sistema retorna um array JSON vazio `[]` ([`LocalidadeController::cidadesPorUf`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/LocalidadeController.php#L46-L48)).
  - **A4 - Resolução Assíncrona via Fila e Rate Limiting do Nominatim (`$sync === false`):** Quando acionado assincronamente, o Sistema retorna os dados preliminares locais e despacha [`ResolveReverseGeocodingJob`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Jobs/ResolveReverseGeocodingJob.php#L14-L81). O Job aplica `RateLimiter::attempt('nominatim_reverse_geocoding', 1, ..., 1)` (máximo de 1 requisição por segundo); se o limite for atingido fora do ambiente de testes, o Job executa `$this->release(2)` para reprocessamento após 2 segundos e, ao concluir, atualiza os campos de endereço da [`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L19-L741) ([`ResolveReverseGeocodingJob::processGeocoding`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Jobs/ResolveReverseGeocodingJob.php#L58-L80)).
- **Pós-condições:** Localidades e endereços estruturados retornados ao cliente, com novos bairros persistidos no banco e resultados armazenados em cache por 30 dias.
