<?php

namespace App\Models;

use Database\Factories\CidadeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cidade extends Model
{
    /** @use HasFactory<CidadeFactory> */
    use HasFactory;

    protected $fillable = [
        'estado_id',
        'nome',
    ];

    /**
     * Relacionamento: A cidade pertence a um estado.
     */
    public function estado(): BelongsTo
    {
        return $this->belongsTo(Estado::class);
    }

    /**
     * Relacionamento: A cidade possui vários bairros.
     */
    public function bairros(): HasMany
    {
        return $this->hasMany(Bairro::class);
    }
}
