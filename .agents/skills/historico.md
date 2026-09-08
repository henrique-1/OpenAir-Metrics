# Histórico e Estado Atual do Projeto - OpenAir Metrics

**Última Atualização:** 7 de Setembro de 2026  
**Status do Projeto:** Em Desenvolvimento Ativo  
**Versão do Framework:** Laravel 13 / PHP 8.3+ / Tailwind CSS v4

---

## 1. Visão Geral do Projeto

O **OpenAir Metrics** é uma plataforma web para monitoramento em tempo real e georreferenciado da qualidade do ar e de parâmetros meteorológicos urbanos. O sistema agrega dados de estações IoT (sensores de qualidade do ar) e fornece:

1. **Mapa Interativo Público (`/`)**: Visualização em mapa contínuo com camadas de calor (_heatmaps_) aceleradas por WebGL para métricas ambientais críticas.
2. **Painel Administrativo / Dashboard (`/dashboard`)**: Área de autenticação restrita para acompanhamento de métricas e alertas da rede de sensores.
3. **Módulo "Minhas Estações" (`/estacoes`) e Cadastro (`/estacoes/create`)**: Gerenciamento completo de estações IoT (Matrizes e Satélites), com seleção de localidades IBGE em cascata, posicionamento em mapa Leaflet com regra visual e geoespacial de proximidade de 200 metros.
4. **Servidor MCP Local (`routes/ai.php`)**: Suporte ao _Model Context Protocol_ para integração com agentes de inteligência artificial.

---

## 2. Stack Tecnológica e Dependências

### Backend & Framework

- **PHP**: `^8.3` (compatível com PHP 8.5)
- **Laravel Framework**: `^13.8`
- **Laravel Sanctum**: `^4.0` (autenticação de API baseada em tokens)
- **Laravel MCP**: `^0.8.2` (servidor de ferramentas para agentes de IA)
- **Laravel Boost**: `^2.2` (ferramentas e regras de desenvolvimento assistido)

### Frontend & Visualização

- **Tailwind CSS**: `^4.0.0` (com `@tailwindcss/vite` e paleta customizada no tema)
- **Leaflet.js**: `^1.9.4` (renderização e manipulação do mapa interativo e buffers de raio de 200m)
- **WebGL Heatmap**: (`leaflet-webgl-heatmap.js` e `webgl-heatmap.js`) para renderização acelerada por GPU
- **Blade UI Kit / Heroicons**: Componentes de ícones no Blade (`<x-heroicon-o-...>`)
- **Vite**: `^8.0.0` com `laravel-vite-plugin`

### Testes & Qualidade de Código

- **Pest PHP**: `^4.7` com `pest-plugin-laravel` (90 testes automatizados ativos, 425 asserções)
- **Laravel Pint**: `^1.27` para padronização de código PHP
- **Banco em Testes**: SQLite em memória (`:memory:`)

---

## 3. Arquitetura e Estrutura de Diretórios

```
OpenAir-Metrics/
├── .agents/
│   ├── mcp_config.json                # Configuração do servidor MCP local (openair-local)
│   └── skills/
│       └── historico.md               # Este documento de histórico e estado do projeto
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php         # Login, autenticação de sessão e logout
│   │   │   ├── EstacaoController.php      # Listagem, formulário e salvamento de estações IoT
│   │   │   └── LocalidadeController.php   # Endpoints AJAX de estados, cidades, bairros e coordenadas
│   │   └── Requests/
│   │       └── StoreEstacaoRequest.php    # Validação de formulário com regra geoespacial estrita de 200m
│   ├── Mcp/Servers/
│   │   └── OpenAirMetricsServer.php   # Servidor MCP registrado para agentes de IA
│   ├── Models/
│   │   ├── Bairro.php                 # Model de Bairro vinculado à Cidade e Estações
│   │   ├── Cidade.php                 # Model de Cidade vinculada ao Estado e Bairros
│   │   ├── Estado.php                 # Model de Estado IBGE com código UF
│   │   ├── Estacao.php                # Model de estação IoT (Accessors/Mutators POINT, regra 200m)
│   │   └── User.php                   # Model de usuário padrão (relação com estações)
│   └── Providers/
│       └── AppServiceProvider.php     # Service Provider da aplicação
├── database/
│   ├── factories/
│   │   ├── BairroFactory.php          # Factory de Bairros
│   │   ├── CidadeFactory.php          # Factory de Cidades
│   │   ├── EstadoFactory.php          # Factory de Estados
│   │   ├── EstacaoFactory.php         # Factory de Estações (estados matriz e satelite)
│   │   └── UserFactory.php            # Factory de Usuários
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── ..._create_personal_access_tokens_table.php
│   │   ├── 2026_08_26_172033_create_estacoes_table.php          # Tabela estacoes com geometry POINT
│   │   ├── 2026_08_26_172033_create_medicoes_table.php          # Tabela medicoes com índices temporais
│   │   ├── 2026_08_29_181324_create_estados_table.php           # Tabela estados (IBGE)
│   │   ├── 2026_08_29_181336_create_cidades_table.php           # Tabela cidades (IBGE)
│   │   ├── 2026_08_29_181340_create_bairros_table.php           # Tabela bairros (IBGE)
│   │   └── 2026_08_29_181357_add_fields_to_estacoes_table.php   # mac_address, tipo_estacao, bairro_id
│   └── seeders/
│       ├── DatabaseSeeder.php         # Seed principal (admin e chamada de localidades)
│       └── LocalidadesSeeder.php      # Seed dos 27 estados do Brasil e amostra de cidades/bairros
├── resources/
│   ├── css/
│   │   └── app.css                    # Definições do Tailwind v4 e paleta de cores institucional
│   ├── js/
│   │   ├── app.js                     # Inicialização do Leaflet e injeção do WebGL Heatmap
│   │   └── vendor/                    # Dependências WebGL de terceiros
│   └── views/
│       ├── auth/login.blade.php       # Tela de login estilizada
│       ├── components/
│       │   ├── layouts/app.blade.php  # Layout base da aplicação
│       │   └── sidebar.blade.php      # Navegação lateral unificada com destaque de rotas ativas
│       ├── dashboard.blade.php        # Dashboard com KPIs, métricas e listagem de estações
│       ├── estacoes/
│       │   ├── create.blade.php       # Formulário com cascata IBGE e mapa Leaflet com regra dos 200m
│       │   └── index.blade.php        # Tela "Minhas Estações" com tabela e cards de resumo
│       └── home.blade.php             # Mapa público full-screen com camadas e interpolação IDW
├── routes/
│   ├── ai.php                         # Registro do servidor MCP (OpenAirMetricsServer)
│   ├── api.php                        # Endpoints de API para localidades IBGE e coordenadas
│   ├── console.php                    # Comandos artisan customizados
│   └── web.php                        # Rotas web (guest, auth, dashboard, estacoes)
└── tests/
    ├── Feature/
    │   ├── EstacaoTest.php            # Testes de integração de estações, cascata IBGE e regra dos 200m
    │   └── ExampleTest.php            # Teste de fumaça da landing page
    └── Unit/
        ├── EstacaoUnitTest.php        # Teste unitário de fórmulas geoespaciais e accessors POINT
        └── ExampleTest.php            # Teste unitário base
```

---

## 4. Estado Atual das Funcionalidades

### 4.1 Mapa Público Interativo (`/`)

- **Visualização Full-Screen**: Ocupa 100% da tela com suporte a navegação por zoom e pan.
- **Camadas de Monitoramento (WebGL Heatmap)**: `iqa`, `temperatura`, `umidade`, `pm`, `co2`.
- **Interpolação IDW (_Inverse Distance Weighting_)**: Cálculo em tempo real ponderado pelo inverso da distância ao quadrado.
- **Pin Informativo Customizado** e **Legenda Dinâmica**.
- **Imposição Estrita de Modo Claro (Light Mode Exclusivo)**: A rota pública do mapa desativa forçadamente a classe `.dark` e renderiza permanentemente no tema claro com a versão light do logotipo (`images/3.png`), garantindo legibilidade e conformidade cromática mesmo quando o usuário tiver ativado o Dark Mode no painel administrativo.

### 4.2 Autenticação de Usuários (`/login`, `/logout`)

- Formulário estilizado com validação de campos e persistência de sessão (_Remember Me_).
- Proteção de rotas via middlewares `guest` e `auth`.

