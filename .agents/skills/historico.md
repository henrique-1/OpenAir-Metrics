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

---

### Sessão: 09 de Setembro de 2026 (Ingestão de Medições por Patrimônio e Registro Automático de Data/Hora)

#### 1. Resumo Executivo das Entregas

- **Substituição de MAC Address por Patrimônio na API de Medições (`POST /api/medicoes`)**:
    - Alterada a validação do endpoint para esperar `patrimonio` (`required_without:estacao_id`) em vez de `mac_address`.
    - Resolução da estação associada via relacionamento com o modelo `Patrimonio`, buscando tanto por `numero_patrimonio` (ex: `"OAir-Estacao-1-0001"`) quanto por seu `public_id` (UUID).
    - Preservada a busca alternativa via `estacao_id` para flexibilidade.
    - Mensagens de erro 404 e 422 atualizadas para refletir a busca por patrimônio e status da estação.
    - O payload de resposta da medição registrada agora retorna a chave `patrimonio` com o número de patrimônio da estação.
- **Registro Automático de Data e Hora (`data_hora`)**:
    - Removido o campo `data_hora` da validação da API (não é mais recebido nem aceito das estações via payload).
    - No `MedicaoApiController`, a medição é gravada explicitamente com `now()`.
    - No modelo `Medicao` (`booted`), adicionada garantia de preenchimento automático no evento `creating` (`if (empty($medicao->data_hora)) { $medicao->data_hora = now(); }`), blindando qualquer fluxo de criação contra campos temporais nulos.
- **Validação e Testes Automatizados**:
    - Refatoração completa da suíte `tests/Feature/MedicaoApiTest.php` cobrindo:
        - Envio de medições via `numero_patrimonio`.
        - Envio de medições via `public_id` do patrimônio.
        - Descarte de `data_hora` enviada no payload e garantia de data e hora atuais no banco de dados.
        - Envio de medições via `estacao_id` público.
        - Retorno 404 para patrimônio não localizado.
        - Retorno 422 para estação ainda não instalada.
        - Validação dos campos obrigatórios (`patrimonio`, `temperatura`, `umidade`, `co2`, `poeira`).
        - Teste unitário de preenchimento automático de `data_hora` no ciclo de vida do modelo `Medicao`.
    - Suíte completa do Pest executada com 125 testes aprovados (594 asserções), 0 falhas.

#### 2. Arquivos Modificados

- `app/Http/Controllers/Api/MedicaoApiController.php`: Validação e busca de estação por `patrimonio`, remoção do recebimento de `data_hora` da requisição e gravação com `now()`, retorno de `patrimonio` no JSON.
- `app/Models/Medicao.php`: Garantia de preenchimento automático de `data_hora` no evento `creating` do modelo.
- `tests/Feature/MedicaoApiTest.php`: Atualização de testes para envio por patrimônio, persistência automática de data/hora e validações.
- `.agents/skills/historico.md`: Registro documental desta sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `vendor/bin/pint --format agent`: Formatação PHP 100% aprovada.
- `php artisan test --compact`: Suíte completa do Pest com 125 testes aprovados (594 asserções), 0 falhas.

---

### Sessão: 09 de Setembro de 2026 (Cálculo e Armazenamento do IQA por Interpolação Linear e Atualização do Mapa)

#### 1. Resumo Executivo das Entregas

- **Persistência do IQA na Tabela `medicoes`**:
    - Criada a migration `2026_09_09_134500_add_iqa_to_medicoes_table.php` adicionando a coluna inteira `iqa` (nullable) na tabela `medicoes`.
    - Atualizado o modelo `Medicao` adicionando `iqa` ao `$fillable` e `$casts`.
    - No `MedicaoApiController@store`, o valor do IQA é calculado imediatamente ao receber os dados dos sensores e persistido na coluna `iqa` do registro criado.
    - No hook de ciclo de vida `creating` do modelo `Medicao`, configurado cálculo de fallback automático de `iqa` caso seja omitido em qualquer criação de medição.
- **Implementação da Fórmula de Interpolação Linear do IQA**:
    - Implementada a fórmula oficial no modelo `Medicao::calcularIqa`:
      $$I_p = I_{\text{inf}} + \left[ \frac{I_{\text{sup}} - I_{\text{inf}}}{C_{\text{sup}} - C_{\text{inf}}} \right] \times (C_p - C_{\text{inf}})$$
    - O índice composto da estação é definido pelo valor mais crítico entre os poluentes avaliados: $\text{IQA} = \max(I_{\text{PM2.5}}, I_{\text{CO2}})$.
    - Tabela de pontos de corte aplicada para ambos os poluentes:
        - **Boa**: $I = 0 \text{ a } 50$ | PM2.5 $= 0 \text{ a } 25{,}0\,\mu\text{g/m}^3$ | CO2 $= 0 \text{ a } 700\,\text{ppm}$
        - **Moderada**: $I = 51 \text{ a } 100$ | PM2.5 $= >25{,}0 \text{ a } 60{,}0\,\mu\text{g/m}^3$ | CO2 $= >700 \text{ a } 1000\,\text{ppm}$
        - **Ruim**: $I = 101 \text{ a } 150$ | PM2.5 $= >60{,}0 \text{ a } 125{,}0\,\mu\text{g/m}^3$ | CO2 $= >1000 \text{ a } 1500\,\text{ppm}$
        - **Muito Ruim**: $I = 151 \text{ a } 200$ | PM2.5 $= >125{,}0 \text{ a } 210{,}0\,\mu\text{g/m}^3$ | CO2 $= >1500 \text{ a } 2500\,\text{ppm}$
        - **Péssima**: $I > 200$ (escala até $500$) | PM2.5 $= >210{,}0\,\mu\text{g/m}^3$ | CO2 $= >2500\,\text{ppm}$
- **Pontos de Corte e Exibição no Mapa Público (`home.blade.php`)**:
    - Atualizados os segmentos da camada `iqa` para os 5 níveis oficiais: **Boa**, **Moderada**, **Ruim**, **Muito Ruim** e **Péssima**.
    - Atualizada a função JavaScript `getStatusInfo` para aplicar exatamente as faixas $\le 50$, $\le 100$, $\le 150$, $\le 200$ e $> 200$.
    - Gradiente da camada de calor WebGL reconfigurado para as novas faixas e cores institucionais.
- **Validação e Testes Automatizados**:
    - Suíte expandida em `tests/Feature/MedicaoApiTest.php` validando cada faixa de corte da interpolação, dominância de poluentes e a persistência direta do `iqa` na coluna física do banco de dados.
    - Teste de séries temporais do dashboard (`DashboardTest.php`) atualizado para o novo ponto de corte da faixa Boa.
    - Suíte completa do Pest com 127 testes aprovados (605 asserções), 0 falhas.

#### 2. Arquivos Modificados e Criados

- `database/migrations/2026_09_09_134500_add_iqa_to_medicoes_table.php`: Nova migration adicionando coluna `iqa`.
- `app/Models/Medicao.php`: `$fillable`, `$casts`, hook `creating`, método `calcularIqa` e `calcularIqaPoluente`.
- `app/Http/Controllers/Api/MedicaoApiController.php`: Cálculo e gravação de `iqa` na criação da medição.
- `resources/views/home.blade.php`: Segmentos, gradiente WebGL e função `getStatusInfo` com a nova tabela de pontos de corte.
- `tests/Feature/MedicaoApiTest.php`: Testes para interpolação linear, faixas e persistência de `iqa`.
- `tests/Feature/DashboardTest.php`: Ajuste para limite superior da faixa Boa do novo IQA.
- `.agents/skills/historico.md`: Registro documental desta sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `vendor/bin/pint --format agent`: Formatação PHP 100% aprovada.
- `php artisan test --compact`: Suíte completa do Pest com 127 testes aprovados (605 asserções), 0 falhas.

---

### Sessão: 09 de Setembro de 2026 (Integração de Dados dos Sensores do Banco de Dados no Mapa Público)

#### 1. Resumo Executivo das Entregas

- **Alimentação do Mapa Público (`resources/views/home.blade.php`) com Dados do Banco**:
    - Substituídos os arrays estáticos (mock) de pontos nas camadas de monitoramento (`iqa`, `temperatura`, `umidade`, `pm`, `co2`) por injeções Blade `@json($dados...)` alimentadas diretamente pelas medições mais recentes de cada estação ativa.
    - Preservação estrita de todas as paletas de cores, gradientes WebGL, escalas e configurações visuais originais.
    - Cálculo dinâmico do centro e zoom inicial do mapa (`$centroMapa`), enquadrando automaticamente o centroide geográfico das estações com leituras válidas, ou adotando as coordenadas padrão quando não houver estações cadastradas.
