<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_logs', function (Blueprint $table) {
            $table->id();
            $table->string('send_to');
            $table->string('subject')->nullable();
            $table->string('message_type')->nullable(); // checkout, checkin, etc.
            $table->longText('message_content')->nullable();
            $table->text('system_response')->nullable();
            $table->tinyInteger('status')->default(0); // 0: failed, 1: success
            $table->integer('creator_id')->unsigned()->nullable();
            $table->integer('company_id')->unsigned()->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['send_to']);
            $table->index(['status']);
            $table->index(['creator_id']);
            $table->index(['company_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mail_logs');
    }
}
