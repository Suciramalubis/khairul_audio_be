<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 1. Akun SUPER ADMIN (Gunakan updateOrCreate)
        User::updateOrCreate(
            ['email' => 'khairulaudioo@gmail.com'], 
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin123'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // 2. Akun PELANGGAN DEMO
        User::updateOrCreate(
            ['email' => 'miau@gmail.com'],
            [
                'name' => 'Miau',
                'password' => Hash::make('miau123'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );
    }
}