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
        'actual_arrival_date',
        'received_at',
        'status',
        'nationalized',
        'files',
        'credits',
        'do_code',
        'commercial_invoice_number',
        'proforma_invoice_number',
        'bl_number',
        'container_ref',
        'container_pdf',
        'proforma_pdf',
        'proforma_invoice_low_pdf',
        'invoice_pdf',
        'commercial_invoice_low_pdf',
        'bl_pdf',
        'packing_list_pdf',
        'apostillamiento_pdf',
        'other_documents_pdf',
        'shipping_company',
        'free_days_at_dest',
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

    /**
     * Calculate credit expiration date
     * Uses actual_arrival_date if available, otherwise arrival_date
     */
    public function getCreditExpirationDate()
    {
        if (!$this->credit_time) {
            return null;
        }

        $arrivalDate = $this->actual_arrival_date ?? $this->arrival_date;
        
        if (!$arrivalDate) {
            return null;
        }

        return \Carbon\Carbon::parse($arrivalDate)->addDays($this->credit_time);
    }

    /**
     * Check if credit is expired
     */
    public function isCreditExpired()
    {
        $expirationDate = $this->getCreditExpirationDate();
        
        if (!$expirationDate) {
            return false;
        }

        return $expirationDate->isPast();
    }

    /**
     * Get days until credit expiration (negative if expired)
     */
    public function getDaysUntilCreditExpiration()
    {
        $expirationDate = $this->getCreditExpirationDate();
        
        if (!$expirationDate) {
            return null;
        }

        return now()->diffInDays($expirationDate, false);
    }

    /**
     * Check if credit is about to expire (within 7 days)
     */
    public function isCreditExpiringSoon()
    {
        $daysUntil = $this->getDaysUntilCreditExpiration();
        
        if ($daysUntil === null) {
            return false;
        }

        return $daysUntil >= 0 && $daysUntil <= 7;
    }
}

