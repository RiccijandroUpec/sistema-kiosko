<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Kiosko extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'kioskos';

    protected $fillable = [
        'nombre_comercial',
        'estado',
        'precio_blanco_negro',
        'precio_color',
        'nombre_cups',
        'color_tema',
        'logo_url',
        'pin',
        'ultima_conexion',
    ];

    protected $casts = [
        'precio_blanco_negro' => 'decimal:2',
        'precio_color' => 'decimal:2',
        'ultima_conexion' => 'datetime',
    ];

    public function ordenes()
    {
        return $this->hasMany(OrdenImpresion::class, 'kiosko_id');
    }
}
