<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo tài khoản admin mặc định
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@quanlyhocphi.com',
            'password' => Hash::make('admin12345'),
            'is_admin' => true,
        ]);
    }
}
