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
        Schema::create('email_changes', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('new_email')->nullable();
            $table->char('one_time_password', 8)->nullable();
            $table->boolean('verified_owner')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('otp_created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_changes');
    }
};
