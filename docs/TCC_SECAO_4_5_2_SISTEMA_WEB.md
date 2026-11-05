# 4.5.2 - Desenvolvimento do Sistema Web de Monitoramento

O módulo web do projeto **OpenAir-Metrics** compreende a camada central de apresentação, gestão operacional, análise temporal e geoprocessamento da plataforma. Ele atua como o ponto de convergência de todos os dados gerados pelas estações monitoras em campo, provendo tanto uma interface pública de conscientização cidadã quanto um painel analítico administrativo com controle de acesso multijurisdicional por município.

---

## 4.5.2.1 - Arquitetura do Sistema

Para conciliar alto desempenho, facilidade de manutenção e atualização de dados em tempo real sem a sobrecarga e a complexidade de roteamento de uma *Single Page Application* (SPA) tradicional, o sistema foi concebido sobre uma **arquitetura híbrida baseada no padrão MVC (Model-View-Controller) orientada a serviços de domínio**, combinando renderização no lado do servidor (*Server-Side Rendering* – SSR) via *Blade Templates* com componentes reativos no cliente.

```
+---------------------------------------------------------------------------------------+
|                               CAMADA DE CLIENTE (FRONTEND)                           |
|  - Leaflet.js (Renderização SIG / Canvas IDW)       - Chart.js (Séries Temporais)    |
|  - Tailwind CSS v4 (Design System)                  - Laravel Echo (WebSockets)      |
+-------------------------------------------+-------------------------------------------+
                                            ^
                       HTTP / JSON          |  WebSockets (Canal "medicoes")
                                            v
+-------------------------------------------+-------------------------------------------+
|                               CAMADA DE APLICAÇÃO (BACKEND)                          |
|  [Roteamento / Middlewares] -> [Controllers (Home, Dashboard, MedicaoApi, etc.)]      |
|                                     |                                                 |
|  [Camada de Serviços]:              v                                                 |
|   * GeocodingService (Nominatim/Overpass/Haversine)                                   |
|   * PlanejamentoMalhaService (Topologia em Árvore / Snap-to-Road)                     |
|                                                                                       |
|  [Eventos e Broadcasting]:                                                            |
|   * NovaMedicaoRecebida -> Laravel Reverb (Servidor WebSocket Nativo)                 |
+-------------------------------------------+-------------------------------------------+
                                            v  Eloquent ORM
+-------------------------------------------+-------------------------------------------+
|                              CAMADA DE PERSISTÊNCIA (BANCO)                           |
|  - Tabelas Convencionais (users, cidades, bairros, patrimonios)                       |
|  - Dados Espaciais: GEOMETRY(Point, 4326), LINESTRING com SPATIAL INDEX              |
|  - Séries Temporais: Tabela `medicoes` indexada por estacao_id e data_hora            |
+---------------------------------------------------------------------------------------+
```

Os principais pilares arquiteturais desta camada incluem:

1. **Backend em Laravel 13 e PHP 8.5:** O núcleo da aplicação processa as regras de negócio através de controladores enxutos delegados a serviços especializados (`GeocodingService`, `PlanejamentoMalhaService`).
2. **Segregação de Identificadores (Chaves Primárias vs. Chaves Públicas):** Para blindar a aplicação contra ataques de enumeração horizontal de recursos, as entidades de banco utilizam chaves primárias inteiras autoincrementais (`private_id`) exclusivamente para junções (*joins*) internas de alta performance, enquanto todas as rotas web, parâmetros de URL e payloads JSON expõem exclusivamente identificadores únicos universais (`public_id` no formato UUIDv4).
3. **Comunicação Reativa em Tempo Real (Laravel Reverb):** A sincronização entre as medições recebidas na API e a interface do usuário prescinde de mecanismos arcaicos de consulta periódica (*polling* HTTP). O sistema emprega o servidor de *WebSockets* nativo **Laravel Reverb**, que difunde instantaneamente eventos leves para as instâncias do cliente via biblioteca **Laravel Echo**.
4. **Persistência Geoespacial:** O banco de dados armazena as localizações geográficas através do tipo de dados espacial `POINT` (SRID 4326), complementado por geometrias viárias em `LINESTRING` com índices espaciais (`SPATIAL INDEX`), viabilizando projeções ortogonais e cálculos de proximidade através de funções espaciais analíticas (`MBRIntersects`, `ST_Distance_Sphere`).

---

## 4.5.2.2 - Padrão Visual e Escolhas de Design (UI/UX)

