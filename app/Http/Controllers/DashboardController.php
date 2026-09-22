<?php

namespace App\Http\Controllers;

use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Medicao;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Exibe a página principal do Dashboard com os filtros e dados iniciais.
     */
    public function index(): View
    {
        $user = Auth::user();
        if ($user?->isSuperAdmin()) {
            abort(403, 'O super-usuário só pode gerenciar administradores.');
        }

        $estacaoQuery = Estacao::query();
        $medicaoQuery = Medicao::query();
        $cidadesQuery = Cidade::whereHas('bairros.estacoes')->with('estado')->orderBy('nome');
        $bairrosQuery = Bairro::whereHas('estacoes')->with(['cidade.estado'])->orderBy('nome');

        if ($user && $user->cidade_id) {
            $estacaoQuery->whereHas('bairro', fn ($q) => $q->where('cidade_id', $user->cidade_id));
            $medicaoQuery->whereHas('estacao.bairro', fn ($q) => $q->where('cidade_id', $user->cidade_id));
            $cidadesQuery->where('id', $user->cidade_id);
            $bairrosQuery->where('cidade_id', $user->cidade_id);
        }

        $totalEstacoes = $estacaoQuery->count();
        $totalLeituras = (clone $medicaoQuery)->count();

        // Alertas reais computados a partir das medições
        $alertasIqa = (clone $medicaoQuery)->where(fn ($q) => $q->where('poeira', '>', 50)->orWhere('co2', '>', 1000))->count();
        $alertasTemp = (clone $medicaoQuery)->where(fn ($q) => $q->where('temperatura', '>', 35)->orWhere('temperatura', '<', 10))->count();
        $alertasUmidade = (clone $medicaoQuery)->where(fn ($q) => $q->where('umidade', '<', 30)->orWhere('umidade', '>', 85))->count();
        $alertasCo2 = (clone $medicaoQuery)->where('co2', '>', 1000)->count();
        $alertasPm = (clone $medicaoQuery)->where('poeira', '>', 50)->count();

        // Cidades que possuem estações cadastradas
        $cidades = $cidadesQuery->get();

        // Bairros que possuem estações cadastradas
        $bairros = $bairrosQuery->get();

        return view('dashboard', [
            'totalEstacoes' => $totalEstacoes,
            'totalLeituras' => $totalLeituras,
            'alertasIqa' => $alertasIqa,
            'alertasTemp' => $alertasTemp,
            'alertasUmidade' => $alertasUmidade,
            'alertasCo2' => $alertasCo2,
            'alertasPm' => $alertasPm,
            'cidades' => $cidades,
            'bairros' => $bairros,
        ]);
    }

    /**
     * Endpoint API para agregação e cálculo de séries temporais dos gráficos.
     */
    public function dadosGrafico(Request $request): JsonResponse
    {
        $user = Auth::user();
        if ($user?->isSuperAdmin()) {
            abort(403, 'O super-usuário só pode gerenciar administradores.');
        }

        $tipoAgrupamento = $request->input('tipo_agrupamento', 'cidade'); // 'cidade' ou 'bairro'
        $localidadeId = $request->input('localidade_id');
        $metrica = $request->input('metrica', 'qualidade_ar'); // 'qualidade_ar', 'temperatura', 'umidade', 'poeira', 'co2'
        $periodo = $request->input('periodo', '24h'); // '24h', '7d', '30d', 'todos'

        // 1. Identifica as estações vinculadas à localidade selecionada
        $localidadeNome = 'Geral';
        $estacoesQuery = Estacao::query();

        if ($user && $user->cidade_id) {
            $estacoesQuery->whereHas('bairro', fn ($q) => $q->where('cidade_id', $user->cidade_id));
        }

        if ($tipoAgrupamento === 'bairro' && $localidadeId) {
            $bairroQuery = Bairro::with('cidade.estado');
            if ($user && $user->cidade_id) {
                $bairroQuery->where('cidade_id', $user->cidade_id);
            }
            $bairro = $bairroQuery->find($localidadeId);
            if ($bairro) {
                $localidadeNome = "{$bairro->nome} ({$bairro->cidade?->nome} - {$bairro->cidade?->estado?->uf})";
                $estacoesQuery->where('bairro_id', $bairro->id);
            }
        } elseif ($localidadeId) {
            $cidadeQuery = Cidade::with('estado');
            if ($user && $user->cidade_id) {
                $cidadeQuery->where('id', $user->cidade_id);
            }
            $cidade = $cidadeQuery->find($localidadeId);
            if ($cidade) {
                $localidadeNome = "{$cidade->nome} - {$cidade->estado?->uf}";
                $estacoesQuery->whereHas('bairro', fn ($q) => $q->where('cidade_id', $cidade->id));
            }
        } else {
            // Se nenhuma localidade foi informada, seleciona a primeira cidade disponível com estações
            $primeiraCidadeQuery = Cidade::whereHas('bairros.estacoes')->with('estado');
            if ($user && $user->cidade_id) {
                $primeiraCidadeQuery->where('id', $user->cidade_id);
            }
            $primeiraCidade = $primeiraCidadeQuery->first();
            if ($primeiraCidade) {
                $localidadeNome = "{$primeiraCidade->nome} - {$primeiraCidade->estado?->uf}";
                $estacoesQuery->whereHas('bairro', fn ($q) => $q->where('cidade_id', $primeiraCidade->id));
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
                'status_classificacao' => 'Sem Dados',
                'status_cor' => 'text-athens-gray-500',
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
            'status_classificacao' => $classificacao['texto'],
            'status_cor' => $classificacao['cor'],
        ]);
    }

    /**
     * Classifica o valor médio para exibição de status contextual.
     *
     * @return array{texto: string, cor: string}
     */
    protected function obterClassificacao(string $metrica, float $valor): array
    {
        return match ($metrica) {
            'qualidade_ar' => match (true) {
                $valor <= 50 => ['texto' => 'Boa', 'cor' => 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 border-emerald-200 dark:border-emerald-800'],
                $valor <= 100 => ['texto' => 'Moderada', 'cor' => 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-950/50 border-yellow-200 dark:border-yellow-800'],
                $valor <= 150 => ['texto' => 'Ruim', 'cor' => 'text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/50 border-orange-200 dark:border-orange-800'],
                $valor <= 200 => ['texto' => 'Muito Ruim', 'cor' => 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/50 border-red-200 dark:border-red-800'],
                default => ['texto' => 'Péssima', 'cor' => 'text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-950/50 border-purple-200 dark:border-purple-800'],
            },
            'temperatura' => match (true) {
                $valor < 15 => ['texto' => 'Frio', 'cor' => 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/50 border-blue-200 dark:border-blue-800'],
                $valor <= 26 => ['texto' => 'Agradável', 'cor' => 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 border-emerald-200 dark:border-emerald-800'],
                $valor <= 32 => ['texto' => 'Quente', 'cor' => 'text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/50 border-orange-200 dark:border-orange-800'],
                default => ['texto' => 'Muito Quente', 'cor' => 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/50 border-red-200 dark:border-red-800'],
            },
            'umidade' => match (true) {
                $valor < 30 => ['texto' => 'Estado de Alerta', 'cor' => 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/50 border-red-200 dark:border-red-800'],
                $valor <= 40 => ['texto' => 'Atenção', 'cor' => 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-950/50 border-yellow-200 dark:border-yellow-800'],
                $valor <= 70 => ['texto' => 'Ideal', 'cor' => 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 border-emerald-200 dark:border-emerald-800'],
                default => ['texto' => 'Elevada', 'cor' => 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/50 border-blue-200 dark:border-blue-800'],
            },
            'poeira' => match (true) {
                $valor <= 25 => ['texto' => 'Baixo', 'cor' => 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 border-emerald-200 dark:border-emerald-800'],
                $valor <= 50 => ['texto' => 'Moderado', 'cor' => 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-950/50 border-yellow-200 dark:border-yellow-800'],
                default => ['texto' => 'Elevado', 'cor' => 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/50 border-red-200 dark:border-red-800'],
            },
            'co2' => match (true) {
                $valor <= 600 => ['texto' => 'Excelente', 'cor' => 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 border-emerald-200 dark:border-emerald-800'],
                $valor <= 1000 => ['texto' => 'Aceitável', 'cor' => 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-950/50 border-yellow-200 dark:border-yellow-800'],
                default => ['texto' => 'Alto', 'cor' => 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/50 border-red-200 dark:border-red-800'],
            },
            default => ['texto' => 'Normal', 'cor' => 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 border-emerald-200 dark:border-emerald-800'],
        };
    }

    /**
     * Determina a chave de agrupamento e o label temporal adequados para a granularidade dos dados.
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
}
