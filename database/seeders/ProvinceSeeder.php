<?php

namespace Database\Seeders;

use App\Models\Province;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Danh sách các thành phố trực thuộc trung ương
        $centralCities = [
            ['name' => 'Hà Nội', 'code' => 'HNI', 'region' => 'north'],
            ['name' => 'TP. Hồ Chí Minh', 'code' => 'HCM', 'region' => 'south'], // hợp nhất Bình Dương + TP.HCM + Bà Rịa - Vũng Tàu
            ['name' => 'Hải Phòng', 'code' => 'HAP', 'region' => 'north'], // hợp nhất Hải Dương + TP.Hải Phòng
            ['name' => 'Đà Nẵng', 'code' => 'DNG', 'region' => 'central'], // hợp nhất Quảng Nam + TP. Đà Nẵng
            ['name' => 'Huế', 'code' => 'HUE', 'region' => 'central'],
            ['name' => 'Cần Thơ', 'code' => 'CAT', 'region' => 'south'], // hợp nhất Cần Thơ + Sóc Trăng + Hậu Giang
        ];
        
        // Danh sách các tỉnh không sáp nhập
        $unchangedProvinces = [
            ['name' => 'Cao Bằng', 'code' => 'CAB', 'region' => 'north'],
            ['name' => 'Điện Biên', 'code' => 'DIB', 'region' => 'north'],
            ['name' => 'Lai Châu', 'code' => 'LAI', 'region' => 'north'],
            ['name' => 'Sơn La', 'code' => 'SOL', 'region' => 'north'],
            ['name' => 'Lạng Sơn', 'code' => 'LAS', 'region' => 'north'],
            ['name' => 'Quảng Ninh', 'code' => 'QUN', 'region' => 'north'],
            ['name' => 'Thanh Hóa', 'code' => 'THA', 'region' => 'central'],
            ['name' => 'Nghệ An', 'code' => 'NGA', 'region' => 'central'],
            ['name' => 'Hà Tĩnh', 'code' => 'HAT', 'region' => 'central'],
        ];
        
        // Danh sách các tỉnh mới sau sáp nhập
        $mergedProvinces = [
            ['name' => 'Tuyên Quang', 'code' => 'TUQ', 'region' => 'north'],
            ['name' => 'Lào Cai', 'code' => 'LAC', 'region' => 'north'],
            ['name' => 'Thái Nguyên', 'code' => 'THN', 'region' => 'north'],
            ['name' => 'Phú Thọ', 'code' => 'PHT', 'region' => 'north'], // gồm Phú Thọ + Vĩnh Phúc + Hòa Bình
            ['name' => 'Bắc Ninh', 'code' => 'BAN', 'region' => 'north'],
            ['name' => 'Hưng Yên', 'code' => 'HUY', 'region' => 'north'],
            ['name' => 'Ninh Bình', 'code' => 'NIB', 'region' => 'north'], // gồm Ninh Bình + Hà Nam + Nam Định
            ['name' => 'Quảng Trị', 'code' => 'QUT', 'region' => 'central'], // gồm Quảng Trị + Quảng Bình
            ['name' => 'Quảng Ngãi', 'code' => 'QNG', 'region' => 'central'], // gồm Quảng Ngãi + Kon Tum
            ['name' => 'Gia Lai', 'code' => 'GIL', 'region' => 'central'], // gồm Gia Lai + Bình Định
            ['name' => 'Khánh Hòa', 'code' => 'KHA', 'region' => 'central'], // gồm Khánh Hòa + Ninh Thuận
            ['name' => 'Lâm Đồng', 'code' => 'LAD', 'region' => 'south'], // gồm Lâm Đồng + Đắk Nông + Bình Thuận
            ['name' => 'Đắk Lắk', 'code' => 'DAL', 'region' => 'central'], // gồm Đắk Lắk + Phú Yên
            ['name' => 'Đồng Nai', 'code' => 'DON', 'region' => 'south'], // gồm Đồng Nai + Bình Phước
            ['name' => 'Tây Ninh', 'code' => 'TAN', 'region' => 'south'], // gồm Tây Ninh + Long An
            ['name' => 'Vĩnh Long', 'code' => 'VIL', 'region' => 'south'], // gồm Vĩnh Long + Bến Tre + Trà Vinh
            ['name' => 'Đồng Tháp', 'code' => 'DOT', 'region' => 'south'], // gồm Đồng Tháp + Tiền Giang
            ['name' => 'Cà Mau', 'code' => 'CAM', 'region' => 'south'], // gồm Cà Mau + Bạc Liêu
            ['name' => 'An Giang', 'code' => 'ANG', 'region' => 'south'], // gồm An Giang + Kiên Giang
        ];
        
        // Gộp tất cả tỉnh thành
        $allProvinces = array_merge($centralCities, $unchangedProvinces, $mergedProvinces);
        
        // Thêm tất cả tỉnh thành vào database (nếu chưa tồn tại)
        foreach ($allProvinces as $province) {
            Province::updateOrCreate(
                ['code' => $province['code']], // Tìm theo code
                $province // Cập nhật hoặc tạo mới
            );
        }
        
        $this->command->info('Đã thêm thành công ' . count($allProvinces) . ' tỉnh thành sau sáp nhập.');
    }
}