- **Criação do `HomeController` e Otimização de Consultas**:
    - Criado `app/Http/Controllers/HomeController.php` responsável por extrair as estações com medições (`Estacao::whereHas('medicoes')->with(['ultimaMedicao'])`).
    - Compatibilidade geoespacial em múltiplos bancos: no MySQL/MariaDB utiliza `ST_AsText(coordenadas)`, `ST_X` e `ST_Y`; em SQLite (ambiente de testes automatizados), consome os accessors nativos do modelo `Estacao` (`latitude` e `longitude`).
    - Formatação dos dados estruturados `{ lat, lng, value }` para cada métrica ambiental, calculando o IQA em tempo real caso o valor físico na coluna ainda não estivesse populado.
- **Relacionamento `ultimaMedicao` no Modelo `Estacao`**:
    - Adicionado relacionamento `ultimaMedicao(): HasOne` utilizando `latestOfMany('data_hora')` para busca de alta performance da última leitura registrada por estação.
- **Validação e Testes Automatizados**:
    - Adicionado suporte a `RefreshDatabase` e testes de integração em `tests/Feature/ExampleTest.php` cobrindo a renderização inicial da home sem estações e com dados reais de sensores persistidos no banco.
    - Suíte completa do Pest executada com 128 testes aprovados (618 asserções), 0 falhas.
    - Código formatado pelo Laravel Pint (`vendor/bin/pint --format agent`).

#### 2. Arquivos Modificados e Criados

- `app/Http/Controllers/HomeController.php`: Novo controller para carga e agrupamento das medições mais recentes por camada para a view pública.
- `app/Models/Estacao.php`: Relacionamento `ultimaMedicao(): HasOne` via `latestOfMany('data_hora')`.
- `routes/web.php`: Rota pública `/` apontada para `HomeController@index`.
- `resources/views/home.blade.php`: Injeção de `@json($dados...)` e coordenadas dinâmicas no mapa Leaflet, mantendo gradientes e estilos visuais intactos.
- `tests/Feature/ExampleTest.php`: Testes de integração da home com e sem estações.
- `.agents/skills/historico.md`: Registro documental desta sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `vendor/bin/pint --format agent`: Formatação PHP 100% aprovada.
- `php artisan test --compact`: Suíte completa com 128 testes aprovados (618 asserções), 0 falhas.

---

### Sessão: 09 de Setembro de 2026 (Seleção Exclusiva via Dropdown no Roteiro de Instalação em Campo)

#### 1. Resumo Executivo das Entregas

- **Seleção Exclusiva via Dropdown de Equipamentos em Campo (`resources/views/instalacoes/show.blade.php`)**:
    - Removida a seleção alternada de modos ("Por Patrimônio" vs "Por MAC Address").
    - Removidos todos os campos de texto manual (`patrimonio-input` e `mac-input`), eliminando preenchimento manual ou autopreenchimento indevido.
    - O dropdown `<select>` agora é o controle único de seleção de placas disponíveis em estoque, iniciando permanentemente vazio com a opção padrão `"Selecione o equipamento (Patrimônio / MAC)..."`.
    - Cada opção do dropdown apresenta de forma clara e consolidada o número do Patrimônio e o MAC Address correspondente (`Patrimônio: {numero} — MAC: {mac}`).
    - Caso não existam equipamentos com status "Disponível" no município, o formulário desabilita os controles e exibe alerta explicativo orientando o cadastramento prévio no módulo de Patrimônio.
- **Simplificação e Otimização do Script Frontend**:
    - Removidas as funções de chaveamento de abas (`setModoVinculacao`), preenchimento auxiliar (`selecionarPatrimonioEstoque`) e máscara dinâmica de MAC.
    - A função assíncrona `vincularEstacao` foi simplificada para extrair o `patrimonio_id` diretamente do valor selecionado no dropdown e enviá-lo ao endpoint `POST /instalacoes/{public_id}/vincular-mac`.
- **Validação e Testes Automatizados**:
    - Adicionado teste de integração em `tests/Feature/PlanejamentoEInstalacaoTest.php` garantindo a presença do select com opção padrão vazia e dados de patrimônio/MAC, a ausência de abas de modo ou inputs de texto, e a ativação bem-sucedida via payload do dropdown.
    - Suíte de testes aprovada com 100% de sucesso.
    - Código formatado com o Laravel Pint.

#### 2. Arquivos Modificados

- `resources/views/instalacoes/show.blade.php`: Substituição do formulário duplo por dropdown único com Patrimônio e MAC, e script simplificado.
- `tests/Feature/PlanejamentoEInstalacaoTest.php`: Teste de integração do roteiro com seleção exclusiva via dropdown.
- `.agents/skills/historico.md`: Registro documental desta sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `vendor/bin/pint --format agent`: Formatação PHP 100% aprovada.
- `php artisan test --compact tests/Feature/PlanejamentoEInstalacaoTest.php`: 26 testes aprovados (138 asserções), 0 falhas.
- `npm run build`: Assets compilados via Vite para produção.

---

### Sessão: 09 de Setembro de 2026 (Correção de Estilização dos Botões de Métricas no Modo Escuro do Dashboard)

#### 1. Resumo Executivo das Entregas

- **Correção da Seleção de Métricas, Período e Agrupamento no Modo Escuro (`resources/views/dashboard.blade.php`)**:
    - **Diagnóstico da Causa Raiz**: Os botões de seleção de métricas (`.btn-metrica` - Qualidade do Ar, Temperatura, Umidade Relativa, Material Particulado e Dióxido de Carbono) possuíam classes de modo escuro estáticas no HTML inicial (`dark:bg-emerald-950/60`, `dark:text-emerald-200` no botão de Qualidade do Ar, e `dark:bg-athens-gray-800`, `dark:border-athens-gray-700` nos botões inativos). O JavaScript manipulava apenas classes de modo claro (`border-emerald-500`, `bg-emerald-50`, etc.) durante os cliques, mantendo o botão de Qualidade do Ar visualmente ativo no modo escuro mesmo quando a métrica de Temperatura (ou outra) era selecionada.
    - **Implementação de Atributos Reativos de Classes (`data-active-classes` e `data-inactive-classes`)**:
        - Cada botão de métrica recebeu a especificação explícita de classes completas para seus estados ativo e inativo em ambos os temas:
            - **Qualidade do Ar**: Ativo com verde esmeralda (`border-emerald-500 dark:border-emerald-500 bg-emerald-50 dark:bg-emerald-950/60 text-emerald-900 dark:text-emerald-200`);
            - **Temperatura**: Ativo com laranja dourado (`border-tahiti-gold-500 dark:border-tahiti-gold-500 bg-tahiti-gold-50 dark:bg-tahiti-gold-950/60 text-tahiti-gold-900 dark:text-tahiti-gold-200`);
            - **Umidade Relativa**: Ativo com azul (`border-dodger-blue-500 dark:border-dodger-blue-500 bg-dodger-blue-50 dark:bg-dodger-blue-950/60 text-dodger-blue-900 dark:text-dodger-blue-200`);
            - **Material Particulado**: Ativo com vermelho/cinábrio (`border-cinnabar-500 dark:border-cinnabar-500 bg-cinnabar-50 dark:bg-cinnabar-950/60 text-cinnabar-900 dark:text-cinnabar-200`);
            - **Dióxido de Carbono**: Ativo com tons de ardósia/cinza escuro (`border-athens-gray-500 dark:border-athens-gray-400 bg-athens-gray-100 dark:bg-athens-gray-800/90 text-athens-gray-900 dark:text-athens-gray-100`);
            - **Estado Inativo Unificado**: Classes neutras consistentes para light e dark mode (`border-athens-gray-200 dark:border-athens-gray-700 bg-white dark:bg-athens-gray-800 text-athens-gray-700 dark:text-athens-gray-200 hover:bg-athens-gray-50 dark:hover:bg-athens-gray-700`).
        - O mesmo padrão robusto foi estendido aos botões de agrupamento (`btn-tipo-cidade` e `btn-tipo-bairro`) e aos seletores de período (`24 Horas`, `7 Dias`, `30 Dias`).
    - **Funções Auxiliares de Sincronização em JavaScript**:
        - Implementada a função `aplicarEstadoBotao(elemento, ativo)` que extrai individualmente os tokens via `split(' ').filter(Boolean)` e aplica via `classList.add`/`classList.remove`.
        - Implementadas as rotinas `atualizarBotoesMetrica()`, `atualizarBotoesPeriodo()` e `atualizarBotoesTipo()`.
        - Integrada a re-sincronização no listener do evento customizado `themechanged`, assegurando transição visual impecável caso o usuário alterne o tema enquanto navega no dashboard.
- **Badges de Classificação com Suporte Completo ao Dark Mode (`app/Http/Controllers/DashboardController.php`)**:
    - O método `obterClassificacao` foi atualizado para retornar classes contextuais para ambos os modos (`text-*-600 dark:text-*-400 bg-*-50 dark:bg-*-950/50 border-*-200 dark:border-*-800`), garantindo contraste e visualização nítida das faixas nos cards de média calculada.
