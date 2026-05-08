<?php

namespace App\Services;

use App\Models\PrintJob;
use App\Models\Transaction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\WhatsAppService;

class PrintService
{
    /**
     * Process a print job
     */
    public function processPrintJob(PrintJob $printJob)
    {
        try {
            // Actualizar estado a "printing"
            $printJob->update(['status' => 'printing']);
            
            Log::info("Iniciando impresión", [
                'print_job_id' => $printJob->id,
                'user_id' => $printJob->user_id,
                'pdf_file' => $printJob->pdfFile->original_name,
                'copies' => $printJob->copies,
            ]);

            // Ejecutar proceso de impresión
            $this->executePrinting($printJob);
            
            // Actualizar estado a "completed"
            $printJob->update([
                'status' => 'completed',
                'printed_at' => now()
            ]);

            // Crear transacción de pago
            $this->createTransaction($printJob);

            // Enviar notificación al usuario
            $this->notifyUser($printJob, 'completed');

            Log::info("Impresión completada", [
                'print_job_id' => $printJob->id,
            ]);

            return $printJob;
        } catch (\Exception $e) {
            Log::error("Error durante la impresión", [
                'print_job_id' => $printJob->id,
                'error' => $e->getMessage(),
            ]);

            $printJob->update(['status' => 'failed']);
            $this->notifyUser($printJob, 'failed');

            throw $e;
        }
    }
    
    /**
     * Execute the actual printing process
     */
    private function executePrinting(PrintJob $printJob)
    {
        // Obtener la ruta del archivo PDF
        $filePath = storage_path('app/public/' . $printJob->pdfFile->file_path);

        // Verificar que el archivo existe
        if (!file_exists($filePath)) {
            throw new \Exception('Archivo PDF no encontrado: ' . $filePath);
        }

        // En producción, aquí se enviaría a una impresora real
        // Por ahora, simulamos el proceso
        $this->simulatePrinting($printJob);

        // Registrar estadísticas
        Log::info("Estadísticas de impresión", [
            'print_job_id' => $printJob->id,
            'total_pages' => $printJob->pdfFile->pages_count * $printJob->copies,
            'color_type' => $printJob->color_type,
            'paper_size' => $printJob->paper_size,
        ]);
    }
    
    /**
     * Simulate the printing process (for development)
     */
    private function simulatePrinting(PrintJob $printJob)
    {
        // Simulación: esperar un tiempo proporcional a las páginas
        $sleep_time = min(($printJob->pdfFile->pages_count * $printJob->copies) / 10, 5);
        sleep((int)$sleep_time);
    }

    /**
     * Create a payment transaction for the print job
     */
    private function createTransaction(PrintJob $printJob)
    {
        return Transaction::create([
            'user_id' => $printJob->user_id,
            'print_job_id' => $printJob->id,
            'amount' => $printJob->cost,
            'type' => 'print_job',
            'description' => 'Impresión de ' . $printJob->pdfFile->original_name,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);
    }

    /**
     * Queue a print job for processing
     */
    public function queuePrintJob(PrintJob $printJob)
    {
        // En una implementación real con colas, aquí se pondría el trabajo en una cola
        // Dispatch a job to the queue
        // \App\Jobs\ProcessPrintJob::dispatch($printJob);

        // Por ahora, procesamos inmediatamente
        return $this->processPrintJob($printJob);
    }

    /**
     * Notify the user about print job status
     */
    private function notifyUser(PrintJob $printJob, $status)
    {
        $user = $printJob->user;

        if ($status === 'completed') {
            Log::info("Notificación de impresión completada", [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'print_job_id' => $printJob->id,
            ]);

            // En producción, enviar email real
            // Mail::send(new PrintJobCompletedMail($printJob));

            // Enviar WhatsApp si el usuario tiene teléfono
            if (!empty($user->phone)) {
                $message = "Tu impresión (ID: {$printJob->id}) ha sido completada. Gracias por usar el kiosko.";
                try {
                    app(WhatsAppService::class)->sendMessage('whatsapp:' . $user->phone, $message);
                } catch (\Exception $e) {
                    Log::error('Error enviando WhatsApp al completar impresión', ['error' => $e->getMessage()]);
                }
            }
        } elseif ($status === 'failed') {
            Log::warning("Notificación de error de impresión", [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'print_job_id' => $printJob->id,
            ]);

            // En producción, enviar email real
            // Mail::send(new PrintJobFailedMail($printJob));

            if (!empty($user->phone)) {
                $message = "Hubo un error procesando tu impresión (ID: {$printJob->id}). Por favor intenta nuevamente o contacta al soporte.";
                try {
                    app(WhatsAppService::class)->sendMessage('whatsapp:' . $user->phone, $message);
                } catch (\Exception $e) {
                    Log::error('Error enviando WhatsApp al fallar impresión', ['error' => $e->getMessage()]);
                }
            }
        }
    }

    /**
     * Cancel a print job
     */
    public function cancelPrintJob(PrintJob $printJob)
    {
        if ($printJob->status !== 'pending' && $printJob->status !== 'printing') {
            throw new \Exception('Solo se pueden cancelar trabajos pendientes o en proceso');
        }

        $printJob->update(['status' => 'cancelled']);

        // Crear transacción de reembolso
        if ($printJob->status === 'pending') {
            Transaction::create([
                'user_id' => $printJob->user_id,
                'print_job_id' => $printJob->id,
                'amount' => -$printJob->cost,
                'type' => 'refund',
                'description' => 'Reembolso: Cancelación de impresión de ' . $printJob->pdfFile->original_name,
                'payment_method' => 'cash',
                'status' => 'completed',
            ]);
        }

        Log::info("Trabajo de impresión cancelado", [
            'print_job_id' => $printJob->id,
        ]);

        return $printJob;
    }

    /**
     * Get print job statistics
     */
    public function getStatistics()
    {
        return [
            'total_jobs' => PrintJob::count(),
            'pending_jobs' => PrintJob::where('status', 'pending')->count(),
            'printing_jobs' => PrintJob::where('status', 'printing')->count(),
            'completed_jobs' => PrintJob::where('status', 'completed')->count(),
            'cancelled_jobs' => PrintJob::where('status', 'cancelled')->count(),
            'failed_jobs' => PrintJob::where('status', 'failed')->count(),
            'total_revenue' => PrintJob::where('status', 'completed')->sum('cost'),
            'total_pages_printed' => PrintJob::where('status', 'completed')
                ->selectRaw('SUM(pages_count * copies) as total')
                ->value('total') ?? 0,
        ];
    }
}