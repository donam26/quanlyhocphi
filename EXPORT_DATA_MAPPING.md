# 📊 **EXPORT DATA MAPPING - CHI TIẾT MAPPING DỮ LIỆU**

## 🎯 **Tổng quan**

Tài liệu này mô tả chi tiết cách mapping dữ liệu trong tất cả các Export classes để đảm bảo data được xuất đúng và đầy đủ.

---

## 📋 **1. STUDENT EXPORT MAPPING**

### **File:** `app/Exports/StudentsExport.php`

#### **Column Mappings:**
```php
'full_name' => 'Họ và tên',                    // $student->full_name (computed)
'first_name' => 'Họ',                          // $student->first_name
'last_name' => 'Tên',                          // $student->last_name
'phone' => 'Số điện thoại',                    // $student->phone
'email' => 'Email',                            // $student->email
'date_of_birth' => 'Ngày sinh',                // $student->date_of_birth (d/m/Y)
'gender' => 'Giới tính',                       // formatGender($student->gender)
'province' => 'Địa chỉ hiện tại',                 // $student->province->name
'place_of_birth_province' => 'Nơi sinh', // $student->placeOfBirthProvince->name
'ethnicity' => 'Dân tộc',                      // $student->ethnicity->name
'address' => 'Địa chỉ',                        // $student->address
'current_workplace' => 'Nơi công tác',         // $student->current_workplace
'accounting_experience_years' => 'Kinh nghiệm kế toán (năm)', // $student->accounting_experience_years
'education_level' => 'Trình độ học vấn',       // formatEducationLevel($student->education_level)
'training_specialization' => 'Chuyên môn đào tạo', // $student->training_specialization
'hard_copy_documents' => 'Hồ sơ bản cứng',     // formatBoolean($student->hard_copy_documents)
'company_name' => 'Tên công ty',               // $student->company_name
'tax_code' => 'Mã số thuế',                    // $student->tax_code
'invoice_email' => 'Email hóa đơn',            // $student->invoice_email
'company_address' => 'Địa chỉ công ty',        // $student->company_address
'source' => 'Nguồn',                          // formatSource($student->source)
'notes' => 'Ghi chú',                         // $student->notes
'created_at' => 'Ngày tạo',                   // $student->created_at (d/m/Y)
'enrollments_count' => 'Số khóa học',          // $student->enrollments->count()
'total_paid' => 'Tổng đã thanh toán',          // $student->getTotalPaidAmount() (formatted)
'total_fee' => 'Tổng học phí',                 // $student->getTotalFeeAmount() (formatted)
'payment_status' => 'Trạng thái thanh toán'   // getPaymentStatus() (computed)
```

#### **Format Functions:**
```php
formatGender($gender): 'Nam', 'Nữ', 'Khác'
formatEducationLevel($level): 'Trung học', 'Cao đẳng', 'Đại học', 'Thạc sĩ', 'Nghề'
formatSource($source): 'Website', 'Facebook', 'Giới thiệu', 'Khác'
formatBoolean($value): 'Có', 'Không'
```

---

## 💰 **2. PAYMENT EXPORT MAPPING**

### **File:** `app/Exports/PaymentExport.php`

#### **Column Mappings:**
```php
'student_name' => 'Họ và tên học viên',        // $payment->enrollment->student->full_name
'student_phone' => 'Số điện thoại',            // $payment->enrollment->student->phone
'student_email' => 'Email',                    // $payment->enrollment->student->email
'course_name' => 'Khóa học',                   // $payment->enrollment->courseItem->name
'payment_date' => 'Ngày thanh toán',           // $payment->payment_date (d/m/Y)
'amount' => 'Số tiền (VNĐ)',                   // number_format($payment->amount)
'payment_method' => 'Phương thức thanh toán',  // formatPaymentMethod($payment->payment_method)
'status' => 'Trạng thái',                      // formatStatus($payment->status)
'transaction_reference' => 'Mã giao dịch',     // $payment->transaction_reference
'enrollment_date' => 'Ngày ghi danh',          // $payment->enrollment->enrollment_date (d/m/Y)
'final_fee' => 'Học phí (VNĐ)',                // number_format($payment->enrollment->final_fee)
'notes' => 'Ghi chú',                          // $payment->notes
'student_address' => 'Địa chỉ học viên',       // $payment->enrollment->student->address
'student_workplace' => 'Nơi công tác'          // $payment->enrollment->student->current_workplace
```

#### **Format Functions:**
```php
formatPaymentMethod($method): 'Tiền mặt', 'Chuyển khoản', 'Thẻ tín dụng', 'Quét QR', 'SePay'
formatStatus($status): 'Chờ xác nhận', 'Đã xác nhận', 'Đã hủy'
```

---

## 🎓 **3. ENROLLMENT EXPORT MAPPING**

### **File:** `app/Exports/EnrollmentExport.php`

#### **Column Mappings:**
```php
'student_name' => 'Họ và tên học viên',        // $enrollment->student->full_name
'student_phone' => 'Số điện thoại',            // $enrollment->student->phone
'student_email' => 'Email',                    // $enrollment->student->email
'course_name' => 'Khóa học',                   // $enrollment->courseItem->name
'enrollment_date' => 'Ngày ghi danh',          // $enrollment->enrollment_date (d/m/Y)
'status' => 'Trạng thái ghi danh',             // formatStatus($enrollment->status)
'final_fee' => 'Học phí (VNĐ)',                // number_format($enrollment->final_fee)
'paid_amount' => 'Đã thanh toán (VNĐ)',        // number_format($enrollment->paid_amount)
'remaining_amount' => 'Còn lại (VNĐ)',         // number_format(final_fee - paid_amount)
'payment_status' => 'Trạng thái thanh toán',   // formatPaymentStatus($enrollment->payment_status)
'student_address' => 'Địa chỉ học viên',       // $enrollment->student->address
'student_workplace' => 'Nơi công tác',         // $enrollment->student->current_workplace
'student_province' => 'Địa chỉ hiện tại',        // $enrollment->student->province->name
'notes' => 'Ghi chú'                          // $enrollment->notes
```

