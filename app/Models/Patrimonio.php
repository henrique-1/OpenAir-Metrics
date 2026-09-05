<?php

namespace App\Models;

use Database\Factories\PatrimonioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Patrimonio extends Model
{
    /** @use HasFactory<PatrimonioFactory> */
    use HasFactory;

    /**
     * O nome da tabela associada ao modelo.
     */
    protected $table = 'patrimonios';

    /**
     * A chave primária associada à tabela.
     */
    protected $primaryKey = 'private_id';

    /**
     * Os atributos que são designáveis em massa.
     */
    protected $fillable = [
        'public_id',
        'mac_address',
        'numero_patrimonio',
        'tipo_sugerido',
        'status',
        'data_aquisicao',
        'observacoes',
        'created_by',
    ];

    /**
     * Os atributos que devem ser convertidos.
     */
    protected $casts = [
        'data_aquisicao' => 'date',
    ];

    /**
     * O método "booted" do modelo para geração automática de UUIDv4.
     */
    protected static function booted(): void
    {
        static::creating(function (Patrimonio $patrimonio) {
            if (empty($patrimonio->public_id)) {
                $patrimonio->public_id = (string) Str::uuid();
            }

            if (! empty($patrimonio->mac_address)) {
                $patrimonio->mac_address = strtoupper(trim($patrimonio->mac_address));
            }
        });

        static::updating(function (Patrimonio $patrimonio) {
            if (! empty($patrimonio->mac_address)) {
                $patrimonio->mac_address = strtoupper(trim($patrimonio->mac_address));
            }
        });
    }

    /**
     * Relacionamento: Usuário criador do patrimônio.
     */
    public function criador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relacionamento: Estação vinculada a este patrimônio.
     */
    public function estacao(): HasOne
    {
        return $this->hasOne(Estacao::class, 'patrimonio_id', 'private_id');
    }
}
