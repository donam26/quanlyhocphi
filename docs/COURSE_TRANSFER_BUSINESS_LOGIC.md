# 💼 BUSINESS LOGIC: CHUYỂN KHÓA HỌC KHI ĐÃ ĐÓNG TIỀN

## 🎯 1. TỔNG QUAN BUSINESS CASE

### 📋 **Tình huống thực tế**
Học viên đã ghi danh và đóng một phần hoặc toàn bộ học phí cho khóa A, nhưng muốn chuyển sang khóa B với mức học phí khác nhau.

### 🔍 **Các trường hợp có thể xảy ra**

| Trường hợp | Mô tả | Hành động cần thiết |
|------------|-------|-------------------|
| **Case 1** | Học phí mới > Đã đóng | Học viên cần đóng thêm |
| **Case 2** | Học phí mới < Đã đóng | Hoàn tiền hoặc tạo credit |
| **Case 3** | Học phí mới = Đã đóng | Chuyển đổi trực tiếp |
| **Case 4** | Đã đóng > Học phí mới | Hoàn tiền thừa |

## 🏗️ 2. KIẾN TRÚC BUSINESS LOGIC

### 📊 **Flow Chart Quy trình**

```
[Yêu cầu chuyển khóa]
         ↓
[Validate điều kiện]
         ↓
[Tính toán chi phí]
         ↓
[Xem trước kết quả] ← [User xác nhận]
         ↓
[Thực hiện chuyển khóa]
         ↓
[Xử lý thanh toán]
         ↓
[Cập nhật trạng thái]
         ↓
[Hoàn tất]
```

### 🔧 **Các thành phần chính**

1. **Validation Layer**: Kiểm tra điều kiện chuyển khóa
2. **Calculation Engine**: Tính toán chi phí và điều chỉnh
3. **Payment Handler**: Xử lý các giao dịch thanh toán
4. **Status Manager**: Quản lý trạng thái enrollment
5. **Audit Trail**: Ghi lại lịch sử thay đổi

## 💰 3. LOGIC TÍNH TOÁN CHI PHÍ

### 📈 **Công thức tính toán**

```php
// Bước 1: Tính học phí mới
$newBaseFee = $targetCourse->fee;
$discountPercentage = $oldEnrollment->discount_percentage;
$newFinalFee = $newBaseFee * (1 - $discountPercentage / 100);

// Bước 2: Áp dụng giảm giá bổ sung (nếu có)
if ($additionalDiscountPercentage > 0) {
    $newFinalFee *= (1 - $additionalDiscountPercentage / 100);
}
if ($additionalDiscountAmount > 0) {
    $newFinalFee -= $additionalDiscountAmount;
}

// Bước 3: Tính chênh lệch
$totalPaid = $oldEnrollment->getTotalPaidAmount();
$feeDifference = $newFinalFee - $totalPaid;

// Bước 4: Xác định loại chuyển đổi
if ($feeDifference > 0) {
    $transferType = 'additional_payment_required';
} elseif ($feeDifference < 0) {
    $transferType = 'refund_required';
} else {
    $transferType = 'equal_transfer';
}
```

### 🎛️ **Các tham số điều chỉnh**

- **Giảm giá bổ sung (%)**: Áp dụng thêm cho khóa mới
- **Giảm giá bổ sung (VND)**: Số tiền giảm cố định
- **Chính sách hoàn tiền**: full/credit/none
- **Trạng thái mới**: active/waiting

## 🔄 4. XỬ LÝ CÁC TRƯỜNG HỢP

### 💸 **Case 1: Cần đóng thêm tiền**

**Điều kiện**: `newFinalFee > totalPaid`

**Hành động**:
1. Tạo enrollment mới với học phí đầy đủ
2. Chuyển tất cả payments đã confirmed từ enrollment cũ
3. Tạo payment pending cho số tiền thiếu
4. Hủy enrollment cũ

