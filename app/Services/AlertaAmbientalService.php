<?php

namespace App\Services;

use App\Enums\NivelAlerta;
use App\Models\Medicao;

class AlertaAmbientalService
{
    /**
     * Avalia o Índice de Qualidade do Ar (IQA).
     *
     * @return array{nivel: NivelAlerta, classe: string, status: string, descricao: string, cor: string, badge_class: string}
     */
    public function avaliarIqa(float|int $iqa): array
    {
        $valor = (float) $iqa;

        return match (true) {
            $valor <= 40.0 => [
                'nivel' => NivelAlerta::Nenhum,
                'classe' => 'Boa',
                'status' => 'Sem Alerta',
                'descricao' => 'Ar seguro à saúde. Nível ideal de renovação e baixíssimo risco de contaminação.',
                'cor' => NivelAlerta::Nenhum->corHex(),
                'badge_class' => NivelAlerta::Nenhum->badgeClass(),
            ],
            $valor <= 80.0 => [
                'nivel' => NivelAlerta::Atencao,
                'classe' => 'Moderada',
                'status' => 'Alerta de Atenção',
                'descricao' => 'Tolerável. Grupos sensíveis (crianças, idosos, cardiopatas) podem apresentar tosse seca e cansaço.',
                'cor' => NivelAlerta::Atencao->corHex(),
                'badge_class' => NivelAlerta::Atencao->badgeClass(),
            ],
            $valor <= 120.0 => [
                'nivel' => NivelAlerta::Critico,
                'classe' => 'Ruim',
                'status' => 'Alerta Crítico',
                'descricao' => 'Insalubre. Toda a população pode ter irritação nos olhos/garganta e cansaço. Ventilação inadequada.',
                'cor' => NivelAlerta::Critico->corHex(),
                'badge_class' => NivelAlerta::Critico->badgeClass(),
            ],
            $valor <= 200.0 => [
                'nivel' => NivelAlerta::CriticoSevero,
                'classe' => 'Muito Ruim',
                'status' => 'Alerta Crítico Severo',
                'descricao' => 'Muito insalubre. Agravamento dos sintomas na população em geral, falta de ar e respiração ofegante.',
                'cor' => NivelAlerta::CriticoSevero->corHex(),
                'badge_class' => NivelAlerta::CriticoSevero->badgeClass(),
            ],
            default => [
                'nivel' => NivelAlerta::Emergencia,
                'classe' => 'Péssima',
                'status' => 'Alerta de Emergência',
                'descricao' => 'Perigoso. Riscos severos de complicações respiratórias e cardiovasculares agudas em toda a população.',
                'cor' => NivelAlerta::Emergencia->corHex(),
                'badge_class' => NivelAlerta::Emergencia->badgeClass(),
            ],
        };
    }

