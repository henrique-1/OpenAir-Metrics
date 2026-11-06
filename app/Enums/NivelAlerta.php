<?php

namespace App\Enums;

enum NivelAlerta: string
{
    case Nenhum = 'nenhum';
    case Atencao = 'atencao';
    case Critico = 'critico';
    case CriticoSevero = 'critico_severo';
    case Emergencia = 'emergencia';

    /**
     * Retorna o rótulo amigável em português.
     */
    public function rotulo(): string
    {
        return match ($this) {
            self::Nenhum => 'Sem Alerta',
            self::Atencao => 'Alerta de Atenção',
            self::Critico => 'Alerta Crítico',
            self::CriticoSevero => 'Alerta Crítico Severo',
            self::Emergencia => 'Alerta de Emergência',
        };
    }

    /**
     * Retorna o peso numérico da criticidade para ordenação e comparações.
     */
    public function peso(): int
    {
        return match ($this) {
            self::Nenhum => 0,
            self::Atencao => 1,
            self::Critico => 2,
            self::CriticoSevero => 3,
            self::Emergencia => 4,
        };
    }

    /**
     * Retorna a cor hexadecimal associada.
     */
    public function corHex(): string
    {
        return match ($this) {
            self::Nenhum => '#10b981',
            self::Atencao => '#f59e0b',
            self::Critico => '#f97316',
            self::CriticoSevero => '#ef4444',
            self::Emergencia => '#9333ea',
        };
    }

    /**
     * Retorna as classes Tailwind CSS para o badge de status.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Nenhum => 'text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 border-emerald-200 dark:border-emerald-800',
            self::Atencao => 'text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 border-amber-200 dark:border-amber-800',
            self::Critico => 'text-orange-700 dark:text-orange-400 bg-orange-50 dark:bg-orange-950/50 border-orange-200 dark:border-orange-800',
            self::CriticoSevero => 'text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-950/50 border-red-200 dark:border-red-800',
            self::Emergencia => 'text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/50 border-purple-200 dark:border-purple-800',
        };
    }
}
