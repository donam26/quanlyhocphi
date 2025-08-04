<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\CourseItemController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\LearningPathController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\LearningProgressController;

// Auth Routes (đã được tạo bởi laravel/ui)
Auth::routes(['register' => false]); // Tắt đăng ký, vì chỉ có admin

// Chuyển hướng trang chủ công khai đến trang đăng nhập
Route::get('/', function () {
    return redirect()->route('login');
});

// Các routes yêu cầu đăng nhập và quyền admin
Route::middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getDataByTimeRange'])->name('dashboard.data');

    // Students
    Route::get('students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
    Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::get('students/{student}/enrollments', [StudentController::class, 'enrollments'])->name('students.enrollments');
    Route::get('students/{student}/payments', [StudentController::class, 'payments'])->name('students.payments');

    // Cấu trúc cây khóa học mới
    Route::post('course-items/update-order', [CourseItemController::class, 'updateOrder'])->name('course-items.update-order');
    Route::get('course-items/download-template', [CourseItemController::class, 'exportTemplate'])->name('course-items.download-template');
    Route::post('course-items/{courseItem}/toggle-active', [CourseItemController::class, 'toggleActive'])->name('course-items.toggle-active');
    Route::get('course-items/tree', [CourseItemController::class, 'tree'])->name('course-items.tree');
    Route::get('course-items/{id}/students', [CourseItemController::class, 'showStudents'])->name('course-items.students');
    Route::get('course-items/{id}/add-student', [CourseItemController::class, 'addStudentForm'])->name('course-items.add-student');
    Route::post('course-items/{id}/add-student', [CourseItemController::class, 'addStudent'])->name('course-items.store-student');
    Route::post('course-items/{id}/import-students', [CourseItemController::class, 'importStudents'])->name('course-items.import-students');
    Route::resource('course-items', CourseItemController::class)->except(['show']);


    // Chức năng điểm danh theo khóa học
    Route::get('course-items/{courseItem}/attendance', [AttendanceController::class, 'createByCourse'])->name('course-items.attendance');
    Route::post('course-items/{courseItem}/attendance', [AttendanceController::class, 'storeByCourse'])->name('course-items.attendance.store');
    Route::get('course-items/{courseItem}/attendance/{date}', [AttendanceController::class, 'showByDate'])->name('course-items.attendance.by-date');

    // Lộ trình học tập
    Route::get('course-items/{courseItem}/learning-paths/create', [LearningPathController::class, 'create'])->name('learning-paths.create');
    Route::post('course-items/{courseItem}/learning-paths', [LearningPathController::class, 'store'])->name('learning-paths.store');
    Route::get('course-items/{courseItem}/learning-paths/edit', [LearningPathController::class, 'edit'])->name('learning-paths.edit');
    Route::put('course-items/{courseItem}/learning-paths', [LearningPathController::class, 'update'])->name('learning-paths.update');
    Route::post('enrollments/{enrollment}/learning-paths/{learningPath}/toggle', [LearningPathController::class, 'toggleCompletion'])->name('learning-paths.toggle-completion');
    Route::post('course-items/{courseItem}/learning-paths/{learningPath}/toggle', [LearningPathController::class, 'toggleCompletionFromCourse'])->name('learning-paths.toggle-from-course');

    // Enrollments
    Route::resource('enrollments', EnrollmentController::class);
    Route::post('enrollments/update-fee', [EnrollmentController::class, 'updateFee'])->name('enrollments.update-fee');
    Route::get('unpaid-enrollments', [EnrollmentController::class, 'unpaidList'])->name('enrollments.unpaid');

    // Danh sách chờ (Waitlist) - tích hợp trong Enrollment
    Route::get('waiting-list', [EnrollmentController::class, 'waitingList'])->name('enrollments.waiting-list');
    Route::get('waiting-list/needs-contact', [EnrollmentController::class, 'needsContact'])->name('enrollments.needs-contact');
    Route::post('enrollments/{enrollment}/confirm-waiting', [EnrollmentController::class, 'confirmFromWaiting'])->name('enrollments.confirm-waiting');
    Route::post('enrollments/{enrollment}/add-waiting-note', [EnrollmentController::class, 'addWaitingNote'])->name('enrollments.add-waiting-note');
    Route::post('enrollments/move-to-waiting', [EnrollmentController::class, 'moveToWaiting'])->name('enrollments.move-to-waiting');
    Route::get('course-items/{courseItem}/waiting-list', [CourseItemController::class, 'waitingList'])->name('course-items.waiting-list');

    // Attendance
    Route::resource('attendance', AttendanceController::class);
    Route::get('attendance/class/show', [AttendanceController::class, 'showClass'])->name('attendance.show-class');
    Route::get('attendance/student/{student}/report', [AttendanceController::class, 'studentReport'])->name('attendance.student-report');
    Route::get('attendance/class/{class}/report', [AttendanceController::class, 'classReport'])->name('attendance.class-report');
    Route::post('attendance/quick', [AttendanceController::class, 'quickAttendance'])->name('attendance.quick');
    Route::get('attendance/export/report', [AttendanceController::class, 'exportReport'])->name('attendance.export');

    // Payments
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/create', [PaymentController::class, 'create'])->name('create');
        Route::post('/', [PaymentController::class, 'store'])->name('store');
        Route::get('/{payment}', [PaymentController::class, 'show'])->name('show');
        Route::get('/{payment}/edit', [PaymentController::class, 'edit'])->name('edit');
        Route::put('/{payment}', [PaymentController::class, 'update'])->name('update');
        Route::delete('/{payment}', [PaymentController::class, 'destroy'])->name('destroy');
        Route::post('/send-reminder', [PaymentController::class, 'sendReminder'])->name('send-reminder');
        Route::post('/direct-reminder', [PaymentController::class, 'sendDirectReminder'])->name('send-direct-reminder');
        Route::post('/combined-reminder', [PaymentController::class, 'sendCombinedReminder'])->name('send-combined-reminder');
        Route::get('/receipt/{payment}', [PaymentController::class, 'generateReceipt'])->name('receipt');
        Route::get('/bulk-receipt', [PaymentController::class, 'bulkReceipt'])->name('bulk-receipt');
        Route::post('/quick', [PaymentController::class, 'quickPayment'])->name('quick');
        Route::get('/monthly-report', [PaymentController::class, 'monthlyReport'])->name('monthly-report');
        Route::post('/bulk-action', [PaymentController::class, 'bulkAction'])->name('bulk-action');
        Route::get('/course/{courseItem}', [PaymentController::class, 'coursePayments'])->name('course');
        Route::get('/by-course/{courseItem}', [PaymentController::class, 'coursePayments'])->name('by-course');
        Route::post('/confirm/{payment}', [PaymentController::class, 'confirmPayment'])->name('confirm');
        Route::post('/refund/{payment}', [PaymentController::class, 'refundPayment'])->name('refund');
    });

    // Payment Gateway
    Route::prefix('payment-gateway')->name('payment.gateway.')->group(function () {
        Route::get('/{payment}', [PaymentGatewayController::class, 'show'])->name('show');
        Route::get('/direct/{enrollment}', [PaymentController::class, 'showDirectPaymentGateway'])->name('direct');
        Route::get('/status/{payment}', [PaymentGatewayController::class, 'checkPaymentStatus'])->name('status');
        Route::get('/check-direct', [PaymentGatewayController::class, 'checkDirectPaymentStatus'])->name('check-direct');
        Route::post('/process', [PaymentGatewayController::class, 'process'])->name('process');
        Route::post('/webhook', [PaymentGatewayController::class, 'webhook'])->name('webhook');
    });

    // Search
    Route::get('search', [SearchController::class, 'index'])->name('search.index');
    Route::post('search', [SearchController::class, 'search'])->name('search');
    Route::get('search/student/{student}/history', [SearchController::class, 'studentHistory'])->name('search.student-history');

    // Các route cho báo cáo
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/revenue', [ReportController::class, 'revenueReport'])->name('revenue');
        Route::get('/students', [ReportController::class, 'studentReport'])->name('students');
        Route::get('/enrollments', [ReportController::class, 'enrollmentReport'])->name('enrollments');
        Route::get('/payments', [ReportController::class, 'paymentReport'])->name('payments');
        Route::get('/attendance', [ReportController::class, 'attendanceReport'])->name('attendance');

        // Route xuất báo cáo
        Route::get('/revenue/export', [ReportController::class, 'exportRevenueReport'])->name('revenue.export');
        Route::get('/payments/export', [ReportController::class, 'exportPaymentReport'])->name('payments.export');
        Route::get('/attendance/export', [ReportController::class, 'exportAttendanceReport'])->name('attendance.export');
    });
});

