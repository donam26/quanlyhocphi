<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CourseItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\CourseItemController as ApiCourseItemController;
use App\Http\Controllers\Api\ProvinceController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/healthy', function () {
    return response()->json(['message' => 'API is running']);
});

// API cho khóa học - đặt trước để đảm bảo route đúng
Route::get('/course-items/available', [ApiCourseItemController::class, 'available']);

// API tìm kiếm học viên (không yêu cầu xác thực)
Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])->name('api.search.autocomplete');

// API tìm kiếm khóa học
Route::get('/course-items/search', [CourseItemController::class, 'search'])->name('api.course-items.search');

// API học viên
Route::get('/students/{id}/info', [App\Http\Controllers\Api\StudentController::class, 'getInfo']);
Route::post('/students/{id}/update', [App\Http\Controllers\Api\StudentController::class, 'update']);
Route::post('/students/create', [App\Http\Controllers\Api\StudentController::class, 'store']);
Route::get('/enrollments/{id}/info', [App\Http\Controllers\Api\StudentController::class, 'getEnrollmentInfo']);
Route::get('/students/province/{provinceId}', [App\Http\Controllers\Api\StudentController::class, 'getByProvince']);
Route::get('/students/region/{region}', [App\Http\Controllers\Api\StudentController::class, 'getByRegion']);
Route::get('/students', [App\Http\Controllers\Api\StudentController::class, 'index']);

// API cho cấu trúc cây khóa học mới
Route::get('/course-items', [CourseItemController::class, 'index']);
Route::post('/course-items', [CourseItemController::class, 'store']);
Route::get('/course-items/{id}', [CourseItemController::class, 'getCourseDetails']);
Route::put('/course-items/{id}', [CourseItemController::class, 'update']);
Route::delete('/course-items/{id}', [CourseItemController::class, 'destroy']);

// API cho thanh toán
Route::post('/payments/bulk-action', [PaymentController::class, 'bulkAction'])->name('api.payments.bulk-action');
Route::post('/payments/{payment}/confirm', [PaymentController::class, 'confirmPayment']);
Route::post('/payments/{payment}/refund', [PaymentController::class, 'refundPayment']);
Route::get('/payments/{payment}', [PaymentController::class, 'getPaymentInfo']);

// Thanh toán
Route::prefix('payments')->group(function () {
    Route::get('/{id}', [App\Http\Controllers\Api\PaymentController::class, 'getInfo']);
    Route::post('/', [App\Http\Controllers\Api\PaymentController::class, 'store']);
    Route::post('/{id}', [App\Http\Controllers\Api\PaymentController::class, 'update']);
    Route::post('/{id}/confirm', [App\Http\Controllers\Api\PaymentController::class, 'confirm']);
    Route::post('/{id}/cancel', [App\Http\Controllers\Api\PaymentController::class, 'cancel']);
});

// API cho ghi danh
Route::prefix('enrollments')->group(function() {
    Route::post('/', [App\Http\Controllers\Api\EnrollmentController::class, 'store']);
    Route::get('/{id}', [App\Http\Controllers\Api\EnrollmentController::class, 'getInfo']);
    Route::post('/{id}', [App\Http\Controllers\Api\EnrollmentController::class, 'update']);
    Route::get('/{id}/payments', [App\Http\Controllers\Api\EnrollmentController::class, 'getPayments']);
    Route::get('/student/{id}', [App\Http\Controllers\Api\EnrollmentController::class, 'getStudentEnrollments']);
});

// API cho tỉnh thành
Route::prefix('provinces')->group(function () {
    Route::get('/', [ProvinceController::class, 'index']);
    Route::get('/search', [ProvinceController::class, 'search']);
    Route::get('/region/{region}', [ProvinceController::class, 'getByRegion']);
    Route::get('/{id}', [ProvinceController::class, 'show']);
});

// API cho tiến độ học tập
Route::prefix('learning-progress')->group(function () {
    Route::get('/course/{courseId}', [App\Http\Controllers\Api\LearningProgressController::class, 'getCourseProgress']);
    Route::get('/student/{studentId}', [App\Http\Controllers\Api\LearningProgressController::class, 'getStudentProgress']);
    Route::post('/update', [App\Http\Controllers\Api\LearningProgressController::class, 'updateProgress']);
    Route::post('/update-bulk', [App\Http\Controllers\Api\LearningProgressController::class, 'updateBulkProgress']);
    Route::post('/update-path-status', [App\Http\Controllers\Api\LearningProgressController::class, 'updatePathStatus']);
});
