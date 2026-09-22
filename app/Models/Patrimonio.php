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

    public const STATUS_DISPONIVEL = 'Disponível';

    public const STATUS_INSTALADA = 'Instalada';

    public const STATUS_DESCARTADO = 'Descartado';

    public const STATUSES = [
        self::STATUS_DISPONIVEL,
        self::STATUS_INSTALADA,
        self::STATUS_DESCARTADO,
    ];

    /**
     * Os atributos que são designáveis em massa.
     */
    protected $fillable = [
        'public_id',
        'cidade_id',
        'mac_address',
        'numero_patrimonio',
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

            if (empty($patrimonio->numero_patrimonio) && ! empty($patrimonio->cidade_id)) {
                $patrimonio->numero_patrimonio = static::gerarProximoCodigo((int) $patrimonio->cidade_id);
            }
        });

        static::updating(function (Patrimonio $patrimonio) {
            if (! empty($patrimonio->mac_address)) {
                $patrimonio->mac_address = strtoupper(trim($patrimonio->mac_address));
            }
        });
    }

    /**
     * Gera o próximo código de patrimônio sequencial no formato OAir-Estacao-<IdCidade>-<Num>.
     */
    public static function gerarProximoCodigo(int $cidadeId): string
    {
        $prefix = "OAir-Estacao-{$cidadeId}-";
        $latest = static::where('cidade_id', $cidadeId)
            ->where('numero_patrimonio', 'like', "{$prefix}%")
            ->orderByRaw('LENGTH(numero_patrimonio) DESC, numero_patrimonio DESC')
            ->first();

        $nextNum = 1;
        if ($latest && preg_match('/^'.preg_quote($prefix, '/').'(\d+)$/', $latest->numero_patrimonio, $matches)) {
            $nextNum = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%04d', $prefix, $nextNum);
    }

    /**
     * Relacionamento: Cidade do patrimônio.
     */
    public function cidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class, 'cidade_id');
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
