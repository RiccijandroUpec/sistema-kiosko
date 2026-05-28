<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class PdfFile extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'filename',
        'original_name',
        'email',
        'pages_count',
        'file_path',
        'file_size',
    ];
    
    /**
     * Get the print jobs associated with the PDF file.
     */
    public function printJobs(): HasMany
    {
        return $this->hasMany(PrintJob::class);
    }
}
