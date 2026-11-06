# Documento de Análise de Requisitos — OpenAir-Metrics

## Visão Geral do Módulo

O **OpenAir-Metrics** é uma plataforma web e de telemetria IoT desenvolvida em **Laravel 13 (PHP 8.5)** voltada ao planejamento geoespacial, implantação em campo, gestão patrimonial e monitoramento ambiental em tempo real da qualidade do ar urbano.

Arquiteturalmente, o sistema adota o padrão **MVC orientado a serviços de domínio**, combinando:
- **Ingestão de Telemetria e Broadcasting em Tempo Real:** Recepção de leituras de sensores em campo via API REST ([`MedicaoApiController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/Api/MedicaoApiController.php#L12-L113)), cálculo automatizado do Índice de Qualidade do Ar (IQA) em [`Medicao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Medicao.php#L11-L150) e difusão imediata via WebSockets ([`NovaMedicaoRecebida`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Events/NovaMedicaoRecebida.php#L14-L79)) com Laravel Reverb.
- **Motor Geoespacial e Planejamento de Malha em Cascata:** Cálculo de malhas de estações (Matrizes e Satélites) posicionadas sobre o eixo de vias públicas ([`PlanejamentoMalhaService`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/PlanejamentoMalhaService.php#L12-L757) e [`GeocodingService`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L13-L626)), utilizando indexação espacial (`POINT` e `LINESTRING` SRID 4326), projeção ortogonal (*Snap to Road*), detecção de colisão (*Anti-Overlap*) e ordenação topológica em árvore via busca em profundidade (DFS) ([`Estacao::ordenarEmCascata`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L647-L740)).
- **Gestão Multi-Jurisdicional (Escopo Municipal):** Controle de acesso baseado em papéis (`superadmin`, `administrador`, `cadastrador`/`planejador`, `instalador`) com isolamento de dados por município (`cidade_id`) para inventário de hardware ([`PatrimonioController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PatrimonioController.php#L14-L285)), ordens de instalação em campo ([`InstalacaoController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L14-L322)) e ciclo de vida/substituição de sensores ([`EstacaoController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L17-L401)).

---

## Requisitos Funcionais (RF)

### Autenticação, Perfil e Gestão de Usuários
- **RF01 — Autenticação de Usuários:** O sistema deve autenticar usuários ativos por e-mail e senha via [`AuthController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/AuthController.php#L23-L56) (`POST /login` em [web.php](file:///home/henrique_1/Documents/OpenAir-Metrics/routes/web.php#L25)), redirecionando o usuário conforme seu perfil (`superadmin` para `/usuarios`, `instalador` para `/instalacoes`, demais para `/dashboard`). O formulário deve disponibilizar botão de alternância de visibilidade de senha (mostrar/ocultar senha com suporte a acessibilidade e ARIA, conforme NIST SP 800-63B) e mecanismo para transportar o e-mail preenchido via query string ao clicar em recuperação de senha.
- **RF02 — Encerramento de Sessão:** O sistema deve permitir o encerramento seguro da sessão autenticada com invalidação de sessão e regeneração de token CSRF via [`AuthController::destroy`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/AuthController.php#L61-L72) (`POST /logout`).
- **RF03 — Gestão de Perfil Próprio:** O sistema deve permitir que o usuário autenticado edite seu nome e endereço cadastral (e vincule `cidade_id` apenas caso ainda não possua município fixado) via [`PerfilController::edit`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PerfilController.php#L15-L25) e [`PerfilController::update`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PerfilController.php#L30-L59) (`GET/PUT /perfil`). Para usuários com perfil `administrador` ou `instalador` que ainda não configuraram segundo fator de autenticação, a tela deve exibir banner de alerta recomendando a ativação de MFA (TOTP) ou cadastro de Passkey.
- **RF04 — Administração de Usuários e Administradores Municipais:** O sistema deve permitir a listagem, busca, filtragem, criação, edição e ativação/desativação de contas via [`UsuarioController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L14-L361) (`/usuarios`), restringindo o escopo conforme o papel do gestor logado. Na criação de novos usuários, o administrador define apenas dados básicos (Nome Completo, E-mail e Nível/Jurisdição); o sistema cria a conta com senha provisória aleatória e envia automaticamente um e-mail de ativação ([`ConviteCadastroNotification`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Notifications/ConviteCadastroNotification.php)) para que o próprio colaborador defina sua senha e informe opcionalmente seu endereço. Em casos de perda do autenticador, deve prover contingência via `POST /usuarios/{user}/resetar-mfa`.

### Monitoramento Ambiental e Dashboards
- **RF05 — Mapa Público em Tempo Real:** O sistema deve exibir na página inicial ([`HomeController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/HomeController.php#L16-L116), `GET /`) as coordenadas e os indicadores da última medição de todas as estações com leituras registradas (IQA, temperatura, umidade, PM2.5 e $\text{CO}_2$), centralizando o mapa na média geográfica das estações ativas. O mapa deve disponibilizar painel flutuante retrátil sinalizando estações sob alerta ambiental ativo em tempo real, além de cartões de detalhamento nos pontos com indicadores de severidade e recomendações preventivas de saúde pública.
- **RF06 — Painel Analítico e Contadores de Alertas Dinâmicos por Período e Localidade:** O sistema deve apresentar no painel principal ([`DashboardController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/DashboardController.php#L25-L108), `GET /dashboard`) o total de estações, total de leituras e cinco cards dedicados de contadores de alertas ambientais (Qualidade do Ar/IQA, Material Particulado, Temperatura, Umidade e $\text{CO}_2$), com estilização visual moderna destacando ícones e badges de severidade. Para gestores municipais (`administrador`, `planejador`, `instalador`), os contadores e séries temporais são circunscritos à sua jurisdição fixa (`cidade_id`), permitindo variação territorial apenas por bairro. Para o **Super-Usuário**, o painel atua em nível global (**Painel Global de Monitoramento**), permitindo selecionar livremente qualquer cidade com estações cadastradas e seus respectivos bairros com estações ativas. Os contadores de alerta devem ser recalculados dinamicamente com base no período selecionado (`24h`, `7d`, `30d`), acompanhados de rótulo da localidade e janela temporal. Além disso, os gráficos de séries temporais devem demarcar visualmente no plano de fundo as faixas/bandas de normalidade e criticidade do parâmetro selecionado, destacando individualmente os pontos médios que ultrapassam os limiares de segurança.
- **RF07 — Séries Temporais com Granularidade Adaptativa:** O sistema deve fornecer agregações temporais (média, máximo, mínimo, rótulos e classificação qualitativa) filtradas por cidade ou bairro, métrica (`qualidade_ar`, `temperatura`, `umidade`, `poeira`, `co2`) e período (`24h`, `7d`, `30d`, `todos`) via [`DashboardController::dadosGrafico`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/DashboardController.php#L71-L275) (`GET /dashboard/graficos`).
- **RF08 — Recepção de Telemetria IoT:** O sistema deve disponibilizar um endpoint de ingestão de medições ([`MedicaoApiController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/Api/MedicaoApiController.php#L17-L112), `POST /api/medicoes` em [api.php](file:///home/henrique_1/Documents/OpenAir-Metrics/routes/api.php#L7-L9)) que receba `mac_address`, `temperatura`, `umidade`, `co2` e `poeira`, calcule o IQA, persista a leitura e transmita o evento [`NovaMedicaoRecebida`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Events/NovaMedicaoRecebida.php#L14-L79) no canal público `medicoes`.

### Gestão de Estações e Ciclo de Vida de Sensores
- **RF09 — Listagem e Filtragem de Estações:** O sistema deve listar as estações da jurisdição do usuário com busca textual (MAC, logradouro, bairro, cidade, número de patrimônio), filtros por tipo e status (`instalada`, `pendente`, `substituicao`) e ordenação (incluindo ordenação pelo percentual de vida útil restante) via [`EstacaoController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L22-L111) (`GET /estacoes`).
- **RF10 — Cadastro Individual de Estação:** O sistema deve permitir o cadastro de uma estação (`Estação Matriz` ou `Estação Satélite`) via [`EstacaoController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L166-L266) (`POST /estacoes`), resolvendo automaticamente o endereço por geocodificação reversa, criando/associando o bairro real, vinculando a topologia de rede e alocando eventual equipamento de patrimônio correspondente ao MAC informado.
- **RF11 — Solicitação de Substituição de Sensores:** O sistema deve permitir registrar solicitações de substituição preventiva ou corretiva para estações instaladas via [`EstacaoController::solicitarSubstituicao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L271-L322) (`POST /estacoes/{public_id}/solicitar-substituicao`).
- **RF12 — Efetivação de Substituição de Sensor:** O sistema deve permitir substituir o equipamento físico de uma estação por um novo item de patrimônio disponível via [`EstacaoController::substituirSensor`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L329-L400) (`POST /estacoes/{public_id}/substituir-sensor`), descartando o patrimônio anterior, ativando o novo e encerrando a pendência de substituição.

### Planejamento Automático de Malha Viária
- **RF13 — Simulação e Cálculo de Malha em Cascata:** O sistema deve calcular automaticamente o posicionamento de 1 Estação Matriz e de 1 a 20 Estações Satélites ao longo das vias públicas em ramos divergentes via [`PlanejamentoController::calcular`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PlanejamentoController.php#L76-L103) (`POST /estacoes/calcular-malha`) e [`PlanejamentoMalhaService::calcularMalha`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/PlanejamentoMalhaService.php#L28-L165).
- **RF14 — Ajuste Fino sobre Vias Públicas (*Snap to Road*):** O sistema deve fornecer um endpoint para encaixar coordenadas candidatas sobre o segmento de rua válido mais próximo, respeitando o limite de distância até a origem e evitando sobreposição com outras estações, via [`PlanejamentoController::snapToRoad`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PlanejamentoController.php#L108-L141) (`POST /estacoes/snap-to-road`).
- **RF15 — Persistência de Malha Planejada:** O sistema deve salvar em transação atômica toda a topologia planejada (Matriz e Satélites com status `Planejada`, vínculos `matriz_pai_id` e `estacao_origem_id`, e ordem sequencial de instalação normalizada em cascata) via [`PlanejamentoController::salvar`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PlanejamentoController.php#L146-L289) (`POST /estacoes/salvar-malha`).

### Gestão de Patrimônio e Ordens de Instalação em Campo
- **RF16 — Gestão de Inventário de Patrimônio (Individual e em Lote):** O sistema deve listar, filtrar e exibir contadores de estoque (`Disponível`, `Instalada`, `Descartado`) via [`PatrimonioController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PatrimonioController.php#L19-L83), além de permitir o cadastro individual ([`PatrimonioController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PatrimonioController.php#L110-L154)), cadastro em lote de múltiplos MAC Addresses ([`PatrimonioController::storeBatch`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PatrimonioController.php#L159-L242)), consulta de itens disponíveis via JSON ([`PatrimonioController::apiDisponiveis`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PatrimonioController.php#L268-L284)) e baixa lógica para `Descartado` ([`PatrimonioController::destroy`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PatrimonioController.php#L247-L263)).
- **RF17 — Acompanhamento de Ordens de Instalação:** O sistema deve listar as malhas agrupadas por Estação Matriz com contagem total de satélites, satélites instaladas e substituições pendentes ([`InstalacaoController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L19-L92), `GET /instalacoes`) e exibir o roteiro passo a passo ordenado em cascata ([`InstalacaoController::show`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L97-L137), `GET /instalacoes/{public_id}`).
- **RF18 — Ativação e Vinculação de Hardware em Campo:** O sistema deve permitir que técnicos instaladores ativem estações em campo vinculando um Número de Patrimônio ou MAC Address via [`InstalacaoController::vincularMac`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L142-L321) (`POST /instalacoes/{public_id}/vincular-mac`), criando o registro de patrimônio automaticamente caso não exista em estoque.

### Localidades e Geocodificação
- **RF19 — Consulta de Localidades e Importação Sob Demanda de Bairros:** O sistema deve expor endpoints JSON para listagem de estados, cidades (por ID ou UF), bairros e coordenadas de estações em [`LocalidadeController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/LocalidadeController.php#L17-L295). Ao consultar bairros de uma cidade sem registros locais, deve importar automaticamente os bairros via API Overpass do OpenStreetMap ([`LocalidadeController::importarBairrosOverpass`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/LocalidadeController.php#L73-L175)).
- **RF20 — Geocodificação Reversa Síncrona e Assíncrona:** O sistema deve resolver endereços a partir de coordenadas (`GET /geocoding/reverse` em [`LocalidadeController::reverse`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/LocalidadeController.php#L228-L294) e [`GeocodingService::obterDetalhesEndereco`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L442-L524)), utilizando cache local, proximidade com estações existentes, malha viária local e resolução na API Nominatim (síncrona ou via fila assíncrona [`ResolveReverseGeocodingJob`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Jobs/ResolveReverseGeocodingJob.php#L14-L81)).

### Segurança, Credenciais e Autenticação Avançada
- **RF21 — Recuperação Autônoma de Senha (Self-Service):** O sistema deve permitir a redefinição de senha esquecida via [`PasswordResetLinkController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PasswordResetLinkController.php) e [`NewPasswordController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/NewPasswordController.php) (`/esqueci-senha` e `/redefinir-senha/{token}`), com link de recuperação de 30 minutos enviado por e-mail ([`RedefinirSenhaNotification`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Notifications/RedefinirSenhaNotification.php)), preenchimento prévio automático do e-mail digitado no login via query string, encerramento de outras sessões ativas e proteção uniforme contra enumeração de contas.
- **RF22 — Ativação de Conta e Conclusão de Cadastro:** O sistema deve disponibilizar interface pública com validação de token temporário (30 minutos) via [`CompletarCadastroController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/CompletarCadastroController.php) (`GET/POST /completar-cadastro/{token}`) para que colaboradores recém-cadastrados definam sua senha e preencham opcionalmente endereço cadastral (CEP, logradouro, número, complemento, bairro, estado).
- **RF23 — Autenticação Multifator Baseada em Tempo (TOTP):** O sistema deve fornecer autenticação em duas etapas voluntária para os colaboradores via Laravel Fortify (`/user/two-factor-authentication`), permitindo escanear o QR Code em aplicativos como Google Authenticator ou Authy, confirmar a ativação mediante código de 6 dígitos, interceptar o login tradicional para desafio em `/two-factor-challenge` e disponibilizar 8 códigos descartáveis de recuperação (backup) com regeneração e desativação a qualquer momento. Em casos de perda irrecuperável do autenticador, o sistema deve prover contingência administrativa via `POST /usuarios/{user}/resetar-mfa`, permitindo ao Super-Usuário ou Administrador Municipal (dentro de sua jurisdição) redefinir emergencialmente o MFA da conta com registro em log de auditoria.
- **RF24 — Autenticação Biométrica e Sem Senha via Passkeys (WebAuthn / FIDO2):** O sistema deve permitir o cadastro de Passkeys (Touch ID, Face ID, Windows Hello, YubiKey) e login sem senha via `spatie/laravel-passkeys`, com acesso restrito com exclusividade a usuários de nível `administrador` e `instalador` (NIST AAL3). Usuários `cadastrador` e `superadmin` têm a funcionalidade ocultada na interface e bloqueada a nível de política (`HTTP 403 Forbidden`). Contas desativadas têm autenticação por Passkey sumariamente rejeitada. Na tela de perfil, é exibido um aviso recomendando a ativação de MFA/Passkey para administradores e instaladores que ainda não configuraram segundo fator.

#### Diretrizes de Segurança e Política de Credenciais (NIST SP 800-63B Rev. 4)
- **Tamanho Mínimo e Máximo:** Senhas devem ter entre 15 e 64 caracteres (`Password::min(15)->max(64)`), permitindo o uso de frases secretas longas (*passphrases*).
- **Sem Regras Artificiais de Composição:** Não são exigidas regras de complexidade forçada (maiúsculas, dígitos ou caracteres especiais obrigatórios), reduzindo o uso de padrões previsíveis.
- **Proteção Contra Senhas Vazadas:** Toda definição de senha é submetida à verificação contra listas de credenciais comprometidas conhecidas (`Password::uncompromised()`), utilizando *k-anonymity* para não expor a senha.
- **Não Expiração Periódica Arbitrária:** Eliminação de trocas forçadas a cada 90 dias; a alteração ocorre exclusivamente por solicitação do usuário ou caso de comprometimento.
- **Ciclo de Vida de Tokens:** Tokens de redefinição e ativação possuem validade limitada a 30 minutos (`AUTH_PASSWORD_RESET_EXPIRE=30`) e são invalidados após o uso ou expiração.
- **Privacidade e Autonomia:** Administradores não definem senhas de outros colaboradores; credenciais são atribuídas exclusivamente pelos próprios titulares.
- **Acessibilidade e Usabilidade de Senhas:** Todas as telas com campos de credenciais (`login`, `redefinir-senha`, `completar-cadastro`) devem incluir botões para alternar a visualização da senha digitada (texto visível / mascarado), garantindo conformidade com a recomendação da NIST para redução de erros de digitação e acessibilidade.

---

## Requisitos Não Funcionais (RNF)

### Tecnologias, Frameworks e Arquitetura
- **RNF01 — Stack Tecnológica:** Aplicação construída sobre **PHP 8.3+ / 8.5**, **Laravel Framework v13**, **Laravel Sanctum v4**, **Laravel Reverb v1** (WebSockets), **Laravel MCP v0.8** ([`OpenAirMetricsServer`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Mcp/Servers/OpenAirMetricsServer.php#L13-L26) registrado em [ai.php](file:///home/henrique_1/Documents/OpenAir-Metrics/routes/ai.php#L7)), **Tailwind CSS v4**, **Vite v8**, **Leaflet v1.9** e **Laravel Echo / Pusher JS** ([composer.json](file:///home/henrique_1/Documents/OpenAir-Metrics/composer.json), [package.json](file:///home/henrique_1/Documents/OpenAir-Metrics/package.json)).

### Persistência, Geoprocessamento e Portabilidade de Banco
- **RNF02 — Persistência Geoespacial e Índices Espaciais:** As coordenadas de estações são armazenadas como geometria `POINT` (SRID 4326) na tabela `estacoes` ([`Estacao::booted`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L105-L130)) e os eixos viários como `LINESTRING` (SRID 4326) com `SPATIAL INDEX` na tabela `malha_viaria` ([2026_09_02_110000_create_malha_viaria_table.php](file:///home/henrique_1/Documents/OpenAir-Metrics/database/migrations/2026_09_02_110000_create_malha_viaria_table.php#L16-L26)). As consultas espaciais utilizam `MBRIntersects` com *Bounding Box* e `ST_Distance_Sphere` no MariaDB/MySQL ([`GeocodingService::buscarViasProximas`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L26-L104)).
- **RNF03 — Compatibilidade Híbrida de Driver (MariaDB/MySQL e SQLite):** Todos os recursos espaciais possuem *fallback* transparente em PHP utilizando cálculo ortodrômico de Haversine e parsing de WKT/WKB ([`Estacao::extractCoordinatesFromAttribute`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L304-L341), [`GeocodingService::buscarViasProximasSqlite`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L111-L153)), garantindo execução de testes automatizados em SQLite sem dependência de extensões espaciais de SGBD.
- **RNF04 — Ingestão Massiva de Malha Viária (GDAL/ogr2ogr):** O seeder [`MalhaViariaSeeder`](file:///home/henrique_1/Documents/OpenAir-Metrics/database/seeders/MalhaViariaSeeder.php#L12-L197) realiza o download da base nacional do OpenStreetMap (`brazil-latest.osm.pbf`) e importa as vias para o MariaDB via utilitário nativo `ogr2ogr` em lotes (`-gt 65536`), compilando o índice espacial ao término.

### Segurança e Controle de Acesso
- **RNF05 — Segregação de Identificadores (Chaves Internas vs. Públicas):** As entidades críticas ([`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L33-L40), [`Medicao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Medicao.php#L24-L31), [`Patrimonio`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Patrimonio.php#L25-L44)) utilizam chave primária inteira autoincremental (`private_id`) exclusivamente para joins e chaves estrangeiras internas, expondo apenas UUIDv4 (`public_id`) em rotas, URLs e payloads de API.
- **RNF06 — Limitação de Taxa (*Rate Limiting*):**
  - O endpoint de login (`POST /login`) é protegido por `throttle:5,1` (máximo de 5 tentativas por minuto) ([web.php](file:///home/henrique_1/Documents/OpenAir-Metrics/routes/web.php#L25)).
  - O endpoint de telemetria (`POST /api/medicoes`) é protegido por `throttle:60,1` (60 requisições por minuto) ([api.php](file:///home/henrique_1/Documents/OpenAir-Metrics/routes/api.php#L8)).
  - O job assíncrono de geocodificação reversa aplica `RateLimiter` estrito de 1 requisição por segundo para a API pública do Nominatim, re-enfileirando com atraso de 2 segundos quando excedido ([`ResolveReverseGeocodingJob::handle`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Jobs/ResolveReverseGeocodingJob.php#L34-L53)).
- **RNF07 — Autenticidade de Dispositivos IoT em Tempo Constante:** O endpoint [`MedicaoApiController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/Api/MedicaoApiController.php#L31-L39) valida o cabeçalho HTTP `X-Sensor-Key` contra a chave configurada em `services.telemetry.key` / `TELEMETRY_API_KEY` utilizando `hash_equals()` para mitigar ataques de temporização (*timing attacks*).

### Desempenho, Resiliência e Confiabilidade
- **RNF08 — Estratégia de Cache em Múltiplas Camadas:**
  - Detalhes de geocodificação reversa são armazenados em cache por **30 dias** (`geocoding_details_{lat}_{lng}`, arredondado em 6 casas decimais) ([`GeocodingService::obterDetalhesEndereco`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L444-L485)).
  - Consultas de geometrias viárias na API Overpass são armazenadas em cache por **24 horas** ([`PlanejamentoMalhaService::obterViasDoOpenStreetMap`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/PlanejamentoMalhaService.php#L174-L180)).
- **RNF09 — Alta Disponibilidade em Integrações Externas (Failover Overpass):** As consultas externas ao OpenStreetMap Overpass implementam rotação automática de servidores espelho (`overpass.private.coffee`, `overpass-api.de`, `overpass.kumi.systems`, `maps.mail.ru`) com timeouts curtos (6 a 8 segundos) e captura de exceções para não interromper o fluxo do usuário ([`PlanejamentoMalhaService::obterViasDoOpenStreetMap`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/PlanejamentoMalhaService.php#L188-L214), [`LocalidadeController::importarBairrosOverpass`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/LocalidadeController.php#L105-L131)).
- **RNF10 — Transacionalidade e Preservação de Histórico:** Operações compostas (salvamento de malha planejada, ativação em campo e substituição de sensores) executam dentro de transações `DB::beginTransaction()` / `DB::commit()`. Equipamentos de patrimônio nunca sofrem exclusão física (`DELETE` no banco) pelo controlador, sendo marcados com status `Descartado` ([`PatrimonioController::destroy`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PatrimonioController.php#L247-L263)).

### Comunicação, Notificações e Ferramentas Auxiliares
- **RNF11 — Padronização Visual de E-mails Institucionais e Internacionalização:** As notificações transacionais por e-mail (recuperação de senha e convite para conclusão de cadastro) devem utilizar templates customizados do Laravel Mail (`resources/views/vendor/mail/html/`), incorporando a identidade visual da plataforma (logotipo institucional, paleta de cores com `#0f4c5c` Blue Dianne e `#00a5bd` Persian Green, tipografia Inter, contêineres arredondados com contraste suave e rodapé conciso). Todos os textos nativos de autenticação e notificações do framework devem ser localizados para português brasileiro via arquivos de tradução (`lang/pt.json` e `lang/pt_BR.json`).
- **RNF12 — Ferramentas de Homologação e Criptografia do Ambiente:** O sistema deve disponibilizar rotas seguras restritas ao ambiente de desenvolvimento local (`/preview-email/convite` e `/preview-email/recuperar-senha` sob `APP_ENV=local`) para validação visual dos templates de e-mail diretamente no navegador sem dependência de provedor SMTP, bem como comando Artisan customizado `php artisan env:prod` em `routes/console.php` para automatização da criptografia simétrica de segredos no arquivo `.env.production`.

---

## Regras de Negócio (RN)

### Hierarquia de Perfis, Jurisdição e Sucessão Administrativa
- **RN01 — Segregação de Funções do Super-Usuário (`superadmin`):** O Super-Usuário possui permissão exclusiva para gerenciar usuários de nível `administrador` em qualquer município ([`UsuarioController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L29-L37)). É-lhe expressamente bloqueado (`HTTP 403`) o acesso aos módulos operacionais de Dashboard, Estações, Patrimônios e Instalações.
- **RN02 — Escopo de Jurisdição Municipal (`cidade_id`):** Administradores municipais (`administrador`), Planejadores Técnicos (`cadastrador`/`planejador`) e Instaladores (`instalador`) vinculados a um `cidade_id` só podem visualizar, cadastrar, planejar, ativar ou substituir estações e patrimônios pertencentes ao seu próprio município ([`StoreEstacaoRequest::after`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Requests/StoreEstacaoRequest.php#L90-L101), [`PatrimonioController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/PatrimonioController.php#L117-L120), [`InstalacaoController::vincularMac`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L155-L160)).
- **RN03 — Regra de Sucessão Administrativa Municipal:** Quando um Administrador Municipal cadastra um novo usuário com nível `administrador` para o seu município, a conta do administrador atual é imediatamente desativada (`ativo = false`), sua sessão é encerrada e ele é desconectado do sistema ([`UsuarioController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L189-L196)).
- **RN04 — Bloqueio de Auto-Desativação:** Nenhum administrador ou super-usuário pode desativar a própria conta ([`UsuarioController::toggleStatus`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/UsuarioController.php#L212-L221)). Contas com `ativo = false` são impedidas de autenticar ([`AuthController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/AuthController.php#L34)).

### Topologia de Rede, Geoprocessamento e Planejamento de Malha
- **RN05 — Alcance Máximo de Rádio de 200 Metros para Satélites:** Toda `Estação Satélite` deve estar localizada a uma distância máxima estrita de **200 metros** (calculada por Haversine / `ST_Distance_Sphere`) de sua estação de origem (`estacao_origem_id`) ou de uma estação já cadastrada no banco. Não é permitido cadastrar uma Estação Satélite caso não exista nenhuma estação previamente cadastrada ([`StoreEstacaoRequest::after`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Requests/StoreEstacaoRequest.php#L108-L137)).
- **RN06 — Exclusão de Vias Expressas e Rodovias:** O algoritmo de busca viária e *Snap to Road* proíbe o posicionamento de estações em vias classificadas como `'motorway'`, `'trunk'`, `'motorway_link'` ou `'trunk_link'`, permitindo apenas vias urbanas (`residential`, `tertiary`, `secondary`, `primary` e seus links) ([`GeocodingService::buscarViasProximas`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L62), [`GeocodingService::snapToRoad`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L288-L303)).
- **RN07 — Algoritmo de Expansão em Cascata e *Anti-Overlap*:**
  - O planejamento automático aceita entre **1 e 20 satélites** por Matriz, distribuindo-as em **2 ramos** (para 1 a 3 satélites), **3 ramos** (para 4 a 6 satélites) ou **4 ramos** cardeais divergentes (acima de 6 satélites) ([`PlanejamentoMalhaService::gerarMalhaEmCascataPelasRuas`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/PlanejamentoMalhaService.php#L302-L317)).
  - Cada salto na cascata busca pontos discretizados no eixo da rua a uma distância entre **100m e 198m** do pai imediato, exigindo afastamento progressivo em relação à Matriz e distância mínima de **180m a 200m** (*Anti-Overlap*) de qualquer outra estação que não seja o próprio pai ([`PlanejamentoMalhaService::encontrarProximoPontoCascataNaRua`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/PlanejamentoMalhaService.php#L481-L515), [`GeocodingService::snapToRoad`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L353-L391)).
- **RN08 — Ordenação Topológica em Cascata (DFS):** A sequência de instalação (`ordem_instalacao`) de uma malha é normalizada de `1` a `N` por busca em profundidade (DFS) a partir da `Estação Matriz` (sempre Passo `#1`), garantindo que cada ramo seja percorrido continuamente (`Pai -> Filho -> Neto`) antes de iniciar o próximo ramo ([`Estacao::ordenarEmCascata`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L647-L740)).
- **RN09 — Precedência de Instalação em Campo:** Uma `Estação Satélite` só pode ser instalada/ativada em campo por um `instalador` se a sua estação pai imediata (`estacao_origem_id`) já estiver com `status_instalacao = 'Instalada'` ([`InstalacaoController::vincularMac`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L200-L209)).

### Patrimônio, Vida Útil e Manutenção de Sensores
- **RN10 — Codificação Sequencial de Patrimônio por Município:** Todo equipamento cadastrado recebe um código sequencial único por cidade no formato `OAir-Estacao-<cidade_id>-<XXXX>` (com 4 dígitos preenchidos com zero à esquerda, ex: `OAir-Estacao-1-0001`), calculado a partir do maior sufixo numérico existente para aquele município ([`Patrimonio::gerarProximoCodigo`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Patrimonio.php#L89-L103)).
- **RN11 — Unicidade e Normalização de MAC Address:** O endereço MAC é normalizado para letras maiúsculas no padrão `AA:BB:CC:DD:EE:FF` e deve ser único tanto na tabela `patrimonios` quanto na tabela `estacoes`. Um patrimônio já vinculado a uma estação ativa não pode ser vinculado a outra estação ([`InstalacaoController::vincularMac`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L246-L255)).
- **RN12 — Cálculo de Vida Útil de Sensores (5 Anos):** A vida útil nominal dos sensores é de **5 anos** ($5 \times 365,25\text{ dias}$) a contar de `data_instalacao` ([`Estacao::calcularVidaUtil`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L465-L511)):
  - Sem `data_instalacao`: Status `Não Instalada` (`100%` restante).
  - `dias_restantes <= 0`: Status `Vencida` (`0%` restante).
  - `1 <= dias_restantes <= 180` (< 6 meses): Status `Crítica (< 6 meses)`.
  - `dias_restantes > 180`: Status `Normal`.
- **RN13 — Regras de Solicitação e Substituição de Sensores:**
  - Apenas estações já instaladas (`data_instalacao != null`) podem receber solicitação de substituição ([`EstacaoController::solicitarSubstituicao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L286-L290)).
  - Se a vida útil restante for **superior a 20%**, o preenchimento de `motivo_substituicao` (mínimo 3 caracteres) é **obrigatório**; caso seja $\le 20\%$ e não informado, assume automaticamente o valor padrão `"Fim da vida útil da estação"` ([`EstacaoController::solicitarSubstituicao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L292-L306)).
  - Ao efetivar a substituição (via [`EstacaoController::substituirSensor`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/EstacaoController.php#L365-L386) ou [`InstalacaoController::vincularMac`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/InstalacaoController.php#L267-L293)), o patrimônio antigo é automaticamente alterado para `Descartado`, o novo patrimônio assume `Instalada`, a `data_instalacao` da estação é renovada para `now()` e as flags de substituição são limpas.

### Telemetria, Cálculo de IQA e Classificações Ambientais
- **RN14 — Validação de Telemetria e Status de Ativação:** O endpoint [`MedicaoApiController::store`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/Api/MedicaoApiController.php#L19-L74) aceita leituras apenas dentro dos limites físicos (`temperatura` entre $-50$ e $100\text{ °C}$, `umidade` entre $0$ e $100\%$, `co2` entre $0$ e $50000\text{ ppm}$, `poeira` entre $0$ e $5000\text{ }\mu\text{g/m}^3$) e rejeita (`HTTP 422`) medições de estações cujo `status_instalacao` seja diferente de `'Instalada'`.
- **RN15 — Cálculo do Índice de Qualidade do Ar (IQA):** O IQA é calculado por interpolação linear em [`Medicao::calcularIqa`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Medicao.php#L103-L149) segundo os padrões CONAMA 506/2024 ($\text{PM}_{2.5}$) e ANVISA RE nº 09/2003 ($\text{CO}_2$), assumindo o maior índice arredondado entre os dois poluentes ($\text{IQA} = \text{round}(\max(\text{IQA}_{\text{PM}}, \text{IQA}_{\text{CO2}}))$), limitado ao teto de $500$:
  $$\text{IQA}_p = I_{\text{inf}} + \frac{I_{\text{sup}} - I_{\text{inf}}}{C_{\text{sup}} - C_{\text{inf}}} \times (C - C_{\text{inf}})$$
  - **N1 — Boa ($0\text{ a }40$):** $\text{PM}_{2.5} \in [0; 15,0]$ | $\text{CO}_2 \in [0; 700]$
  - **N2 — Moderada ($41\text{ a }80$):** $\text{PM}_{2.5} \in (15,0; 50,0]$ | $\text{CO}_2 \in (700; 1000]$
  - **N3 — Ruim ($81\text{ a }120$):** $\text{PM}_{2.5} \in (50,0; 75,0]$ | $\text{CO}_2 \in (1000; 1500]$
  - **N4 — Muito Ruim ($121\text{ a }200$):** $\text{PM}_{2.5} \in (75,0; 125,0]$ | $\text{CO}_2 \in (1500; 2500]$
  - **N5 — Péssima ($201\text{ a }400$):** $\text{PM}_{2.5} \in (125,0; 300,0]$ | $\text{CO}_2 \in (2500; 5000]$
- **RN16 — Matriz de Alertas Ambientais Multicritério e Demarcação Gráfica:**
  - O sistema padroniza os alertas ambientais em cinco níveis de severidade (`Sem Alerta`, `Alerta de Atenção`, `Alerta Crítico`, `Alerta Crítico Severo` e `Alerta de Emergência`) gerenciados centralizadamente por [`AlertaAmbientalService`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/AlertaAmbientalService.php) e enum [`NivelAlerta`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Enums/NivelAlerta.php).
  - **Limiares e Diagnósticos por Grandeza:**
    - **Índice de Qualidade do Ar (IQA):** $\le 40$ (Boa / Sem Alerta); $41\text{ a }80$ (Moderada / Alerta de Atenção); $81\text{ a }120$ (Ruim / Alerta Crítico); $121\text{ a }200$ (Muito Ruim / Alerta Crítico Severo); $> 200$ (Péssima / Alerta de Emergência).
    - **Material Particulado ($\text{PM}_{2.5}$):** $\le 15,0\text{ }\mu\text{g/m}^3$ (Boa / Sem Alerta); $15,1\text{ a }50,0$ (Moderada / Alerta de Atenção); $50,1\text{ a }75,0$ (Ruim / Alerta Crítico); $75,1\text{ a }125,0$ (Muito Ruim / Alerta Crítico Severo); $> 125,0$ (Péssima / Alerta de Emergência).
    - **Temperatura:** $< 0,0\text{ °C}$ (Alerta Crítico de Congelamento/Geada); $0,0\text{ a }9,9\text{ °C}$ (Alerta Crítico Severo - Frio Intenso); $10,0\text{ a }17,9\text{ °C}$ (Alerta Crítico - Frio Severo); $18,0\text{ a }19,9\text{ °C}$ (Alerta de Atenção - Frio Leve); $20,0\text{ a }24,0\text{ °C}$ (Sem Alerta - Faixa de Equilíbrio); $24,1\text{ a }28,0\text{ °C}$ (Alerta de Calor Moderado); $28,1\text{ a }35,0\text{ °C}$ (Alerta Crítico - Estresse Térmico); $> 35,0\text{ °C}$ (Alerta Crítico Extremo).
    - **Umidade Relativa:** $< 20,0\%$ (Alerta Crítico Severo - Emergência por Secura Severa); $20,0\text{ a }29,9\%$ (Alerta Crítico - Estado de Alerta de Ar Seco); $30,0\text{ a }39,9\%$ (Alerta de Atenção - Ar Seco Moderado); $40,0\text{ a }65,0\%$ (Sem Alerta - Faixa Saudável e Confortável); $66,0\text{ a }75,0\%$ (Alerta de Atenção - Umidade Elevada); $> 75,0\%$ (Alerta Crítico - Risco de Mofo e Fungos).
    - **Dióxido de Carbono ($\text{CO}_2$):** $\le 700\text{ ppm}$ (Excelente / Sem Alerta); $701\text{ a }1000\text{ ppm}$ (Alerta de Atenção - Limite VMR da ANVISA); $1001\text{ a }1500\text{ ppm}$ (Alerta Crítico - Ventilação Inadequada e Sonolência); $1501\text{ a }2500\text{ ppm}$ (Alerta Crítico Severo - Estagnação e Cefaleia); $> 2500\text{ ppm}$ (Alerta de Emergência - Risco Agudo).
  - **Contadores de Alertas Dinâmicos no Dashboard:** O [`DashboardController::index`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/DashboardController.php#L25-L103) computa contadores iniciais de alertas para a cidade ativa nas últimas 24 horas. Ao alterar o período (`24h`, `7d`, `30d`) ou a localidade (alternando entre a **Cidade** completa ou um **Bairro** específico da jurisdição), o endpoint [`DashboardController::dadosGrafico`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/DashboardController.php#L108-L342) recalcula e retorna no JSON os cinco contadores de alerta para a janela temporal e territorial selecionada (`IQA > 40`, `PM > 15`, `Temp < 20 ou > 24`, `Umidade < 40 ou > 65`, `CO2 > 700`), atualizando reativamente os cards na interface, o rótulo da localidade (`#alerta-localidade-rotulo`) e o subtítulo da janela de análise (`#alerta-subtitulo-periodo`). Para gestores municipais (`administrador`, `planejador`, `instalador`), como a jurisdição é fixa em nível municipal, apenas o bairro é elegível como recorte geográfico variável. Para o **Super-Usuário**, o painel atua em nível global (**Painel Global de Monitoramento**), permitindo selecionar livremente qualquer cidade que possua estações de monitoramento cadastradas (`Cidade::whereHas('bairros.estacoes')`) e seus respectivos bairros com estações ativas (`GET /dashboard/cidades/{cidade}/bairros`), atualizando contadores e séries para o município ou bairro escolhido.
  - **Demarcação Visual no Gráfico:** O endpoint [`DashboardController::dadosGrafico`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/DashboardController.php#L98-L345) inclui metadados de faixas (`faixas_alerta`) para desenho de bandas sombreadas na área do canvas e classificação individual de pontos (`alertas_pontos`), destacando em cores vivas os momentos em que a média entra em faixas de atenção ou criticidade.
  - **Granularidade Adaptativa:** O agrupamento temporal adapta-se automaticamente à amplitude (`spanMinutos`) entre a primeira e a última leitura: $\le 2\text{ min} \to 5\text{s}$; $\le 10\text{ min} \to 15\text{s}$; $\le 120\text{ min} \to 1\text{ min}$; $\le 360\text{ min} \to 5\text{ min}$; $\le 1440\text{ min} \to 15\text{ min}$; $\le 10080\text{ min} \to 1\text{ hora}$; $> 10080\text{ min} \to 1\text{ dia}$.

---

## Modelagem de Domínio

```mermaid
erDiagram
    ESTADO ||--o{ CIDADE : "possui"
    CIDADE ||--o{ BAIRRO : "possui"
    CIDADE ||--o{ USER : "jurisdicao (cidade_id)"
    CIDADE ||--o{ PATRIMONIO : "estoque (cidade_id)"
    BAIRRO ||--o{ ESTACAO : "localiza (bairro_id)"
    USER ||--o{ ESTACAO : "cria (created_by)"
    USER ||--o{ ESTACAO : "instala (instalado_por)"
    USER ||--o{ ESTACAO : "solicita troca (solicitado_por)"
    USER ||--o{ PATRIMONIO : "cadastra (created_by)"
    PATRIMONIO ||--o| ESTACAO : "alocado em (patrimonio_id)"
    ESTACAO ||--o{ ESTACAO : "coordena malha (matriz_pai_id)"
    ESTACAO ||--o{ ESTACAO : "propaga sinal (estacao_origem_id)"
    ESTACAO ||--o{ MEDICAO : "registra (estacao_id)"
```

### 1. [`User`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/User.php#L30-L105) (Tabela `users`)
Representa os operadores, gestores municipais, técnicos e super-administradores do sistema.
- **Atributos Críticos:**
  - `id` (PK, bigint)
  - `name` (string), `email` (string, unique), `password` (string, hashed)
  - `nivel` (enum: `'superadmin'`, `'administrador'`, `'cadastrador'`, `'planejador'`, `'instalador'`)
  - `ativo` (boolean, default `true`)
  - `cidade_id` (FK nullable $\to$ `cidades.id`, `nullOnDelete`)
  - `two_factor_secret` (text, nullable, encrypted — segredo TOTP)
  - `two_factor_recovery_codes` (text, nullable, encrypted — códigos de backup)
  - `two_factor_confirmed_at` (timestamp, nullable — confirmação do 2FA)
  - `logradouro`, `numero`, `complemento`, `bairro`, `estado` (UF, char 2), `cep`
- **Relacionamentos Primários:**
  - `cidade()`: `BelongsTo` $\to$ [`Cidade`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Cidade.php#L11-L36)
  - `estacoes()`: `HasMany` $\to$ [`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L19-L741) (`created_by`)
  - `passkeys()`: `HasMany` $\to$ `Passkey` (`user_id`)

### 2. [`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L19-L741) (Tabela `estacoes`)
Entidade central que representa um ponto de monitoramento ambiental (planejado ou instalado em campo) e sua posição na topologia da malha.
- **Atributos Críticos:**
  - `private_id` (PK, bigint) e `public_id` (UUIDv4, unique)
  - `mac_address` (string 17, unique, nullable enquanto planejada)
  - `patrimonio_id` (FK nullable $\to$ `patrimonios.private_id`, `nullOnDelete`)
  - `tipo_estacao` (enum: `'Estação Matriz'`, `'Estação Satélite'`)
  - `status_instalacao` (enum: `'Planejada'`, `'Em Instalação'`, `'Instalada'`, `'Inativa'`)
  - `ordem_instalacao` (unsigned int, nullable — passo na cascata DFS)
  - `matriz_pai_id` (Auto-FK nullable $\to$ `estacoes.private_id`, `nullOnDelete`)
  - `estacao_origem_id` (Auto-FK nullable $\to$ `estacoes.private_id`, `nullOnDelete`)
  - `distancia_origem_metros` (decimal 8,2, nullable)
  - `coordenadas` (geometry `POINT` SRID 4326, exposto virtualmente via accessors/mutators `latitude` e `longitude`)
  - `bairro_id` (FK $\to$ `bairros.id`, `restrictOnDelete`)
  - `logradouro` (formatado em Title Case), `numero`, `bairro_nome`, `cidade_nome`, `estado_uf`, `cep`, `endereco_completo`
  - `data_instalacao` (datetime, nullable), `instalado_por` (FK nullable $\to$ `users.id`)
  - `solicitacao_substituicao` (boolean), `solicitacao_substituicao_em` (datetime), `motivo_substituicao` (text), `solicitado_por` (FK nullable $\to$ `users.id`)
  - `created_by` (FK $\to$ `users.id`, `restrictOnDelete`)
- **Relacionamentos Primários:**
  - `bairro()`: `BelongsTo` $\to$ [`Bairro`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Bairro.php#L11-L36)
  - `patrimonio()`: `BelongsTo` $\to$ [`Patrimonio`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Patrimonio.php#L12-L128)
  - `medicoes()`: `HasMany` $\to$ [`Medicao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Medicao.php#L11-L150) e `ultimaMedicao()`: `HasOne` (`latestOfMany('data_hora')`)
  - `matrizPai()` e `estacaoOrigem()`: `BelongsTo` $\to$ [`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L19-L741)
  - `satelitesFilhas()` e `satelitesMalha()`: `HasMany` $\to$ [`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L19-L741)
  - `criador()`, `instalador()`, `solicitanteSubstituicao()`: `BelongsTo` $\to$ [`User`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/User.php#L30-L105)

### 3. [`Patrimonio`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Patrimonio.php#L12-L128) (Tabela `patrimonios`)
Representa o ativo físico (placa/sensor IoT) no inventário municipal.
- **Atributos Críticos:**
  - `private_id` (PK, bigint) e `public_id` (UUIDv4, unique)
  - `cidade_id` (FK nullable $\to$ `cidades.id`, `nullOnDelete`)
  - `mac_address` (string 17, unique)
  - `numero_patrimonio` (string 50, nullable, padrão `OAir-Estacao-<cidade_id>-<XXXX>`)
  - `status` (enum: `'Disponível'`, `'Instalada'`, `'Descartado'`)
  - `data_aquisicao` (date, nullable), `observacoes` (text, nullable)
  - `created_by` (FK $\to$ `users.id`, `cascadeOnDelete`)
- **Relacionamentos Primários:**
  - `cidade()`: `BelongsTo` $\to$ [`Cidade`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Cidade.php#L11-L36)
  - `criador()`: `BelongsTo` $\to$ [`User`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/User.php#L30-L105)
  - `estacao()`: `HasOne` $\to$ [`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L19-L741) (`patrimonio_id`)

### 4. [`Medicao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Medicao.php#L11-L150) (Tabela `medicoes`)
Representa uma leitura de telemetria ambiental enviada por uma estação em um instante de tempo.
- **Atributos Críticos:**
  - `private_id` (PK, bigint) e `public_id` (UUIDv4, unique)
  - `estacao_id` (FK $\to$ `estacoes.private_id`, `cascadeOnDelete`, indexado em conjunto com `data_hora`)
  - `temperatura` (decimal 4,1 — °C)
  - `umidade` (decimal 4,1 — %)
  - `co2` (int — ppm)
  - `poeira` (decimal 6,2 — $\mu\text{g/m}^3$)
  - `iqa` (int, nullable — calculado automaticamente na criação/leitura)
  - `data_hora` (datetime)
- **Relacionamentos Primários:**
  - `estacao()`: `BelongsTo` $\to$ [`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L19-L741)

### 5. [`MalhaViaria`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/MalhaViaria.php#L8-L21) (Tabela `malha_viaria`)
Base geoespacial de segmentos de ruas importados do OpenStreetMap para suporte ao *Snap to Road* e geração de malhas em cascata.
- **Atributos Críticos:**
  - `id` (PK, bigint)
  - `logradouro` (string 256, nullable, indexado)
  - `tipo_via` (string 50, indexado — classificação `highway` do OSM)
  - `geometria` (geometry `LINESTRING` SRID 4326, com `SPATIAL INDEX`)

### 6. Entidades Geográficas ([`Estado`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estado.php#L10-L27), [`Cidade`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Cidade.php#L11-L36), [`Bairro`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Bairro.php#L11-L36))
- **[`Estado`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estado.php#L10-L27) (`estados`):** `id` (PK), `nome`, `uf` (unique, 2 chars). Possui `cidades()` (`HasMany` $\to$ [`Cidade`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Cidade.php#L11-L36)).
- **[`Cidade`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Cidade.php#L11-L36) (`cidades`):** `id` (PK), `estado_id` (FK $\to$ `estados.id`), `nome`. Pertence a `estado()` (`BelongsTo` $\to$ [`Estado`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estado.php#L10-L27)) e possui `bairros()` (`HasMany` $\to$ [`Bairro`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Bairro.php#L11-L36)).
- **[`Bairro`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Bairro.php#L11-L36) (`bairros`):** `id` (PK), `cidade_id` (FK $\to$ `cidades.id`), `nome`. Pertence a `cidade()` (`BelongsTo` $\to$ [`Cidade`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Cidade.php#L11-L36)) e possui `estacoes()` (`HasMany` $\to$ [`Estacao`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Estacao.php#L19-L741)).

### 7. [`Passkey`](file:///home/henrique_1/Documents/OpenAir-Metrics/vendor/laravel/passkeys/src/Passkey.php) (Tabela `passkeys`)
Representa uma credencial pública biométrica ou chave física FIDO2 registrada por um Administrador Municipal ou Instalador.
- **Atributos Críticos:**
  - `id` (PK, bigint)
  - `user_id` (FK $\to$ `users.id`, `cascadeOnDelete`)
  - `name` (string 255 — Ex: "MacBook Touch ID", "YubiKey 5C")
  - `credential_id` (string 255, unique — identificador da credencial pública gerado pelo autenticador)
  - `credential` (json — dados estruturados da chave pública COSE, algoritmo e contadores)
  - `last_used_at` (timestamp, nullable)
- **Relacionamentos Primários:**
  - `user()`: `BelongsTo` $\to$ [`User`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/User.php#L30-L105)
