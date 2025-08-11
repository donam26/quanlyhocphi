<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Chạy các seeder theo thứ tự phụ thuộc
        $this->call([
            AdminUserSeeder::class, // Thêm admin user seeder đầu tiên
            ProvinceSeeder::class,  // Seeder tỉnh thành
            EthnicitySeeder::class
        ]);
    }
}
