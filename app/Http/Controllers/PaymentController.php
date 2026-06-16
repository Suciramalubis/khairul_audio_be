<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function komerceWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Komerce Webhook Payload: ', $payload); 

        // 1. Ambil ID dari Komerce
        $orderId = $request->input('order_id'); 
        $paymentStatus = strtolower($request->input('status')); 

        // 2. Cari berdasarkan ID atau Invoice Number (Penting!)
        $order = Order::where('id', $orderId)
                      ->orWhere('invoice_number', $orderId)
                      ->first();

        if (!$order) {
            Log::warning('Webhook diterima untuk order yang tidak ditemukan: ' . $orderId);
            return response()->json(['message' => 'Order not found'], 404);
        }

        // 3. Update Status & Buat Notifikasi
        if (in_array($paymentStatus, ['paid', 'settled', 'success'])) {
            
            // Cek agar tidak terjadi double notifikasi jika webhook terkirim 2x
            if ($order->status !== 'processing') { 
                $order->update(['status' => 'processing']); 
                Log::info('Order ' . $order->invoice_number . ' berhasil dibayar dan sedang diproses.');

                // ✅ NOTIFIKASI PEMBAYARAN KE USER
                Notification::create([
                    'user_id'  => $order->user_id,
                    'order_id' => $order->id,
                    'title'    => 'Pembayaran Berhasil! 🎉',
                    'message'  => 'Pembayaran untuk pesanan ' . ($order->invoice_number ?? $order->id) . ' telah kami terima dan sedang diproses.',
                    'role'     => 'user',
                ]);

                // ✅ NOTIFIKASI PEMBAYARAN KE ADMIN
                Notification::create([
                    'order_id' => $order->id,
                    'title'    => 'Pembayaran Diterima 💰',
                    'message'  => 'Pesanan ' . ($order->invoice_number ?? $order->id) . ' telah lunas dibayar dan siap diproses.',
                    'role'     => 'admin',
                ]);
            }
            
        } 
        else if (in_array($paymentStatus, ['expired', 'failed', 'canceled'])) {
            if ($order->status !== 'cancelled') { 
                $order->update(['status' => 'cancelled']);
                Log::info('Order ' . $order->invoice_number . ' dibatalkan.');
            }
        }

        return response()->json(['message' => 'Webhook received successfully'], 200);
    }
}