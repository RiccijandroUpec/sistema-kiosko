<?php

namespace Database\Factories;

use App\Models\PdfFile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PdfFile>
 */
class PdfFileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->uuid() . '.pdf';

        return [
            'filename' => $filename,
            'original_name' => 'documento.pdf',
            'pages_count' => 5,
            'file_path' => 'pdfs/' . $filename,
            'file_size' => 100,
        ];
    }
}
