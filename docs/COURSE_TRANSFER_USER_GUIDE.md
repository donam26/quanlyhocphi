# 📚 HƯỚNG DẪN SỬ DỤNG: CHUYỂN KHÓA HỌC

## 🎯 Tổng quan

Tính năng chuyển khóa học cho phép bạn chuyển học viên từ khóa học hiện tại sang khóa học khác, với xử lý thanh toán tự động và minh bạch.

## 🚀 Cách sử dụng

### 📋 **Bước 1: Truy cập chức năng chuyển khóa**

#### **Cách 1: Từ modal chỉnh sửa ghi danh**
1. Vào trang **Quản lý Ghi danh**
2. Click vào nút **Chỉnh sửa** (✏️) của ghi danh cần chuyển
3. Trong modal chỉnh sửa, chọn tab **"Chuyển khóa học"**

#### **Cách 2: Từ menu thao tác**
1. Vào trang **Quản lý Ghi danh**
2. Click vào nút **Thao tác** (⋮) của ghi danh
3. Chọn **"Chuyển khóa học"**

### 📝 **Bước 2: Điền thông tin chuyển khóa**

#### **Thông tin bắt buộc:**
- **Khóa học đích**: Chọn khóa học muốn chuyển đến
- **Lý do chuyển khóa**: Mô tả lý do (tùy chọn)

#### **Cài đặt nâng cao:**
- **Chính sách hoàn tiền**:
  - 🏦 **Hoàn tiền đầy đủ**: Hoàn tiền mặt số tiền thừa
  - 💳 **Tạo credit balance**: Tạo số dư tín dụng (mặc định)
  - 🚫 **Không hoàn tiền**: Không hoàn lại số tiền thừa

- **Giảm giá bổ sung**:
  - **Giảm giá %**: Phần trăm giảm giá thêm
  - **Giảm giá VND**: Số tiền giảm cố định

- **Phương thức thanh toán**: Cho khoản thanh toán bổ sung (nếu có)

### 🔍 **Bước 3: Xem trước chi phí**

1. Click nút **"Xem trước chi phí chuyển khóa"**
2. Hệ thống sẽ hiển thị:
   - **Thông tin khóa học hiện tại và mới**
   - **Tính toán chi phí chi tiết**
   - **Các hành động cần thực hiện**

#### **Các trường hợp có thể xảy ra:**

🟡 **Cần đóng thêm tiền**
- Học phí khóa mới > Số tiền đã đóng
- Hệ thống sẽ tạo thanh toán chờ xử lý

🔵 **Cần hoàn tiền**
- Học phí khóa mới < Số tiền đã đóng
- Xử lý theo chính sách hoàn tiền đã chọn

🟢 **Chuyển đổi trực tiếp**
- Học phí khóa mới = Số tiền đã đóng
- Không cần điều chỉnh thanh toán

### ✅ **Bước 4: Xác nhận chuyển khóa**

1. Kiểm tra kỹ thông tin trong phần xem trước
2. Click nút **"Xác nhận chuyển khóa"**
3. Hệ thống sẽ:
   - Hủy ghi danh cũ
   - Tạo ghi danh mới
   - Xử lý thanh toán theo tính toán
   - Ghi lại lịch sử chuyển khóa

## 📊 Ví dụ thực tế

### **Ví dụ 1: Cần đóng thêm tiền**
```
Khóa hiện tại: Kế toán cơ bản (2,000,000 VND)
Đã thanh toán: 1,500,000 VND
Khóa mới: Kế toán nâng cao (3,000,000 VND)
Giảm giá: 10%

→ Học phí mới: 2,700,000 VND
→ Cần đóng thêm: 1,200,000 VND
```

### **Ví dụ 2: Cần hoàn tiền**
```
Khóa hiện tại: Kế toán nâng cao (3,000,000 VND)
Đã thanh toán: 2,500,000 VND
Khóa mới: Kế toán cơ bản (2,000,000 VND)

→ Học phí mới: 2,000,000 VND
→ Thừa thanh toán: 500,000 VND
→ Xử lý: Tạo credit balance
```

## ⚠️ Lưu ý quan trọng

### **Điều kiện chuyển khóa:**
- ✅ Ghi danh phải ở trạng thái **"Chờ xác nhận"** hoặc **"Đang học"**
- ❌ Không thể chuyển ghi danh đã **"Hoàn thành"** hoặc **"Đã hủy"**
- ✅ Khóa học đích phải đang **hoạt động**
- ❌ Học viên chưa được ghi danh vào khóa học đích

### **Về thanh toán:**
- 💰 Tất cả thanh toán đã xác nhận sẽ được chuyển sang ghi danh mới
- 🔄 Thanh toán chờ xử lý của ghi danh cũ sẽ bị hủy
- 📝 Lịch sử thanh toán được ghi lại đầy đủ

### **Về dữ liệu:**
- 📋 Thông tin học viên không thay đổi
- 🎓 Lịch sử điểm danh của khóa cũ được giữ lại
- 📊 Báo cáo và thống kê được cập nhật tự động

## 🔍 Theo dõi và kiểm tra

### **Kiểm tra lịch sử chuyển khóa:**
1. Vào **Tìm kiếm nâng cao**
2. Nhập tên hoặc SĐT học viên
3. Xem tab **"Lịch sử ghi danh"**

### **Kiểm tra thanh toán:**
1. Vào trang **Quản lý Thanh toán**
2. Lọc theo học viên hoặc khóa học
3. Xem chi tiết các giao dịch

## 🆘 Xử lý sự cố

### **Lỗi thường gặp:**

❌ **"Học viên đã được ghi danh vào khóa học này"**
- **Nguyên nhân**: Học viên đã có ghi danh active/waiting cho khóa đích
- **Giải pháp**: Kiểm tra và hủy ghi danh trùng lặp trước

❌ **"Khóa học đích không còn hoạt động"**
- **Nguyên nhân**: Khóa học đã bị đóng hoặc hoàn thành
- **Giải pháp**: Chọn khóa học khác hoặc mở lại khóa học

❌ **"Không thể chuyển khóa học đã hoàn thành"**
- **Nguyên nhân**: Ghi danh đã ở trạng thái completed
- **Giải pháp**: Tạo ghi danh mới thay vì chuyển khóa

### **Liên hệ hỗ trợ:**
- 📧 Email: support@example.com
- 📞 Hotline: 1900-xxxx
- 💬 Chat: Góc phải màn hình

## 🎯 Tips sử dụng hiệu quả

1. **Luôn xem trước** chi phí trước khi xác nhận
2. **Ghi rõ lý do** chuyển khóa để dễ theo dõi
3. **Kiểm tra thanh toán** sau khi chuyển khóa
4. **Thông báo học viên** về việc chuyển khóa
5. **Cập nhật lịch học** nếu cần thiết

---

**Lưu ý**: Tính năng này yêu cầu quyền **Admin** hoặc **Staff**. Liên hệ quản trị viên nếu không thể truy cập.
