# Tính năng Khóa học đặc biệt - Trường thông tin tùy chỉnh

## Tổng quan
Tính năng này cho phép tạo khóa học đặc biệt với các trường thông tin bổ sung dành riêng cho lớp Kế toán trưởng. Khi một khóa học được đánh dấu là "đặc biệt", hệ thống sẽ hiển thị và yêu cầu nhập thêm các thông tin chuyên môn của học viên.

## Các trường thông tin bổ sung

### 1. Nơi công tác hiện tại (`current_workplace`)
- Kiểu: String
- Bắt buộc: Có (đối với khóa học đặc biệt)
- Mô tả: Nơi làm việc hiện tại của học viên

### 2. Số năm kinh nghiệm kế toán (`accounting_experience_years`)
- Kiểu: Integer
- Bắt buộc: Có (đối với khóa học đặc biệt)
- Mô tả: Số năm kinh nghiệm làm kế toán của học viên

### 3. Chuyên môn đào tạo (`training_specialization`)
- Kiểu: String
- Bắt buộc: Có (đối với khóa học đặc biệt)
- Mô tả: Lĩnh vực chuyên môn đào tạo của học viên

### 4. Trình độ học vấn (`education_level`)
- Kiểu: Enum
- Giá trị: `secondary`, `vocational`, `associate`, `bachelor`, `master`
- Bắt buộc: Có (đối với khóa học đặc biệt)
- Mô tả: Trình độ học vấn cao nhất của học viên

### 5. Tình trạng hồ sơ bản cứng (`hard_copy_documents`)
- Kiểu: Enum
- Giá trị: `submitted`, `not_submitted`
- Bắt buộc: Có (đối với khóa học đặc biệt)
- Mô tả: Tình trạng nộp hồ sơ giấy tờ bản cứng

## Luồng hoạt động

### 1. Tạo khóa học đặc biệt
1. Vào trang Courses
2. Click "Thêm khóa học mới"
3. Điền thông tin cơ bản
4. Bật switch "Khóa học đặc biệt"
5. Hệ thống hiển thị thông báo về các trường bổ sung
6. Lưu khóa học

### 2. Thêm học viên vào khóa học đặc biệt
1. Mở modal thêm học viên
2. Điền thông tin cơ bản
3. Tab "Thông tin Kế toán trưởng" sẽ xuất hiện
4. Điền đầy đủ các thông tin bổ sung (bắt buộc)
5. Lưu thông tin học viên

### 3. Xem chi tiết học viên trong khóa học đặc biệt
1. Vào CourseDetailModal
2. Tab "Học viên" sẽ hiển thị thêm thông tin bổ sung
3. Mỗi học viên sẽ có một khung thông tin riêng với:
   - Nơi công tác
   - Kinh nghiệm (số năm)
   - Chuyên môn đào tạo
   - Trình độ học vấn
   - Tình trạng hồ sơ

## Các file đã được cập nhật

### Frontend
1. **CourseModal.jsx**
   - Thêm thông báo khi bật "Khóa học đặc biệt"
   - Hiển thị danh sách các trường sẽ được thêm

2. **CourseDetailModal.jsx**
   - Hiển thị thông tin bổ sung trong danh sách học viên
   - Chỉ hiển thị khi `course.is_special === true`

3. **StudentModal.jsx**
   - Thêm prop `course` để nhận thông tin khóa học
   - Chỉ hiển thị tab "Thông tin Kế toán trưởng" khi khóa học đặc biệt
   - Đánh dấu các trường là bắt buộc cho khóa học đặc biệt

4. **ReopenCourseModal.jsx** (mới)
   - Modal cho phép chọn cách xử lý trạng thái học viên khi mở lại khóa học

### Backend
1. **Database Migration**
   - Các trường đã có sẵn trong migration `create_students_table.php`

2. **Student Model**
   - Các trường đã được khai báo trong `$fillable`

3. **CourseItemController**
   - Method `students()` đã trả về đầy đủ thông tin học viên
   - Method `complete()` và `reopen()` đã được triển khai

## Cách sử dụng

### Tạo khóa học đặc biệt
```javascript
// Khi tạo khóa học, set is_special = true
const courseData = {
  name: "Lớp Kế toán trưởng",
  is_special: true,
  // ... các trường khác
};
```

### Thêm học viên với thông tin bổ sung
```javascript
// Khi thêm học viên vào khóa học đặc biệt
const studentData = {
  first_name: "Nguyễn",
  last_name: "Văn A",
  // ... thông tin cơ bản
  
  // Thông tin bổ sung cho khóa học đặc biệt
  current_workplace: "Công ty ABC",
  accounting_experience_years: 5,
  training_specialization: "Kế toán doanh nghiệp",
  education_level: "bachelor",
  hard_copy_documents: "submitted"
};
```

## Validation

### Frontend
- Các trường bổ sung được đánh dấu `required` khi khóa học đặc biệt
- Hiển thị helper text "Bắt buộc cho khóa học đặc biệt"

### Backend
- Validation đã có sẵn trong StudentController
- Kiểm tra `accounting_experience_years >= 0`

## Lưu ý kỹ thuật

1. **Tương thích ngược**: Các khóa học thường vẫn hoạt động bình thường
2. **Hiển thị có điều kiện**: UI chỉ hiển thị khi cần thiết
3. **Validation linh hoạt**: Chỉ bắt buộc khi là khóa học đặc biệt
4. **Dữ liệu an toàn**: Các trường nullable trong database

## Test cases

1. ✅ Tạo khóa học thường - không hiển thị trường bổ sung
2. ✅ Tạo khóa học đặc biệt - hiển thị thông báo
3. ✅ Thêm học viên vào khóa học thường - tab bình thường
4. ✅ Thêm học viên vào khóa học đặc biệt - hiển thị tab bổ sung
5. ✅ Xem danh sách học viên khóa học thường - hiển thị cơ bản
6. ✅ Xem danh sách học viên khóa học đặc biệt - hiển thị thông tin bổ sung
7. ✅ Build frontend thành công
8. ✅ Backend API hoạt động đúng

## Kết luận

Tính năng đã được triển khai thành công với đầy đủ chức năng:
- Tạo khóa học đặc biệt
- Hiển thị trường bổ sung khi cần
- Validation phù hợp
- UI/UX trực quan và dễ sử dụng
- Tương thích với hệ thống hiện tại
