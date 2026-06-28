<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Kiosko;
use App\Models\TransaccionPago;
use App\Services\EvolutionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckSystemHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-system-health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisa kioskos desconectados y pagos atascados sin verificar, y avisa al administrador';

    /**
     * Execute the console command.
     */
    public function handle(EvolutionService $evolutionService)
    {
        $adminPhone = env('ADMIN_PHONE');

        $offline = $this->checkOfflineKioskos($evolutionService, $adminPhone);
        $atascados = $this->checkStuckPayments($evolutionService, $adminPhone);

        $this->info("Kioskos marcados offline: {$offline}. Pagos atascados detectados: {$atascados}.");

        return Command::SUCCESS;
    }

    /**
     * Marca como offline (y avisa una sola vez) a los kioskos activos que no
     * mandan heartbeat hace más de 5 minutos.
     */
    protected function checkOfflineKioskos(EvolutionService $evolutionService, ?string $adminPhone): int
    {
        $kioskosCaidos = Kiosko::where('estado', 'activo')
            ->whereNotNull('ultima_conexion')
            ->where('ultima_conexion', '<', now()->subMinutes(5))
            ->get();

        foreach ($kioskosCaidos as $kiosko) {
            $kiosko->update(['estado' => 'offline']);

            Log::warning("Kiosko desconectado: {$kiosko->nombre_comercial} ({$kiosko->id})");

            if ($adminPhone) {
                $evolutionService->sendMessage(
                    $adminPhone,
                    "🔴 *KIOSKO DESCONECTADO*\n{$kiosko->nombre_comercial} no responde desde {$kiosko->ultima_conexion->diffForHumans()}.\nRevisa que el kiosk-agent siga corriendo ahí."
                );
            }
        }

        return $kioskosCaidos->count();
    }

    /**
     * Avisa (una sola vez por pago) cuando un cliente ya puso su referencia
     * pero la verificación automática lleva 25+ minutos sin confirmarlo.
     */
    protected function checkStuckPayments(EvolutionService $evolutionService, ?string $adminPhone): int
    {
        $atascados = TransaccionPago::where('estado', 'pendiente')
            ->whereNotNull('referencia_usuario')
            ->where('updated_at', '<', now()->subMinutes(25))
            ->get();

        $avisados = 0;

        foreach ($atascados as $pago) {
            $cacheKey = "alert:stuck-payment:{$pago->id}";

            if (Cache::has($cacheKey)) {
                continue;
            }

            Cache::put($cacheKey, true, now()->addHours(2));
            $avisados++;

            Log::warning("Pago sin verificar automáticamente: {$pago->id}");

            if ($adminPhone) {
                $evolutionService->sendMessage(
                    $adminPhone,
                    "⚠️ *PAGO SIN VERIFICAR*\nOrden: {$pago->orden_id}\nReferencia: {$pago->referencia_usuario}\nMonto: \${$pago->monto}\n\nLa verificación automática no lo ha confirmado en 25+ minutos. Revísalo manualmente en el panel admin."
                );
            }
        }

        return $avisados;
    }
}
