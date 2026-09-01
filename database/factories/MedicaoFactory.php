<?php

namespace Database\Factories;

use App\Models\Estacao;
use App\Models\Medicao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medicao>
 */
class MedicaoFactory extends Factory
{
    protected $model = Medicao::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'estacao_id' => Estacao::factory(),
            'temperatura' => fake()->randomFloat(1, 18, 35),
            'umidade' => fake()->randomFloat(1, 35, 85),
            'co2' => fake()->numberBetween(400, 1100),
            'poeira' => fake()->randomFloat(2, 5, 75),
            'data_hora' => fake()->dateTimeBetween('-7 days', 'now'),
        ];
    }
}
