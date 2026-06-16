<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
        'name' => fake()->words(3, true), // Nama produk (3 kata)
        'description' => fake()->paragraph(), // Deskripsi produk
        'price' => fake()->numberBetween(150000, 4000000), // Harga antara 150rb - 4jt
        'stock' => fake()->numberBetween(5, 50), // Stok antara 5 - 50
        'image_url' => 'https://via.placeholder.com/640x480.png/003366?text=Audio+Produk', // URL gambar placeholder
        'category_id' => 1, // Kita akan perbaiki ini di langkah berikutnya
        ];
    }
}
