<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLiquidacionExpensesTable extends Migration
{
    public function up()
    {
        Schema::create('liquidacion_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liquidacion_id')->constrained('liquidaciones')->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->decimal('valor', 12, 0)->default(0);
            $table->decimal('galones', 8, 2)->nullable();
            $table->timestamps();
            $table->unique(['liquidacion_id', 'expense_category_id'], 'uq_liq_exp_cat');
            $table->index('expense_category_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('liquidacion_expenses');
    }
}
