<?php

namespace App\Http\Controllers;

use App\Models\PdfFile;
use App\Services\WhatsAppBusinessService;
use App\Services\DeepseekService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class WhatsAppController extends Controller
{
    protected WhatsAppBusinessService $whatsAppService;
    protected DeepseekService $deepseekService;

    public function __construct(
        WhatsAppBusinessService $whatsAppService,
        DeepseekService $deepseekService
    ) {
        $this->whatsAppService = $whatsAppService;
        $this->deepseekService = $deepseekService;
    }

    /**
     * Webhook para recibir mensajes de WhatsApp (Meta)
     */
    public function webhook(Request $request)
    {
        // Verificar el token del webhook (GET para verificación inicial)
        if ($request->isMethod('get')) {
            return $this->verifyWebhook($request);
        }

        // Procesar mensajes entrantes (POST)
        if ($request->isMethod('post')) {
            return $this->handleIncomingMessage($request);
        }

        return response()->json(['error' => 'Method not allowed'], 405);
    }

    /**
     * Verificar token del webhook (Meta)
     */
    protected function verifyWebhook(Request $request)
    {
        $verifyToken = config('whatsapp-business.webhook_verify_token');
        $mode = $request->get('hub_mode');
        $token = $request->get('hub_verify_token');
        $challenge = $request->get('hub_challenge');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('WhatsApp webhook verified');
            return response($challenge, 200);
        }

        Log::warning('WhatsApp webhook verification failed', ['token' => $token]);
        return response()->json(['error' => 'Invalid token'], 403);
    }

    /**
     * Procesar mensajes entrantes de Meta
     */
    protected function handleIncomingMessage(Request $request)
    {
        try {
            $body = $request->json('entry.0.changes.0.value');

            // Puede ser un mensaje o un cambio de estado
            if (empty($body['messages'])) {
                return response()->json(['status' => 'ok']);
            }

            $message = $body['messages'][0];
            $from = $body['contacts'][0]['wa_id'] ?? null;

            if (!$from) {
                return response()->json(['status' => 'error', 'message' => 'No sender']);
            }

            // Agregar + al número si no lo tiene
            if (!str_starts_with($from, '+')) {
                $from = '+' . $from;
            }

            // Procesar según el tipo de mensaje
            if ($message['type'] === 'text') {
                $this->handleTextMessage($from, $message['text']['body']);
            } elseif ($message['type'] === 'document') {
                $this->handleDocumentMessage($from, $message['document']);
            } elseif ($message['type'] === 'image') {
                // Ignorar imágenes por ahora
                Log::info('Image received', ['from' => $from]);
            }

            // Marcar como leído
            if (isset($message['id'])) {
                $this->whatsAppService->markAsRead($message['id']);
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook error', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Manejar mensaje de texto
     */
    protected function handleTextMessage(string $from, string $text)
    {
        // Obtener respuesta de la IA
        $aiResponse = $this->deepseekService->chat($text);

        // Enviar respuesta
        $this->whatsAppService->sendMessage($from, $aiResponse);

        // Enviar link para descargar PDF
        $downloadLink = route('kiosko.index') . '?wa=' . urlencode($from);
        $this->whatsAppService->sendMessage(
            $from,
            "🖨️ Para imprimir un PDF, usa este link: " . $downloadLink
        );

        Log::info('Text message processed', ['from' => $from, 'text' => $text]);
    }

    /**
     * Manejar documento (PDF)
     */
    protected function handleDocumentMessage(string $from, array $document)
    {
        try {
            $mediaId = $document['id'] ?? null;
            $fileName = $document['filename'] ?? 'documento.pdf';

            if (!$mediaId) {
                $this->whatsAppService->sendMessage($from, 'Error: No se pudo procesar el documento.');
                return;
            }

            // Descargar el archivo desde Meta
            $fileContent = $this->downloadMediaFromMeta($mediaId);

            if (!$fileContent) {
                $this->whatsAppService->sendMessage($from, 'Error: No se pudo descargar el documento.');
                return;
            }

            // Guardar archivo
            $uniqueFileName = uniqid() . '_' . time() . '.pdf';
            $path = "pdfs/{$uniqueFileName}";
            Storage::disk('public')->put($path, $fileContent);

            // Procesar PDF
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

            // Enviar link de configuración
            $configLink = route('kiosko.configure', $pdfFile->id);
            $this->whatsAppService->sendMessage(
                $from,
                "📄 PDF recibido correctamente ({$pages} páginas).\n\n" .
                "Usa este link para configurar tu impresión: {$configLink}"
            );

            Log::info('PDF processed from WhatsApp', ['from' => $from, 'pages' => $pages]);
        } catch (\Exception $e) {
            Log::error('Error processing PDF from WhatsApp', ['error' => $e->getMessage()]);
            $this->whatsAppService->sendMessage($from, 'Error al procesar el PDF.');
        }
    }

    /**
     * Descargar media desde Meta
     */
    protected function downloadMediaFromMeta(string $mediaId): ?string
    {
        try {
            $token = config('whatsapp-business.token');
            $apiVersion = config('whatsapp-business.api_version');
            $baseUrl = config('whatsapp-business.base_url');

            // Obtener URL del media
            $mediaResponse = \Illuminate\Support\Facades\Http::withToken($token)
                ->get("{$baseUrl}/{$apiVersion}/{$mediaId}");

            if (!$mediaResponse->successful()) {
                Log::error('Error getting media URL', ['response' => $mediaResponse->json()]);
                return null;
            }

            $mediaUrl = $mediaResponse->json('url');

            if (!$mediaUrl) {
                return null;
            }

            // Descargar el archivo
            $fileResponse = \Illuminate\Support\Facades\Http::withToken($token)
                ->get($mediaUrl);

            if ($fileResponse->successful()) {
                return $fileResponse->body();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error downloading media from Meta', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Enviar mensaje de prueba (para testing)
     */
    public function sendTestMessage(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        try {
            $sent = $this->whatsAppService->sendMessage($request->phone, $request->message);

            return response()->json([
                'success' => $sent,
                'message' => $sent ? 'Mensaje enviado' : 'Error al enviar',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Validar credenciales
     */
    public function validateCredentials()
    {
        $valid = $this->whatsAppService->validateCredentials();

        return response()->json([
            'valid' => $valid,
            'phone_number' => config('whatsapp-business.phone_number'),
            'message' => $valid ? 'Credenciales válidas' : 'Credenciales inválidas',
        ]);
    }
}
