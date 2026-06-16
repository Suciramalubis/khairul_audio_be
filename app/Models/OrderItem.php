<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Relasi balik ke Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Relasi ke Produk (PENTING: agar nama & gambar produk muncul)
    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}