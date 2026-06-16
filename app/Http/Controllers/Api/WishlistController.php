<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    // Method untuk mengambil semua produk di wishlist user yang sedang login
    public function index()
    {
        $wishlist = auth()->user()->wishlists()->with('product')->get();
        return response()->json([
            'success' => true,
            'data' => $wishlist
        ]);
    }

    // Method untuk menambah produk ke wishlist
    public function add($productId)
    {
        $user = auth()->user();
        $product = Product::findOrFail($productId);

        $wishlistItem = Wishlist::firstOrCreate([
            'user_id' => $user->id,
            'product_id' => $product->id
        ]);

        if ($wishlistItem->wasRecentlyCreated) {
            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil ditambahkan ke wishlist',
                'is_wishlisted' => true
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Produk sudah ada di wishlist',
            'is_wishlisted' => true
        ]);
    }

    // Method untuk menghapus produk dari wishlist
    public function remove($productId)
    {
        $user = auth()->user();

        $deleted = Wishlist::where('user_id', $user->id)
                           ->where('product_id', $productId)
                           ->delete();

        if ($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil dihapus dari wishlist',
                'is_wishlisted' => false
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Produk tidak ditemukan di wishlist'
        ]);
    }

    // Method untuk cek status produk di wishlist user
    public function check($productId)
    {
        $exists = Wishlist::where('user_id', auth()->id())
                          ->where('product_id', $productId)
                          ->exists();

        return response()->json([
            'success' => true,
            'is_wishlisted' => $exists
        ]);
    }
}