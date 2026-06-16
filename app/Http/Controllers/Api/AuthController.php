<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyOtpMail;
use App\Mail\ResetPasswordMail;
use Carbon\Carbon;

class AuthController extends Controller
{
    // 🔹 LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // ❌ CEK JIKA BELUM VERIFIKASI EMAIL
        if ($user->email_verified_at === null) {
            // (Opsional) Jika OTP expired, kita bisa buat fungsi resend OTP. 
            // Tapi untuk sekarang kita blokir saja dan minta verifikasi.
            return response()->json([
                'message' => 'Akun belum diverifikasi. Silakan cek email Anda untuk kode OTP.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer'
        ]);
    }

    // 🔹 REGISTER
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Generate 6 digit OTP random (contoh: 045123)
        $otp = sprintf("%06d", mt_rand(1, 999999));

        $user = User::create([
            'name'           => $request->name,
            'email'          => $request->email,
            'password'       => Hash::make($request->password),
            'role'           => 'user',
            'otp'            => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10), // Expired dalam 10 menit
        ]);

        // Kirim email OTP
        Mail::to($user->email)->send(new VerifyOtpMail($otp));

        // Jangan return token, beri info sukses saja
        return response()->json([
            'message' => 'Registrasi berhasil. Silakan cek email untuk kode verifikasi.',
            'email'   => $user->email
        ], 201);
    }

    // 🔹 VERIFY OTP (FUNGSI BARU)
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6'
        ]);

        $user = User::where('email', $request->email)->first();

        // Jika user tidak ada atau OTP tidak cocok
        if (!$user || $user->otp !== $request->otp) {
            return response()->json(['message' => 'Kode OTP salah!'], 400);
        }

        // Jika OTP kadaluarsa
        if (Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json(['message' => 'Kode OTP sudah kadaluarsa. Silakan daftar ulang atau minta kode baru.'], 400);
        }

        // Berhasil! Kosongkan OTP dan isi email_verified_at
        $user->update([
            'otp'               => null,
            'otp_expires_at'    => null,
            'email_verified_at' => Carbon::now()
        ]);

        // Buatkan token login agar bisa langsung masuk
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Email berhasil diverifikasi!',
            'token'   => $token,
            'user'    => $user
        ], 200);
    }

    // 🔹 RESEND OTP (FUNGSI BARU)
    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        // Jika user tidak ada, atau emailnya sudah terverifikasi, tolak request
        if (!$user) {
            return response()->json(['message' => 'Email tidak ditemukan.'], 404);
        }

        if ($user->email_verified_at !== null) {
            return response()->json(['message' => 'Email ini sudah diverifikasi sebelumnya. Silakan langsung login.'], 400);
        }

        // Generate 6 digit OTP random baru
        $otp = sprintf("%06d", mt_rand(1, 999999));

        // Update OTP dan waktu kadaluarsa baru (10 menit)
        $user->update([
            'otp'            => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Kirim email OTP ulang
        Mail::to($user->email)->send(new VerifyOtpMail($otp));

        return response()->json([
            'message' => 'Kode OTP baru berhasil dikirim ke email Anda.'
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out berhasil']);
    }

    // ===============================================
    // 🔹 LOGIC BARU: LUPA PASSWORD
    // ===============================================

    // 1. Mengirim OTP Lupa Password
    public function forgotPasswordSendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email tidak terdaftar di sistem kami.'], 404);
        }

        $otp = sprintf("%06d", mt_rand(1, 999999));
        
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10)
        ]);

        Mail::to($user->email)->send(new ResetPasswordMail($otp));

        return response()->json(['message' => 'Kode OTP pemulihan telah dikirim ke email Anda.']);
    }

    // 2. Verifikasi OTP Lupa Password (Hanya mengecek validitas)
    public function forgotPasswordVerifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp'   => 'required|string|size:6'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || $user->otp !== $request->otp) {
            return response()->json(['message' => 'Kode OTP salah!'], 400);
        }

        if (Carbon::now()->isAfter($user->otp_expires_at)) {
            return response()->json(['message' => 'Kode OTP sudah kadaluarsa. Silakan minta ulang.'], 400);
        }

        return response()->json(['message' => 'OTP Valid. Silakan masukkan password baru.']);
    }

    // 3. Simpan Password Baru
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'otp'      => 'required|string|size:6',
            'password' => 'required|string|min:8'
        ]);

        $user = User::where('email', $request->email)->first();

        // Validasi ganda untuk memastikan yang mengganti password benar-benar yang memegang OTP valid
        if (!$user || $user->otp !== $request->otp) {
            return response()->json(['message' => 'Validasi gagal. Kode OTP tidak valid.'], 400);
        }

        $user->update([
            'password'       => Hash::make($request->password),
            'otp'            => null,
            'otp_expires_at' => null
        ]);

        return response()->json(['message' => 'Password berhasil diubah. Silakan login.']);
    }
}