**Code Logic**:
```php
// Tạo payment pending cho số tiền thiếu
Payment::create([
    'enrollment_id' => $newEnrollment->id,
    'amount' => $feeDifference,
    'status' => 'pending',
    'notes' => 'Thanh toán bổ sung do chuyển khóa học'
]);
```

### 💰 **Case 2: Cần hoàn tiền**

**Điều kiện**: `newFinalFee < totalPaid`

**Các chính sách hoàn tiền**:

#### 🏦 **Full Refund (Hoàn tiền đầy đủ)**
```php
Payment::create([
    'enrollment_id' => $newEnrollment->id,
    'amount' => -abs($feeDifference), // Negative = refund
    'payment_method' => 'refund',
    'status' => 'confirmed',
    'notes' => 'Hoàn tiền do chuyển khóa học'
]);
```

#### 💳 **Credit Balance (Tạo số dư)**
```php
Payment::create([
    'enrollment_id' => $newEnrollment->id,
    'amount' => abs($feeDifference),
    'payment_method' => 'credit',
    'status' => 'confirmed',
    'notes' => 'Credit balance do chuyển khóa học'
]);
```

#### 🚫 **No Refund (Không hoàn tiền)**
```php
// Chỉ ghi nhận trong notes
$newEnrollment->notes .= "\nSố tiền thừa: " . number_format(abs($feeDifference)) . " VND";
```

### ⚖️ **Case 3: Chuyển đổi trực tiếp**

**Điều kiện**: `newFinalFee == totalPaid`

**Hành động**:
1. Chuyển tất cả payments từ enrollment cũ sang mới
2. Không cần tạo payment bổ sung
3. Cập nhật trạng thái

## 🛡️ 5. VALIDATION RULES

### ✅ **Điều kiện bắt buộc**

1. **Enrollment hiện tại**:
   - Không được ở trạng thái `completed` hoặc `cancelled`
   - Phải tồn tại và thuộc về student

2. **Khóa học đích**:
   - Phải ở trạng thái `active`
   - Học viên chưa được ghi danh vào khóa này
   - Khóa học phải khác khóa hiện tại

3. **Thanh toán**:
   - Không có payment đang pending xử lý
   - Tổng đã thanh toán >= 0

### ⚠️ **Business Rules**

```php
// Kiểm tra enrollment có thể chuyển
if ($enrollment->status === 'completed') {
    throw new Exception('Không thể chuyển khóa học đã hoàn thành');
}

// Kiểm tra khóa đích
if (!$targetCourse->isActive()) {
    throw new Exception('Khóa học đích không còn hoạt động');
}

// Kiểm tra trùng lặp
$existing = Enrollment::where('student_id', $studentId)
    ->where('course_item_id', $targetCourseId)
    ->whereIn('status', ['active', 'waiting'])
    ->exists();
    
if ($existing) {
    throw new Exception('Học viên đã được ghi danh vào khóa học này');
}
```

## 📊 6. DATABASE DESIGN

### 🗄️ **Cấu trúc lưu trữ**

#### **Enrollments Table**
```sql
-- Thêm các trường tracking transfer
ALTER TABLE enrollments ADD COLUMN custom_fields JSON;

-- Lưu thông tin transfer
{
  "transfer_from_enrollment_id": 123,
  "transfer_date": "2024-01-15",
  "transfer_reason": "Học viên yêu cầu",
  "payment_calculation": {...}
}
```

#### **Payments Table**
```sql
-- Các loại payment method mới
payment_method ENUM('cash', 'bank_transfer', 'card', 'qr_code', 'sepay', 'refund', 'credit')

-- Amount có thể âm cho refund
amount DECIMAL(15,2) -- Có thể âm
```

### 🔍 **Audit Trail**

Mỗi transfer được ghi lại đầy đủ:
- Enrollment cũ → status = 'cancelled'
- Enrollment mới → custom_fields chứa thông tin transfer
- Payments → transaction_reference liên kết

## 🎨 7. FRONTEND UX/UI

### 📱 **Transfer Modal Components**

