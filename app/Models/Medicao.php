<?php

namespace App\Models;

use Database\Factories\MedicaoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Medicao extends Model
{
    /** @use HasFactory<MedicaoFactory> */
    use HasFactory;

    /**
     * O nome da tabela associada à model.
     */
    protected $table = 'medicoes';

    /**
     * A chave primária associada à tabela.
     */
    protected $primaryKey = 'private_id';

    /**
     * Os atributos que são designáveis em massa.
     */
    protected $fillable = [
        'public_id',
        'estacao_id',
        'temperatura',
        'umidade',
        'co2',
        'poeira',
        'data_hora',
    ];

    /**
     * As conversões de tipo nativas dos atributos.
     */
    protected $casts = [
        'temperatura' => 'float',
        'umidade' => 'float',
        'co2' => 'integer',
        'poeira' => 'float',
        'data_hora' => 'datetime',
    ];

    /**
     * O método "booted" do modelo para geração automática de UUIDv4.
     */
    protected static function booted(): void
    {
        static::creating(function (Medicao $medicao) {
            if (empty($medicao->public_id)) {
                $medicao->public_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Estação à qual esta medição pertence.
     */
    public function estacao(): BelongsTo
    {
        return $this->belongsTo(Estacao::class, 'estacao_id', 'private_id');
    }

    /**
     * Accessor para o Índice de Qualidade do Ar (IQA).
     */
    public function getIqaAttribute(): int
    {
        return self::calcularIqa((float) $this->poeira, (int) $this->co2);
    }

    /**
     * Helper estático para cálculo do IQA / AQI com base em Material Particulado e CO₂.
     * Escala: 0 a 50 (Boa), 51 a 100 (Moderada), 101 a 150 (Ruim), 151 a 200 (Muito Ruim), > 200 (Péssima).
     */
    public static function calcularIqa(float $poeira, int $co2): int
    {
        // Normalização baseada em faixas de PM2.5 (poeira em µg/m³)
        if ($poeira <= 12.0) {
            $aqiPm = ($poeira / 12.0) * 50;
        } elseif ($poeira <= 35.4) {
            $aqiPm = 50 + (($poeira - 12.0) / (35.4 - 12.0)) * 50;
        } elseif ($poeira <= 55.4) {
            $aqiPm = 100 + (($poeira - 35.4) / (55.4 - 35.4)) * 50;
        } elseif ($poeira <= 150.4) {
            $aqiPm = 150 + (($poeira - 55.4) / (150.4 - 55.4)) * 50;
        } else {
            $aqiPm = 200 + min(100, (($poeira - 150.4) / 100.0) * 100);
        }

        // Penalidade por excesso de CO2 (> 1000 ppm)
        $penalidadeCo2 = 0;
        if ($co2 > 1000) {
            $penalidadeCo2 = min(50, ($co2 - 1000) / 40);
        }

        return (int) round($aqiPm + $penalidadeCo2);
    }
}
