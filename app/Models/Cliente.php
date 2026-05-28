<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Cliente extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'clientes';

    protected $fillable = [
        'telefono',
        'cedula',
        'nombre',
    ];

    public function ordenes()
    {
        return $this->hasMany(OrdenImpresion::class, 'cliente_id');
    }
}
