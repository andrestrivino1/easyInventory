<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportContainer extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_id',
        'reference',
        'pdf_path',
        'image_pdf_path',
    ];

    public function import()
    {
        return $this->belongsTo(Import::class);
    }
}