A interface gráfica foi concebida para atender simultaneamente a dois públicos distintos: o cidadão comum, que necessita de compreensão visual intuitiva e imediata sobre o ar que respira, e os gestores públicos ou técnicos instaladores, que demandam precisão analítica e rastreabilidade patrimonial.

### A. Identidade Visual e Tipografia
O design system foi construído com auxílio do **Tailwind CSS v4**, definindo uma identidade institucional orientada à sustentabilidade, tecnologia e clareza de leitura:
* **Tipografia:** Foi adotada a família tipográfica **Inter** como fonte primária (`--font-sans`). Projetada especificamente para telas digitais, a Inter oferece altura de x (*x-height*) elevada e excelente diferenciação de numerais e glifos pequenos, facilitando a interpretação de tabelas e valores métricos nos cards.
* **Paleta Cromática Institucional:** A identidade visual apoia-se em quatro tonalidades centrais:
  * *Blue Dianne* (`#0f4c5c` / `#043948`): Azul petróleo profundo que transmite seriedade técnica e confere contraste elevado aos cabeçalhos e elementos estruturais.
  * *Persian Green* (`#2a9d8f`): Verde mineral que remete à preservação ambiental e estabilidade ecológica.
  * *Dodger Blue* (`#4da3ff`): Azul atmosférico associado aos elementos climáticos e medições de umidade.
  * *Athens Gray* (`#f8fafc` a `#0f172a`): Escala de tons neutros utilizada como base de fundos, bordas e divisores.

### B. Padronização Semântica de Cores Ambientais
Para a classificação visual dos indicadores de qualidade do ar, foram rigorosamente implementadas as diretrizes cromáticas da **Resolução CONAMA nº 506/2024** e da **Resolução ANVISA RE nº 09/2003**:
* **Boa (0 a 40 IQA):** Verde Esmeralda (`#2ecc71` / `#10b981`) – Qualidade satisfatória;
* **Moderada (41 a 80 IQA):** Amarelo Dourado (`#f1c40f` / `#f59e0b`) – Pessoas sensíveis podem apresentar sintomas leves;
* **Ruim (81 a 120 IQA):** Laranja Alerta (`#e67e22`) – Sintomas perceptíveis na população em geral;
* **Muito Ruim (121 a 200 IQA):** Vermelho Crítico (`#e74c3c` / `#ef4444`) – Agravamento severo de problemas respiratórios;
* **Péssima (201 a 400 IQA):** Púrpura/Roxo (`#8e44ad`) – Condição de emergência sanitária;
* **Extrema (> 400 IQA):** Marrom/Castanho Profundo (`#43110c`) – Nível crítico excepcional.

### C. Responsividade e Alternância de Temas (Light / Dark Mode)
A aplicação adota a filosofia *Mobile-First*. O painel administrativo adapta seus componentes flexíveis (`flex-col lg:flex-row`), permitindo visualização fluida em celulares e monitores de alta resolução. O sistema conta com suporte completo a temas claro (*Light*) e escuro (*Dark*), preservando a preferência do usuário via `localStorage` e consultando automaticamente a diretiva de mídia `prefers-color-scheme` do sistema operacional.

---

## 4.5.2.3 - Interface Pública e Posicionamento Cartográfico (Home)

A tela inicial pública (`home.blade.php`) foi projetada para ocupar a totalidade da área útil da tela (*fullscreen map wrapper*), utilizando a biblioteca cartográfica de código aberto **Leaflet.js** sobre a base de blocos (*tiles*) do OpenStreetMap.

> **[INSERIR FIGURA AQUI]**  
> **Figura 4.X – Página inicial pública com mapa interativo de monitoramento ambiental em tempo real.**  
> *Fonte: Elaborado pelo autor (2026).*

### A. Interpolação Espacial Contínua por IDW (Shepard Modificado)
Ao contrário de soluções triviais que utilizam *heatmaps* genéricos com mistura aditiva de cores (que causam saturações e falsos picos visuais onde sensores estão agrupados), foi desenvolvida uma camada customizada em elemento HTML5 Canvas: a `L.IdwSurfaceLayer`. 

Essa camada aplica a formulação matemática de **Ponderação pelo Inverso da Distância** (*Inverse Distance Weighting* – IDW) com o **Método de Shepard Modificado com Suporte Compacto**:
$$w_i = \left( \frac{\max(0, 1 - d_i / R)}{d_i / R} \right)^2$$
onde $d_i$ representa a distância euclidiana da célula até o sensor $i$, e $R$ é o raio de busca adaptativo (variando de 320 metros em escalas macroscópicas até 500 metros em zoom de quarteirão). A estimativa contínua do parâmetro no ponto $(\text{lat}, \text{lng})$ é calculada como:
$$V(\text{lat}, \text{lng}) = \frac{\sum_{i=1}^n w_i \cdot V_i}{\sum_{i=1}^n w_i}$$

