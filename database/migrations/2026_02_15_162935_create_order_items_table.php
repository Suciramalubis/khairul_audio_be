<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
        $table->id();
        // Hubungkan ke tabel orders (Wajib ada onDelete cascade agar kalau order dihapus, itemnya ikut terhapus)
        $table->foreignId('order_id')->constrained()->onDelete('cascade');
        
        // Hubungkan ke tabel products
        $table->foreignId('product_id')->constrained()->onDelete('cascade');
        
        $table->integer('quantity');
        $table->decimal('price', 15, 2); // Harga satuan saat dibeli
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
