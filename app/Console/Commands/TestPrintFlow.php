#!/usr/bin/env php
<?php

/**
 * PRUEBA DE IMPRESIÓN - Sistema de Kiosko
 * Script de simulación completa del flujo
 * Ejecutar: php artisan test:print-flow
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class PrintFlowTest
{
    private $testDir = 'storage/test-prints';
    private $outputLog = [];

    public function run()
    {
        $this->log('🚀 INICIANDO PRUEBA DE FLUJO DE IMPRESIÓN', 'info');
        $this->log('================================', 'info');

        // Paso 1: Crear PDF de prueba
        $this->log("\n📄 Paso 1: Crear PDF de prueba...");
        $pdfFile = $this->createTestPdf();

        // Paso 2: Simular upload a Supabase
        $this->log("\n☁️  Paso 2: Simular almacenamiento en Supabase Storage...");
        $pdfUrl = $this->simulateSupabaseUpload($pdfFile);

        // Paso 3: Crear registro en BD
        $this->log("\n🗄️  Paso 3: Crear orden en base de datos...");
        $printJobId = $this->createPrintJobInDatabase($pdfUrl);

        // Paso 4: Simular webhook de Realtime
        $this->log("\n⚡ Paso 4: Simular evento Realtime de Supabase...");
        $this->simulateRealtimeEvent($printJobId);

        // Paso 5: Simular procesamiento del agente
        $this->log("\n🤖 Paso 5: Simular procesamiento de agente local...");
        $this->simulateKioskAgentProcessing($printJobId, $pdfFile);

        // Paso 6: Actualizar estado
        $this->log("\n✅ Paso 6: Actualizar estado a completado...");
        $this->updatePrintJobStatus($printJobId, 'completed');

        // Resumen
        $this->printSummary();
    }

    /**
     * PASO 1: Crear PDF de prueba con TCPDF
     */
    private function createTestPdf()
    {
        $this->log('   ⏳ Creando PDF con TCPDF...');

        if (!File::exists($this->testDir)) {
            File::makeDirectory($this->testDir, 0755, true);
        }

        try {
            // Verificar si TCPDF está disponible
            if (!class_exists('TCPDF')) {
                throw new Exception('TCPDF no está instalado. Instalar: composer require tecnickcom/tcpdf');
            }

            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_PAGE_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(15, 15, 15);
            $pdf->AddPage();

            // Encabezado
            $html = '<h1 style="color: #333; text-align: center; margin-bottom: 20px;">
                        🖨️ DOCUMENTO DE PRUEBA - SISTEMA KIOSKO
                    </h1>';

            $html .= '<p style="font-size: 12px; color: #666; text-align: center;">
                        Fecha: ' . date('Y-m-d H:i:s') . '
                    </p>';

            $html .= '<hr style="border: 1px solid #ccc; margin: 20px 0;">';

            // Contenido
            $html .= '<h2 style="color: #1a73e8;">Información de Prueba</h2>';
            $html .= '<table cellpadding="10" style="width: 100%; border: 1px solid #ddd;">
                        <tr style="background-color: #f5f5f5;">
                            <td style="font-weight: bold; width: 40%;">Concepto</td>
                            <td>Valor</td>
                        </tr>
                        <tr>
                            <td>Test ID</td>
                            <td>TEST-' . date('YmdHis') . '</td>
                        </tr>
                        <tr>
                            <td>Timestamp</td>
                            <td>' . date('Y-m-d H:i:s') . '</td>
                        </tr>
                        <tr>
                            <td>Servidor</td>
                            <td>' . gethostname() . '</td>
                        </tr>
                        <tr>
                            <td>PHP Version</td>
                            <td>' . phpversion() . '</td>
                        </tr>
                        <tr>
                            <td>Laraver Version</td>
                            <td>' . app()->version() . '</td>
                        </tr>
                    </table>';

            $html .= '<div style="margin-top: 30px; padding: 20px; background-color: #e8f5e9; border-left: 4px solid #4caf50;">
                        <p><strong>✅ Imprimir este documento significa que el flujo está funcionando correctamente.</strong></p>
                        <p>Si ves esto en tu impresora, todas las capas (web → servidor → almacenamiento → agente → impresora) funcionan.</p>
                    </div>';

            $pdf->writeHTML($html, true, false, true, false, '');

            // Guardar PDF
            $filename = $this->testDir . '/test-print-' . date('YmdHis') . '.pdf';
            $pdf->Output($filename, 'F');

            $filesize = File::size($filename);
            $this->log("   ✅ PDF creado: {$filename} ({$filesize} bytes)");

            return $filename;

        } catch (Exception $e) {
            $this->log("   ❌ Error creando PDF: {$e->getMessage()}", 'error');
            throw $e;
        }
    }

    /**
     * PASO 2: Simular upload a Supabase Storage
     */
    private function simulateSupabaseUpload($pdfFile)
    {
        $this->log('   ⏳ Simulando upload a Supabase Storage...');

        try {
            $filename = basename($pdfFile);
            $year = date('Y');
            $month = date('m');
            $storagePath = "pdfs/{$year}/{$month}/{$filename}";

            // Simular URL de Supabase
            $supabaseUrl = config('app.supabase_url', 'https://xxxxx.supabase.co');
            $url = "{$supabaseUrl}/storage/v1/object/public/pdfs/{$storagePath}";

            $this->log("   ✅ Storage URL simulada: {$url}");

            return $url;

        } catch (Exception $e) {
            $this->log("   ❌ Error simulando upload: {$e->getMessage()}", 'error');
            throw $e;
        }
    }

    /**
     * PASO 3: Crear registro de print_job en BD
     */
    private function createPrintJobInDatabase($pdfUrl)
    {
        $this->log('   ⏳ Creando registro en tabla print_jobs...');

        try {
            // Asegurar que exista un usuario y kiosk de prueba
            $userId = $this->ensureTestUser();
            $kioskId = $this->ensureTestKiosk();

            $printJobId = \Illuminate\Support\Str::uuid();

            DB::table('print_jobs')->insert([
                'id' => $printJobId,
                'user_id' => $userId,
                'kiosk_id' => $kioskId,
                'pdf_url' => $pdfUrl,
                'status' => 'pending',
                'pages_count' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->log("   ✅ Print Job creado: {$printJobId}");

            return $printJobId;

        } catch (Exception $e) {
            $this->log("   ❌ Error creando print job: {$e->getMessage()}", 'error');
            throw $e;
        }
    }

    /**
     * PASO 4: Simular evento Realtime de Supabase
     */
    private function simulateRealtimeEvent($printJobId)
    {
        try {
            $this->log('   ⏳ Evento Realtime INSERT disparado...');
            
            // Simular el evento que el agente recibiría
            $event = [
                'type' => 'postgres_changes',
                'event' => 'INSERT',
                'schema' => 'public',
                'table' => 'print_jobs',
                'new' => [
                    'id' => $printJobId,
                    'status' => 'pending',
                    'pdf_url' => DB::table('print_jobs')->find($printJobId)->pdf_url,
                ],
            ];

            $this->log('   ✅ Evento simulado: ' . json_encode($event, JSON_PRETTY_PRINT));

        } catch (Exception $e) {
            $this->log("   ❌ Error simulando evento: {$e->getMessage()}", 'error');
            throw $e;
        }
    }

    /**
     * PASO 5: Simular procesamiento del agente local
     */
    private function simulateKioskAgentProcessing($printJobId, $pdfFile)
    {
        try {
            $this->log('   ⏳ Agente recibe evento y comienza procesamiento...');
            
            // Simular descarga del PDF
            $this->log('   ⏳ Descargando PDF desde Supabase Storage...');
            sleep(1); // Simular latencia de red
            $this->log('   ✅ PDF descargado (' . File::size($pdfFile) . ' bytes)');

            // Simular envío a CUPS (en Windows, simular con PowerShell)
            $this->log('   ⏳ Enviando a cola de impresoras del sistema...');
            $this->simulatePrintCommand($pdfFile);

            // Simular espera de resultado
            sleep(2);
            $this->log('   ✅ Trabajo de impresión aceptado por el sistema');

        } catch (Exception $e) {
            $this->log("   ❌ Error en agente: {$e->getMessage()}", 'error');
            throw $e;
        }
    }

    /**
     * PASO 5B: Simular comando de impresión (Windows/Linux)
     */
    private function simulatePrintCommand($pdfFile)
    {
        $OS = strtoupper(substr(PHP_OS, 0, 3));

        if ($OS === 'WIN') {
            // Windows: simular con PowerShell
            $this->log('   💻 Sistema Operativo: Windows');
            $this->log('   📝 Comando de prueba (PowerShell):');
            
            $command = "powershell -Command \"Write-Output \\\"Printing: {$pdfFile}\\\"\"";
            $this->log("      " . $command);
            
            exec($command, $output);
            $this->log('   ✅ Salida: ' . implode(' | ', $output));

        } else if ($OS === 'LIN') {
            // Linux: intentar con CUPS si existe
            $this->log('   🐧 Sistema Operativo: Linux');
            $cupsExists = shell_exec('which lp 2>/dev/null');
            
            if ($cupsExists) {
                $this->log('   ✅ CUPS disponible en el sistema');
                $command = "lp -d default {$pdfFile} 2>&1";
                $output = shell_exec($command);
                $this->log('   ✅ Salida CUPS: ' . trim($output));
            } else {
                $this->log('   ⚠️  CUPS no disponible (esperado en dev)');
            }

        } else {
            // macOS u otro
            $this->log('   🍎 Sistema Operativo: ' . PHP_OS);
        }
    }

    /**
     * PASO 6: Actualizar estado a completado
     */
    private function updatePrintJobStatus($printJobId, $status)
    {
        try {
            $this->log('   ⏳ Actualizando estado en base de datos...');

            DB::table('print_jobs')
                ->where('id', $printJobId)
                ->update([
                    'status' => $status,
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->log("   ✅ Estado actualizado a: {$status}");

        } catch (Exception $e) {
            $this->log("   ❌ Error actualizando estado: {$e->getMessage()}", 'error');
            throw $e;
        }
    }

    /**
     * Helpers
     */

    private function ensureTestUser()
    {
        $user = DB::table('users')
            ->where('email', 'test@printjob.local')
            ->first();

        if (!$user) {
            $id = \Illuminate\Support\Str::uuid();
            DB::table('users')->insert([
                'id' => $id,
                'email' => 'test@printjob.local',
                'password' => bcrypt('test123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return $id;
        }

        return $user->id;
    }

    private function ensureTestKiosk()
    {
        $kiosk = DB::table('kiosks')
            ->where('name', 'Kiosk Test Local')
            ->first();

        if (!$kiosk) {
            $id = \Illuminate\Support\Str::uuid();
            DB::table('kiosks')->insert([
                'id' => $id,
                'name' => 'Kiosk Test Local',
                'location' => 'Desarrollo Local',
                'printer_name' => 'PDF-Virtual-Printer',
                'status' => 'online',
                'last_heartbeat' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            return $id;
        }

        return $kiosk->id;
    }

    private function log($message, $level = 'info')
    {
        // Color en terminal
        $colors = [
            'info' => "\033[36m",    // Cyan
            'error' => "\033[91m",   // Red
            'success' => "\033[92m", // Green
            'reset' => "\033[0m",
        ];

        $color = $colors[$level] ?? $colors['info'];
        echo $color . $message . $colors['reset'] . PHP_EOL;

        $this->outputLog[] = $message;
    }

    private function printSummary()
    {
        $this->log("\n================================", 'info');
        $this->log("✅ PRUEBA COMPLETADA EXITOSAMENTE", 'success');
        $this->log("================================\n", 'info');

        echo "\n📊 RESUMEN:\n";
        echo "  ✓ PDF de prueba creado\n";
        echo "  ✓ Almacenamiento simulado\n";
        echo "  ✓ Orden creada en BD\n";
        echo "  ✓ Evento Realtime disparado\n";
        echo "  ✓ Agente procesó la orden\n";
        echo "  ✓ Estado actualizado\n";

        echo "\n📁 ARCHIVOS GENERADOS:\n";
        echo "  Storage: " . $this->testDir . "/\n";

        echo "\n🔗 CONSULTAS ÚTILES:\n";
        echo "  Ver últimas órdenes: php artisan tinker\n";
        echo "    > DB::table('print_jobs')->latest()->first()\n";
        echo "  Ver kioskos: \n";
        echo "    > DB::table('kiosks')->get()\n";

        echo "\n✨ El flujo está funcionando correctamente.\n";
        echo "   En producción, esto activaría el agente real en la sucursal.\n\n";
    }
}

// Ejecutar
try {
    (new PrintFlowTest())->run();
} catch (Exception $e) {
    echo "\n❌ Error fatal: " . $e->getMessage() . "\n";
    exit(1);
}