- **Validação e Testes Automatizados**:
    - Testes do Dashboard (`tests/Feature/DashboardTest.php`) executados com sucesso (8 testes, 22 asserções, 0 falhas).
    - Código formatado via Laravel Pint (`vendor/bin/pint --format agent`).
    - Assets frontend compilados com sucesso via Vite (`npm run build`).

#### 2. Arquivos Modificados

- `resources/views/dashboard.blade.php`: Configuração de `data-active-classes` e `data-inactive-classes` em todos os botões e implementação das rotinas reativas em JavaScript (`aplicarEstadoBotao`, `atualizarBotoesMetrica`, `atualizarBotoesPeriodo`, `atualizarBotoesTipo` e listener `themechanged`).
- `app/Http/Controllers/DashboardController.php`: Inclusão de classes com suporte a Dark Mode no método `obterClassificacao`.
- `.agents/skills/historico.md`: Registro documental desta sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `vendor/bin/pint --format agent`: Formatação PHP 100% aprovada.
- `php artisan test --compact tests/Feature/DashboardTest.php`: 8 testes aprovados (22 asserções), 0 falhas.
- `npm run build`: Assets compilados via Vite para produção com sucesso.

---

### Sessão: 09 de Setembro de 2026 (Exibição de Data e Hora no Marcador/Popup do Mapa Público)

#### 1. Resumo Executivo das Entregas

- **Exibição de Data e Hora no `markerHtml` do Mapa Público (`resources/views/home.blade.php`)**:
    - O marcador informativo interativo (`markerHtml`), acionado por clique no mapa público, agora exibe com elegância a data e a hora da leitura associada (`d/m/Y H:i`).
    - Inclusão do componente de ícone de relógio `<x-heroicon-o-clock class="w-3.5 h-3.5 text-[#b0b2b5] shrink-0" />`, acompanhado de divisor sutil (`border-t border-white/10 pt-1`) e texto sem quebra de linha (`whitespace-nowrap`).
    - Ajuste defensivo da largura mínima da caixa (`min-w-[155px]`) para assegurar espaçamento harmonioso sem colisão com o botão de fechar e o badge de IQA.
- **Rastreabilidade Temporal na Interpolação IDW**:
    - Implementada a função `getInterpolatedData(lat, lng, layerKey)`, que além de calcular a média ponderada espacial (IDW) do ponto clicado, identifica a estação física mais próxima (`nearestPt`) dentro do raio de influência do gradiente visual e extrai o respectivo atributo temporal `data_hora`.
    - Função `getInterpolatedValue(lat, lng, layerKey)` preservada como wrapper de compatibilidade.
- **Formatação de Data e Hora no `HomeController` (`app/Http/Controllers/HomeController.php`)**:
    - Inclusão de `data_hora` formatada (`d/m/Y H:i`) nos dados estruturados de cada camada ambiental (`dadosIqa`, `dadosTemperatura`, `dadosUmidade`, `dadosPm`, `dadosCo2`), a partir de `medicao->data_hora` com fallback para `medicao->created_at`.
- **Validação e Testes Automatizados**:
    - Expandido o teste de integração em `tests/Feature/ExampleTest.php` para validar o formato e presença de `data_hora` nas leituras fornecidas para a view da home.
    - Suíte executada com 100% de aprovação.
    - Código formatado com Laravel Pint e assets compilados via Vite (`npm run build`).

#### 2. Arquivos Modificados

- `app/Http/Controllers/HomeController.php`: Adicionado campo `data_hora` formatado nos arrays de sensores de cada camada.
- `resources/views/home.blade.php`: Implementação de `getInterpolatedData`, inclusão do bloco `dataHoraHtml` no `markerHtml` com ícone de relógio e divisor visual.
- `tests/Feature/ExampleTest.php`: Adição de asserção para verificar o campo `data_hora` na view pública.
- `.agents/skills/historico.md`: Registro documental desta sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `vendor/bin/pint --format agent`: Formatação PHP 100% aprovada.
- `php artisan test --compact tests/Feature/ExampleTest.php`: 2 testes aprovados (14 asserções), 0 falhas.
- `npm run build`: Assets compilados via Vite para produção com sucesso.

---

### Sessão: 09 de Setembro de 2026 (Implementação de WebSockets em Tempo Real com Laravel Reverb e Laravel Echo)

#### 1. Resumo Executivo das Entregas

- **Integração de WebSockets em Tempo Real no Ecossistema**:
    - Instalado e configurado o servidor WebSocket nativo **Laravel Reverb** (`laravel/reverb` `^1.11`) no backend e **Laravel Echo** (`laravel-echo` e `pusher-js`) no frontend.
    - Publicado e configurado `config/reverb.php` e atualizado `config/broadcasting.php` para utilizar a conexão `reverb`.
    - Configuradas as variáveis de ambiente em `.env` e `.env.example` (`BROADCAST_CONNECTION=reverb`, `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` e suas correspondentes `VITE_*`).
- **Evento de Broadcast de Telemetria (`NovaMedicaoRecebida`)**:
    - Criada a classe `App\Events\NovaMedicaoRecebida` implementando `ShouldBroadcastNow` para emissão imediata e síncrona pelo WebSocket no canal público `Channel('medicoes')` sob o nome `NovaMedicaoRecebida`.
    - Payload estruturado contendo a identificação da estação, coordenadas geográficas (`lat`, `lng`), leituras de todos os sensores (`iqa`, `temperatura`, `umidade`, `poeira`, `co2`) e carimbo de data e hora formatado.
    - O controller de ingestão de dados (`App\Http\Controllers\Api\MedicaoApiController`) agora dispara o evento via `NovaMedicaoRecebida::dispatch($medicao)` assim que a telemetria é gravada com sucesso.
- **Atualização Reativa do Mapa Público (`resources/views/home.blade.php`)**:
    - Instanciado o cliente Echo em `resources/js/echo.js` e exposto globalmente via `resources/js/app.js`.
    - Implementada a função `processarNovaMedicao(dados)` na view pública, que:
        1. Localiza a estação ou insere novos pontos nos arrays de memória `mapLayersData[layerKey].data.data` para todas as camadas (`iqa`, `temperatura`, `umidade`, `pm`, `co2`).
        2. Recalcula os dados de intensidade e atualiza a camada de calor ativa diretamente na GPU (`currentHeatmapLayer.setData(webglData)`).
        3. Se o visitante estiver com o popup do marcador aberto na tela (`window.clickMarker`), reexecuta `updateClickMarker(...)`, atualizando o valor numérico e o carimbo de Data e Hora no popup instantaneamente sem necessidade de recarregar a página (F5).
- **Validação e Testes Automatizados**:
    - Novo teste de integração em `tests/Feature/MedicaoApiTest.php` com `Event::fake([NovaMedicaoRecebida::class])`, validando que a requisição à API dispara o evento com canal `medicoes`, nome de evento e payload corretos.
    - Suíte executada com 100% de aprovação (11 testes e 51 asserções no `MedicaoApiTest`).
    - Assets frontend compilados com sucesso via Vite (`npm run build`).
    - Código formatado via Laravel Pint (`vendor/bin/pint --format agent`).

#### 2. Arquivos Modificados e Criados

- `composer.json` e `composer.lock`: Adição de dependência `laravel/reverb`.
- `package.json` e `package-lock.json`: Adição de `laravel-echo` e `pusher-js`.
- `config/reverb.php`: Configuração do servidor e aplicações Reverb.
- `config/broadcasting.php`: Configuração do driver de broadcasting.
- `.env` e `.env.example`: Adição de variáveis de ambiente do Reverb e Vite.
- `app/Events/NovaMedicaoRecebida.php`: Nova classe de evento com interface `ShouldBroadcastNow`.
- `app/Http/Controllers/Api/MedicaoApiController.php`: Disparo do evento `NovaMedicaoRecebida`.
- `resources/js/echo.js`: Novo script de inicialização do Laravel Echo.
- `resources/js/app.js`: Importação do `echo.js`.
- `resources/views/home.blade.php`: Função `processarNovaMedicao` e escuta do canal `medicoes`.
- `tests/Feature/MedicaoApiTest.php`: Adição de teste para validação do broadcast do evento.
- `.agents/skills/historico.md`: Registro documental desta sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `vendor/bin/pint --format agent`: Formatação PHP 100% aprovada.
- `php artisan test --compact tests/Feature/MedicaoApiTest.php`: 11 testes aprovados (51 asserções), 0 falhas.
- `php artisan test --compact tests/Feature/ExampleTest.php`: 2 testes aprovados (14 asserções), 0 falhas.
- `npm run build`: Assets compilados via Vite para produção com sucesso.

---

### Sessão: 09 de Setembro de 2026 (Extensão de WebSockets em Tempo Real para o Painel Analítico de Monitoramento)

