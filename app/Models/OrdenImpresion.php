<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrdenImpresion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'ordenes_impresion';

    protected $fillable = [
        'kiosko_id',
        'cliente_id',
        'archivo_url',
        'paginas',
        'copias',
        'rango_paginas',
        'papel',
        'orientacion',
        'color',
        'costo_total',
        'estado',
    ];

    protected $casts = [
        'color' => 'boolean',
        'costo_total' => 'decimal:2',
    ];

    public function kiosko()
    {
        return $this->belongsTo(Kiosko::class, 'kiosko_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function transacciones()
    {
        return $this->hasMany(TransaccionPago::class, 'orden_id');
    }
}
