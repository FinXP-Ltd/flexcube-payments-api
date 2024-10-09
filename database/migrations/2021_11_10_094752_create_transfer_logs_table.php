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
        Schema::create('transfer_logs', function (Blueprint $table) {
            $table->bigIncrements('id')->index('id');
            $table->longText('payload')->index('payload');
            $table->string('transfer_type')->index('transfer_type');
            $table->string('status')->nullable()->index('status');
            $table->longText('response')->nullable()->index('response');
            $table->string('transaction_ref_no')->nullable()->index('transaction_ref_no');
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
        Schema::dropIfExists('transfer_logs');
    }
};
