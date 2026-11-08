# OpenAir-Metrics

<p align="center">
  <strong>Uma Proposta Sistêmica de Baixo Custo em IoT para Monitoramento Distribuído da Qualidade do Ar</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.5-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.5">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/IoT-ESP32-E7352C?style=flat-square&logo=espressif&logoColor=white" alt="ESP32">
  <img src="https://img.shields.io/badge/Protocol-ESP--NOW-black?style=flat-square" alt="ESP-NOW">
  <img src="https://img.shields.io/badge/WebSockets-Laravel_Reverb-FF2D20?style=flat-square" alt="Laravel Reverb">
  <img src="https://img.shields.io/badge/Database-MariaDB_Spatial-003545?style=flat-square&logo=mariadb&logoColor=white" alt="MariaDB Spatial">
  <img src="https://img.shields.io/badge/Maps-Leaflet_1.9-199900?style=flat-square&logo=leaflet&logoColor=white" alt="Leaflet">
  <img src="https://img.shields.io/badge/CSS-Tailwind_v4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS v4">
</p>

---

## 📋 Sumário

- [1. Tema da Pesquisa e Contexto do Projeto](#1-tema-da-pesquisa-e-contexto-do-projeto)
  - [1.1 Problema e Motivação Acadêmica](#11-problema-e-motivação-acadêmica)
  - [1.2 O Papel Central da Internet das Coisas (IoT)](#12-o-papel-central-da-internet-das-coisas-iot)
  - [1.3 Instrumentação Embarcada e Sensores Selecionados](#13-instrumentação-embarcada-e-sensores-selecionados)
  - [1.4 Rede Mesh em Cascata com Protocolo ESP-NOW](#14-rede-mesh-em-cascata-com-protocolo-esp-now)
- [2. Arquitetura da Solução](#2-arquitetura-da-solução)
  - [2.1 Visão Geral Multicamadas do Ecossistema](#21-visão-geral-multicamadas-do-ecossistema)
  - [2.2 Fluxo Ponta a Ponta: Da Captura Física ao Mapa em Tempo Real](#22-fluxo-ponta-a-ponta-da-captura-física-ao-mapa-em-tempo-real)
  - [2.3 Motor Geoespacial e Algoritmo de Planejamento Viário (*Snap to Road*)](#23-motor-geoespacial-e-algoritmo-de-planejamento-viário-snap-to-road)
  - [2.4 Modelagem de Dados e Domínio Espacial](#24-modelagem-de-dados-e-domínio-espacial)
- [3. Requisitos do Sistema](#3-requisitos-do-sistema)
  - [3.1 Requisitos Funcionais (RF01 a RF24)](#31-requisitos-funcionais-rf01-a-rf24)
  - [3.2 Requisitos Não Funcionais (RNF01 a RNF14)](#32-requisitos-não-funcionais-rnf01-a-rnf14)
  - [3.3 Regras de Negócio Fundamentais (RN01 a RN16)](#33-regras-de-negócio-fundamentais-rn01-a-rn16)
- [4. Especificação de Casos de Uso](#4-especificação-de-casos-de-uso)
  - [4.1 Catálogo de Atores do Sistema](#41-catálogo-de-atores-do-sistema)
  - [4.2 Matriz Sintética de Casos de Uso (UC01 a UC20)](#42-matriz-sintética-de-casos-de-uso-uc01-a-uc20)
- [5. Inteligência Ambiental e Cálculo do IQA](#5-inteligência-ambiental-e-cálculo-do-iqa)
  - [5.1 Formulação Matemática do IQA](#51-formulação-matemática-do-iqa)
  - [5.2 Faixas de Qualidade segundo CONAMA e ANVISA](#52-faixas-de-qualidade-segundo-conama-e-anvisa)
- [6. Stack Tecnológica](#6-stack-tecnológica)
- [7. Estrutura do Repositório e Documentação Técnica](#7-estrutura-do-repositório-e-documentação-técnica)
- [8. Guia de Instalação e Execução Local](#8-guia-de-instalação-e-execução-local)
  - [8.1 Pré-requisitos](#81-pré-requisitos)
  - [8.2 Configuração do Ambiente (.env)](#82-configuração-do-ambiente-env)
  - [8.3 Instalação das Dependências](#83-instalação-das-dependências)
  - [8.4 Criação e População Automática do Banco de Dados (Migrate & Seed)](#84-criação-e-população-automática-do-banco-de-dados-migrate--seed)
  - [8.5 Inicialização dos Serviços (composer run dev -vv)](#85-inicialização-dos-serviços-composer-run-dev--vv)
  - [8.6 Acesso e Credenciais de Desenvolvimento](#86-acesso-e-credenciais-de-desenvolvimento)
- [9. Testes Automatizados e Qualidade de Código](#9-testes-automatizados-e-qualidade-de-código)

---

## 1. Tema da Pesquisa e Contexto do Projeto

### 1.1 Problema e Motivação Acadêmica

O projeto **OpenAir-Metrics** é resultado de uma pesquisa desenvolvida no âmbito do **Bacharelado em Ciência da Computação** (com concentração em Engenharia de Software) do **Instituto Federal de Educação, Ciência e Tecnologia de São Paulo (IFSP) – Câmpus São João da Boa Vista**, sob autoria de **Henrique Bissoli Malaman Alonso**, orientação do **Prof. Dr. Breno Lisi Romano** e coorientação do **Prof. Me. Everton Rafael da Silva**.

A poluição atmosférica é apontada pela Organização Mundial da Saúde (OMS) e pela Organização das Nações Unidas (ONU) como uma das maiores ameaças ambientais à saúde global, sendo responsável por mais de 7 milhões de mortes prematuras por ano decorrentes de complicações cardiovasculares, pulmonares e neurodegenerativas. No Brasil, embora o Programa Nacional de Controle da Qualidade do Ar (Pronar) exista desde 1989, a infraestrutura oficial de monitoramento é **excessivamente restrita**:
- Menos de **2% dos municípios brasileiros** contam com estações de monitoramento ativas;
- Cerca de **78% das estações existentes** estão concentradas na Região Sudeste;
- O modelo convencional é fundamentado em **estações meteorológicas de referência (macroescala)**, com custo individual de implantação e manutenção na casa de centenas de milhares de reais, impedindo a capilaridade espacial necessária para captar disparidades ambientais hiperlocais.

Nesse contexto, torna-se imperativo o desenvolvimento de soluções em **microescala** que permitam diagnosticar a dispersão de contaminantes em vias urbanas, cruzamentos e perímetros de edifícios públicos (como escolas e postos de saúde), viabilizando tomadas de decisão preventivas pelo poder público e acesso democrático à informação ambiental pela população.

---

### 1.2 O Papel Central da Internet das Coisas (IoT)

A **Internet das Coisas (IoT)** é o pilar estrutural que viabiliza a solução técnica do OpenAir-Metrics. Por meio da integração de microcontroladores de baixo custo, sensores dedicados e arquitetura de comunicação distribuída, a IoT transforma objetos físicos em nós autônomos de sensoriamento contínuo:

```mermaid
flowchart LR
    subgraph S["Sensoriamento Físico (IoT)"]
        D["DHT22 (Temp/Umid)"]
        P["DSM501A (Particulados)"]
        C["MH-Z19C (CO2)"]
    end

    subgraph B["Processamento de Borda"]
        ESP["ESP32 Dual-Core 240MHz"]
    end

    subgraph M["Enlace Local (Sem Roteador)"]
        NOW["ESP-NOW (Malha em Cascata ≤ 200m)"]
    end

    subgraph G["Gateway & Conectividade"]
        GW["Estação Matriz (Wi-Fi 802.11)"]
    end

    subgraph CLOUD["Infraestrutura em Nuvem"]
        API["Laravel 13 REST API + Reverb"]
    end

    D & P & C --> ESP
    ESP --> NOW
    NOW --> GW
    GW -->|HTTP POST| API
```

As vantagens da abordagem IoT adotada neste projeto abrangem:
1. **Redução Drástica de Custos:** Viabiliza uma estação de monitoramento completa por uma fração mínima do custo de instrumentos tradicionais de referência.
2. **Capilaridade e Resolução Espacial:** Permite criar uma malha densa ao longo do leito de vias públicas urbanas, monitorando variações atmosféricas rua a rua.
3. **Autonomia e Resiliência:** Os nós sensores realizam a leitura contínua e transmitem dados de forma descentralizada.
4. **Configuração Amigável em Campo:** Em sua inicialização de fábrica, o ESP32 emite um Ponto de Acesso (AP) Wi-Fi temporário para que os técnicos em campo configurem credenciais e parâmetros operacionais de forma simplificada.

---

### 1.3 Instrumentação Embarcada e Sensores Selecionados

A seleção tecnológica do hardware priorizou componentes acessíveis no mercado nacional, de consumo elétrico balanceado e acurácia comprovada na literatura científica:

| Componente / Sensor | Parâmetro Mensurado | Faixa Operacional | Resolução / Precisão | Princípio Físico / Químico |
| :--- | :--- | :--- | :--- | :--- |
| **ESP32 DevKit** | *Controlador do Nó* | 2 núcleos Xtensa 32-bit (240 MHz) | 520 KB SRAM / Flash 4MB | Microcontrolador com Wi-Fi b/g/n e BLE nativos |
| **DHT22 (AM2302)** | Temperatura e Umidade | -40 a +80 °C / 0 a 100% RH | ±0,5 °C / ±2% RH | Polímero capacitivo e termistor NTC calibrado |
| **DSM501A (Samyoung)** | Material Particulado (PM<sub>1.0</sub> e PM<sub>2.5</sub>) | Até 15.000 partículas / 283 ml | Partículas a partir de 1 µm | Dispersão óptica de luz (LED + heater térmico + PWM) |
| **MH-Z19C (Winsen)** | Dióxido de Carbono (CO<sub>2</sub>) | 400 a 5.000 ppm | ±(50 ppm + 5% leitura) | Infravermelho Não Dispersivo (NDIR) com compensação térmica |

---

### 1.4 Rede Mesh em Cascata com Protocolo ESP-NOW

Um dos maiores desafios de projetos IoT em vias públicas é a ausência de conectividade Wi-Fi aberta contínua em postes ou esquinas. Para superar esse obstáculo sem depender de modems 4G/LTE caros em cada unidade:

1. **Protocolo Connectionless ESP-NOW:** Desenvolvido pela Espressif, opera na frequência industrial de 2.4 GHz sem overhead de associação de rede Wi-Fi convencional. Apresenta tempo de conexão de apenas ~364 µs e transmissão de ~227 µs, com alcance de até 200 metros em visada direta.
2. **Topologia em Cascata Multidirecional:** As **Estações Satélites** transmitem seus pacotes de telemetria diretamente via endereço MAC para a estação imediatamente anterior na malha (`Satélite C -> Satélite B -> Satélite A -> Matriz`).
3. **Nó Concentrador (Estação Matriz / Gateway):** Apenas o nó central da malha (a Estação Matriz) necessita de link ativo com a Internet (via Wi-Fi institucional, comercial ou rede pública). Ele recebe as leituras da malha ESP-NOW e despacha as requisições HTTP REST em lote para a nuvem.

---

## 2. Arquitetura da Solução

### 2.1 Visão Geral Multicamadas do Ecossistema

A plataforma adota o padrão **MVC orientado a serviços de domínio** sobre o ecossistema Laravel 13 e PHP 8.5, garantindo segregação de responsabilidades e alta manutenibilidade:

```mermaid
flowchart TD
    subgraph CAMADA_BORDA["Camada de Borda & IoT"]
        S1["Sensor Satélite 2\n(ESP32 + Sensores)"]
        S2["Sensor Satélite 1\n(ESP32 + Sensores)"]
        SM["Estação Matriz / Gateway\n(ESP32 + Wi-Fi)"]
        S1 -->|ESP-NOW ≤ 200m| S2
        S2 -->|ESP-NOW ≤ 200m| SM
    end

    subgraph CAMADA_INGESTAO["Camada de Ingestão & Segurança"]
        API["POST /api/medicoes\n(MedicaoApiController)"]
        AUTH["Validação X-Sensor-Key\n(Tempo Constante: hash_equals)"]
        SM -->|HTTPS JSON Payload| API
        API --- AUTH
    end

    subgraph CAMADA_PROCESSAMENTO["Camada de Domínio & Processamento"]
        IQA["Cálculo do IQA\n(CONAMA 506 / ANVISA RE 09)"]
        VU["Cálculo de Vida Útil\n(5 Anos / Notificações)"]
        SERIES["Agregação Adaptativa de Séries Temporais\n(Buckets de 5s a 1 dia)"]
        API --> IQA
        API --> VU
    end

    subgraph CAMADA_PERSISTENCIA["Camada de Dados & GIS"]
        DB[(MariaDB 11.4+ Spatial)]
        SPATIAL["Índices Espaciais: POINT & LINESTRING\nSRID 4326"]
        SQLITE["Fallback Ortodrômico Haversine\n(Ambiente de Testes / SQLite)"]
        IQA --> DB
        DB --- SPATIAL
        DB -.- SQLITE
    end

    subgraph CAMADA_TEMPO_REAL["Camada de Difusão em Tempo Real"]
        REVERB["Laravel Reverb (WebSocket Server)"]
        EVENT["Evento NovaMedicaoRecebida\n(ShouldBroadcastNow)"]
        API --> EVENT
        EVENT --> REVERB
    end

    subgraph CAMADA_APRESENTACAO["Camada de Apresentação & Cartografia"]
        MAPA["Mapa Público Interativo\n(Leaflet 1.9 + Camadas Dinâmicas)"]
        DASH["Dashboard Analítico Administrativo\n(Métricas & Alertas)"]
        PLAN["Planejador de Malha Viária\n(Snap to Road)"]
        REVERB -->|WebSocket Push| MAPA
        DB --> DASH
        DB --> PLAN
    end
```

---

### 2.2 Fluxo Ponta a Ponta: Da Captura Física ao Mapa em Tempo Real

1. **Amostragem Física:** Os sensores DHT22, DSM501A e MH-Z19C realizam a leitura periódica no circuito embarcado. O ESP32 calcula as concentrações de gases e particulados.
2. **Propagação Local:** Caso seja uma Estação Satélite, o payload (máx. 250 bytes) é enviado via ESP-NOW para o nó pai até alcançar a Estação Matriz.
3. **Ingestão HTTP:** A Estação Matriz realiza uma chamada `POST /api/medicoes` transmitindo o JSON com `mac_address`, `temperatura`, `umidade`, `co2` e `poeira`, assinada com o token no header `X-Sensor-Key`.
4. **Validação e Cálculo:** O controlador [`MedicaoApiController`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Http/Controllers/Api/MedicaoApiController.php#L12-L113) valida os limites físicos das grandezas, confirma a autenticidade via `hash_equals()`, calcula o Índice de Qualidade do Ar (IQA) e grava a medição na tabela `medicoes`.
5. **Broadcasting Instantâneo:** O evento [`NovaMedicaoRecebida`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Events/NovaMedicaoRecebida.php#L14-L79) despacha imediatamente o novo registro via WebSocket pelo **Laravel Reverb**.
6. **Atualização da Interface:** O cliente conectado na página inicial (Leaflet + Laravel Echo) renderiza o novo ponto geográfico com sua cor representativa de criticidade sem recarregar a tela.

---

### 2.3 Motor Geoespacial e Algoritmo de Planejamento Viário (*Snap to Road*)

O OpenAir-Metrics possui um algoritmo avançado de planejamento urbano implementado em [`PlanejamentoMalhaService`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/PlanejamentoMalhaService.php#L12-L757) e [`GeocodingService`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Services/GeocodingService.php#L13-L626), responsável por:

1. **Importação e Filtragem de Vias do OpenStreetMap:** Extrai as geometrias viárias locais (`LINESTRING` com SRID 4326), excluindo expressamente rodovias e vias de tráfego pesado (`motorway`, `trunk`), restringindo a malha a vias urbanas (`residential`, `tertiary`, `secondary`, `primary`).
2. **Snapping Ortogonal:** Encaixa a Estação Matriz no segmento de via mais próximo do clique do planejador.
3. **Expansão Multidirecional em Cascata:** Distribui de 1 a 20 Estações Satélites em 2, 3 ou 4 ramos angulares cardeais, projetando nós a saltos de **100m a 198m** de distância do pai imediato.
4. **Mecanismo Anti-Overlap:** Garante que nenhum nó satélite seja posicionado a menos de **180m a 200m** de nós de outros ramos, evitando redundância e interferência.
5. **Ordenação Topológica DFS:** Ordena a instalação em árvore por Busca em Profundidade (`Estacao::ordenarEmCascata`), permitindo que a equipe de campo ative sequencialmente o fluxo de rádio sem quebrar a rota de pacotes.

---

### 2.4 Modelagem de Dados e Domínio Espacial

O banco de dados relacional (MariaDB com extensões espaciais) é estruturado em 4 grandes domínios:

```mermaid
erDiagram
    ESTADO ||--o{ CIDADE : "possui"
    CIDADE ||--o{ BAIRRO : "possui"
    CIDADE ||--o{ USER : "jurisdicao (cidade_id)"
    CIDADE ||--o{ PATRIMONIO : "estoque municipal"
    BAIRRO ||--o{ ESTACAO : "localizacao (bairro_id)"
    USER ||--o{ ESTACAO : "criado / instalado por"
    USER ||--o{ PATRIMONIO : "registrado por"
    PATRIMONIO ||--o| ESTACAO : "alocado fisicamente"
    ESTACAO ||--o{ ESTACAO : "matriz_pai_id (arvore de malha)"
    ESTACAO ||--o{ ESTACAO : "estacao_origem_id (salto anterior)"
    ESTACAO ||--o{ MEDICAO : "coletas de telemetria"
```

- **Usuários e Localidades:** Tabelas `users`, `estados`, `cidades`, `bairros` e `malha_viaria` (geometrias viárias `LINESTRING`).
- **Infraestrutura e Ativos IoT:** Tabelas `patrimonios` (gestão de ciclo de hardware) e `estacoes` (topologia física, coordenadas `POINT` SRID 4326, autorrelacionamentos de rede).
- **Telemetria Ambiental:** Tabela `medicoes` particionada logicamente com índices compostos por `estacao_id` e `data_hora`.
- **Operações do Framework:** Tabelas `jobs`, `failed_jobs`, `cache`, `sessions` e tokens de autenticação.

---

## 3. Requisitos do Sistema

> 📖 **Documentação Completa de Requisitos:** Consulte os detalhes exaustivos, assinaturas de métodos e regras em [`docs/ANALISE_DE_REQUISITOS.md`](file:///home/henrique_1/Documents/OpenAir-Metrics/docs/ANALISE_DE_REQUISITOS.md).

### 3.1 Requisitos Funcionais (RF01 a RF24)

| Código | Módulo | Descrição Resumida |
| :--- | :--- | :--- |
| **RF01** | Autenticação | Login por e-mail/senha com redirecionamento contextual por perfil (`superadmin`, `instalador`, `administrador`). |
| **RF02** | Autenticação | Encerramento seguro da sessão (Logout) com renovação do token CSRF. |
| **RF03** | Perfil | Visualização e atualização de dados cadastrais e endereço; bloqueio de alteração de cidade após fixada. |
| **RF04** | Usuários | Gestão completa de usuários locais, hierarquia de permissões e regra de sucessão administrativa municipal. |
| **RF05** | Monitoramento | Mapa público em tempo real na página inicial (`GET /`) com IQA, Temperatura, Umidade, PM<sub>2.5</sub> e CO<sub>2</sub>. |
| **RF06** | Dashboard | Painel analítico municipal com contadores de estações ativas e alertas ambientais críticos. |
| **RF07** | Dashboard | Séries temporais analíticas com agrupamento temporal adaptativo (5 segundos a 1 dia) por cidade/bairro. |
| **RF08** | Telemetria IoT | Ingestão de dados via API REST (`POST /api/medicoes`), validação física de sensores e disparo em WebSocket. |
| **RF09** | Estações | Listagem, busca e filtragem de estações com ordenação por percentual de vida útil restante dos sensores. |
| **RF10** | Estações | Cadastro manual de Estação Matriz e Satélite no mapa, com geocodificação reversa e validação de 200m. |
| **RF11** | Manutenção | Solicitação de substituição preventiva ou corretiva de sensores instalados em campo. |
| **RF12** | Manutenção | Efetivação da substituição de sensores: descarte do hardware antigo e ativação do novo item do estoque. |
| **RF13** | Planejamento | Simulação e cálculo automatizado de malha em cascata viária (1 Matriz e até 20 Satélites) sobre ruas reais. |
| **RF14** | Planejamento | Ajuste fino interativo de estações no mapa com projeção sobre a via mais próxima (*Snap to Road*). |
| **RF15** | Planejamento | Persistência atômica da malha calculada com geração automática de ordem de instalação em árvore (DFS). |
| **RF16** | Patrimônio | Inventário de equipamentos: cadastro individual, importação em lote de MACs e descarte lógico. |
| **RF17** | Instalações | Acompanhamento de ordens de instalação em campo agrupadas por Estação Matriz com roteiro em cascata. |
| **RF18** | Instalações | Ativação física de nós em campo por técnicos instaladores via MAC Address ou código de patrimônio. |
| **RF19** | Localidades | Consulta de estados/cidades/bairros e importação sob demanda de bairros via OpenStreetMap Overpass. |
| **RF20** | Geocodificação | Resolução de endereços geográficos reversos com estratégia de cache e filas assíncronas no Nominatim. |
| **RF21** | Segurança | Redefinição de senha por autoatendimento (*Forgot Password*) com token assinado e expiração de 60 minutos. |
| **RF22** | Segurança | Conclusão de primeiro acesso / convite de operadores com definição de senha forte e verificação de e-mail. |
| **RF23** | Segurança | Autenticação Multifator via TOTP (RFC 6238) com QR Code, segredo Base32 e 8 códigos de recuperação. |
| **RF24** | Segurança | Autenticação sem senha (*Passwordless*) via Passkeys / WebAuthn (FIDO2 - NIST AAL3) com suporte abrangente a biometria nativa, chaves físicas e autenticadores virtuais CTAP2 em Linux (`linux-passkey`). |

---

### 3.2 Requisitos Não Funcionais (RNF01 a RNF14)

- **RNF01 — Stack Tecnológica:** PHP 8.5, Laravel 13, Sanctum v4, Reverb v1, Tailwind CSS v4, Leaflet 1.9 e Laravel MCP.
- **RNF02 — Persistência Geoespacial:** Armazenamento em geometrias nativas `POINT` e `LINESTRING` com SRID 4326 e `SPATIAL INDEX` no MariaDB.
- **RNF03 — Compatibilidade Híbrida de Driver:** Mecanismo de contingência automática em PHP (cálculo de Haversine e decodificação WKT/WKB) para testes automatizados em SQLite.
- **RNF04 — Ingestão Massiva de Malha Viária:** Seeder automatizado via `ogr2ogr`/GDAL para importação em lote de bases PBF do OpenStreetMap (`brazil-latest.osm.pbf`).
- **RNF05 — Segregação de Chaves Primárias:** Proteção de chaves internas autoincrementais (`private_id`), expondo unicamente UUIDv4 (`public_id`) em rotas, payloads e links.
- **RNF06 — Limitação de Taxa (*Rate Limiting*):** Proteção multinível contra abusos: `throttle:5,1` no Login; `throttle:3,1` na Recuperação de Senha; `throttle:60,1` na Telemetria; limite de 1 req/s na geocodificação externa.
- **RNF07 — Autenticação de Dispositivos IoT em Tempo Constante:** Validação do header `X-Sensor-Key` via `hash_equals()` contra ataques de temporização (*timing attacks*).
- **RNF08 — Cache em Múltiplas Camadas:** Cache de 30 dias para geocodificação reversa e cache de 24 horas para geometrias de vias do Overpass.
- **RNF09 — Alta Disponibilidade e Failover:** Rotação de 4 servidores espelho Overpass com timeouts curtos (6 a 8s) para resiliência de rede.
- **RNF10 — Transacionalidade e Preservação de Histórico:** Atomicidade estrita em transações compostas (`DB::beginTransaction`) e descarte lógico de patrimônios (preservação de histórico).
- **RNF11 — Padronização Visual e Internacionalização de Notificações Transacionais:** Templates de e-mail responsivos, compatíveis com modo claro/escuro e tradução integral para Português do Brasil (`lang/pt_BR/`) e Inglês (`lang/en/`).
- **RNF12 — Isolamento de Ferramentas e Rotas de Diagnóstico:** Rotas de pré-visualização de e-mails (`/preview-email/*`) bloqueadas em ambiente de produção (`app.env === 'production'`) via comando `php artisan env:prod`.
- **RNF13 — Conformidade Criptográfica e Diretrizes de Identidade Digital (NIST SP 800-63B Rev. 4 & AAL3):** Armazenamento de senhas via hash lento e unidirecional (bcrypt com custo adaptativo), segredos de autenticação multifator (TOTP / RFC 6238) criptografados com AES-256 no banco e suporte a Passkeys (FIDO2 / WebAuthn AAL3) com serializador resiliente (`WebauthnSerializer`), habilitando suporte a múltiplos formatos de atestação (`none`, `packed`, `fido-u2f`, `android-key`, `apple`, `tpm`) e tratamento estrito de exceções em JSON.
- **RNF14 — Privacidade por Padrão (*Privacy by Design* & LGPD):** Ausência de rastreadores comportamentais invasivos de terceiros, mapas de calor ou scripts analíticos externos no painel de gestão, mitigando riscos de vazamento de dados de operadores municipais conforme o Art. 46 da Lei Geral de Proteção de Dados (Lei nº 13.709/2018).

---

### 3.3 Regras de Negócio Fundamentais (RN01 a RN16)

1. **Isolamento de Jurisdição Municipal (RN02):** Todos os dados operacionais (estações, patrimônio, técnicos) são estritamente particionados pelo `cidade_id` do gestor.
2. **Sucessão Administrativa Municipal (RN03):** Quando um administrador municipal cadastra outro administrador para sua cidade, a conta anterior é automaticamente desativada e a sessão encerrada.
3. **Alcance Máximo de Rádio de 200 Metros (RN05):** Nenhuma Estação Satélite pode ser posicionada a uma distância geodésica superior a 200 metros de sua estação pai na malha.
4. **Exclusão de Rodovias (RN06):** É estritamente proibido o posicionamento de nós de monitoramento sobre rodovias e autoestradas (`motorway`, `trunk`).
5. **Precedência na Ativação em Campo (RN09):** Um instalador não pode ativar uma Estação Satélite se o nó pai imediato ainda não estiver com status `Instalada`.
6. **Ciclo de Vida Nominal de 5 Anos (RN12):** Os sensores possuem vida útil de 5 anos; abaixo de 6 meses restantes entram em estado crítico, e com menos de 20% permitem substituição simplificada.

---

## 4. Especificação de Casos de Uso

> 📖 **Documentação Completa de Casos de Uso:** Consulte fluxos principais, caminhos alternativos e códigos HTTP em [`docs/ESPECIFICACAO_CASOS_DE_USO.md`](file:///home/henrique_1/Documents/OpenAir-Metrics/docs/ESPECIFICACAO_CASOS_DE_USO.md).

### 4.1 Catálogo de Atores do Sistema

- 👤 **Visitante:** Cidadão que navega no mapa público de qualidade do ar e na tela de login.
- 👑 **Super-Usuário (`superadmin`):** Administrador global encarregado exclusivamente da criação e gestão de contas de Administradores Municipais.
- 🏛️ **Administrador Municipal (`administrador`):** Gestor responsável por um município específico (`cidade_id`), com autoridade sobre equipes locais, estações e inventário.
- 📐 **Planejador Técnico (`cadastrador` / `planejador`):** Especialista encarregado do desenho de malhas viárias, cadastro de estações e controle de estoque de hardware.
- 🔧 **Instalador (`instalador`):** Técnico em campo que executa o roteiro de ativação física das placas, vinculando MAC Address e número de patrimônio aos pontos planejados.
- 📡 **Sensor IoT (Estação em Campo):** Dispositivo microcontrolado (ESP32) que coleta variáveis ambientais e despacha pacotes de telemetria via REST API.
- 🌐 **APIs OpenStreetMap (Nominatim & Overpass):** Serviços externos integrados para geocodificação reversa e fornecimento de malhas viárias.
- ⚡ **Subsistema Interno (Fila & Reverb):** Processamento assíncrono em segundo plano e servidor de WebSockets para difusão ao vivo.

---

### 4.2 Matriz Sintética de Casos de Uso (UC01 a UC20)

```mermaid
flowchart TD
    subgraph PUBLICO_SEGURANCA["Acesso Público & Segurança Avançada"]
        UC01["UC01: Visualizar Mapa Público em Tempo Real"]
        UC02["UC02: Autenticar no Sistema (Login / Logout)"]
        UC17["UC17: Redefinir Senha por Autoatendimento"]
        UC18["UC18: Concluir Cadastro de Primeiro Acesso"]
        UC19["UC19: Gerenciar e Autenticar com 2FA TOTP"]
        UC20["UC20: Gerenciar e Autenticar com Passkeys"]
    end

    subgraph GESTAO["Gestão de Acessos & Painéis"]
        UC03["UC03: Gerenciar Perfil Próprio"]
        UC04["UC04: Gerenciar Usuários e Administradores"]
        UC05["UC05: Consultar Dashboard e Séries Temporais"]
    end

    subgraph REDE_ESTACOES["Topologia & Planejamento de Rede IoT"]
        UC06["UC06: Consultar e Filtrar Estações"]
        UC07["UC07: Cadastrar Estação Individualmente"]
        UC08["UC08: Simular e Planejar Malha Viária (Snap to Road)"]
        UC09["UC09: Salvar Malha e Gerar Ordem de Instalação"]
    end

    subgraph OPERACAO_CAMPO["Operação em Campo & Ativos"]
        UC10["UC10: Gerenciar Inventário de Patrimônio"]
        UC11["UC11: Consultar Ordens e Roteiro em Cascata"]
        UC12["UC12: Ativar Estação e Vincular Hardware"]
        UC13["UC13: Solicitar Substituição de Sensores"]
        UC14["UC14: Efetivar Substituição de Sensores"]
    end

    subgraph TELEMETRIA_GIS["Telemetria e Serviços GIS"]
        UC15["UC15: Enviar e Processar Telemetria IoT"]
        UC16["UC16: Consultar Localidades e Geocodificação Reversa"]
    end
```

| ID | Nome do Caso de Uso | Atores Primários | Escopo e Responsabilidade Principal |
| :--- | :--- | :--- | :--- |
| **UC01** | Visualizar Mapa Público | Visitante, Reverb | Renderização cartográfica pública com medições e marcadores dinâmicos. |
| **UC02** | Autenticar no Sistema | Visitante, Autenticado | Login seguro, proteção de taxa contra força bruta e roteamento por papel. |
| **UC03** | Gerenciar Perfil Próprio | Usuário Autenticado | Atualização de dados cadastrais e endereço do operador. |
| **UC04** | Gerenciar Usuários | Super-Usuário, Administrador | Cadastro hierárquico com regra estrita de sucessão municipal. |
| **UC05** | Consultar Dashboard | Administrador, Técnico, Instalador | Indicadores de saúde do ar, contadores de alerta e séries temporais adaptativas. |
| **UC06** | Consultar Estações | Administrador, Técnico, Instalador | Tabela operacional com filtros, status e indicador de vida útil nominal. |
| **UC07** | Cadastrar Estação Individual | Planejador, Administrador | Posicionamento pontual no mapa com validação de 200m e geocodificação reversa. |
| **UC08** | Simular Malha em Cascata | Planejador, Administrador | Cálculo automatizado de 1 Matriz e até 20 Satélites sobre ruas reais (*Snap to Road*). |
| **UC09** | Salvar Malha Planejada | Planejador, Administrador | Persistência transacional atômica e ordenação DFS em árvore (`1..N`). |
| **UC10** | Gerenciar Patrimônio | Planejador, Administrador | Cadastro unitário, importação em lote de MACs e descarte lógico de itens. |
| **UC11** | Consultar Ordens de Instalação | Instalador, Planejador | Visualização do roteiro de instalação ordenado topologicamente. |
| **UC12** | Ativar Estação em Campo | Instalador | Vinculação do MAC ou Patrimônio ao ponto físico respeitando a ordem da malha. |
| **UC13** | Solicitar Substituição | Administrador, Planejador | Abertura de ordem de manutenção com justificativa obrigatória (> 20% vida). |
| **UC14** | Efetivar Substituição | Instalador, Planejador | Troca física da placa, descarte do hardware antigo e renovação da vida útil. |
| **UC15** | Enviar Telemetria IoT | Sensor IoT (ESP32), Reverb | Ingestão REST com chave `X-Sensor-Key`, cálculo de IQA e difusão em tempo real. |
| **UC16** | Resolver Geocodificação | Usuário, Fila, APIs OSM | Resolução síncrona/assíncrona de endereços e importação sob demanda de bairros. |
| **UC17** | Redefinir Senha por Autoatendimento | Usuário Visitante | Solicitação de link temporário assinado via e-mail e redefinição segura segundo NIST SP 800-63B. |
| **UC18** | Concluir Cadastro de Primeiro Acesso | Novo Operador | Conclusão do convite enviado por e-mail com definição da primeira senha e verificação instantânea. |
| **UC19** | Gerenciar e Autenticar com 2FA TOTP | Usuário Autenticado | Configuração de segredo RFC 6238 via QR Code, códigos de recuperação e desafio no login. |
| **UC20** | Gerenciar e Autenticar com Passkeys | Usuário Autenticado | Registro de credencial biométrica/FIDO2 via WebAuthn (NIST AAL3) com ampla interoperabilidade (Windows Hello, Touch ID, YubiKey e Linux CTAP2/linux-passkey) e login sem senha (*Passwordless*). |

---

## 5. Inteligência Ambiental e Cálculo do IQA

### 5.1 Formulação Matemática do IQA

O Índice de Qualidade do Ar (IQA) sintetiza múltiplas variáveis físico-químicas em uma escala adimensional compreensível. O cálculo é computado por interpolação linear multivariada implementada em [`Medicao::calcularIqa`](file:///home/henrique_1/Documents/OpenAir-Metrics/app/Models/Medicao.php#L103-L149), baseada nas normativas da **Resolução CONAMA nº 506/2024** (para Material Particulado PM<sub>2.5</sub>) e da **Resolução ANVISA RE nº 09/2003** (para Dióxido de Carbono CO<sub>2</sub>):

$$\text{IQA}_p = I_{\text{inf}} + \frac{I_{\text{sup}} - I_{\text{inf}}}{C_{\text{sup}} - C_{\text{inf}}} \times (C - C_{\text{inf}})$$

$$\text{IQA} = \min\left(500, \;\text{round}\left(\max\left(\text{IQA}_{\text{PM}_{2.5}}, \;\text{IQA}_{\text{CO}_2}\right)\right)\right)$$

Onde:
- $C$: Concentração observada do poluente na medição;
- $C_{\text{inf}}, C_{\text{sup}}$: Limites inferior e superior da faixa de concentração do poluente;
- $I_{\text{inf}}, I_{\text{sup}}$: Limites inferior e superior do intervalo do IQA correspondente.

---

### 5.2 Faixas de Qualidade segundo CONAMA e ANVISA

| Nível | Classificação | Faixa IQA | PM<sub>2.5</sub> (µg/m³) | CO<sub>2</sub> (ppm) | Efeitos na Saúde e Recomendações |
| :---: | :--- | :---: | :---: | :---: | :--- |
| **N1** | 🟢 **Boa** | 0 – 40 | 0,0 a 15,0 | 0 a 700 | Qualidade ideal do ar; sem riscos à população. |
| **N2** | 🟡 **Moderada** | 41 – 80 | 15,1 a 50,0 | 701 a 1000 | Pessoas de grupos sensíveis podem apresentar sintomas leves. |
| **N3** | 🟠 **Ruim** | 81 – 120 | 50,1 a 75,0 | 1001 a 1500 | Aumento de sintomas respiratórios; recomendada ventilação. |
| **N4** | 🔴 **Muito Ruim** | 121 – 200 | 75,1 a 125,0 | 1501 a 2500 | Agravamento de doenças pulmonares e cardíacas em toda a população. |
| **N5** | 🟣 **Péssima** | 201 – 400 | 125,1 a 300,0 | 2501 a 5000 | Efeitos graves à saúde; recomendada evacuação ou isolamento. |

---

## 6. Stack Tecnológica

| Camada | Tecnologia | Finalidade no Projeto |
| :--- | :--- | :--- |
| **Firmware & Hardware** | **ESP32 (C/C++)** | Microcontrolador de borda com processamento dual-core e baixo consumo. |
| **Rede de Sensores** | **ESP-NOW (2.4 GHz)** | Protocolo peer-to-peer em malha viária (alcance até 200m sem roteador Wi-Fi). |
| **Sensores Físicos** | **DHT22, DSM501A, MH-Z19C** | Aferição de Temperatura, Umidade, Particulados finos (PM) e CO<sub>2</sub>. |
| **Framework Backend** | **Laravel 13 (PHP 8.5)** | Núcleo MVC, injeção de dependências, filas assíncronas e API RESTful. |
| **WebSockets & Real-Time** | **Laravel Reverb v1** | Servidor WebSocket nativo de alta performance para transmissão de telemetria. |
| **Banco de Dados Relacional** | **MariaDB 11.4+ / MySQL** | Persistência transacional com suporte geoespacial (`SPATIAL INDEX`). |
| **Banco para Testes** | **SQLite 3** | Execução ágil de testes com fallback Haversine transparente em PHP. |
| **Geoprocessamento** | **GDAL / ogr2ogr** | Ingestão massiva de redes viárias completas a partir de arquivos `.osm.pbf`. |
| **Serviços de Cartografia** | **OpenStreetMap (Nominatim & Overpass)** | Geocodificação reversa e extração de geometria de logradouros urbanos. |
| **Frontend & Estilização** | **Blade & Tailwind CSS v4** | Interface responsiva e moderna com compilação ultra-rápida via Vite v8. |
| **Visualização de Mapas** | **Leaflet v1.9** | Cartografia interativa com camadas de calor e marcadores dinâmicos. |
| **Autenticação Biométrica / FIDO2** | **WebAuthn (Spatie Passkeys & web-auth)** | Autenticação sem senha resistente a phishing (NIST AAL3) com suporte a múltiplos formatos de atestação (macOS, Windows, Linux, Android). |
| **Suíte de Testes** | **Pest PHP v4 & PHPUnit 12** | Testes automatizados de unidade, integração e feature com alta cobertura. |

---

## 7. Estrutura do Repositório e Documentação Técnica

```text
OpenAir-Metrics/
├── app/
│   ├── Actions/                    # Ações de domínio e autenticação avançada (Passkeys, Resoluções)
│   ├── Events/                     # Eventos de difusão em tempo real (ex: NovaMedicaoRecebida)
│   ├── Http/
│   │   ├── Controllers/            # Controladores MVC e API de Ingestão de Sensores
│   │   ├── Middleware/             # Controle de autenticação e proteção de rotas
│   │   └── Requests/               # Validações espaciais e regras de negócio (Form Requests)
│   ├── Jobs/                       # Processamento assíncrono (ResolveReverseGeocodingJob)
│   ├── Mcp/                        # Servidores, ferramentas e recursos do Model Context Protocol (Laravel MCP)
│   ├── Models/                     # Modelos Eloquent com lógica de domínio e IQA
│   ├── Providers/                  # Provedores de serviços e injeção de dependências
│   ├── Services/                   # Serviços de Geocodificação e Planejamento de Malha
│   └── Support/                    # Serializadores e utilitários auxiliares (WebauthnSerializer)
├── config/                         # Configurações de telemetria, reverb, passkeys, cache e banco
├── database/
│   ├── migrations/                 # Esquemas relacionais e tabelas espaciais (SRID 4326)
│   └── seeders/                    # Carga de dados, malha viária OSM e usuários de teste
├── docs/                           # 📚 Documentação Técnica Exaustiva do Projeto
│   ├── ANALISE_DE_REQUISITOS.md    # Requisitos Funcionais (RF01–RF24), Não Funcionais (RNF01–RNF14) e Regras
│   └── ESPECIFICACAO_CASOS_DE_USO.md # Especificação Completa dos Casos de Uso (UC01 a UC20) e Matriz de Rastreabilidade
├── lang/                           # 🌐 Internacionalização (pt_BR e en) para templates e validações
├── resources/
│   ├── css/                        # Estilos Tailwind CSS v4
│   ├── js/                         # Inicialização do Echo, Reverb e Leaflet
│   └── views/                      # Templates Blade (Home, Dashboard, Estações, Auth e vendor/mail)
├── routes/
│   ├── api.php                     # Endpoint seguro de telemetria IoT (/api/medicoes)
│   └── web.php                     # Rotas administrativas, autenticação e mapa público
└── tests/                          # Suíte de testes automatizados com Pest PHP
```

---

## 8. Guia de Instalação e Execução Local

Este guia detalha o passo a passo completo para configurar, importar o banco de dados e executar o **OpenAir-Metrics** em ambiente de desenvolvimento local, utilizando as credenciais e configurações mapeadas no arquivo `.env`.

### 8.1 Pré-requisitos

Certifique-se de possuir instalado em sua estação de trabalho:
- **PHP 8.3 ou 8.5** com extensões: `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `curl`, `xml`, `gd`, `sqlite3`
- **Composer 2.x** (gerenciador de dependências PHP)
- **Node.js 20+** e **NPM** (compilação de assets front-end e Tailwind CSS v4)
- **Podman** (ou Docker) com container do **MariaDB** (versão 11.4+ recomendada) e ferramenta gráfica opcional (**PHPMyAdmin**)

---

### 8.2 Configuração do Ambiente (.env)

Clone o repositório e crie o arquivo de configuração `.env` a partir do modelo de exemplo:

```bash
cp .env.example .env
```

Abra o arquivo `.env` e configure os parâmetros essenciais para o seu ambiente local:

```env
APP_NAME=OpenAir-Metrics
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=America/Sao_Paulo
APP_URL=http://localhost:8000
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en

# Conexão com Banco de Dados MariaDB / MySQL (Preencha com seus dados locais)
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=seu_banco_aqui
DB_USERNAME=seu_usuario_aqui
DB_PASSWORD=sua_senha_aqui

# Servidor WebSocket Nativo (Laravel Reverb)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=openair-metrics-local
REVERB_APP_KEY=openairlocalkey
REVERB_APP_SECRET=openairlocalsecret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# Sensores e Segurança de Telemetria
TELEMETRY_API_KEY=sua_chave_de_telemetria_aqui
INITIAL_SUPERADMIN_PASSWORD="OpenAir@Root#SenhaForte2026"

# Filas e Sessões
QUEUE_CONNECTION=sync
SESSION_DRIVER=database

# E-mail em Desenvolvimento (Driver log ou Mailpit local)
MAIL_MAILER=log
MAIL_FROM_ADDRESS="no-reply@openairmetrics.com.br"
MAIL_FROM_NAME="OpenAir Metrics"
```

#### Como Gerar a Chave de Telemetria (`TELEMETRY_API_KEY`)

O sistema exige uma chave de autenticação criptograficamente segura de **256 bits (64 caracteres hexadecimais)** para autenticar as requisições dos sensores IoT no cabeçalho `X-Sensor-Key`. Você pode gerá-la com qualquer um dos comandos abaixo:

```bash
# Opção 1: Via PHP nativo no terminal (Recomendada)
php -r "echo bin2hex(random_bytes(32)) . PHP_EOL;"

# Opção 2: Via OpenSSL no terminal
openssl rand -hex 32
```

> 🔑 **Aplicação da Chave:** Copie a sequência de 64 caracteres gerada e cole no campo `TELEMETRY_API_KEY=` do seu `.env`. Essa mesma chave deverá ser configurada no firmware do microcontrolador ESP32 para envio dos pacotes.

---

### 8.3 Instalação das Dependências

Execute os comandos abaixo na raiz do projeto para baixar os pacotes PHP e Node.js e gerar a chave criptográfica da aplicação:

```bash
# Instalação das dependências do backend Laravel
composer install

# Instalação das dependências do frontend (Tailwind v4, Leaflet, Laravel Echo)
npm install

# Geração da chave mestra do Laravel
php artisan key:generate
```

---

### 8.4 Criação e População Automática do Banco de Dados (Migrate & Seed)

**Não é necessário importar nenhum arquivo SQL externo!** A aplicação é 100% autossuficiente e provisiona toda a base de dados relacional e geoespacial executando unicamente:

```bash
php artisan migrate --seed
```

#### O que este comando executa automaticamente?

1. **Estrutura Relacional e Geoespacial (`migrate`):** Cria todas as tabelas do sistema com geometrias nativas (`POINT` e `LINESTRING` com SRID 4326), índices espaciais (`SPATIAL INDEX`), além das tabelas de autenticação avançada (Dois Fatores TOTP e Passkeys WebAuthn FIDO2).
2. **Conta do Superadministrador (`SuperAdminSeeder`):** Cria o usuário global com o e-mail `superadmin@openair.local` e a senha definida no parâmetro `INITIAL_SUPERADMIN_PASSWORD` do `.env`.
3. **Divisão Territorial do Brasil (`LocalidadesSeeder`):** Popula automaticamente todos os 27 Estados (UFs) e os mais de 5.500 Municípios brasileiros, além de bairros estruturados.
4. **Malha Viária e Geoprocessamento (`MalhaViariaSeeder`):** Inicializa as vias estruturadas de teste com coordenadas espaciais reais. Além disso, a aplicação possui integração dinâmica com a API Overpass do OpenStreetMap — se o usuário planejar uma malha em qualquer ponto do mapa que ainda não esteja no banco local, o sistema busca e desenha as ruas automaticamente em tempo de execução.

> 💡 **Dica de Reset:** Se em qualquer momento desejar recriar a base de dados do zero com dados limpos, basta rodar:
> ```bash
> php artisan migrate:fresh --seed
> ```

---

### 8.5 Inicialização dos Serviços (composer run dev -vv)

O OpenAir-Metrics possui um script unificado pré-configurado no Composer que inicializa todos os microsserviços e processos locais necessários em paralelo em um único terminal com cores e logs em tempo real:

```bash
composer run dev -vv
```

#### O que é executado simultaneamente por este comando?
- 🚀 **`server`:** Servidor HTTP do Laravel (`php artisan serve --host=0.0.0.0 --port=8000`) acessível em [http://localhost:8000](http://localhost:8000).
- ⚡ **`reverb`:** Servidor nativo de WebSockets (`php artisan reverb:start`) escutando na porta `ws://localhost:8080`.
- 🎨 **`vite`:** Compilação com Hot Module Replacement / HMR (`npm run dev`) para Tailwind CSS v4, Leaflet e Laravel Echo.
- 📬 **`queue`:** Processador de filas assíncronas (`php artisan queue:listen`) para geocodificação reversa em segundo plano.
- 📋 **`logs`:** Monitor de logs e exceções em tempo real (`php artisan pail`).

> 💡 **Execução em Terminais Separados (Alternativa):** Caso prefira rodar os serviços individualmente em abas isoladas:
> - Terminal 1: `php artisan serve`
> - Terminal 2: `php artisan reverb:start`
> - Terminal 3: `npm run dev` (ou `npm run build` para bundle estático)
> - Terminal 4: `php artisan queue:listen`

---

### 8.6 Acesso e Credenciais de Desenvolvimento

Após iniciar os serviços, você pode testar todas as funcionalidades do sistema:

- **Mapa Público Interativo:**  
  Acesse [http://localhost:8000/](http://localhost:8000/) no navegador para visualizar as estações e a telemetria em tempo real.
- **Painel Administrativo:**  
  Acesse [http://localhost:8000/login](http://localhost:8000/login) e utilize as credenciais padrão:
  - **E-mail:** `superadmin@openair.local`
  - **Senha:** A senha configurada no parâmetro `INITIAL_SUPERADMIN_PASSWORD` do `.env`
- **Pré-visualização dos Templates de E-mail (Somente em Ambiente Local):**
  - Recuperação de Senha: [http://localhost:8000/preview-email/reset-password](http://localhost:8000/preview-email/reset-password)
  - Conclusão de Cadastro: [http://localhost:8000/preview-email/complete-registration](http://localhost:8000/preview-email/complete-registration)
- **Simulação de Telemetria IoT via `curl`:**
  Envie um pacote de medição simulando um sensor ESP32 para verificar a ingestão e atualização instantânea no mapa:
  ```bash
  curl -X POST http://localhost:8000/api/medicoes \
    -H "Content-Type: application/json" \
    -H "X-Sensor-Key: SUA_TELEMETRY_API_KEY_AQUI" \
    -d '{
      "mac_address": "AA:BB:CC:DD:EE:01",
      "temperatura": 23.8,
      "umidade": 52.4,
      "co2": 415.0,
      "poeira": 11.2
    }'
  ```

---

## 9. Testes Automatizados e Qualidade de Código

### 9.1 Execução da Suíte de Testes (Pest PHP)

O projeto conta com mais de 240 testes unitários e de integração abrangendo algoritmos geoespaciais, cálculo do IQA, fluxos de autenticação (convite, redefinição de senha, 2FA TOTP com QR Code, Passkeys WebAuthn/FIDO2 com suporte multiplataforma incluindo CTAP2 em Linux) e ingestão de telemetria:

```bash
# Executa todos os testes automatizados
php artisan test --compact

# Executa apenas uma suíte ou teste específico
php artisan test --compact --filter=Auth
```

### 9.2 Formatação e Padronização de Código (Laravel Pint)

Para garantir a aderência aos padrões de código PSR-12 e convenções do ecossistema Laravel:

```bash
vendor/bin/pint --format agent
```

### 9.3 Segurança e Proteção em Produção (`php artisan env:prod`)

O sistema conta com um comando customizado de prontidão operacional que valida se a chave `LARAVEL_ENV_ENCRYPTION_KEY` está configurada, trava as rotas de diagnóstico e debug (`RNF12`) e valida as variáveis sensíveis:

```bash
php artisan env:prod
```
