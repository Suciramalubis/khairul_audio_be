<?php

namespace App\Http\Controllers\Api;
use App\Models\Notification;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http; 
use Illuminate\Support\Facades\Log;  
use Carbon\Carbon;

class UserOrderController extends Controller
{
    private function autoCancelExpiredOrders()
    {
        // 1. Batalkan pesanan QRIS yang sudah lewat 15 Menit
        Order::where('status', 'pending')
             ->where('payment_method', 'QRIS')
             ->where('created_at', '<', Carbon::now()->subMinutes(15))
             ->update(['status' => 'cancelled']);

        // 2. Batalkan pesanan Transfer Bank (VA) yang sudah lewat 24 Jam
        Order::where('status', 'pending')
             ->where('payment_method', '!=', 'QRIS')
             ->where('created_at', '<', Carbon::now()->subHours(24))
             ->update(['status' => 'cancelled']);
    }

    public function index(Request $request)
    {
        $this->autoCancelExpiredOrders();

        $orders = Order::where('user_id', $request->user()->id)
                       ->orderBy('created_at', 'desc')
                       ->get();

        $formatted = $orders->map(function($order) {
            $firstItem = DB::table('order_items')
                ->leftJoin('products', 'order_items.product_id', '=', 'products.id')
                ->where('order_items.order_id', $order->id)
                ->select('products.name', 'products.image_url')
                ->first();

            $imageUrl = null;
            if ($firstItem && $firstItem->image_url) {
                $imageUrl = str_starts_with($firstItem->image_url, 'http')
                    ? $firstItem->image_url
                    : 'http://127.0.0.1:8000' . (str_starts_with($firstItem->image_url, '/') ? '' : '/') . $firstItem->image_url;
            }

            return [
                'id'             => $order->id,
                'invoice'        => $order->invoice_number ?? 'INV-'.$order->id,
                'date'           => $order->created_at->format('d M Y'),
                'total'          => $order->total_price,
                'status'         => $order->status,
                'courier'        => strtoupper(($order->shipping_courier ?? '') . ' ' . ($order->shipping_service ?? '')),
                'payment_method' => $order->payment_method,
                'resi'           => $order->tracking_number,
                'items_count'    => DB::table('order_items')->where('order_id', $order->id)->count(),
                'product_name'   => $firstItem ? $firstItem->name : 'Produk Khairul Audio',
                'product_image'  => $imageUrl,
            ];
        });

        return response()->json($formatted);
    }

    public function show(Request $request, $id)
    {
        $this->autoCancelExpiredOrders();

        $order = Order::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (!$order) return response()->json(['message' => 'Not found'], 404);

        $items = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.order_id', $order->id)
            ->select(
                'order_items.id',
                'order_items.product_id',
                'order_items.quantity',
                'order_items.price',
                'products.name as product_name',
                'products.image_url as product_image'
            )->get();

        $isReviewed = DB::table('reviews')
                        ->where('order_id', $order->id)
                        ->where('user_id', $request->user()->id)
                        ->exists();

        $orderData = $order->toArray();
        $orderData['items'] = $items;
        $orderData['is_reviewed'] = $isReviewed;
        
        // Memastikan qris_url dan payment_code dikirim ke frontend
        $orderData['qris_url'] = $order->qris_url; 
        $orderData['payment_code'] = $order->payment_code; 

        return response()->json($orderData);
    }

    public function updatePaymentMethod(Request $request, $id)
    {
        $request->validate([
            'payment_method' => 'required|string'
        ]);

        $order = Order::where('id', $id)
                      ->where('user_id', $request->user()->id)
                      ->where('status', 'pending')
                      ->first();

        if (!$order) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        }

