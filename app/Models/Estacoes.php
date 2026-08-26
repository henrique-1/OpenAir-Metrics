<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Estacao extends Model
{
    use HasFactory;

    /**
     * O nome da tabela associada à model.
     * Necessário pois o Laravel pluralizaria automaticamente para 'estacaos' (em inglês).
     */
    protected $table = 'estacoes';

    /**
     * A chave primária associada à tabela.
     */
    protected $primaryKey = 'private_id';

    /**
     * Os atributos que são designáveis em massa (Mass Assignment).
     */
    protected $fillable = [
        'public_id',
        'coordenadas',
        'created_by',
    ];

    /**
     * O método "booted" do modelo.
     * Utilizado aqui para injetar um UUID automaticamente antes de criar o registro no banco.
     */
    protected static function booted(): void
    {
        static::creating(function (Estacao $estacao) {
            if (empty($estacao->public_id)) {
                $estacao->public_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Relacionamento: A estação pertence a um usuário (criador).
     */
    public function criador(): BelongsTo
    {
        // O segundo parâmetro informa explicitamente qual é a chave estrangeira na tabela 'estacoes'
        return $this->belongsTo(User::class, 'created_by');
    }
}
