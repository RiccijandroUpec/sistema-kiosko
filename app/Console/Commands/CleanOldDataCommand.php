<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\PdfFile;
use Carbon\Carbon;

class CleanOldDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:old-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpia registros de BD y archivos PDF físicos de más de 48 horas de antigüedad';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limitDate = Carbon::now()->subHours(48);
        $this->info("Buscando registros y archivos creados antes de: " . $limitDate->toDateTimeString());

        DB::beginTransaction();
        try {
            // Encontrar PDFs antiguos
            $oldPdfs = PdfFile::where('created_at', '<', $limitDate)->get();
            $filesDeleted = 0;

            foreach ($oldPdfs as $pdf) {
                // Borrar archivo físico del disco (Storage configurado)
                if (Storage::disk('public')->exists($pdf->file_path)) {
                    Storage::disk('public')->delete($pdf->file_path);
                    $filesDeleted++;
                }

                // Las tablas relacionadas (OrdenImpresion, TransaccionPago) deberían tener cascade delete, 
                // pero si no, podemos borrarlas aquí, aunque al borrar el modelo se dispararían los eventos.
                // Como es una base de datos simple, el borrado en cascada (si está configurado) hará el resto,
                // de lo contrario las órdenes de impresión quedarán huérfanas o fallará.
                // Asegurémonos eliminando manualmente si no hay cascade.
                $pdf->ordenes()->delete(); // Eliminar órdenes asociadas
                $pdf->delete(); // Eliminar el PDF de BD
            }

            // Eliminar órdenes antiguas que no tienen PDF (por si acaso)
            $oldOrdersDeleted = DB::table('ordenes_impresion')->where('created_at', '<', $limitDate)->delete();

            // Eliminar transacciones antiguas no completadas
            $oldTransactionsDeleted = DB::table('transacciones_pago')
                ->where('created_at', '<', $limitDate)
                ->where('estado', '!=', 'completado')
                ->delete();

            DB::commit();

            $this->info("Limpieza completada.");
            $this->info("- Archivos físicos eliminados: $filesDeleted");
            $this->info("- Órdenes antiguas eliminadas: " . (count($oldPdfs) + $oldOrdersDeleted));
            
            Log::info("CleanOldDataCommand ejecutado con éxito", [
                'archivos_borrados' => $filesDeleted,
                'ordenes_borradas' => count($oldPdfs) + $oldOrdersDeleted,
                'transacciones_borradas' => $oldTransactionsDeleted
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Ocurrió un error: " . $e->getMessage());
            Log::error("Error en CleanOldDataCommand", ['error' => $e->getMessage()]);
        }
    }
}
