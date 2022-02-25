<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePayersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('payers', function (Blueprint $table) {
            $table->id();
            $table->string('pp_id');
            $table->string('email');
            $table->string('name');
            $table->string('country_code', 5);

            $table->unsignedBigInteger('firefly_expense_id')->nullable();
            $table->unsignedBigInteger('firefly_revenue_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('payers');
    }
}
