# Histórico e Estado Atual do Projeto - OpenAir Metrics

**Última Atualização:** 29 de Agosto de 2026  
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

- **Pest PHP**: `^4.7` com `pest-plugin-laravel` (17 testes automatizados ativos)
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
- **Geocodificação Reversa & Substituição Automática de Bairro**:
    - Ao posicionar o pino no Leaflet ou ao submeter o formulário (`EstacaoController@store`), o sistema consulta a API OpenStreetMap Nominatim (`GeocodingService::obterDetalhesEndereco`) utilizando as coordenadas exatas da estação.
    - Extrai e persiste colunas individuais de endereço: `logradouro`, `numero`, `bairro_nome`, `cidade_nome`, `estado_uf`, `cep` e `endereco_completo`.
    - **Substituição de Bairro**: Se o Nominatim identificar o bairro das coordenadas, o backend localiza ou cadastra o `Bairro` correspondente na cidade (`Bairro::firstOrCreate`) e substitui o `bairro_id` do sensor, garantindo fidelidade geográfica máxima.
- **Dados da Placa**: Input de MAC Address com máscara e sanitização em tempo real; Select de Tipo ("Estação Matriz" ou "Estação Satélite").
- **UX Interativa com Leaflet.js**:
    - Foco e enquadramento automático do mapa: ao selecionar a **Cidade** o mapa transiciona suavemente no **Zoom 14**; ao selecionar o **Bairro**, o mapa aproxima automaticamente sobre o bairro selecionado no **Zoom 16**.
    - Carregamento de estações existentes via `/api/estacoes/coordenadas`.
    - Ao selecionar **"Estação Satélite"**, o mapa desenha automaticamente círculos azuis semitransparentes (`L.circle` com raio de 200m) ao redor de todas as estações existentes.
    - Ao clicar no mapa:
        - Se "Estação Matriz": clique livre em qualquer ponto.
        - Se "Estação Satélite": o JavaScript valida se o clique está a uma distância $\le 200\text{m}$ de alguma estação prévia. Se estiver fora, bloqueia a fixação do marcador com alerta visual claro.
- **Segurança e Validação Espacial no Backend (`StoreEstacaoRequest`)**:
    - Validação estrita via banco de dados (`ST_Distance_Sphere` no MySQL com fallback Haversine para SQLite). Se uma Estação Satélite for enviada com distância $> 200\text{m}$ da estação mais próxima (ou sem nenhuma estação prévia), a submissão é bloqueada com erro 422.

### 4.6 Banco de Dados e Modelagem Espacial

- **`Estado`, `Cidade`, `Bairro`**: Estrutura relacional normalizada para localidades IBGE.
- **`Estacao`**:
    - Campos: `private_id`, `public_id` (UUIDv4), `mac_address` (unique), `tipo_estacao` (enum), `bairro_id` (FK), `logradouro` (string), `numero` (string), `bairro_nome` (string), `cidade_nome` (string), `estado_uf` (string), `cep` (string), `endereco_completo` (text), `coordenadas` (POINT), `created_by` (FK).
    - Accessors e Mutators nos atributos `latitude` e `longitude` para conversão bidirecional de dados geométricos `POINT(lng lat)`.
    - Accessor `endereco` integrado com o banco de dados e `GeocodingService` para resolução de logradouro/número via OpenStreetMap Nominatim.
    - Método estático `Estacao::menorDistanciaAte($lat, $lng)` para consultas espaciais de proximidade.
    - Relacionamentos: `bairro(): BelongsTo`, `medicoes(): HasMany`.
- **`Medicao`**:
    - Campos: `private_id`, `public_id` (UUIDv4), `estacao_id` (FK), `temperatura`, `umidade`, `co2`, `poeira`, `data_hora`.
    - Relacionamento: `estacao(): BelongsTo`.
    - Helper/Accessor `getIqaAttribute()` e `calcularIqa($poeira, $co2)`.
    - Factory correspondente: `MedicaoFactory`.
- **`User`**: Relação `estacoes(): HasMany` adicionada.

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
