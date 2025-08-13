<?php

require_once 'vendor/autoload.php';

use App\Imports\StudentsImport;

// Test data với normalized headers từ log
$testRow = [
    'ho' => 'Nguyễn Văn',
    'ten' => 'Test Normalized',
    'email' => 'testnormalized@example.com',
    'so_dien_thoai' => '0901234777',
    'ngay_sinh_ddmmyyyy' => '01/01/1990',
    'gioi_tinh_namnukhac' => 'Nam',
    'dia_chi' => '123 Normalized Street',
    'tinh_thanh' => 'Hồ Chí Minh',
    'noi_cong_tac_hien_tai' => 'Normalized Company',
    'kinh_nghiem_ke_toan_nam' => '5',
    'noi_sinh' => 'Hồ Chí Minh',
    'dan_toc' => 'Kinh',
    'ho_so_ban_cung_da_nopchua_nop' => 'Đã nộp',
    'bang_cap_vb2trung_capcao_dangdai_hocthac_si' => 'Đại học',
    'chuyen_mon_dao_tao' => 'Kế toán',
    'ten_don_vi_cho_hoa_don' => 'Công ty TNHH Normalized Test',
    'ma_so_thue' => '2222222222',
    'email_nhan_hoa_don' => 'invoice@normalizedtest.com',
    'dia_chi_don_vi' => '999 Normalized Company Street',
    'ghi_chu' => 'Normalized test note'
];

echo "Testing normalized mapping...\n";
echo "Raw data keys: " . implode(', ', array_keys($testRow)) . "\n\n";

// Test the mapping logic
$import = new StudentsImport('create_and_update');

// Simulate the normalizeData method
$reflection = new ReflectionClass($import);
$method = $reflection->getMethod('normalizeData');
$method->setAccessible(true);

$result = $method->invoke($import, $testRow);

echo "Mapped data:\n";
print_r($result);

echo "\nChecking company fields specifically:\n";
echo "Company name: " . ($result['company_name'] ?? 'MISSING') . "\n";
echo "Tax code: " . ($result['tax_code'] ?? 'MISSING') . "\n";
echo "Invoice email: " . ($result['invoice_email'] ?? 'MISSING') . "\n";
echo "Company address: " . ($result['company_address'] ?? 'MISSING') . "\n";
