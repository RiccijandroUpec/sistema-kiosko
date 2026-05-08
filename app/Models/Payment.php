<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'print_job_id',
        'reference_code',
        'amount',
        'status',
        'bank_name',
        'notes',
    ];

    /**
     * Get the print job associated with the payment.
     */
    public function printJob(): BelongsTo
    {
        return $this->belongsTo(PrintJob::class);
    }

    /**
     * Generar código de referencia único.
     */
    public static function generateReferenceCode(): string
    {
        do {
            $code = 'REF' . date('YmdHi') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (self::where('reference_code', $code)->exists());

        return $code;
    }
}
