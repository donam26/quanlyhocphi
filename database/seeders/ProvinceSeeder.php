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
        // Danh sách các tỉnh thành miền Bắc
        $northProvinces = [
            ['name' => 'Hà Nội', 'code' => 'HNI', 'region' => 'north'],
            ['name' => 'Hà Giang', 'code' => 'HAG', 'region' => 'north'],
            ['name' => 'Cao Bằng', 'code' => 'CAB', 'region' => 'north'],
            ['name' => 'Bắc Kạn', 'code' => 'BAK', 'region' => 'north'],
            ['name' => 'Tuyên Quang', 'code' => 'TUQ', 'region' => 'north'],
            ['name' => 'Lào Cai', 'code' => 'LAC', 'region' => 'north'],
            ['name' => 'Điện Biên', 'code' => 'DIB', 'region' => 'north'],
            ['name' => 'Lai Châu', 'code' => 'LAI', 'region' => 'north'],
            ['name' => 'Sơn La', 'code' => 'SOL', 'region' => 'north'],
            ['name' => 'Yên Bái', 'code' => 'YEB', 'region' => 'north'],
            ['name' => 'Hòa Bình', 'code' => 'HOB', 'region' => 'north'],
            ['name' => 'Thái Nguyên', 'code' => 'THN', 'region' => 'north'],
            ['name' => 'Lạng Sơn', 'code' => 'LAS', 'region' => 'north'],
            ['name' => 'Quảng Ninh', 'code' => 'QUN', 'region' => 'north'],
            ['name' => 'Bắc Giang', 'code' => 'BAG', 'region' => 'north'],
            ['name' => 'Phú Thọ', 'code' => 'PHT', 'region' => 'north'],
            ['name' => 'Vĩnh Phúc', 'code' => 'VIP', 'region' => 'north'],
            ['name' => 'Bắc Ninh', 'code' => 'BAN', 'region' => 'north'],
            ['name' => 'Hải Dương', 'code' => 'HAD', 'region' => 'north'],
            ['name' => 'Hải Phòng', 'code' => 'HAP', 'region' => 'north'],
            ['name' => 'Hưng Yên', 'code' => 'HUY', 'region' => 'north'],
            ['name' => 'Thái Bình', 'code' => 'THB', 'region' => 'north'],
            ['name' => 'Hà Nam', 'code' => 'HNA', 'region' => 'north'],
            ['name' => 'Nam Định', 'code' => 'NAD', 'region' => 'north'],
            ['name' => 'Ninh Bình', 'code' => 'NIB', 'region' => 'north'],
        ];
        
        // Danh sách các tỉnh thành miền Trung
        $centralProvinces = [
            ['name' => 'Thanh Hóa', 'code' => 'THA', 'region' => 'central'],
            ['name' => 'Nghệ An', 'code' => 'NGA', 'region' => 'central'],
            ['name' => 'Hà Tĩnh', 'code' => 'HAT', 'region' => 'central'],
            ['name' => 'Quảng Bình', 'code' => 'QUB', 'region' => 'central'],
            ['name' => 'Quảng Trị', 'code' => 'QUT', 'region' => 'central'],
            ['name' => 'Thừa Thiên Huế', 'code' => 'TTH', 'region' => 'central'],
            ['name' => 'Đà Nẵng', 'code' => 'DNG', 'region' => 'central'], // Sửa lại code của Đà Nẵng
            ['name' => 'Quảng Nam', 'code' => 'QNA', 'region' => 'central'],
            ['name' => 'Quảng Ngãi', 'code' => 'QNG', 'region' => 'central'],
            ['name' => 'Bình Định', 'code' => 'BDI', 'region' => 'central'], // Sửa lại code
            ['name' => 'Phú Yên', 'code' => 'PHY', 'region' => 'central'],
            ['name' => 'Khánh Hòa', 'code' => 'KHA', 'region' => 'central'],
            ['name' => 'Ninh Thuận', 'code' => 'NIT', 'region' => 'central'],
            ['name' => 'Bình Thuận', 'code' => 'BIT', 'region' => 'central'],
        ];
        
        // Danh sách các tỉnh thành miền Nam
        $southProvinces = [
            ['name' => 'Kon Tum', 'code' => 'KOT', 'region' => 'south'],
            ['name' => 'Gia Lai', 'code' => 'GIL', 'region' => 'south'],
            ['name' => 'Đắk Lắk', 'code' => 'DAL', 'region' => 'south'],
            ['name' => 'Đắk Nông', 'code' => 'DNO', 'region' => 'south'], // Sửa lại code
            ['name' => 'Lâm Đồng', 'code' => 'LAD', 'region' => 'south'],
            ['name' => 'Bình Phước', 'code' => 'BIP', 'region' => 'south'],
            ['name' => 'Tây Ninh', 'code' => 'TAN', 'region' => 'south'],
            ['name' => 'Bình Dương', 'code' => 'BDU', 'region' => 'south'], // Sửa lại code
            ['name' => 'Đồng Nai', 'code' => 'DON', 'region' => 'south'],
            ['name' => 'Bà Rịa - Vũng Tàu', 'code' => 'BRV', 'region' => 'south'],
            ['name' => 'TP. Hồ Chí Minh', 'code' => 'HCM', 'region' => 'south'],
            ['name' => 'Long An', 'code' => 'LOA', 'region' => 'south'],
            ['name' => 'Tiền Giang', 'code' => 'TIG', 'region' => 'south'],
            ['name' => 'Bến Tre', 'code' => 'BET', 'region' => 'south'],
            ['name' => 'Trà Vinh', 'code' => 'TRV', 'region' => 'south'],
            ['name' => 'Vĩnh Long', 'code' => 'VIL', 'region' => 'south'],
            ['name' => 'Đồng Tháp', 'code' => 'DOT', 'region' => 'south'],
            ['name' => 'An Giang', 'code' => 'ANG', 'region' => 'south'],
            ['name' => 'Kiên Giang', 'code' => 'KIG', 'region' => 'south'],
            ['name' => 'Cần Thơ', 'code' => 'CAT', 'region' => 'south'],
            ['name' => 'Hậu Giang', 'code' => 'HAU', 'region' => 'south'], // Sửa lại code
            ['name' => 'Sóc Trăng', 'code' => 'SOT', 'region' => 'south'],
            ['name' => 'Bạc Liêu', 'code' => 'BAL', 'region' => 'south'],
            ['name' => 'Cà Mau', 'code' => 'CAM', 'region' => 'south'],
        ];
        
        // Gộp tất cả tỉnh thành
        $allProvinces = array_merge($northProvinces, $centralProvinces, $southProvinces);
        
        // Thêm tất cả tỉnh thành vào database (nếu chưa tồn tại)
        foreach ($allProvinces as $province) {
            Province::updateOrCreate(
                ['code' => $province['code']], // Tìm theo code
                $province // Cập nhật hoặc tạo mới
            );
        }
        
        $this->command->info('Đã thêm thành công ' . count($allProvinces) . ' tỉnh thành.');
    }
}
