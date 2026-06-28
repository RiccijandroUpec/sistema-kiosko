<?php

namespace Database\Factories;

use App\Models\TransaccionPago;
use App\Models\OrdenImpresion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TransaccionPago>
 */
class TransaccionPagoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'orden_id' => OrdenImpresion::factory(),
            'monto' => 0.25,
            'metodo' => 'Deuna',
            'estado' => 'pendiente',
        ];
    }
}
