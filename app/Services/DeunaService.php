<?php

namespace App\Services;

use App\Models\OrdenImpresion;
use App\Models\TransaccionPago;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeunaService
{
    protected ?string $apiKey;
    protected ?string $merchantId;
    protected string $environment;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.deuna.api_key', env('DEUNA_API_KEY'));
        $this->merchantId = config('services.deuna.merchant_id', env('DEUNA_MERCHANT_ID'));
        $this->environment = config('services.deuna.environment', env('DEUNA_ENVIRONMENT', 'sandbox'));
        $this->baseUrl = $this->environment === 'production'
            ? 'https://api.deuna.app'
            : 'https://api.sandbox.deuna.app';
    }

    /**
     * Generar los datos de pago para DeUna.
     */
    public function generatePaymentData(OrdenImpresion $orden, TransaccionPago $transaccion): array
    {
        $monto = (float) $transaccion->monto;
        $orderToken = strtoupper(substr(str_replace('-', '', $orden->id), 0, 8));

        // Si existen credenciales oficiales de comercio de DeUna, se puede consultar su API
        if (!empty($this->apiKey) && !empty($this->merchantId)) {
            try {
                $response = Http::withHeaders([
                    'X-API-KEY' => $this->apiKey,
                    'X-MERCHANT-ID' => $this->merchantId,
                    'Content-Type' => 'application/json',
                ])->timeout(5)->post("{$this->baseUrl}/merchants/v1/orders", [
                    'order_id' => $orden->id,
                    'amount' => $monto,
                    'currency' => 'USD',
                    'description' => "Impresion Kiosko {$orden->kiosko?->nombre_comercial} ({$orden->paginas} pags)",
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'success' => true,
                        'is_mock' => false,
                        'order_id' => $orden->id,
                        'order_token' => $orderToken,
                        'monto' => $monto,
                        'qr_data' => $data['qr_code'] ?? $data['qr'] ?? null,
                        'deep_link' => $data['deep_link'] ?? "https://link.deuna.app/pay?token={$orderToken}&amount={$monto}",
                    ];
                }

                Log::warning('Respuesta no exitosa de DeUna API: ' . $response->body());
            } catch (\Exception $e) {
                Log::error('Error al conectar con DeUna API: ' . $e->getMessage());
            }
        }

        // Fallback robusto para operar con DeUna (QR directo + deep link a la app Deuna)
        $deepLink = "https://link.deuna.app/open?amount={$monto}&reference={$orderToken}";

        return [
            'success' => true,
            'is_mock' => empty($this->apiKey),
            'order_id' => $orden->id,
            'order_token' => $orderToken,
            'monto' => $monto,
            'qr_data' => $deepLink,
            'deep_link' => $deepLink,
        ];
    }

    /**
     * Procesar webhook de DeUna.
     */
    public function processWebhook(array $payload): array
    {
        Log::info('Webhook DeUna recibido:', $payload);

        $orderId = $payload['order_id'] ?? $payload['orderId'] ?? $payload['data']['order_id'] ?? null;
        $status = strtoupper($payload['status'] ?? $payload['payment_status'] ?? $payload['data']['status'] ?? '');
        $txId = $payload['transaction_id'] ?? $payload['id'] ?? null;

        if (!$orderId) {
            return [
                'success' => false,
                'message' => 'Falta order_id en el payload de DeUna.',
            ];
        }

        $orden = OrdenImpresion::with(['cliente', 'kiosko'])->find($orderId);
        if (!$orden) {
            return [
                'success' => false,
                'message' => 'Orden de impresión no encontrada.',
            ];
        }

        $isPaid = in_array($status, ['PAID', 'SUCCESS', 'APPROVED', 'COMPLETED', 'PAGADO']);

        if ($isPaid) {
            $nuevoEstado = ($orden->modo_entrega === 'pin_retiro')
                ? 'esperando_retiro'
                : 'pagado';

            $orden->update([
                'estado' => $nuevoEstado,
            ]);

            TransaccionPago::where('orden_id', $orden->id)->update([
                'estado' => 'completado',
                'referencia_usuario' => $txId ?? 'DEUNA-' . time(),
            ]);

            // Notificación WhatsApp si aplica
            if ($orden->cliente && $orden->cliente->telefono && $orden->cliente->telefono !== 'web_guest') {
                try {
                    $evolution = app(EvolutionService::class);
                    if ($nuevoEstado === 'pagado') {
                        $evolution->sendMessage(
                            $orden->cliente->telefono,
                            "✅ *¡Pago confirmado con DeUna!*\nTu documento está siendo enviado a la impresora en *{$orden->kiosko?->nombre_comercial}*."
                        );
                    } else {
                        $evolution->sendMessage(
                            $orden->cliente->telefono,
                            "✅ *¡Pago confirmado con DeUna!*\nTu orden está lista para retirar. Tu código PIN de retiro es: *{$orden->pin_retiro}*.\nPresiona 'Imprimir ahora' en la web cuando estés frente a la máquina."
                        );
                    }
                } catch (\Exception $e) {
                    Log::error("Fallo al notificar cliente por WhatsApp tras pago DeUna: " . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'message' => 'Pago procesado correctamente.',
                'nuevo_estado' => $nuevoEstado,
                'orden_id' => $orden->id,
            ];
        }

        return [
            'success' => false,
            'message' => "Estado de pago DeUna no aprobado: {$status}",
        ];
    }
}
