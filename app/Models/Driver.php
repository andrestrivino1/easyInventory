<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Driver extends Model
{
    protected $table = 'drivers';
    public $timestamps = false;
    protected $fillable = [
        'name',
        'identity',
        'phone',
        'photo_path',
        'vehicle_plate',
        'vehicle_photo_path',
        'social_security_date',
        'social_security_pdf',
        'vehicle_owner',
        'active',
    ];

    /**
     * Check if social security is expired (more than 1 day past expiration date)
     */
    public function isSocialSecurityExpired()
    {
        if (!$this->social_security_date) {
            return false; // Si no hay fecha, no se considera vencida
        }
        
        $expirationDate = Carbon::parse($this->social_security_date);
        $today = Carbon::today();
        
        // Se considera vencida si pasó más de 1 día desde la fecha de vencimiento
        return $today->greaterThan($expirationDate);
    }

    /**
     * Scope to get only drivers with valid social security
     */
    public function scopeWithValidSocialSecurity($query)
    {
        return $query->where(function($q) {
            $q->whereNull('social_security_date')
              ->orWhere('social_security_date', '>=', Carbon::today()->toDateString());
        });
    }

    /**
     * Scope to get active drivers with valid social security
     */
    public function scopeActiveWithValidSocialSecurity($query)
    {
        return $query->where('active', true)
                     ->where(function($q) {
                         $q->whereNull('social_security_date')
                           ->orWhere('social_security_date', '>=', Carbon::today()->toDateString());
                     });
    }
}