#### 1. Resumo Executivo das Entregas

- **Atualização Dinâmica e Silenciosa no Painel Analítico (`resources/views/dashboard.blade.php`)**:
    - Integrado o listener do **Laravel Echo** escutando o canal público `medicoes` e o evento `NovaMedicaoRecebida` na tela do Dashboard Analítico.
    - Implementada a função `processarNovaMedicaoDashboard(dados)` que:
        1. Analisa a localidade atualmente selecionada pelo usuário:
            - Se agrupando por **Cidade**, verifica se a telemetria pertence à mesma `cidade_id` selecionada.
            - Se agrupando por **Bairro**, verifica se a telemetria pertence ao mesmo `bairro_id` selecionado.
            - Descarta silenciosamente medições de outras localidades para poupar requisições desnecessárias.
        2. Dispara `carregarDadosGrafico(silent = true)`:
            - Recalcula a série temporal e re-renderiza o gráfico de linhas Chart.js com transição suave da curva.
            - Atualiza os 4 cards estatísticos superiores: **Média Calculada**, **Pico Máximo**, **Ponto Mínimo** e total de **Amostras Registradas**.
            - Atualiza o badge qualitativo de classificação da média e as estações ativas.
            - O parâmetro `silent = true` previne o piscar do spinner de carregamento central, garantindo fluidez total da interface durante novas chegadas de dados.
- **Enriquecimento do Evento de Broadcast (`app/Events/NovaMedicaoRecebida.php`)**:
    - Inclusão dos atributos `cidade_id` e `bairro_id` no payload `broadcastWith()`, viabilizando filtragem local precisa no frontend do Dashboard.
- **Proteção e Resiliência na API de Ingestão (`app/Http/Controllers/Api/MedicaoApiController.php`)**:
    - Disparo do broadcast encapsulado em bloco `try/catch (\Throwable $e)` com `report($e)`. Caso o serviço Reverb esteja reiniciando ou indisponível, a resposta `201 Created` para a estação IoT é preservada e os dados continuam salvos no banco.
- **Automação no Script de Desenvolvimento (`composer.json`)**:
    - Adicionado `"php artisan reverb:start"` no comando `composer run dev`, unificando a inicialização de `server`, `queue`, `logs`, `vite` e `reverb`.
- **Padronização de Fuso Horário (`America/Sao_Paulo`)**:
    - Definido `APP_TIMEZONE=America/Sao_Paulo` no `.env` e `config/app.php`, assegurando gravação e exibição oficial no horário de Brasília (UTC-3).

#### 2. Arquivos Modificados

- `resources/views/dashboard.blade.php`: Parâmetro `silent` em `carregarDadosGrafico`, funções `processarNovaMedicaoDashboard` e `inicializarEchoDashboard`.
- `app/Events/NovaMedicaoRecebida.php`: Inclusão de `cidade_id` e `bairro_id` no payload do broadcast.
- `app/Http/Controllers/Api/MedicaoApiController.php`: Tratamento defensivo no disparo do evento.
- `composer.json`: Inclusão do Reverb no script `dev`.
- `.env` e `config/app.php`: Configuração do timezone `America/Sao_Paulo`.
- `.agents/skills/historico.md`: Registro documental desta sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `vendor/bin/pint --format agent`: Formatação PHP 100% aprovada.
- `php artisan test --compact tests/Feature/DashboardTest.php tests/Feature/MedicaoApiTest.php`: 19 testes aprovados (73 asserções), 0 falhas.
- `php artisan view:cache` e `php artisan view:clear`: Templates Blade compilados sem erros.
- `npm run build`: Assets frontend compilados com sucesso.

---

### Sessão: 09 de Setembro de 2026 (Correção da Atualização em Tempo Real do Mapa e Transição Suave do Gráfico do Dashboard)

#### 1. Resumo Executivo das Entregas

- **Correção da Atualização em Tempo Real no Mapa Público (`resources/views/home.blade.php`)**:
    - **Identificação Exata por Estação**: Adicionado o identificador `estacao_id` em todas as camadas no `HomeController.php` (`$dadosIqa`, `$dadosTemperatura`, `$dadosUmidade`, `$dadosPm`, `$dadosCo2`), permitindo que a função `processarNovaMedicao` no frontend localize o ponto correto instantaneamente sem depender exclusivamente de tolerâncias flutuantes de coordenadas geográficas.
    - **Compatibilidade Espacial no Broadcast**: No `MedicaoApiController.php` e no `NovaMedicaoRecebida.php`, adicionado o escopo `withCoordinates()` (`ST_X` e `ST_Y` do MySQL) e a associação `setRelation('estacao', $estacao)`, garantindo que a latitude e a longitude nunca cheguem como nulas ou corrompidas pelo binário WKB do MySQL no evento transmitido via WebSocket.
    - **Redesenho Completo da Camada e Marcadores**: A função `processarNovaMedicao` agora invoca `renderLayer(currentLayerKey)`, recalculando tanto a camada de calor WebGL quanto os dados interpolados do marcador/popup ativo (`updateClickMarker`). O visitante vê o valor, o badge e a Data/Hora atualizarem em tempo real sem precisar teclar F5.
    - **Resiliência do Script e Ciclo de Vida**: Removido o atributo `type="module"` e adicionada verificação segura `document.readyState` para execução do mapa sem bloqueios ou condições de corrida com o carregamento do Leaflet.
- **Transição Suave do Gráfico no Painel Analítico (`resources/views/dashboard.blade.php`)**:
    - **Eliminação do "Reset" do Gráfico**: Em `renderizarGrafico(data)`, removida a chamada incondicional de `chartInstance.destroy()` a cada chegada de dado via WebSocket.
    - Quando `chartInstance` já existe, a instância do Chart.js é atualizada _in-place_ (`chartInstance.data.labels`, `chartInstance.data.datasets[0].data`, cores, gradientes e callbacks de tooltip) seguida de `chartInstance.update()`.
    - Isso proporciona uma transição fluida e suave da curva do gráfico, eliminando piscadas em branco e a reinicialização da animação a partir do zero.
    - A recriação do gráfico (`destroy`) foi preservada exclusivamente para eventos de alternância de tema claro/escuro (`themechanged`) ou quando a consulta não retornar leituras.

#### 2. Arquivos Modificados

- `app/Http/Controllers/HomeController.php`: Adicionado `estacao_id` nos arrays de pontos de todas as camadas.
- `app/Http/Controllers/Api/MedicaoApiController.php`: Consulta da estação com `withCoordinates()` e injeção da relação no modelo da medição antes do dispatch.
- `app/Events/NovaMedicaoRecebida.php`: Fallback defensivo com `Estacao::withCoordinates()` para garantir integridade das coordenadas espaciais.
- `resources/views/home.blade.php`: Script unificado, busca por `estacao_id` e coordenadas, chamada de `renderLayer` para atualização do mapa de calor e marcador.
- `resources/views/dashboard.blade.php`: Atualização suave _in-place_ com `chartInstance.update()`, evitando o reset visual do canvas.
- `.agents/skills/historico.md`: Registro documental desta sessão.

#### 3. Testes, Formatação e Compilação

- `vendor/bin/pint --format agent`: Código PHP 100% formatado e padronizado.
- `php artisan test --compact`: Suíte completa com 130 testes automatizados (647 asserções), 100% aprovados.
- `npm run build`: Assets frontend compilados com sucesso.

- **Resolução de Gargalo e Latência de 500ms em Rajadas de Dados**:
    - **Habilitação de Múltiplos Workers**: Descomentada a diretiva `PHP_CLI_SERVER_WORKERS=4` no `.env` e `.env.example`, permitindo que o `php artisan serve` processe requisições HTTP em paralelo, eliminando o enfileiramento sequencial de conexões.
    - **Remoção de Flood de Browser Logs**: Removido o `console.log` de debug da recepção de eventos no `home.blade.php`, que disparava dezenas de requisições HTTP `POST /_boost/browser-logs` no servidor local para cada medição recebida via WebSocket.
    - **Debounce de Atualização no Dashboard**: Adicionado timer de debounce de 350ms em `processarNovaMedicaoDashboard` no `dashboard.blade.php`, consolidando rajadas simultâneas de dezenas de estações em uma única requisição AJAX ao invés de dezenas de chamadas concorrentes.

---

### Sessão: 09 de Setembro de 2026 (Exibição de Máximo e Mínimo nos Gráficos do Dashboard e Formatação do Eixo Y)

#### 1. Resumo Executivo das Entregas