        $order->payment_method = $request->payment_method;
        $order->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Metode pembayaran berhasil diperbarui'
        ]);
    }

    public function checkout(Request $request)
    {
        try {
            $request->validate([
                'shipping_address' => 'required',
                'shipping_courier' => 'required',
                'shipping_service' => 'required',
                'shipping_cost'    => 'required|numeric|min:0',
                'total_price'      => 'required|numeric|min:0',
                'payment_method'   => 'required|string',
                'items'            => 'required|array|min:1',
            ]);

            DB::beginTransaction();

            $order = new Order();
            $order->user_id        = $request->user()->id;
            $order->invoice_number = 'INV-' . strtoupper(Str::random(8));
            $order->total_price    = $request->total_price;
            $order->status         = 'pending';
            $order->shipping_courier = $request->shipping_courier;
            $order->shipping_service = $request->shipping_service;
            $order->shipping_cost  = $request->shipping_cost;
            $order->shipping_address = $request->shipping_address;
            $order->payment_method = $request->payment_method;
            $order->save();

            // Insert Items & Stok
            foreach ($request->items as $item) {
                DB::table('order_items')->insert([
                    'order_id'    => $order->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'price'       => $item['price'],
                    'created_at'  => now(),
                ]);
                Product::where('id', $item['product_id'])->decrement('stock', $item['quantity']);
            }

            // ✅ LOGIKA API KOMERCE UNTUK QRIS DAN VIRTUAL ACCOUNT
            $paymentMethod = strtoupper($request->payment_method);
            $qrisKey = env('KOMERCE_QRIS_KEY'); 

            if ($paymentMethod === 'QRIS') {
                $qrisId = env('KOMERCE_QRIS_ID'); 
                
                // Minta durasi QRIS 15 menit ke Komerce
                $qrisExpirationDate = Carbon::now()->addMinutes(15)->format('Y-m-d H:i:s');

                $response = Http::withHeaders([
                    'X-API-Key' => $qrisKey,
                    'Content-Type' => 'application/json'
                ])->post('https://api-sandbox.collaborator.komerce.id/user/api/v1/qrisly/generate-qris', [
                    'qris_id' => (int) $qrisId,
                    'amount' => (int) $order->total_price,
                    'output_type' => 'image',
                    'unique_amount' => true,
                    'expired_date' => $qrisExpirationDate // Pastikan Komerce mengenali ini
                ]);

                if ($response->successful()) {
                    $resData = $response->json();
                    $order->qris_url = $resData['data']['qris_image_url'] ?? ($resData['data']['qris_string'] ?? null);
                    $order->save();
                } else {
                    Log::error('Komerce QRIS API Error: ' . $response->body());
                    throw new \Exception("Gagal generate QRIS: " . $response->json('meta.message'));
                }
            } 
            else if (str_contains($paymentMethod, 'VA_')) {
                $bankCode = str_replace('VA_', '', $paymentMethod);
                
                // Minta durasi VA 24 Jam ke Komerce
                $vaExpirationDate = Carbon::now()->addHours(24)->format('Y-m-d H:i:s');

                $response = Http::withHeaders([
                    'X-API-Key' => $qrisKey, 
                    'Content-Type' => 'application/json'
                ])->post('https://api-sandbox.collaborator.komerce.id/user/api/v1/payment/generate-va', [
                    'bank_code' => strtolower($bankCode),
                    'amount' => (int) $order->total_price,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'phone' => $request->user()->phone ?? '081234567890',
                    'expired_date' => $vaExpirationDate // Tambahkan parameter expire date 
                ]);

                if ($response->successful()) {
                    $resData = $response->json();
                    $order->payment_code = $resData['data']['virtual_account_number'] ?? null;
                } else {
                    Log::error('Komerce VA API Error: ' . $response->body());
                    $dummyVa = match($bankCode) {
                        'BNI' => '8002' . rand(10000000, 99999999),
                        'BCA' => '3901' . rand(10000000, 99999999),
                        'BRI' => '1042' . rand(10000000, 99999999),
                        'MANDIRI' => '89508' . rand(1000000, 9999999),
                        default => rand(1000000000, 9999999999),
                    };
                    $order->payment_code = $dummyVa;
                }
                $order->save();
            }


            // ✅ 1. BUAT NOTIFIKASI UNTUK USER
            Notification::create([
                'user_id'  => $order->user_id,
                'order_id' => $order->id,
                'title'    => 'Pesanan Berhasil Dibuat',
                'message'  => 'Pesanan Anda (' . $order->invoice_number . ') berhasil dibuat. Silakan lakukan pembayaran.',
                'role'     => 'user',
            ]);

            // ✅ 2. BUAT NOTIFIKASI UNTUK ADMIN
            Notification::create([
                'order_id' => $order->id,
                'title'    => 'Pesanan Baru Masuk!',
                'message'  => 'Ada pesanan baru (' . $order->invoice_number . ') senilai Rp ' . number_format($order->total_price, 0, ',', '.'),
                'role'     => 'admin',
            ]);

            DB::commit();
            return response()->json(['status' => 'success', 'data' => $order], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function cancelOrder(Request $request, $id)
    {
        $order = Order::where('id', $id)
                      ->where('user_id', $request->user()->id)
                      ->where('status', 'pending')
                      ->first();

        if (!$order) {
            return response()->json(['message' => 'Pesanan tidak ditemukan atau tidak bisa dibatalkan.'], 400);
        }

        $order->status = 'cancelled';
        $order->save();

        return response()->json(['message' => 'Pesanan berhasil dibatalkan']);
    }

    // ========================= NOTIFIKASI USER =========================
    public function getUserNotifications(Request $request)
    {
        DB::table('notifications')
            ->where('created_at', '<', Carbon::now()->subDays(7))
            ->delete();

        $query = DB::table('notifications')->orderBy('created_at', 'desc');

        if (Schema::hasColumn('notifications', 'user_id')) {
            $query->where('user_id', $request->user()->id);
        }
        if (Schema::hasColumn('notifications', 'role')) {
            $query->where('role', 'user');
        }

        return response()->json(['notifications' => $query->get()]);
    }

    public function markUserNotifAsRead(Request $request, $id)
    {
        $query = DB::table('notifications')->where('id', $id);
        if (Schema::hasColumn('notifications', 'user_id')) {
            $query->where('user_id', $request->user()->id);
        }

        $updateData = ['is_read' => true];
        if (Schema::hasColumn('notifications', 'read_at')) {
            $updateData['read_at'] = now();
        }
        $query->update($updateData);

        return response()->json(['status' => 'success']);
    }

    public function markAllUserNotifAsRead(Request $request)
    {
        $query = DB::table('notifications');
        if (Schema::hasColumn('notifications', 'role')) {
            $query->where('role', 'user');
        }
        if (Schema::hasColumn('notifications', 'user_id')) {
            $query->where('user_id', $request->user()->id);
        }

        $updateData = ['is_read' => true];
        if (Schema::hasColumn('notifications', 'read_at')) {
            $updateData['read_at'] = now();
        }
        $query->update($updateData);

        return response()->json(['status' => 'success']);
    }

    public function deleteUserNotification(Request $request, $id)
    {
        $query = DB::table('notifications')->where('id', $id);
        if (Schema::hasColumn('notifications', 'user_id')) {
            $query->where('user_id', $request->user()->id);
        }
        $query->delete();

        return response()->json(['status' => 'success', 'message' => 'Notifikasi dihapus']);
    }

    // ========================= NOTIFIKASI ADMIN =========================
    public function getAdminNotifications()
    {
        DB::table('notifications')
            ->where('created_at', '<', Carbon::now()->subDays(7))
            ->delete();

        $query = DB::table('notifications')->orderBy('created_at', 'desc');
        if (Schema::hasColumn('notifications', 'role')) {
            $query->where('role', 'admin');
        }

        return response()->json($query->get());
    }

    public function markNotificationAsRead($id)
    {
        $updateData = ['is_read' => true];
        if (Schema::hasColumn('notifications', 'read_at')) {
            $updateData['read_at'] = now();
        }
        DB::table('notifications')->where('id', $id)->update($updateData);

        return response()->json(['status' => 'success']);
    }

    public function markAllNotificationsAsRead()
    {
        $query = DB::table('notifications');
        if (Schema::hasColumn('notifications', 'role')) {
            $query->where('role', 'admin');
        }

        $updateData = ['is_read' => true];
        if (Schema::hasColumn('notifications', 'read_at')) {
            $updateData['read_at'] = now();
        }
        $query->update($updateData);

        return response()->json(['status' => 'success']);
    }

    public function deleteNotification($id)
    {
        DB::table('notifications')->where('id', $id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Notifikasi berhasil dihapus']);
    }
}