### 4.3 Painel Administrativo (`/dashboard`)

- Sidebar lateral integrada via componente `<x-sidebar active="dashboard" />`.

### 4.3 Dashboard Analítico e Séries Temporais (`/dashboard`)

- **Painel Analítico Interativo (Chart.js)**:
    - Gráfico de linhas responsivo com preenchimento em degradê (gradient fill), curvas bezier suaves e tooltips contextualizados.
    - **Filtros Geográficos**: Alternância dinâmica de agrupamento por **Cidade** ou **Bairro** com select alimentado exclusivamente por localidades com estações ativas.
    - **Seletor de Grandezas Ambientais**:
        - 🍃 **Qualidade do Ar (IQA)**: Índice composto normalizado com base em PM2.5 e CO₂.
        - 🌡️ **Temperatura**: Exibição em °C com indicador térmico.
        - 💧 **Umidade Relativa**: Exibição em % com faixas de conforto.
        - 🌫️ **Material Particulado (Poeira)**: Concentração em $\mu\text{g/m}^3$ (PM).
        - 🏭 **Dióxido de Carbono (CO₂)**: Concentração em $\text{ppm}$.
    - **Filtros de Período**: Pílulas de seleção rápida (Últimas 24 Horas, Últimos 7 Dias, Últimos 30 Dias).
    - **Cards de Resumo Estatístico em Tempo Real**:
        - 📈 **Média Calculada**: Média ponderada com badge de classificação qualitativa (ex: _Boa_, _Moderada_, _Ideal_, _Agradável_).
        - 🔺 **Pico Máximo Registrado**: Maior valor do período na localidade.
        - 🔻 **Ponto Mínimo Registrado**: Menor valor observado no intervalo.
        - 📊 **Amostras & Estações**: Total de leituras processadas e quantidade de estações ativas agregadas.
- **Endpoint AJAX Analítico**: `GET /api/dashboard/graficos` gerenciado pelo `DashboardController@dadosGrafico`.

### 4.4 Módulo "Minhas Estações" (`/estacoes`)

- Visualização dedicada para gerenciamento de dispositivos do usuário autenticado.
- Cards superiores com contadores rápidos: Total de Estações, Estações Matrizes e Estações Satélites.
- Tabela moderna com dados da placa (MAC Address, UUID), Badges de Tipo (Matriz vs Satélite), Localidade e Endereço Estruturado (Logradouro com Número, Bairro, Cidade - UF, CEP), Coordenadas e Status.
- Botão de ação primária em destaque **"Cadastrar Nova Estação"**.

### 4.5 Fluxo "Cadastro de Nova Estação" (`/estacoes/create`)

- **Cascata de Localidades IBGE & Integração Overpass Turbo**:
    - Selects encadeados (Estado -> Cidade -> Bairro) alimentados dinamicamente via AJAX.
    - Ao requisitar os bairros de uma cidade (`/api/cidades/{id}/bairros`), o backend consulta a API **Overpass Turbo** utilizando query otimizada delimitada pela UF (`ISO3166-2="BR-UF"` e `admin_level=4`) e mecanismo de failover automático entre múltiplos servidores espelho públicos (`overpass-api.de`, `overpass.kumi.systems`, `overpass.private.coffee`), cruzando os bairros retornados com os registros já existentes no banco local e inserindo apenas os registros inéditos.
    - A resposta consolidada contendo todos os bairros ordenados por nome é retornada para preencher a seleção.
- **Geocodificação Reversa, Autopreenchimento & Substituição Automática de Bairro**:
    - Ao posicionar ou arrastar o pino no Leaflet, ou ao submeter o formulário (`EstacaoController@store`), o sistema consulta a API OpenStreetMap Nominatim (`GeocodingService::obterDetalhesEndereco` ou endpoint `/api/localidades/reversa`) utilizando as coordenadas exatas da estação.
    - **Sincronização Bidirecional com os Selects**: O clique no mapa identifica o Estado, Cidade e Bairro pelas coordenadas, selecionando-os automaticamente nos selects em cascata (se o Bairro for novo, o backend cria e retorna o `bairro_id` instantaneamente via `Bairro::firstOrCreate`).
    - Extrai e persiste colunas individuais de endereço: `logradouro`, `numero`, `bairro_nome`, `cidade_nome`, `estado_uf`, `cep` e `endereco_completo`.
- **Dados da Placa**: Input de MAC Address com máscara e sanitização em tempo real; Select de Tipo ("Estação Matriz" ou "Estação Satélite").
- **Minimapa Avançado com Visualização de Rede e Raio de 200m**:
    - Foco e enquadramento automático do mapa: ao selecionar a **Cidade** o mapa transiciona suavemente no **Zoom 14**; ao selecionar o **Bairro**, o mapa aproxima automaticamente sobre o bairro selecionado no **Zoom 16**.
    - **Topologia Completa em Tempo Real**: Carrega as estações cadastradas da cidade (`/api/estacoes/coordenadas`) e desenha permanentemente um círculo de cobertura/alcance com raio de 200 metros (`L.circle`) ao redor de **todas** as estações (tanto Matrizes quanto Satélites).
    - **Linhas Poligonais da Malha Existente**: Renderiza linhas tracejadas conectando cada estação satélite já existente à sua estação de origem/matriz pai.
    - **Encadeamento de Satélites em Cadeia**: O usuário pode posicionar uma nova Estação Satélite a $\le 200\text{m}$ de **qualquer** estação já instalada (seja Matriz ou outra Satélite), permitindo topologias lineares e em árvore.
    - **Linha Elástica Dinâmica**: Ao posicionar ou arrastar o pino de uma Estação Satélite válida ($\le 200\text{m}$), o mapa desenha uma linha pontilhada dinâmica conectando o pino à estação mais próxima com tooltip flutuante exibindo a distância exata em metros.
    - Se "Estação Satélite": o JavaScript impede a fixação fora do raio de $200\text{m}$ de uma estação existente, emitindo alerta visual com a menor distância encontrada.
- **Controle de Jurisdição Municipal (`cidade_id`)**:
    - Se o usuário autenticado possuir vínculo com um município específico (`user->cidade_id`), os selects de Estado e Cidade já são renderizados travados e protegidos para sua localidade.
    - O mapa é inicializado automaticamente centrado nas coordenadas de sua cidade e a busca de estações restringe-se ao seu município.
- **Segurança e Validação Espacial no Backend (`StoreEstacaoRequest`)**:
    - Validação estrita via banco de dados (`ST_Distance_Sphere` no MySQL/MariaDB com fallback Haversine para SQLite). Se uma Estação Satélite for submetida com distância $> 200\text{m}$ de qualquer estação existente, a requisição é rejeitada com erro 422.
    - Validação de jurisdição: assegura que o `bairro_id` informado pertença estritamente à cidade do usuário logado (`user->cidade_id`), prevenindo manipulação de payload.

### 4.6 Banco de Dados e Modelagem Espacial

- **`Estado`, `Cidade`, `Bairro`**: Estrutura relacional normalizada para localidades IBGE.
- **`Patrimonio`**:
    - Campos: `private_id`, `public_id` (UUIDv4), `mac_address` (unique), `numero_patrimonio`, `tipo_sugerido`, `status` (Disponível, Alocado, Instalado, Manutenção, Descartado), `data_aquisicao`, `observacoes`, `created_by`.
    - Relacionamentos: `criador(): BelongsTo`, `estacao(): HasOne`.
- **`Estacao`**:
    - Campos: `private_id`, `public_id` (UUIDv4), `mac_address` (nullable unique), `patrimonio_id` (FK), `tipo_estacao` (enum), `status_instalacao` (Planejada, Em Instalação, Instalada, Inativa), `ordem_instalacao` (int), `matriz_pai_id` (FK), `estacao_origem_id` (FK), `distancia_origem_metros` (decimal), `data_instalacao` (datetime), `instalado_por` (FK), `bairro_id` (FK), `logradouro` (string), `numero` (string), `bairro_nome` (string), `cidade_nome` (string), `estado_uf` (string), `cep` (string), `endereco_completo` (text), `coordenadas` (POINT), `created_by` (FK).
    - Accessors e Mutators nos atributos `latitude` e `longitude` para conversão bidirecional de dados geométricos `POINT(lng lat)`.
    - Accessor `endereco` integrado com o banco de dados e `GeocodingService` para resolução de logradouro/número via OpenStreetMap Nominatim.
    - Métodos estáticos: `Estacao::menorDistanciaAte($lat, $lng)` e `Estacao::menorDistanciaAteMatriz($lat, $lng)`.
    - Relacionamentos: `bairro(): BelongsTo`, `medicoes(): HasMany`, `patrimonio(): BelongsTo`, `matrizPai(): BelongsTo`, `estacaoOrigem(): BelongsTo`, `satelitesFilhas(): HasMany`, `instalador(): BelongsTo`.
