<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // Ambil notifikasi user yang sedang login
        $notifications = $request->user()->notifications;
        
        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $request->user()->unreadNotifications->count()
        ]);
    }

    public function markAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json(['message' => 'Semua ditandai sudah dibaca']);
    }
}