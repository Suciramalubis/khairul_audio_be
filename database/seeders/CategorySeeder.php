<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run()
    {
        // Hapus data lama
        DB::table('categories')->truncate();

        // Masukkan Kategori Tetap (Sesuai ID di ProductSeeder)
        $categories = [
            ['id' => 1, 'name' => 'Head Unit', 'slug' => 'head-unit'],
            ['id' => 2, 'name' => 'Speaker', 'slug' => 'speaker'],
            ['id' => 3, 'name' => 'Subwoofer', 'slug' => 'subwoofer'],
            ['id' => 4, 'name' => 'Power & DSP', 'slug' => 'power-dsp'],
            ['id' => 5, 'name' => 'Aksesoris', 'slug' => 'aksesoris'],
        ];

        DB::table('categories')->insert($categories);
    }
}