1. **Step 1: Chọn khóa đích**
   - Autocomplete với danh sách khóa available
   - Hiển thị học phí của từng khóa

2. **Step 2: Cấu hình transfer**
   - Chính sách hoàn tiền
   - Giảm giá bổ sung
   - Lý do chuyển khóa

3. **Step 3: Preview chi phí**
   - Bảng so sánh chi tiết
   - Highlight số tiền cần đóng thêm/hoàn lại
   - Các hành động sẽ thực hiện

4. **Step 4: Xác nhận**
   - Checkbox xác nhận đã đọc điều khoản
   - Button thực hiện transfer

### 🎯 **User Experience**

```jsx
// Preview Component
<TransferPreview>
  <CurrentCourse fee={oldFee} paid={totalPaid} />
  <Arrow />
  <TargetCourse fee={newFee} finalFee={finalFee} />
  <PaymentAdjustment 
    type={transferType}
    amount={Math.abs(feeDifference)}
    actions={requiredActions}
  />
</TransferPreview>
```

## 🚀 8. API ENDPOINTS

### 📡 **RESTful API Design**

```php
// Preview transfer cost
POST /api/enrollments/{id}/transfer-preview
{
  "target_course_id": 456,
  "additional_discount_percentage": 5,
  "refund_policy": "credit"
}

// Execute transfer
POST /api/enrollments/{id}/transfer
{
  "target_course_id": 456,
  "reason": "Học viên yêu cầu chuyển khóa",
  "refund_policy": "credit",
  "additional_discount_percentage": 5,
  "create_pending_payment": true
}

// Get transfer history
GET /api/students/{id}/transfer-history
```

### 📋 **Response Format**

```json
{
  "success": true,
  "data": {
    "new_enrollment": {...},
    "payment_summary": {
      "old_fee": 2000000,
      "new_final_fee": 2500000,
      "total_paid": 1500000,
      "fee_difference": 1000000,
      "transfer_type": "additional_payment_required",
      "actions_needed": [...]
    },
    "transfer_type": "additional_payment_required"
  }
}
```

## 🔒 9. SECURITY & COMPLIANCE

### 🛡️ **Bảo mật**

1. **Authorization**: Chỉ admin/staff có quyền transfer
2. **Validation**: Kiểm tra ownership của enrollment
3. **Rate Limiting**: Giới hạn số lần transfer/ngày
4. **Audit Log**: Ghi lại tất cả thay đổi

### 📝 **Compliance**

1. **Data Privacy**: Không lưu thông tin thanh toán nhạy cảm
2. **Financial Audit**: Đảm bảo tính toàn vẹn dữ liệu tài chính
3. **Business Rules**: Tuân thủ chính sách của trung tâm

## 🎯 10. KẾT LUẬN

### ✅ **Lợi ích của giải pháp**

1. **Tự động hóa**: Giảm thiểu thao tác thủ công
2. **Minh bạch**: Học viên thấy rõ chi phí trước khi quyết định
3. **Linh hoạt**: Hỗ trợ nhiều chính sách hoàn tiền
4. **Audit Trail**: Theo dõi đầy đủ lịch sử thay đổi
5. **User-friendly**: Giao diện trực quan, dễ sử dụng

### 🚀 **Khả năng mở rộng**

1. **Bulk Transfer**: Chuyển nhiều học viên cùng lúc
2. **Auto Transfer**: Tự động chuyển khi khóa bị hủy
3. **Transfer Rules**: Thiết lập quy tắc chuyển tự động
4. **Integration**: Tích hợp với hệ thống kế toán

### 📊 **Metrics theo dõi**

- Số lượng transfer/tháng
- Tỷ lệ transfer thành công
- Thời gian xử lý trung bình
- Mức độ hài lòng của học viên

---

**Kết luận**: Hệ thống chuyển khóa học đã được thiết kế hoàn chỉnh với logic business rõ ràng, xử lý đầy đủ các trường hợp edge case, và đảm bảo tính toàn vẹn dữ liệu tài chính.
