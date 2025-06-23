<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToKomuMessageLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('komu_message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('send_to')->nullable();
            $table->text('message')->nullable();
            $table->text('system_response')->nullable();
            $table->integer('status')->default(0);
            $table->integer('creator_id')->nullable();
            $table->integer('company_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('komu_message_logs');
    }
}
