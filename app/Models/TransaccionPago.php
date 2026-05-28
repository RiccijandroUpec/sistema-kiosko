<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TransaccionPago extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'transacciones_pago';

    protected $fillable = [
        'orden_id',
        'monto',
        'metodo',
        'referencia_externa',
        'estado',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenImpresion::class, 'orden_id');
    }
}