- **Séries Temporais de Máximo e Mínimo nos Gráficos do Dashboard**:
    - **Agregação no Backend (`app/Http/Controllers/DashboardController.php`)**:
        - No método `dadosGrafico`, foram incorporados os cálculos de `maximos` e `minimos` para cada grupo de intervalo temporal analisado (horas ou dias), além do vetor de média `valores`.
        - Em consultas sem registros, retorna vetores vazios normalizados (`maximos: []`, `minimos: []`).
    - **Visualização Multissérie no Chart.js (`resources/views/dashboard.blade.php`)**:
        - Implementada a função `gerarDatasetsGrafico(data, isDark)`, estruturando 3 curvas simultâneas para qualquer métrica selecionada (Qualidade do Ar, Temperatura, Umidade Relativa, Material Particulado, Dióxido de Carbono):
            1. **Média (`Média`)**: Curva contínua sólida com a cor tema da métrica (`data.cor`) e espessura de 2.5px.
            2. **Valor Máximo (`Máximo`)**: Linha tracejada (`borderDash: [5, 4]`) em tom vermelho/cinnabar (`#ef4444` / `#dc2626`) indicando o teto de medições registradas.
            3. **Valor Mínimo (`Mínimo`)**: Linha tracejada (`borderDash: [5, 4]`) em tom azul/dodger (`#3b82f6` / `#0284c7`) indicando o piso de medições registradas.
        - **Legenda Interativa**: Legenda habilitada no canto superior direito (`plugins.legend.display: true`), permitindo que o operador clique para exibir ou ocultar qualquer uma das curvas dinamicamente.
        - **Atualização In-Place Suave**: Tanto no carregamento AJAX quanto nas atualizações automáticas via WebSocket (Reverb), os datasets são atualizados suavemente preservando o canvas e a animação.
- **Formatação Numérica Padronizada no Eixo Y e Tooltips**:
    - **Qualidade do Ar (IQA) e Dióxido de Carbono (CO₂)**:
        - Os valores exibidos na legenda do eixo Y (`scales.y.ticks`) agora são rigorosamente inteiros (`precision: 0`), sem casas decimais, acompanhados da respectiva unidade (ex: `50 IQA`, `450 ppm`).
    - **Demais Métricas (Temperatura, Umidade Relativa, Material Particulado)**:
        - Os valores na legenda do eixo Y agora são formatados com exatamente 2 casas decimais e separador decimal por vírgula (`toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })`), gerando marcações elegantes (ex: `24,50 °C`, `60,00 %`, `15,20 µg/m³`).
    - **Harmonização dos Tooltips e Cards**:
        - Os tooltips flutuantes e os cards de estatísticas no topo (`statMedia`, `statMaximo`, `statMinimo`) passam a seguir a mesma regra de formatação numérica (inteiro para IQA e CO2, 2 casas decimais com vírgula para os demais).

#### 2. Arquivos Modificados

- `app/Http/Controllers/DashboardController.php`: Inclusão dos arrays `maximos` e `minimos` no retorno JSON.
- `resources/views/dashboard.blade.php`: Suporte a múltiplos datasets (Média, Máximo, Mínimo), ativação de legenda interativa e formatação condicional de inteiros e decimais com vírgula no eixo Y e tooltips.
- `tests/Feature/DashboardTest.php`: Asserções para `maximos` e `minimos` no retorno da API e teste de agregação simultânea de múltiplas estações.
- `.agents/skills/historico.md`: Registro documental desta sessão.

#### 3. Testes, Formatação e Compilação

- `php artisan test --compact tests/Feature/DashboardTest.php`: 9 testes aprovados (29 asserções), 0 falhas.
- `vendor/bin/pint --format agent`: Código PHP 100% formatado.
- `npm run build`: Assets frontend compilados com sucesso.

---

### Sessão: 09 de Setembro de 2026 (Granularidade Temporal Adaptativa nos Gráficos da Dashboard)

#### 1. Resumo Executivo das Entregas

- **Correção da Granularidade e Agrupamento dos Gráficos (`app/Http/Controllers/DashboardController.php`)**:
    - **Identificação da Causa Raiz**: O agrupamento temporal do backend estava estaticamente fixado em horas (`Y-m-d H:00`). Quando medições eram geradas ou transmitidas ao longo de minutos (ex: 6.428 leituras entre 15:57 e 16:28), todas as leituras colapsavam em apenas dois baldes horários (hora 15 e hora 16), gerando exatamente dois pontos no gráfico (`15:57` e `16:28`) e resultando em linhas retas horizontais que pareciam ligar apenas o primeiro e o último registro.
    - **Implementação do Algoritmo Adaptativo (`determinarBucketTemporal`)**:
        - O sistema agora calcula a amplitude real dos dados (`spanMinutos = max_time - min_time`) e ajusta a resolução temporal de forma inteligente:
            - **<= 2 minutos**: Agrupamento a cada 5 segundos (`H:i:s`).
            - **<= 10 minutos**: Agrupamento a cada 15 segundos (`H:i:s`).
            - **<= 2 horas**: Agrupamento **minuto a minuto** (`H:i`), gerando ~30 a 120 pontos que exibem em alta fidelidade a oscilação contínua e as curvas de Média, Máximo e Mínimo.
            - **<= 6 horas**: Agrupamento a cada 5 minutos (`H:i`).
            - **<= 24 horas**: Agrupamento a cada 15 minutos (`H:i`).
            - **<= 7 dias**: Agrupamento a cada 1 hora (`d/m H:00`).
            - **> 7 dias**: Agrupamento diário (`d/m`).
- **Validação e Testes**:
    - Novo teste implementado em `tests/Feature/DashboardTest.php` validando que telemetrias em intervalos curtos preservam todos os pontos temporais com suas respectivas médias, máximos e mínimos.
    - Suíte executada com 100% de aprovação (10 testes, 33 asserções).
    - Código formatado via Laravel Pint e assets compilados via Vite.

---

### Sessão: 09 de Setembro de 2026 (Cálculo dos Blocos Estatísticos por Período e Resolução de Warning no Span Minutos)

#### 1. Resumo Executivo das Entregas

- **Eliminação do Alerta de Conversão Float-to-Int (`app/Http/Controllers/DashboardController.php`)**:
    - **Causa Raiz**: O método `Carbon::diffInMinutes()` retorna um `float` (ex: `48.13333333333333`). Ao passá-lo para `determinarBucketTemporal(Carbon $dt, int $spanMinutos, string $periodo)`, o PHP 8.1+ emitia o aviso `Implicit conversion from float to int loses precision`.
    - **Correção**:
        1. Cálculo do span arredondado para o inteiro superior mais próximo: `$spanMinutos = max(1, (int) ceil($primeiraData->diffInMinutes($ultimaData)));`.
        2. Assinatura do método flexibilizada com union type: `protected function determinarBucketTemporal(Carbon $dt, int|float $spanMinutos, string $periodo): array`.
- **Cálculo Rigoroso dos Blocos Estatísticos por Período (`24h`, `7d`, `30d`)**:
    - **Backend (`DashboardController.php`)**:
        - Validação do filtro temporal nas consultas com suporte a fallback de `created_at` caso `data_hora` seja nulo.
        - Inclusão dos metadados `'periodo'` e `'periodo_rotulo'` no payload JSON da API `/dashboard/graficos`.
        - Os blocos de Média Calculada, Valor Máximo, Valor Mínimo e Amostras são calculados estritamente sobre as medições recuperadas para o período requisitado.
    - **Frontend (`resources/views/dashboard.blade.php`)**:
        - Correção das legendas descritivas dos cards: substituição do texto estático antigo ("Pico máximo registrado na localidade") por legendas descritivas dinâmicas (`Pico máximo registrado nas últimas 24 horas`, `Pico máximo registrado nos últimos 7 dias`, `Pico máximo registrado nos últimos 30 dias`).
        - Atualização contextual em tempo real (`atualizarCardsEstatisticos`) sincronizada com as trocas de botão de período e eventos de WebSocket.
- **Validação e Testes**:
    - Novo teste de integração em `tests/Feature/DashboardTest.php` (`api de graficos calcula blocos estatisticos estritamente para o periodo selecionado`) inserindo medições distribuídas em 2 horas atrás, 3 dias atrás e 15 dias atrás, comprovando numericamente a diferenciação de média, máximo e mínimo entre os períodos 24h, 7d e 30d.
    - Suíte executada com 100% de aprovação (11 testes, 39 asserções no arquivo de Dashboard).
    - Código validado via Laravel Pint e frontend compilado via Vite.

---

### Sessão: 10 de Setembro de 2026 (Ordem Sucessória Municipal, Roteiro de Substituição de Sensores e Permissões por Papel)

#### 1. Resumo Executivo das Entregas

- **Correção da Migration MySQL/MariaDB (Erro 1265 - Data Truncated)**:
    - Resolução da falha na migration `2026_09_10_140000_update_patrimonio_status_and_user_roles.php` no MySQL/MariaDB ao alterar dados para o novo enum. A coluna `status` é temporariamente convertida em `VARCHAR(50)`, os registros antigos com valor `'Instalado'` são normalizados para `'Instalada'`, e a restrição `ENUM('Disponível', 'Instalada', 'Descartado')` é aplicada de forma estrita.
