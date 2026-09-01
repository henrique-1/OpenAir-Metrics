<?php

namespace App\Models;

use Database\Factories\EstadoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estado extends Model
{
    /** @use HasFactory<EstadoFactory> */
    use HasFactory;

    protected $fillable = [
        'nome',
        'uf',
    ];

    /**
     * Relacionamento: O estado possui várias cidades.
     */
    public function cidades(): HasMany
    {
        return $this->hasMany(Cidade::class);
    }
}
