<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; 
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Kolom yang boleh diisi secara massal (mass assignable).
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'image_url',
        'category_id',
        'weight',
        'dimensions',   
        'status',
        'discount_percent', 
        'discount_end_date'
    ];

    /**
     * Kolom yang harus dikonversi ke tipe data Carbon (tanggal)
     */
    protected $dates = ['deleted_at']; 

    /**
     * PENTING: Memberi tahu Laravel untuk selalu menyertakan 'product_code'
     * setiap kali data produk diambil (JSON response).
     */
    protected $appends = ['product_code'];

    /**
     * ACCESSOR: Membuat "Virtual Column" untuk kode produk.
     */
    public function getProductCodeAttribute()
    {
        if (!$this->id) {
            return null;
        }
        return 'PD' . str_pad($this->id, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Relasi ke Kategori
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi ke Galeri Foto
     */
    public function galleries(): HasMany
    {
        return $this->hasMany(ProductGallery::class);
    }

    /**
     * Relasi ke Wishlist
     */
    public function wishlistedByUsers()
    {
        return $this->belongsToMany(User::class, 'wishlists');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}