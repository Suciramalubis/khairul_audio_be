<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    // Update Password Asli
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed', // butuh field new_password_confirmation
        ]);

        $user = Auth::user();

        // 1. Cek apakah password lama benar?
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Password lama salah!'
            ], 400);
        }

        // 2. Update password baru
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password berhasil diperbarui!'
        ]);
    }
    
    // Update Profil (Nama/HP/Bio)
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        // Tambahkan validasi untuk phone dan bio agar aman
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20', 
            'bio' => 'nullable|string',
        ]);

        // ✅ Masukkan phone dan bio ke proses update database
        $user->update([
            'name' => $request->name,
            'phone' => $request->phone, 
            'bio' => $request->bio, 
        ]);

        return response()->json([
            'message' => 'Profil berhasil diupdate', 
            'user' => $user
        ]);
    }
}