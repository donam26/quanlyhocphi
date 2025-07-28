<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WaitingListController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\CoursePackageController;
use App\Http\Controllers\CourseItemController;
use App\Http\Controllers\ClassesController;
use App\Http\Controllers\LearningPathController;
use Illuminate\Support\Facades\Auth;

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

    // Students
    Route::resource('students', StudentController::class);
    Route::get('students/{student}/enrollments', [StudentController::class, 'enrollments'])->name('students.enrollments');
    Route::get('students/{student}/payments', [StudentController::class, 'payments'])->name('students.payments');

    // Cấu trúc cây khóa học mới
    Route::post('course-items/update-order', [CourseItemController::class, 'updateOrder'])->name('course-items.update-order');
    Route::get('course-items/download-template', [CourseItemController::class, 'exportTemplate'])->name('course-items.download-template');
    Route::post('course-items/{courseItem}/toggle-active', [CourseItemController::class, 'toggleActive'])->name('course-items.toggle-active');
    Route::get('tree', [CourseItemController::class, 'tree'])->name('course-items.tree');
    Route::get('course-items/{id}/students', [CourseItemController::class, 'showStudents'])->name('course-items.students');
    Route::post('course-items/{id}/import-students', [CourseItemController::class, 'importStudents'])->name('course-items.import-students');
    Route::resource('course-items', CourseItemController::class);

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
    Route::post('enrollments/{enrollment}/confirm', [EnrollmentController::class, 'confirm'])->name('enrollments.confirm');
    Route::delete('enrollments/{enrollment}/cancel', [EnrollmentController::class, 'cancel'])->name('enrollments.cancel');
    Route::get('enrollments/waiting-list/{waitingList}', [EnrollmentController::class, 'createFromWaitingList'])->name('enrollments.from-waiting-list');
    Route::post('enrollments/from-waiting-list', [EnrollmentController::class, 'storeFromWaitingList'])->name('enrollments.store-from-waiting');
    Route::get('unpaid-enrollments', [EnrollmentController::class, 'unpaidList'])->name('enrollments.unpaid');

    // Waiting Lists
    Route::resource('waiting-lists', WaitingListController::class);
    Route::post('waiting-lists/{waitingList}/mark-contacted', [WaitingListController::class, 'markContacted'])->name('waiting-lists.mark-contacted');
    Route::post('waiting-lists/{waitingList}/mark-not-interested', [WaitingListController::class, 'markNotInterested'])->name('waiting-lists.mark-not-interested');
    Route::post('waiting-lists/{waitingList}/move-to-enrollment', [WaitingListController::class, 'moveToEnrollment'])->name('waiting-lists.move-to-enrollment');
    Route::get('waiting-lists-needs-contact', [WaitingListController::class, 'needsContact'])->name('waiting-lists.needs-contact');
    Route::get('waiting-lists-statistics', [WaitingListController::class, 'statistics'])->name('waiting-lists.statistics');
    Route::get('course-items/{courseItem}/waiting-lists', [WaitingListController::class, 'showByCourseItem'])->name('course-items.waiting-lists');
    Route::post('enrollments/move-to-waiting-list', [WaitingListController::class, 'moveFromEnrollment'])->name('enrollments.move-to-waiting');

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

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/revenue', [ReportController::class, 'revenueReport'])->name('reports.revenue');
    Route::get('reports/students', [ReportController::class, 'studentReport'])->name('reports.students');
    Route::get('reports/enrollments', [ReportController::class, 'enrollmentReport'])->name('reports.enrollments');
    Route::get('reports/payments', [ReportController::class, 'paymentReport'])->name('reports.payments');
    Route::get('reports/attendance', [ReportController::class, 'attendanceReport'])->name('reports.attendance');
    
    // Export reports
    Route::get('reports/revenue/export', [ReportController::class, 'exportRevenueReport'])->name('reports.revenue.export');
    Route::get('reports/payments/export', [ReportController::class, 'exportPaymentReport'])->name('reports.payments.export');
    Route::get('reports/attendance/export', [ReportController::class, 'exportAttendanceReport'])->name('reports.attendance.export');

    // Gói khóa học
    Route::resource('course-packages', CoursePackageController::class);
    Route::post('course-packages/{package}/add-classes', [CoursePackageController::class, 'addClasses'])->name('course-packages.add-classes');
    Route::delete('course-packages/{package}/classes/{class}', [CoursePackageController::class, 'removeClass'])->name('course-packages.remove-class');
    Route::post('course-packages/{package}/update-order', [CoursePackageController::class, 'updateClassesOrder'])->name('course-packages.update-order');
});
