<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('pp_id');
            $table->string('reference_id')->nullable();
            $table->string('event_code', 5);
            $table->datetime('initiation_date');
            $table->string('currency', 5);
            $table->float('value');
            $table->text('description');

            $table->unsignedBigInteger('firefly_id')->nullable();

            $table->foreignId('payer_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}
