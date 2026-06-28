<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PaymentVerificationService;
use App\Services\EvolutionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VerifyPaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verify-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Revisa los correos electrónicos para verificar pagos por transferencia o DeUna';

    /**
     * Execute the console command.
     */
    public function handle(PaymentVerificationService $service, EvolutionService $evolutionService)
    {
        $this->info('Iniciando verificación de correos de pago...');

        try {
            $count = $service->verifyPendingPayments();
            $this->info("Verificación completada. Se liberaron {$count} impresiones.");
            Log::info("VerifyPaymentsCommand ejecutado con éxito. Pagos liberados: {$count}");
        } catch (\Exception $e) {
            $this->error('Ocurrió un error al verificar pagos: ' . $e->getMessage());
            Log::error('Error en VerifyPaymentsCommand: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Avisar al admin, pero como esto corre cada minuto, evitamos saturarlo
            // de mensajes repetidos si el problema persiste (1 aviso cada 30 min).
            $cooldownKey = 'alert:verify-payments-failure';
            if (!Cache::has($cooldownKey)) {
                Cache::put($cooldownKey, true, now()->addMinutes(30));

                $adminPhone = env('ADMIN_PHONE');
                if ($adminPhone) {
                    $evolutionService->sendMessage(
                        $adminPhone,
                        "🚨 *ALERTA SISTEMA*\nLa verificación automática de pagos por correo está fallando:\n{$e->getMessage()}\n\nRevisa la conexión IMAP/Gmail."
                    );
                }
            }

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
