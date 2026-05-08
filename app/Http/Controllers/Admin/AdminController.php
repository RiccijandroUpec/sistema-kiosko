<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrintJob;
use App\Models\User;
use App\Services\PrintService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    protected $printService;

    public function __construct(PrintService $printService)
    {
        $this->printService = $printService;
        $this->middleware('role:admin');
    }

    /**
     * Show admin dashboard.
     */
    public function dashboard()
    {
        $stats = [
            'total_users' => User::count(),
            'total_pdfs' => \App\Models\PdfFile::count(),
            'total_jobs' => PrintJob::count(),
            'pending_jobs' => PrintJob::where('status', 'pending')->count(),
            'total_revenue' => PrintJob::where('status', 'completed')->sum('cost'),
        ];

        $recentJobs = PrintJob::latest()->take(10)->get();
        $topUsers = User::withCount('printJobs')->orderByDesc('print_jobs_count')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentJobs', 'topUsers'));
    }

    /**
     * Show all jobs.
     */
    public function jobs()
    {
        $jobs = PrintJob::with('user', 'pdfFile')
            ->latest()
            ->paginate(20);

        return view('admin.jobs', compact('jobs'));
    }

    /**
     * Show all transactions.
     */
    public function transactions()
    {
        $transactions = \App\Models\Transaction::with('user', 'printJob')
            ->latest()
            ->paginate(20);

        return view('admin.transactions', compact('transactions'));
    }

    /**
     * Approve a print job.
     */
    public function approveJob(PrintJob $job)
    {
        $job->update(['status' => 'printing']);

        // Process the print job
        $this->printService->processPrintJob($job);

        // Create transaction
        \App\Models\Transaction::create([
            'user_id' => $job->user_id,
            'print_job_id' => $job->id,
            'amount' => $job->cost,
            'type' => 'print_job',
            'description' => 'Impresión de ' . $job->pdfFile->original_name,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        return redirect()->back()->with('success', 'Trabajo aprobado y procesado exitosamente');
    }

    /**
     * Cancel a print job.
     */
    public function cancelJob(PrintJob $job)
    {
        if ($job->status !== 'pending' && $job->status !== 'printing') {
            return redirect()->back()->with('error', 'Solo puedes cancelar trabajos pendientes o en proceso');
        }

        $job->update(['status' => 'cancelled']);

        return redirect()->back()->with('success', 'Trabajo cancelado exitosamente');
    }
}
