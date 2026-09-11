<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'nivel',
    'ativo',
    'cidade_id',
    'logradouro',
    'numero',
    'complemento',
    'bairro',
    'estado',
    'cep',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
        ];
    }

    /**
     * Relacionamento: Cidade de atuação do usuário/administrador.
     */
    public function cidade(): BelongsTo
    {
        return $this->belongsTo(Cidade::class, 'cidade_id');
    }

    /**
     * Relacionamento: O usuário possui várias estações de monitoramento.
     */
    public function estacoes(): HasMany
    {
        return $this->hasMany(Estacao::class, 'created_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->nivel === 'superadmin' || $this->nivel === 'super_administrador';
    }

    public function isAdministrador(): bool
    {
        return $this->nivel === 'administrador';
    }

    public function isCadastrador(): bool
    {
        return $this->nivel === 'cadastrador' || $this->nivel === 'planejador';
    }

    public function isPlanejador(): bool
    {
        return $this->nivel === 'cadastrador' || $this->nivel === 'planejador';
    }

    public function isPlanejadorTecnico(): bool
    {
        return $this->isPlanejador();
    }

    public function isInstalador(): bool
    {
        return $this->nivel === 'instalador';
    }

    public function getNivelLabelAttribute(): string
    {
        return match ($this->nivel) {
            'superadmin', 'super_administrador' => 'Super-usuário',
            'administrador' => 'Administrador',
            'cadastrador', 'planejador' => 'Planejador Técnico',
            'instalador' => 'Instalador',
            default => ucfirst($this->nivel ?? 'Usuário'),
        };
    }
}
