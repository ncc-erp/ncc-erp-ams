<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeToWebhooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->json('type')->nullable();
        });
    }

    public function down()
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
