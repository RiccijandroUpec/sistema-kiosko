<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'print_job_id',
        'amount',
        'type',
        'description',
        'payment_method',
        'status',
    ];

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the print job associated with the transaction.
     */
    public function printJob(): BelongsTo
    {
        return $this->belongsTo(PrintJob::class);
    }
}
