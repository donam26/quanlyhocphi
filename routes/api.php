<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\CourseItemController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\LearningPathController;
use App\Http\Controllers\Api\EnrollmentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Dashboard API
    Route::prefix('dashboard')->group(function () {
        Route::get('/', [DashboardController::class, 'index']);
        Route::get('/summary', [DashboardController::class, 'summary']);
        Route::get('/data-by-time-range', [DashboardController::class, 'getDataByTimeRange']);
    });

    // Students API
    Route::prefix('students')->group(function () {
        Route::post('/export', [StudentController::class, 'export']);
        Route::get('/', [StudentController::class, 'index']);
        Route::get('/advanced-search', [StudentController::class, 'advancedSearch']);
        Route::get('/import-template', [StudentController::class, 'downloadImportTemplate']);
        Route::post('/', [StudentController::class, 'store']);
        Route::get('/{student}', [StudentController::class, 'show']);
        Route::put('/{student}', [StudentController::class, 'update']);
        Route::delete('/{student}', [StudentController::class, 'destroy']);

        // Student relationships
        Route::get('/{student}/enrollments', [StudentController::class, 'enrollments']);
        Route::get('/{student}/payments', [StudentController::class, 'payments']);
        Route::get('/{student}/details', [StudentController::class, 'details']);
        Route::get('/{student}/info', [StudentController::class, 'info']);
        Route::get('/{student}/available-courses', [StudentController::class, 'availableCourses']);

        // Bulk operations
        Route::post('/import', [StudentController::class, 'import']);

        // Search and filter
        Route::get('/province/{province}', [StudentController::class, 'byProvince']);
        Route::get('/region/{region}', [StudentController::class, 'byRegion']);
    });

    // Course Items API
    Route::prefix('course-items')->group(function () {
        // Search and filter (must be before parameterized routes)
        Route::get('/search', [CourseItemController::class, 'search']);
        Route::get('/search-active', [CourseItemController::class, 'searchActive']);
        Route::get('/available', [CourseItemController::class, 'available']);
        Route::get('/active-leaf-courses', [CourseItemController::class, 'activeLeafCourses']);
        Route::get('/available-for-enrollment', [CourseItemController::class, 'availableForEnrollment']);
        Route::get('/leaf-courses', [CourseItemController::class, 'leafCourses']);
        Route::get('/tree', [CourseItemController::class, 'tree']);

        // CRUD operations
        Route::get('/', [CourseItemController::class, 'index']);
        Route::post('/', [CourseItemController::class, 'store']);
        Route::get('/{courseItem}', [CourseItemController::class, 'show']);
        Route::put('/{courseItem}', [CourseItemController::class, 'update']);
        Route::delete('/{courseItem}', [CourseItemController::class, 'destroy']);

        // Tree structure
        Route::post('/update-order', [CourseItemController::class, 'updateOrder']);
        Route::post('/reorder', [CourseItemController::class, 'reorder']);

        // Course status
        Route::post('/{courseItem}/toggle-status', [CourseItemController::class, 'toggleStatus']);
        Route::post('/{courseItem}/complete', [CourseItemController::class, 'completeCourse']);
        Route::post('/{courseItem}/reopen', [CourseItemController::class, 'reopenCourse']);
        Route::post('/{courseItem}/complete', [CourseItemController::class, 'complete']);
        Route::post('/{courseItem}/reopen', [CourseItemController::class, 'reopen']);

        // Course students
        Route::get('/{courseItem}/students', [CourseItemController::class, 'students']);
        Route::get('/{courseItem}/students-recursive', [CourseItemController::class, 'studentsRecursive']);
        Route::post('/{courseItem}/add-student', [CourseItemController::class, 'addStudent']);
        Route::post('/{courseItem}/import-students', [CourseItemController::class, 'importStudents']);
        Route::post('/{courseItem}/import-students-to-waiting', [CourseItemController::class, 'importStudentsToWaiting']);
        Route::post('/{courseItem}/export-students', [CourseItemController::class, 'exportStudents']);

        // Waiting list
        Route::get('/{courseItem}/waiting-list', [CourseItemController::class, 'waitingList']);
        Route::get('/{courseItem}/waiting-count', [CourseItemController::class, 'waitingCount']);

        // Learning paths
        Route::get('/{courseItem}/learning-paths', [CourseItemController::class, 'getLearningPaths']);
        Route::post('/{courseItem}/learning-paths', [CourseItemController::class, 'saveLearningPaths']);
    });

    // Enrollments API
    Route::prefix('enrollments')->group(function () {
        // Statistics and overview (phải đặt trước routes có parameter)
        Route::get('/stats', [EnrollmentController::class, 'getStats']);
        Route::get('/waiting-list-tree', [EnrollmentController::class, 'getWaitingListTree']);
        Route::get('/course/{courseId}/waiting-students', [EnrollmentController::class, 'getWaitingStudentsByCourse']);
        Route::get('/course/{courseId}/waiting-list', [EnrollmentController::class, 'getWaitingListByCourse']);
        Route::get('/course/{courseId}/all-students', [EnrollmentController::class, 'getAllStudentsByCourse']);
        Route::post('/{enrollmentId}/generate-payment-link', [EnrollmentController::class, 'generatePaymentLink']);

        // CRUD operations
        Route::get('/', [EnrollmentController::class, 'index']);
        Route::post('/', [EnrollmentController::class, 'store']);
        Route::get('/{enrollment}', [EnrollmentController::class, 'show']);
        Route::put('/{enrollment}', [EnrollmentController::class, 'update']);
        Route::delete('/{enrollment}', [EnrollmentController::class, 'destroy']);

        // Enrollment actions
        Route::post('/{enrollment}/confirm-waiting', [EnrollmentController::class, 'confirmFromWaiting']);
        Route::post('/{enrollment}/cancel', [EnrollmentController::class, 'cancel']);
        Route::post('/{enrollment}/move-to-waiting', [EnrollmentController::class, 'moveToWaiting']);
        Route::post('/{enrollment}/transfer', [EnrollmentController::class, 'transferStudent']);
        Route::post('/{enrollment}/transfer-preview', [EnrollmentController::class, 'previewTransfer']);

        // Export
        Route::post('/export', [EnrollmentController::class, 'export']);
    });

    // Payments API
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::post('/', [PaymentController::class, 'store']);

        // Statistics and overview (phải đặt trước routes có parameter)
        Route::get('/stats', [PaymentController::class, 'stats']);
        Route::get('/unpaid-enrollments', [PaymentController::class, 'getUnpaidEnrollments']);
        Route::get('/payment-overview', [PaymentController::class, 'getPaymentOverview']);
        Route::get('/courses-with-unpaid', [PaymentController::class, 'getCoursesWithUnpaidStudents']);
        Route::get('/course/{courseId}/unpaid-students', [PaymentController::class, 'getCourseUnpaidStudents']);

        // Reminder endpoints
        Route::post('/send-bulk-reminders', [PaymentController::class, 'sendBulkReminders']);
        Route::post('/send-course-reminders', [PaymentController::class, 'sendCourseReminders']);

        // Payment relationships
        Route::get('/by-student/{student}', [PaymentController::class, 'byStudent']);
        Route::get('/by-course/{courseItem}', [PaymentController::class, 'byCourse']);
        Route::get('/history/{enrollment}', [PaymentController::class, 'history']);

        // Bulk operations
        Route::post('/bulk-action', [PaymentController::class, 'bulkAction']);
        Route::post('/send-reminder', [PaymentController::class, 'sendReminder']);
        Route::post('/bulk-receipt', [PaymentController::class, 'bulkReceipt']);

        // Routes với parameter (phải đặt cuối)
        Route::get('/{payment}', [PaymentController::class, 'show']);
        Route::put('/{payment}', [PaymentController::class, 'update']);
        Route::delete('/{payment}', [PaymentController::class, 'destroy']);

        // Payment actions
        Route::post('/{payment}/confirm', [PaymentController::class, 'confirm']);
        Route::post('/{payment}/cancel', [PaymentController::class, 'cancel']);
        Route::post('/{payment}/refund', [PaymentController::class, 'refund']);

        // SePay integration
        Route::post('/sepay/initiate', [PaymentController::class, 'initiateSePayPayment']);
        Route::get('/sepay/status/{paymentId}', [PaymentController::class, 'checkSePayPaymentStatus']);

        // Reports and exports
        Route::post('/export', [PaymentController::class, 'export']);
        Route::get('/receipt/{payment}', [PaymentController::class, 'generateReceipt']);
        Route::get('/monthly-report', [PaymentController::class, 'monthlyReport']);
        Route::get('/batches', [PaymentController::class, 'getUserBatches']);
        Route::get('/batches/{batchId}/stats', [PaymentController::class, 'getBatchStats']);
    });

    // Attendance API
    Route::prefix('attendance')->group(function () {
        Route::get('/', [AttendanceController::class, 'index']);
        Route::post('/', [AttendanceController::class, 'store']);
        

        // Tree and course structure
        Route::get('/tree', [AttendanceController::class, 'getAttendanceTree']);
        Route::get('/course/{courseItem}/students', [AttendanceController::class, 'getStudentsByCourse']);

        // Attendance by course
        Route::get('/course/{courseItem}', [AttendanceController::class, 'byCourse']);
        Route::post('/course/{courseItem}', [AttendanceController::class, 'storeByCourse']);
        Route::get('/course/{courseItem}/date/{date}', [AttendanceController::class, 'byDate']);

        // Bulk operations
        Route::post('/quick', [AttendanceController::class, 'quickAttendance']);
        Route::post('/save-from-tree', [AttendanceController::class, 'saveFromTree']);

        // Reports
        Route::post('/export', [AttendanceController::class, 'export']);
        Route::get('/student/{student}/report', [AttendanceController::class, 'studentReport']);
        Route::get('/class/{class}/report', [AttendanceController::class, 'classReport']);

        Route::get('/{attendance}', [AttendanceController::class, 'show']);
        Route::put('/{attendance}', [AttendanceController::class, 'update']);
        Route::delete('/{attendance}', [AttendanceController::class, 'destroy']);
    });

    // Reports API
    Route::prefix('reports')->group(function () {
        Route::get('/revenue', [ReportController::class, 'revenue']);
        Route::get('/students', [ReportController::class, 'students']);
        Route::get('/enrollments', [ReportController::class, 'enrollments']);
        Route::get('/payments', [ReportController::class, 'payments']);
        Route::get('/attendance', [ReportController::class, 'attendance']);
        Route::get('/course-completion', [ReportController::class, 'courseCompletion']);
        Route::get('/revenue-trend', [ReportController::class, 'revenueTrend']);
        Route::get('/enrollment-trend', [ReportController::class, 'enrollmentTrend']);
        Route::get('/waiting-list-stats', [ReportController::class, 'waitingListStats']);
        Route::get('/overdue-payments', [ReportController::class, 'overduePayments']);
        Route::get('/course-distribution', [ReportController::class, 'courseDistribution']);
        Route::get('/student-demographics', [ReportController::class, 'studentDemographics']);
        Route::get('/top-courses', [ReportController::class, 'topCourses']);
    });

    // Search API
    Route::prefix('search')->group(function () {
        Route::get('/autocomplete', [SearchController::class, 'autocomplete']);
        Route::get('/details', [SearchController::class, 'details']);
        Route::get('/student/{student}/history', [SearchController::class, 'studentHistory']);
    });

    // Provinces and Ethnicities
    Route::get('/provinces', function () {
        return \App\Models\Province::all();
    });

    Route::get('/provinces/search', function (Request $request) {
        return \App\Models\Province::where('name', 'like', '%' . $request->term . '%')->get();
    });

    Route::get('/provinces/region/{region}', function ($region) {
        return \App\Models\Province::where('region', $region)->get();
    });

    Route::get('/provinces/{province}', function ($province) {
        return \App\Models\Province::findOrFail($province);
    });

    Route::get('/ethnicities', function () {
        return \App\Models\Ethnicity::all();
    });

    Route::get('/ethnicities/{ethnicity}', function ($ethnicity) {
        return \App\Models\Ethnicity::findOrFail($ethnicity);
    });

    // Student Sources
    Route::get('/student-sources', function () {
        return \App\Enums\StudentSource::getSelectOptions();
    });

    // Learning Paths API
    Route::prefix('learning-paths')->group(function () {
        Route::get('/incomplete-courses', [LearningPathController::class, 'getCoursesWithIncompletePaths']);
        Route::get('/course/{courseId}/progress', [LearningPathController::class, 'getCoursePathProgress']);
        Route::put('/course/{courseId}/step/{stepId}', [LearningPathController::class, 'updatePathStepCompletion']);
        Route::get('/statistics', [LearningPathController::class, 'getPathStatistics']);
        Route::post('/course/{courseId}/complete', [LearningPathController::class, 'completeCoursePathProgress']);
        Route::post('/course/{courseId}/reset', [LearningPathController::class, 'resetCoursePathProgress']);
    });
});

// Public routes (không cần authentication) - SePay webhook
Route::prefix('api')->group(function () {
    Route::post('/sepay/webhook', [\App\Http\Controllers\Api\SePayWebhookController::class, 'handleWebhook']);
    Route::post('/sepay/test', [\App\Http\Controllers\Api\SePayWebhookController::class, 'test']);
});