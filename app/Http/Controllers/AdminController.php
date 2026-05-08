<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    /**
     * Dashboard del admin.
     */
    public function dashboard()
    {
        $stats = [
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'confirmed_payments' => Payment::where('status', 'confirmed')->count(),
            'ready_to_print' => PrintJob::where('status', 'printing')->where('paid', true)->count(),
            'completed' => PrintJob::where('status', 'completed')->count(),
            'cancelled' => PrintJob::where('status', 'cancelled')->count(),
            'total_revenue' => Payment::where('status', 'confirmed')->sum('amount'),
        ];

        $recentPayments = Payment::with('printJob.pdfFile')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $pendingPayments = Payment::where('status', 'pending')
            ->with('printJob.pdfFile')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.dashboard', compact('stats', 'recentPayments', 'pendingPayments'));
    }

    /**
     * Lista de trabajos pendientes de impresión.
     */
    public function printJobs(Request $request)
    {
        $query = PrintJob::with('pdfFile', 'payment')
            ->orderBy('created_at', 'desc');

        // Filtrar por estado
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filtrar por pagado/no pagado
        if ($request->has('paid')) {
            $query->where('paid', $request->paid === 'yes');
        }

        $printJobs = $query->paginate(15);

        return view('admin.print-jobs', compact('printJobs'));
    }

    /**
     * Detalles de un trabajo de impresión.
     */
    public function jobDetails(PrintJob $printJob)
    {
        $printJob->load('pdfFile', 'payment');

        return view('admin.job-details', compact('printJob'));
    }

    /**
     * Descargar PDF de un trabajo.
     */
    public function downloadPdf(PrintJob $printJob)
    {
        $pdfFile = $printJob->pdfFile;
        $filePath = storage_path('app/public/' . $pdfFile->file_path);

        if (!file_exists($filePath)) {
            return back()->withErrors('Archivo no encontrado.');
        }

        Log::info('PDF descargado', ['job_reference' => $printJob->job_reference]);

        return response()->download($filePath, $pdfFile->original_name);
    }

    /**
     * Marcar trabajo como impreso.
     */
    public function markAsPrinted(PrintJob $printJob)
    {
        try {
            $printJob->update([
                'status' => 'completed',
                'printed_at' => now(),
            ]);

            Log::info('Trabajo marcado como impreso', ['job_reference' => $printJob->job_reference]);

            return back()->with('success', 'Trabajo marcado como completado.');
        } catch (\Exception $e) {
            Log::error('Error al marcar trabajo', ['error' => $e->getMessage()]);
            return back()->withErrors('Error al actualizar el trabajo.');
        }
    }

    /**
     * Cancelar trabajo.
     */
    public function cancelJob(PrintJob $printJob)
    {
        try {
            $printJob->update(['status' => 'cancelled']);

            // Si hay pago confirmado, crear nota
            if ($printJob->payment && $printJob->payment->status === 'confirmed') {
                $printJob->payment->update([
                    'status' => 'cancelled',
                    'notes' => 'Trabajo cancelado por el administrador',
                ]);
            }

            Log::info('Trabajo cancelado', ['job_reference' => $printJob->job_reference]);

            return back()->with('success', 'Trabajo cancelado.');
        } catch (\Exception $e) {
            Log::error('Error al cancelar trabajo', ['error' => $e->getMessage()]);
            return back()->withErrors('Error al cancelar el trabajo.');
        }
    }

    /**
     * Reporte de transacciones.
     */
    public function transactions(Request $request)
    {
        $query = Payment::with('printJob.pdfFile')
            ->orderBy('created_at', 'desc');

        // Filtrar por estado
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filtrar por fecha
        if ($request->has('from_date') && $request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $payments = $query->paginate(20);

        $summary = [
            'total_confirmed' => Payment::where('status', 'confirmed')->sum('amount'),
            'total_pending' => Payment::where('status', 'pending')->sum('amount'),
            'count_confirmed' => Payment::where('status', 'confirmed')->count(),
            'count_pending' => Payment::where('status', 'pending')->count(),
        ];

        return view('admin.transactions', compact('payments', 'summary'));
    }

    /**
     * Estadísticas del sistema.
     */
    public function statistics()
    {
        $stats = [
            'total_jobs' => PrintJob::count(),
            'completed_jobs' => PrintJob::where('status', 'completed')->count(),
            'pending_jobs' => PrintJob::where('status', 'pending')->count(),
            'cancelled_jobs' => PrintJob::where('status', 'cancelled')->count(),
            'total_revenue' => Payment::where('status', 'confirmed')->sum('amount'),
            'total_pages' => PrintJob::sum('copies') * \DB::table('pdf_files')->avg('pages_count'),
        ];

        return view('admin.statistics', compact('stats'));
    }
}
