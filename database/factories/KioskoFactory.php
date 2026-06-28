<?php

namespace Database\Factories;

use App\Models\Kiosko;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kiosko>
 */
class KioskoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre_comercial' => 'Kiosko ' . fake()->city(),
            'estado' => 'activo',
            'precio_blanco_negro' => 0.05,
            'precio_color' => 0.20,
            'nombre_cups' => 'Microsoft Print to PDF',
            'pin' => fake()->numerify('####'),
            'ultima_conexion' => now(),
        ];
    }
}