Dessa forma, o valor em qualquer ponto geográfico corresponde a uma transição contínua de classe $C^1$ suave, sem degraus ou descontinuidades circulares nas bordas.

### B. Trecho de Código: Renderização da Superfície IDW e Escuta de WebSockets

O fragmento a seguir, extraído de `resources/views/home.blade.php`, evidencia o cálculo de interpolação e a atualização reativa em tempo real via Laravel Echo:

```javascript
// Interpolação matemática IDW (Shepard Modificado) no Canvas nativo do Leaflet
function calcularPontoIdw(x, y, dataPoints, searchRadius, colorFn) {
    let num = 0, den = 0, minD2 = Infinity;
    const r2 = searchRadius * searchRadius;
    const invRadius = 1.0 / searchRadius;

    for (let i = 0; i < dataPoints.length; i++) {
        const pt = dataPoints[i];
        const dx = x - pt.px;
        const dy = y - pt.py;
        const d2 = dx * dx + dy * dy;

        if (d2 < r2) {
            const dist = Math.sqrt(d2);
            const u = dist * invRadius; // Distância normalizada [0, 1)
            const oneMinusU = 1.0 - u;
            // Derivada suave na borda para eliminar arcos visíveis
            const w = (oneMinusU * oneMinusU) / Math.max(u, 0.005);

            num += pt.val * w;
            den += w;
            if (d2 < minD2) minD2 = d2;
        }
    }

    if (den === 0) return null;
    return { valor: num / den, distMin: Math.sqrt(minD2) };
}

// Atualização em tempo real via WebSockets (Laravel Reverb + Echo)
function inicializarEscutaTelemetria() {
    window.Echo.channel('medicoes')
        .listen('NovaMedicaoRecebida', (dados) => {
            // Atualiza o array de pontos em memória para a métrica correspondente
            atualizarPontoMemoria(dados);
            
            // Reagenda a renderização gráfica via requestAnimationFrame sem recriar camadas
            agendarAtualizacaoVisual();
        });
}
```

Ao clicar sobre qualquer área da mancha colorida, o sistema calcula localmente o valor interpolado naquele ponto e plota um marcador (*Click Marker / Pin*) estilizado, exibindo a unidade de medida, o badge qualitativo (ex: "Qualidade Boa", "Moderada") e o carimbo de data e hora do sensor físico mais próximo.

---

## 4.5.2.4 - Painel Analítico e Séries Temporais (Dashboard)

Enquanto a página inicial provê uma fotografia espacial instantânea do ar urbano, o **Dashboard** (`dashboard.blade.php`) concentra as ferramentas analíticas voltadas ao planejamento e tomada de decisão pelo poder público.

> **[INSERIR FIGURA AQUI]**  
> **Figura 4.Y – Painel Analítico (Dashboard) evidenciando contadores de alerta, abas de seleção de métricas e gráfico temporal de linhas.**  
> *Fonte: Elaborado pelo autor (2026).*

### A. Contadores de Rede e Alertas de Saúde Pública
No topo do painel, uma grade com 4 cartões informativos sumariza a situação da rede municipal:
1. **Estações Ativas:** Quantidade de estações físicas operando com telemetria ativa;
2. **Total de Leituras:** Contagem acumulada de amostras processadas pela base;
3. **Alertas de Qualidade (IQA):** Quantidade de leituras onde $\text{PM}_{2.5} > 50\ \mu\text{g/m}^3$ ou $\text{CO}_2 > 1000\text{ ppm}$;
4. **Alertas de Particulado:** Ocorrências em que o material particulado ultrapassou o teto moderado.

### B. Agregação Temporal Adaptativa (*Bucketing*) no Backend
Para exibir gráficos de linhas contendo dezenas de milhares de registros sem degradar a memória do navegador do cliente, foi desenvolvido um algoritmo de **agrupamento temporal adaptativo** no método `DashboardController::determinarBucketTemporal`.

A granularidade temporal dos pontos do gráfico adapta-se dinamicamente ao intervalo de tempo (*span*) total entre a primeira e a última leitura da localidade consultada:

