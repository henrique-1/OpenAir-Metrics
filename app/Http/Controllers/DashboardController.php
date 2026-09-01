<?php

namespace App\Http\Controllers;

use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Medicao;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Exibe a página principal do Dashboard com os filtros e dados iniciais.
     */
    public function index(): View
    {
        $totalEstacoes = Estacao::count();
        $totalLeituras = Medicao::count();

        // Alertas reais computados a partir das medições
        $alertasIqa = Medicao::where('poeira', '>', 50)->orWhere('co2', '>', 1000)->count();
        $alertasTemp = Medicao::where('temperatura', '>', 35)->orWhere('temperatura', '<', 10)->count();
        $alertasUmidade = Medicao::where('umidade', '<', 30)->orWhere('umidade', '>', 85)->count();
        $alertasCo2 = Medicao::where('co2', '>', 1000)->count();
        $alertasPm = Medicao::where('poeira', '>', 50)->count();

        // Cidades que possuem estações cadastradas
        $cidades = Cidade::whereHas('bairros.estacoes')
            ->with('estado')
            ->orderBy('nome')
            ->get();

        // Bairros que possuem estações cadastradas
        $bairros = Bairro::whereHas('estacoes')
            ->with(['cidade.estado'])
            ->orderBy('nome')
            ->get();

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
        $tipoAgrupamento = $request->input('tipo_agrupamento', 'cidade'); // 'cidade' ou 'bairro'
        $localidadeId = $request->input('localidade_id');
        $metrica = $request->input('metrica', 'qualidade_ar'); // 'qualidade_ar', 'temperatura', 'umidade', 'poeira', 'co2'
        $periodo = $request->input('periodo', '24h'); // '24h', '7d', '30d', 'todos'

        // 1. Identifica as estações vinculadas à localidade selecionada
        $localidadeNome = 'Geral';
        $estacoesQuery = Estacao::query();

        if ($tipoAgrupamento === 'bairro' && $localidadeId) {
            $bairro = Bairro::with('cidade.estado')->find($localidadeId);
            if ($bairro) {
                $localidadeNome = "{$bairro->nome} ({$bairro->cidade?->nome} - {$bairro->cidade?->estado?->uf})";
                $estacoesQuery->where('bairro_id', $bairro->id);
            }
        } elseif ($localidadeId) {
            $cidade = Cidade::with('estado')->find($localidadeId);
            if ($cidade) {
                $localidadeNome = "{$cidade->nome} - {$cidade->estado?->uf}";
                $estacoesQuery->whereHas('bairro', fn ($q) => $q->where('cidade_id', $cidade->id));
            }
        } else {
            // Se nenhuma localidade foi informada, seleciona a primeira cidade disponível com estações
            $primeiraCidade = Cidade::whereHas('bairros.estacoes')->with('estado')->first();
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
        if ($medicoes->isEmpty()) {
            return response()->json([
                'localidade' => $localidadeNome,
                'metrica' => $metrica,
                'nome_metrica' => $infoMetrica['nome'],
                'unidade' => $infoMetrica['unidade'],
                'cor' => $infoMetrica['cor'],
                'bgCor' => $infoMetrica['bgCor'],
                'media' => 0.0,
                'maximo' => 0.0,
                'minimo' => 0.0,
                'total_leituras' => 0,
                'estacoes_ativas' => $estacaoIds->count(),
                'labels' => [],
                'valores' => [],
                'status_classificacao' => 'Sem Dados',
                'status_cor' => 'text-athens-gray-500',
            ]);
        }

        // 5. Extração e cálculo dos pontos temporais
        // Agrupa por intervalo para gerar curva suave e legível
        $formatoAgrupamento = match ($periodo) {
            '24h' => 'Y-m-d H:00',
            '7d', '30d' => 'Y-m-d H:00',
            default => 'Y-m-d H:00',
        };

        $formatoLabel = match ($periodo) {
            '24h' => 'H:i',
            '7d' => 'd/m H:i',
            '30d' => 'd/m',
            default => 'd/m H:i',
        };

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

            $chaveTempo = $m->data_hora ? Carbon::parse($m->data_hora)->format($formatoAgrupamento) : $m->created_at->format($formatoAgrupamento);
            $labelTempo = $m->data_hora ? Carbon::parse($m->data_hora)->format($formatoLabel) : $m->created_at->format($formatoLabel);

            $grupos[$chaveTempo]['label'] = $labelTempo;
            $grupos[$chaveTempo]['valores'][] = $val;
            $todosValores[] = $val;
        }

        $labels = [];
        $valores = [];

        foreach ($grupos as $g) {
            $labels[] = $g['label'];
            // Calcula a média das estações no intervalo de tempo
            $valores[] = round(array_sum($g['valores']) / count($g['valores']), 2);
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
            'media' => $mediaGeral,
            'maximo' => $maxGeral,
            'minimo' => $minGeral,
            'total_leituras' => count($todosValores),
            'estacoes_ativas' => $estacaoIds->count(),
            'labels' => $labels,
            'valores' => $valores,
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
                $valor <= 50 => ['texto' => 'Boa', 'cor' => 'text-emerald-600 bg-emerald-50 border-emerald-200'],
                $valor <= 100 => ['texto' => 'Moderada', 'cor' => 'text-yellow-600 bg-yellow-50 border-yellow-200'],
                $valor <= 150 => ['texto' => 'Ruim', 'cor' => 'text-orange-600 bg-orange-50 border-orange-200'],
                $valor <= 200 => ['texto' => 'Muito Ruim', 'cor' => 'text-red-600 bg-red-50 border-red-200'],
                default => ['texto' => 'Péssima', 'cor' => 'text-purple-700 bg-purple-50 border-purple-200'],
            },
            'temperatura' => match (true) {
                $valor < 15 => ['texto' => 'Frio', 'cor' => 'text-blue-600 bg-blue-50 border-blue-200'],
                $valor <= 26 => ['texto' => 'Agradável', 'cor' => 'text-emerald-600 bg-emerald-50 border-emerald-200'],
                $valor <= 32 => ['texto' => 'Quente', 'cor' => 'text-orange-600 bg-orange-50 border-orange-200'],
                default => ['texto' => 'Muito Quente', 'cor' => 'text-red-600 bg-red-50 border-red-200'],
            },
            'umidade' => match (true) {
                $valor < 30 => ['texto' => 'Estado de Alerta', 'cor' => 'text-red-600 bg-red-50 border-red-200'],
                $valor <= 40 => ['texto' => 'Atenção', 'cor' => 'text-yellow-600 bg-yellow-50 border-yellow-200'],
                $valor <= 70 => ['texto' => 'Ideal', 'cor' => 'text-emerald-600 bg-emerald-50 border-emerald-200'],
                default => ['texto' => 'Elevada', 'cor' => 'text-blue-600 bg-blue-50 border-blue-200'],
            },
            'poeira' => match (true) {
                $valor <= 25 => ['texto' => 'Baixo', 'cor' => 'text-emerald-600 bg-emerald-50 border-emerald-200'],
                $valor <= 50 => ['texto' => 'Moderado', 'cor' => 'text-yellow-600 bg-yellow-50 border-yellow-200'],
                default => ['texto' => 'Elevado', 'cor' => 'text-red-600 bg-red-50 border-red-200'],
            },
            'co2' => match (true) {
                $valor <= 600 => ['texto' => 'Excelente', 'cor' => 'text-emerald-600 bg-emerald-50 border-emerald-200'],
                $valor <= 1000 => ['texto' => 'Aceitável', 'cor' => 'text-yellow-600 bg-yellow-50 border-yellow-200'],
                default => ['texto' => 'Alto', 'cor' => 'text-red-600 bg-red-50 border-red-200'],
            },
            default => ['texto' => 'Normal', 'cor' => 'text-emerald-600 bg-emerald-50 border-emerald-200'],
        };
    }
}
