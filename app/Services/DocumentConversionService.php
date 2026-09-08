<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class DocumentConversionService
{
    /**
     * Convierte un archivo subido (imagen u Office) a PDF si es necesario.
     * Retorna la ruta al archivo PDF (que puede ser el mismo si ya era PDF o uno nuevo convertido).
     *
     * @param  UploadedFile  $file
     * @return array ['path' => string, 'converted' => bool, 'original_extension' => string]
     * @throws \Exception
     */
    public function convertToPdf(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $realPath = $file->getRealPath();

        // Si ya es PDF, no requiere conversión
        if ($ext === 'pdf') {
            return [
                'path' => $realPath,
                'converted' => false,
                'original_extension' => 'pdf',
            ];
        }

        // Si es una imagen (JPG, JPEG, PNG, WEBP)
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $tempPdf = tempnam(sys_get_temp_dir(), 'img_pdf_') . '.pdf';
            $this->convertImageToPdf($realPath, $tempPdf, $ext);

            return [
                'path' => $tempPdf,
                'converted' => true,
                'original_extension' => $ext,
            ];
        }

        // Si es documento de Office (DOCX, DOC, PPTX)
        if (in_array($ext, ['docx', 'doc', 'pptx', 'ppt', 'odt'])) {
            $tempPdf = $this->convertOfficeToPdf($realPath, $ext);

            return [
                'path' => $tempPdf,
                'converted' => true,
                'original_extension' => $ext,
            ];
        }

        throw new \InvalidArgumentException("El formato .{$ext} no está soportado para impresión.");
    }

    /**
     * Convierte una imagen (JPG/PNG) a un archivo PDF estándar A4 en pure PHP.
     */
    public function convertImageToPdf(string $imagePath, string $outputPath, string $ext): void
    {
        $jpegPath = $imagePath;
        $needsCleanup = false;

        // Si es PNG o WEBP, convertir a JPEG temporal usando GD
        if (in_array(strtolower($ext), ['png', 'webp'])) {
            $jpegPath = tempnam(sys_get_temp_dir(), 'img_conv_') . '.jpg';
            $needsCleanup = true;

            $img = strtolower($ext) === 'png'
                ? @imagecreatefrompng($imagePath)
                : @imagecreatefromwebp($imagePath);

            if (!$img) {
                throw new \RuntimeException("No se pudo leer la imagen {$ext}.");
            }

            // Crear fondo blanco si tiene transparencias
            $width = imagesx($img);
            $height = imagesy($img);
            $bg = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($bg, 255, 255, 255);
            imagefilledrectangle($bg, 0, 0, $width, $height, $white);
            imagecopy($bg, $img, 0, 0, 0, 0, $width, $height);

            imagejpeg($bg, $jpegPath, 92);
            imagedestroy($img);
            imagedestroy($bg);
        }

        $imageInfo = @getimagesize($jpegPath);
        if (!$imageInfo) {
            if ($needsCleanup && file_exists($jpegPath)) @unlink($jpegPath);
            throw new \RuntimeException('No se pudieron leer las dimensiones de la imagen.');
        }

        $imgWidth = $imageInfo[0];
        $imgHeight = $imageInfo[1];
        $jpegData = file_get_contents($jpegPath);

        if ($needsCleanup && file_exists($jpegPath)) {
            @unlink($jpegPath);
        }

        // Dimensiones página A4 estándar en puntos (72 dpi): 595 x 842 pt
        $pageWidth = 595.28;
        $pageHeight = 841.89;
        $margin = 28.35; // 10mm de margen

        $availWidth = $pageWidth - ($margin * 2);
        $availHeight = $pageHeight - ($margin * 2);

        // Escalar manteniendo proporción
        $ratio = min($availWidth / $imgWidth, $availHeight / $imgHeight);
        $renderWidth = round($imgWidth * $ratio, 2);
        $renderHeight = round($imgHeight * $ratio, 2);

        // Centrar imagen en la página
        $posX = round(($pageWidth - $renderWidth) / 2, 2);
        $posY = round(($pageHeight - $renderHeight) / 2, 2);

        // Generar sintaxis estándar PDF 1.4
        $contentStream = sprintf(
            "q\n%.2f 0 0 %.2f %.2f %.2f cm\n/Im1 Do\nQ\n",
            $renderWidth,
            $renderHeight,
            $posX,
            $posY
        );
        $contentLen = strlen($contentStream);
        $jpegLen = strlen($jpegData);

        $objects = [];
        $offsets = [];

        // Obj 1: Catalog
        $objects[1] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // Obj 2: Pages
        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // Obj 3: Page
        $objects[3] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Contents 4 0 R /Resources << /XObject << /Im1 5 0 R >> >> >>\nendobj\n";

        // Obj 4: Content Stream
        $objects[4] = "4 0 obj\n<< /Length {$contentLen} >>\nstream\n{$contentStream}endstream\nendobj\n";

        // Obj 5: Image XObject
        $objects[5] = "5 0 obj\n<< /Type /XObject /Subtype /Image /Width {$imgWidth} /Height {$imgHeight} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$jpegLen} >>\nstream\n{$jpegData}\nendstream\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offset = strlen($pdf);

        for ($i = 1; $i <= 5; $i++) {
            $offsets[$i] = $offset;
            $pdf .= $objects[$i];
            $offset = strlen($pdf);
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        file_put_contents($outputPath, $pdf);
    }

    /**
     * Convierte un documento de Office a PDF usando LibreOffice headless si está disponible.
     */
    protected function convertOfficeToPdf(string $filePath, string $ext): string
    {
        $binaries = ['soffice', 'libreoffice'];
        $foundBinary = null;

        foreach ($binaries as $bin) {
            $checkCmd = (PHP_OS_FAMILY === 'Windows')
                ? "where {$bin} 2>nul"
                : "which {$bin} 2>/dev/null";

            $output = [];
            $retCode = 0;
            @exec($checkCmd, $output, $retCode);

            if ($retCode === 0 && !empty($output[0])) {
                $foundBinary = trim($output[0]);
                break;
            }
        }

        // Si no está en PATH en Windows, probar rutas típicas de instalación
        if (!$foundBinary && PHP_OS_FAMILY === 'Windows') {
            $commonPaths = [
                'C:\\Program Files\\LibreOffice\\program\\soffice.exe',
                'C:\\Program Files (x86)\\LibreOffice\\program\\soffice.exe',
            ];
            foreach ($commonPaths as $p) {
                if (file_exists($p)) {
                    $foundBinary = "\"{$p}\"";
                    break;
                }
            }
        }

        if (!$foundBinary) {
            throw new \RuntimeException(
                "El servidor no tiene instalado LibreOffice para convertir archivos .{$ext} automáticamente. Por favor, exporta tu documento a PDF antes de subirlo."
            );
        }

        $outDir = sys_get_temp_dir();
        $cmd = "{$foundBinary} --headless --convert-to pdf --outdir \"{$outDir}\" \"{$filePath}\"";

        $output = [];
        $ret = 0;
        @exec($cmd, $output, $ret);

        $baseName = pathinfo($filePath, PATHINFO_FILENAME);
        $generatedPdf = $outDir . DIRECTORY_SEPARATOR . $baseName . '.pdf';

        if (!file_exists($generatedPdf)) {
            Log::error("Fallo al convertir documento con LibreOffice: " . implode("\n", $output));
            throw new \RuntimeException("No se pudo convertir el archivo .{$ext} a PDF.");
        }

        return $generatedPdf;
    }
}
