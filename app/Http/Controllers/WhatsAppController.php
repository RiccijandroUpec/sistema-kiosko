<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Kiosko;
use App\Models\OrdenImpresion;
use App\Models\TransaccionPago;
use App\Services\EvolutionService;
use App\Services\DeepseekService;
use App\Services\GeminiVisionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;

class WhatsAppController extends Controller
{
    protected EvolutionService $evolutionService;
    protected DeepseekService $deepseekService;
    protected GeminiVisionService $geminiVisionService;

    public function __construct(
        EvolutionService $evolutionService,
        DeepseekService $deepseekService,
        GeminiVisionService $geminiVisionService
    ) {
        $this->evolutionService = $evolutionService;
        $this->deepseekService = $deepseekService;
        $this->geminiVisionService = $geminiVisionService;
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

        $data = $payload['data'] ?? [];
        $messageId = $data['key']['id'] ?? '';
        if ($messageId) {
            $lockKey = 'webhook_lock_' . $messageId;
            if (Cache::has($lockKey)) {
                Log::info('Webhook ignorado: Mensaje duplicado detectado (lock activo)', ['messageId' => $messageId]);
                return response()->json(['status' => 'ignored_duplicate']);
            }
            Cache::put($lockKey, true, now()->addMinutes(5));
        }

        return $this->handleIncomingMessage($data);
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

            // Filtrar mensajes antiguos (evita procesar mensajes de pruebas anteriores)
            $messageTimestamp = $data['messageTimestamp'] ?? now()->timestamp;
            $messageTtlMinutes = (int) env('WEBHOOK_MESSAGE_TTL_MINUTES', 5);
            $messageTime = \Carbon\Carbon::createFromTimestamp($messageTimestamp);

            if ($messageTime->diffInMinutes(now()) > $messageTtlMinutes) {
                Log::info('Mensaje rechazado por antiguo', [
                    'from' => explode('@', $fromJid)[0],
                    'timestamp' => $messageTime->toDateTimeString(),
                    'age_minutes' => $messageTime->diffInMinutes(now()),
                    'ttl_minutes' => $messageTtlMinutes,
                ]);
                return response()->json(['status' => 'ignored_old_message']);
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

            // 2. Imagen (Comprobante de Pago)
            if (isset($message['imageMessage'])) {
                $this->handleImageMessage($from, $messageId, $message['imageMessage']);
                return response()->json(['status' => 'ok']);
            }

            // 3. Documento (PDF)
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
            $message = "✅ Sede detectada: *{$matchedKiosk->nombre_comercial}*";
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
                
                // Buscar si hay selección pendiente
                $state = $this->getSelectionState($from);
                if ($state) {
                    $this->handlePrintConfigMessage($from, $text, $state);
                    return;
                }
            }
        }

