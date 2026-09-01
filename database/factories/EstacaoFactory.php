<?php

namespace Database\Factories;

use App\Models\Bairro;
use App\Models\Estacao;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Estacao>
 */
class EstacaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'mac_address' => fake()->unique()->macAddress(),
            'tipo_estacao' => 'Estação Matriz',
            'bairro_id' => Bairro::factory(),
            'created_by' => User::factory(),
            'latitude' => fake()->latitude(-23.6, -23.4),
            'longitude' => fake()->longitude(-46.7, -46.5),
        ];
    }

    /**
     * Estado para Estação Matriz.
     */
    public function matriz(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_estacao' => 'Estação Matriz',
        ]);
    }

    /**
     * Estado para Estação Satélite.
     */
    public function satelite(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_estacao' => 'Estação Satélite',
        ]);
    }
}
