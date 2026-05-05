<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateUserRolFromUsuarioToClientes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Actualizar todos los usuarios con rol 'usuario' a 'clientes'
        DB::table('users')
            ->where('rol', 'usuario')
            ->update(['rol' => 'clientes']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revertir el cambio: cambiar 'clientes' de vuelta a 'usuario'
        DB::table('users')
            ->where('rol', 'clientes')
            ->update(['rol' => 'usuario']);
    }
}
