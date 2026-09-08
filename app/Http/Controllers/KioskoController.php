<?php

namespace App\Http\Controllers;

use App\Models\PdfFile;
use App\Models\Kiosko;
use App\Models\OrdenImpresion;
use App\Models\TransaccionPago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class KioskoController extends Controller
{
    /**
     * Mostrar página de inicio (landing de negocio, no tiene flujo de cliente).
     */
    public function index()
    {
        return view('kiosko.index');
    }

    /**
     * Entrar directamente a un kiosko especifico via su URL fija (/k/{slug}).
     * Esta es la unica forma de llegar a la pagina de subida: evita que el
     * cliente elija el lugar equivocado o caiga en una pagina generica sin kiosko.
     */
    public function enterKiosk(string $slug)
    {
        $kiosko = Kiosko::where('slug', $slug)->first();

        if (!$kiosko) {
            return redirect()->route('kiosko.index')
                ->withErrors(['kiosk' => 'No encontramos ese kiosko. Verifica el enlace con el encargado del local.']);
        }

        session([
            'default_kiosk_id' => $kiosko->id,
            'default_kiosk_location' => $kiosko->nombre_comercial,
        ]);

        return view('kiosko.upload', ['activeKiosk' => $kiosko]);
    }

    /**
     * Guardar archivo subido (PDF, Word, PPT o Imagen) convirtiéndolo a PDF si es necesario.
     */
    public function uploadPdf(Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|max:20480|mimes:pdf,docx,doc,pptx,ppt,jpg,jpeg,png,webp',
            'email' => 'nullable|email',
        ], [
            'pdf.mimes' => 'Solo se permiten archivos PDF, documentos Word (.docx/.doc), PowerPoint (.pptx) o imágenes (.jpg/.png).',
            'pdf.max' => 'El archivo no puede exceder 20 MB.',
        ]);

        try {
            $file = $request->file('pdf');

            // Procesar y convertir a PDF si es necesario (imágenes, office)
            $conversionService = app(\App\Services\DocumentConversionService::class);
            $conversionResult = $conversionService->convertToPdf($file);
            $pdfSourcePath = $conversionResult['path'];

            $filename = uniqid() . '_' . time() . '.pdf';
            $storageDest = storage_path('app/public/pdfs/' . $filename);

            if (!file_exists(dirname($storageDest))) {
                mkdir(dirname($storageDest), 0755, true);
            }

            copy($pdfSourcePath, $storageDest);
            $relativePath = 'pdfs/' . $filename;

            // Si fue un archivo temporal generado en la conversión, limpiarlo
            if ($conversionResult['converted'] && file_exists($pdfSourcePath)) {
                @unlink($pdfSourcePath);
            }

            // Contar páginas
            $parser = new Parser();
            $document = $parser->parseFile($storageDest);
            $pages = max(1, count($document->getPages()));

            // Subir a Supabase Storage si está configurado
            $supabasePath = null;
            try {
                $supabasePath = app(\App\Services\SupabaseStorageService::class)
                    ->upload(file_get_contents($storageDest), $filename);
            } catch (\Exception $e) {
                // Almacenamiento local disponible como fallback
            }

            // Guardar en base de datos
            $pdfFile = PdfFile::create([
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'email' => $request->email,
                'pages_count' => $pages,
                'file_path' => $relativePath,
                'supabase_path' => $supabasePath,
                'file_size' => filesize($storageDest) / 1024, // en KB
            ]);

            $successMsg = $conversionResult['converted']
                ? "Archivo {$file->getClientOriginalName()} convertido a PDF correctamente ({$pages} pág)."
                : "PDF subido correctamente. Contiene {$pages} página(s).";

            return redirect()->route('kiosko.configure', ['pdf' => $pdfFile->id])
                ->with('success', $successMsg);
        } catch (\Exception $e) {
            return back()->withErrors('Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario de configuración de impresión.
     */
    public function configureForm(Request $request, PdfFile $pdf, $kioskoId = null)
    {
        $kiosks = Kiosko::orderBy('nombre_comercial')->get(['id', 'nombre_comercial', 'estado']);
        
        $selectedKiosk = null;
        if ($kioskoId) {
            $selectedKiosk = Kiosko::find($kioskoId);
        } elseif ($request->query('kiosk')) {
            $selectedKiosk = Kiosko::find($request->query('kiosk'));
        } elseif (session('default_kiosk_id')) {
            $selectedKiosk = Kiosko::find(session('default_kiosk_id'));
        }

        // Precios por defecto o los de la sede elegida
        $costBW = $selectedKiosk ? (float)$selectedKiosk->precio_blanco_negro : (float)config('printing.cost_bw', 0.05);
        $costColor = $selectedKiosk ? (float)$selectedKiosk->precio_color : (float)config('printing.cost_color', 0.20);
        $admiteDuplex = $selectedKiosk ? (bool)$selectedKiosk->admite_duplex : false;

        return view('kiosko.configure', [
            'pdf' => $pdf,
            'costBW' => $costBW,
            'costColor' => $costColor,
            'admiteDuplex' => $admiteDuplex,
            'kiosks' => $kiosks,
            'defaultKioskId' => $selectedKiosk ? $selectedKiosk->id : null,
            'defaultKioskLocation' => $selectedKiosk ? $selectedKiosk->nombre_comercial : session('default_kiosk_location'),
        ]);
    }

    /**
     * Crear trabajo de impresión.
     */
    public function createPrintJob(Request $request, PdfFile $pdf)
    {
        $request->validate([
            'copies' => 'required|integer|min:1|max:999',
            'color_type' => 'required|in:bw,color',
            'paper_size' => 'required|in:a4,letter,legal',
            'orientation' => 'required|in:portrait,landscape',
            'duplex' => 'nullable',
            'delivery_mode' => 'nullable|in:inmediato,pin_retiro',
            'page_selection' => 'nullable|in:all,custom',
            'custom_pages' => 'nullable|string|max:50',
            'kiosk_id' => 'nullable|exists:kioskos,id',
        ]);

        $resolvedKioskId = $this->resolveKioskId(
            $request->input('kiosk_id'),
            session('default_kiosk_location')
        );

        if (!$resolvedKioskId) {
            return back()->withErrors('No hay kioskos disponibles para asignar este trabajo.')->withInput();
        }

        $kiosko = Kiosko::findOrFail($resolvedKioskId);

        // Resolver cuántas páginas únicas se van a imprimir realmente (respeta el rango personalizado)
        $rangoPaginas = null;
        $paginasAImprimir = $pdf->pages_count;

        if ($request->input('page_selection') === 'custom' && $request->filled('custom_pages')) {
            $rangoPaginas = preg_replace('/\s+/', '', $request->input('custom_pages'));
            $paginasEnRango = $this->contarPaginasEnRango($rangoPaginas, $pdf->pages_count);

            // Si el rango no resulta en ninguna página válida, no cobramos por algo que no existe.
            if ($paginasEnRango > 0) {
                $paginasAImprimir = $paginasEnRango;
            } else {
                $rangoPaginas = null;
            }
        }

        $copias = (int) $request->copies;

        // Calcular costo (en base a las páginas reales a imprimir, no siempre el PDF completo)
        $costBW = (float)$kiosko->precio_blanco_negro;
        $costColor = (float)$kiosko->precio_color;
        $costPerPage = $request->color_type === 'color' ? $costColor : $costBW;
        $totalCost = $paginasAImprimir * $copias * $costPerPage;

        // Registrar o encontrar el Cliente
        $cliente = \App\Models\Cliente::firstOrCreate(
            ['telefono' => 'web_guest'],
            ['nombre' => 'Invitado Web']
        );

        // Crear Orden de Impresión
        $archivoUrl = $pdf->supabase_path
            ? app(\App\Services\SupabaseStorageService::class)->publicUrl($pdf->supabase_path)
            : asset('storage/' . $pdf->file_path);

        $isDuplex = $kiosko->admite_duplex && filter_var($request->input('duplex'), FILTER_VALIDATE_BOOLEAN);
        $deliveryMode = $request->input('delivery_mode', 'inmediato');
        $pinRetiro = ($deliveryMode === 'pin_retiro')
            ? str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT)
            : null;

        $orden = OrdenImpresion::create([
            'kiosko_id' => $kiosko->id,
            'cliente_id' => $cliente->id,
            'archivo_url' => $archivoUrl,
            'paginas' => $paginasAImprimir * $copias,
            'copias' => $copias,
            'rango_paginas' => $rangoPaginas,
            'papel' => $request->paper_size,
            'orientacion' => $request->orientation,
            'color' => $request->color_type === 'color' ? 1 : 0,
            'duplex' => $isDuplex,
            'modo_entrega' => $deliveryMode,
            'pin_retiro' => $pinRetiro,
            'costo_total' => $totalCost,
            'estado' => 'pendiente',
        ]);

        // Crear Transacción de Pago
        $payment = TransaccionPago::create([
            'orden_id' => $orden->id,
            'monto' => $totalCost,
            'metodo' => 'Deuna',
            'estado' => 'pendiente',
        ]);

        return redirect()->route('kiosko.payment', $orden->id)
            ->with('success', 'Trabajo creado. Proceda con el pago.');
    }

    /**
     * Mostrar detalles del pago.
     */
    public function paymentForm(OrdenImpresion $printJob)
    {
        $payment = $printJob->transacciones()->first();
        
        if (!$payment) {
            return back()->withErrors('Pago no encontrado.');
        }

        $deunaData = app(\App\Services\DeunaService::class)->generatePaymentData($printJob, $payment);

        return view('kiosko.payment', [
            'printJob' => $printJob,
            'payment' => $payment,
            'deunaData' => $deunaData,
        ]);
    }

    /**
     * Verificar estado de la orden en tiempo real para polling (AJAX).
     */
    public function checkOrderStatus(OrdenImpresion $printJob)
    {
        $payment = $printJob->transacciones()->latest()->first();

        return response()->json([
            'id' => $printJob->id,
            'estado' => $printJob->estado,
            'paid' => in_array($printJob->estado, ['pagado', 'esperando_retiro', 'imprimiendo', 'completado']),
            'modo_entrega' => $printJob->modo_entrega,
            'pin_retiro' => $printJob->pin_retiro,
            'payment_status' => $payment ? $payment->estado : null,
            'redirect_url' => route('kiosko.status', $printJob->id),
        ]);
    }

    /**
     * Liberar orden para imprimir inmediatamente (presionado desde el móvil o al llegar).
     */
    public function releaseNow(OrdenImpresion $printJob)
    {
        if (in_array($printJob->estado, ['esperando_retiro', 'pagado'])) {
            $printJob->update([
                'estado' => 'pagado',
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Orden enviada a la impresora! Saliendo en este momento.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'La orden no está en un estado que permita liberación.',
        ], 400);
    }

    /**
     * Simulación de pago con DeUna para pruebas y demostraciones instantáneas.
     */
    public function simulateDeunaPayment(OrdenImpresion $printJob)
    {
        $deunaService = app(\App\Services\DeunaService::class);
        $result = $deunaService->processWebhook([
            'order_id' => $printJob->id,
            'status' => 'PAID',
            'transaction_id' => 'DEMO-' . strtoupper(substr(md5((string) time()), 0, 8)),
        ]);

        return response()->json($result);
    }

    /**
     * Guardar la referencia de transferencia del usuario.
     */
    public function saveReference(Request $request, OrdenImpresion $printJob)
    {
        $request->validate([
            'referencia' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\p{N}\-_ ]+$/u'],
        ]);

        $payment = $printJob->transacciones()->first();
        if ($payment) {
            $payment->update([
                'referencia_usuario' => $request->referencia,
            ]);
        }

        return back()->with('success', 'Referencia guardada. El sistema está verificando tu pago.');
    }

    /**
     * Mostrar estado del trabajo de impresión.
     */
    public function status($jobReference)
    {
        $printJob = OrdenImpresion::with('transacciones', 'cliente', 'kiosko')
            ->where('id', $jobReference)
            ->firstOrFail();

        return view('kiosko.status', [
            'printJob' => $printJob,
        ]);
    }

    /**
     * Mostrar formulario de búsqueda de trabajo.
     */
    public function searchForm()
    {
        return view('kiosko.search');
    }

    /**
     * Buscar trabajo por referencia (para la pantalla de estado).
     */
    public function searchJob(Request $request)
    {
        $request->validate([
            'job_reference' => 'required|string',
        ]);

        $printJob = OrdenImpresion::where('id', trim($request->job_reference))
            ->first();

        if (!$printJob) {
            return back()->withErrors('Trabajo no encontrado. Verifique el código de referencia.');
        }

        return redirect()->route('kiosko.status', $printJob->id);
    }

    /**
     * Generar código QR para WhatsApp.
     */
    public function generateQr()
    {
        $whatsappNumber = config('evolution.whatsapp_number', '+1234567890');
        $whatsappMessage = config('evolution.whatsapp_message', 'Hola, quiero imprimir un PDF');

        $cleanNumber = str_replace(['+', ' ', '-'], '', $whatsappNumber);
        $whatsappLink = "https://wa.me/{$cleanNumber}?text=" . rawurlencode($whatsappMessage);

        $qrCode = new QrCode($whatsappLink);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return response($result->getString())
            ->header('Content-Type', 'image/png');
    }

    /**
     * Generar código QR específico para un kiosko.
     */
    public function generateKioskQr(Kiosko $kiosk)
    {
        $whatsappNumber = config('evolution.whatsapp_number', '+1234567890');
        $location = trim((string) ($kiosk->nombre_comercial ?? ''));
        $whatsappMessage = "Estoy en {$location}";

        $cleanNumber = str_replace(['+', ' ', '-'], '', $whatsappNumber);
        $whatsappLink = "https://wa.me/{$cleanNumber}?text=" . rawurlencode($whatsappMessage);

        $qrCode = new QrCode($whatsappLink);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return response($result->getString())
            ->header('Content-Type', 'image/png');
    }

    /**
     * Liberar un trabajo manualmente usando el PIN del administrador.
     */
    public function releaseWithPin(Request $request, OrdenImpresion $printJob)
    {
        $request->validate([
            'pin' => 'required|string|digits:4',
        ]);

        $kiosko = $printJob->kiosko;

        if (!$kiosko || $kiosko->pin !== $request->pin) {
            return response()->json([
                'success' => false,
                'message' => 'PIN de kiosko incorrecto.'
            ], 403);
        }

        // Marcar como pagado
        $printJob->update([
            'estado' => 'pagado',
        ]);

        $payment = $printJob->transacciones()->first();
        if ($payment) {
            $payment->update(['estado' => 'completado']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Trabajo liberado correctamente.'
        ]);
    }


    /**
     * Cuenta cuántas páginas únicas y válidas (entre 1 y $totalPaginas) hay en un
     * rango tipo "1-5,8". Misma lógica que parsePageRange() en configure.blade.php,
     * pero del lado del servidor: nunca confiamos en el total calculado por el cliente.
     */
    private function contarPaginasEnRango(string $rango, int $totalPaginas): int
    {
        $paginas = [];

        foreach (explode(',', $rango) as $parte) {
            $parte = trim($parte);
            if ($parte === '') {
                continue;
            }

            if (str_contains($parte, '-')) {
                [$inicio, $fin] = array_pad(explode('-', $parte, 2), 2, null);
                $inicio = (int) $inicio;
                $fin = (int) $fin;
                if ($inicio <= 0 || $fin <= 0) {
                    continue;
                }
                for ($i = min($inicio, $fin); $i <= max($inicio, $fin); $i++) {
                    if ($i >= 1 && $i <= $totalPaginas) {
                        $paginas[$i] = true;
                    }
                }
            } else {
                $n = (int) $parte;
                if ($n >= 1 && $n <= $totalPaginas) {
                    $paginas[$n] = true;
                }
            }
        }

        return count($paginas);
    }

    /**
     * Resuelve kiosko usando prioridad: manual > ubicación de sesión > kiosko online de la misma ubicación > cualquier kiosko online > cualquiera.
     */
    private function resolveKioskId(?string $manualKioskId, ?string $preferredLocation = null): ?string
    {
        if ($manualKioskId && Kiosko::whereKey($manualKioskId)->exists()) {
            return $manualKioskId;
        }

        $sessionKioskId = session('default_kiosk_id');
        if ($sessionKioskId && Kiosko::whereKey($sessionKioskId)->exists()) {
            return $sessionKioskId;
        }

        $location = trim((string) ($preferredLocation ?? ''));

        if ($location !== '') {
            $locationMatched = Kiosko::query()
                ->where('estado', 'activo')
                ->where(function ($query) use ($location) {
                    $query->where('nombre_comercial', $location)
                        ->orWhere('nombre_comercial', 'like', '%' . $location . '%');
                })
                ->first();

            if ($locationMatched) {
                return $locationMatched->id;
            }
        }

        $activeLeastLoaded = Kiosko::query()
            ->where('estado', 'activo')
            ->first();

        if ($activeLeastLoaded) {
            return $activeLeastLoaded->id;
        }

        $fallback = Kiosko::orderBy('nombre_comercial')->first();

        return $fallback ? $fallback->id : null;
    }

    public function poster(Kiosko $kiosk)
    {
        return view('admin.kiosk-poster', ['kiosk' => $kiosk]);
    }
}
