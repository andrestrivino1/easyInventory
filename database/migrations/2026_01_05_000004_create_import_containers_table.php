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
        Schema::create('import_containers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id');
            $table->string('reference');
            $table->string('pdf_path')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();

            $table->foreign('import_id')->references('id')->on('imports')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('import_containers');
    }
};


