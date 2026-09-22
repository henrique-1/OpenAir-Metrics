<?php

namespace Database\Factories;

use App\Models\Patrimonio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patrimonio>
 */
class PatrimonioFactory extends Factory
{
    protected $model = Patrimonio::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => fake()->uuid(),
            'mac_address' => strtoupper(fake()->unique()->macAddress()),
            'numero_patrimonio' => 'PAT-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => 'Disponível',
            'data_aquisicao' => fake()->dateTimeBetween('-6 months', 'now'),
            'observacoes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function instalada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Instalada',
        ]);
    }

    public function descartado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Descartado',
        ]);
    }
}