    /**
     * Avalia a Temperatura em graus Celsius (°C).
     *
     * @return array{nivel: NivelAlerta, classe: string, status: string, descricao: string, cor: string, badge_class: string}
     */
    public function avaliarTemperatura(float|int $temperatura): array
    {
        $valor = (float) $temperatura;

        return match (true) {
            $valor < 0.0 => [
                'nivel' => NivelAlerta::Emergencia,
                'classe' => 'Congelamento/Geada',
                'status' => 'Alerta Crítico de Congelamento/Geada',
                'descricao' => 'Dispara o alerta máximo de Risco de Congelamento/Geada, indicando hipotermia e necessidade de aquecimento.',
                'cor' => NivelAlerta::Emergencia->corHex(),
                'badge_class' => NivelAlerta::Emergencia->badgeClass(),
            ],
            $valor < 10.0 => [
                'nivel' => NivelAlerta::CriticoSevero,
                'classe' => 'Frio Intenso',
                'status' => 'Alerta Crítico Severo - Frio Intenso',
                'descricao' => 'Frio extremo; risco severo de hipotermia corporal e suscetibilidade acentuada a infecções respiratórias.',
                'cor' => NivelAlerta::CriticoSevero->corHex(),
                'badge_class' => NivelAlerta::CriticoSevero->badgeClass(),
            ],
            $valor < 18.0 => [
                'nivel' => NivelAlerta::Critico,
                'classe' => 'Frio Severo',
                'status' => 'Alerta Crítico - Frio Severo',
                'descricao' => 'Desencadeia vasoconstrição da mucosa do trato respiratório, aumentando a suscetibilidade a infecções e crises asmáticas.',
                'cor' => NivelAlerta::Critico->corHex(),
                'badge_class' => NivelAlerta::Critico->badgeClass(),
            ],
            $valor < 20.0 => [
                'nivel' => NivelAlerta::Atencao,
                'classe' => 'Frio Leve',
                'status' => 'Alerta de Atenção - Frio Leve',
                'descricao' => 'Início de desconforto térmico e perda de rendimento cognitivo.',
                'cor' => NivelAlerta::Atencao->corHex(),
                'badge_class' => NivelAlerta::Atencao->badgeClass(),
            ],
            $valor <= 24.0 => [
                'nivel' => NivelAlerta::Nenhum,
                'classe' => 'Equilíbrio',
                'status' => 'Sem Alerta',
                'descricao' => 'Faixa de equilíbrio e máximo desempenho cognitivo/ergonômico.',
                'cor' => NivelAlerta::Nenhum->corHex(),
                'badge_class' => NivelAlerta::Nenhum->badgeClass(),
            ],
            $valor <= 28.0 => [
                'nivel' => NivelAlerta::Atencao,
                'classe' => 'Calor Moderado',
                'status' => 'Alerta de Calor Moderado',
                'descricao' => 'Início de abafamento e elevação de transpiração.',
                'cor' => NivelAlerta::Atencao->corHex(),
                'badge_class' => NivelAlerta::Atencao->badgeClass(),
            ],
            $valor <= 35.0 => [
                'nivel' => NivelAlerta::Critico,
                'classe' => 'Estresse Térmico',
                'status' => 'Alerta Crítico - Estresse Térmico',
                'descricao' => 'Aumento da proliferação viral/bacteriana, redução do poder de concentração e risco de desidratação e estresse térmico.',
                'cor' => NivelAlerta::Critico->corHex(),
                'badge_class' => NivelAlerta::Critico->badgeClass(),
            ],
            default => [
                'nivel' => NivelAlerta::CriticoSevero,
                'classe' => 'Calor Extremo',
                'status' => 'Alerta Crítico Extremo',
                'descricao' => 'Risco de insolação e estresse térmico em vias públicas.',
                'cor' => NivelAlerta::CriticoSevero->corHex(),
                'badge_class' => NivelAlerta::CriticoSevero->badgeClass(),
            ],
        };
    }

    /**
     * Avalia a Umidade Relativa do Ar (%).
     *
     * @return array{nivel: NivelAlerta, classe: string, status: string, descricao: string, cor: string, badge_class: string}
     */
    public function avaliarUmidade(float|int $umidade): array
    {
        $valor = (float) $umidade;

        return match (true) {
            $valor < 20.0 => [
                'nivel' => NivelAlerta::CriticoSevero,
                'classe' => 'Emergência (Secura Severa)',
                'status' => 'Alerta Crítico Severo - Estado de Emergência',
                'descricao' => 'Risco grave de ressecamento severo das mucosas, sangramentos nasais e surtos respiratórios intensos.',
                'cor' => NivelAlerta::CriticoSevero->corHex(),
                'badge_class' => NivelAlerta::CriticoSevero->badgeClass(),
            ],
            $valor < 30.0 => [
                'nivel' => NivelAlerta::Critico,
                'classe' => 'Ar Seco Crítico',
                'status' => 'Alerta Crítico - Estado de Alerta de Ar Seco',
                'descricao' => 'Produz inflamação, obstrução e hiper-reatividade nas vias respiratórias superiores, além de elevar a poeira em suspensão.',
                'cor' => NivelAlerta::Critico->corHex(),
                'badge_class' => NivelAlerta::Critico->badgeClass(),
            ],
            $valor < 40.0 => [
                'nivel' => NivelAlerta::Atencao,
                'classe' => 'Ar Seco Moderado',
                'status' => 'Alerta de Atenção - Ar Seco Moderado',
                'descricao' => 'Sensação de ressecamento leve de mucosas e olhos.',
                'cor' => NivelAlerta::Atencao->corHex(),
                'badge_class' => NivelAlerta::Atencao->badgeClass(),
            ],
            default => [
                'nivel' => NivelAlerta::Nenhum,
                'classe' => 'Ideal / Confortável',
                'status' => 'Sem Alerta',
                'descricao' => 'Faixa considerada saudável e confortável pelas diretrizes de vigilância em saúde ambiental.',
                'cor' => NivelAlerta::Nenhum->corHex(),
                'badge_class' => NivelAlerta::Nenhum->badgeClass(),
            ],
        };
    }

