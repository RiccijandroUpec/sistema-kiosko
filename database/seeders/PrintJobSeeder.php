<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PdfFile;
use App\Models\PrintJob;
use Illuminate\Support\Str;

class PrintJobSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create 3 dummy PDF entries
        $pdfs = [];
        for ($i = 1; $i <= 3; $i++) {
            $pdfs[] = PdfFile::create([
                'filename' => 'demo_' . $i . '.pdf',
                'original_name' => 'Demo PDF ' . $i . '.pdf',
                'email' => 'user' . $i . '@example.com',
                'pages_count' => rand(1, 10),
                'file_path' => 'pdfs/demo_' . $i . '.pdf',
                'file_size' => rand(100, 500),
            ]);
        }

        // Create 10 print jobs linked to random PDF files
        for ($j = 1; $j <= 10; $j++) {
            $pdf = $pdfs[array_rand($pdfs)];
            PrintJob::create([
                'job_reference' => Str::upper(substr(md5(uniqid()), 0, 8)),
                'pdf_file_id' => $pdf->id,
                'email' => $pdf->email,
                'copies' => rand(1, 5),
                'color_type' => ['bw', 'color'][array_rand(['bw', 'color'])],
                'paper_size' => ['a4', 'letter'][array_rand(['a4', 'letter'])],
                'orientation' => ['portrait', 'landscape'][array_rand(['portrait', 'landscape'])],
                'cost' => rand(5, 50),
                'status' => ['pending', 'printing', 'completed'][array_rand(['pending', 'printing', 'completed'])],
                'paid' => false,
                'printed_at' => null,
            ]);
        }
    }
}
?>
