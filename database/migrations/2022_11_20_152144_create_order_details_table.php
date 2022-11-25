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
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->ulid('order_id');
            $table->unsignedBigInteger('product_id');
            $table->string('name', 50);
            $table->string('slug', 50);
            $table->string('display_name', 50);
            $table->tinyInteger('primary_attribute')->nullable();
            $table->float('amount', 6, 2);
            $table->tinyInteger('quantity');
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
        Schema::dropIfExists('order_details');
    }
};
