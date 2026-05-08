<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrintJob;
use App\Models\PdfFile;
use App\Services\PrintService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PrintJobApiController extends Controller
{
    protected $printService;

    public function __construct(PrintService $printService)
    {
        $this->printService = $printService;
    }

    /**
     * Get all print jobs.
     */
    public function index(): JsonResponse
    {
        $jobs = PrintJob::with('user', 'pdfFile')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    /**
     * Get a specific print job.
     */
    public function show($id): JsonResponse
    {
        $job = PrintJob::with('user', 'pdfFile')->find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Trabajo no encontrado',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $job,
        ]);
    }

    /**
     * Create a new print job.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pdf_file_id' => 'required|exists:pdf_files,id',
            'user_id' => 'required|exists:users,id',
            'copies' => 'required|integer|min:1',
            'color_type' => 'required|in:bw,color',
            'paper_size' => 'required|in:a4,letter,legal',
            'orientation' => 'required|in:portrait,landscape',
        ]);

        $pdfFile = PdfFile::find($validated['pdf_file_id']);
        
        // Calculate cost
        $baseCostPerPage = $validated['color_type'] === 'color' ? 0.20 : 0.05;
        $totalCost = $pdfFile->pages_count * $validated['copies'] * $baseCostPerPage;

        $printJob = PrintJob::create([
            'user_id' => $validated['user_id'],
            'pdf_file_id' => $validated['pdf_file_id'],
            'copies' => $validated['copies'],
            'color_type' => $validated['color_type'],
            'paper_size' => $validated['paper_size'],
            'orientation' => $validated['orientation'],
            'cost' => $totalCost,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trabajo de impresión creado exitosamente',
            'data' => $printJob->load('user', 'pdfFile'),
        ], 201);
    }

    /**
     * Update print job status.
     */
    public function updateStatus($id, Request $request): JsonResponse
    {
        $job = PrintJob::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Trabajo no encontrado',
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,printing,completed,cancelled',
        ]);

        $job->update(['status' => $validated['status']]);

        if ($validated['status'] === 'completed') {
            $job->update(['printed_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado exitosamente',
            'data' => $job,
        ]);
    }

    /**
     * Get print job statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_jobs' => PrintJob::count(),
            'pending_jobs' => PrintJob::where('status', 'pending')->count(),
            'printing_jobs' => PrintJob::where('status', 'printing')->count(),
            'completed_jobs' => PrintJob::where('status', 'completed')->count(),
            'cancelled_jobs' => PrintJob::where('status', 'cancelled')->count(),
            'total_revenue' => PrintJob::where('status', 'completed')->sum('cost'),
            'total_pages' => PdfFile::sum('pages_count'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
