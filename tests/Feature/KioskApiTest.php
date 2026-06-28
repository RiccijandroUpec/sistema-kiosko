<?php

namespace Tests\Feature;

use App\Models\Kiosko;
use App\Models\OrdenImpresion;
use App\Models\TransaccionPago;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KioskApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // El EvolutionService manda mensajes de WhatsApp reales por HTTP; los interceptamos
        // para que los tests no dependan de que el bot este corriendo.
        Http::fake();
    }

    public function test_no_autentica_con_un_token_invalido(): void
    {
        $response = $this->postJson('/api/kiosk/heartbeat', [], [
            'X-Kiosk-Token' => 'token-que-no-existe',
        ]);

        $response->assertStatus(401);
    }

    public function test_el_heartbeat_marca_el_kiosko_como_activo_y_actualiza_la_fecha(): void
    {
        $kiosko = Kiosko::factory()->create(['estado' => 'offline', 'ultima_conexion' => now()->subHour()]);

        $response = $this->postJson('/api/kiosk/heartbeat', [], [
            'X-Kiosk-Token' => $kiosko->id,
        ]);

        $response->assertOk();
        $this->assertEquals('activo', $kiosko->fresh()->estado);
        $this->assertTrue($kiosko->fresh()->ultima_conexion->isAfter(now()->subMinute()));
    }

    public function test_pending_jobs_solo_devuelve_ordenes_pagadas_o_imprimiendo_del_kiosko_correcto(): void
    {
        $kioskoA = Kiosko::factory()->create();
        $kioskoB = Kiosko::factory()->create();

        $pagada = OrdenImpresion::factory()->create(['kiosko_id' => $kioskoA->id, 'estado' => 'pagado']);
        OrdenImpresion::factory()->create(['kiosko_id' => $kioskoA->id, 'estado' => 'pendiente']); // no pagada, no debe salir
        OrdenImpresion::factory()->create(['kiosko_id' => $kioskoB->id, 'estado' => 'pagado']); // de otro kiosko, no debe salir

        $response = $this->getJson('/api/kiosk/jobs/pending', [
            'X-Kiosk-Token' => $kioskoA->id,
        ]);

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertCount(1, $ids);
        $this->assertTrue($ids->contains($pagada->id));
    }

    public function test_downloadpdf_sirve_el_archivo_local_cuando_el_host_coincide_con_la_app(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('pdfs/prueba.pdf', 'contenido-falso-del-pdf');

        $kiosko = Kiosko::factory()->create();
        $orden = OrdenImpresion::factory()->create([
            'kiosko_id' => $kiosko->id,
            'archivo_url' => url('storage/pdfs/prueba.pdf'),
        ]);

        $response = $this->get("/api/kiosk/jobs/{$orden->id}/pdf", [
            'X-Kiosk-Token' => $kiosko->id,
        ]);

        $response->assertOk();
    }

    public function test_downloadpdf_redirige_cuando_la_url_es_externa_de_otro_host(): void
    {
        $kiosko = Kiosko::factory()->create();
        $orden = OrdenImpresion::factory()->create([
            'kiosko_id' => $kiosko->id,
            'archivo_url' => 'https://fhobcjujlrcslrucecep.supabase.co/storage/v1/object/public/pdfs/algo.pdf',
        ]);

        $response = $this->get("/api/kiosk/jobs/{$orden->id}/pdf", [
            'X-Kiosk-Token' => $kiosko->id,
        ]);

        $response->assertRedirect('https://fhobcjujlrcslrucecep.supabase.co/storage/v1/object/public/pdfs/algo.pdf');
    }

    public function test_un_kiosko_no_puede_descargar_el_pdf_de_la_orden_de_otro_kiosko(): void
    {
        $kioskoA = Kiosko::factory()->create();
        $kioskoB = Kiosko::factory()->create();
        $orden = OrdenImpresion::factory()->create(['kiosko_id' => $kioskoB->id]);

        $response = $this->get("/api/kiosk/jobs/{$orden->id}/pdf", [
            'X-Kiosk-Token' => $kioskoA->id,
        ]);

        $response->assertStatus(401);
    }

    public function test_completejob_marca_la_orden_completada_y_confirma_la_transaccion(): void
    {
        $kiosko = Kiosko::factory()->create();
        $orden = OrdenImpresion::factory()->create(['kiosko_id' => $kiosko->id, 'estado' => 'imprimiendo']);
        TransaccionPago::factory()->create(['orden_id' => $orden->id, 'estado' => 'pendiente']);

        $response = $this->postJson("/api/kiosk/jobs/{$orden->id}/complete", [], [
            'X-Kiosk-Token' => $kiosko->id,
        ]);

        $response->assertOk();
        $this->assertEquals('completado', $orden->fresh()->estado);
        $this->assertDatabaseHas('transacciones_pago', ['orden_id' => $orden->id, 'estado' => 'completado']);
    }

    public function test_reporterror_marca_la_orden_en_error(): void
    {
        $kiosko = Kiosko::factory()->create();
        $orden = OrdenImpresion::factory()->create(['kiosko_id' => $kiosko->id, 'estado' => 'imprimiendo']);

        $response = $this->postJson("/api/kiosk/jobs/{$orden->id}/error", [
            'error' => 'La impresora no tiene papel',
        ], [
            'X-Kiosk-Token' => $kiosko->id,
        ]);

        $response->assertOk();
        $this->assertEquals('error', $orden->fresh()->estado);
    }
}
