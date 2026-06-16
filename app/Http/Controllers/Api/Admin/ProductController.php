<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductGallery;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // 1. LIHAT SEMUA PRODUK
    public function index()
    {
        // Panggil produk beserta relasi category dan galleries
        return response()->json(Product::with(['category', 'galleries'])->latest()->get());
    }

    // 2. TAMBAH PRODUK BARU
    public function store(Request $request)
    {
        // Validasi data yang masuk, termasuk weight, dimensions, status, dan DISKON
        $request->validate([
            'name'              => 'required|string',
            'price'             => 'required|numeric',
            'stock'             => 'required|numeric',
            'category_id'       => 'required',
            'description'       => 'nullable|string',
            'weight'            => 'nullable|numeric', 
            'dimensions'        => 'nullable|string',  
            'status'            => 'nullable|string',  
            'discount_percent'  => 'nullable|numeric|min:1|max:99', 
            'discount_end_date' => 'nullable|date',                 
            'image'             => 'nullable|image|max:5120',
            'gallery.*'         => 'image|max:5120',
        ]);

        // Ambil semua input kecuali gallery (karena gallery masuk ke tabel beda)
        $data = $request->except(['gallery']);

        // Simpan Foto Utama (Sampul)
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = url('storage/' . $path);
        } else {
            $data['image_url'] = 'https://placehold.co/400'; 
        }

        // Set default status jika kosong
        if (!isset($data['status'])) {
            $data['status'] = 'active';
        }

        // Buat Produk (Otomatis menyimpan name, price, discount_percent, dll)
        $product = Product::create($data);

        // Simpan Foto Tambahan (Gallery) ke tabel product_galleries
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

        return response()->json(['message' => 'Produk berhasil ditambahkan', 'data' => $product->load('galleries')]);
    }

    // 3. AMBIL 1 PRODUK (Untuk Form Edit & Detail)
    public function show($id)
    {
        return response()->json(Product::with(['category', 'galleries'])->findOrFail($id));
    }

    // 4. UPDATE PRODUK
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        // Validasi data saat diupdate
        $request->validate([
            'name'              => 'required|string',
            'price'             => 'required|numeric',
            'stock'             => 'required|numeric',
            'category_id'       => 'required',
            'description'       => 'nullable|string',
            'weight'            => 'nullable|numeric', 
            'dimensions'        => 'nullable|string',  
            'status'            => 'nullable|string',  
            'discount_percent'  => 'nullable|numeric|min:1|max:99', // ✅ VALIDASI DISKON
            'discount_end_date' => 'nullable|date',                 // ✅ VALIDASI BATAS WAKTU DISKON
            'image'             => 'nullable|image|max:5120',
            'gallery.*'         => 'image|max:5120',
        ]);

        // Kecualikan image, gallery, dan method PUT dari variabel $data
        $data = $request->except(['image', 'gallery', '_method']); 

        // ✅ Pastikan diskon menjadi null jika di form frontend dikosongkan (admin menghapus diskon)
        if (!$request->filled('discount_percent')) {
            $data['discount_percent'] = null;
        }
        if (!$request->filled('discount_end_date')) {
            $data['discount_end_date'] = null;
        }

        // Update Foto Utama (Jika ada upload baru)
        if ($request->hasFile('image')) {
            // Hapus gambar lama dari storage server
            if ($product->image_url && !str_contains($product->image_url, 'placehold.co')) {
                $oldPath = str_replace(url('storage/'), '', $product->image_url);
                Storage::disk('public')->delete($oldPath);
            }
            // Upload gambar baru
            $path = $request->file('image')->store('products', 'public');
            $data['image_url'] = url('storage/' . $path);
        }

        // ✅ UPDATE DATA TEKS KE DATABASE
        $product->update($data);

        // Tambah Foto Tambahan (Gallery) Jika ada
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

        return response()->json(['message' => 'Produk berhasil diupdate', 'data' => $product->load('galleries')]);
    }

    // 5. HAPUS PRODUK
    public function destroy($id)
    {
        $product = Product::with('galleries')->findOrFail($id);
        
        // Hapus foto utama dari server
        if ($product->image_url && !str_contains($product->image_url, 'placehold.co')) {
             Storage::disk('public')->delete(str_replace(url('storage/'), '', $product->image_url));
        }

        // Hapus foto-foto gallery dari server
        foreach ($product->galleries as $gallery) {
             Storage::disk('public')->delete(str_replace(url('storage/'), '', $gallery->image_url));
        }

        $product->delete();
        return response()->json(['message' => 'Produk dihapus']);
    }

    // 6. HAPUS FOTO GALLERY
    public function destroyGallery($id)
    {
        $gallery = ProductGallery::findOrFail($id);
        
        // Hapus file fisik dari storage jika ada
        if ($gallery->image_url && !str_contains($gallery->image_url, 'placehold.co')) {
             $path = str_replace(url('storage/'), '', $gallery->image_url);
             Storage::disk('public')->delete($path);
        }

        // Hapus record dari database
        $gallery->delete();

        return response()->json(['message' => 'Foto tambahan berhasil dihapus']);
    }
}