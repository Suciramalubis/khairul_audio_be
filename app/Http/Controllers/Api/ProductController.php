<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductGallery;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::with(['category', 'galleries'])->latest()->get());
    }

    public function store(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'name'        => 'required',
            'price'       => 'required|numeric',
            'stock'       => 'required|numeric',
            'category_id' => 'required',
            'image'       => 'nullable|image|max:10240',
            // Kita validasi 'gallery' sebagai file (karena dikirim sebagai array file)
            'gallery.*'   => 'image|max:10240', 
        ]);

        $data = $request->except('gallery');

        // 2. Simpan Foto Utama (Sampul)
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = url('storage/' . $path);
        } else {
            $data['image_url'] = 'https://placehold.co/400'; 
        }

        $product = Product::create($data);

        // 3. SIMPAN FOTO TAMBAHAN (GALLERY)
        // PENTING: React mengirim 'gallery[]', Laravel membacanya sebagai 'gallery'
        if ($request->hasFile('gallery')) {
            $files = $request->file('gallery');
            foreach ($files as $file) {
                // Simpan file fisik
                $galleryPath = $file->store('product_galleries', 'public');
                
                // Simpan ke database
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
            'gallery.*' => 'image|max:10240',
        ]);

        $data = $request->except(['image', 'gallery', '_method']); 

        // Update Foto Utama
        if ($request->hasFile('image')) {
            if ($product->image_url && !str_contains($product->image_url, 'placehold.co')) {
                $oldPath = str_replace(url('storage/'), '', $product->image_url);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = url('storage/' . $path);
        }

        $product->update($data);

        // UPDATE/TAMBAH FOTO TAMBAHAN
        if ($request->hasFile('gallery')) {
            $files = $request->file('gallery');
            foreach ($files as $file) {
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
        return response()->json(Product::with(['category', 'galleries', 'reviews.user', 'reviews.images'])->findOrFail($id));
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
