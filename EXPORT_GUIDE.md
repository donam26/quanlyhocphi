# 📊 HƯỚNG DẪN SỬ DỤNG CHỨC NĂNG EXPORT

## 🎯 Tổng quan

Hệ thống export đã được hoàn thiện với các tính năng:
- ✅ Export học viên (Students)
- ✅ Export thanh toán (Payments)
- ✅ Export ghi danh (Enrollments)
- ✅ Export điểm danh (Attendance)
- ✅ **Export khóa học phân cấp** - Xuất khóa cha bao gồm cả học viên từ khóa con
- ✅ Bộ lọc đầy đủ
- ✅ Chọn cột tùy chỉnh
- ✅ Format dữ liệu chuẩn

## 🔧 Cách sử dụng

### 1. **Backend API Endpoints**

#### Student Export
```bash
POST /api/students/export
Content-Type: application/json
Authorization: Bearer {token}

{
  "columns": ["full_name", "phone", "email", "province"],
  "filters": {
    "search": "Nguyễn",
    "gender": "male",
    "province_id": 1,
    "start_date": "2024-01-01",
    "end_date": "2024-12-31"
  }
}
```

#### Payment Export
```bash
POST /api/payments/export
Content-Type: application/json
Authorization: Bearer {token}

{
  "columns": ["student_name", "amount", "payment_date", "status"],
  "filters": {
    "status": "confirmed",
    "start_date": "2024-01-01",
    "end_date": "2024-12-31"
  }
}
```

#### Enrollment Export
```bash
POST /api/enrollments/export
Content-Type: application/json
Authorization: Bearer {token}

{
  "columns": ["student_name", "course_name", "enrollment_date", "status"],
  "filters": {
    "status": "active",
    "payment_status": "paid"
  }
}
```

### 2. **Frontend Usage**

#### Sử dụng Export Modal
```jsx
import StudentExportModal from './components/Export/StudentExportModal';

function MyComponent() {
  const [exportModalOpen, setExportModalOpen] = useState(false);
  const [currentFilters, setCurrentFilters] = useState({});

  return (
    <>
      <Button onClick={() => setExportModalOpen(true)}>
        Export Students
      </Button>
      
      <StudentExportModal
        open={exportModalOpen}
        onClose={() => setExportModalOpen(false)}
        filters={currentFilters}
      />
    </>
  );
}
```

#### Sử dụng Export Hook trực tiếp
```jsx
import useStudentExport from './hooks/useStudentExport';

function MyComponent() {
  const { exportStudents, isExporting } = useStudentExport();

  const handleExport = async () => {
    const success = await exportStudents({
      columns: ['full_name', 'phone', 'email'],
      filters: { gender: 'male' },
      filename: 'male_students.xlsx'
    });
    
    if (success) {
      console.log('Export successful!');
    }
  };

  return (
    <Button 
      onClick={handleExport} 
      disabled={isExporting}
    >
      {isExporting ? 'Exporting...' : 'Export'}
    </Button>
  );
}
```

## 📋 Danh sách Columns có sẵn

### Student Export Columns
```javascript
const studentColumns = [
  'full_name',              // Họ và tên
  'first_name',             // Họ
  'last_name',              // Tên
  'phone',                  // Số điện thoại
  'email',                  // Email
  'date_of_birth',          // Ngày sinh
  'gender',                 // Giới tính
  'province',               // Tỉnh hiện tại
  'place_of_birth_province', // Tỉnh nơi sinh
  'ethnicity',              // Dân tộc
  'address',                // Địa chỉ
  'current_workplace',      // Nơi công tác
  'accounting_experience_years', // Kinh nghiệm kế toán
  'education_level',        // Trình độ học vấn
  'training_specialization', // Chuyên môn đào tạo
  'hard_copy_documents',    // Hồ sơ bản cứng
  'company_name',           // Tên công ty
  'tax_code',               // Mã số thuế
  'invoice_email',          // Email hóa đơn
  'company_address',        // Địa chỉ công ty
  'source',                 // Nguồn
  'notes',                  // Ghi chú
  'created_at',             // Ngày tạo
  'enrollments_count',      // Số khóa học
  'total_paid',             // Tổng đã thanh toán
  'total_fee',              // Tổng học phí
  'payment_status'          // Trạng thái thanh toán
];
```

### Payment Export Columns
```javascript
const paymentColumns = [
  'student_name',           // Họ tên học viên
  'student_phone',          // Số điện thoại
  'student_email',          // Email
  'course_name',            // Khóa học
  'payment_date',           // Ngày thanh toán
  'amount',                 // Số tiền
  'payment_method',         // Phương thức thanh toán
  'status',                 // Trạng thái
  'transaction_reference',  // Mã giao dịch
  'enrollment_date',        // Ngày ghi danh
  'final_fee',              // Học phí
  'notes'                   // Ghi chú
];
```

