<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PaymentVerificationService;
use Illuminate\Support\Facades\Log;

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
    public function handle(PaymentVerificationService $service)
    {
        $this->info('Iniciando verificación de correos de pago...');
        
        try {
            $count = $service->verifyPendingPayments();
            $this->info("Verificación completada. Se liberaron {$count} impresiones.");
            Log::info("VerifyPaymentsCommand ejecutado con éxito. Pagos liberados: {$count}");
        } catch (\Exception $e) {
            $this->error('Ocurrió un error al verificar pagos: ' . $e->getMessage());
            Log::error('Error en VerifyPaymentsCommand: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
