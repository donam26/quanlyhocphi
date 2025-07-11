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
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseClassController;
use App\Http\Controllers\PaymentGatewayController;
use App\Http\Controllers\CoursePackageController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Trang chủ chuyển hướng đến dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Students
Route::resource('students', StudentController::class);
Route::get('students/{student}/enrollments', [StudentController::class, 'enrollments'])->name('students.enrollments');
Route::get('students/{student}/payments', [StudentController::class, 'payments'])->name('students.payments');

// Courses
Route::resource('courses', CourseController::class);
Route::post('courses/{course}/toggle-active', [CourseController::class, 'toggleActive'])->name('courses.toggle-active');
Route::post('courses/{course}/duplicate', [CourseController::class, 'duplicate'])->name('courses.duplicate');
Route::get('courses/{course}/sub-courses', [CourseController::class, 'subCourses'])->name('courses.sub-courses');
Route::get('courses/report/overview', [CourseController::class, 'report'])->name('courses.report');

// Course Classes
Route::resource('course-classes', CourseClassController::class);
Route::post('course-classes/{courseClass}/change-status', [CourseClassController::class, 'changeStatus'])->name('course-classes.change-status');
Route::post('course-classes/{courseClass}/duplicate', [CourseClassController::class, 'duplicate'])->name('course-classes.duplicate');
Route::post('course-classes/{courseClass}/mark-full', [CourseClassController::class, 'markFull'])->name('course-classes.mark-full');
Route::get('course-classes/{courseClass}/students', [CourseClassController::class, 'students'])->name('course-classes.students');
Route::get('course-classes/{courseClass}/financial-report', [CourseClassController::class, 'financialReport'])->name('course-classes.financial-report');
Route::get('course-classes-overview', [CourseClassController::class, 'overview'])->name('course-classes.overview');

// Enrollments
Route::resource('enrollments', EnrollmentController::class);
Route::post('enrollments/{enrollment}/confirm', [EnrollmentController::class, 'confirm'])->name('enrollments.confirm');
Route::delete('enrollments/{enrollment}/cancel', [EnrollmentController::class, 'cancel'])->name('enrollments.cancel');
Route::get('enrollments/waiting-list/{waitingList}', [EnrollmentController::class, 'createFromWaitingList'])->name('enrollments.from-waiting-list');
Route::get('unpaid-enrollments', [EnrollmentController::class, 'unpaidList'])->name('enrollments.unpaid');

// Waiting Lists
Route::resource('waiting-lists', WaitingListController::class);
Route::post('waiting-lists/{waitingList}/mark-contacted', [WaitingListController::class, 'markContacted'])->name('waiting-lists.mark-contacted');
Route::post('waiting-lists/{waitingList}/mark-not-interested', [WaitingListController::class, 'markNotInterested'])->name('waiting-lists.mark-not-interested');
Route::post('waiting-lists/{waitingList}/move-to-enrollment', [WaitingListController::class, 'moveToEnrollment'])->name('waiting-lists.move-to-enrollment');
Route::get('waiting-lists-needs-contact', [WaitingListController::class, 'needsContact'])->name('waiting-lists.needs-contact');
Route::get('waiting-lists-statistics', [WaitingListController::class, 'statistics'])->name('waiting-lists.statistics');

// Attendance
Route::resource('attendance', AttendanceController::class);
Route::get('attendance/class/show', [AttendanceController::class, 'showClass'])->name('attendance.show-class');
Route::get('attendance/student/{student}/report', [AttendanceController::class, 'studentReport'])->name('attendance.student-report');
Route::get('attendance/class/{courseClass}/report', [AttendanceController::class, 'classReport'])->name('attendance.class-report');
Route::post('attendance/quick', [AttendanceController::class, 'quickAttendance'])->name('attendance.quick');
Route::get('attendance/export/report', [AttendanceController::class, 'exportReport'])->name('attendance.export');

