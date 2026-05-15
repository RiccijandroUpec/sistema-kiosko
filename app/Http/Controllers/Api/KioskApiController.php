<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kiosk;
use App\Models\Payment;
use App\Models\PrintJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KioskApiController extends Controller
{
    protected function resolveKiosk(Request $request): ?Kiosk
    {
        $token = (string) $request->header('X-Kiosk-Token', $request->input('api_token', ''));

        if ($token === '') {
            return null;
        }

        return Kiosk::where('api_token', $token)->first();
    }

    protected function unauthorized(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'No autorizado para este kiosko.',
        ], 401);
    }

    public function authenticate(Request $request): JsonResponse
    {
        $kiosk = $this->resolveKiosk($request);

        if (!$kiosk) {
            return $this->unauthorized();
        }

        $kiosk->update([
            'estado_conexion' => 'online',
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $kiosk->id,
                'nombre' => $kiosk->nombre,
                'ubicacion' => $kiosk->ubicacion,
                'estado_conexion' => $kiosk->estado_conexion,
                'last_seen_at' => optional($kiosk->last_seen_at)->toDateTimeString(),
            ],
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $kiosk = $this->resolveKiosk($request);

        if (!$kiosk) {
            return $this->unauthorized();
        }

        $kiosk->update([
            'estado_conexion' => 'online',
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'server_time' => now()->toDateTimeString(),
            ],
        ]);
    }

    public function pendingJobs(Request $request): JsonResponse
    {
        $kiosk = $this->resolveKiosk($request);

        if (!$kiosk) {
            return $this->unauthorized();
        }

        $jobs = PrintJob::with(['pdfFile', 'payment'])
            ->where('kiosk_id', $kiosk->id)
            ->where('status', 'printing')
            ->orderBy('created_at')
            ->get()
            ->map(fn (PrintJob $job) => $this->jobPayload($job));

        $kiosk->update([
            'estado_conexion' => 'online',
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    public function showJob(Request $request, PrintJob $printJob): JsonResponse
    {
        $kiosk = $this->resolveKiosk($request);

        if (!$kiosk || (int) $printJob->kiosk_id !== (int) $kiosk->id) {
            return $this->unauthorized();
        }

        return response()->json([
            'success' => true,
            'data' => $this->jobPayload($printJob->load(['pdfFile', 'payment'])),
        ]);
    }

    public function downloadPdf(Request $request, PrintJob $printJob)
    {
        $kiosk = $this->resolveKiosk($request);

        if (!$kiosk || (int) $printJob->kiosk_id !== (int) $kiosk->id) {
            return $this->unauthorized();
        }

        $pdfFile = $printJob->pdfFile;

        if (!$pdfFile || !Storage::disk('public')->exists($pdfFile->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo no encontrado.',
            ], 404);
        }

        return Storage::disk('public')->download($pdfFile->file_path, $pdfFile->original_name);
    }

    public function completeJob(Request $request, PrintJob $printJob): JsonResponse
    {
        $kiosk = $this->resolveKiosk($request);

        if (!$kiosk || (int) $printJob->kiosk_id !== (int) $kiosk->id) {
            return $this->unauthorized();
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $printJob->loadMissing('payment');
        $printJob->update([
            'status' => 'completed',
            'printed_at' => now(),
        ]);

        if ($printJob->payment && $printJob->payment->status !== 'confirmed') {
            $printJob->payment->update([
                'status' => 'confirmed',
                'notes' => $validated['notes'] ?? $printJob->payment->notes,
            ]);
        }

        $kiosk->update([
            'estado_conexion' => 'online',
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trabajo completado correctamente.',
            'data' => $this->jobPayload($printJob->load(['pdfFile', 'payment'])),
        ]);
    }

    public function markPrinting(Request $request, PrintJob $printJob): JsonResponse
    {
        $kiosk = $this->resolveKiosk($request);

        if (!$kiosk || (int) $printJob->kiosk_id !== (int) $kiosk->id) {
            return $this->unauthorized();
        }

        if ($printJob->status !== 'printing') {
            return response()->json([
                'success' => false,
                'message' => 'El trabajo no está en cola de impresión.',
            ], 422);
        }

        $kiosk->update([
            'estado_conexion' => 'online',
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trabajo confirmado por el kiosko.',
            'data' => $this->jobPayload($printJob->load(['pdfFile', 'payment'])),
        ]);
    }

    protected function jobPayload(PrintJob $job): array
    {
        return [
            'id' => $job->id,
            'job_reference' => $job->job_reference,
            'kiosk_id' => $job->kiosk_id,
            'status' => $job->status,
            'copies' => $job->copies,
            'color_type' => $job->color_type,
            'paper_size' => $job->paper_size,
            'orientation' => $job->orientation,
            'cost' => $job->cost,
            'paid' => (bool) $job->paid,
            'printed_at' => optional($job->printed_at)?->toDateTimeString(),
            'created_at' => optional($job->created_at)?->toDateTimeString(),
            'pdf_file' => $job->pdfFile ? [
                'id' => $job->pdfFile->id,
                'original_name' => $job->pdfFile->original_name,
                'pages_count' => $job->pdfFile->pages_count,
                'download_url' => url("/api/kiosk/jobs/{$job->id}/pdf"),
            ] : null,
            'payment' => $job->payment ? [
                'id' => $job->payment->id,
                'reference_code' => $job->payment->reference_code,
                'amount' => $job->payment->amount,
                'status' => $job->payment->status,
            ] : null,
        ];
    }
}