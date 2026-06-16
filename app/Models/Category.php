<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    /**
     * Kolom yang boleh diisi secara massal (mass assignable).
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Mendefinisikan relasi "satu Kategori memiliki banyak Produk".
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}