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
        Schema::table('products', function (Blueprint $table) {
            // Tambahkan 2 kolom baru ini setelah kolom price
            $table->integer('discount_percent')->nullable()->after('price');
            $table->dateTime('discount_end_date')->nullable()->after('discount_percent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Hapus kolom jika migration di-rollback
            $table->dropColumn(['discount_percent', 'discount_end_date']);
        });
    }
};