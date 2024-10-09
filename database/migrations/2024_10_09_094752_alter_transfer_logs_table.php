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
        Schema::table('transfer_logs', function (Blueprint $table) {
            $table->bigIncrements('id')->index('id')->change();
            $table->string('status')->nullable()->index('status')->change();
            $table->string('transaction_ref_no')->nullable()->index('transaction_ref_no')->change();

            $table->string('transfer_type')->after('status')->index('transfer_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transfer_logs', function (Blueprint $table) {
            $table->dropColumn('transfer_type');
        });
    }
};
