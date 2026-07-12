<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Kiosko extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'kioskos';

    protected $fillable = [
        'nombre_comercial',
        'slug',
        'estado',
        'precio_blanco_negro',
        'precio_color',
        'nombre_cups',
        'color_tema',
        'logo_url',
        'pin',
        'api_token',
        'ultima_conexion',
    ];

    protected $hidden = [
        'api_token',
    ];

    protected $casts = [
        'precio_blanco_negro' => 'decimal:2',
        'precio_color' => 'decimal:2',
        'ultima_conexion' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Kiosko $kiosko) {
            if (empty($kiosko->slug) && !empty($kiosko->nombre_comercial)) {
                $kiosko->slug = static::generateUniqueSlug($kiosko->nombre_comercial);
            }

            if (empty($kiosko->api_token)) {
                $kiosko->api_token = Str::random(48);
            }
        });
    }

    public function regenerateApiToken(): string
    {
        $token = Str::random(48);
        $this->update(['api_token' => $token]);

        return $token;
    }

    protected static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-" . ++$i;
        }

        return $slug;
    }

    public function ordenes()
    {
        return $this->hasMany(OrdenImpresion::class, 'kiosko_id');
    }
}