    /**
     * Avalia o Material Particulado PM2.5 (µg/m³).
     *
     * @return array{nivel: NivelAlerta, classe: string, status: string, descricao: string, cor: string, badge_class: string}
     */
    public function avaliarPm(float|int $poeira): array
    {
        $valor = (float) $poeira;

        return match (true) {
            $valor <= 15.0 => [
                'nivel' => NivelAlerta::Nenhum,
                'classe' => 'Boa',
                'status' => 'Sem Alerta',
                'descricao' => 'Concentração de partículas inaláveis em nível seguro e sem restrições.',
                'cor' => NivelAlerta::Nenhum->corHex(),
                'badge_class' => NivelAlerta::Nenhum->badgeClass(),
            ],
            $valor <= 50.0 => [
                'nivel' => NivelAlerta::Atencao,
                'classe' => 'Moderada',
                'status' => 'Alerta de Atenção',
                'descricao' => 'Pessoas de grupos sensíveis devem evitar esforços físicos pesados ao ar livre.',
                'cor' => NivelAlerta::Atencao->corHex(),
                'badge_class' => NivelAlerta::Atencao->badgeClass(),
            ],
            $valor <= 75.0 => [
                'nivel' => NivelAlerta::Critico,
                'classe' => 'Ruim',
                'status' => 'Alerta Crítico',
                'descricao' => 'Insalubre. Toda a população pode começar a apresentar tosse seca, cansaço e ardência nos olhos.',
                'cor' => NivelAlerta::Critico->corHex(),
                'badge_class' => NivelAlerta::Critico->badgeClass(),
            ],
            $valor <= 125.0 => [
                'nivel' => NivelAlerta::CriticoSevero,
                'classe' => 'Muito Ruim',
                'status' => 'Alerta Crítico Severo',
                'descricao' => 'Muito insalubre. Agravamento dos sintomas de asma e doenças cardiorrespiratórias.',
                'cor' => NivelAlerta::CriticoSevero->corHex(),
                'badge_class' => NivelAlerta::CriticoSevero->badgeClass(),
            ],
            default => [
                'nivel' => NivelAlerta::Emergencia,
                'classe' => 'Péssima / Emergência',
                'status' => 'Alerta de Emergência',
                'descricao' => 'Perigoso. Toda a população está sob alto risco de efeitos respiratórios e cardiovasculares graves.',
                'cor' => NivelAlerta::Emergencia->corHex(),
                'badge_class' => NivelAlerta::Emergencia->badgeClass(),
            ],
        };
    }

