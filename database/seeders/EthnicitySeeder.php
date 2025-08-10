<?php

namespace Database\Seeders;

use App\Models\Ethnicity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EthnicitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Danh sách 54 dân tộc Việt Nam theo thứ tự alphabet
        $ethnicities = [
            ['name' => 'Ba Na', 'code' => 'BNA'],
            ['name' => 'Bố Y', 'code' => 'BOY'],
            ['name' => 'Brâu', 'code' => 'BRA'],
            ['name' => 'Bru - Vân Kiều', 'code' => 'BVK'],
            ['name' => 'Chăm', 'code' => 'CHA'],
            ['name' => 'Chơ Ro', 'code' => 'CRO'],
            ['name' => 'Chu Ru', 'code' => 'CRU'],
            ['name' => 'Chứt', 'code' => 'CHU'],
            ['name' => 'Co', 'code' => 'CO'],
            ['name' => 'Cống', 'code' => 'CON'],
            ['name' => 'Co Tu', 'code' => 'CTU'],
            ['name' => 'Cờ Ho', 'code' => 'CHO'],
            ['name' => 'Cờ Lao', 'code' => 'CLA'],
            ['name' => 'Dao', 'code' => 'DAO'],
            ['name' => 'Ê Đê', 'code' => 'EDE'],
            ['name' => 'Gia Rai', 'code' => 'GRA'],
            ['name' => 'Giáy', 'code' => 'GIA'],
            ['name' => 'Giẻ Triêng', 'code' => 'GTR'],
            ['name' => 'Hà Nhì', 'code' => 'HNI'],
            ['name' => 'Hmông', 'code' => 'HMO'],
            ['name' => 'Hrê', 'code' => 'HRE'],
            ['name' => 'Khang', 'code' => 'KHA'],
            ['name' => 'Khmer', 'code' => 'KHM'],
            ['name' => 'Kho Mú', 'code' => 'KMU'],
            ['name' => 'Kinh', 'code' => 'KIN'],
            ['name' => 'La Chí', 'code' => 'LCH'],
            ['name' => 'La Ha', 'code' => 'LHA'],
            ['name' => 'La Hủ', 'code' => 'LHU'],
            ['name' => 'Lào', 'code' => 'LAO'],
            ['name' => 'Lô Lô', 'code' => 'LLO'],
            ['name' => 'Lự', 'code' => 'LU'],
            ['name' => 'Mạ', 'code' => 'MA'],
            ['name' => 'Mảng', 'code' => 'MAN'],
            ['name' => 'Mnông', 'code' => 'MNO'],
            ['name' => 'Mường', 'code' => 'MUO'],
            ['name' => 'Ngái', 'code' => 'NGA'],
            ['name' => 'Nùng', 'code' => 'NUN'],
            ['name' => 'Ơ Đu', 'code' => 'ODU'],
            ['name' => 'Phù Lá', 'code' => 'PLA'],
            ['name' => 'Pu Péo', 'code' => 'PPE'],
            ['name' => 'Ra Glai', 'code' => 'RGL'],
            ['name' => 'Rơ Măm', 'code' => 'RMA'],
            ['name' => 'Sán Chay', 'code' => 'SCH'],
            ['name' => 'Sán Dìu', 'code' => 'SDI'],
            ['name' => 'Si La', 'code' => 'SLA'],
            ['name' => 'Tà Ôi', 'code' => 'TAO'],
            ['name' => 'Tày', 'code' => 'TAY'],
            ['name' => 'Thái', 'code' => 'THA'],
            ['name' => 'Thổ', 'code' => 'THO'],
            ['name' => 'Xinh Mun', 'code' => 'XMU'],
            ['name' => 'Xơ Đăng', 'code' => 'XDA'],
            ['name' => 'X\'tiêng', 'code' => 'XTI'],
            ['name' => 'Xứ', 'code' => 'XU'],
            ['name' => 'Khác', 'code' => 'KHA'], // Cho trường hợp khác
        ];
        
        // Thêm tất cả dân tộc vào database (nếu chưa tồn tại)
        foreach ($ethnicities as $ethnicity) {
            Ethnicity::updateOrCreate(
                ['code' => $ethnicity['code']], // Tìm theo code
                $ethnicity // Cập nhật hoặc tạo mới
            );
        }
        
        $this->command->info('Đã thêm thành công ' . count($ethnicities) . ' dân tộc Việt Nam.');
    }
}
