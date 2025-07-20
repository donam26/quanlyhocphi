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

// Trang chủ chuyển hướng đến dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Students
Route::resource('students', StudentController::class);
Route::get('students/{student}/enrollments', [StudentController::class, 'enrollments'])->name('students.enrollments');
Route::get('students/{student}/payments', [StudentController::class, 'payments'])->name('students.payments');

// Cấu trúc cây khóa học mới
Route::resource('course-items', CourseItemController::class);
Route::post('course-items/update-order', [CourseItemController::class, 'updateOrder'])->name('course-items.update-order');
Route::post('course-items/{courseItem}/toggle-active', [CourseItemController::class, 'toggleActive'])->name('course-items.toggle-active');
Route::get('tree', [CourseItemController::class, 'tree'])->name('course-items.tree');
Route::get('course-items/{id}/students', [CourseItemController::class, 'showStudents'])->name('course-items.students');
Route::post('course-items/{id}/students/import', [CourseItemController::class, 'importStudents'])->name('course-items.students.import');
Route::get('course-items/students/download-template', [CourseItemController::class, 'downloadImportTemplate'])->name('course-items.students.download-template');

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
Route::get('attendance/class/{class}/report', [AttendanceController::class, 'classReport'])->name('attendance.class-report');
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

Route::get('/welcome', function () {
    return view('welcome');
});
