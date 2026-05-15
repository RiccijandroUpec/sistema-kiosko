<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PrintJob extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'job_reference',
        'pdf_file_id',
        'email',
        'copies',
        'color_type',
        'paper_size',
        'orientation',
        'cost',
        'status',
        'paid',
        'printed_at',
    ];

    protected $casts = [
        'paid' => 'boolean',
        'printed_at' => 'datetime',
    ];
    
    /**
     * Get the PDF file associated with the print job.
     */
    public function pdfFile(): BelongsTo
    {
        return $this->belongsTo(PdfFile::class);
    }

    /**
     * Get the payment for this print job.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * Generar referencia única para este trabajo.
     */
    public static function generateJobReference(?string $filename = null): string
    {
        $prefix = '';
        if ($filename) {
            $nameOnly = pathinfo($filename, PATHINFO_FILENAME);
            $clean = preg_replace('/[^A-Za-z0-9]/', '', $nameOnly);
            $prefix = strtoupper(substr($clean, 0, 4)) . '-';
        }

        $datePart = date('dm'); // Día y Mes (ej: 1405)

        do {
            $code = strtoupper(substr(md5(uniqid()), 0, 2)); // 2 letras finales para no hacerlo muy largo
            $reference = $prefix . $datePart . '-' . $code;
        } while (self::where('job_reference', $reference)->exists());

        return $reference;
    }
}
