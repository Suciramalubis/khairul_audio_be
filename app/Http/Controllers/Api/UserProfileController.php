<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        // 1. Validasi Data yang Masuk
        $request->validate([
            'username'         => 'nullable|string|max:50|unique:users,username,' . $user->id,
            'name'             => 'nullable|string|max:255',
            'email'            => 'nullable|email|unique:users,email,' . $user->id,
            'phone'            => 'nullable|string|max:20',
            'gender'           => 'nullable|string|in:Laki-laki,Perempuan', // Validasi enum
            'birth_date'       => 'nullable|date',
            
            // Logika ganti password
            'current_password' => 'required_with:password|string',
            'password'         => ['nullable', 'confirmed', Password::min(8)], 
        ]);

        // 2. Verifikasi dan Update Password 
        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Password saat ini yang Anda masukkan salah.'
                ], 400);
            }
            $user->password = Hash::make($request->password);
        }

        // 3. Update Data Profil Dasar
        if ($request->has('username')) {
            $user->username = $request->username;
        }
        if ($request->filled('name')) {
            $user->name = $request->name;
        }
        if ($request->filled('email')) {
            $user->email = $request->email;
        }
        if ($request->has('phone')) {
            $user->phone = $request->phone;
        }
        if ($request->has('gender')) {
            $user->gender = $request->gender;
        }
        if ($request->has('birth_date')) {
            $user->birth_date = $request->birth_date;
        }

        // 4. Simpan Perubahan ke Database
        $user->save();

        // 5. Kirim Respons ke React
        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user'    => $user
        ], 200);
    }
}