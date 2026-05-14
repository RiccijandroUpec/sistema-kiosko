<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\DeepseekService;
use App\Services\WhatsAppService;
use App\Models\PdfFile;
use Smalot\PdfParser\Parser;

class WhatsAppController extends Controller
{
    public function webhook(Request $request, DeepseekService $deepseek, WhatsAppService $wa)
    {
        $from = $this->extractSender($request);
        $body = $this->extractText($request);
        $media = $this->extractMedia($request);

        Log::info('Evolution webhook received', [
            'from' => $from,
            'body' => $body,
            'has_media' => !empty($media),
        ]);

        if (empty($from)) {
            return response()->json(['status' => 'ignored', 'reason' => 'missing_sender']);
        }

        try {
            if (!empty($media)) {
                $reply = $this->processPdfFromWhatsApp($media, $from, $wa);
                $wa->sendMessage($from, $reply);

                return response()->json(['status' => 'ok', 'type' => 'media']);
            }

            if (!empty($body)) {
                $reply = $deepseek->chat($body, ['from' => $from]);
                $wa->sendMessage($from, $reply);

                return response()->json(['status' => 'ok', 'type' => 'text']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling Evolution webhook', ['error' => $e->getMessage()]);
            $wa->sendMessage($from, '❌ Hubo un error al procesar tu solicitud. Por favor, intenta de nuevo.');

            return response()->json(['status' => 'error'], 500);
        }

        $wa->sendMessage($from, 'Escríbeme tu duda o envíame un PDF para continuar.');

        return response()->json(['status' => 'ok', 'type' => 'fallback']);
    }

    /**
     * Process a PDF sent by Evolution API webhook and create a PdfFile record.
     */
    private function processPdfFromWhatsApp(array $media, string $from, WhatsAppService $wa): string
    {
        $mediaUrl = (string) ($media['url'] ?? '');
        $mediaType = (string) ($media['mime_type'] ?? '');
        $base64 = (string) ($media['base64'] ?? '');
        $originalName = (string) ($media['file_name'] ?? 'documento_whatsapp.pdf');

        $isPdf = $mediaType === 'application/pdf'
            || str_ends_with(strtolower($originalName), '.pdf')
            || ($mediaUrl !== '' && str_ends_with(strtolower($mediaUrl), '.pdf'));

        if (!$isPdf) {
            throw new \Exception('Solo se aceptan archivos PDF.');
        }

        if ($base64 !== '') {
            $binary = base64_decode($base64, true);
            if ($binary === false) {
                throw new \Exception('El archivo recibido no tiene un base64 valido.');
            }
            $fileBinary = $binary;
        } elseif ($mediaUrl !== '') {
            $fileBinary = $wa->downloadFile($mediaUrl);
        } else {
            throw new \Exception('No se encontro URL ni contenido base64 para el archivo.');
        }

        $filename = uniqid('wa_') . '_' . time() . '.pdf';
        $filePath = 'pdfs/' . $filename;
        Storage::disk('public')->put($filePath, $fileBinary);

        $parser = new Parser();
        $fullPath = storage_path('app/public/' . $filePath);
        $document = $parser->parseFile($fullPath);
        $pagesCount = count($document->getPages());

        $pdfFile = PdfFile::create([
            'filename' => $filename,
            'original_name' => $originalName,
            'email' => null,
            'pages_count' => $pagesCount,
            'file_path' => $filePath,
            'file_size' => strlen($fileBinary) / 1024,
        ]);

        $configLink = route('kiosko.configure', $pdfFile->id, false);
        $fullUrl = secure_url($configLink);

        $message = "📄 Tu PDF se ha subido correctamente\n";
        $message .= "📄 Páginas detectadas: {$pagesCount}\n";
        $message .= "📱 Configura tu impresión aquí:\n{$fullUrl}";

        Log::info('PDF processed from Evolution webhook', [
            'from' => $from,
            'pdf_id' => $pdfFile->id,
            'pages' => $pagesCount,
            'original_name' => $originalName,
        ]);

        return $message;
    }

    private function extractSender(Request $request): ?string
    {
        $from = $request->input('from')
            ?? $request->input('sender')
            ?? data_get($request->all(), 'data.key.remoteJid');

        if (!is_string($from) || $from === '') {
            return null;
        }

        return str_replace('@s.whatsapp.net', '', $from);
    }

    private function extractText(Request $request): string
    {
        $payload = $request->all();

        return (string) (
            $request->input('body', '')
            ?: $request->input('text', '')
            ?: data_get($payload, 'data.message.conversation', '')
            ?: data_get($payload, 'data.message.extendedTextMessage.text', '')
            ?: data_get($payload, 'data.message.imageMessage.caption', '')
            ?: data_get($payload, 'data.message.documentMessage.caption', '')
        );
    }

    private function extractMedia(Request $request): array
    {
        $payload = $request->all();

        $url = (string) (
            $request->input('mediaUrl', '')
            ?: data_get($payload, 'data.mediaUrl', '')
            ?: data_get($payload, 'data.message.documentMessage.url', '')
        );

        $mimeType = (string) (
            $request->input('mimeType', '')
            ?: data_get($payload, 'data.mimeType', '')
            ?: data_get($payload, 'data.message.documentMessage.mimetype', '')
        );

        $fileName = (string) (
            $request->input('fileName', '')
            ?: data_get($payload, 'data.fileName', '')
            ?: data_get($payload, 'data.message.documentMessage.fileName', '')
        );

        $base64 = (string) (
            $request->input('base64', '')
            ?: data_get($payload, 'data.base64', '')
            ?: data_get($payload, 'data.message.documentMessage.base64', '')
        );

        if ($url === '' && $base64 === '') {
            return [];
        }

        return [
            'url' => $url,
            'mime_type' => $mimeType,
            'file_name' => $fileName,
            'base64' => $base64,
        ];
    }
}
