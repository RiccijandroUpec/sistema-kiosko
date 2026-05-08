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
    public static function generateJobReference(): string
    {
        do {
            $reference = strtoupper(substr(md5(uniqid()), 0, 8));
        } while (self::where('job_reference', $reference)->exists());

        return $reference;
    }
}
