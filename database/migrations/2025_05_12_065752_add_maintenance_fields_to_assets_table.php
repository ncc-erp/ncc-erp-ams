<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMaintenanceFieldsToAssetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    // ...existing code...
    public function up()
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->date('maintenance_date')->nullable();
            $table->integer('maintenance_cycle')->nullable();
        });
    }

    public function down()
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['maintenance_date', 'maintenance_cycle']);
        });
    }
    // ...existing code...
}
