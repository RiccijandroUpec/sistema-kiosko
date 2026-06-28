<?php

namespace Tests\Feature;

use App\Models\Kiosko;
use App\Models\OrdenImpresion;
use App\Models\TransaccionPago;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckSystemHealthCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_marca_offline_un_kiosko_activo_sin_heartbeat_reciente_y_avisa(): void
    {
        $kiosko = Kiosko::factory()->create([
            'estado' => 'activo',
            'ultima_conexion' => now()->subMinutes(10),
        ]);

        $this->artisan('app:check-system-health');

        $this->assertEquals('offline', $kiosko->fresh()->estado);
        Http::assertSentCount(1); // el aviso de WhatsApp al admin
    }

    public function test_no_marca_offline_un_kiosko_con_heartbeat_reciente(): void
    {
        $kiosko = Kiosko::factory()->create([
            'estado' => 'activo',
            'ultima_conexion' => now()->subMinutes(1),
        ]);

        $this->artisan('app:check-system-health');

        $this->assertEquals('activo', $kiosko->fresh()->estado);
        Http::assertNothingSent();
    }

    public function test_no_repite_el_aviso_de_un_kiosko_que_ya_quedo_marcado_offline(): void
    {
        $kiosko = Kiosko::factory()->create([
            'estado' => 'activo',
            'ultima_conexion' => now()->subMinutes(10),
        ]);

        $this->artisan('app:check-system-health'); // primera vez: avisa y marca offline
        $this->artisan('app:check-system-health'); // segunda vez: no debe volver a avisar

        Http::assertSentCount(1);
    }

    public function test_avisa_una_sola_vez_de_un_pago_atascado_sin_verificar(): void
    {
        $kiosko = Kiosko::factory()->create();
        $orden = OrdenImpresion::factory()->create(['kiosko_id' => $kiosko->id, 'estado' => 'pendiente']);
        $pago = TransaccionPago::factory()->create([
            'orden_id' => $orden->id,
            'estado' => 'pendiente',
            'referencia_usuario' => 'ABC123',
        ]);
        $pago->forceFill(['updated_at' => now()->subMinutes(30)])->save();

        $this->artisan('app:check-system-health');
        $this->artisan('app:check-system-health'); // no debe repetir el aviso

        Http::assertSentCount(1);
    }

    public function test_no_avisa_de_un_pago_pendiente_reciente_que_aun_no_cumple_el_plazo(): void
    {
        $kiosko = Kiosko::factory()->create();
        $orden = OrdenImpresion::factory()->create(['kiosko_id' => $kiosko->id, 'estado' => 'pendiente']);
        TransaccionPago::factory()->create([
            'orden_id' => $orden->id,
            'estado' => 'pendiente',
            'referencia_usuario' => 'ABC123',
        ]); // updated_at = ahora mismo

        $this->artisan('app:check-system-health');

        Http::assertNothingSent();
    }
}
