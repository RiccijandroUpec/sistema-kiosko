<?php

namespace App\Services;

use Webklex\IMAP\Facades\Client;
use App\Models\TransaccionPago;
use App\Models\OrdenImpresion;
use Illuminate\Support\Facades\Log;

class PaymentVerificationService
{
    protected $evolutionService;

    public function __construct(EvolutionService $evolutionService)
    {
        $this->evolutionService = $evolutionService;
    }

    public function verifyPendingPayments(): int
    {
        // 1. Obtener todas las transacciones pendientes que tienen una referencia escrita por el usuario
        $pendingPayments = TransaccionPago::with('orden.cliente')
            ->where('estado', 'pendiente')
            ->whereNotNull('referencia_usuario')
            ->get();

        if ($pendingPayments->isEmpty()) {
            return 0; // No hay nada que verificar
        }

        $releasedCount = 0;

        try {
            /** @var \Webklex\PHPIMAP\Client $client */
            $client = Client::account('default');
            $client->connect();

            /** @var \Webklex\PHPIMAP\Folder $folder */
            $folder = $client->getFolder('INBOX');

            // Por cada pago pendiente, buscamos en el servidor (no descargamos todo
            // el inbox) los correos no leidos de los ultimos 3 dias que contengan
            // su referencia. Esto evita descargar el cuerpo de correos irrelevantes.
            foreach ($pendingPayments as $payment) {
                $referencia = trim($payment->referencia_usuario);

                if ($referencia === '') {
                    continue;
                }

                $messages = $folder->query()
                    ->unseen()
                    ->since(now()->subDays(3))
                    ->body($referencia)
                    ->get();

                foreach ($messages as $message) {
                    // Obtener el texto del correo (puede venir en HTML o Texto plano)
                    $body = $message->hasHTMLBody() ? $message->getHTMLBody() : $message->getTextBody();
                    $cleanBody = strtolower(strip_tags($body));

                    // Verificar que el monto también coincida (ej. 0.25, 0,25)
                    $montoStr1 = number_format($payment->monto, 2, '.', '');
                    $montoStr2 = number_format($payment->monto, 2, ',', '');

                    if (str_contains($cleanBody, $montoStr1) || str_contains($cleanBody, $montoStr2)) {
                        // ¡COINCIDENCIA EXACTA!
                        $this->releasePrintJob($payment->orden, $payment);
                        $releasedCount++;

                        // Marcar el correo como leído para no volver a procesarlo
                        $message->setFlag('SEEN');

                        Log::info("Pago liberado automáticamente. Referencia: {$referencia}");

                        break; // ya encontramos el correo de este pago, seguimos con el siguiente pago
                    }
                }
            }

            $client->disconnect();

        } catch (\Exception $e) {
            Log::error('IMAP Error: ' . $e->getMessage());
            throw $e;
        }

        return $releasedCount;
    }

    protected function releasePrintJob(OrdenImpresion $printJob, TransaccionPago $payment)
    {
        // 1. Actualizar estados
        $payment->update(['estado' => 'completado']);
        $printJob->update(['estado' => 'pagado']); // El agente lo tomará e imprimirá

        // 2. Notificar al cliente por WhatsApp
        if ($printJob->cliente && $printJob->cliente->telefono !== 'web_guest') {
            $this->evolutionService->sendMessage(
                $printJob->cliente->telefono,
                "✅ *¡Pago verificado automáticamente!*\nHemos confirmado tu transferencia con referencia *{$payment->referencia_usuario}*.\n\n🖨️ Tu documento ha sido enviado a la impresora del kiosko."
            );
        }
    }
}