        // Si no hay config, enviar solo el texto de la IA
        $cleanResponse = trim(preg_replace('/\{.*\}/s', '', $aiResponse));
        $this->evolutionService->sendMessage($from, $cleanResponse ?: $aiResponse);
    }

    /**
     * Manejar mensaje de imagen (Comprobantes)
     */
    protected function handleImageMessage(string $from, string $messageId, array $imageMessage)
    {
        try {
            $this->evolutionService->sendMessage($from, "⏳ Estoy revisando tu comprobante con Inteligencia Artificial. Dame un momento...");

            // Descargar la imagen
            $imageContent = $this->evolutionService->downloadMedia($messageId);
            
            if (!$imageContent) {
                $this->evolutionService->sendMessage($from, "❌ Hubo un problema al descargar la imagen. ¿Podrías enviarla de nuevo?");
                return;
            }

            // Enviar a Gemini para extraer datos
            $receiptData = $this->geminiVisionService->extractReceiptData($imageContent, $imageMessage['mimetype'] ?? 'image/jpeg');

            if (!$receiptData || !isset($receiptData['monto'])) {
                $this->evolutionService->sendMessage($from, "❌ No logré detectar el monto de la transferencia en esta imagen. Asegúrate de que los números se vean claros o escribe el número de comprobante en la web.");
                return;
            }

            $montoExtraido = (float) $receiptData['monto'];
            $referenciaExtraida = $receiptData['referencia'] ?? '';

            // Buscar orden pendiente del cliente por el monto exacto
            // Primero, buscamos al cliente
            $cliente = Cliente::where('telefono', $from)->first();

            $pendingPayment = null;

            if ($cliente) {
                // Buscar transacción del cliente
                $pendingPayment = TransaccionPago::where('estado', 'pendiente')
                    ->whereHas('orden', function ($query) use ($cliente) {
                        $query->where('cliente_id', $cliente->id);
                    })
                    ->where('monto', $montoExtraido)
                    ->first();
            }

            // Si no encuentra por cliente, buscar cualquier transacción pendiente con ese monto exacto (peligroso pero útil si es web_guest)
            if (!$pendingPayment) {
                $pendingPayment = TransaccionPago::where('estado', 'pendiente')
                    ->where('monto', $montoExtraido)
                    ->first();
            }

            if ($pendingPayment) {
                $orden = $pendingPayment->orden;

                // Marcar como pagado
                $pendingPayment->update([
                    'estado' => 'completado',
                    'referencia_usuario' => $referenciaExtraida ?: 'WhatsApp Image'
                ]);

                $orden->update(['estado' => 'pagado']);

                $this->evolutionService->sendMessage(
                    $from, 
                    "✅ *¡Pago confirmado exitosamente!*\nDetecté un pago de *$" . number_format($montoExtraido, 2) . "*.\n\n🖨️ Tu documento acaba de ser liberado y ya se está imprimiendo en el kiosko."
                );
                
                Log::info('Pago liberado por Gemini Vision', ['monto' => $montoExtraido, 'ref' => $referenciaExtraida]);
            } else {
                $this->evolutionService->sendMessage($from, "⚠️ Detecté un pago por $" . number_format($montoExtraido, 2) . ", pero no encontré ninguna impresión pendiente con ese valor exacto. Acércate al administrador.");
            }

        } catch (\Exception $e) {
            Log::error('Error processing Image from Evolution', ['error' => $e->getMessage()]);
            $this->evolutionService->sendMessage($from, "Ocurrió un error interno al analizar la imagen.");
        }
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

            // Subir a Supabase Storage (con fallback local)
            $pdfUrl = $this->uploadToSupabase($fileContent, $uniqueFileName);

            // Contar páginas
            $parser = new Parser();
            $document = $parser->parseContent($fileContent);
            $pages = count($document->getPages());

            // Guardar en la base de datos (pdf_files) con UUID
            $pdfFile = \App\Models\PdfFile::create([
                'filename' => $uniqueFileName,
                'original_name' => $fileName,
                'email' => null,
                'pages_count' => $pages,
                'file_path' => $path,
                'file_size' => strlen($fileContent) / 1024, // KB
            ]);

            // Obtener contexto de kiosko activo si existe
            $preferredKiosk = $this->getKioskContext($from);
            $kioskoId = $preferredKiosk ? $preferredKiosk->id : null;

            // Generar enlace público para configuración web
            $configureUrl = route('kiosko.configure', [
                'pdf' => $pdfFile->id,
                'kiosko' => $kioskoId
            ]);

            $message = "📄 *¡He recibido tu archivo!* \n\n" .
                       "📝 *Nombre:* {$fileName}\n" .
                       "📄 *Páginas:* {$pages}\n\n" .
                       "Puedes configurar las opciones de tu impresión en el siguiente enlace:\n" .
                       $configureUrl;

            if ($preferredKiosk) {
                $message .= "\n\n📍 Sede preseleccionada: *{$preferredKiosk->nombre_comercial}*";
            }

            $this->evolutionService->sendMessage($from, $message);

        } catch (\Exception $e) {
            Log::error('Error processing PDF from Evolution', ['error' => $e->getMessage()]);
            $this->evolutionService->sendMessage($from, "No pude procesar ese PDF. Asegúrate de que no tenga contraseña.");
        }
    }

    private function uploadToSupabase(string $fileContent, string $uniqueFileName): string
    {
        $supabaseUrl = env('SUPABASE_URL');
        $supabaseKey = env('SUPABASE_ANON_KEY');
        
        if (!empty($supabaseUrl) && !empty($supabaseKey)) {
            $bucket = 'pdfs';
            $uploadUrl = rtrim($supabaseUrl, '/') . "/storage/v1/object/{$bucket}/{$uniqueFileName}";
            
            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$supabaseKey}",
                    'apiKey' => $supabaseKey,
                    'Content-Type' => 'application/pdf',
                ])->withBody($fileContent, 'application/pdf')
                  ->post($uploadUrl);
                  
                if ($response->successful()) {
                    return rtrim($supabaseUrl, '/') . "/storage/v1/object/public/{$bucket}/{$uniqueFileName}";
                }
                
                Log::warning('Supabase storage upload unsuccessful, using local storage fallback', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            } catch (\Exception $e) {
                Log::error('Supabase storage upload exception: ' . $e->getMessage());
            }
        }
        
        // Local fallback URL
        return asset("storage/pdfs/{$uniqueFileName}");
    }

    protected function promptForKioskSelection(string $from, array $pdfState, ?Kiosko $preferredKiosk = null): void
    {
        $kiosks = Kiosko::query()->orderBy('nombre_comercial')->get();

        if ($kiosks->isEmpty()) {
            $this->evolutionService->sendMessage($from, "📄 Recibí tu PDF, pero todavía no hay kioskos registrados para asignarlo.");
            return;
        }

        if ($preferredKiosk) {
            Cache::put($this->selectionStateKey($from), array_merge($pdfState, [
                'step' => 'awaiting_print_config',
                'kiosk_id' => $preferredKiosk->id,
            ]), now()->addMinutes(20));

            $preferredMessage = "✅ PDF recibido para la sede *{$preferredKiosk->nombre_comercial}*";
            $preferredMessage .= "\n\nAhora dime cómo quieres imprimirlo. Ejemplos: '3 copias a color' o '2 copias blanco y negro'.";

            $this->evolutionService->sendMessage($from, $preferredMessage);
            return;
        }

        Cache::put($this->selectionStateKey($from), array_merge($pdfState, [
            'step' => 'awaiting_kiosk_selection',
        ]), now()->addMinutes(20));

        $this->evolutionService->sendMessage($from, $this->buildKioskPrompt($kiosks));
    }

    protected function handleKioskSelection(string $from, string $text, array $state): void
    {
        $kiosks = Kiosko::query()->orderBy('nombre_comercial')->get();
        $kiosk = $this->resolveKioskSelection($text, $kiosks);

        if ($kiosks->isEmpty()) {
            $this->clearSelectionState($from);
            $this->evolutionService->sendMessage($from, "No hay kioskos disponibles. Vuelve a enviar el archivo.");
            return;
        }

        if (!$kiosk) {
            $this->evolutionService->sendMessage($from, $this->buildKioskPrompt($kiosks));
            return;
        }

        Cache::put($this->selectionStateKey($from), array_merge($state, [
            'step' => 'awaiting_print_config',
            'kiosk_id' => $kiosk->id,
        ]), now()->addMinutes(20));

        $this->evolutionService->sendMessage($from, "✅ Sede seleccionada: *{$kiosk->nombre_comercial}*\n\nAhora dime cómo quieres imprimirlo. Ejemplos: '3 copias a color' o '2 copias blanco y negro'.");
    }

    protected function handlePrintConfigMessage(string $from, string $text, array $state): void
    {
        $kiosk = Kiosko::find($state['kiosk_id'] ?? null);

        if (!$kiosk) {
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

        $this->createPrintJobFromConfig($state, $from, $data['config'], $kiosk);
    }

    protected function createPrintJobFromConfig(array $state, string $from, array $config, Kiosko $kiosk): void
    {
        $copies = max(1, (int) ($config['copies'] ?? 1));
        $colorType = ($config['color_type'] ?? 'bw') === 'color' ? 'color' : 'bw';

        $costBW = $kiosk->precio_blanco_negro ?? 0.05;
        $costColor = $kiosk->precio_color ?? 0.20;
        $costPerPage = $colorType === 'color' ? $costColor : $costBW;
        
        $pdfPagesCount = (int) ($state['pdf_pages_count'] ?? 1);
        $totalCost = $pdfPagesCount * $copies * $costPerPage;

        // Registrar o encontrar el Cliente
        $cliente = Cliente::firstOrCreate(
            ['telefono' => $from],
            ['nombre' => null]
        );

        // Crear la OrdenImpresion
        $orden = OrdenImpresion::create([
            'kiosko_id' => $kiosk->id,
            'cliente_id' => $cliente->id,
            'archivo_url' => $state['pdf_url'],
            'paginas' => $pdfPagesCount * $copies,
            'color' => $colorType === 'color',
            'costo_total' => $totalCost,
            'estado' => 'pendiente',
        ]);

        // Crear TransaccionPago pendiente
        TransaccionPago::create([
            'orden_id' => $orden->id,
            'monto' => $totalCost,
            'metodo' => 'Deuna',
            'estado' => 'pendiente',
        ]);

        $this->clearSelectionState($from);

        $kioskName = $kiosk->nombre_comercial ?? 'la sede seleccionada';
        $this->evolutionService->sendMessage($from, "✅ ¡Impresión configurada para {$kioskName}!\n\n" .
            "📍 Ref: *{$orden->id}*\n" .
            "💰 Total: *$" . number_format($totalCost, 2, '.', '') . "*\n" .
            "📝 Detalle: {$copies} copias • " . ($colorType === 'color' ? 'COLOR' : 'B/N') . " ({$pdfPagesCount} págs. orig.)\n\n" .
            "Puedes pagar usando el código QR en el kiosko.");
    }

    protected function resolveKioskSelection(string $text, $kiosks): ?Kiosko
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

            return Kiosko::find($position);
        }

        foreach ($kiosks as $kiosk) {
            $haystack = mb_strtolower(trim($kiosk->nombre_comercial));
            if (str_contains($haystack, $cleanText)) {
                return $kiosk;
            }
        }

        return null;
    }

    protected function buildKioskPrompt($kiosks): string
    {
        $lines = $kiosks->values()->map(function (Kiosko $kiosk, int $index) {
            $position = $index + 1;
            return "{$position}. {$kiosk->nombre_comercial}";
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

    protected function captureKioskContextFromText(string $from, string $text): ?Kiosko
    {
        $normalized = mb_strtolower(trim($text));

        if ($normalized === '') {
            return null;
        }

        if (!preg_match('/\b(estoy en|estoy|sede|kiosko|kiosk)\b/u', $normalized)) {
            return null;
        }

        foreach (Kiosko::query()->get() as $kiosk) {
            $haystack = mb_strtolower(trim($kiosk->nombre_comercial));
            if ($haystack !== '' && str_contains($normalized, $haystack)) {
                Cache::put($this->kioskContextKey($from), $kiosk->id, now()->addHours(12));
                return $kiosk;
            }
        }

        return null;
    }

    protected function getKioskContext(string $from): ?Kiosko
    {
        $kioskId = Cache::get($this->kioskContextKey($from));

        if (!$kioskId) {
            return null;
        }

        return Kiosko::find($kioskId);
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