- **Ordem Sucessória de Administradores Municipais**:
    - Implementada a regra de sucessão da gestão municipal: o Administrador municipal agora pode cadastrar outro Administrador vinculado à sua jurisdição (`cidade_id`).
    - Ao concluir o cadastro do novo titular, a conta do administrador autor da ação é imediatamente desativada (`ativo = false`), sua sessão é encerrada (`Auth::logout()`, invalidação de sessão e regeneração de tokens CSRF), e o usuário é redirecionado para o login com mensagem informativa de transição de cargo.
    - Na interface de cadastro (`usuarios/create.blade.php`), foi adicionado o card interativo para o nível "Administrador" acompanhado de banner de alerta dinâmico (`aviso-sucessao-admin`) que conscientiza o gestor sobre o logout e a desativação da sua conta.
- **Remoção de Menção ao Super-usuário no Guia de Permissões**:
    - O card informativo referente ao "Super-usuário" foi excluído do Guia de Níveis e Permissões em `resources/views/perfil/edit.blade.php`, reorganizando a visualização em uma grade balanceada de 3 colunas focada exclusivamente nos níveis sob jurisdição municipal (Administrador, Planejador Técnico e Instalador).
- **Ocultação de Recursos Restritos para o Perfil Instalador**:
    - Botões que levariam o usuário com perfil de Instalador a telas restritas (gerando erros 403) foram ocultados condicionalmente com `@if(!auth()->user()?->isInstalador())`:
        - Ocultados os botões "Planejar Nova Malha" e "Planejar Primeira Malha" em `resources/views/instalacoes/index.blade.php`;
        - Ocultados os botões "Planejar Malha (Automático)" e "Cadastrar Nova Estação" em `resources/views/estacoes/index.blade.php`;
        - Ocultados o botão "Cadastrar Equipamentos" e o botão de ação de descarte de patrimônio (`title="Marcar como Descartado"`) em `resources/views/patrimonios/index.blade.php`.
- **Ordem de Instalação para Substituição de Sensores em Campo**:
    - Quando a substituição de sensores de uma estação é confirmada (pelo Planejador Técnico ou Administrador), uma Ordem de Instalação/Substituição é aberta no roteiro de campo para o Instalador:
        - Na listagem de ordens (`instalacoes/index.blade.php`), a malha passa a exibir badge pulsante de "Substituição Pendente" e o quantitativo de sensores pendentes de troca, ajustando o cálculo de progresso;
        - No roteiro de instalação (`instalacoes/show.blade.php`), a estação é realçada com badge de "Ordem de Substituição Aberta", exibe aviso descritivo com o número de patrimônio/MAC da placa anterior a ser descartada e renderiza o dropdown de seleção do novo equipamento disponível com botão "Concluir Substituição";
        - No backend (`InstalacaoController::vincularMac`), ao registrar a nova placa, o patrimônio anterior vinculado à estação passa imediatamente para o status `'Descartado'`, o novo equipamento assume o status `'Instalada'` e a pendência de substituição na estação é finalizada (`solicitacao_substituicao = false`).

#### 2. Arquivos Modificados e Criados

- `database/migrations/2026_09_10_140000_update_patrimonio_status_and_user_roles.php`: Conversão temporária para `VARCHAR(50)` antes de atualizar para `'Instalada'`, evitando o erro MySQL 1265 e aplicando ENUM estrito (`'Disponível', 'Instalada', 'Descartado'`).
- `resources/views/perfil/edit.blade.php`: Remoção da menção ao Super-usuário no Guia de Níveis e Permissões e reestruturação do grid para 3 colunas.
- `app/Http/Controllers/UsuarioController.php`: Liberação do cadastro de sucessor municipal pelo Administrador, acompanhado de logout e desativação imediata da conta do administrador atual.
- `resources/views/usuarios/create.blade.php`: Inclusão do card de nível "Administrador", banner dinâmico de aviso de sucessão e atualização da rotina JavaScript `selecionarNivel`.
- `resources/views/instalacoes/index.blade.php`: Ocultação do botão "Planejar Nova Malha" para instaladores, exibição de badge de substituição pendente e cálculo de progresso com satélites pendentes de troca.
- `resources/views/instalacoes/show.blade.php`: Tratamento de estações com `solicitacao_substituicao` ativa como ordens abertas, exibição de dados do equipamento antigo e formulário para conclusão da substituição pelo instalador.
- `app/Http/Controllers/InstalacaoController.php`: Ajuste das queries com `substituicoes_pendentes` e garantia de descarte automático do patrimônio anterior durante `vincularMac`.
- `resources/views/estacoes/index.blade.php`: Ocultação dos botões de planejar malha e cadastrar nova estação para instaladores.
- `resources/views/patrimonios/index.blade.php`: Ocultação de botão de cadastro de novos equipamentos e de descarte manual de patrimônios para instaladores.
- `tests/Feature/UsuarioMunicipalTest.php`: Atualização do teste de cadastro de administrador para validar a nova regra de sucessão municipal com desativação de conta e exibição do seletor em tela.
- `tests/Feature/PermissoesPapeisTest.php`: Atualização dos testes de permissões administrativas com sucessão, bloqueio visual de botões para o instalador e execução da ordem de substituição em campo com descarte de equipamento anterior.
- `.agents/skills/historico.md`: Registro documental da sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `php artisan test --compact tests/Feature/UsuarioMunicipalTest.php`: 10 testes executados com 100% de aprovação (61 asserções).
- `php artisan test --compact tests/Feature/PermissoesPapeisTest.php tests/Feature/EstacaoSubstituicaoTest.php`: 24 testes executados com 100% de aprovação (129 asserções).
- `php artisan test --compact`: Suíte completa com 156 testes automatizados (773 asserções), 100% aprovados.
- `vendor/bin/pint --dirty --format agent`: Formatação de código PHP 100% validada conforme o padrão do projeto.

---

### Sessão: 11 de Setembro de 2026 (Seleção em Cascata de Jurisdição Municipal, Unificação da Escala IDW e Otimizações de Zoom no Mapa)

#### 1. Resumo Executivo das Entregas

- **Seleção em Cascata de Jurisdição Municipal por Estado (`/usuarios/create` e `/usuarios/{id}/edit`)**:
    - Reestruturação do sistema de seleção de jurisdição municipal nos formulários de cadastro e edição de Administradores Municipais operados pelo Super-usuário.
    - O operador seleciona previamente o Estado (UF) em um dropdown alimentado com todos os estados brasileiros (`Estado::orderBy('nome')->get()`), e o dropdown de Município é populado dinamicamente via requisição assíncrona (`/localidades/estados/{estadoId}/cidades`) apenas com as cidades daquela unidade federativa.
    - No backend (`UsuarioController`), foram adicionadas validações estritas de `estado_id` (`nullable, exists:estados,id`), validação de vínculo garantindo que a cidade pertença ao estado selecionado e sanitização do array antes de persistir em `users`. Na edição, o estado e o município do administrador já são pré-carregados selecionados.
- **Unificação da Escala de Cores, Interpolação IDW e Legenda no Mapa Público (`home.blade.php`)**:
    - **Diagnóstico da Causa Raiz**: O componente anterior (`webgl-heatmap.js`) utilizava blending aditivo no shader WebGL (`ONE, ONE`), fazendo com que leituras próximas de estações somassem intensidades exponencialmente até saturar na cor máxima da escala (1.0), exibindo estações de 13 °C em tons marrom/vermelhos (como se fossem 40 °C). Além disso, a régua da legenda possuía paradas não lineares desconectadas da normalização da textura WebGL.
    - **Implementação do `L.IdwSurfaceLayer` em HTML5 Canvas**: Camada matricial contínua nativa do Leaflet que calcula a média física real entre os sensores utilizando ponderação pelo inverso do quadrado da distância ($w = 1 / d^2$), eliminando completamente a saturação aditiva falsa. O desvanecimento nas bordas do raio de busca (350 metros) ocorre exclusivamente pelo canal alfa nos últimos 35% do alcance, preservando a fidelidade numérica.
    - **Função Centralizadora de Cores (`getColorForValue` e `layerColorStops`)**: Mapeamento unificado de valores para RGB para todas as grandezas ambientais (IQA, Temperatura, Umidade Relativa, Material Particulado e Dióxido de Carbono).
    - **Sincronização Absoluta de Legenda e Pin**: O gradiente CSS da legenda (`linear-gradient`) agora é gerado a partir de `getColorForValue` nas posições percentuais exatas de cada parada. O cálculo do popup de clique (`getInterpolatedData`) adota rigorosamente a mesma fórmula e raio de busca do canvas, e o selo circular do IQA reflete as cores exatas da legenda (`#2ecc71` Boa, `#f1c40f` Moderada, `#e67e22` Insalubre, `#e74c3c` Perigoso, `#8e44ad` Péssima).
    - **Resolução de Erro de Inicialização Assíncrona do Vite**: A definição da classe `L.IdwSurfaceLayer` foi encapsulada na função `definirIdwSurfaceLayer()`, chamada de dentro de `inicializarMapa()` após a resolução assíncrona do módulo Leaflet (`window.L`) pelo Vite, sanando o erro `ReferenceError: L is not defined`.
