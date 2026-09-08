<?php

namespace Tests\Feature;

use App\Models\Kiosko;
use App\Models\PdfFile;
use App\Services\DocumentConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class DocumentConversionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
        $this->artisan('migrate');
    }

    public function test_convierte_imagen_jpg_a_pdf_valido_y_crea_registro(): void
    {
        // Crear una imagen JPEG mínima en memoria usando GD
        $img = imagecreatetruecolor(200, 200);
        $color = imagecolorallocate($img, 66, 133, 244);
        imagefilledrectangle($img, 0, 0, 200, 200, $color);

        $tempImgPath = tempnam(sys_get_temp_dir(), 'test_img_') . '.jpg';
        imagejpeg($img, $tempImgPath);
        imagedestroy($img);

        $file = new UploadedFile(
            $tempImgPath,
            'recibo.jpg',
            'image/jpeg',
            null,
            true
        );

        $response = $this->post(route('kiosko.upload-pdf'), [
            'pdf' => $file,
        ]);

        $response->assertRedirect();

        $pdfFile = PdfFile::latest()->first();
        $this->assertNotNull($pdfFile);
        $this->assertEquals('recibo.jpg', $pdfFile->original_name);
        $this->assertEquals(1, $pdfFile->pages_count);
        $this->assertFileExists(storage_path('app/public/' . $pdfFile->file_path));

        // Limpiar archivo temporal
        if (file_exists($tempImgPath)) @unlink($tempImgPath);
    }

    public function test_servicio_conversion_mantiene_pdf_original(): void
    {
        // PDF mínimo válido
        $pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 612 792]>>endobj\nxref\n0 4\n0000000000 65535 f \n0000000009 00000 n \n0000000052 00000 n \n0000000108 00000 n \ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n185\n%%EOF\n";
        $tempPdf = tempnam(sys_get_temp_dir(), 'test_doc_') . '.pdf';
        file_put_contents($tempPdf, $pdfContent);

        $file = new UploadedFile($tempPdf, 'document.pdf', 'application/pdf', null, true);

        $service = new DocumentConversionService();
        $result = $service->convertToPdf($file);

        $this->assertFalse($result['converted']);
        $this->assertEquals('pdf', $result['original_extension']);

        if (file_exists($tempPdf)) @unlink($tempPdf);
    }

    public function test_configura_trabajo_con_tamano_carta_y_oficio(): void
    {
        $kiosko = Kiosko::create([
            'nombre_comercial' => 'Kiosko Papel Test',
            'estado' => 'activo',
            'precio_blanco_negro' => 0.05,
            'precio_color' => 0.20,
            'nombre_cups' => 'EpsonPrinter',
            'pin' => '1234',
        ]);

        $pdf = PdfFile::create([
            'filename' => 'doc_paper.pdf',
            'original_name' => 'doc_paper.pdf',
            'file_path' => 'pdfs/doc_paper.pdf',
            'pages_count' => 3,
            'file_size' => 120,
        ]);

        // Probar con tamaño Letter (Carta)
        $response = $this->post(route('kiosko.create-job', $pdf->id), [
            'kiosk_id' => $kiosko->id,
            'copies' => 1,
            'color_type' => 'bw',
            'paper_size' => 'letter',
            'orientation' => 'portrait',
        ]);

        $response->assertRedirect();
        $orden = \App\Models\OrdenImpresion::latest()->first();
        $this->assertNotNull($orden);
        $this->assertEquals('letter', $orden->papel);

        // Probar con tamaño Legal (Oficio)
        $response2 = $this->post(route('kiosko.create-job', $pdf->id), [
            'kiosk_id' => $kiosko->id,
            'copies' => 1,
            'color_type' => 'color',
            'paper_size' => 'legal',
            'orientation' => 'landscape',
        ]);

        $response2->assertRedirect();
        $orden2 = \App\Models\OrdenImpresion::where('papel', 'legal')->first();
        $this->assertNotNull($orden2);
        $this->assertEquals('legal', $orden2->papel);
        $this->assertEquals('landscape', $orden2->orientacion);
    }
}
