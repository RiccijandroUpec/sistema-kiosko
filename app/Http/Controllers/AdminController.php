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

        $jobs = \App\Models\PrintJob::with('pdfFile')->orderBy('created_at', 'desc')->get();
        $jobs = \App\Models\PrintJob::with('pdfFile')->orderBy('created_at', 'desc')->get();
        return view('admin.index', compact('stats', 'recentPayments', 'pendingPayments', 'jobs'));
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
                'paid' => true,
                'printed_at' => now(),
            ]);

            // IMPORTANTE: Confirmar el pago para que sume al dinero ganado
            if ($printJob->payment) {
                $printJob->payment->update(['status' => 'confirmed']);
            }

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Trabajo marcado como completado.']);
            }

            return back()->with('success', 'Trabajo marcado como completado.');
        } catch (\Exception $e) {
            Log::error('Error al marcar trabajo', ['error' => $e->getMessage()]);
            
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al actualizar el trabajo.'], 500);
            }

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

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Trabajo cancelado.']);
            }

            return back()->with('success', 'Trabajo cancelado.');
        } catch (\Exception $e) {
            Log::error('Error al cancelar trabajo', ['error' => $e->getMessage()]);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al cancelar el trabajo.'], 500);
            }

            return back()->withErrors('Error al cancelar el trabajo.');
        }
    }

    /**
     * Eliminar trabajo de impresión (y su archivo físico).
     */
    public function deleteJob(PrintJob $printJob)
    {
        try {
            $pdfFile = $printJob->pdfFile;

            // 1. Borrar archivo físico si existe
            if ($pdfFile && Storage::disk('public')->exists($pdfFile->file_path)) {
                Storage::disk('public')->delete($pdfFile->file_path);
            }

            // 2. El pago se borrará por cascada (si está configurado) o manualmente
            if ($printJob->payment) {
                $printJob->payment->delete();
            }

            // 3. Borrar registros de BD
            if ($pdfFile) {
                $pdfFile->delete();
            }
            
            $printJob->delete();

            Log::info('Trabajo eliminado definitivamente', ['job_reference' => $printJob->job_reference]);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Trabajo eliminado correctamente.']);
            }

            return redirect()->route('admin.print-jobs')->with('success', 'Trabajo y archivo eliminados correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar trabajo', ['error' => $e->getMessage()]);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Error al intentar eliminar el trabajo.'], 500);
            }

            return back()->withErrors('Error al intentar eliminar el trabajo.');
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

    /**
     * API: Estadísticas en JSON
     */
    public function apiStats()
    {
        return response()->json([
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'confirmed_payments' => Payment::where('status', 'confirmed')->count(),
            'ready_to_print' => PrintJob::where('status', 'printing')->where('paid', true)->count(),
            'completed' => PrintJob::where('status', 'completed')->count(),
            'cancelled' => PrintJob::where('status', 'cancelled')->count(),
            'revenue' => (float) Payment::where('status', 'confirmed')
                ->whereDate('updated_at', today())
                ->sum('amount'),
        ]);
    }

    /**
     * API: Trabajos en JSON
     */
    public function apiJobs(Request $request)
    {
        $query = PrintJob::with('pdfFile', 'payment')->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $jobs = $query->get();

        return response()->json([
            'jobs' => $jobs->map(fn($job) => [
                'id' => $job->id,
                'job_reference' => $job->job_reference,
                'status' => $job->status,
                'paid' => $job->paid,
                'copies' => $job->copies,
                'color_type' => $job->color_type,
                'cost' => $job->cost,
                'pdf_file' => [
                    'original_name' => $job->pdfFile->original_name,
                    'pages_count' => $job->pdfFile->pages_count,
                ],
            ]),
        ]);
    }

    /**
     * API: Pagos pendientes en JSON
     */
    public function apiPendingPayments()
    {
        $payments = Payment::where('status', 'pending')
            ->with('printJob')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'payments' => $payments->map(fn($p) => [
                'id' => $p->id,
                'reference_code' => $p->reference_code,
                'amount' => $p->amount,
                'print_job_id' => $p->print_job_id,
            ]),
        ]);
    }

    /**
     * Actualizar precios de impresión y persistirlos en .env
     */
    public function updatePrices(Request $request)
    {
        $validated = $request->validate([
            'cost_bw' => 'required|numeric|min:0',
            'cost_color' => 'required|numeric|min:0',
        ]);

        try {
            // 1. Actualizar en la memoria actual
            config(['printing.cost_bw' => $validated['cost_bw']]);
            config(['printing.cost_color' => $validated['cost_color']]);

            // 2. Persistir en el archivo .env
            $envPath = base_path('.env');
            if (file_exists($envPath)) {
                $envContent = file_get_contents($envPath);
                
                // Actualizar o añadir PRINT_COST_BW
                if (str_contains($envContent, 'PRINT_COST_BW=')) {
                    $envContent = preg_replace('/PRINT_COST_BW=.*/', 'PRINT_COST_BW=' . $validated['cost_bw'], $envContent);
                } else {
                    $envContent .= "\nPRINT_COST_BW=" . $validated['cost_bw'];
                }

                // Actualizar o añadir PRINT_COST_COLOR
                if (str_contains($envContent, 'PRINT_COST_COLOR=')) {
                    $envContent = preg_replace('/PRINT_COST_COLOR=.*/', 'PRINT_COST_COLOR=' . $validated['cost_color'], $envContent);
                } else {
                    $envContent .= "\nPRINT_COST_COLOR=" . $validated['cost_color'];
                }

                file_put_contents($envPath, $envContent);
                
                // Limpiar caché de configuración para que Laravel lea el nuevo .env
                \Illuminate\Support\Facades\Artisan::call('config:clear');
            }

            Log::info('Precios actualizados permanentemente', $validated);

            return response()->json(['success' => true, 'message' => 'Precios actualizados permanentemente']);
        } catch (\Exception $e) {
            Log::error('Error al actualizar precios', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error al persistir los precios'], 500);
        }
    }
}
