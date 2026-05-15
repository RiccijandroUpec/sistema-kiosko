<?php

namespace App\Http\Controllers;

use App\Models\PdfFile;
use App\Models\PrintJob;
use App\Models\Payment;
use App\Services\EvolutionService;
use App\Services\DeepseekService;
use Illuminate\Http\Request;
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
                    $copies = $config['copies'] ?? 1;
                    $colorType = $config['color_type'] ?? 'bw';

                    $costBW = config('printing.cost_bw', 0.05);
                    $costColor = config('printing.cost_color', 0.20);
                    $costPerPage = $colorType === 'color' ? $costColor : $costBW;
                    $totalCost = $lastPdf->pages_count * $copies * $costPerPage;

                    // Crear Trabajo de Impresión
                    $printJob = PrintJob::create([
                        'job_reference' => PrintJob::generateJobReference($lastPdf->original_name),
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

                    // Crear Registro de Pago
                    $payment = Payment::create([
                        'print_job_id' => $printJob->id,
                        'reference_code' => Payment::generateReferenceCode(),
                        'amount' => $totalCost,
                        'status' => 'pending',
                    ]);

                    // Limpiar el JSON de la respuesta para el usuario
                    $cleanResponse = trim(preg_replace('/\{.*\}/s', '', $aiResponse));
                    
                    $this->evolutionService->sendMessage($from, $cleanResponse);
                    $this->evolutionService->sendMessage($from, 
                        "✅ ¡Impresión configurada!\n\n" .
                        "📍 Ref: *{$printJob->job_reference}*\n" .
                        "💰 Total: *${$totalCost}*\n" .
                        "📝 Detalle: {$copies} copias • " . ($colorType === 'color' ? 'COLOR' : 'B/N') . "\n\n" .
                        "Puedes pagar usando el código QR en el kiosko."
                    );
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

            // Enviar respuesta con link
            $configLink = route('kiosko.configure', $pdfFile->id);
            
            $this->evolutionService->sendMessage(
                $from,
                "📄 ¡He recibido tu archivo! *{$fileName}* ({$pages} páginas).\n\n" .
                "Dime cómo quieres imprimirlo (ej: 'Quiero 3 copias a color') o usa este link para configurar: \n{$configLink}"
            );

        } catch (\Exception $e) {
            Log::error('Error processing PDF from Evolution', ['error' => $e->getMessage()]);
            $this->evolutionService->sendMessage($from, "No pude procesar ese PDF. Asegúrate de que no tenga contraseña.");
        }
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
