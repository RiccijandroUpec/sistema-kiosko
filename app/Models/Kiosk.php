<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kiosk extends Model
{
    protected $fillable = [
        'nombre',
        'ubicacion',
        'api_token',
        'access_pin',
        'estado_conexion',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
