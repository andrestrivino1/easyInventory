<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Import extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'origin',
        'destination',
        'departure_date',
        'arrival_date',
        'status',
        'files',
        'credits',
        'do_code',
        'product_name',
        'container_ref',
        'container_pdf',
        'container_images',
        'proforma_pdf',
        'proforma_invoice_low_pdf',
        'invoice_pdf',
        'commercial_invoice_low_pdf',
        'bl_pdf',
        'packing_list_pdf',
        'apostillamiento_pdf',
        'etd',
        'shipping_company',
        'free_days_at_dest',
        'supplier',
        'credit_time',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function containers()
    {
        return $this->hasMany(ImportContainer::class);
    }
}

