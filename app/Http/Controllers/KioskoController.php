<?php

namespace App\Http\Controllers;

use App\Models\PdfFile;
use App\Models\Kiosk;
use App\Models\PrintJob;
use App\Models\Payment;
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

            return redirect()->route('kiosko.configure', $pdfFile->id)
                ->with('success', 'PDF subido correctamente. Contiene ' . $pages . ' páginas.');
        } catch (\Exception $e) {
            return back()->withErrors('Error al subir el PDF: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario de configuración de impresión.
     */
    public function configureForm(PdfFile $pdf)
    {
        // Calcular costos según configuración
        $costBW = config('printing.cost_bw', 0.05);
        $costColor = config('printing.cost_color', 0.20);
        $kiosks = Kiosk::orderBy('nombre')->get(['id', 'nombre', 'ubicacion', 'estado_conexion']);
        $defaultKioskId = session('default_kiosk_id');
        $defaultKioskLocation = session('default_kiosk_location');

        return view('kiosko.configure', [
            'pdf' => $pdf,
            'costBW' => $costBW,
            'costColor' => $costColor,
            'kiosks' => $kiosks,
            'defaultKioskId' => $defaultKioskId,
            'defaultKioskLocation' => $defaultKioskLocation,
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
            'kiosk_id' => 'nullable|exists:kiosks,id',
        ]);

        $resolvedKioskId = $this->resolveKioskId(
            $request->input('kiosk_id'),
            session('default_kiosk_location')
        );

        if (!$resolvedKioskId) {
            return back()->withErrors('No hay kioskos disponibles para asignar este trabajo.')->withInput();
        }

        // Calcular costo
        $costBW = config('printing.cost_bw', 0.05);
        $costColor = config('printing.cost_color', 0.20);
        $costPerPage = $request->color_type === 'color' ? $costColor : $costBW;
        $totalCost = $pdf->pages_count * (int)$request->copies * $costPerPage;

        // Crear trabajo de impresión
        $printJob = PrintJob::create([
            'job_reference' => PrintJob::generateJobReference($pdf->original_name),
            'kiosk_id' => $resolvedKioskId,
            'pdf_file_id' => $pdf->id,
            'email' => $pdf->email,
            'copies' => (int)$request->copies,
            'color_type' => $request->color_type,
            'paper_size' => $request->paper_size,
            'orientation' => $request->orientation,
            'cost' => $totalCost,
            'status' => 'pending',
            'paid' => false,
        ]);

        // Generar código de pago
        $referenceCode = Payment::generateReferenceCode();

        // Crear registro de pago
        $payment = Payment::create([
            'print_job_id' => $printJob->id,
            'kiosk_id' => $printJob->kiosk_id,
            'reference_code' => $referenceCode,
            'amount' => $totalCost,
            'status' => 'pending',
        ]);

        return redirect()->route('kiosko.payment', $printJob->id)
            ->with('success', 'Trabajo creado. Proceda con el pago.');
    }

    /**
     * Mostrar detalles del pago.
     */
    public function paymentForm(PrintJob $printJob)
    {
        $payment = $printJob->payment;
        
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
        $printJob = PrintJob::where('job_reference', $jobReference)
            ->with('payment', 'pdfFile')
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
            'job_reference' => 'required|string|max:20',
        ]);

        $printJob = PrintJob::where('job_reference', strtoupper($request->job_reference))
            ->with('payment', 'pdfFile')
            ->first();

        if (!$printJob) {
            return back()->withErrors('Trabajo no encontrado. Verifique el código de referencia.');
        }

        return redirect()->route('kiosko.status', $printJob->job_reference);
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
    public function generateKioskQr(Kiosk $kiosk)
    {
        $whatsappNumber = config('evolution.whatsapp_number', '+1234567890');
        $location = trim((string) ($kiosk->ubicacion ?? ''));
        $label = trim($kiosk->nombre . ($location !== '' ? ' - ' . $location : ''));
        $whatsappMessage = "Estoy en {$label}";

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
    public function releaseWithPin(Request $request, PrintJob $printJob)
    {
        $request->validate([
            'pin' => 'required|string|digits:4',
        ]);

        // Buscamos al administrador principal
        $admin = \App\Models\User::where('email', 'admin@kiosko.com')->first();

        if (!$admin || $admin->pin !== $request->pin) {
            return response()->json([
                'success' => false,
                'message' => 'PIN de administrador incorrecto.'
            ], 403);
        }

        // Marcar como pagado y listo para imprimir
        $printJob->update([
            'status' => 'printing',
            'paid' => true
        ]);

        if ($printJob->payment) {
            $printJob->payment->update(['status' => 'confirmed']);
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

        if (Kiosk::whereKey((int) $kioskId)->exists()) {
            session(['default_kiosk_id' => (int) $kioskId]);
        }

        if ($kioskLocation !== '') {
            session(['default_kiosk_location' => $kioskLocation]);
        }
    }

    /**
     * Resuelve kiosko usando prioridad: manual > ubicación de sesión > kiosko online de la misma ubicación > cualquier kiosko online > cualquiera.
     */
    private function resolveKioskId(?string $manualKioskId, ?string $preferredLocation = null): ?int
    {
        if ($manualKioskId && Kiosk::whereKey((int) $manualKioskId)->exists()) {
            return (int) $manualKioskId;
        }

        $sessionKioskId = session('default_kiosk_id');
        if ($sessionKioskId && Kiosk::whereKey((int) $sessionKioskId)->exists()) {
            return (int) $sessionKioskId;
        }

        $location = trim((string) ($preferredLocation ?? ''));

        if ($location !== '') {
            $locationMatched = Kiosk::query()
                ->where('estado_conexion', 'online')
                ->where(function ($query) use ($location) {
                    $query->where('ubicacion', $location)
                        ->orWhere('ubicacion', 'like', '%' . $location . '%');
                })
                ->withCount([
                    'printJobs as active_jobs_count' => function ($query) {
                        $query->whereIn('status', ['pending', 'printing']);
                    }
                ])
                ->orderBy('active_jobs_count')
                ->orderByDesc('last_seen_at')
                ->first();

            if ($locationMatched) {
                return (int) $locationMatched->id;
            }
        }

        $onlineLeastLoaded = Kiosk::query()
            ->where('estado_conexion', 'online')
            ->withCount([
                'printJobs as active_jobs_count' => function ($query) {
                    $query->whereIn('status', ['pending', 'printing']);
                }
            ])
            ->orderBy('active_jobs_count')
            ->orderByDesc('last_seen_at')
            ->first();

        if ($onlineLeastLoaded) {
            return (int) $onlineLeastLoaded->id;
        }

        $fallback = Kiosk::orderBy('nombre')->first();

        return $fallback ? (int) $fallback->id : null;
    }
}
