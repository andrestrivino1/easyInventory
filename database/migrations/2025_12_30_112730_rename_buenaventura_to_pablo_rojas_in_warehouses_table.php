<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RenameBuenaventuraToPabloRojasInWarehousesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('warehouses')
            ->where('nombre', 'Buenaventura')
            ->update(['nombre' => 'Pablo Rojas']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('warehouses')
            ->where('nombre', 'Pablo Rojas')
            ->update(['nombre' => 'Buenaventura']);
    }
}
