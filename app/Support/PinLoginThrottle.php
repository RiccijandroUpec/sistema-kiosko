<?php

namespace App\Support;

use App\Services\EvolutionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Bloqueo progresivo para los logins por PIN de 4 digitos (admin y kiosko).
 * El throttle:N,1 de la ruta solo limita rafagas por minuto; esto ademas
 * bloquea la IP por una ventana mas larga tras varios fallos y avisa al
 * administrador, para que un PIN de 4 digitos no sea fuerza-bruteable
 * simplemente espaciando los intentos.
 */
class PinLoginThrottle
{
    protected const MAX_ATTEMPTS = 8;
    protected const DECAY_MINUTES = 15;

    public static function tooManyAttempts(string $key): bool
    {
        return (int) Cache::get(self::cacheKey($key), 0) >= self::MAX_ATTEMPTS;
    }

    public static function hit(string $key, string $label, EvolutionService $evolutionService): void
    {
        $count = (int) Cache::get(self::cacheKey($key), 0) + 1;
        Cache::put(self::cacheKey($key), $count, now()->addMinutes(self::DECAY_MINUTES));

        if ($count === self::MAX_ATTEMPTS) {
            Log::warning("Bloqueo temporal por intentos fallidos de PIN ({$label})", ['key' => $key]);

            $adminPhone = env('ADMIN_PHONE');
            if ($adminPhone) {
                $evolutionService->sendMessage(
                    $adminPhone,
                    "🚨 *ALERTA DE SEGURIDAD*\nSe bloqueó temporalmente un login por PIN ({$label}) tras {$count} intentos fallidos seguidos.\nOrigen: {$key}"
                );
            }
        }
    }

    public static function clear(string $key): void
    {
        Cache::forget(self::cacheKey($key));
    }

    protected static function cacheKey(string $key): string
    {
        return 'pin_login_attempts:' . $key;
    }
}
