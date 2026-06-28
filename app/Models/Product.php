<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory, SoftDeletes;

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
        'discount_end_date',
        'sold_count'
    ];

    protected $dates = ['deleted_at'];

    protected $appends = ['product_code', 'total_sold'];

    public function getProductCodeAttribute()
    {
        return 'PD' . str_pad($this->id, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Menghitung total sold dari order yang sudah selesai (status completed/selesai)
     */
    public function getTotalSoldAttribute()
    {
        $realSoldCount = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $this->id)
            ->where(function ($query) {
                $query->where('orders.status', 'completed')
                      ->orWhere('orders.status', 'selesai')
                      ->orWhere('orders.status', 'Selesai');
            })
            ->sum('order_items.quantity');

        return max((int) $this->sold_count, (int) $realSoldCount);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function galleries(): HasMany
    {
        return $this->hasMany(ProductGallery::class);
    }

    public function wishlistedByUsers()
    {
        return $this->belongsToMany(User::class, 'wishlists');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}