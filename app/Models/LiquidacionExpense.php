<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidacionExpense extends Model
{
    protected $table = 'liquidacion_expenses';

    protected $fillable = ['liquidacion_id', 'expense_category_id', 'valor', 'galones'];

    protected $casts = [
        'valor' => 'integer',
        'galones' => 'float',
    ];

    public function liquidacion(): BelongsTo
    {
        return $this->belongsTo(Liquidacion::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
