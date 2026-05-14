<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Services\DeepseekService;
use App\Services\WhatsAppService;
use App\Models\PdfFile;
use Smalot\PdfParser\Parser;

class WhatsAppController extends Controller
{
    public function webhook(Request $request, DeepseekService $deepseek, WhatsAppService $wa)
    {
        $from = $request->input('From'); // e.g. "whatsapp:+123456789"
        $body = $request->input('Body', '');
        $numMedia = (int)$request->input('NumMedia', 0);

        Log::info('Twilio webhook received', ['from' => $from, 'body' => $body, 'numMedia' => $numMedia]);

        if (empty($from)) {
            return response('OK', 200);
        }

        try {
            // Handle file uploads
            if ($numMedia > 0) {
                return $this->handleMediaUpload($request, $from, $wa);
            }

            // Handle text messages
            if (!empty($body)) {
                $reply = $deepseek->chat($body, ['from' => $from]);
                return $this->twimlMessage($reply);
            }
        } catch (\Exception $e) {
            Log::error('Error handling Twilio webhook', ['error' => $e->getMessage()]);
            return $this->twimlMessage('❌ Hubo un error al procesar tu solicitud. Por favor, intenta de nuevo.');
        }

        return $this->twimlMessage('Escríbeme tu duda o envíame un PDF para continuar.');
    }

    /**
     * Handle media (file) uploads from WhatsApp
     */
    private function handleMediaUpload(Request $request, string $from, WhatsAppService $wa)
    {
        $numMedia = (int)$request->input('NumMedia', 0);
        
        if ($numMedia === 0) {
            return $this->twimlMessage('No se detectaron archivos. Por favor envía un PDF.');
        }

        // Extract phone number from WhatsApp format "whatsapp:+1234567890"
        $phoneNumber = str_replace('whatsapp:', '', $from);

        for ($i = 0; $i < $numMedia; $i++) {
            $mediaUrl = $request->input("MediaUrl{$i}");
            $mediaType = $request->input("MediaContentType{$i}", '');

            // Only process PDFs
            $isPdf = $mediaType === 'application/pdf' || ($mediaUrl && str_ends_with($mediaUrl, '.pdf'));
            if (!$isPdf) {
                $wa->sendMessage($from, "⚠️ El archivo " . ($i + 1) . " no es un PDF. Solo se aceptan archivos PDF.");
                continue;
            }

            try {
                $reply = $this->processPdfFromWhatsApp($mediaUrl, $phoneNumber, $from, $wa);
                return $this->twimlMessage($reply);
            } catch (\Exception $e) {
                Log::error('Error processing PDF from WhatsApp', ['error' => $e->getMessage()]);
                return $this->twimlMessage("❌ Error al procesar el PDF: " . $e->getMessage());
            }
        }

        return $this->twimlMessage('Envía un PDF válido para continuar.');
    }

    /**
     * Download PDF from Twilio and save it
     */
    private function processPdfFromWhatsApp(string $mediaUrl, string $phoneNumber, string $from, WhatsAppService $wa)
    {
        $accountSid = config('twilio.account_sid');
        $authToken = config('twilio.auth_token');

        if (empty($accountSid) || empty($authToken)) {
            throw new \Exception('Faltan TWILIO_ACCOUNT_SID o TWILIO_AUTH_TOKEN en el archivo .env.');
        }

        // Download file from Twilio using Basic Auth
        $response = Http::withBasicAuth($accountSid, $authToken)
            ->acceptJson()
            ->get($mediaUrl);

        if (!$response->ok()) {
            Log::error('Twilio media download failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $mediaUrl,
            ]);

            if (in_array($response->status(), [401, 403], true)) {
                throw new \Exception('Twilio rechazó la descarga del archivo. Revisa TWILIO_ACCOUNT_SID y TWILIO_AUTH_TOKEN.');
            }

            throw new \Exception('No se pudo descargar el archivo de Twilio');
        }

        // Generate filename
        $filename = uniqid('wa_') . '_' . time() . '.pdf';
        $filePath = 'pdfs/' . $filename;

        // Store file
        Storage::disk('public')->put($filePath, $response->body());

        // Parse PDF to count pages
        $parser = new Parser();
        $fullPath = storage_path('app/public/' . $filePath);
        $document = $parser->parseFile($fullPath);
        $pagesCount = count($document->getPages());

        // Get original filename from Content-Disposition header if available
        $originalName = 'documento_whatsapp.pdf';
        $contentDisposition = $response->header('Content-Disposition');
        if ($contentDisposition && preg_match('/filename="(.+)"/', $contentDisposition, $matches)) {
            $originalName = $matches[1];
        }

        // Save to database
        $pdfFile = PdfFile::create([
            'filename' => $filename,
            'original_name' => $originalName,
            'email' => null, // Can be extracted later if user logs in
            'pages_count' => $pagesCount,
            'file_path' => $filePath,
            'file_size' => strlen($response->body()) / 1024, // KB
        ]);

        // Send confirmation with link to configure printing
        $configLink = route('kiosko.configure', $pdfFile->id, false);
        $fullUrl = secure_url($configLink);
        
        $message = "📄 Tu PDF se ha subido correctamente\n";
        $message .= "📄 Páginas detectadas: {$pagesCount}\n";
        $message .= "📱 Configura tu impresión aquí:\n{$fullUrl}";
        
        return $message;

        Log::info('PDF processed from WhatsApp', [
            'from' => $from,
            'pdf_id' => $pdfFile->id,
            'pages' => $pagesCount,
            'original_name' => $originalName
        ]);
    }

    /**
     * Build a TwiML response so Twilio replies without needing outbound API calls.
     */
    private function twimlMessage(string $body)
    {
        $safeBody = htmlspecialchars($body, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><Response><Message>{$safeBody}</Message></Response>";

        return response($xml, 200)->header('Content-Type', 'text/xml');
    }
}
