<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OrdenImpresion;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PrintJobApiController extends Controller
{
    /**
     * Get all print jobs.
     */
    public function index(): JsonResponse
    {
        $jobs = OrdenImpresion::with('cliente', 'kiosko')
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
        $job = OrdenImpresion::with('cliente', 'kiosko')->find($id);

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
            'cliente_id' => 'required|exists:clientes,id',
            'kiosko_id' => 'required|exists:kioskos,id',
            'archivo_url' => 'required|url',
            'paginas' => 'required|integer|min:1',
            'color' => 'required|boolean',
            'costo_total' => 'required|numeric|min:0',
        ]);

        $orden = OrdenImpresion::create([
            'cliente_id' => $validated['cliente_id'],
            'kiosko_id' => $validated['kiosko_id'],
            'archivo_url' => $validated['archivo_url'],
            'paginas' => $validated['paginas'],
            'color' => $validated['color'],
            'costo_total' => $validated['costo_total'],
            'estado' => 'pendiente',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trabajo de impresión creado exitosamente',
            'data' => $orden->load('cliente', 'kiosko'),
        ], 201);
    }

    /**
     * Update print job status.
     */
    public function updateStatus($id, Request $request): JsonResponse
    {
        $job = OrdenImpresion::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Trabajo no encontrado',
            ], 404);
        }

        $validated = $request->validate([
            'status' => 'required|in:pendiente,imprimiendo,completado,cancelado',
        ]);

        $job->update(['estado' => $validated['status']]);

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
            'total_jobs' => OrdenImpresion::count(),
            'pending_jobs' => OrdenImpresion::where('estado', 'pendiente')->count(),
            'printing_jobs' => OrdenImpresion::where('estado', 'imprimiendo')->count(),
            'completed_jobs' => OrdenImpresion::where('estado', 'completado')->count(),
            'cancelled_jobs' => OrdenImpresion::where('estado', 'cancelado')->count(),
            'total_revenue' => OrdenImpresion::where('estado', 'completado')->sum('costo_total'),
            'total_pages' => OrdenImpresion::sum('paginas'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