    /**
     * Avalia a concentração de Dióxido de Carbono CO₂ (ppm).
     *
     * @return array{nivel: NivelAlerta, classe: string, status: string, descricao: string, cor: string, badge_class: string}
     */
    public function avaliarCo2(float|int $co2): array
    {
        $valor = (float) $co2;

        return match (true) {
            $valor <= 700.0 => [
                'nivel' => NivelAlerta::Nenhum,
                'classe' => 'Excelente',
                'status' => 'Sem Alerta',
                'descricao' => 'Nível ideal de renovação de ar e dispersão de gases.',
                'cor' => NivelAlerta::Nenhum->corHex(),
                'badge_class' => NivelAlerta::Nenhum->badgeClass(),
            ],
            $valor <= 1000.0 => [
                'nivel' => NivelAlerta::Atencao,
                'classe' => 'Atenção (Limite VMR)',
                'status' => 'Alerta de Atenção',
                'descricao' => 'Tolerável. Limite de Valor Máximo Recomendável da ANVISA (1000 ppm) para ar ambiente.',
                'cor' => NivelAlerta::Atencao->corHex(),
                'badge_class' => NivelAlerta::Atencao->badgeClass(),
            ],
            $valor <= 1500.0 => [
                'nivel' => NivelAlerta::Critico,
                'classe' => 'Crítico (Ventilação Inadequada)',
                'status' => 'Alerta Crítico',
                'descricao' => 'Insalubre. CO₂ indica ventilação inadequada, estagnação de ar e risco de sonolência e fadiga.',
                'cor' => NivelAlerta::Critico->corHex(),
                'badge_class' => NivelAlerta::Critico->badgeClass(),
            ],
            $valor <= 2500.0 => [
                'nivel' => NivelAlerta::CriticoSevero,
                'classe' => 'Crítico Severo',
                'status' => 'Alerta Crítico Severo',
                'descricao' => 'Muito insalubre. Ar altamente viciado com sonolência extrema, perda de concentração e cefaleia.',
                'cor' => NivelAlerta::CriticoSevero->corHex(),
                'badge_class' => NivelAlerta::CriticoSevero->badgeClass(),
            ],
            default => [
                'nivel' => NivelAlerta::Emergencia,
                'classe' => 'Emergência (Risco Agudo)',
                'status' => 'Alerta de Emergência',
                'descricao' => 'Concentração de CO₂ perigosa. Risco agudo de intoxicação e necessidade imediata de dispersão/evacuação.',
                'cor' => NivelAlerta::Emergencia->corHex(),
                'badge_class' => NivelAlerta::Emergencia->badgeClass(),
            ],
        };
    }

    /**
     * Avalia uma medição completa e retorna o sumário de alertas ativos.
     *
     * @return array{
     *     tem_alerta: bool,
     *     alerta_maximo: array{nivel: NivelAlerta, classe: string, status: string, descricao: string, cor: string, badge_class: string},
     *     alertas_ativos: array<string, array{nivel: NivelAlerta, parametro: string, status: string, descricao: string, valor: float|int, unidade: string, cor: string}>
     * }
     */
    public function avaliarMedicao(Medicao $medicao): array
    {
        $iqaValor = $medicao->iqa ?? Medicao::calcularIqa((float) $medicao->poeira, (int) $medicao->co2);

        $avaliacaoIqa = $this->avaliarIqa($iqaValor);
        $avaliacaoTemp = $this->avaliarTemperatura((float) $medicao->temperatura);
        $avaliacaoUmid = $this->avaliarUmidade((float) $medicao->umidade);
        $avaliacaoPm = $this->avaliarPm((float) $medicao->poeira);
        $avaliacaoCo2 = $this->avaliarCo2((int) $medicao->co2);

        $candidatos = [
            'iqa' => array_merge($avaliacaoIqa, ['parametro' => 'Qualidade do Ar (IQA)', 'valor' => $iqaValor, 'unidade' => 'IQA']),
            'temperatura' => array_merge($avaliacaoTemp, ['parametro' => 'Temperatura', 'valor' => (float) $medicao->temperatura, 'unidade' => '°C']),
            'umidade' => array_merge($avaliacaoUmid, ['parametro' => 'Umidade Relativa', 'valor' => (float) $medicao->umidade, 'unidade' => '%']),
            'poeira' => array_merge($avaliacaoPm, ['parametro' => 'Material Particulado', 'valor' => (float) $medicao->poeira, 'unidade' => 'µg/m³']),
            'co2' => array_merge($avaliacaoCo2, ['parametro' => 'Dióxido de Carbono (CO₂)', 'valor' => (int) $medicao->co2, 'unidade' => 'ppm']),
        ];

        $alertasAtivos = [];
        $alertaMaximo = $avaliacaoIqa;
        $maiorPeso = NivelAlerta::Nenhum->peso();

        foreach ($candidatos as $chave => $item) {
            /** @var NivelAlerta $nivel */
            $nivel = $item['nivel'];
            if ($nivel !== NivelAlerta::Nenhum) {
                $alertasAtivos[$chave] = $item;
            }
            if ($nivel->peso() > $maiorPeso) {
                $maiorPeso = $nivel->peso();
                $alertaMaximo = $item;
            }
        }

        return [
            'tem_alerta' => ! empty($alertasAtivos),
            'alerta_maximo' => $alertaMaximo,
            'alertas_ativos' => $alertasAtivos,
        ];
    }