- **Otimizações de Performance no Zoom-in e Zoom-out da Camada IDW**:
    - **Escalonamento Hardware via CSS Transform (`zoomanim` e `leaflet-zoom-animated`)**: Durante os 250ms da transição de zoom do Leaflet, o canvas existente é transformado e escalonado suavemente a 60 FPS pela GPU (`L.DomUtil.setTransform`), eliminando congelamentos e "piscadas" na tela.
    - **Debounce de Renderização com `requestAnimationFrame`**: Consolidação de múltiplos eventos rápidos de scroll do mouse, cancelando redesenhos intermediários e executando apenas 1 cálculo por frame de tela quando a visualização estabiliza.
    - **Resolução de Grade (`cellSize`) Adaptativa**: Tamanho de célula ajustado dinamicamente com base no zoom (4px para zoom $\le 13$, 5px para zoom 14-15, 6px para zoom $\ge 16$), economizando até 75% de ciclos de CPU em zoom aproximado sem qualquer perda de qualidade perceptível graças à suavização bilinear na GPU (`imageSmoothingQuality = 'high'`).
    - **Early-Exit no Laço IDW**: Rejeição antecipada de estações fora do raio do pixel com checagens unidimensionais (`|dx| > r` ou `|dy| > r`), suprimindo cálculos de distância e multiplicações desnecessárias.

#### 2. Arquivos Modificados e Criados

- `app/Http/Controllers/UsuarioController.php`: Carga de estados na criação/edição de usuários para Super-usuário, validação de pertinência cidade/estado e sanitização de dados.
- `resources/views/usuarios/create.blade.php`: Campo de jurisdição municipal reestruturado em 2 colunas com busca assíncrona de cidades dependente do estado selecionado.
- `resources/views/usuarios/edit.blade.php`: Campo de jurisdição municipal reestruturado com pré-seleção de estado/cidade e atualização dinâmica de cidades ao alternar o estado.
- `resources/views/home.blade.php`: Criação da camada `L.IdwSurfaceLayer`, unificação das escalas de cores em `getColorForValue`, geração do gradiente da legenda, alinhamento do popup de clique IDW, tratamento de carregamento assíncrono do Leaflet e aceleração de zoom via `zoomanim`, `cellSize` dinâmico e debounce.
- `tests/Feature/SuperUsuarioTest.php`: Adição de testes de integração para o fluxo em cascata de estado e cidades no cadastro e edição de administradores municipais pelo Super-usuário.
- `.agents/skills/historico.md`: Registro documental desta sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `php artisan test --compact tests/Feature/SuperUsuarioTest.php`: 12 testes executados com 100% de aprovação (47 asserções).
- `php artisan test --compact tests/Feature/ExampleTest.php`: 2 testes da página inicial executados com 100% de aprovação (14 asserções).
- `php artisan test --compact`: Suíte completa com 159 testes automatizados aprovados (785 asserções), 0 falhas.
- `vendor/bin/pint --dirty --format agent`: Código PHP 100% formatado e em conformidade com as regras do Laravel Pint.

---

### Sessão: 22 de Setembro de 2026 (Remediação de Vulnerabilidades de Segurança - High e Medium)

#### 1. Resumo Executivo das Entregas

- **Correção da Vulnerabilidade SEC-OAIR-001 (High - IDOR no Módulo de Substituição de Sensores)**:
    - Identificada vulnerabilidade em `EstacaoController::substituirSensor` onde a seleção do novo equipamento (`Patrimonio`) não validava a pertinência municipal (`cidade_id`) com o usuário autenticado ou a estação de destino, permitindo sequestro e alteração de status de patrimônios de municípios vizinhos.
    - Implementada checagem estrita de isolamento municipal: `$user->cidade_id && $novoPatrimonio->cidade_id && (int) $novoPatrimonio->cidade_id !== (int) $user->cidade_id`, retornando redirecionamento com mensagem de erro amigável caso a placa pertença a outra jurisdição.
    - Criado teste de regressão automatizado `tests/Feature/EstacaoTenantIsolationTest.php` comprovando que tentativas cross-tenant são bloqueadas e preservam o status do equipamento.
- **Correção da Vulnerabilidade SEC-OAIR-002 (High - Ausência de Rate Limiting na Ingestão de Telemetria)**:
    - O endpoint `POST /api/medicoes` operava sem limitação de taxa de requisições, expondo o banco de dados e os broadcasts WebSockets a inundações de dados forjados.
    - Aplicado o middleware de limitação `throttle:60,1` na rota em `routes/api.php`, restringindo a 60 requisições por minuto por IP/dispositivo.
    - Criado teste de regressão automatizado `tests/Feature/MedicaoApiRateLimitTest.php` validando o bloqueio com status HTTP 429 na 61ª requisição.
- **Correção da Vulnerabilidade SEC-OAIR-003 (Medium - Ausência de Rate Limiting no Formulário de Autenticação)**:
    - A rota `POST /login` operava sem proteção contra ataques de força bruta ou credential stuffing.
    - Aplicado middleware `throttle:5,1` e nomeação de rota `name('login.store')` em `routes/web.php`.
    - Criado teste de regressão automatizado `tests/Feature/AuthThrottleTest.php` validando bloqueio com HTTP 429 após 5 tentativas consecutivas com credenciais incorretas.
- **Correção da Vulnerabilidade SEC-OAIR-004 (Medium - Ingestão Irrestrita de Array no Planejamento de Malha / DoS)**:
    - O endpoint `POST /estacoes/salvar-malha` (`PlanejamentoController::salvar`) validava apenas `min:1` para o array `satelites`, permitindo submissão de milhares de coordenadas e disparando inserções massivas e ordenação topológica recursiva DFS em memória dentro do ciclo da requisição HTTP.
    - Adicionada regra `max:20` na validação de `satelites` em `PlanejamentoController.php`, alinhando a persistência com o limite do cálculo geométrico em `PlanejamentoMalhaService`.
    - Criado teste de regressão automatizado `tests/Feature/PlanejamentoMaxSatelitesTest.php` validando rejeição com erro de validação na sessão.

#### 2. Arquivos Modificados e Criados

- `app/Http/Controllers/EstacaoController.php`: Validação de jurisdição municipal no método `substituirSensor` para prevenir IDOR cross-tenant (SEC-OAIR-001).
- `routes/api.php`: Inclusão de `throttle:60,1` na rota `api.medicoes.store` para mitigar injeção em massa e DoS na telemetria (SEC-OAIR-002).
- `routes/web.php`: Inclusão de `throttle:5,1` e nome `login.store` na rota de login contra ataques de dicionário e brute force (SEC-OAIR-003).
- `app/Http/Controllers/PlanejamentoController.php`: Inclusão do limite `max:20` no array de satélites no método `salvar` (SEC-OAIR-004).
- `tests/Feature/EstacaoTenantIsolationTest.php`: Teste de regressão para garantia de isolamento municipal na substituição de sensores.
- `tests/Feature/MedicaoApiRateLimitTest.php`: Teste de regressão para validação do rate limiting na API de telemetria.
- `tests/Feature/AuthThrottleTest.php`: Teste de regressão para validação do rate limiting de 5 tentativas no login.
- `tests/Feature/PlanejamentoMaxSatelitesTest.php`: Teste de regressão para validação do limite máximo de 20 satélites no planejamento de malha.
- `.agents/skills/historico.md`: Registro documental desta sessão de desenvolvimento.

#### 3. Testes, Formatação e Compilação

- `vendor/bin/pint --format agent`: Formatação de código executada em conformidade com as regras do Laravel Pint.
- `php artisan test tests/Feature/EstacaoTenantIsolationTest.php tests/Feature/MedicaoApiRateLimitTest.php tests/Feature/AuthThrottleTest.php tests/Feature/PlanejamentoMaxSatelitesTest.php --compact`: 4 testes de regressão executados com 100% de aprovação (72 asserções).
- `php artisan test [suíte principal de features e units] --compact`: 128 testes executados com 100% de aprovação (616 asserções).

---

### Sessão: 22 de Setembro de 2026 (Auditoria Completa de Segurança Pós-Patches em Português Brasileiro)

#### 1. Resumo Executivo das Entregas