- **`Medicao`**:
    - Campos: `private_id`, `public_id` (UUIDv4), `estacao_id` (FK), `temperatura`, `umidade`, `co2`, `poeira`, `data_hora`.
    - Relacionamento: `estacao(): BelongsTo`.
    - Helper/Accessor `getIqaAttribute()` e `calcularIqa($poeira, $co2)`.
    - Factory correspondente: `MedicaoFactory`.
- **`User`**: Relações `estacoes(): HasMany` e `patrimonios(): HasMany` adicionadas.

### 4.7 Módulos de Patrimônio, Planejamento e Instalação em Campo

- **Gestão de Patrimônio (`/patrimonios`)**:
    - Inventário físico de placas/sensores adquiridos.
    - Cadastro individual e importação em lote (`storeBatch`) com normalização de formatos MAC e descarte automático de duplicados.
    - Status de ciclo de vida (`Disponível`, `Instalado`, `Manutenção`).
- **Planejador Automático de Malha de Sensores (`/estacoes/planejar`)**:
    - O usuário seleciona dinamicamente a quantidade de satélites (através de slider contínuo, input numérico ou botões rápidos de 1 a 15) e marca a Estação Matriz no mapa Leaflet.
    - O `PlanejamentoMalhaService` implementa uma **topologia em cascata multidirecional (árvore de corredores viários)**:
        - Consulta vias públicas no OpenStreetMap via Overpass com **raio de busca dinâmico** (`$raioBuscaMetros = ($quantidadeSatelites * 200) + 200;`) e filtro estrito por regex (`[highway~"^(residential|tertiary|secondary|primary)$"]`).
        - A partir da Matriz A (#1), projeta ramos em direções divergentes ao longo dos corredores de ruas do bairro (ex: Ramo 1 $\rightarrow$ B #2, B1 #3, B2 #4; Ramo 2 $\rightarrow$ C #5, C1 #6; Ramo 3 $\rightarrow$ D #7...).
        - Cada salto avança continuamente no leito da via a uma distância de $130\text{m} \le d \le 195\text{m}$ ($\le 200\text{m}$), maximizando o alcance linear e cobrindo integralmente o bairro.
        - Posiciona todos os nós estritamente sobre as ruas do OpenStreetMap e captura os nomes reais das vias (`logradouro`).
    - Gera as estações com status `Planejada`, mantendo a integridade da árvore de saltos (`estacao_origem_id`) e a ordem sequencial de instalação.
    - Renderiza no mapa as estações existentes da localidade juntamente com suas linhas de interconexão topológica.
    - **Restrição de Jurisdição Municipal**: Usuários vinculados a um município (`user->cidade_id`) têm o cálculo e a persistência do planejamento travados estritamente na sua cidade, retornando `403 Forbidden` no backend se houver tentativa de salvar em outro município.
- **Roteiro e Ordem de Instalação em Campo (`/instalacoes`)**:
    - Visão sequencial e responsiva para o técnico instalador em campo com links diretos para navegação no GPS (Google Maps / Waze).
    - Regra estrita de emparelhamento: desbloqueia a instalação de cada satélite somente após a estação anterior (ou matriz) estar ativada.
- **Motor Geoespacial Nativo MariaDB (`ST_Distance_Sphere`) & Malha Viária com Logradouro**:
    - Reversão da arquitetura para utilizar o MariaDB nativo de forma unificada no banco principal, eliminando dependências compiladas externas (SpatiaLite) na hospedagem e otimizando a volumetria (~1.5 GB).
    - Tabela `malha_viaria` estruturada com `id` (PK), `logradouro` (VARCHAR 256, indexado), `tipo_via` (VARCHAR 50, indexado) e `geometria` (LINESTRING). A migration cria a tabela sem o índice espacial inicial para permitir Bulk Inserts de alta velocidade.
    - Model `MalhaViaria` operando diretamente na conexão padrão com `$fillable = ['logradouro', 'tipo_via', 'geometria']`.
    - `MalhaViariaSeeder` com bulk insert veloz via `ogr2ogr -f "MySQL"` usando `-lco SPATIAL_INDEX=NO`, evitando gargalos severos de I/O por reconstrução de R-Tree durante a inserção de milhões de geometrias.
    - Compilação do índice espacial pós-importação em memória via `DB::statement('ALTER TABLE malha_viaria ADD SPATIAL INDEX(geometria)')` logo após o término da carga do GDAL.
    - `GeocodingService::buscarViasProximas` consultando o MariaDB com filtro ultra-rápido por Bounding Box indexada via `MBRIntersects(geometria, ST_GeomFromText(boxPolygon, 4326))`, seguido de projeção e cálculo geodésico milimétrico por segmento no PHP via Haversine, contornando a limitação do MariaDB (onde `ST_Distance_Sphere` não aceita `LINESTRING`) e retornando o `logradouro` diretamente na chave `tags.name` para uso nos roteamentos e planejamento de malha.
    - `GeocodingService::obterDetalhesEndereco` corrigido para consultar estações próximas através da coluna espacial nativa `coordenadas` (`ST_Distance_Sphere(coordenadas, point) <= 10`), eliminando o erro de coluna inexistente `latitude`/`longitude` no MariaDB.
    - Job assíncrono `ResolveReverseGeocodingJob` com `RateLimiter` (1 req/s) para resolução de detalhes faltantes (número predial e CEP) via Nominatim em segundo plano.
    - **Algoritmo de Snap to Road e Exclusão de Rodovias (`GeocodingService::snapToRoad`)**:
        - Eliminação definitiva de posicionamento de estações em rios ou pastos ("Rua Projetada"): qualquer ponto candidato é projetado ortogonalmente e discretizado sobre os eixos viários reais importados no banco de dados.
        - Exclusão rigorosa de rodovias e vias expressas (`motorway`, `trunk`, `motorway_link`, `trunk_link`) em todas as consultas SQL do MariaDB, no fallback SQLite e nos loops de projeção geométrica.
        - Garantia matemática do limite estrito de 200m: ao projetar e discretizar segmentos viários, o algoritmo descarta qualquer ponto que ultrapasse $200.0\text{m}$ em relação à estação pai (`origem`), selecionando o nó na via pública permitida que minimiza a distância até o alvo desejado mantendo a regra de proximidade intacta.
        - Validação pós-processamento no `PlanejamentoMalhaService`: todas as estações geradas passam por checagem e alinhamento viário com preenchimento obrigatório de seus logradouros reais a partir da `malha_viaria`.

### 4.8 Sistema Global de Modo Escuro e Claro (Dark/Light Mode)

- **Alternador de Tema Centralizado**: Botão dedicado incorporado à barra lateral de navegação (`components/sidebar.blade.php`), permitindo alternar instantaneamente entre os modos Claro e Escuro.
- **Prevenção de FOUC (_Flash of Unstyled Content_)**: Script inline executado no `<head>` de `layouts/app.blade.php`, sincronizando a classe `.dark` do elemento `<html>` com o `localStorage.theme` antes do primeiro ciclo de pintura do navegador.
- **Cobertura Integral das Telas Administrativas**: Adaptação sistemática das classes Tailwind com prefixo `dark:` em todos os módulos: Painel Analítico, Minhas Estações, Cadastro de Nova Estação, Planejador de Malha de Sensores, Patrimônio, Roteiro de Instalação, Gestão de Usuários e Login.
- **Logotipo Adaptativo**: Alternância automática entre a versão clara do logotipo (`images/3.png`) e a versão escura de alto contraste (`images/3-dark.png`) com base na presença da classe `.dark`.
- **Isolamento da Tela do Mapa (`/`)**: A rota pública principal executa script que força a desativação da classe `.dark`, preservando a legibilidade e renderização padrão de cartografia e WebGL.

### 4.9 Consulta e Preenchimento Automático de Endereço por CEP (ViaCEP)

- **Integração na Tela de Edição de Usuário (`/usuarios/{id}/edit`)**: Monitoramento em tempo real do campo de CEP (8 dígitos numéricos).
- **Consulta Assíncrona à API ViaCEP**: Requisição via `fetch('https://viacep.com.br/ws/{cep}/json/')` com limpeza de caracteres não numéricos.
- **Autopreenchimento Inteligente**: Preenche automaticamente Logradouro, Bairro, Cidade e UF, focando o cursor diretamente no campo de Número para agilizar o fluxo operacional.
- **Tratamento Resiliente de Erros**: Detecção de retorno `{ erro: "true" }` ou falha de conexão, informando o usuário com mensagem discreta sem interromper a digitação manual.

---

## 5. Pontos de Atenção e Débitos Técnicos

1. **Dados Mockados no Mapa Público**:
    - Os pontos de dados do mapa em `home.blade.php` continuam estáticos no script JavaScript.
2. **Implementação de Ferramentas MCP**:
    - As propriedades `$tools`, `$resources` e `$prompts` em `OpenAirMetricsServer` estão vazias, aguardando definição de ferramentas de consulta a dados climáticos.

---

## 6. Roadmap e Próximos Passos Sugeridos

- [ ] **Desenvolver API de Ingestão de Dados IoT**:
    - Endpoint `POST /api/medicoes` autenticado via Sanctum para envio de telemetria por microcontroladores (ESP32/Arduino).
- [ ] **Tornar o Mapa da Home Dinâmico**:
    - Consumir as medições em tempo real e colorir os círculos com base no IQA real da estação.
    - Conectar o script da `home.blade.php` com as leituras mais recentes das estações do banco.
- [ ] **Implementar Ferramentas no Servidor MCP**:
    - Adicionar ferramentas para agentes de IA consultarem estações próximas, alertas ativos e histórico de qualidade do ar.

---

## 7. Histórico de Alterações e Log de Sessões (Changelog)

### Sessão: 07 de Setembro de 2026

#### 1. Resumo Executivo das Entregas

- **Harmonização de Cores e Badges**: Ajuste da paleta de cores das pílulas e badges de status de vida útil (`$vida['badge_class']`), utilizando cinza claro suave para melhor legibilidade e harmonia com o design system.
- **Consulta e Preenchimento Automático por CEP (ViaCEP)**: Integração assíncrona da API pública do ViaCEP (`viacep.com.br/ws/{cep}/json/`) no formulário de edição de endereço do usuário (`usuarios/edit.blade.php`), preenchendo automaticamente Logradouro, Bairro, Cidade e UF com tratamento de erros.
- **Sistema Global de Modo Escuro e Claro (Dark/Light Mode)**:
    - Alternador interativo de tema integrado à barra lateral (`components/sidebar.blade.php`) com persistência em `localStorage.theme`.
    - Script inline anti-FOUC no `<head>` de `layouts/app.blade.php` para sincronizar a classe `.dark` antes da renderização inicial.
    - Troca dinâmica de logotipo: versão clara institucional (`images/3.png`) e versão escura (`images/3-dark.png`).
    - Cobertura integral de classes `dark:*` em todas as telas administrativas e de autenticação (Dashboard, Minhas Estações, Cadastro de Estação, Planejamento de Malha, Patrimônios, Roteiro de Instalação, Gestão de Usuários e Login).
    - Isolamento forçado de modo claro na tela do mapa público (`/`) para preservar contraste e fidelidade da renderização cartográfica e WebGL.
- **Minimapa Avançado de Cadastro de Estações (`/estacoes/create`)**:
    - Visualização completa da topologia: exibe todas as estações existentes na cidade com raios de 200m permanentemente desenhados (`L.circle`).
    - Traçado de linhas tracejadas interligando estações satélites existentes às suas matrizes/origens.
    - Encadeamento de satélites: permite posicionar uma nova estação satélite conectada a qualquer estação existente (matriz ou satélite prévia) a $\le 200\text{m}$.
    - Linha elástica dinâmica com tooltip interativo exibindo a distância em metros até a estação mais próxima elegível.
    - Geocodificação reversa bidirecional: ao clicar no mapa, o sistema resolve o endereço completo e seleciona automaticamente Estado, Cidade e Bairro nos selects em cascata (cadastrando o bairro no banco de dados se inexistente).
- **Controle Estrito de Jurisdição Municipal (`cidade_id`)**:
    - Usuários vinculados a um município específico têm as interfaces de cadastro e planejador de malha travadas e pré-selecionadas para a sua cidade.
    - O mapa centra-se automaticamente nas coordenadas da cidade e filtra estações da jurisdição.
    - Proteção em nível de backend: `StoreEstacaoRequest` valida o pertencimento de `bairro_id` à cidade do usuário, e `PlanejamentoController` retorna `403 Forbidden` caso haja tentativa de cálculo ou salvamento de malha fora do município atribuído.

#### 2. Arquivos Modificados e Criados

- `app/Http/Controllers/EstacaoController.php`: Filtragem de estações pela jurisdição da cidade do usuário e persistência de dados de ligação (`estacao_origem_id`, `matriz_pai_id`, `distancia_origem_metros`).
- `app/Http/Controllers/LocalidadeController.php`: Filtragem de estados e cidades por `user->cidade_id` e enriquecimento do endpoint `/api/localidades/reversa` para registrar e retornar IDs de bairro, cidade e estado.
- `app/Http/Controllers/PlanejamentoController.php`: Imposição de `cidade_id` da jurisdição do usuário no cálculo e bloqueio com `403 Forbidden` no salvamento fora do município.
- `app/Http/Requests/StoreEstacaoRequest.php`: Validação geoespacial de proximidade ($\le 200\text{m}$) a qualquer estação existente e validação de jurisdição do `bairro_id`.
- `resources/views/components/layouts/app.blade.php`: Inclusão do script anti-FOUC no `<head>` e chaveamento do logotipo escuro (`images/3-dark.png`).
- `resources/views/components/sidebar.blade.php`: Adição do botão alternador de Dark/Light mode com persistência em `localStorage`.
- `resources/views/estacoes/create.blade.php`: Implementação do minimapa Leaflet com raios de 200m, linhas da malha existente, encadeamento de satélites, linha elástica dinâmica, autopreenchimento de localidade e restrição de jurisdição.
- `resources/views/estacoes/planejar.blade.php`: Renderização de linhas de ligação entre estações existentes, suporte a Dark Mode e travamento de formulário pela jurisdição municipal.
- `resources/views/usuarios/edit.blade.php`: Autopreenchimento de endereço via API ViaCEP e suporte ao Dark Mode.
- `resources/views/home.blade.php`: Desativação forçada de modo escuro para garantir visualização em modo claro no mapa público.
- `resources/views/patrimonios/create.blade.php`: Adaptação de classes Tailwind para Dark Mode.
- `resources/views/instalacoes/index.blade.php`: Adaptação de classes Tailwind para Dark Mode.
- `resources/views/usuarios/create.blade.php`: Adaptação de classes Tailwind para Dark Mode.
- `tests/Feature/ThemeToggleTest.php`: Criação de testes de renderização do alternador de tema e isolamento do mapa público.
- `tests/Feature/EstacaoTest.php`: Adição de testes para encadeamento de satélites ($\le 200\text{m}$) e validação de jurisdição municipal.
- `tests/Feature/PlanejamentoEInstalacaoTest.php`: Adição de teste para proteção de jurisdição no planejador de malha.

#### 3. Testes, Formatação e Comandos Executados

- `php artisan test --compact`: Suíte de testes com 90 testes automatizados (425 asserções), 100% aprovados.
- `vendor/bin/pint --dirty --format agent`: Formatação de código conforme convenções PSR-12/Laravel.
- `php artisan view:cache`: Verificação de sintaxe de todas as views Blade compiladas.
- `npm run build`: Compilação e empacotamento dos assets Vite/Tailwind.

---

### Sessão: 07 de Setembro de 2026 (Revisão e Melhorias Gerais do Sistema)

#### 1. Resumo Executivo das Entregas

- **Ajustes de Contraste e Tipografia**:
    - Correção na escala tipográfica no `resources/css/app.css` (`--text-xs`, `--text-sm`, `--text-base`) que estava configurada com proporções diminutas (~9px e 12px), restabelecendo os padrões do Tailwind (`0.75rem`, `0.875rem`, `1rem`).
    - Ajuste nos tons da paleta `athens-gray` (400, 500, 600) para garantir conformidade com as diretrizes de acessibilidade WCAG AA (taxa de contraste $\ge 4.5:1$).
- **Responsividade Universal para Dispositivos Móveis e Tablets**:
    - Implementação de barra superior mobile (topbar) com botão de menu hambúrguer, logotipo responsivo, alternador de tema e drawer off-canvas lateral com transição suave e backdrop na `<x-sidebar />`.
    - Conversão dos wrappers principais de todas as telas administrativas para `flex flex-col md:flex-row min-h-screen w-full` e paddings adaptativos `p-4 sm:p-6 lg:p-8`.
- **Filtros e Ordenação Completa em Todas as Tabelas**:
    - **Minhas Estações (`/estacoes`)**: Busca textual, filtros por tipo (Matriz / Satélite) e status (Instalada, Pendente, Substituição Solicitada), e ordenação por colunas clicáveis com links e setas indicativas de direção.
    - **Equipe Municipal (`/usuarios`)**: Busca textual por nome/e-mail, filtros por nível de acesso (Administrador, Planejador Técnico, Instalador) e status (Ativo, Inativo), e cabeçalhos ordenáveis.
    - **Patrimônio (`/patrimonios`)**: Busca textual por MAC/Patrimônio, filtro por status e ordenação por colunas (Recentes, MAC, Patrimônio, Status, Aquisição).
    - **Ordens de Instalação (`/instalacoes`)**: Busca textual por localidade/bairro/MAC, filtro por status da matriz e ordenação com alternância de direção crescente/decrescente.
- **Gestão de Ações na Equipe Municipal**:
    - Ações para o Administrador desativar usuários ativos e reativar inativos (`PATCH /usuarios/{user}/toggle-status`), com trava de segurança impedindo a auto-desativação da conta administradora logada.
    - Tela de edição completa de servidores municipais (`/usuarios/{user}/edit` e `PUT /usuarios/{user}`), com busca e autopreenchimento de CEP via ViaCEP e validação da regra de sucessão de titularidade da prefeitura.
- **Bloqueio Estrito de Troca do Próprio E-mail**:
    - Implementada a diretriz de integridade cadastral de que **nenhum usuário pode alterar o próprio e-mail**, tanto no perfil do usuário (`/perfil`) quanto na gestão de equipe municipal (`/usuarios/{id}/edit`). O campo é renderizado desabilitado com indicador visual de bloqueio e protegido no backend.
- **Nomenclatura e Padronização Textual**:
    - Substituição oficial de "Cadastrador / Planejador Técnico" por **"Planejador Técnico"** em todo o sistema (`User::getNivelLabelAttribute`, selects e informativos).
    - Normalização de logradouros para Title/Camel Case no model `Estacao` via `Attribute::make`, preservando preposições minúsculas (ex: "Rua das Acácias e Flores").
    - Substituição do texto "MAC: Pendente" para **"MAC: Pendente de Instalação"** em `estacoes/index.blade.php` e `instalacoes/index.blade.php`.
- **Regras de Solicitação de Substituição de Estações**:
    - Botão de substituição condicionado exclusivamente a estações com status "Instalada".
    - Vida útil dos sensores $> 20\%$: exigência obrigatória de justificativa técnica no modal de solicitação.
    - Vida útil dos sensores $\le 20\%$: autopreenchimento automático do motivo com a mensagem padronizada `"Fim da vida útil da estação"`.

#### 2. Arquivos Modificados e Criados

- `resources/css/app.css`: Correção da escala tipográfica e das variáveis de cores para contraste WCAG AA.
- `resources/views/components/sidebar.blade.php`: Topbar mobile, botão hambúrguer e drawer off-canvas responsivo.
- `app/Models/User.php`: Rótulo atualizado para "Planejador Técnico" no accessor `getNivelLabelAttribute`.
- `app/Models/Estacao.php`: Mutator e Accessor para formatação de logradouro em Title/Camel Case com preposições minúsculas.
- `app/Http/Controllers/PerfilController.php` e `resources/views/perfil/edit.blade.php`: Bloqueio estrito de troca do próprio e-mail e layout responsivo mobile.
- `app/Http/Controllers/EstacaoController.php`: Busca, filtros e ordenação no `index()`, e validação condicional de motivo na substituição de estações.
- `resources/views/estacoes/index.blade.php`: Barra de busca, filtros, ordenação nos cabeçalhos, modal de substituição condicional e texto "MAC: Pendente de Instalação".
- `routes/web.php`: Registro das rotas `usuarios.toggle-status`, `usuarios.edit` e `usuarios.update`.
- `app/Http/Controllers/UsuarioController.php`: Métodos `index` (com busca, filtros e ordenação), `toggleStatus`, `edit` e `update` com bloqueio de troca de próprio e-mail e regra de sucessão de titularidade.
- `resources/views/usuarios/index.blade.php`: Coluna de Ações (Editar, Desativar/Reativar), barra de filtros e ordenação, atualização para "Planejador Técnico" e layout mobile.
- `resources/views/usuarios/create.blade.php`: Atualização de nomenclatura "Planejador Técnico" e layout mobile.
- `resources/views/usuarios/edit.blade.php`: Nova view completa com ViaCEP e proteção de e-mail próprio.
- `app/Http/Controllers/PatrimonioController.php` e `resources/views/patrimonios/index.blade.php`: Filtros, busca e ordenação por colunas na gestão de patrimônio.
- `app/Http/Controllers/InstalacaoController.php` e `resources/views/instalacoes/index.blade.php`: Busca, filtros de status, ordenação e texto "MAC: Pendente de Instalação".
- `resources/views/dashboard.blade.php`, `resources/views/estacoes/create.blade.php`, `resources/views/estacoes/planejar.blade.php`, `resources/views/patrimonios/create.blade.php`, `resources/views/instalacoes/show.blade.php`: Adequação dos wrappers para layout responsivo universal.
- `tests/Feature/EstacaoSubstituicaoTest.php`: Cobertura de testes de substituição e formatação de logradouro.
- `tests/Feature/PerfilTest.php`: Testes de preservação de e-mail inalterável.
- `tests/Feature/UsuarioMunicipalTest.php`: Testes de busca, filtros, ordenação, toggle status, edição e proibição de alteração de próprio e-mail.
- `tests/Feature/PatrimonioTest.php`: Testes de busca, filtros e ordenação de patrimônio.
- `tests/Feature/PlanejamentoEInstalacaoTest.php`: Testes de busca, filtros e ordenação em ordens de instalação.

#### 3. Testes, Formatação e Compilação

- `vendor/bin/pest`: 79 testes automatizados (382 asserções), 100% aprovados.
- `vendor/bin/pint --dirty --format agent`: Código PHP 100% aprovado pelo formatador.
- `npm run build`: Assets Vite/Tailwind compilados com sucesso.

---

### Sessão: 07 de Setembro de 2026 (Cards Interativos de Permissão e Correção da Sidebar)

#### 1. Resumo Executivo das Entregas

- **Seleção de Nível de Acesso como Cards/Botões Interativos**:
    - Substituição do elemento `<select>` tradicional de nível de acesso em `resources/views/usuarios/create.blade.php` e `resources/views/usuarios/edit.blade.php` por um sistema de cards/botões interativos, espelhando a identidade visual e o conteúdo do "Guia de Níveis e Permissões" de `resources/views/perfil/edit.blade.php`.
    - Cada nível de permissão (Planejador Técnico, Instalador, Administrador) possui um card dedicado contendo:
        - Ícone representativo contextualizado com background suave.
        - Rótulo oficial do cargo em destaque.
        - Descrição detalhada das atribuições do nível dentro da plataforma.
        - Badge informativo inferior de responsabilidade (ex: "Gestão Técnica & Malhas", "Ativação & Roteiro em Campo", "Sucessão / Titularidade Única").
        - Indicador circular de seleção com checkmark SVG que transiciona suavemente de opacidade e cor.
    - Preservação da compatibilidade com o backend: inclusão de inputs de rádio ocultos (`input[type="radio"].sr-only`) com `name="nivel"`, mantendo a validação e o processamento de formulários sem quebrar requisições POST e PUT.
    - Script JavaScript reativo `selecionarNivel(nivel)` com alternância dinâmica de classes de estilo (bordas azuis/douradas, anéis de foco, preenchimento e realce) e sincronização com a função de alerta de transferência de titularidade administrativa (`verificarNivel`).
- **Correção Definitiva do Posicionamento do Rodapé da Sidebar**:
    - Identificada e corrigida a inconsistência no posicionamento dos botões de alternância de tema, perfil e logout no componente `resources/views/components/sidebar.blade.php`.
    - **Causa Raiz**: O elemento `<aside>` utilizava `h-full` sob um container pai flex sem altura definida explicitamente, fazendo com que em páginas com pouco conteúdo vertical a sidebar assumisse altura automática (`height: auto`), impedindo a propriedade `mt-auto` de empurrar o rodapé até o fim da tela.
    - **Solução Técnica**: Aplicação das classes `md:sticky md:top-0 md:h-screen shrink-0` no container da sidebar desktop, assegurando altura exata de `100vh` e fixação contínua no topo durante rolagem, com o rodapé permanentemente alinhado à base inferior da viewport em todas as páginas do sistema.
- **Validação e Testes Automatizados**:
    - Novo teste implementado em `tests/Feature/UsuarioMunicipalTest.php` validando a renderização adequada de todos os cards e rádios interativos em `usuarios.create` e `usuarios.edit`.
    - Suíte completa do Pest executada com 100% de aprovação (101 testes e 505 asserções).
    - Código formatado pelo Laravel Pint e assets compilados via Vite (`npm run build`).

#### 2. Arquivos Modificados

- `resources/views/components/sidebar.blade.php`: Ajuste de altura e fixação desktop com `md:sticky md:top-0 md:h-screen shrink-0`.
- `resources/views/usuarios/create.blade.php`: Implementação dos cards interativos de nível de acesso com script de seleção e radios acessíveis.
- `resources/views/usuarios/edit.blade.php`: Implementação dos cards interativos com pré-seleção dinâmica do nível atual e script reativo.
- `tests/Feature/UsuarioMunicipalTest.php`: Adição de teste para os cards interativos de nível de acesso.

#### 3. Testes, Formatação e Compilação

- `php artisan test --compact`: 101 testes aprovados (505 asserções), 0 falhas.
- `vendor/bin/pint --format agent`: Formatação PHP em conformidade com o padrão do projeto.
- `npm run build`: Assets Vite/Tailwind compilados com sucesso.

---

### Sessão: 08 de Setembro de 2026 (Snap to Road na Tela de Cadastro de Estações)

#### 1. Resumo Executivo das Entregas

- **Snap to Road na Tela de Cadastro Individual (`/estacoes/create`)**:
    - Implementação da função `executarSnapToRoad` e do fluxo reativo unificado `posicionarEstacaoComSnap`, espelhando a experiência do Planejador de Malha (`/estacoes/planejar`).
    - **Estação Matriz**: Ao clicar no mapa ou soltar o pino arrastável, a coordenada é enviada para `POST /estacoes/snap-to-road` (sem estação de origem), projetando e fixando o pino exatamente sobre o leito viário da rua mais próxima no banco de dados / OpenStreetMap.
    - **Estação Satélite**: Valida a proximidade com as estações existentes na cidade e projeta a coordenada na via pública mais próxima garantindo matematicamente a distância máxima de $200.0\text{m}$ em relação à estação de origem selecionada.
    - **Reversão Defensiva e Feedback Visual**:
        - Alertas em tempo real no container `#map-feedback` com animações suaves durante a requisição de encaixe na via.
        - Se o usuário arrastar o pino para um local sem vias públicas válidas ou fora do alcance de 200m, o pino é revertido defensivamente para a posição anterior (`dragStartLatLng`) com mensagem de erro explicativa.
        - Em cliques novos em locais inválidos, um marcador circular temporário vermelho é exibido por 2,5 segundos e a posição é descartada.
    - **Sincronização com Endereço e Cascata de Localidades**: Após a fixação no leito da rua via Snap to Road, o sistema invoca `buscarEnderecoReverso`, preenchendo o popup com o logradouro real, garantindo o cadastro automático do bairro na base e sincronizando os selects de Estado, Cidade e Bairro.
    - **Aprimoramentos de UI/UX**: Inclusão de badge indicativo _"Snap to Road Ativo"_ no cabeçalho do mapa de implantação, estilos de cursor `cursor: grab` / `cursor: grabbing` e escala no hover do pino customizado, e textos explicativos atualizados nos cards de tipo de estação.

#### 2. Arquivos Modificados

- `resources/views/estacoes/create.blade.php`: Inclusão dos estilos de cursor no pino, badge de Snap to Road no cabeçalho, funções `executarSnapToRoad` e `posicionarEstacaoComSnap`, e integração nos eventos de clique no mapa e arrasto do pino.
- `tests/Feature/PlanejamentoEInstalacaoTest.php`: Adição de teste para a API `snap-to-road` sem estação de origem (cenário de Estação Matriz).
- `tests/Feature/EstacaoTest.php`: Adição de asserções garantindo que a tela de criação renderiza os recursos de Snap to Road.
- `.agents/skills/historico.md`: Registro documental desta sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `php artisan test --compact`: Suíte completa do Pest com 102 testes aprovados (511 asserções), 0 falhas.
- `vendor/bin/pint --format agent`: Formatação PHP 100% aprovada.
- `php artisan view:cache`: Compilação de templates Blade validada com sucesso.
- `npm run build`: Assets Vite/Tailwind compilados para produção.

---

### Sessão: 08 de Setembro de 2026 (Correção de Rolagem das Telas Administrativas)

#### 1. Resumo Executivo das Entregas

- **Habilitação e Correção da Rolagem Vertical (Scrolling) nas Telas do Sistema**:
    - **Diagnóstico da Causa Raiz**: O layout principal da aplicação (`resources/views/components/layouts/app.blade.php`) fixa a viewport com `<body class="... h-screen w-screen overflow-hidden ...">` e envolve o conteúdo em `<main class="h-full w-full">{{ $slot }}</main>`. Telas com wrappers definidos como `min-h-screen` permitiam que o container flexível expandisse indefinidamente além de `100vh`, impedindo o elemento `<main class="flex-1 overflow-y-auto">` interno de transbordar sua própria altura (e, portanto, nunca rolar), enquanto o `overflow-hidden` do `<body>` bloqueava a rolagem nativa da janela.
    - **Solução Técnica**: Substituição sistemática de `min-h-screen` por `h-full` no container externo (`flex flex-col md:flex-row h-full w-full ...`) e inclusão de `min-h-0` no elemento principal (`<main class="flex-1 overflow-y-auto min-h-0 ...">`) em todas as telas administrativas e de fluxo operacional:
        - **Gestão de Patrimônio**: Listagem (`patrimonios/index.blade.php`) e Cadastro de Equipamento (`patrimonios/create.blade.php`).
        - **Ordens de Instalação de Estações**: Listagem de Ordens (`instalacoes/index.blade.php`) e Roteiro de Instalação em Campo (`instalacoes/show.blade.php`).
        - **Gestão de Usuários Municipais**: Listagem da Equipe (`usuarios/index.blade.php`), Editar Usuário (`usuarios/edit.blade.php`) e Cadastrar Novo Usuário (`usuarios/create.blade.php`).
        - **Painel Analítico de Monitoramento**: Dashboard analítico e gráficos de séries temporais (`dashboard.blade.php`).
        - **Módulo de Estações IoT**: Cadastro de Estação (`estacoes/create.blade.php`), Planejador de Malha (`estacoes/planejar.blade.php`), Minhas Estações (`estacoes/index.blade.php`) e Perfil (`perfil/edit.blade.php`).
    - **Comportamento Resultante**: Rolagem suave, previsível e sem quebras visuais em desktop, tablets e dispositivos móveis (flex-col com `min-h-0`), com a barra lateral (`<x-sidebar />`) permanecendo perfeitamente alinhada e fixa.

#### 2. Arquivos Modificados

- `resources/views/patrimonios/index.blade.php`: Ajuste de container para `h-full` e `min-h-0`.
- `resources/views/patrimonios/create.blade.php`: Ajuste de container para `h-full` e `min-h-0`.
- `resources/views/instalacoes/index.blade.php`: Ajuste de container para `h-full` e `min-h-0`.
- `resources/views/instalacoes/show.blade.php`: Ajuste de container para `h-full` e `min-h-0`.
- `resources/views/usuarios/edit.blade.php`: Ajuste de container para `h-full` e `min-h-0`.
- `resources/views/usuarios/create.blade.php`: Ajuste de container para `h-full` e `min-h-0`.
- `resources/views/usuarios/index.blade.php`: Ajuste de container para `h-full` e `min-h-0`.
- `resources/views/dashboard.blade.php`: Ajuste de container para `h-full` e `min-h-0`.
- `resources/views/estacoes/create.blade.php`: Ajuste de container para `h-full` e `min-h-0`.
- `resources/views/estacoes/planejar.blade.php`: Ajuste de container para `h-full` e `min-h-0`.
- `resources/views/estacoes/index.blade.php`: Adição de `min-h-0` para resiliência de rolagem flexbox.
- `resources/views/perfil/edit.blade.php`: Adição de `min-h-0` para resiliência de rolagem flexbox.
- `.agents/skills/historico.md`: Registro documental desta sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `php artisan test --compact`: Suíte completa do Pest com 102 testes aprovados (511 asserções), 0 falhas.
- `vendor/bin/pint --format agent`: Formatação PHP validada.
- `php artisan view:cache`: Compilação de templates Blade validada com sucesso.
- `npm run build`: Assets Vite/Tailwind compilados para produção.

---

### Sessão: 08 de Setembro de 2026 (Padronização do Status Inicial de Patrimônio e Ativação em Campo)

#### 1. Resumo Executivo das Entregas

- **Remoção do Campo "Status Inicial" no Cadastro Individual de Patrimônio**:
    - Removido o `<select id="status" name="status">` do formulário individual (`resources/views/patrimonios/create.blade.php`).
    - Exibição de banner informativo contextualizando que os equipamentos são registrados sempre com status inicial **Disponível** para instalação.
- **Garantia de Regra de Negócio no Backend (`PatrimonioController`)**:
    - O método `store` não exige mais o campo `status` no payload de validação.
    - Imposição estrita de `$validated['status'] = 'Disponível';` no momento da criação, impossibilitando que qualquer equipamento entre no estoque com status divergente.
    - O cadastro em lote (`storeBatch`) já preserva o status inicial `Disponível`.
- **Transição de Status para "Instalada" na Ordem de Instalação e Ativação em Campo**:
    - No `InstalacaoController::vincularMac`, ao concluir a ativação de uma estação em campo pelo instalador, a estação é atualizada com `status_instalacao => 'Instalada'` e o equipamento no patrimônio tem seu status atualizado para `Instalado`.
    - Ajustado o cadastro on-the-fly de patrimônio no fluxo de instalação para herdar a jurisdição do município (`cidade_id`).
    - Na tela de criação de estações individuais (`EstacaoController::store`), caso o MAC informado já conste no estoque de patrimônio, o vínculo (`patrimonio_id`) é associado e o status é atualizado para `Instalado`.
    - Na listagem de patrimônio (`resources/views/patrimonios/index.blade.php`), a badge e o filtro de status foram harmonizados para exibir o termo feminino **Instalada** ("Instaladas em Campo"), condizente com a nomenclatura adotada em todo o módulo de estações.
- **Validação e Testes Automatizados**:
    - Suíte de testes do Pest expandida em `tests/Feature/PatrimonioTest.php` cobrindo o cadastro sem campo status, rejeição de status divergente forçando `Disponível`, ausência do campo no HTML do formulário, e transição de status para `Instalado` e renderização de `Instalada` ao ativar na Ordem de Instalação.
    - 105 testes automatizados aprovados (522 asserções), 0 falhas.

#### 2. Arquivos Modificados

- `resources/views/patrimonios/create.blade.php`: Remoção do campo `Status Inicial` e inclusão de banner informativo de regras de estoque e instalação.
- `app/Http/Controllers/PatrimonioController.php`: Remoção da validação de status no `store`, imposição de `$validated['status'] = 'Disponível'` e suporte aos filtros na listagem.
- `resources/views/patrimonios/index.blade.php`: Ajuste dos cards e badges da tabela para exibir "Instalada" e "Instaladas em Campo".
- `app/Http/Controllers/InstalacaoController.php`: Associação de `cidade_id` no cadastro em campo on-the-fly de patrimônio.
- `app/Http/Controllers/EstacaoController.php`: Sincronização automática de patrimônio e status `Instalado` caso o MAC pertença ao estoque.
- `tests/Feature/PatrimonioTest.php`: Adição de novos testes para validação das regras de status inicial e transição em campo.
- `.agents/skills/historico.md`: Registro documental desta sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `php artisan test --compact`: Suíte completa com 105 testes aprovados (522 asserções), 0 falhas.
- `vendor/bin/pint --format agent`: Formatação PHP 100% aprovada.
- `php artisan view:cache`: Compilação de templates Blade validada com sucesso.
- `npm run build`: Assets Vite/Tailwind compilados para produção.

---

### Sessão: 08 de Setembro de 2026 (Jurisdição Municipal Estrita nas Tabelas e Lotes de Patrimônio, e Permissão do Planejador Técnico)

#### 1. Resumo Executivo das Entregas

- **Fixação da Jurisdição Municipal no Cadastro de Patrimônio (`/patrimonios/create`)**:
    - O formulário individual e o formulário de importação em lote (`painel-lote`) foram equipados com o badge oficial de **Jurisdição Municipal Fixa** (`cidadeUsuario`), alinhado aos padrões visuais já presentes em `estacoes/create.blade.php` e `estacoes/planejar.blade.php`.
    - Os selects de município (`cidade_id` e `cidade_id_batch`) são desabilitados com `@disabled` quando o usuário possui vínculo municipal (`user->cidade_id`), com injeção de `<input type="hidden" name="cidade_id" value="{{ $cidadeUsuario->id }}">`.
    - O controller `PatrimonioController` bloqueia no backend (`abort(403)`) qualquer tentativa de submissão de município divergente do usuário, tanto no cadastro individual (`store`) quanto no cadastro em lote (`storeBatch`), forçando a chave `cidade_id` da jurisdição.
- **Permissão de Solicitação de Substituição de Sensores para o Planejador Técnico**:
    - No model `User`, adicionados os métodos auxiliares `isPlanejador(): bool` e `isPlanejadorTecnico(): bool` (retornando `true` quando `nivel === 'cadastrador' || nivel === 'planejador'`), e atualizado `isCadastrador()` para aceitar ambos.
    - No `EstacaoController@solicitarSubstituicao`, a autorização foi expandida para permitir **Administradores** e **Planejadores Técnicos** (`isPlanejador()`), mantendo o bloqueio para outros papéis como Instalador (`403 Forbidden`).
    - Adicionada verificação de jurisdição municipal no `solicitarSubstituicao`: impede que um usuário solicite substituição de sensores para uma estação localizada em outro município (`abort(403)`).
    - Na listagem de estações (`estacoes/index.blade.php`), o botão "Solicitar Substituição" foi habilitado visualmente para o Planejador Técnico (`Auth::user()->isPlanejador()`).
- **Isolamento e Filtragem Estrita por Jurisdição Municipal em Todas as Tabelas**:
    - **Minhas Estações (`/estacoes` - `EstacaoController@index`)**: O filtro foi refinado para aplicar estritamente `whereHas('bairro', fn($q) => $q->where('cidade_id', $user->cidade_id))`, eliminando cláusulas `orWhere('created_by')` que poderiam vazar estações criadas em testes fora do município.
    - **Gestão de Patrimônio (`/patrimonios` - `PatrimonioController@index`)**: Listagem estritamente filtrada por `cidade_id = $user->cidade_id`, e todos os contadores dos cards estatísticos (`total`, `disponiveis`, `instalados`, `manutencao`) escopados por município. Adicionada proteção de jurisdição no método `destroy` e na API `apiDisponiveis`.
    - **Ordens de Instalação (`/instalacoes` - `InstalacaoController@index`)**: Listagem das estações matrizes escopada estritamente pelo `cidade_id` do bairro da matriz (`whereHas('bairro', fn($q) => $q->where('cidade_id', $user->cidade_id))`).
    - **Roteiro de Instalação (`/instalacoes/{public_id}` - `InstalacaoController@show`)**: Bloqueio de acesso (`abort(403)`) caso a malha pertença a outro município, e filtragem da lista de patrimônios disponíveis em estoque (`$patrimoniosDisponiveis`) pela cidade do usuário.
    - **Ativação em Campo (`InstalacaoController@vincularMac`)**: Validação impedindo que instaladores vinculem estações ou patrimônios de municípios fora de sua jurisdição.
    - **Dashboard Analítico (`/dashboard` - `DashboardController@index` e `dadosGrafico`)**: Contadores de estações e leituras, métricas ambientais, alertas e seletores de cidades e bairros escopados pelo `cidade_id` do usuário autenticado.
    - **Equipe Municipal (`/usuarios` - `UsuarioController`)**: Mantido o isolamento completo por jurisdição em listagem, edição e alteração de status.
- **Validação e Testes Automatizados**:
    - Testes adicionados e expandidos em `PatrimonioTest.php`, `EstacaoSubstituicaoTest.php`, `EstacaoTest.php`, `PlanejamentoEInstalacaoTest.php` e `DashboardTest.php`.
    - Todos os testes passando com 100% de sucesso. Código formatado pelo Laravel Pint e assets compilados via Vite.

#### 2. Arquivos Modificados

- `app/Models/User.php`: Métodos `isPlanejador()` e `isPlanejadorTecnico()`.
- `app/Http/Controllers/EstacaoController.php`: Filtragem estrita de jurisdição no `index()` e permissão/jurisdição no `solicitarSubstituicao()`.
- `app/Http/Controllers/PatrimonioController.php`: Escopo de jurisdição no `index()`, `create()`, `store()`, `storeBatch()`, `destroy()` e `apiDisponiveis()`.
- `app/Http/Controllers/InstalacaoController.php`: Escopo de jurisdição no `index()`, `show()` e `vincularMac()`.
- `app/Http/Controllers/DashboardController.php`: Escopo de jurisdição no `index()` e `dadosGrafico()`.
- `resources/views/patrimonios/create.blade.php`: Badge de Jurisdição Municipal Fixa, desabilitação de selects e input hidden para cadastro individual e em lote.
- `resources/views/estacoes/index.blade.php`: Exibição do botão "Solicitar Substituição" para Planejador Técnico.
- `tests/Feature/PatrimonioTest.php`: Testes de isolamento por jurisdição, bloqueio 403 individual e lote, e badge de jurisdição.
- `tests/Feature/EstacaoSubstituicaoTest.php`: Testes de permissão para Planejador Técnico e bloqueio intermunicipal.
- `tests/Feature/EstacaoTest.php`: Teste de listagem estrita por jurisdição municipal.
- `tests/Feature/PlanejamentoEInstalacaoTest.php`: Testes de listagem, acesso e vinculação em ordens de instalação por município.
- `tests/Feature/DashboardTest.php`: Teste de escopo de contadores e localidades do dashboard.
- `.agents/skills/historico.md`: Registro documental da sessão.

#### 3. Testes, Formatação e Compilação

- `vendor/bin/pint --format agent`: Formatação PHP 100% aprovada.
- `php artisan view:clear && php artisan view:cache`: Compilação de templates Blade sem erros.
- `npm run build`: Assets Vite/Tailwind compilados para produção.
- `php artisan test`: Suíte de testes automatizados 100% verde.

---

### Sessão: 08 de Setembro de 2026 (Ordenação Topológica em Cascata e Prevenção de Deadlock no Roteiro de Instalação)

#### 1. Resumo Executivo das Entregas

- **Diagnóstico do Problema de Deadlock na Ordem de Instalação**:
    - No planejamento ou persistência de malhas (inclusive por operações de arrasto e re-parentamento no mapa), estações satélites podiam receber um `ordem_instalacao` menor do que a estação da qual dependiam (ex: Passo #3 apontando para `estacao_origem_id` da Estação #4).
    - Na tela de Roteiro de Instalação em Campo (`/instalacoes/{public_id}`), a renderização liberava a primeira estação não instalada da lista (Passo #3) e bloqueava as subsequentes (Passo #4).
    - Ao tentar registrar e ativar o Passo #3, o endpoint `POST /instalacoes/{public_id}/vincular-mac` interceptava a tentativa e retornava erro `422 Unprocessable Entity`: `"Atenção: A estação anterior (#4 - Estação Satélite) deve ser instalada e ativada antes desta."`, gerando um deadlock incontornável para o instalador em campo.
- **Implementação do Método Estático `Estacao::ordenarEmCascata`**:
    - Implementado em `app/Models/Estacao.php` um algoritmo de ordenação topológica baseado em travessia em profundidade (DFS) a partir da Estação Matriz raiz.
    - Garante matematicamente que a Estação Matriz seja permanentemente o Passo #1 e que cada estação satélite seja posicionada estritamente após sua respectiva estação de origem (`estacao_origem_id`), percorrendo a cascata viária de forma sequencial e natural ao longo das ruas.
    - Normaliza sequencialmente os valores de `ordem_instalacao` (1 a N) tanto no banco de dados (via `updateQuietly` para preservar integridade e performance) quanto nos modelos em memória (`setRelation('estacaoOrigem', ...)`).
- **Ajuste na Persistência do Planejador (`PlanejamentoController@salvar`)**:
    - Ao salvar uma malha planejada (após a 2ª passagem de vinculação das estações de origem), o controller executa `Estacao::ordenarEmCascata` para gravar os passos normalizados no banco desde a criação da malha.
- **Ajuste na Consulta do Roteiro (`InstalacaoController@show`)**:
    - Antes de enviar a coleção de estações para a view, `Estacao::ordenarEmCascata($estacoes)` é invocado, corrigindo de imediato qualquer malha já existente no banco de dados e sincronizando os relacionamentos em memória.
- **Refatoração da Interface do Roteiro de Instalação (`resources/views/instalacoes/show.blade.php`)**:
    - A verificação de bloqueio (`$bloqueada`) agora checa explicitamente se a estação de origem está instalada e com MAC address preenchido (`$origemInstalada = $isMatriz || ($origem && $origem->status_instalacao === 'Instalada' && ! empty($origem->mac_address));`).
    - **Badges Semânticos e Contextualizados**:
        - Estação instalada: `Ativa e Instalada` (verde).
        - Estação cuja origem não está instalada: `Aguardando Estação Anterior (#{{ $origem->ordem_instalacao }})` (cinza com cadeado).
        - Primeira estação pronta a ser instalada: `Próxima a Instalar` (dourado pulsante).
        - Outra estação com origem instalada: `Liberada para Instalação` (azul).
    - **Mensagem Explicativa Detalhada**: No card bloqueado, o sistema exibe com precisão: `"Instale e ative primeiro a estação anterior (#X - Tipo da Estação) para liberar a vinculação desta."`.
    - No indicador de distância da estação anterior: `"Conexão a Xm da Estação #Y (Tipo)"`.
- **Validação e Testes Automatizados**:
    - Testes adicionados em `tests/Feature/PlanejamentoEInstalacaoTest.php`:
        - `test('metodo estatico Estacao::ordenarEmCascata reorganiza arvore e sincroniza ordem_instalacao no banco')`: Validação unitária do algoritmo com dependências invertidas e persistência.
        - `test('roteiro de instalacao reordena estacoes topologicamente em cascata e exibe badges e estacao anterior')`: Validação de ponta a ponta na rota `instalacoes.show`, exibição de badges, ativação sequencial e ausência de erro 422.
    - Todos os 123 testes automatizados da aplicação aprovados com 100% de sucesso (586 asserções).

#### 2. Arquivos Modificados

- `app/Models/Estacao.php`: Método estático `ordenarEmCascata` com travessia DFS e normalização de `ordem_instalacao`.
- `app/Http/Controllers/InstalacaoController.php`: Invocação de `Estacao::ordenarEmCascata` no `show()`.
- `app/Http/Controllers/PlanejamentoController.php`: Invocação de `Estacao::ordenarEmCascata` na persistência do `salvar()`.
- `resources/views/instalacoes/show.blade.php`: Lógica de `$origemInstalada`, badges semânticos, mensagens claras da estação anterior e indicação da estação pai.
- `tests/Feature/PlanejamentoEInstalacaoTest.php`: Testes cobrindo a ordenação topológica e o roteiro sem deadlocks.
- `.agents/skills/historico.md`: Registro documental desta sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `vendor/bin/pint --format agent`: Formatação PHP 100% aprovada.
- `php artisan view:clear && php artisan view:cache`: Compilação de templates Blade sem erros.
- `npm run build`: Assets Vite/Tailwind compilados para produção.
- `php artisan test`: Suíte completa do Pest com 123 testes aprovados (586 asserções), 0 falhas.
