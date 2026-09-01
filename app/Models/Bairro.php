<?php

namespace App\Models;

use Database\Factories\BairroFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bairro extends Model
{
    /** @use HasFactory<BairroFactory> */
    use HasFactory;

    protected $fillable = [
        'cidade_id',
        'nome',
    ];

    /**
     * Relacionamento: O bairro pertence a uma cidade.
     */
    public function cidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class);
    }

    /**
     * Relacionamento: O bairro possui várias estações de monitoramento.
     */
    public function estacoes(): HasMany
    {
        return $this->hasMany(Estacao::class, 'bairro_id', 'id');
    }
}