// Payments
Route::resource('payments', PaymentController::class);
Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirm'])->name('payments.confirm');
Route::post('payments/{payment}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
Route::get('payments/quick/{enrollment}', [PaymentController::class, 'quickPayment'])->name('payments.quick');
Route::get('payments/pending/list', [PaymentController::class, 'pendingPayments'])->name('payments.pending');
Route::get('payments/bulk-receipt', [PaymentController::class, 'bulkReceipt'])->name('payments.bulk-receipt');
Route::post('payments/bulk-action', [PaymentController::class, 'bulkAction'])->name('payments.bulk-action');
Route::get('payments/{payment}/receipt', [PaymentController::class, 'generateReceipt'])->name('payments.receipt');

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

// Send payment reminder
Route::post('payments/send-reminder', [PaymentController::class, 'sendReminder'])->name('payments.send-reminder');

// API Routes for AJAX calls
Route::prefix('api')->group(function () {
    // Search autocomplete
    Route::get('search/autocomplete', [SearchController::class, 'autocomplete'])->name('api.search.autocomplete');
    
    // Course info
    Route::get('courses/{course}/info', [CourseController::class, 'getCourseInfo'])->name('api.courses.info');
    Route::get('majors/{major}/courses', [CourseController::class, 'getByMajor'])->name('api.majors.courses');
    
    // Class info
    Route::get('course-classes/{courseClass}/info', [CourseClassController::class, 'getClassInfo'])->name('api.course-classes.info');
    Route::get('courses/{course}/classes', [CourseClassController::class, 'getByCourse'])->name('api.courses.classes');
    
    // Student info
    Route::get('students/{student}/info', [StudentController::class, 'getStudentInfo'])->name('api.students.info');
    
    // Enrollment info
    Route::get('enrollments/{enrollment}/info', [EnrollmentController::class, 'getEnrollmentInfo'])->name('api.enrollments.info');
    
    // Quick actions
    Route::post('enrollments/quick', [EnrollmentController::class, 'quickEnrollment'])->name('api.enrollments.quick');
    Route::post('payments/quick', [PaymentController::class, 'quickPayment'])->name('api.payments.quick');
    
    // Payments API
    Route::post('payments/bulk-action', [PaymentController::class, 'bulkAction'])->name('api.payments.bulk-action');
    Route::post('payments/{payment}/confirm', [PaymentController::class, 'confirmPayment'])->name('api.payments.confirm');
    Route::post('payments/{payment}/refund', [PaymentController::class, 'refundPayment'])->name('api.payments.refund');
    Route::get('payments/{payment}/info', [PaymentController::class, 'getPaymentInfo'])->name('api.payments.info');
});

// Payment Gateway Routes
Route::get('payment-gateway/{payment}', [PaymentGatewayController::class, 'show'])->name('payment.gateway.show');
Route::post('payment-gateway/{payment}/generate-qr', [PaymentGatewayController::class, 'generateQrCode'])->name('payment.gateway.generate-qr');
Route::get('payment-gateway/{payment}/status', [PaymentGatewayController::class, 'checkPaymentStatus'])->name('payment.gateway.status');
Route::post('payment-gateway/webhook/{payment}', [PaymentGatewayController::class, 'updatePaymentStatus'])->name('payment.gateway.webhook');

// Gói khóa học
Route::resource('course-packages', CoursePackageController::class);
Route::post('course-packages/{package}/add-classes', [CoursePackageController::class, 'addClasses'])->name('course-packages.add-classes');
Route::delete('course-packages/{package}/classes/{class}', [CoursePackageController::class, 'removeClass'])->name('course-packages.remove-class');
Route::post('course-packages/{package}/update-order', [CoursePackageController::class, 'updateClassesOrder'])->name('course-packages.update-order');
Route::post('courses/{course}/auto-create-package', [CoursePackageController::class, 'autoCreatePackage'])->name('courses.auto-create-package');

// Khóa con
Route::get('courses/{course}/sub-courses/create', [CourseController::class, 'createSubCourse'])->name('courses.sub-courses.create');
Route::post('courses/{course}/sub-courses', [CourseController::class, 'storeSubCourse'])->name('courses.sub-courses.store');
Route::get('courses/{course}/sub-courses/{subCourse}/edit', [CourseController::class, 'editSubCourse'])->name('courses.sub-courses.edit');
Route::put('courses/{course}/sub-courses/{subCourse}', [CourseController::class, 'updateSubCourse'])->name('courses.sub-courses.update');
Route::delete('courses/{course}/sub-courses/{subCourse}', [CourseController::class, 'destroySubCourse'])->name('courses.sub-courses.destroy');

// API routes
Route::get('api/courses/{course}/sub-courses', [CourseController::class, 'apiSubCourses']);

// Setup khóa học phức tạp
Route::post('courses/{course}/setup-complex', [CourseController::class, 'setupComplexCourse'])->name('courses.setup-complex');

// Liên kết lớp học với gói
Route::post('course-classes/{courseClass}/add-to-package', [CourseClassController::class, 'addToPackage'])->name('course-classes.add-to-package');
Route::delete('course-classes/{courseClass}/remove-from-package', [CourseClassController::class, 'removeFromPackage'])->name('course-classes.remove-from-package');

// Quản lý lớp con
Route::post('course-classes/{parentClass}/add-child', [CourseClassController::class, 'addChildClass'])->name('course-classes.add-child');
Route::delete('course-classes/{parentClass}/children/{childClass}', [CourseClassController::class, 'removeChildClass'])->name('course-classes.remove-child');

Route::get('/welcome', function () {
    return view('welcome');
});
