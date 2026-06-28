<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductGallery;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'galleries', 'reviews'])->latest()->get();

        foreach ($products as $product) {
            $realSoldCount = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $product->id)
                ->where(function ($query) {
                    $query->where('orders.status', 'completed')
                          ->orWhere('orders.status', 'selesai')
                          ->orWhere('orders.status', 'Selesai');
                })
                ->sum('order_items.quantity');

            $newSold = max((int) $product->sold_count, (int) $realSoldCount);
            
            // Fallback: Jika terjual 0 tapi ulasan ada, pastikan jumlah terjual = jumlah ulasan
            $reviewCount = $product->reviews->count();
            if ($newSold == 0 && $reviewCount > 0) {
                $newSold = $reviewCount;
            }

            if ($newSold != $product->sold_count) {
                DB::table('products')->where('id', $product->id)->update(['sold_count' => $newSold]);
            }

            // WAJIB: Paksa atribut masuk ke dalam Response JSON Frontend
            $product->setAttribute('sold_count', $newSold);
            
            // Opsional: Hitung rata-rata rating dari backend
            $avgRating = $product->reviews->avg('rating') ?: 0;
            $product->setAttribute('rating', round($avgRating, 1));
        }

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required',
            'price'       => 'required|numeric',
            'stock'       => 'required|numeric',
            'category_id' => 'required',
            'image'       => 'nullable|image|max:10240',
<<<<<<< HEAD
            'gallery.*'   => 'image|max:10240',
=======
            // Kita validasi 'gallery' sebagai file (karena dikirim sebagai array file)
            'gallery.*'   => 'image|max:10240', 
>>>>>>> 1bf70e54e96cab20b6e5b05daf16bb36485a1645
        ]);

        $data = $request->except('gallery');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = url('storage/' . $path);
        } else {
            $data['image_url'] = 'https://placehold.co/400';
        }

        $product = Product::create($data);

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $galleryPath = $file->store('product_galleries', 'public');
                ProductGallery::create([
                    'product_id' => $product->id,
                    'image_url'  => url('storage/' . $galleryPath)
                ]);
            }
        }

        return response()->json(['message' => 'Berhasil', 'data' => $product->load('galleries')]);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
<<<<<<< HEAD
            'image'       => 'nullable|image|max:10240',
            'gallery.*'   => 'image|max:10240',
=======
            'gallery.*' => 'image|max:10240',
>>>>>>> 1bf70e54e96cab20b6e5b05daf16bb36485a1645
        ]);

        $data = $request->except(['image', 'gallery', '_method']);

        if ($request->hasFile('image')) {
            if ($product->image_url && !str_contains($product->image_url, 'placehold.co')) {
                $oldPath = str_replace(url('storage/'), '', $product->image_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = url('storage/' . $path);
        }

        $product->update($data);

        if ($request->hasFile('gallery')) {
            foreach ($request->file('gallery') as $file) {
                $galleryPath = $file->store('product_galleries', 'public');
                ProductGallery::create([
                    'product_id' => $product->id,
                    'image_url'  => url('storage/' . $galleryPath)
                ]);
            }
        }

        return response()->json(['message' => 'Diupdate', 'data' => $product->load('galleries')]);
    }

    public function show($id)
    {
        $product = Product::with(['category', 'galleries', 'reviews.user', 'reviews.images'])->findOrFail($id);

        // Sinkronisasi sold_count dari order yang sudah selesai
        $realSoldCount = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $id)
            ->where(function ($query) {
                $query->where('orders.status', 'completed')
                      ->orWhere('orders.status', 'selesai')
                      ->orWhere('orders.status', 'Selesai');
            })
            ->sum('order_items.quantity');

        $newSold = max((int) $product->sold_count, (int) $realSoldCount);
        
        $reviewCount = $product->reviews->count();
        if ($newSold == 0 && $reviewCount > 0) {
            $newSold = $reviewCount;
        }

        if ($newSold != $product->sold_count) {
            $product->sold_count = $newSold;
            $product->save();
        }

        // WAJIB: Paksa atribut masuk ke dalam Response JSON Frontend
        $product->setAttribute('sold_count', $newSold);
        $avgRating = $product->reviews->avg('rating') ?: 0;
        $product->setAttribute('rating', round($avgRating, 1));

        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::with('galleries')->findOrFail($id);
        if ($product->image_url && !str_contains($product->image_url, 'placehold.co')) {
            Storage::disk('public')->delete(str_replace(url('storage/'), '', $product->image_url));
        }
        foreach ($product->galleries as $gallery) {
            Storage::disk('public')->delete(str_replace(url('storage/'), '', $gallery->image_url));
        }
        $product->delete();
        return response()->json(['message' => 'Dihapus']);
    }
}