## 🔍 Bộ lọc (Filters)

### Student Filters
```javascript
const studentFilters = {
  search: 'Nguyễn Văn',      // Tìm kiếm theo tên, phone, email
  gender: 'male',            // male, female, other
  province_id: 1,            // ID tỉnh thành
  education_level: 'bachelor', // vocational, associate, bachelor, master, secondary
  start_date: '2024-01-01',  // Ngày tạo từ
  end_date: '2024-12-31',    // Ngày tạo đến
  course_item_id: 1,         // ID khóa học
  status: 'active'           // active, completed, waiting, cancelled
};
```

### Payment Filters
```javascript
const paymentFilters = {
  search: 'Nguyễn',          // Tìm kiếm học viên
  status: 'confirmed',       // pending, confirmed, cancelled
  payment_method: 'cash',    // cash, bank_transfer, card, qr_code, sepay
  start_date: '2024-01-01',  // Ngày thanh toán từ
  end_date: '2024-12-31',    // Ngày thanh toán đến
  course_item_id: 1          // ID khóa học
};
```

## 🌳 **Hierarchical Export (Khóa học phân cấp)**

### **Tính năng mới:**
Khi xuất học viên từ một khóa học cha, hệ thống sẽ **tự động bao gồm tất cả học viên từ các khóa học con**.

### **Cách hoạt động:**
1. **Khóa cha**: Khi export từ khóa cha → Bao gồm học viên từ tất cả khóa con
2. **Khóa con**: Khi export từ khóa con → Chỉ bao gồm học viên của khóa đó
3. **Thông tin khóa học**: Thêm cột `course_name` và `course_path` để phân biệt

### **Ví dụ:**
```
Kế toán Tổng hợp (Khóa cha)
├── Kế toán Cơ bản (2 học viên)
└── Kế toán Nâng cao (3 học viên)

Export "Kế toán Tổng hợp" → 5 học viên (2+3)
Export "Kế toán Cơ bản" → 2 học viên
```

### **Columns mới:**
- `course_name`: Tên khóa học cụ thể mà học viên đăng ký
- `course_path`: Đường dẫn đầy đủ (VD: "Kế toán Tổng hợp > Kế toán Cơ bản")

## 🧪 Testing

### 1. **Backend Testing**
```bash
# Chạy test export functionality
php test_export_functionality.php

# Test API endpoints
php test_export_api.php

# Test hierarchical export
php test_hierarchical_export.php
```

### 2. **Frontend Testing**
Truy cập: `/export-test` để sử dụng Export Test Page

### 3. **Manual Testing**
1. Đăng nhập vào hệ thống
2. Vào trang Students/Payments/Enrollments
3. Click nút "Export"
4. Chọn columns và filters
5. Click "Xuất Excel"
6. Kiểm tra file được download

## 🐛 Troubleshooting

### Lỗi thường gặp:

#### 1. "Lỗi khi xuất file"
- **Nguyên nhân**: Lỗi server hoặc validation
- **Giải pháp**: Kiểm tra logs Laravel, đảm bảo data hợp lệ

#### 2. File không download
- **Nguyên nhân**: CORS hoặc response headers
- **Giải pháp**: Kiểm tra network tab, đảm bảo API trả về blob

#### 3. "No data found"
- **Nguyên nhân**: Filters quá strict hoặc không có data
- **Giải pháp**: Thử export không filter trước

#### 4. Authentication error
- **Nguyên nhân**: Token hết hạn hoặc không hợp lệ
- **Giải pháp**: Đăng nhập lại

### Debug Steps:
1. Kiểm tra browser console
2. Kiểm tra network requests
3. Kiểm tra Laravel logs: `tail -f storage/logs/laravel.log`
4. Test API với Postman/curl

## 📈 Performance Tips

1. **Limit data**: Sử dụng filters để giảm số lượng records
2. **Chunking**: Với datasets lớn (>10k records), cân nhắc implement chunking
3. **Background jobs**: Với exports lớn, sử dụng queue jobs
4. **Caching**: Cache frequent exports

## 🔒 Security

1. **Authentication**: Tất cả endpoints yêu cầu auth token
2. **Authorization**: Kiểm tra user permissions
3. **Rate limiting**: Implement rate limiting cho export endpoints
4. **Data sanitization**: Validate và sanitize input data

## 📝 Logs

Export activities được log tại:
- Laravel logs: `storage/logs/laravel.log`
- Browser console: Network và error logs
- Database: Có thể implement audit logging nếu cần
