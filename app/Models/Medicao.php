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
        'iqa',
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
        'iqa' => 'integer',
        'data_hora' => 'datetime',
    ];

    /**
     * O método "booted" do modelo para geração automática de UUIDv4, data_hora e cálculo de IQA.
     */
    protected static function booted(): void
    {
        static::creating(function (Medicao $medicao) {
            if (empty($medicao->public_id)) {
                $medicao->public_id = (string) Str::uuid();
            }

            if (empty($medicao->data_hora)) {
                $medicao->data_hora = now();
            }

            if (! isset($medicao->attributes['iqa']) && $medicao->poeira !== null && $medicao->co2 !== null) {
                $medicao->attributes['iqa'] = self::calcularIqa((float) $medicao->poeira, (int) $medicao->co2);
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
    public function getIqaAttribute(?int $value): int
    {
        if ($value !== null) {
            return $value;
        }

        return self::calcularIqa((float) $this->poeira, (int) $this->co2);
    }

    /**
     * Cálculo do IQA pela fórmula de Interpolação Linear para Material Particulado (PM2.5) e Dióxido de Carbono (CO₂):
     * IQAr = Iini + [ (Ifin - Iini) / (Cfin - Cini) ] * (C - Cini)
     *
     * Faixas de corte CONAMA 506/2024 (PM2.5) e ANVISA RE nº 09/2003 (CO₂):
     * - N1 - Boa:        I = 0 a 40   | PM2.5 = 0 a 15.0       | CO2 = 0 a 700
     * - N2 - Moderada:   I = 41 a 80  | PM2.5 = >15.0 a 50.0   | CO2 = >700 a 1000
     * - N3 - Ruim:       I = 81 a 120 | PM2.5 = >50.0 a 75.0   | CO2 = >1000 a 1500
     * - N4 - Muito Ruim: I = 121 a 200| PM2.5 = >75.0 a 125.0  | CO2 = >1500 a 2500
     * - N5 - Péssima:    I = 201 a 400| PM2.5 = >125.0 a 300.0 | CO2 = >2500 a 5000
     */
    public static function calcularIqa(float $poeira, int $co2): int
    {
        $iqaPm = self::calcularIqaPoluente($poeira, [
            ['c_inf' => 0.0, 'c_sup' => 15.0, 'i_inf' => 0, 'i_sup' => 40],
            ['c_inf' => 15.0, 'c_sup' => 50.0, 'i_inf' => 41, 'i_sup' => 80],
            ['c_inf' => 50.0, 'c_sup' => 75.0, 'i_inf' => 81, 'i_sup' => 120],
            ['c_inf' => 75.0, 'c_sup' => 125.0, 'i_inf' => 121, 'i_sup' => 200],
            ['c_inf' => 125.0, 'c_sup' => 300.0, 'i_inf' => 201, 'i_sup' => 400],
        ]);

        $iqaCo2 = self::calcularIqaPoluente($co2, [
            ['c_inf' => 0.0, 'c_sup' => 700.0, 'i_inf' => 0, 'i_sup' => 40],
            ['c_inf' => 700.0, 'c_sup' => 1000.0, 'i_inf' => 41, 'i_sup' => 80],
            ['c_inf' => 1000.0, 'c_sup' => 1500.0, 'i_inf' => 81, 'i_sup' => 120],
            ['c_inf' => 1500.0, 'c_sup' => 2500.0, 'i_inf' => 121, 'i_sup' => 200],
            ['c_inf' => 2500.0, 'c_sup' => 5000.0, 'i_inf' => 201, 'i_sup' => 400],
        ]);

        return (int) round(max($iqaPm, $iqaCo2));
    }

    /**
     * Aplica a interpolação linear do poluente para uma dada concentração e tabela de faixas.
     *
     * @param  array<int, array{c_inf: float, c_sup: float, i_inf: int, i_sup: int}>  $faixas
     */
    public static function calcularIqaPoluente(float $concentracao, array $faixas): float
    {
        if ($concentracao <= 0.0) {
            return 0.0;
        }

        foreach ($faixas as $faixa) {
            if ($concentracao <= $faixa['c_sup']) {
                $cInf = $faixa['c_inf'];
                $cSup = $faixa['c_sup'];
                $iInf = $faixa['i_inf'];
                $iSup = $faixa['i_sup'];

                return $iInf + (($iSup - $iInf) / ($cSup - $cInf)) * ($concentracao - $cInf);
            }
        }

        $ultima = end($faixas);

        return min(500.0, (float) $ultima['i_sup']);
    }
}
