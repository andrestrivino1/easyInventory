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
        Schema::table('imports', function (Blueprint $table) {
            // Eliminar campos obsoletos
            $table->dropColumn(['product_name', 'etd', 'supplier']);
            
            // Agregar nuevos campos
            $table->string('commercial_invoice_number')->nullable()->after('do_code');
            $table->string('proforma_invoice_number')->nullable()->after('commercial_invoice_number');
            $table->string('bl_number')->nullable()->after('proforma_invoice_number');
            $table->string('other_documents_pdf')->nullable()->after('apostillamiento_pdf');
            $table->date('actual_arrival_date')->nullable()->after('arrival_date')->comment('Fecha real de llegada');
            $table->timestamp('received_at')->nullable()->after('actual_arrival_date')->comment('Fecha y hora cuando se marcó como recibido');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('imports', function (Blueprint $table) {
            // Restaurar campos eliminados
            $table->string('product_name')->nullable();
            $table->string('etd')->nullable();
            $table->string('supplier')->nullable();
            
            // Eliminar nuevos campos
            $table->dropColumn([
                'commercial_invoice_number',
                'proforma_invoice_number',
                'bl_number',
                'other_documents_pdf',
                'actual_arrival_date',
                'received_at'
            ]);
        });
    }
};
