<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\CourseItemController;
use App\Http\Controllers\Api\ProvinceController;
use App\Http\Controllers\Api\EthnicityController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\EnrollmentController;
use App\Http\Controllers\Api\LearningProgressController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/healthy', function () {
    return response()->json(['message' => 'API is running']);
});

// API tìm kiếm học viên (không yêu cầu xác thực)
Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])->name('api.search.autocomplete');
Route::get('/search/details', [SearchController::class, 'getStudentDetails'])->name('api.search.details');
Route::get('/search/student/{student}/history', [SearchController::class, 'getStudentHistory'])->name('api.search.student-history');

// API RESOURCES
// Tất cả API endpoints được phân nhóm và sử dụng controllers trong thư mục Api/

// API cho Khóa học
Route::prefix('course-items')->group(function () {
    Route::get('/', [CourseItemController::class, 'index']);
    Route::get('/available', [CourseItemController::class, 'available']);
    Route::get('/search', [CourseItemController::class, 'search']);
    Route::get('/search-active', [CourseItemController::class, 'searchActiveCourses']);
    Route::get('/leaf-courses', [CourseItemController::class, 'getLeafCourses']);
    Route::get('/active-leaf-courses', [CourseItemController::class, 'getActiveLeafCourses']);
    Route::get('/{id}', [CourseItemController::class, 'show']);
    Route::get('/{id}/learning-paths', [CourseItemController::class, 'getLearningPaths']);
    Route::post('/{id}/learning-paths', [CourseItemController::class, 'saveLearningPaths']);
    Route::post('/', [CourseItemController::class, 'store']);
    Route::put('/{id}', [CourseItemController::class, 'update']);
    Route::delete('/{id}', [CourseItemController::class, 'destroy']);
});

// API cho Học sinh
Route::prefix('students')->group(function () {
    Route::get('/', [StudentController::class, 'index']);
    Route::get('/{id}/info', [StudentController::class, 'getInfo']);
    Route::get('/{id}/details', [StudentController::class, 'getStudentDetails']);
    Route::post('/{id}/update', [StudentController::class, 'update']);
    Route::post('/create', [StudentController::class, 'store']);
    Route::delete('/{id}/delete', [StudentController::class, 'destroy']);
    Route::get('/province/{provinceId}', [StudentController::class, 'getByProvince']);
    Route::get('/region/{region}', [StudentController::class, 'getByRegion']);
});

// API cho Thanh toán
Route::prefix('payments')->group(function () {
    // Lấy thông tin thanh toán
    Route::get('/{id}', [PaymentController::class, 'getInfo']);
    Route::get('/by-student/{studentId}', [PaymentController::class, 'getByStudent']);
    Route::get('/by-course/{courseId}', [PaymentController::class, 'getByCourse']);
    
    // Thêm/cập nhật thanh toán
    Route::post('/', [PaymentController::class, 'store']);
    Route::post('/{id}', [PaymentController::class, 'update']);
    
    // Quản lý trạng thái thanh toán
    Route::post('/{id}/confirm', [PaymentController::class, 'confirm']);
    Route::post('/{id}/cancel', [PaymentController::class, 'cancel']);
    Route::post('/{id}/refund', [PaymentController::class, 'refund']);
    
    // Hành động hàng loạt
    Route::post('/bulk-action', [PaymentController::class, 'bulkAction']);
});

// API cho Ghi danh
Route::prefix('enrollments')->group(function() {
    Route::post('/', [EnrollmentController::class, 'store']);
    Route::get('/{id}', [EnrollmentController::class, 'getInfo']);
    Route::post('/{id}', [EnrollmentController::class, 'update']);
    Route::post('/{id}/cancel', [EnrollmentController::class, 'cancelEnrollment']);
    Route::get('/{id}/payments', [EnrollmentController::class, 'getPayments']);
    Route::get('/student/{id}', [EnrollmentController::class, 'getStudentEnrollments']);
});

// API cho Tỉnh thành
Route::prefix('provinces')->group(function () {
    Route::get('/', [ProvinceController::class, 'index']);
    Route::get('/search', [ProvinceController::class, 'search']);
    Route::get('/region/{region}', [ProvinceController::class, 'getByRegion']);
    Route::get('/{id}', [ProvinceController::class, 'show']);
});
Route::get('/provinces', [ProvinceController::class, 'index']); // Route trực tiếp cho provinces

// API cho Dân tộc
Route::prefix('ethnicities')->group(function () {
    Route::get('/', [EthnicityController::class, 'index']);
    Route::get('/{id}', [EthnicityController::class, 'show']);
});

// API cho Tiến độ học tập
Route::prefix('learning-progress')->group(function () {
    Route::get('/course/{courseId}', [LearningProgressController::class, 'getCourseProgress']);
    Route::get('/student/{studentId}', [LearningProgressController::class, 'getStudentProgress']);
    Route::post('/update', [LearningProgressController::class, 'updateProgress']);
    Route::post('/update-bulk', [LearningProgressController::class, 'updateBulkProgress']);
    Route::post('/update-path-status', [LearningProgressController::class, 'updatePathStatus']);
    Route::post('/toggle-path-completion/{pathId}', [LearningProgressController::class, 'togglePathCompletion']);
});
