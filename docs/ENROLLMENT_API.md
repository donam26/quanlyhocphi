# API Ghi danh (Enrollment API)

## Tổng quan
API này cung cấp các endpoint để quản lý ghi danh học viên vào các khóa học, bao gồm danh sách chờ, xác nhận ghi danh, và các thao tác liên quan.

## Base URL
```
/api/enrollments
```

## Authentication
Tất cả các endpoint đều yêu cầu authentication với Laravel Sanctum token.

## Endpoints

### 1. Lấy danh sách ghi danh
```http
GET /api/enrollments
```

**Query Parameters:**
- `status` (string, optional): Lọc theo trạng thái (waiting, active, completed, cancelled)
- `course_item_id` (integer, optional): Lọc theo khóa học
- `student_id` (integer, optional): Lọc theo học viên
- `search` (string, optional): Tìm kiếm theo tên học viên hoặc khóa học
- `per_page` (integer, optional): Số bản ghi trên mỗi trang (default: 15)
- `date_from` (date, optional): Lọc từ ngày (Y-m-d)
- `date_to` (date, optional): Lọc đến ngày (Y-m-d)

**Response:**
```json
{
  "success": true,
  "data": {
    "data": [
      {
        "id": 1,
        "student_id": 1,
        "course_item_id": 1,
        "enrollment_date": "2024-01-15",
        "status": "active",
        "discount_percentage": 10,
        "discount_amount": 100000,
        "final_fee": 900000,
        "notes": "Ghi chú",
        "student": {
          "id": 1,
          "full_name": "Nguyễn Văn A",
          "phone": "0901234567"
        },
        "course_item": {
          "id": 1,
          "name": "Khóa học Kế toán",
          "fee": 1000000
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total": 50,
      "per_page": 15
    }
  }
}
```

### 2. Tạo ghi danh mới
```http
POST /api/enrollments
```

**Request Body:**
```json
{
  "student_id": 1,
  "course_item_id": 1,
  "enrollment_date": "2024-01-15",
  "status": "waiting",
  "discount_percentage": 10,
  "discount_amount": 0,
  "final_fee": 900000,
  "notes": "Ghi chú"
}
```

**Validation Rules:**
- `student_id`: required, exists:students,id
- `course_item_id`: required, exists:course_items,id
- `enrollment_date`: required, date
- `status`: required, in:waiting,active,completed,cancelled
- `discount_percentage`: nullable, numeric, min:0, max:100
- `discount_amount`: nullable, numeric, min:0
- `final_fee`: nullable, numeric, min:0
- `notes`: nullable, string, max:1000

### 3. Cập nhật ghi danh
```http
PUT /api/enrollments/{id}
```

**Request Body:** (Tương tự POST nhưng tất cả field đều optional)

### 4. Xóa ghi danh
```http
DELETE /api/enrollments/{id}
```

### 5. Xác nhận từ danh sách chờ
```http
POST /api/enrollments/{id}/confirm-waiting
```

Chuyển trạng thái từ "waiting" sang "active".

### 6. Hủy ghi danh
```http
POST /api/enrollments/{id}/cancel
```

Chuyển trạng thái sang "cancelled" và set cancelled_at.

### 7. Chuyển học viên sang khóa học khác
```http
POST /api/enrollments/{id}/transfer
```

**Request Body:**
```json
{
  "target_course_id": 2,
  "notes": "Lý do chuyển"
}
```

### 8. Lấy thống kê ghi danh
```http
GET /api/enrollments/stats
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total": 100,
    "waiting": 15,
    "active": 60,
    "completed": 20,
    "cancelled": 5,
    "completion_rate": 20.0,
    "cancellation_rate": 5.0
  }
}
```

### 9. Lấy cây danh sách chờ
```http
GET /api/enrollments/waiting-list-tree
```

Trả về cấu trúc cây khóa học với số lượng học viên đang chờ.

### 10. Lấy học viên chờ theo khóa học
```http
GET /api/enrollments/course/{courseId}/waiting-students
```

### 11. Xuất danh sách ghi danh
```http
POST /api/enrollments/export
```

**Request Body:** (Tương tự query parameters của GET)

## Status Codes

- `200`: Success
- `201`: Created
- `422`: Validation Error
- `404`: Not Found
- `500`: Server Error

## Trạng thái ghi danh (Enrollment Status)

- `waiting`: Danh sách chờ
- `active`: Đang học
- `completed`: Hoàn thành
- `cancelled`: Đã hủy

## Business Logic

### Quy trình ghi danh:
1. Học viên được thêm vào danh sách chờ (`waiting`)
2. Admin xác nhận chuyển sang trạng thái học chính thức (`active`)
3. Khi hoàn thành khóa học, chuyển sang (`completed`)
4. Có thể hủy bất kỳ lúc nào (`cancelled`)

### Ràng buộc:
- Một học viên chỉ có thể ghi danh một lần cho mỗi khóa học
- Không thể xác nhận từ danh sách chờ nếu trạng thái không phải `waiting`
- Học phí cuối cùng = Học phí gốc - (Chiết khấu % + Chiết khấu số tiền)

## Ví dụ sử dụng

### Tạo ghi danh mới:
```javascript
const enrollment = await enrollmentService.createEnrollment({
  student_id: 1,
  course_item_id: 1,
  enrollment_date: '2024-01-15',
  status: 'waiting',
  discount_percentage: 10,
  final_fee: 900000
});
```

### Xác nhận từ danh sách chờ:
```javascript
await enrollmentService.confirmFromWaiting(enrollmentId);
```

### Lấy thống kê:
```javascript
const stats = await enrollmentService.getStats();
console.log(`Tổng số ghi danh: ${stats.total}`);
```
