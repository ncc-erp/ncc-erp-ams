<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebhookIdToConsumablesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('consumables', function (Blueprint $table) {
            $table->unsignedBigInteger('webhook_id')->nullable()->after('maintenance_cycle');
            $table->foreign('webhook_id')->references('id')->on('webhooks')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('consumables', function (Blueprint $table) {
            $table->dropForeign(['webhook_id']);
            $table->dropColumn('webhook_id');
        });
    }
}