    /**
     * Retorna a lista de bandas/faixas de corte para o Chart.js desenhar a demarcação das áreas no gráfico.
     *
     * @return array<int, array{min: float, max: float|null, label: string, nivel: string, cor: string, bg_cor: string}>
     */
    public function obterFaixasGrafico(string $metrica): array
    {
        return match ($metrica) {
            'temperatura' => [
                ['min' => -30.0, 'max' => 0.0, 'label' => 'Congelamento/Geada', 'nivel' => 'emergencia', 'cor' => '#9333ea', 'bg_cor' => 'rgba(147, 51, 234, 0.12)'],
                ['min' => 0.0, 'max' => 10.0, 'label' => 'Frio Intenso', 'nivel' => 'critico_severo', 'cor' => '#ef4444', 'bg_cor' => 'rgba(239, 68, 68, 0.10)'],
                ['min' => 10.0, 'max' => 18.0, 'label' => 'Frio Severo', 'nivel' => 'critico', 'cor' => '#f97316', 'bg_cor' => 'rgba(249, 115, 22, 0.08)'],
                ['min' => 18.0, 'max' => 20.0, 'label' => 'Frio Leve', 'nivel' => 'atencao', 'cor' => '#f59e0b', 'bg_cor' => 'rgba(245, 158, 11, 0.08)'],
                ['min' => 20.0, 'max' => 24.0, 'label' => 'Equilíbrio (Segura)', 'nivel' => 'nenhum', 'cor' => '#10b981', 'bg_cor' => 'rgba(16, 185, 129, 0.08)'],
                ['min' => 24.0, 'max' => 28.0, 'label' => 'Calor Moderado', 'nivel' => 'atencao', 'cor' => '#f59e0b', 'bg_cor' => 'rgba(245, 158, 11, 0.08)'],
                ['min' => 28.0, 'max' => 35.0, 'label' => 'Estresse Térmico', 'nivel' => 'critico', 'cor' => '#f97316', 'bg_cor' => 'rgba(249, 115, 22, 0.08)'],
                ['min' => 35.0, 'max' => 60.0, 'label' => 'Calor Extremo', 'nivel' => 'critico_severo', 'cor' => '#ef4444', 'bg_cor' => 'rgba(239, 68, 68, 0.12)'],
            ],
            'umidade' => [
                ['min' => 0.0, 'max' => 20.0, 'label' => 'Emergência (Ar Seco)', 'nivel' => 'critico_severo', 'cor' => '#ef4444', 'bg_cor' => 'rgba(239, 68, 68, 0.12)'],
                ['min' => 20.0, 'max' => 30.0, 'label' => 'Alerta Ar Seco', 'nivel' => 'critico', 'cor' => '#f97316', 'bg_cor' => 'rgba(249, 115, 22, 0.08)'],
                ['min' => 30.0, 'max' => 40.0, 'label' => 'Ar Seco Moderado', 'nivel' => 'atencao', 'cor' => '#f59e0b', 'bg_cor' => 'rgba(245, 158, 11, 0.08)'],
                ['min' => 40.0, 'max' => 100.0, 'label' => 'Ideal / Confortável', 'nivel' => 'nenhum', 'cor' => '#10b981', 'bg_cor' => 'rgba(16, 185, 129, 0.08)'],
            ],
            'poeira' => [
                ['min' => 0.0, 'max' => 15.0, 'label' => 'Boa (Sem Alerta)', 'nivel' => 'nenhum', 'cor' => '#10b981', 'bg_cor' => 'rgba(16, 185, 129, 0.08)'],
                ['min' => 15.0, 'max' => 50.0, 'label' => 'Atenção (Moderada)', 'nivel' => 'atencao', 'cor' => '#f59e0b', 'bg_cor' => 'rgba(245, 158, 11, 0.08)'],
                ['min' => 50.0, 'max' => 75.0, 'label' => 'Crítico (Ruim)', 'nivel' => 'critico', 'cor' => '#f97316', 'bg_cor' => 'rgba(249, 115, 22, 0.08)'],
                ['min' => 75.0, 'max' => 125.0, 'label' => 'Crítico Severo', 'nivel' => 'critico_severo', 'cor' => '#ef4444', 'bg_cor' => 'rgba(239, 68, 68, 0.10)'],
                ['min' => 125.0, 'max' => 300.0, 'label' => 'Emergência (Péssima)', 'nivel' => 'emergencia', 'cor' => '#9333ea', 'bg_cor' => 'rgba(147, 51, 234, 0.12)'],
            ],
            'co2' => [
                ['min' => 0.0, 'max' => 700.0, 'label' => 'Excelente', 'nivel' => 'nenhum', 'cor' => '#10b981', 'bg_cor' => 'rgba(16, 185, 129, 0.08)'],
                ['min' => 700.0, 'max' => 1000.0, 'label' => 'Atenção (Limite VMR)', 'nivel' => 'atencao', 'cor' => '#f59e0b', 'bg_cor' => 'rgba(245, 158, 11, 0.08)'],
                ['min' => 1000.0, 'max' => 1500.0, 'label' => 'Crítico (Ventilação)', 'nivel' => 'critico', 'cor' => '#f97316', 'bg_cor' => 'rgba(249, 115, 22, 0.08)'],
                ['min' => 1500.0, 'max' => 2500.0, 'label' => 'Crítico Severo', 'nivel' => 'critico_severo', 'cor' => '#ef4444', 'bg_cor' => 'rgba(239, 68, 68, 0.10)'],
                ['min' => 2500.0, 'max' => 5000.0, 'label' => 'Emergência (Agudo)', 'nivel' => 'emergencia', 'cor' => '#9333ea', 'bg_cor' => 'rgba(147, 51, 234, 0.12)'],
            ],
            default => [
                ['min' => 0.0, 'max' => 40.0, 'label' => 'Boa (Sem Alerta)', 'nivel' => 'nenhum', 'cor' => '#10b981', 'bg_cor' => 'rgba(16, 185, 129, 0.08)'],
                ['min' => 40.0, 'max' => 80.0, 'label' => 'Atenção (Moderada)', 'nivel' => 'atencao', 'cor' => '#f59e0b', 'bg_cor' => 'rgba(245, 158, 11, 0.08)'],
                ['min' => 80.0, 'max' => 120.0, 'label' => 'Crítico (Ruim)', 'nivel' => 'critico', 'cor' => '#f97316', 'bg_cor' => 'rgba(249, 115, 22, 0.08)'],
                ['min' => 120.0, 'max' => 200.0, 'label' => 'Crítico Severo', 'nivel' => 'critico_severo', 'cor' => '#ef4444', 'bg_cor' => 'rgba(239, 68, 68, 0.10)'],
                ['min' => 200.0, 'max' => 400.0, 'label' => 'Emergência (Péssima)', 'nivel' => 'emergencia', 'cor' => '#9333ea', 'bg_cor' => 'rgba(147, 51, 234, 0.12)'],
            ],
        };
    }
}
