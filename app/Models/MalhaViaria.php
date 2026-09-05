<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MalhaViaria extends Model
{
    use HasFactory;

    protected $table = 'malha_viaria';

    public $timestamps = false;

    protected $fillable = [
        'logradouro',
        'tipo_via',
        'geometria',
    ];
}
