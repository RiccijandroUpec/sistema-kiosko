<?php

namespace App\Http\Controllers;

use App\Models\Kiosk;
use App\Models\PdfFile;
use App\Models\PrintJob;
use App\Models\Payment;
use App\Services\EvolutionService;
use App\Services\DeepseekService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class WhatsAppController extends Controller
{
    protected EvolutionService $evolutionService;
    protected DeepseekService $deepseekService;

    public function __construct(
        EvolutionService $evolutionService,
        DeepseekService $deepseekService
    ) {
        $this->evolutionService = $evolutionService;
        $this->deepseekService = $deepseekService;
    }

    /**
     * Webhook para recibir mensajes de Evolution API
     */
    public function webhook(Request $request)
    {
        Log::emergency('!!!!! WEBHOOK ACTIVADO - EL MENSAJE LLEGÓ !!!!!');
        $payload = $request->all();
        
        // Log para depuración
        Log::info('Incoming Evolution Webhook', ['event' => $payload['event'] ?? 'unknown']);

        // Solo procesamos mensajes nuevos (messages.upsert)
        if (($payload['event'] ?? '') !== 'messages.upsert') {
            return response()->json(['status' => 'ignored']);
        }

        return $this->handleIncomingMessage($payload['data'] ?? []);
    }

    /**
     * Procesar mensajes entrantes de Evolution API
     */
    protected function handleIncomingMessage(array $data)
    {
        try {
            $key = $data['key'] ?? [];
            $message = $data['message'] ?? [];
            $fromJid = $key['remoteJid'] ?? '';
            $messageId = $key['id'] ?? '';

            if (!$fromJid || str_contains($fromJid, '@g.us')) {
                // Ignorar si no hay remitente o si es un grupo
                return response()->json(['status' => 'ignored']);
            }

            $from = explode('@', $fromJid)[0];

            // 1. Mensaje de Texto
            $text = $message['conversation'] 
                 ?? $message['extendedTextMessage']['text'] 
                 ?? $message['imageMessage']['caption'] 
                 ?? $message['videoMessage']['caption'] 
                 ?? '';

            Log::info('Processing message from', ['from' => $from, 'text' => $text]);

            if (!empty($text)) {
                $this->handleTextMessage($from, $text);
            }

            // 2. Documento (PDF)
            if (isset($message['documentMessage'])) {
                $this->handleDocumentMessage($from, $messageId, $message['documentMessage']);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Evolution handle error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Manejar mensaje de texto con IA
     */
    protected function handleTextMessage(string $from, string $text)
    {
        $matchedKiosk = $this->captureKioskContextFromText($from, $text);

        if ($matchedKiosk) {
            $message = "✅ Sede detectada: *{$matchedKiosk->nombre}*";
            if (!empty($matchedKiosk->ubicacion)) {
                $message .= " ({$matchedKiosk->ubicacion})";
            }
            $message .= "\n\nAhora envíame tu PDF y lo asignaré automáticamente a esa sede.";

            $this->evolutionService->sendMessage($from, $message);
            return;
        }

        $state = $this->getSelectionState($from);

        if ($state && ($state['step'] ?? null) === 'awaiting_kiosk_selection') {
            $this->handleKioskSelection($from, $text, $state);
            return;
        }

        if ($state && ($state['step'] ?? null) === 'awaiting_print_config') {
            $this->handlePrintConfigMessage($from, $text, $state);
            return;
        }

        // Obtener respuesta de la IA (Deepseek)
        Log::info('Asking AI...', ['text' => $text]);
        $aiResponse = $this->deepseekService->chat($text);
        Log::info('AI Response received', ['response' => $aiResponse]);

        // Buscar JSON en la respuesta de la IA (para configuración automática)
        if (preg_match('/\{.*\}/s', $aiResponse, $matches)) {
            $jsonStr = $matches[0];
            $data = json_decode($jsonStr, true);

            if (isset($data['config'])) {
                $config = $data['config'];
                
                // Buscar el último PDF enviado por este número
                $lastPdf = PdfFile::where('email', $from)->orderBy('created_at', 'desc')->first();

                if ($lastPdf) {
                    $this->promptForKioskSelection($from, $lastPdf, $this->getKioskContext($from));
                    return;
                }
            }
        }

        // Si no hay config, enviar solo el texto de la IA
        $cleanResponse = trim(preg_replace('/\{.*\}/s', '', $aiResponse));
        $this->evolutionService->sendMessage($from, $cleanResponse ?: $aiResponse);
    }

    /**
     * Manejar documento (PDF)
     */
    protected function handleDocumentMessage(string $from, string $messageId, array $docMessage)
    {
        try {
            $fileName = $docMessage['fileName'] ?? 'documento.pdf';
            
            Log::info('PDF Document Message payload', ['messageId' => $messageId, 'doc' => $docMessage]);
            
            // Solo aceptamos PDFs
            if (!str_contains(strtolower($fileName), '.pdf') && ($docMessage['mimetype'] ?? '') !== 'application/pdf') {
                $this->evolutionService->sendMessage($from, "Lo siento, por ahora solo puedo procesar archivos PDF. 📄");
                return;
            }

            // Descargar el archivo usando el servicio de Evolution
            $fileContent = $this->evolutionService->downloadMedia($messageId);

            if (!$fileContent) {
                $this->evolutionService->sendMessage($from, "Hubo un problema al descargar tu archivo. ¿Podrías intentarlo de nuevo?");
                return;
            }

            // Guardar archivo físicamente
            $uniqueFileName = uniqid() . '_' . time() . '.pdf';
            $path = "pdfs/{$uniqueFileName}";
            Storage::disk('public')->put($path, $fileContent);

            // Contar páginas
            $parser = new Parser();
            $document = $parser->parseContent($fileContent);
            $pages = count($document->getPages());

            // Guardar en BD
            $pdfFile = PdfFile::create([
                'filename' => $uniqueFileName,
                'original_name' => $fileName,
                'email' => $from,
                'pages_count' => $pages,
                'file_path' => $path,
                'file_size' => strlen($fileContent) / 1024,
            ]);

            $this->promptForKioskSelection($from, $pdfFile, $this->getKioskContext($from));

        } catch (\Exception $e) {
            Log::error('Error processing PDF from Evolution', ['error' => $e->getMessage()]);
            $this->evolutionService->sendMessage($from, "No pude procesar ese PDF. Asegúrate de que no tenga contraseña.");
        }
    }

    protected function promptForKioskSelection(string $from, PdfFile $pdfFile, ?Kiosk $preferredKiosk = null): void
    {
        $kiosks = Kiosk::query()->orderBy('nombre')->get();

        if ($kiosks->isEmpty()) {
            $this->evolutionService->sendMessage($from, "📄 Recibí tu PDF, pero todavía no hay kioskos registrados para asignarlo.");
            return;
        }

        if ($preferredKiosk) {
            Cache::put($this->selectionStateKey($from), [
                'step' => 'awaiting_print_config',
                'pdf_id' => $pdfFile->id,
                'kiosk_id' => $preferredKiosk->id,
            ], now()->addMinutes(20));

            $preferredMessage = "✅ PDF recibido para la sede *{$preferredKiosk->nombre}*";
            if (!empty($preferredKiosk->ubicacion)) {
                $preferredMessage .= " ({$preferredKiosk->ubicacion})";
            }
            $preferredMessage .= "\n\nAhora dime cómo quieres imprimirlo. Ejemplos: '3 copias a color' o '2 copias blanco y negro'.";

            $this->evolutionService->sendMessage($from, $preferredMessage);
            return;
        }

        Cache::put($this->selectionStateKey($from), [
            'step' => 'awaiting_kiosk_selection',
            'pdf_id' => $pdfFile->id,
        ], now()->addMinutes(20));

        $this->evolutionService->sendMessage($from, $this->buildKioskPrompt($kiosks));
    }

    protected function handleKioskSelection(string $from, string $text, array $state): void
    {
        $pdfFile = PdfFile::find($state['pdf_id'] ?? null);
        $kiosks = Kiosk::query()->orderBy('nombre')->get();
        $kiosk = $this->resolveKioskSelection($text, $kiosks);

        if (!$pdfFile || $kiosks->isEmpty()) {
            $this->clearSelectionState($from);
            $this->evolutionService->sendMessage($from, "No pude recuperar el PDF o ya no hay kioskos disponibles. Vuelve a enviar el archivo.");
            return;
        }

        if (!$kiosk) {
            $this->evolutionService->sendMessage($from, $this->buildKioskPrompt($kiosks));
            return;
        }

        Cache::put($this->selectionStateKey($from), [
            'step' => 'awaiting_print_config',
            'pdf_id' => $pdfFile->id,
            'kiosk_id' => $kiosk->id,
        ], now()->addMinutes(20));

        $this->evolutionService->sendMessage($from, "✅ Sede seleccionada: *{$kiosk->nombre}*\n\nAhora dime cómo quieres imprimirlo. Ejemplos: '3 copias a color' o '2 copias blanco y negro'.");
    }

    protected function handlePrintConfigMessage(string $from, string $text, array $state): void
    {
        $pdfFile = PdfFile::find($state['pdf_id'] ?? null);
        $kiosk = Kiosk::find($state['kiosk_id'] ?? null);

        if (!$pdfFile || !$kiosk) {
            $this->clearSelectionState($from);
            $this->evolutionService->sendMessage($from, "No pude continuar con la configuración. Vuelve a enviar el PDF.");
            return;
        }

        Log::info('Asking AI for print config...', ['text' => $text]);
        $aiResponse = $this->deepseekService->chat($text);
        Log::info('Print config AI response', ['response' => $aiResponse]);

        if (!preg_match('/\{.*\}/s', $aiResponse, $matches)) {
            $this->evolutionService->sendMessage($from, "Todavía necesito que me indiques copias, color y demás opciones de impresión.");
            return;
        }

        $data = json_decode($matches[0], true);

        if (!isset($data['config'])) {
            $this->evolutionService->sendMessage($from, "No entendí la configuración. Intenta de nuevo con algo como '3 copias a color'.");
            return;
        }

        $this->createPrintJobFromConfig($pdfFile, $from, $data['config'], $kiosk);
    }

    protected function createPrintJobFromConfig(PdfFile $lastPdf, string $from, array $config, ?Kiosk $kiosk): void
    {
        $copies = max(1, (int) ($config['copies'] ?? 1));
        $colorType = ($config['color_type'] ?? 'bw') === 'color' ? 'color' : 'bw';

        $costBW = config('printing.cost_bw', 0.05);
        $costColor = config('printing.cost_color', 0.20);
        $costPerPage = $colorType === 'color' ? $costColor : $costBW;
        $totalCost = $lastPdf->pages_count * $copies * $costPerPage;

        $printJob = PrintJob::create([
            'job_reference' => PrintJob::generateJobReference($lastPdf->original_name),
            'kiosk_id' => $kiosk?->id,
            'pdf_file_id' => $lastPdf->id,
            'email' => $from,
            'copies' => $copies,
            'color_type' => $colorType,
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'cost' => $totalCost,
            'status' => 'pending',
            'paid' => false,
        ]);

        Payment::create([
            'print_job_id' => $printJob->id,
            'kiosk_id' => $kiosk?->id,
            'reference_code' => Payment::generateReferenceCode(),
            'amount' => $totalCost,
            'status' => 'pending',
        ]);

        $this->clearSelectionState($from);

        $kioskName = $kiosk?->nombre ?? 'la sede seleccionada';
        $this->evolutionService->sendMessage($from, "✅ ¡Impresión configurada para {$kioskName}!\n\n" .
            "📍 Ref: *{$printJob->job_reference}*\n" .
            "💰 Total: *$" . number_format($totalCost, 2, '.', '') . "*\n" .
            "📝 Detalle: {$copies} copias • " . ($colorType === 'color' ? 'COLOR' : 'B/N') . "\n\n" .
            "Puedes pagar usando el código QR en el kiosko.");
    }

    protected function resolveKioskSelection(string $text, $kiosks): ?Kiosk
    {
        $cleanText = trim(mb_strtolower($text));

        if ($cleanText === '') {
            return null;
        }

        if (ctype_digit($cleanText)) {
            $position = (int) $cleanText;
            $byPosition = $kiosks->values()->get($position - 1);
            if ($byPosition) {
                return $byPosition;
            }

            return Kiosk::find($position);
        }

        foreach ($kiosks as $kiosk) {
            $haystack = mb_strtolower(trim($kiosk->nombre . ' ' . ($kiosk->ubicacion ?? '')));
            if (str_contains($haystack, $cleanText)) {
                return $kiosk;
            }
        }

        return null;
    }

    protected function buildKioskPrompt($kiosks): string
    {
        $lines = $kiosks->values()->map(function (Kiosk $kiosk, int $index) {
            $position = $index + 1;
            $location = $kiosk->ubicacion ? " - {$kiosk->ubicacion}" : '';
            return "{$position}. {$kiosk->nombre}{$location}";
        })->implode("\n");

        return "📄 ¡He recibido tu archivo!\n\nAhora dime en qué sede estás:\n{$lines}\n\nResponde con el número o el nombre de la sede.";
    }

    protected function selectionStateKey(string $from): string
    {
        return 'whatsapp:kiosk-selection:' . $from;
    }

    protected function kioskContextKey(string $from): string
    {
        return 'whatsapp:kiosk-context:' . $from;
    }

    protected function captureKioskContextFromText(string $from, string $text): ?Kiosk
    {
        $normalized = mb_strtolower(trim($text));

        if ($normalized === '') {
            return null;
        }

        if (!preg_match('/\b(estoy en|estoy|sede|kiosko|kiosk)\b/u', $normalized)) {
            return null;
        }

        foreach (Kiosk::query()->get() as $kiosk) {
            $haystack = mb_strtolower(trim($kiosk->nombre . ' ' . ($kiosk->ubicacion ?? '')));
            if ($haystack !== '' && str_contains($normalized, $haystack)) {
                Cache::put($this->kioskContextKey($from), $kiosk->id, now()->addHours(12));
                return $kiosk;
            }
        }

        return null;
    }

    protected function getKioskContext(string $from): ?Kiosk
    {
        $kioskId = Cache::get($this->kioskContextKey($from));

        if (!$kioskId) {
            return null;
        }

        return Kiosk::find($kioskId);
    }

    protected function getSelectionState(string $from): ?array
    {
        $state = Cache::get($this->selectionStateKey($from));
        return is_array($state) ? $state : null;
    }

    protected function clearSelectionState(string $from): void
    {
        Cache::forget($this->selectionStateKey($from));
    }

    public function sendTestMessage(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string',
            'message' => 'nullable|string',
        ]);

        $sent = $this->evolutionService->sendMessage(
            $validated['phone'],
            $validated['message'] ?? 'Prueba de conexión del sistema central de kioskos.'
        );

        return response()->json([
            'success' => (bool) $sent,
        ]);
    }

    /**
     * Validar credenciales (usado por el Admin)
     */
    public function validateCredentials()
    {
        $valid = $this->evolutionService->validateCredentials();
        return response()->json([
            'valid' => $valid,
            'message' => $valid ? 'Conexión con Evolution API exitosa' : 'No se pudo conectar con Evolution API',
        ]);
    }
}
