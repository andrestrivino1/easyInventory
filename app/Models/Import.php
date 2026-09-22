<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Import extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Piso del consecutivo do_code por año (yy => número mínimo del PRÓXIMO do_code).
     *
     * Se usa para continuar la numeración tras una limpieza/migración de datos,
     * evitando reutilizar números ya emitidos. Solo aplica si el consecutivo
     * calculado a partir de los registros existentes es MENOR que este piso.
     *
     * 2026: la próxima importación debe salir como VJP26-055.
     */
    public const DO_CODE_FLOOR = [
        '26' => 55,
    ];

    /**
     * Calcula el siguiente do_code del año que corresponda.
     *
     * Fuente única de la regla de numeración: la usan tanto la creación de
     * importaciones como el comando de limpieza para informar qué número se
     * emitirá a continuación.
     *
     * withTrashed() es la pieza clave: las importaciones con borrado suave
     * siguen contando, de modo que un número emitido nunca se reutiliza aunque
     * se haya vaciado el módulo.
     */
    public static function nextDoCode(?string $arrivalDate = null): string
    {
        $year = $arrivalDate ? date('y', strtotime($arrivalDate)) : date('y');

        $last = static::withTrashed()
            ->whereRaw('SUBSTRING(do_code, 4, 2) = ?', [$year])
            ->where(function ($query) use ($year, $arrivalDate) {
                if ($arrivalDate) {
                    $query->whereYear('arrival_date', '20' . $year);
                } else {
                    $query->whereYear('created_at', '20' . $year);
                }
            })
            ->orderByDesc('do_code')
            ->first();

        $next = 1;
        if ($last && preg_match('/VJP' . $year . '-(\d{3})/', $last->do_code, $m)) {
            $next = (int) $m[1] + 1;
        }

        // Piso del consecutivo: nunca emitir por debajo del mínimo del año.
        $floor = self::DO_CODE_FLOOR[$year] ?? 0;
        if ($next < $floor) {
            $next = $floor;
        }

        return sprintf('VJP%s-%03d', $year, $next);
    }

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
        'credit_paid',
        'delivered_to_transport_at',
        'delivered_to_transport_by_user_id',
        'admin_confirmed_at',
        'arrival_confirmed_by_user_id',
    ];

    protected $casts = [
        'delivered_to_transport_at' => 'datetime',
        'admin_confirmed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function containers()
    {
        return $this->hasMany(ImportContainer::class);
    }

    public function arrivalConfirmedByUser()
    {
        return $this->belongsTo(User::class, 'arrival_confirmed_by_user_id');
    }

    public function deliveredToTransportByUser()
    {
        return $this->belongsTo(User::class, 'delivered_to_transport_by_user_id');
    }

    public function itr()
    {
        return $this->hasOne(Itr::class);
    }

    /** Si el admin ya confirmó esta importación (va al final del listado y se deshabilita) */
    public function isAdminConfirmed(): bool
    {
        return $this->admin_confirmed_at !== null;
    }

    /** Si ya fue marcada como entregada a transporte */
    public function isDeliveredToTransport(): bool
    {
        return $this->delivered_to_transport_at !== null;
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