#### **Format Functions:**
```php
formatStatus($status): 'Chờ xác nhận', 'Đang học', 'Hoàn thành', 'Đã hủy'
formatPaymentStatus($status): 'Chưa thanh toán', 'Thanh toán một phần', 'Đã thanh toán đủ', 'Miễn phí'
```

---

## 📅 **4. ATTENDANCE EXPORT MAPPING**

### **File:** `app/Exports/AttendanceExport.php`

#### **Column Mappings:**
```php
'student_name' => 'Họ và tên',                 // $attendance->enrollment->student->full_name
'student_phone' => 'Số điện thoại',            // $attendance->enrollment->student->phone
'student_email' => 'Email',                    // $attendance->enrollment->student->email
'attendance_date' => 'Ngày điểm danh',         // $attendance->attendance_date (d/m/Y)
'attendance_status' => 'Trạng thái',           // formatAttendanceStatus($attendance->status)
'course_name' => 'Khóa học',                   // $attendance->enrollment->courseItem->name
'notes' => 'Ghi chú',                          // $attendance->notes
'enrollment_date' => 'Ngày ghi danh',          // $attendance->enrollment->enrollment_date (d/m/Y)
'student_address' => 'Địa chỉ',                // $attendance->enrollment->student->address
'student_workplace' => 'Nơi công tác'          // $attendance->enrollment->student->current_workplace
```

#### **Format Functions:**
```php
formatAttendanceStatus($status): 'Có mặt', 'Vắng mặt', 'Đi muộn'
```

---

## 🔧 **5. API ENDPOINTS VÀ FILTERS**

### **Student Export API:**
```
POST /api/students/export
Filters: search, province_id, gender, education_level, start_date, end_date, course_item_id, status
```

### **Payment Export API:**
```
POST /api/payments/export
Filters: search, status, payment_method, start_date, end_date, course_item_id
```

### **Enrollment Export API:**
```
POST /api/enrollments/export
Filters: search, status, payment_status, start_date, end_date, course_item_id
```

### **Attendance Export API:**
```
POST /api/attendances/export
Filters: course_item_id (required), start_date, end_date
```

---

## 📝 **6. FRONTEND COLUMN DEFINITIONS**

### **Student Columns:**
```javascript
const studentColumns = [
  { key: 'full_name', label: 'Họ và tên', defaultSelected: true },
  { key: 'phone', label: 'Số điện thoại', defaultSelected: true },
  { key: 'email', label: 'Email', defaultSelected: true },
  { key: 'province', label: 'Địa chỉ hiện tại', defaultSelected: true },
  { key: 'current_workplace', label: 'Nơi công tác', defaultSelected: true },
  { key: 'created_at', label: 'Ngày tạo', defaultSelected: true },
  // ... more columns
];
```

### **Payment Columns:**
```javascript
const paymentColumns = [
  { key: 'student_name', label: 'Họ và tên học viên', defaultSelected: true },
  { key: 'student_phone', label: 'Số điện thoại', defaultSelected: true },
  { key: 'course_name', label: 'Khóa học', defaultSelected: true },
  { key: 'payment_date', label: 'Ngày thanh toán', defaultSelected: true },
  { key: 'amount', label: 'Số tiền (VNĐ)', defaultSelected: true },
  { key: 'payment_method', label: 'Phương thức thanh toán', defaultSelected: true },
  { key: 'status', label: 'Trạng thái', defaultSelected: true },
  // ... more columns
];
```

---

## ⚠️ **7. LƯU Ý QUAN TRỌNG**

### **Data Relationships:**
- **Student**: Có relationship với Province, Ethnicity, Enrollments, Payments
- **Payment**: Có relationship với Enrollment (qua enrollment_id)
- **Enrollment**: Có relationship với Student và CourseItem
- **Attendance**: Có relationship với Enrollment và CourseItem

### **Computed Fields:**
- `full_name`: Được tính từ `first_name + ' ' + last_name`
- `total_paid`: Tổng từ payments với status = 'confirmed'
- `total_fee`: Tổng từ enrollments với status = 'active'
- `payment_status`: Được tính dựa trên tỷ lệ paid/total

### **Date Formatting:**
- Tất cả dates được format thành `d/m/Y` (VD: 25/12/2024)
- API nhận dates ở format `Y-m-d` (VD: 2024-12-25)

### **Number Formatting:**
- Số tiền được format với `number_format($amount, 0, ',', '.')`
- VD: 1000000 → "1.000.000"

---

## 🧪 **8. TESTING DATA MAPPING**

### **Test Script:**
```bash
# Test tất cả export functions
php test_export_functionality.php

# Test API endpoints
php test_export_api.php
```

### **Frontend Test:**
```
Truy cập: /export-test
```

### **Manual Verification:**
1. Kiểm tra headers Excel có đúng tiếng Việt
2. Kiểm tra data mapping đúng với database
3. Kiểm tra format số tiền và ngày tháng
4. Kiểm tra filters hoạt động đúng
5. Kiểm tra file download thành công
