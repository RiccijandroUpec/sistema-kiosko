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
     * Mostrar página de inicio del kiosko.
     */
    public function index()
    {
        return view('kiosko.index');
    }

    /**
     * Mostrar formulario de subida de PDF.
     */
    public function uploadForm(Request $request)
    {
        $this->captureKioskHintFromRequest($request);

        return view('kiosko.upload');
    }

    /**
     * Guardar PDF subido.
     */
    public function uploadPdf(Request $request)
    {
        $request->validate([
            'pdf' => 'required|mimes:pdf|max:10240', // 10MB
            'email' => 'nullable|email',
        ]);

        try {
            $file = $request->file('pdf');
            $filename = uniqid() . '_' . time() . '.pdf';
            $path = $file->storeAs('pdfs', $filename, 'public');

            // Contar páginas
            $parser = new Parser();
            $document = $parser->parseFile(storage_path('app/public/' . $path));
            $pages = count($document->getPages());

            // Guardar en base de datos
            $pdfFile = PdfFile::create([
                'filename' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'email' => $request->email,
                'pages_count' => $pages,
                'file_path' => $path,
                'file_size' => $file->getSize() / 1024, // en KB
            ]);

            return redirect()->route('kiosko.configure', ['pdf' => $pdfFile->id])
                ->with('success', 'PDF subido correctamente. Contiene ' . $pages . ' páginas.');
        } catch (\Exception $e) {
            return back()->withErrors('Error al subir el PDF: ' . $e->getMessage());
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

        return view('kiosko.configure', [
            'pdf' => $pdf,
            'costBW' => $costBW,
            'costColor' => $costColor,
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

        // Calcular costo
        $costBW = (float)$kiosko->precio_blanco_negro;
        $costColor = (float)$kiosko->precio_color;
        $costPerPage = $request->color_type === 'color' ? $costColor : $costBW;
        $totalCost = $pdf->pages_count * (int)$request->copies * $costPerPage;

        // Registrar o encontrar el Cliente
        $cliente = \App\Models\Cliente::firstOrCreate(
            ['telefono' => 'web_guest'],
            ['nombre' => 'Invitado Web']
        );

        // Crear Orden de Impresión
        $orden = OrdenImpresion::create([
            'kiosko_id' => $kiosko->id,
            'cliente_id' => $cliente->id,
            'archivo_url' => asset('storage/' . $pdf->file_path),
            'paginas' => $pdf->pages_count * (int)$request->copies,
            'color' => $request->color_type === 'color',
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

        return view('kiosko.payment', [
            'printJob' => $printJob,
            'payment' => $payment,
        ]);
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
     * Guarda una pista de kiosko (query param) para autoasignar en el flujo web.
     */
    private function captureKioskHintFromRequest(Request $request): void
    {
        $kioskId = $request->query('kiosk');
        $kioskLocation = trim((string) $request->query('ubicacion', ''));

        if (!$kioskId) {
            if ($kioskLocation !== '') {
                session(['default_kiosk_location' => $kioskLocation]);
            }

            return;
        }

        if (Kiosko::whereKey($kioskId)->exists()) {
            session(['default_kiosk_id' => $kioskId]);
        }

        if ($kioskLocation !== '') {
            session(['default_kiosk_location' => $kioskLocation]);
        }
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
}