- **Execução de Nova Auditoria de Segurança Completa (Commit 20fe202)**:
    - Realizada nova rodada de auditoria de segurança completa em conformidade com as fases 1 a 6 da metodologia de auditoria defensiva.
    - Todos os relatórios, sumários executivos e documentações técnicas foram emitidos integralmente em **Português Brasileiro (PT-BR)** no subdiretório dedicado e com carimbo temporal: `security-audits/2026-09-22_11-35/`.
- **Revalidação de Correções Anteriores**:
    - **SEC-OAIR-001 (IDOR na Substituição de Sensores)**: Revalidado como **RESOLVIDO**. O código em `EstacaoController.php` protege o perímetro municipal contra usurpação de hardware.
    - **SEC-OAIR-003 (Força Bruta no Login)**: Revalidado como **RESOLVIDO**. O middleware `throttle:5,1` impede ataques de força bruta.
    - **SEC-OAIR-004 (Ingestão Irrestrita de Array / DoS)**: Revalidado como **RESOLVIDO**. O teto de `max:20` no array de satélites previne esgotamento de memória.
    - **SEC-OAIR-002 (Telemetria IoT)**: Reclassificado para severidade **Média** devido à atenuação do risco volumétrico com o rate limit `throttle:60,1`, permanecendo ativo o risco residual de falsificação de dados por falta de chave de dispositivo.
- **Artefatos e Validação de Esquemas**:
    - Emitidos 7 artefatos obrigatórios: `run-metadata.json`, `architecture.md`, `coverage-ledger.json`, `findings.json`, `REPORT.md`, `FINDINGS-DETAIL.md` e `NEEDS-VALIDATION.md`.
    - Os validadores oficiais `validate-findings.cjs` e `validate-coverage-ledger.cjs` foram executados e retornaram status de 100% de aprovação (7 achados válidos e 18 unidades de cobertura válidas).

#### 2. Arquivos Modificados e Criados

- `security-audits/2026-09-22_11-35/run-metadata.json`: Metadados da execução com referência ao commit `20fe202` e vínculo com a auditoria anterior.
- `security-audits/2026-09-22_11-35/architecture.md`: Modelo de arquitetura, limites de confiança municipal e revalidação de superfícies em PT-BR.
- `security-audits/2026-09-22_11-35/coverage-ledger.json`: Ledger determinístico de 18 unidades de cobertura com registro de revalidação dos patches.
- `security-audits/2026-09-22_11-35/findings.json`: Catálogo de 7 achados com descrições e títulos em português.
- `security-audits/2026-09-22_11-35/REPORT.md`: Relatório executivo completo em português.
- `security-audits/2026-09-22_11-35/FINDINGS-DETAIL.md`: Detalhamento técnico da vulnerabilidade residual SEC-OAIR-002 e registro histórico das resoluções.
- `security-audits/2026-09-22_11-35/NEEDS-VALIDATION.md`: Documentação de hipóteses dependentes de infraestrutura de produção em PT-BR.
- `.agents/skills/historico.md`: Registro documental desta sessão de auditoria.

#### 3. Testes, Formatação e Compilação

- `node .agents/skills/security-audit/validate-findings.cjs security-audits/2026-09-22_11-35/findings.json`: Aprovado (7 achados válidos).
- `node .agents/skills/security-audit/validate-coverage-ledger.cjs security-audits/2026-09-22_11-35/coverage-ledger.json`: Aprovado (18 unidades de cobertura válidas).

---

### Sessão: 22 de Setembro de 2026 (Remediação e Hardening de Vulnerabilidades Residuais da Auditoria)

#### 1. Resumo Executivo das Entregas

- **Aplicação Integral dos Patches de Segurança Aprovados**:
    - **SEC-OAIR-002 (Severidade Média - Ausência de Autenticação na Ingestão de Telemetria)**:
        - Implementada checagem obrigatória de cabeçalho `X-Sensor-Key` contra o segredo configurado em `TELEMETRY_API_KEY` (utilizando comparação temporalmente segura `hash_equals`).
        - Registrada a chave em `config/services.php` (`services.telemetry.key`) e adicionada a variável no `.env.example`.
        - Adicionado teste de regressão automatizado em `tests/Feature/MedicaoApiTest.php` validando rejeição com HTTP 401 sem chave ou com chave inválida, e aceitação com HTTP 201 quando correta.
    - **SEC-OAIR-005 (Severidade Baixa - Credenciais Pré-definidas em Seeders)**:
        - Corrigidos `database/seeders/DatabaseSeeder.php` e `database/seeders/SuperAdminSeeder.php` para aceitar senhas das variáveis de ambiente `INITIAL_ADMIN_PASSWORD` e `INITIAL_SUPERADMIN_PASSWORD`.
        - Em ambientes não-locais (produção/homologação), caso não informadas, o sistema gera senhas criptograficamente seguras aleatórias via `Str::random(32)`, eliminando credenciais previsíveis no banco.
    - **SEC-OAIR-006 (Severidade Baixa - Enumeração de Inventário Cross-Tenant Sem Município)**:
        - Atualizado o método `apiDisponiveis` em `app/Http/Controllers/PatrimonioController.php` para que usuários autenticados com `cidade_id: null` que não sejam `superadmin` recebam uma lista vazia `[]`.
        - Adicionado teste de regressão em `tests/Feature/PatrimonioTest.php`.
    - **SEC-OAIR-007 (Severidade Baixa - Configuração Permissiva de WebSocket no Reverb)**:
        - Ajustado `config/reverb.php` para restringir origens autorizadas com base em `REVERB_ALLOWED_ORIGINS` e `APP_URL`, e ativado o rate limiting de conexões de socket por padrão (`REVERB_APP_RATE_LIMITING_ENABLED=true`).
    - **SEC-OAIR-LEAD-001 & SEC-OAIR-LEAD-002 (Hardening de Ambiente e Cookies)**:
        - Atualizado `.env.example` com `APP_DEBUG=false` por padrão seguro de produção.
        - Atualizado `config/session.php` para ativar a flag `Secure` em cookies de sessão automaticamente quando `APP_ENV` for diferente de `local`.

    - **Remoção de Administrador Padrão e Aprimoramento do SuperAdminSeeder**:
        - Removida a criação do usuário `admin@admin.com` em `database/seeders/DatabaseSeeder.php`, delegando a criação inicial de credenciais exclusivamente ao `SuperAdminSeeder::class`.
        - `SuperAdminSeeder.php` aprimorado para emitir feedback no console do Artisan: avisa se a senha veio de `INITIAL_SUPERADMIN_PASSWORD`, se usou o padrão local `'superadmin123'`, ou exibe em destaque a senha criptográfica aleatória de 24 caracteres gerada para primeiro acesso quando rodado em ambiente não-local.
        - Adicionados testes em `tests/Feature/SuperUsuarioTest.php` validando a execução do seeder tanto com senha padrão quanto com `INITIAL_SUPERADMIN_PASSWORD`.

#### 2. Arquivos Modificados

- `app/Http/Controllers/Api/MedicaoApiController.php`: Inclusão da validação `X-Sensor-Key`.
- `config/services.php`: Adição do nó de configuração `services.telemetry.key`.
- `.env.example`: Configurações de `APP_DEBUG=false`, `TELEMETRY_API_KEY=` e `INITIAL_SUPERADMIN_PASSWORD=`.
- `database/seeders/DatabaseSeeder.php`: Remoção da criação de `admin@admin.com` e inclusão da chamada a `SuperAdminSeeder`.
- `database/seeders/SuperAdminSeeder.php`: Exibição da senha no console e suporte a `INITIAL_SUPERADMIN_PASSWORD`.
- `app/Http/Controllers/PatrimonioController.php`: Bloqueio de inventário cross-tenant para usuários sem jurisdição municipal.
- `config/reverb.php`: Restrição de origens WebSocket e ativação de rate limit.
- `config/session.php`: Habilitação condicional da flag `Secure` nos cookies de sessão.
- `tests/Feature/MedicaoApiTest.php`: Teste de regressão para validação de `X-Sensor-Key`.
- `tests/Feature/PatrimonioTest.php`: Teste de regressão para isolamento de patrimônio sem cidade vinculada.
- `tests/Feature/SuperUsuarioTest.php`: Testes do seeder do Super Usuário com senha padrão e customizada.
- `.agents/skills/historico.md`: Registro detalhado da sessão de remediação.

#### 3. Testes, Formatação e Compilação

- `vendor/bin/pint --dirty --format agent`: Formatação de código executada e aprovada sem violações.
- `php artisan test tests/Feature/MedicaoApiTest.php tests/Feature/PatrimonioTest.php tests/Feature/EstacaoTenantIsolationTest.php tests/Feature/MedicaoApiRateLimitTest.php tests/Feature/AuthThrottleTest.php tests/Feature/PlanejamentoMaxSatelitesTest.php tests/Feature/SuperUsuarioTest.php tests/Feature/UsuarioMunicipalTest.php --compact`: 57 testes executados com 100% de sucesso e 302 asserções.
