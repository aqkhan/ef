<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('unit_id')->unsigned();
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('lastname');
            $table->string('payroll_id')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('zip');
            $table->string('country');
            $table->string('phone')->nullable();
            $table->string('gender');
            $table->date('hire_date');
            $table->integer('job_title_id');
            $table->string('job_title');
            $table->string('type');
            $table->float('wage');
            $table->date('birth_date');
            $table->string('social_security_num')->unique();
            $table->string('marital_status');
            $table->integer('exemptions');
            $table->string('status')->default('Active');
            $table->date('termination_date')->nullable();
            $table->string('termination_reason')->nullable();
            $table->date('rehire_date')->nullable();
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
        Schema::dropIfExists('employees');
    }
}
