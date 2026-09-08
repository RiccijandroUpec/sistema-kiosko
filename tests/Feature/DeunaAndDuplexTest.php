<?php

namespace Tests\Feature;

use App\Models\Kiosko;
use App\Models\OrdenImpresion;
use App\Models\PdfFile;
use App\Models\TransaccionPago;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeunaAndDuplexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        $this->artisan('migrate');
    }

    public function test_crea_trabajo_con_duplex_si_el_kiosko_lo_admite(): void
    {
        $kiosko = Kiosko::create([
            'nombre_comercial' => 'Kiosko Duplex Test',
            'estado' => 'activo',
            'precio_blanco_negro' => 0.05,
            'precio_color' => 0.20,
            'nombre_cups' => 'TestPrinter',
            'admite_duplex' => true,
            'pin' => '1234',
        ]);

        $pdf = PdfFile::create([
            'filename' => 'doc_test.pdf',
            'original_name' => 'doc.pdf',
            'file_path' => 'pdfs/doc_test.pdf',
            'pages_count' => 4,
            'file_size' => 100,
        ]);

        $response = $this->post(route('kiosko.create-job', $pdf->id), [
            'kiosk_id' => $kiosko->id,
            'copies' => 1,
            'color_type' => 'bw',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'duplex' => '1',
            'delivery_mode' => 'inmediato',
        ]);

        $response->assertRedirect();

        $orden = OrdenImpresion::latest()->first();
        $this->assertNotNull($orden);
        $this->assertTrue($orden->duplex);
        $this->assertEquals('inmediato', $orden->modo_entrega);
        $this->assertNull($orden->pin_retiro);
    }

    public function test_no_habilita_duplex_si_el_kiosko_no_lo_admite(): void
    {
        $kiosko = Kiosko::create([
            'nombre_comercial' => 'Kiosko Sin Duplex',
            'estado' => 'activo',
            'precio_blanco_negro' => 0.05,
            'precio_color' => 0.20,
            'nombre_cups' => 'TestPrinterSingle',
            'admite_duplex' => false,
            'pin' => '1234',
        ]);

        $pdf = PdfFile::create([
            'filename' => 'doc_test2.pdf',
            'original_name' => 'doc2.pdf',
            'file_path' => 'pdfs/doc_test2.pdf',
            'pages_count' => 2,
            'file_size' => 100,
        ]);

        $response = $this->post(route('kiosko.create-job', $pdf->id), [
            'kiosk_id' => $kiosko->id,
            'copies' => 1,
            'color_type' => 'bw',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'duplex' => '1',
            'delivery_mode' => 'inmediato',
        ]);

        $response->assertRedirect();

        $orden = OrdenImpresion::latest()->first();
        $this->assertNotNull($orden);
        $this->assertFalse($orden->duplex);
    }

    public function test_crea_trabajo_con_pin_de_retiro(): void
    {
        $kiosko = Kiosko::create([
            'nombre_comercial' => 'Kiosko PIN Test',
            'estado' => 'activo',
            'precio_blanco_negro' => 0.05,
            'precio_color' => 0.20,
            'nombre_cups' => 'TestPrinter',
            'pin' => '1234',
        ]);

        $pdf = PdfFile::create([
            'filename' => 'doc_test3.pdf',
            'original_name' => 'doc3.pdf',
            'file_path' => 'pdfs/doc_test3.pdf',
            'pages_count' => 1,
            'file_size' => 50,
        ]);

        $response = $this->post(route('kiosko.create-job', $pdf->id), [
            'kiosk_id' => $kiosko->id,
            'copies' => 1,
            'color_type' => 'bw',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'delivery_mode' => 'pin_retiro',
        ]);

        $response->assertRedirect();

        $orden = OrdenImpresion::latest()->first();
        $this->assertNotNull($orden);
        $this->assertEquals('pin_retiro', $orden->modo_entrega);
        $this->assertNotNull($orden->pin_retiro);
        $this->assertEquals(4, strlen($orden->pin_retiro));
    }

    public function test_deuna_webhook_procesa_pago_inmediato(): void
    {
        $kiosko = Kiosko::create([
            'nombre_comercial' => 'Kiosko Deuna Inmediato',
            'estado' => 'activo',
            'precio_blanco_negro' => 0.05,
            'precio_color' => 0.20,
            'nombre_cups' => 'Printer',
            'pin' => '1234',
        ]);

        $cliente = \App\Models\Cliente::create([
            'nombre' => 'Test Cliente',
            'telefono' => '0999999999',
        ]);

        $orden = OrdenImpresion::create([
            'kiosko_id' => $kiosko->id,
            'cliente_id' => $cliente->id,
            'archivo_url' => 'https://example.com/test.pdf',
            'paginas' => 1,
            'copias' => 1,
            'color' => false,
            'costo_total' => 0.05,
            'estado' => 'pendiente',
            'modo_entrega' => 'inmediato',
        ]);

        TransaccionPago::create([
            'orden_id' => $orden->id,
            'monto' => 0.05,
            'metodo' => 'Deuna',
            'estado' => 'pendiente',
        ]);

        $response = $this->postJson('/api/webhooks/deuna', [
            'order_id' => $orden->id,
            'status' => 'PAID',
            'transaction_id' => 'TX-123456',
        ]);

        $response->assertStatus(200);

        $orden->refresh();
        $this->assertEquals('pagado', $orden->estado);
    }

    public function test_deuna_webhook_procesa_pago_con_pin_y_luego_se_libera(): void
    {
        $kiosko = Kiosko::create([
            'nombre_comercial' => 'Kiosko Deuna PIN',
            'estado' => 'activo',
            'precio_blanco_negro' => 0.05,
            'precio_color' => 0.20,
            'nombre_cups' => 'Printer',
            'pin' => '1234',
        ]);

        $cliente = \App\Models\Cliente::create([
            'nombre' => 'Test Cliente 2',
            'telefono' => '0999999998',
        ]);

        $orden = OrdenImpresion::create([
            'kiosko_id' => $kiosko->id,
            'cliente_id' => $cliente->id,
            'archivo_url' => 'https://example.com/test.pdf',
            'paginas' => 1,
            'copias' => 1,
            'color' => false,
            'costo_total' => 0.05,
            'estado' => 'pendiente',
            'modo_entrega' => 'pin_retiro',
            'pin_retiro' => '4829',
        ]);

        TransaccionPago::create([
            'orden_id' => $orden->id,
            'monto' => 0.05,
            'metodo' => 'Deuna',
            'estado' => 'pendiente',
        ]);

        // 1. Webhook de Deuna confirma pago
        $this->postJson('/api/webhooks/deuna', [
            'order_id' => $orden->id,
            'status' => 'PAID',
            'transaction_id' => 'TX-999',
        ])->assertStatus(200);

        $orden->refresh();
        // Debe quedar esperando retiro
        $this->assertEquals('esperando_retiro', $orden->estado);

        // 2. Kiosk agent consulta pendingJobs: NO debe salir porque aún no fue liberado
        $token = $kiosko->api_token;
        $pendingRes = $this->withHeader('X-Kiosk-Token', $token)->getJson('/api/kiosk/jobs/pending');
        $pendingRes->assertStatus(200);
        $this->assertCount(0, $pendingRes->json('data'));

        // 3. El usuario libera con su PIN desde el kiosko
        $releaseRes = $this->withHeader('X-Kiosk-Token', $token)->postJson('/api/kiosk/release-pin', [
            'pin' => '4829',
        ]);
        $releaseRes->assertStatus(200);
        $this->assertTrue($releaseRes->json('success'));

        $orden->refresh();
        $this->assertEquals('pagado', $orden->estado);

        // 4. Ahora sí sale en pendingJobs
        $pendingRes2 = $this->withHeader('X-Kiosk-Token', $token)->getJson('/api/kiosk/jobs/pending');
        $this->assertCount(1, $pendingRes2->json('data'));
    }

    public function test_usuario_puede_liberar_orden_desde_su_movil(): void
    {
        $kiosko = Kiosko::create([
            'nombre_comercial' => 'Kiosko Movil',
            'estado' => 'activo',
            'precio_blanco_negro' => 0.05,
            'precio_color' => 0.20,
            'nombre_cups' => 'Printer',
            'pin' => '1234',
        ]);

        $cliente = \App\Models\Cliente::create([
            'nombre' => 'Test Cliente 3',
            'telefono' => '0999999997',
        ]);

        $orden = OrdenImpresion::create([
            'kiosko_id' => $kiosko->id,
            'cliente_id' => $cliente->id,
            'archivo_url' => 'https://example.com/test.pdf',
            'paginas' => 1,
            'copias' => 1,
            'color' => false,
            'costo_total' => 0.05,
            'estado' => 'esperando_retiro',
            'modo_entrega' => 'pin_retiro',
            'pin_retiro' => '7777',
        ]);

        $response = $this->postJson(route('kiosko.order.release-now', $orden->id));
        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));

        $orden->refresh();
        $this->assertEquals('pagado', $orden->estado);
    }
}
