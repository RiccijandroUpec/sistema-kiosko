<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Kiosko;
use App\Models\OrdenImpresion;
use App\Models\TransaccionPago;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Sin esto GeminiVisionService devuelve null de entrada (early-return por
        // api_key vacio) y ningun test de comprobantes llegaria a probar la logica real.
        config(['gemini.api_key' => 'test-key']);

        // Evolution API real (sendMessage) siempre queda interceptada: no queremos
        // mandar WhatsApp real en los tests. Las demas llamadas HTTP (Gemini,
        // downloadMedia) se configuran por test segun lo que necesiten simular.
        Http::fake([
            'http://127.0.0.1:8080/message/sendText/*' => Http::response(['status' => 'ok'], 200),
        ]);
    }

    protected function imagePayload(string $messageId, string $fromPhone): array
    {
        return [
            'event' => 'messages.upsert',
            'data' => [
                'key' => [
                    'id' => $messageId,
                    'remoteJid' => "{$fromPhone}@s.whatsapp.net",
                ],
                'messageTimestamp' => now()->timestamp,
                'message' => [
                    'imageMessage' => [
                        'mimetype' => 'image/jpeg',
                    ],
                ],
            ],
        ];
    }

    protected function fakeMediaAndGemini(float $monto, string $referencia = 'ABC123'): void
    {
        Http::fake([
            'http://127.0.0.1:8080/message/sendText/*' => Http::response(['status' => 'ok'], 200),
            'http://127.0.0.1:8080/chat/getBase64FromMediaMessage/*' => Http::response([
                'base64' => base64_encode('contenido-de-imagen-falso'),
            ], 200),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode(['referencia' => $referencia, 'monto' => $monto]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);
    }

    public function test_el_webhook_rechaza_peticiones_sin_el_secreto_configurado(): void
    {
        config(['evolution.webhook_secret' => 'un-secreto-real']);

        $response = $this->postJson('/webhook-bot', $this->imagePayload('MSG1', '593999999999'));

        $response->assertStatus(404);
    }

    public function test_el_webhook_acepta_peticiones_con_el_secreto_correcto(): void
    {
        config(['evolution.webhook_secret' => 'un-secreto-real']);
        $this->fakeMediaAndGemini(0.25);

        $response = $this->postJson('/webhook-bot?secret=un-secreto-real', $this->imagePayload('MSG1', '593999999999'));

        $response->assertOk();
    }

    public function test_libera_la_orden_del_cliente_cuando_el_monto_coincide(): void
    {
        config(['evolution.webhook_secret' => null]);
        $this->fakeMediaAndGemini(0.25, 'REF001');

        $cliente = Cliente::factory()->create(['telefono' => '593999999999']);
        $orden = OrdenImpresion::factory()->create(['cliente_id' => $cliente->id, 'estado' => 'pendiente']);
        $pago = TransaccionPago::factory()->create(['orden_id' => $orden->id, 'monto' => 0.25, 'estado' => 'pendiente']);

        $response = $this->postJson('/webhook-bot', $this->imagePayload('MSG1', '593999999999'));

        $response->assertOk();
        $this->assertEquals('pagado', $orden->fresh()->estado);
        $this->assertEquals('completado', $pago->fresh()->estado);
    }

    public function test_no_permite_reusar_el_mismo_comprobante_para_pagar_otra_orden(): void
    {
        config(['evolution.webhook_secret' => null]);

        $cliente = Cliente::factory()->create(['telefono' => '593999999999']);

        // Una orden anterior ya se pago con esa referencia.
        $ordenVieja = OrdenImpresion::factory()->create(['cliente_id' => $cliente->id, 'estado' => 'pagado']);
        TransaccionPago::factory()->create([
            'orden_id' => $ordenVieja->id,
            'monto' => 0.25,
            'estado' => 'completado',
            'referencia_usuario' => 'REF-REUSADA',
        ]);

        // Nueva orden pendiente del mismo cliente y mismo monto.
        $ordenNueva = OrdenImpresion::factory()->create(['cliente_id' => $cliente->id, 'estado' => 'pendiente']);
        $pagoNuevo = TransaccionPago::factory()->create(['orden_id' => $ordenNueva->id, 'monto' => 0.25, 'estado' => 'pendiente']);

        $this->fakeMediaAndGemini(0.25, 'REF-REUSADA');

        $response = $this->postJson('/webhook-bot', $this->imagePayload('MSG2', '593999999999'));

        $response->assertOk();
        $this->assertEquals('pendiente', $ordenNueva->fresh()->estado, 'No debe liberarse con un comprobante ya usado');
        $this->assertEquals('pendiente', $pagoNuevo->fresh()->estado);
    }

    public function test_sin_match_de_cliente_no_libera_si_hay_mas_de_una_orden_pendiente_con_el_mismo_monto(): void
    {
        config(['evolution.webhook_secret' => null]);
        $this->fakeMediaAndGemini(0.25, 'REF-AMBIGUA');

        // Dos ordenes de clientes distintos, mismo monto, ninguna es del numero que escribe.
        $pago1 = TransaccionPago::factory()->create(['monto' => 0.25, 'estado' => 'pendiente']);
        $pago2 = TransaccionPago::factory()->create(['monto' => 0.25, 'estado' => 'pendiente']);

        $response = $this->postJson('/webhook-bot', $this->imagePayload('MSG3', '593988888888'));

        $response->assertOk();
        $this->assertEquals('pendiente', $pago1->fresh()->estado);
        $this->assertEquals('pendiente', $pago2->fresh()->estado);
    }

    public function test_sin_match_de_cliente_libera_si_es_la_unica_orden_pendiente_con_ese_monto(): void
    {
        config(['evolution.webhook_secret' => null]);
        $this->fakeMediaAndGemini(0.37, 'REF-UNICA');

        $orden = OrdenImpresion::factory()->create(['estado' => 'pendiente']);
        $pago = TransaccionPago::factory()->create(['orden_id' => $orden->id, 'monto' => 0.37, 'estado' => 'pendiente']);

        $response = $this->postJson('/webhook-bot', $this->imagePayload('MSG4', '593988888888'));

        $response->assertOk();
        $this->assertEquals('pagado', $orden->fresh()->estado);
        $this->assertEquals('completado', $pago->fresh()->estado);
    }
}
