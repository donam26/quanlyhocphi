# API Setup - Backend Laravel

## 🚀 Cấu trúc API đã tạo

### 1. **API Routes** (`routes/api.php`)
Đã tạo đầy đủ API endpoints cho frontend React:

#### Authentication
- `POST /api/login` - Đăng nhập
- `POST /api/logout` - Đăng xuất  
- `GET /api/user` - Thông tin user hiện tại

#### Dashboard
- `GET /api/dashboard/data` - Dữ liệu dashboard
- `GET /api/dashboard/stats` - Thống kê
- `GET /api/dashboard/activities` - Hoạt động gần đây

#### Students
- `GET /api/students` - Danh sách học viên (có pagination, search, filter)
- `POST /api/students` - Tạo học viên mới
- `GET /api/students/{id}` - Chi tiết học viên
- `PUT /api/students/{id}` - Cập nhật học viên
- `DELETE /api/students/{id}` - Xóa học viên
- `GET /api/students/{id}/enrollments` - Ghi danh của học viên
- `GET /api/students/{id}/payments` - Thanh toán của học viên

#### Courses
- `GET /api/course-items` - Danh sách khóa học
- `GET /api/course-items/tree` - Cây khóa học
- `POST /api/course-items` - Tạo khóa học
- `PUT /api/course-items/{id}` - Cập nhật khóa học
- `DELETE /api/course-items/{id}` - Xóa khóa học
- `GET /api/course-items/{id}/students` - Học viên của khóa học
- `GET /api/course-items/{id}/waiting-list` - Danh sách chờ

#### Enrollments
- `GET /api/enrollments` - Danh sách ghi danh
- `POST /api/enrollments` - Tạo ghi danh
- `PUT /api/enrollments/{id}` - Cập nhật ghi danh
- `POST /api/enrollments/{id}/confirm-waiting` - Xác nhận từ danh sách chờ
- `POST /api/enrollments/{id}/cancel` - Hủy ghi danh

#### Payments
- `GET /api/payments` - Danh sách thanh toán
- `POST /api/payments` - Tạo thanh toán
- `POST /api/payments/{id}/confirm` - Xác nhận thanh toán
- `POST /api/payments/{id}/cancel` - Hủy thanh toán

### 2. **API Controllers** (`app/Http/Controllers/Api/`)
Đã tạo các controllers:

- `AuthController` - Xử lý authentication với Sanctum
- `DashboardController` - Thống kê dashboard
- `StudentController` - CRUD học viên với validation
- `CourseItemController` - CRUD khóa học với tree structure
- `EnrollmentController` - CRUD ghi danh với status management

### 3. **Authentication với Laravel Sanctum**
- Đã cấu hình Sanctum cho SPA authentication
- CORS đã được setup cho frontend React
- Stateful domains bao gồm localhost:3000, 127.0.0.1:3000

## 🔧 Cách test API

### 1. **Đăng nhập để lấy token**
```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@example.com",
    "password": "password"
  }'
```

Response:
```json
{
  "user": {...},
  "token": "1|abc123...",
  "message": "Login successful"
}
```

### 2. **Sử dụng token cho các API khác**
```bash
curl -X GET http://127.0.0.1:8000/api/students \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Accept: application/json"
```

### 3. **Test Dashboard API**
```bash
curl -X GET http://127.0.0.1:8000/api/dashboard/data \
  -H "Authorization: Bearer 1|abc123..." \
  -H "Accept: application/json"
```

## 📱 Frontend Integration

### 1. **API Base URL**
Frontend đã được cấu hình để gọi API tại:
```
REACT_APP_API_URL=http://127.0.0.1:8000
```

### 2. **Authentication Flow**
1. User login → Nhận token
2. Store token trong localStorage
3. Gửi token trong header cho mọi API call
4. Auto logout khi token expired

### 3. **API Response Format**
Tất cả API đều trả về JSON với format chuẩn:

**Success Response:**
```json
{
  "data": [...],
  "message": "Success message"
}
```

**Error Response:**
```json
{
  "message": "Error message",
  "errors": {
    "field": ["Validation error"]
  }
}
```

**Pagination Response:**
```json
{
  "data": [...],
  "current_page": 1,
  "last_page": 10,
  "per_page": 15,
  "total": 150
}
```

## 🛠️ Cần hoàn thiện

### 1. **Các API Controllers còn thiếu:**
- `PaymentController` - Hoàn thiện CRUD thanh toán
- `AttendanceController` - API điểm danh  
- `ReportController` - API báo cáo
- `SearchController` - API tìm kiếm

### 2. **Validation Rules**
- Thêm validation rules chi tiết hơn
- Custom validation messages tiếng Việt

### 3. **File Upload/Download**
- API upload file Excel
- API download file Excel/PDF
- API upload avatar học viên

### 4. **Real-time Features**
- WebSocket cho notifications
- Real-time updates

## 🔒 Security

### 1. **Rate Limiting**
Thêm rate limiting cho API:
```php
Route::middleware(['throttle:60,1'])->group(function () {
    // API routes
});
```

### 2. **API Versioning**
Cân nhắc thêm versioning:
```php
Route::prefix('v1')->group(function () {
    // API routes
});
```

### 3. **Input Sanitization**
Đảm bảo tất cả input đều được validate và sanitize.

## 🚀 Deployment

### 1. **Environment Variables**
```env
SANCTUM_STATEFUL_DOMAINS=yourdomain.com
CORS_ALLOWED_ORIGINS=https://yourdomain.com
```

### 2. **Database Migration**
```bash
php artisan migrate
php artisan db:seed
```

### 3. **Cache Configuration**
```bash
php artisan config:cache
php artisan route:cache
```

## 📞 Testing

### 1. **Unit Tests**
Tạo tests cho các API controllers:
```bash
php artisan make:test StudentApiTest
```

### 2. **Feature Tests**
Test toàn bộ flow từ authentication đến CRUD operations.

---

**Kết luận:** API backend đã được cấu trúc chuẩn và sẵn sàng cho frontend React. Cần hoàn thiện thêm một số controllers và features để có hệ thống hoàn chỉnh.
