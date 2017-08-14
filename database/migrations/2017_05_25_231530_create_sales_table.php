<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('unit_id')->unsigned();
            $table->float('gross_sales');
            $table->float('tax')->default(0);
            $table->float('net_sales');
            $table->float('credit_sales')->default(0);
            $table->float('cash');
            $table->integer('transactions')->default(0);
            $table->integer('voids')->default(0);
            $table->float('voids_amount')->default(0);
            $table->float('voids_percent')->default(0);
            $table->integer('refunds')->default(0);
            $table->float('refunds_amount')->default(0);
            $table->float('refunds_percent')->default(0);
            $table->integer('discounts')->default(0);
            $table->float('discounts_amount')->default(0);
            $table->float('discounts_percent')->default(0);
            $table->string('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales');
    }
}
