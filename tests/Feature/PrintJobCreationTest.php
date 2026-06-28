<?php

namespace Tests\Feature;

use App\Models\Kiosko;
use App\Models\PdfFile;
use App\Models\OrdenImpresion;
use App\Models\TransaccionPago;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintJobCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cobra_por_el_documento_completo_cuando_no_hay_rango_personalizado(): void
    {
        $kiosko = Kiosko::factory()->create(['precio_blanco_negro' => 0.05, 'precio_color' => 0.20]);
        $pdf = PdfFile::factory()->create(['pages_count' => 10]);

        $response = $this->post(route('kiosko.create-job', $pdf->id), [
            'kiosk_id' => $kiosko->id,
            'copies' => 2,
            'color_type' => 'bw',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'page_selection' => 'all',
        ]);

        $orden = OrdenImpresion::first();

        $response->assertRedirect(route('kiosko.payment', $orden->id));
        $this->assertEquals(2, $orden->copias);
        $this->assertNull($orden->rango_paginas);
        // 10 paginas x 2 copias x $0.05 = $1.00
        $this->assertEquals('1.00', $orden->costo_total);
    }

    public function test_cobra_solo_por_las_paginas_del_rango_personalizado_no_por_el_documento_completo(): void
    {
        $kiosko = Kiosko::factory()->create(['precio_blanco_negro' => 0.05, 'precio_color' => 0.20]);
        $pdf = PdfFile::factory()->create(['pages_count' => 10]);

        $response = $this->post(route('kiosko.create-job', $pdf->id), [
            'kiosk_id' => $kiosko->id,
            'copies' => 3,
            'color_type' => 'bw',
            'paper_size' => 'a4',
            'orientation' => 'landscape',
            'page_selection' => 'custom',
            'custom_pages' => '1-2, 4',
        ]);

        $orden = OrdenImpresion::first();

        $response->assertRedirect(route('kiosko.payment', $orden->id));
        $this->assertEquals(3, $orden->copias);
        $this->assertEquals('1-2,4', $orden->rango_paginas);
        $this->assertEquals('landscape', $orden->orientacion);
        // 3 paginas en el rango (1,2,4) x 3 copias x $0.05 = $0.45, NO 10 x 3 x 0.05 = $1.50
        $this->assertEquals('0.45', $orden->costo_total);
    }

    public function test_un_rango_invalido_cae_de_vuelta_al_documento_completo_en_vez_de_cobrar_cero(): void
    {
        $kiosko = Kiosko::factory()->create(['precio_blanco_negro' => 0.05]);
        $pdf = PdfFile::factory()->create(['pages_count' => 10]);

        $this->post(route('kiosko.create-job', $pdf->id), [
            'kiosk_id' => $kiosko->id,
            'copies' => 1,
            'color_type' => 'bw',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'page_selection' => 'custom',
            'custom_pages' => '999-1000', // fuera de rango del documento
        ]);

        $orden = OrdenImpresion::first();

        $this->assertNull($orden->rango_paginas);
        // 10 paginas x 1 copia x $0.05 = $0.50 (no se cobra $0.00)
        $this->assertEquals('0.50', $orden->costo_total);
    }

    public function test_crea_una_transaccion_de_pago_pendiente_junto_con_la_orden(): void
    {
        $kiosko = Kiosko::factory()->create();
        $pdf = PdfFile::factory()->create(['pages_count' => 1]);

        $this->post(route('kiosko.create-job', $pdf->id), [
            'kiosk_id' => $kiosko->id,
            'copies' => 1,
            'color_type' => 'bw',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'page_selection' => 'all',
        ]);

        $orden = OrdenImpresion::first();

        $this->assertDatabaseHas('transacciones_pago', [
            'orden_id' => $orden->id,
            'estado' => 'pendiente',
        ]);
    }

    public function test_no_deja_crear_un_trabajo_si_no_hay_ningun_kiosko_disponible(): void
    {
        $pdf = PdfFile::factory()->create(['pages_count' => 1]);

        $response = $this->post(route('kiosko.create-job', $pdf->id), [
            'copies' => 1,
            'color_type' => 'bw',
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'page_selection' => 'all',
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('ordenes_impresion', 0);
    }
}
