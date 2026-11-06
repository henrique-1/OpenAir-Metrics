<?php

namespace App\Http\Controllers;

use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Medicao;
use App\Services\AlertaAmbientalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AlertaAmbientalService $alertaService
    ) {}

    /**
     * Exibe a página principal do Dashboard com os filtros e dados iniciais.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;

        $periodo = $request->input('periodo', '24h');
        $dataInicio = match ($periodo) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            'todos' => null,
            default => now()->subHours(24),
        };

        $periodoRotulo = match ($periodo) {
            '7d' => 'nos últimos 7 dias',
            '30d' => 'nos últimos 30 dias',
            'todos' => 'em todo o período',
            default => 'nas últimas 24 horas',
        };

        // Para gestor municipal a cidade é fixa; para superadmin pode selecionar qualquer cidade com estações
        $cidadeIdFixa = $user?->cidade_id;

        $cidadesQuery = Cidade::whereHas('bairros.estacoes')->with('estado')->orderBy('nome');
        if ($cidadeIdFixa && ! $isSuperAdmin) {
            $cidadesQuery->where('id', $cidadeIdFixa);
        }

        $cidades = $cidadesQuery->get();

        // Determina a cidade inicialmente selecionada
        $cidadeSelecionadaId = ($cidadeIdFixa && ! $isSuperAdmin)
            ? $cidadeIdFixa
            : ($request->input('cidade_id') ?: $cidades->first()?->id);

        $cidadeSelecionada = $cidadeSelecionadaId ? Cidade::with('estado')->find($cidadeSelecionadaId) : $cidades->first();
        $localidadeInicialNome = $cidadeSelecionada ? "{$cidadeSelecionada->nome} - {$cidadeSelecionada->estado?->uf}" : 'Jurisdição';

        // Bairros da cidade selecionada que possuem estações
        $bairros = $cidadeSelecionada
            ? Bairro::where('cidade_id', $cidadeSelecionada->id)->whereHas('estacoes')->orderBy('nome')->get()
            : collect();

        $estacaoQuery = Estacao::query();
        $medicaoQuery = Medicao::query();

        if ($cidadeSelecionada) {
            $estacaoQuery->whereHas('bairro', fn ($q) => $q->where('cidade_id', $cidadeSelecionada->id));
            $medicaoQuery->whereHas('estacao.bairro', fn ($q) => $q->where('cidade_id', $cidadeSelecionada->id));
        }

        $totalEstacoes = $estacaoQuery->count();
        $totalLeituras = (clone $medicaoQuery)->count();

        // Alertas ambientais computados a partir dos limiares multicritério no período inicial (24h)
        $alertasQuery = clone $medicaoQuery;
        if ($dataInicio !== null) {
            $alertasQuery->where('data_hora', '>=', $dataInicio);
        }

        $alertasIqa = (clone $alertasQuery)->where(fn ($q) => $q->where('iqa', '>', 40)->orWhere('poeira', '>', 15.0)->orWhere('co2', '>', 700))->count();
        $alertasPm = (clone $alertasQuery)->where('poeira', '>', 15.0)->count();
        $alertasTemp = (clone $alertasQuery)->where(fn ($q) => $q->where('temperatura', '<', 20.0)->orWhere('temperatura', '>', 24.0))->count();
        $alertasUmidade = (clone $alertasQuery)->where('umidade', '<', 40.0)->count();
        $alertasCo2 = (clone $alertasQuery)->where('co2', '>', 700)->count();

        return view('dashboard', [
            'totalEstacoes' => $totalEstacoes,
            'totalLeituras' => $totalLeituras,
            'alertasIqa' => $alertasIqa,
            'alertasTemp' => $alertasTemp,
            'alertasUmidade' => $alertasUmidade,
            'alertasCo2' => $alertasCo2,
            'alertasPm' => $alertasPm,
            'periodo' => $periodo,
            'periodoRotulo' => $periodoRotulo,
            'localidadeInicialNome' => $localidadeInicialNome,
            'cidades' => $cidades,
            'bairros' => $bairros,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    /**
     * Endpoint API para agregação e cálculo de séries temporais dos gráficos.
     */
    public function dadosGrafico(Request $request): JsonResponse
    {
        $user = Auth::user();
        $isSuperAdmin = $user?->isSuperAdmin() ?? false;

        $tipoAgrupamento = $request->input('tipo_agrupamento', 'cidade'); // 'cidade' ou 'bairro'
        $localidadeId = $request->input('localidade_id');
        $cidadeParam = $request->input('cidade_id');
        $metrica = $request->input('metrica', 'qualidade_ar'); // 'qualidade_ar', 'temperatura', 'umidade', 'poeira', 'co2'
        $periodo = $request->input('periodo', '24h'); // '24h', '7d', '30d', 'todos'

        // 1. Identifica a cidade selecionada: para gestor municipal a cidade é fixa; para superadmin usa o parâmetro
        $cidadeId = ($user?->cidade_id && ! $isSuperAdmin) ? $user->cidade_id : $cidadeParam;

        // Fallback para testes ou caso cidade_id não tenha sido passada
        if (! $cidadeId) {
            if ($tipoAgrupamento === 'bairro' && $localidadeId) {
                $cidadeId = Bairro::find($localidadeId)?->cidade_id;
            } elseif ($localidadeId) {
                $cidadeId = $localidadeId;
            }

            if (! $cidadeId) {
                $primeiraCidade = Cidade::whereHas('bairros.estacoes')->orderBy('nome')->first();
                $cidadeId = $primeiraCidade?->id;
            }
        }

        $cidade = $cidadeId ? Cidade::with('estado')->find($cidadeId) : null;
        $cidadeNome = $cidade ? "{$cidade->nome} - {$cidade->estado?->uf}" : 'Jurisdição';

        $localidadeNome = $cidadeNome;
        $estacoesQuery = Estacao::query();

        if ($tipoAgrupamento === 'bairro') {
            // No modo Bairro, filtra pelo bairro selecionado dentro da cidade
            $bairroQuery = Bairro::whereHas('estacoes');
            if ($cidadeId) {
                $bairroQuery->where('cidade_id', $cidadeId);
            }

            $bairro = null;
            if ($localidadeId) {
                $bairro = (clone $bairroQuery)->find($localidadeId);
            }
            if (! $bairro) {
                $bairro = (clone $bairroQuery)->orderBy('nome')->first();
            }

            if ($bairro) {
                $localidadeNome = "Bairro {$bairro->nome}";
                $estacoesQuery->where('bairro_id', $bairro->id);
            } elseif ($cidadeId) {
                $estacoesQuery->whereHas('bairro', fn ($q) => $q->where('cidade_id', $cidadeId));
            }
        } else {
            // No modo Cidade, congrega todas as estações do município selecionado
            $localidadeNome = $cidadeNome;
            if ($cidadeId) {
                $estacoesQuery->whereHas('bairro', fn ($q) => $q->where('cidade_id', $cidadeId));
            }
        }

        $estacaoIds = $estacoesQuery->pluck('private_id');

        // 2. Filtro temporal
        $dataInicio = match ($periodo) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subHours(24),
        };

        $medicoesQuery = Medicao::whereIn('estacao_id', $estacaoIds);
        if ($periodo !== 'todos') {
            $medicoesQuery->where('data_hora', '>=', $dataInicio);
        }

        $medicoes = $medicoesQuery->orderBy('data_hora', 'asc')->get();

        // 3. Informações da métrica
        $infoMetrica = match ($metrica) {
            'temperatura' => [
                'nome' => 'Temperatura',
                'unidade' => '°C',
                'cor' => '#f59e0b',
                'bgCor' => 'rgba(245, 158, 11, 0.15)',
            ],
            'umidade' => [
                'nome' => 'Umidade Relativa',
                'unidade' => '%',
                'cor' => '#3b82f6',
                'bgCor' => 'rgba(59, 130, 246, 0.15)',
            ],
            'poeira' => [
                'nome' => 'Material Particulado (PM)',
                'unidade' => 'µg/m³',
                'cor' => '#ef4444',
                'bgCor' => 'rgba(239, 68, 68, 0.15)',
            ],
            'co2' => [
                'nome' => 'Dióxido de Carbono (CO₂)',
                'unidade' => 'ppm',
                'cor' => '#78716c',
                'bgCor' => 'rgba(120, 113, 108, 0.15)',
            ],
            default => [
                'nome' => 'Qualidade do Ar (IQA)',
                'unidade' => 'IQA',
                'cor' => '#10b981',
                'bgCor' => 'rgba(16, 185, 129, 0.15)',
            ],
        };

        // 4. Se não houver medições registradas, retorna dados vazios formatados
        $periodoRotulo = match ($periodo) {
            '7d' => 'nos últimos 7 dias',
            '30d' => 'nos últimos 30 dias',
            default => 'nas últimas 24 horas',
        };

        $faixasAlerta = $this->alertaService->obterFaixasGrafico($metrica);

        // Contadores reais de alertas ambientais para a localidade e período selecionados
        $alertasIqa = (clone $medicoesQuery)->where(fn ($q) => $q->where('iqa', '>', 40)->orWhere('poeira', '>', 15.0)->orWhere('co2', '>', 700))->count();
        $alertasPm = (clone $medicoesQuery)->where('poeira', '>', 15.0)->count();
        $alertasTemp = (clone $medicoesQuery)->where(fn ($q) => $q->where('temperatura', '<', 20.0)->orWhere('temperatura', '>', 24.0))->count();
        $alertasUmidade = (clone $medicoesQuery)->where('umidade', '<', 40.0)->count();
        $alertasCo2 = (clone $medicoesQuery)->where('co2', '>', 700)->count();

        $contadoresAlertas = [
            'iqa' => $alertasIqa,
            'pm' => $alertasPm,
            'temperatura' => $alertasTemp,
            'umidade' => $alertasUmidade,
            'co2' => $alertasCo2,
        ];

        if ($medicoes->isEmpty()) {
            return response()->json([
                'localidade' => $localidadeNome,
                'metrica' => $metrica,
                'nome_metrica' => $infoMetrica['nome'],
                'unidade' => $infoMetrica['unidade'],
                'cor' => $infoMetrica['cor'],
                'bgCor' => $infoMetrica['bgCor'],
                'periodo' => $periodo,
                'periodo_rotulo' => $periodoRotulo,
                'media' => 0.0,
                'maximo' => 0.0,
                'minimo' => 0.0,
                'total_leituras' => 0,
                'estacoes_ativas' => $estacaoIds->count(),
                'labels' => [],
                'valores' => [],
                'maximos' => [],
                'minimos' => [],
                'faixas_alerta' => $faixasAlerta,
                'alertas_pontos' => [],
                'contadores_alertas' => [
                    'iqa' => 0,
                    'pm' => 0,
                    'temperatura' => 0,
                    'umidade' => 0,
                    'co2' => 0,
                ],
                'status_classificacao' => 'Sem Dados',
                'status_cor' => 'text-athens-gray-500',
                'status_descricao' => 'Nenhuma medição encontrada para a localidade no período.',
            ]);
        }

        // 5. Extração e cálculo dos pontos temporais com granularidade adaptativa
        $primeiraMedicao = $medicoes->first();
        $ultimaMedicao = $medicoes->last();

        $primeiraData = $primeiraMedicao->data_hora ? Carbon::parse($primeiraMedicao->data_hora) : $primeiraMedicao->created_at;
        $ultimaData = $ultimaMedicao->data_hora ? Carbon::parse($ultimaMedicao->data_hora) : $ultimaMedicao->created_at;
        $spanMinutos = max(1, (int) ceil($primeiraData->diffInMinutes($ultimaData)));

        $grupos = [];
        $todosValores = [];

        foreach ($medicoes as $m) {
            $val = match ($metrica) {
                'temperatura' => (float) $m->temperatura,
                'umidade' => (float) $m->umidade,
                'poeira' => (float) $m->poeira,
                'co2' => (float) $m->co2,
                default => (float) Medicao::calcularIqa((float) $m->poeira, (int) $m->co2),
            };

            $dt = $m->data_hora ? Carbon::parse($m->data_hora) : $m->created_at;
            [$chaveTempo, $labelTempo] = $this->determinarBucketTemporal($dt, $spanMinutos, $periodo);

            $grupos[$chaveTempo]['label'] = $labelTempo;
            $grupos[$chaveTempo]['valores'][] = $val;
            $todosValores[] = $val;
        }

        $labels = [];
        $valores = [];
        $maximos = [];
        $minimos = [];

        foreach ($grupos as $g) {
            $labels[] = $g['label'];
            // Calcula a média, o máximo e o mínimo das estações no intervalo de tempo
            $valores[] = round(array_sum($g['valores']) / count($g['valores']), 2);
            $maximos[] = round(max($g['valores']), 2);
            $minimos[] = round(min($g['valores']), 2);
        }

        $mediaGeral = round(array_sum($todosValores) / count($todosValores), 2);
        $maxGeral = round(max($todosValores), 2);
        $minGeral = round(min($todosValores), 2);

        // Classificação e badge de status da Média
        $classificacao = $this->obterClassificacao($metrica, $mediaGeral);

        // Avaliação de alerta individual para cada ponto médio da série temporal
        $alertasPontos = [];
        foreach ($valores as $valPonto) {
            $aval = match ($metrica) {
                'temperatura' => $this->alertaService->avaliarTemperatura($valPonto),
                'umidade' => $this->alertaService->avaliarUmidade($valPonto),
                'poeira' => $this->alertaService->avaliarPm($valPonto),
                'co2' => $this->alertaService->avaliarCo2($valPonto),
                default => $this->alertaService->avaliarIqa($valPonto),
            };

            $alertasPontos[] = [
                'nivel' => $aval['nivel']->value,
                'peso' => $aval['nivel']->peso(),
                'status' => $aval['status'],
                'classe' => $aval['classe'],
                'descricao' => $aval['descricao'],
                'cor' => $aval['cor'],
            ];
        }

        return response()->json([
            'localidade' => $localidadeNome,
            'metrica' => $metrica,
            'nome_metrica' => $infoMetrica['nome'],
            'unidade' => $infoMetrica['unidade'],
            'cor' => $infoMetrica['cor'],
            'bgCor' => $infoMetrica['bgCor'],
            'periodo' => $periodo,
            'periodo_rotulo' => $periodoRotulo,
            'media' => $mediaGeral,
            'maximo' => $maxGeral,
            'minimo' => $minGeral,
            'total_leituras' => count($todosValores),
            'estacoes_ativas' => $estacaoIds->count(),
            'labels' => $labels,
            'valores' => $valores,
            'maximos' => $maximos,
            'minimos' => $minimos,
            'faixas_alerta' => $faixasAlerta,
            'alertas_pontos' => $alertasPontos,
            'contadores_alertas' => $contadoresAlertas,
            'status_classificacao' => $classificacao['texto'],
            'status_cor' => $classificacao['cor'],
            'status_descricao' => $classificacao['descricao'],
            'status_alerta' => $classificacao['status'],
        ]);
    }

    /**
     * Classifica o valor médio para exibição de status contextual.
     *
     * @return array{texto: string, cor: string, status: string, descricao: string, nivel: string}
     */
    protected function obterClassificacao(string $metrica, float $valor): array
    {
        $avaliacao = match ($metrica) {
            'temperatura' => $this->alertaService->avaliarTemperatura($valor),
            'umidade' => $this->alertaService->avaliarUmidade($valor),
            'poeira' => $this->alertaService->avaliarPm($valor),
            'co2' => $this->alertaService->avaliarCo2($valor),
            default => $this->alertaService->avaliarIqa($valor),
        };

        return [
            'texto' => $avaliacao['classe'],
            'cor' => $avaliacao['badge_class'],
            'status' => $avaliacao['status'],
            'descricao' => $avaliacao['descricao'],
            'nivel' => $avaliacao['nivel']->value,
        ];
    }

    /**
     * Define o bucket de agrupamento e o rótulo temporal com base na amplitude dos dados (tempo total analisado).
     *
     * @return array{0: string, 1: string}
     */
    protected function determinarBucketTemporal(Carbon $dt, int|float $spanMinutos, string $periodo): array
    {
        // Se todas as medições ocorreram em até 2 minutos, agrupa a cada 5 segundos
        if ($spanMinutos <= 2) {
            $seg = str_pad((string) (intdiv($dt->second, 5) * 5), 2, '0', STR_PAD_LEFT);

            return [
                $dt->format('Y-m-d H:i:').$seg,
                $dt->format('H:i:').$seg,
            ];
        }

        // Se ocorreram em até 10 minutos, agrupa a cada 15 segundos
        if ($spanMinutos <= 10) {
            $seg = str_pad((string) (intdiv($dt->second, 15) * 15), 2, '0', STR_PAD_LEFT);

            return [
                $dt->format('Y-m-d H:i:').$seg,
                $dt->format('H:i:').$seg,
            ];
        }

        // Se abrangem até 2 horas (ex: rajadas recentes de 30 a 60 min), agrupa minuto a minuto
        if ($spanMinutos <= 120) {
            return [
                $dt->format('Y-m-d H:i'),
                $dt->format('H:i'),
            ];
        }

        // Se abrangem até 6 horas, agrupa a cada 5 minutos
        if ($spanMinutos <= 360) {
            $min = str_pad((string) (intdiv($dt->minute, 5) * 5), 2, '0', STR_PAD_LEFT);

            return [
                $dt->format('Y-m-d H:').$min,
                $dt->format('H:').$min,
            ];
        }

        // Se abrangem até 24 horas, agrupa a cada 15 minutos
        if ($spanMinutos <= 1440) {
            $min = str_pad((string) (intdiv($dt->minute, 15) * 15), 2, '0', STR_PAD_LEFT);

            return [
                $dt->format('Y-m-d H:').$min,
                $dt->format('H:').$min,
            ];
        }

        // Se abrangem até 7 dias, agrupa a cada hora
        if ($spanMinutos <= 10080) {
            return [
                $dt->format('Y-m-d H:00'),
                $dt->format('d/m H:00'),
            ];
        }

        // Acima de 7 dias (até 30 dias), agrupa por dia
        return [
            $dt->format('Y-m-d'),
            $dt->format('d/m'),
        ];
    }

    /**
     * Endpoint API para listar bairros com estações de uma cidade (usado pelo seletor dinâmico da dashboard).
     */
    public function bairrosComEstacoes(Cidade $cidade): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->cidade_id && ! $user->isSuperAdmin() && (int) $user->cidade_id !== (int) $cidade->id) {
            abort(403, 'Acesso restrito à jurisdição do seu município.');
        }

        $bairros = $cidade->bairros()
            ->whereHas('estacoes')
            ->orderBy('nome')
            ->get(['id', 'nome']);

        return response()->json($bairros);
    }
}
