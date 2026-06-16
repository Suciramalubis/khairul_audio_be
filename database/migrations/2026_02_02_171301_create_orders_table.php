<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique()->nullable();
            $table->decimal('total_price', 15, 2);
            $table->string('status')->default('pending'); 
            $table->string('shipping_courier')->nullable(); 
            $table->string('shipping_service')->nullable(); 
            $table->integer('shipping_cost')->default(0);
            $table->text('shipping_address')->nullable();
            $table->string('tracking_number')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
