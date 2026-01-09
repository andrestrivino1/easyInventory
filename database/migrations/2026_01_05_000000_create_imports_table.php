<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Proveedor que sube la importación
            $table->string('origin');
            $table->string('destination');
            $table->date('departure_date');
            $table->date('arrival_date')->nullable();
            $table->string('status')->default('pending'); // Estado del proceso
            $table->string('files')->nullable(); // ruta o nombres de archivos PDF (json array o csv)
            $table->decimal('credits', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('imports');
    }
};

