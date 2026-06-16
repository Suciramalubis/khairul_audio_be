<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'reviews' => 'required|array',
            'reviews.*.product_id' => 'required|numeric', 
            'reviews.*.rating' => 'required|integer|min:1|max:5',
            'reviews.*.comment' => 'nullable|string',
            'reviews.*.images.*' => 'nullable|image|max:5120' 
        ]);

        $user = $request->user();
        
        // Memastikan orderan milik user yang sedang login
        $order = Order::where('id', $request->order_id)
                      ->where('user_id', $user->id)
                      ->first();

        if (!$order) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        }

        // AMBIL DAFTAR PRODUK YANG BENAR-BENAR DIBELI DI ORDER INI
        // Asumsi: tabel detail pesanan bernama 'order_items'
        $orderedProductIds = DB::table('order_items')
                               ->where('order_id', $order->id)
                               ->pluck('product_id')
                               ->toArray();

        DB::beginTransaction();
        try {
            $reviewsInserted = 0;

            foreach ($request->reviews as $reviewData) {
                $productId = $reviewData['product_id'];

                // ✅ PROTEKSI 1: Tolak jika product_id tidak ada di daftar pesanan ini
                // Ini mencegah serangan dari luar atau bug dari frontend yang mengirim ID salah
                if (!in_array($productId, $orderedProductIds)) {
                    continue; 
                }

                // ✅ PROTEKSI 2: Cek apakah user sudah pernah mereview produk ini di order ini
                // Ini mencegah terjadinya ulasan ganda (duplikat)
                $existingReview = DB::table('reviews')
                    ->where('order_id', $order->id)
                    ->where('product_id', $productId)
                    ->first();

                if ($existingReview) {
                    continue; 
                }

                // 1. Simpan Ulasan Teks & Bintang ke tabel database 'reviews'
                $reviewId = DB::table('reviews')->insertGetId([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'order_id' => $order->id,
                    'rating' => $reviewData['rating'],
                    'comment' => $reviewData['comment'] ?? '',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $reviewsInserted++;

                // 2. Simpan Foto Ulasan Jika Ada
                if (isset($reviewData['images'])) {
                    foreach ($reviewData['images'] as $imageFile) {
                        $path = $imageFile->store('review_images', 'public');
                        
                        DB::table('review_images')->insert([
                            'review_id' => $reviewId,
                            'image_url' => url('storage/' . $path),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }

            DB::commit();

            // Beri respon spesifik jika semua data yang dikirim ternyata sudah direview sebelumnya
            if ($reviewsInserted === 0) {
                 return response()->json(['message' => 'Ulasan sudah pernah diberikan sebelumnya.'], 200);
            }

            return response()->json(['message' => 'Ulasan berhasil disimpan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menyimpan ulasan.', 'error' => $e->getMessage()], 500);
        }
    }
}