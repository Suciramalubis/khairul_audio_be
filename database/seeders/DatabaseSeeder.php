<?php
// Lokasi: database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        $this->call([
            UserSeeder::class,
            CategorySeeder::class, 
            ProductSeeder::class,  
        ]);

        // 3. Aktifkan kembali pengecekan foreign key
        Schema::enableForeignKeyConstraints();
    }
}