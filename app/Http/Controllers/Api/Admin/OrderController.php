<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Notification; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user')->orderBy('created_at', 'desc')->get();
        
        $formattedOrders = $orders->map(function($order) {
            return [
                'id' => $order->id,
                'invoice' => $order->invoice_number ?? 'INV-'.str_pad($order->id, 5, '0', STR_PAD_LEFT),
                'customer' => $order->user->name ?? 'Guest', 
                'date' => $order->created_at->format('d M Y H:i'),
                'total' => $order->total_price,
                'status' => $order->status, 
                'courier' => strtoupper(($order->shipping_courier ?? '') . ' ' . ($order->shipping_service ?? '')),
                'payment_method' => $order->payment_method, 
            ];
        });

        return response()->json($formattedOrders);
    }

    public function show($id)
    {
        $order = Order::with(['user', 'items.product'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json([
            'id' => $order->id,
            'invoice' => $order->invoice_number ?? 'INV-'.str_pad($order->id, 5, '0', STR_PAD_LEFT),
            'date' => $order->created_at->format('d F Y, H:i'). ' WIB',
            'status' => $order->status,
            'email' => $order->user->email ?? '-',
            'phone' => $order->user->phone ?? '-',
            'address' => $order->shipping_address ?? 'Alamat tidak tersedia',
            'customer' => $order->user->name ?? 'Guest',
            'courier' => strtoupper(($order->shipping_courier ?? '') . ' ' . ($order->shipping_service ?? '')), 
            'shipping_cost' => $order->shipping_cost ?? 0,
            // Mengambil tracking_number
            'resi' => $order->tracking_number ?? null,
            'tracking_number' => $order->tracking_number ?? null,
            'payment_method' => $order->payment_method, 
            
            'items' => $order->items ? $order->items->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->product->name ?? 'Produk Dihapus',
                    'price' => $item->price,
                    'qty' => $item->quantity,
                    'image_url' => $item->product->image_url ?? 'https://placehold.co/100'
                ];
            }) : [],
            'subtotal' => $order->total_price - ($order->shipping_cost ?? 0),
            'total' => $order->total_price,
        ]);
    }

    public function update(Request $request, $id)
    {
        $order = Order::find($id);
        if ($order) {
            $oldStatus = $order->status;
            $order->status = $request->status;
            $order->save();

            // ✅ NOTIFIKASI KE USER JIKA STATUS BERUBAH
            if ($oldStatus !== $request->status) {
                $statusText = [
                    'processing' => 'sedang diproses',
                    'shipped'    => 'sedang dalam pengiriman',
                    'completed'  => 'telah selesai dan diterima',
                    'cancelled'  => 'telah dibatalkan'
                ][$request->status] ?? $request->status;

                $invoice = $order->invoice_number ?? 'INV-'.$order->id;

                Notification::create([
                    'user_id'  => $order->user_id,
                    'order_id' => $order->id,
                    'title'    => 'Update Status Pesanan',
                    'message'  => "Pesanan Anda ($invoice) $statusText.",
                    'role'     => 'user',
                ]);
            }

            return response()->json(['message' => 'Status updated', 'status' => $order->status]);
        }
        return response()->json(['message' => 'Order not found'], 404);
    }

    // === FUNGSI BARU: UPDATE NOMOR RESI PENGIRIMAN ===
    public function updateTracking(Request $request, $id)
    {
        $request->validate([
            'tracking_number' => 'required|string|max:255'
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Pesanan tidak ditemukan'], 404);
        }

        // Simpan nomor resi
        $order->tracking_number = $request->tracking_number;
        
        // Opsional: Otomatis ubah status pesanan menjadi "Dikirim" jika admin isi resi
        if ($order->status === 'processing') {
             $order->status = 'shipped';
        }
        
        $order->save();

        // ✅ KIRIM NOTIFIKASI KE PELANGGAN BAHWA RESI SUDAH DIUPDATE
        $invoice = $order->invoice_number ?? 'INV-'.$order->id;
        $courier = strtoupper($order->shipping_courier ?? 'Kurir');

        Notification::create([
            'user_id'  => $order->user_id,
            'order_id' => $order->id,
            'title'    => 'Nomor Resi Telah Diperbarui',
            'message'  => "Pesanan $invoice telah dikirim via $courier. No Resi: " . $request->tracking_number,
            'role'     => 'user',
        ]);

        return response()->json([
            'message' => 'Nomor resi berhasil diperbarui',
            'tracking_number' => $order->tracking_number,
            'status' => $order->status
        ]);
    }
}