// Payment routes
Route::middleware(['auth'])->group(function () {
    // Thanh toán
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('/payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
    Route::post('/payments/quick', [PaymentController::class, 'quickPayment'])->name('payments.quick');
    Route::post('/payments/send-reminder', [PaymentController::class, 'sendReminder'])->name('payments.send-reminder');
    Route::get('/payments/course/{courseItem}', [PaymentController::class, 'coursePayments'])->name('payments.course');
    Route::get('/payments/receipt/{payment}', [PaymentController::class, 'generateReceipt'])->name('payments.receipt');
    Route::get('/payments/bulk-receipt', [PaymentController::class, 'bulkReceipt'])->name('payments.bulk-receipt');
    Route::post('/payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.mark-confirmed');
    Route::post('/payments/{payment}/cancel', [PaymentController::class, 'cancel'])->name('payments.cancel');
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
    Route::get('/payments/export', [PaymentController::class, 'export'])->name('payments.export');
    Route::get('/payments/batches/{batchId}/stats', [PaymentController::class, 'getBatchStats'])->name('payments.batch-stats');
    Route::get('/payments/batches', [PaymentController::class, 'getUserBatches'])->name('payments.user-batches');
});

// Course item routes
Route::middleware(['auth'])->group(function () {
    Route::get('/course-items/{id}/attendance', [AttendanceController::class, 'createByCourse'])->name('course-items.attendance.view');
    Route::post('/course-items/{courseItem}/attendance', [AttendanceController::class, 'storeByCourse'])->name('course-items.attendance.store');
    Route::get('/course-items/{courseItem}/attendance/{date}', [AttendanceController::class, 'showByDate'])->name('course-items.attendance.by-date');

    // Lịch học
    Route::get('/schedules', [ScheduleController::class, 'index'])->name('schedules.index');
    Route::get('/schedules/create', [ScheduleController::class, 'create'])->name('schedules.create');
    Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
    Route::get('/schedules/{schedule}/edit', [ScheduleController::class, 'edit'])->name('schedules.edit');
    Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
    Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('schedules.destroy');
    Route::get('/course-items/{courseItem}/schedules', [ScheduleController::class, 'showCourseSchedule'])->name('course-items.schedules');

    // Tiến độ học tập
    Route::get('/learning-progress', [LearningProgressController::class, 'index'])->name('learning-progress.index');
});
