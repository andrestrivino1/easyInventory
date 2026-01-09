<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAdditionalDocumentFieldsToImportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->string('proforma_invoice_low_pdf')->nullable()->after('proforma_pdf');
            $table->string('commercial_invoice_low_pdf')->nullable()->after('invoice_pdf');
            $table->string('packing_list_pdf')->nullable()->after('bl_pdf');
            $table->string('apostillamiento_pdf')->nullable()->after('packing_list_pdf');
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
            $table->dropColumn([
                'proforma_invoice_low_pdf',
                'commercial_invoice_low_pdf',
                'packing_list_pdf',
                'apostillamiento_pdf'
            ]);
        });
    }
}
