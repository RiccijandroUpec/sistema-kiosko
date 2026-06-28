<?php

namespace Database\Factories;

use App\Models\OrdenImpresion;
use App\Models\Kiosko;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrdenImpresion>
 */
class OrdenImpresionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kiosko_id' => Kiosko::factory(),
            'cliente_id' => Cliente::factory(),
            'archivo_url' => 'http://127.0.0.1:8000/storage/pdfs/' . fake()->uuid() . '.pdf',
            'paginas' => 5,
            'copias' => 1,
            'rango_paginas' => null,
            'papel' => 'a4',
            'orientacion' => 'portrait',
            'color' => false,
            'costo_total' => 0.25,
            'estado' => 'pendiente',
        ];
    }
}