| Intervalo Total Analisado | Janela do *Bucket* | Rótulo no Eixo X | Finalidade |
| :--- | :--- | :--- | :--- |
| $\le 2\text{ minutos}$ | 5 segundos | `HH:mm:ss` | Acompanhamento imediato de calibração em bancada |
| $\le 10\text{ minutos}$ | 15 segundos | `HH:mm:ss` | Resolução de testes operacionais rápidos |
| $\le 2\text{ horas}$ | 1 minuto | `HH:mm` | Observação de rajadas de poluição de curto prazo |
| $\le 6\text{ horas}$ | 5 minutos | `HH:mm` | Dinâmica de tráfego nos horários de pico |
| $\le 24\text{ horas}$ | 15 minutos | `HH:mm` | Ciclo diário de dispersão atmosférica |
| $\le 7\text{ dias}$ | 1 hora | `DD/MM HH:00`| Comparativo semanal de emissões |
| $> 7\text{ dias}$ (até 30 dias) | 1 dia | `DD/MM` | Tendências sazonais e climatológicas municipais |

### C. Trecho de Código: Cálculo dos Buckets Temporais no Backend

O método a seguir, extraído de `DashboardController.php`, exemplifica essa orquestração:

```php
/**
 * Define o bucket de agrupamento e o rótulo temporal com base na amplitude dos dados.
 *
 * @return array{0: string, 1: string} [Chave de Agrupamento, Rótulo Exibido no Eixo X]
 */
protected function determinarBucketTemporal(Carbon $dt, int|float $spanMinutos, string $periodo): array
{
    // Se abranger até 24 horas, agrupa a cada 15 minutos
    if ($spanMinutos <= 1440) {
        $min = str_pad((string) (intdiv($dt->minute, 15) * 15), 2, '0', STR_PAD_LEFT);
        return [
            $dt->format('Y-m-d H:') . $min,
            $dt->format('H:') . $min,
        ];
    }

    // Se abranger até 7 dias, agrupa a cada 1 hora cheia
    if ($spanMinutos <= 10080) {
        return [
            $dt->format('Y-m-d H:00'),
            $dt->format('d/m H:00'),
        ];
    }

    // Acima de 7 dias (até 30 dias), consolida por dia calendário
    return [
        $dt->format('Y-m-d'),
        $dt->format('d/m'),
    ];
}
```

### D. Visualização Gráfica Multivariada com Chart.js
No cliente, o gráfico de linhas renderizado via **Chart.js** plota concomitantemente três curvas para o parâmetro selecionado (IQA, Temperatura, Umidade, $\text{PM}_{2.5}$ ou $\text{CO}_2$):
1. **Linha Contínua Central (Média Ponderada):** Representa o comportamento médio da variável na localidade;
2. **Linha Tracejada Superior (Valor Máximo):** Destaca os picos máximos observados no período, permitindo identificar rajadas atípicas de poluição;
3. **Linha Tracejada Inferior (Valor Mínimo):** Evidencia o piso de qualidade ou de conforto térmico registrado.

O trecho de código JavaScript extraído de `dashboard.blade.php` demonstra a configuração desses três *datasets* sincronizados:

```javascript
function gerarDatasetsGrafico(data, isDark) {
    const corMedia = data.cor;
    const corMaximo = data.metrica === 'poeira' ? '#dc2626' : '#ef4444';
    const corMinimo = data.metrica === 'umidade' ? '#0284c7' : '#3b82f6';

    return [
        {
            label: `Média (${data.unidade})`,
            data: data.valores || [],
            borderColor: corMedia,
            borderWidth: 2.5,
            tension: 0.35, // Curva suavizada (Bézier splines)
            fill: false
        },
        {
            label: `Máximo (${data.unidade})`,
            data: data.maximos || [],
            borderColor: corMaximo,
            borderWidth: 1.8,
            borderDash: [5, 4], // Estilo tracejado para o teto de emissão
            fill: false
        },
        {
            label: `Mínimo (${data.unidade})`,
            data: data.minimos || [],
            borderColor: corMinimo,
            borderWidth: 1.8,
            borderDash: [5, 4], // Estilo tracejado para o piso
            fill: false
        }
    ];
}
```

Abaixo do gráfico, quatro blocos dinâmicos complementam a análise fornecendo a **Média Calculada**, o **Valor Máximo**, o **Valor Mínimo** e o quantitativo total de **Amostras & Estações Agregadas**, com seus respectivos rótulos semânticos (ex: "Normal", "Atenção", "Elevado").
