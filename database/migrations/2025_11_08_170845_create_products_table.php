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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2); // Harga (misal: 1.500.000,00)
            $table->integer('stock'); 
            $table->string('image_url')->nullable(); 

            // ✅ INI KOLOM TAMBAHAN UNTUK MEMPERBAIKI ERROR 500
            $table->integer('weight')->default(1000)->comment('Berat dalam gram (default 1kg)');
            $table->string('dimensions')->nullable()->comment('Format: PxLxT');
            $table->string('status')->default('active'); // Status produk (active/inactive)

            // Kunci asing untuk menghubungkan ke tabel 'categories'
            $table->foreignId('category_id')
                  ->constrained('categories')
                  ->onDelete('cascade');
                  
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};