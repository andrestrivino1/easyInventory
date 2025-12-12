<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Container extends Model
{
    protected $table = 'containers';
    public $timestamps = false;
    protected $fillable = [
        'reference',
        'product_name',
        'boxes',
        'sheets_per_box',
        'note'
    ];
}
