<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kiosko;
use App\Models\OrdenImpresion;
use App\Models\TransaccionPago;
use App\Services\EvolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class KioskApiController extends Controller
{
    protected function resolveKiosk(Request $request): ?Kiosko
    {
        // El token es un secreto propio (api_token), separado del UUID publico del
        // kiosko: el UUID aparece en HTML publico (formulario de configuracion) y en
        // rutas publicas de poster/QR, asi que nunca debe servir como credencial.
        $token = (string) $request->header('X-Kiosk-Token', $request->input('api_token', ''));

        if (empty($token)) {
            return null;
        }

        return Kiosko::where('api_token', $token)->first();
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
            'estado' => 'activo',
            'ultima_conexion' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $kiosk->id,
                'nombre' => $kiosk->nombre_comercial,
                'estado' => $kiosk->estado,
                'nombre_cups' => $kiosk->nombre_cups,
                'ultima_conexion' => optional($kiosk->ultima_conexion)->toDateTimeString(),
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
            'estado' => 'activo',
            'ultima_conexion' => now(),
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

        // Obtener órdenes pagadas que están listas para imprimirse o en proceso
        $jobs = OrdenImpresion::with(['cliente'])
            ->where('kiosko_id', $kiosk->id)
            ->whereIn('estado', ['pagado', 'imprimiendo'])
            ->orderBy('created_at')
            ->get()
            ->map(fn (OrdenImpresion $job) => $this->jobPayload($job));

        $kiosk->update([
            'estado' => 'activo',
            'ultima_conexion' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    public function showJob(Request $request, OrdenImpresion $printJob): JsonResponse
    {
        $kiosk = $this->resolveKiosk($request);

        if (!$kiosk || $printJob->kiosko_id !== $kiosk->id) {
            return $this->unauthorized();
        }

        return response()->json([
            'success' => true,
            'data' => $this->jobPayload($printJob),
        ]);
    }

    public function downloadPdf(Request $request, OrdenImpresion $printJob)
    {
        $kiosk = $this->resolveKiosk($request);

        if (!$kiosk || $printJob->kiosko_id !== $kiosk->id) {
            return $this->unauthorized();
        }

        // Comparamos el host de la URL guardada contra nuestro propio host (config('app.url'),
        // no asset()/url() que dependen de la petición actual y pueden no coincidir como texto
        // aunque sean "el mismo" host) para decidir si el archivo es local o externo (Supabase).
        $archivoHost = parse_url($printJob->archivo_url, PHP_URL_HOST);
        $ourHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $isExternal = $archivoHost !== null && $archivoHost !== $ourHost;

        if ($isExternal) {
            // El agente local la descarga directamente desde la URL pública de Supabase.
            // Para mantener compatibilidad con este endpoint redireccionamos:
            return redirect()->away($printJob->archivo_url);
        }

        $filePath = ltrim((string) parse_url($printJob->archivo_url, PHP_URL_PATH), '/');
        $filePath = preg_replace('#^storage/#', '', $filePath);

        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo no encontrado localmente.',
            ], 404);
        }

        return Storage::disk('public')->download($filePath);
    }

    public function completeJob(Request $request, OrdenImpresion $printJob): JsonResponse
    {
        $kiosk = $this->resolveKiosk($request);

        if (!$kiosk || $printJob->kiosko_id !== $kiosk->id) {
            return $this->unauthorized();
        }

        $printJob->update([
            'estado' => 'completado',
        ]);

        if ($printJob->cliente && $printJob->cliente->telefono !== 'web_guest') {
            app(\App\Services\EvolutionService::class)->sendMessage(
                $printJob->cliente->telefono,
                "🎉 *¡Trabajo completado!*\nTu documento ya salió de la impresora y está listo. ¡Gracias por usar nuestro kiosko!"
            );
        }

        // Confirmar la transacción asociada si existe
        TransaccionPago::where('orden_id', $printJob->id)->update([
            'estado' => 'completado',
        ]);

        $kiosk->update([
            'estado' => 'activo',
            'ultima_conexion' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trabajo completado correctamente.',
            'data' => $this->jobPayload($printJob),
        ]);
    }

    public function markPrinting(Request $request, OrdenImpresion $printJob): JsonResponse
    {
        $kiosk = $this->resolveKiosk($request);

        if (!$kiosk || $printJob->kiosko_id !== $kiosk->id) {
            return $this->unauthorized();
        }

        $printJob->update([
            'estado' => 'imprimiendo',
        ]);

        $kiosk->update([
            'estado' => 'activo',
            'ultima_conexion' => now(),
        ]);

        if ($printJob->cliente && $printJob->cliente->telefono !== 'web_guest') {
            app(\App\Services\EvolutionService::class)->sendMessage(
                $printJob->cliente->telefono,
                "🖨️ *Imprimiendo...*\nTu documento está saliendo en este momento de la impresora."
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Trabajo marcado como imprimiendo.',
            'data' => $this->jobPayload($printJob),
        ]);
    }

    /**
     * Reporta un error desde el kiosko (ej. error de impresora).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $printJob
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportError(Request $request, $printJobId)
    {
        $kiosk = $this->resolveKiosk($request);
        $printJob = OrdenImpresion::findOrFail($printJobId);

        if (!$kiosk || $printJob->kiosko_id !== $kiosk->id) {
            return $this->unauthorized();
        }

        $errorMsg = $request->input('error', 'Error desconocido');

        $printJob->update([
            'estado' => 'error'
        ]);

        Log::error("Error reportado por Kiosko {$kiosk->nombre} en Orden {$printJob->id}: {$errorMsg}");

        try {
            $evolutionService = app(\App\Services\EvolutionService::class);
            $adminPhone = env('ADMIN_PHONE');
            
            if ($adminPhone) {
                $evolutionService->sendMessage($adminPhone, "🚨 *ALERTA KIOSKO*\nKiosko: {$kiosk->nombre}\nOrden: {$printJob->id}\nError: {$errorMsg}");
            }

            if ($printJob->cliente && $printJob->cliente->telefono && $printJob->cliente->telefono !== 'web_guest') {
                $evolutionService->sendMessage($printJob->cliente->telefono, "⚠️ Tuvimos un inconveniente técnico al intentar imprimir tu documento. El administrador ya ha sido notificado y lo resolverá en breve. Tu orden está a salvo.");
            }
        } catch (\Exception $e) {
            Log::error("Fallo al enviar notificación de WhatsApp sobre el error: " . $e->getMessage());
        }

        return response()->json([
            'message' => 'Error reportado con éxito'
        ]);
    }

    protected function jobPayload(OrdenImpresion $job): array
    {
        return [
            'id' => $job->id,
            'job_reference' => $job->id, // Usamos el ID de la orden como referencia
            'kiosk_id' => $job->kiosko_id,
            'status' => $job->estado,
            'copies' => $job->copias ?? 1,
            'pages_range' => $job->rango_paginas, // null = imprimir todo el documento
            'color_type' => $job->color ? 'color' : 'bw',
            'paper_size' => $job->papel ?? 'a4',
            'orientation' => $job->orientacion ?? 'portrait',
            'cost' => $job->costo_total,
            'paid' => in_array($job->estado, ['pagado', 'imprimiendo', 'completado']),
            'created_at' => optional($job->created_at)->toDateTimeString(),
            'pdf_file' => [
                'id' => $job->id,
                'original_name' => 'documento.pdf',
                'pages_count' => $job->paginas,
                'download_url' => $job->archivo_url,
            ],
        ];
    }
}