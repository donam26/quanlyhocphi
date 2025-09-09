<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\CourseItem;
use App\Services\PaymentService;
use App\Services\PaymentMetricsService;
use App\Services\PaymentReconciliationService;
use App\Enums\EnrollmentStatus;
use App\Rules\ValidPaymentAmount;
use App\Rules\WhitelistedDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * API: Danh sách thanh toán
     */
    public function index(Request $request)
    {
        try {
            $query = Payment::with(['enrollment.student', 'enrollment.courseItem']);

            // Search functionality
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->whereHas('enrollment.student', function($q) use ($searchTerm) {
                    $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"])
                      ->orWhere('phone', 'like', "%{$searchTerm}%");
                })->orWhereHas('enrollment.courseItem', function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%");
                })->orWhere('transaction_reference', 'like', "%{$searchTerm}%");
            }

            // Filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->has('start_date')) {
                $query->whereDate('payment_date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('payment_date', '<=', $request->end_date);
            }

            // Pagination parameters
            $perPage = $request->input('per_page', 15);

            $payments = $query->orderBy('payment_date', 'desc')->paginate($perPage);

            return response()->json($payments);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Tạo thanh toán mới
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'enrollment_id' => 'required|exists:enrollments,id',
                'amount' => 'required|numeric|min:0',
                'payment_date' => 'required|date',
                'payment_method' => 'required|in:cash,bank_transfer,card,qr_code',
                'transaction_reference' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            $payment = $this->paymentService->createPayment($validated);

            return response()->json([
                'success' => true,
                'data' => $payment->load(['enrollment.student', 'enrollment.courseItem']),
                'message' => 'Thanh toán đã được tạo thành công'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Chi tiết thanh toán
     */
    public function show(Payment $payment)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $payment->load(['enrollment.student', 'enrollment.courseItem'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy chi tiết thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Cập nhật thanh toán
     */
    public function update(Request $request, Payment $payment)
    {
        try {
            $validated = $request->validate([
                'amount' => 'sometimes|numeric|min:0',
                'payment_date' => 'sometimes|date',
                'payment_method' => 'sometimes|in:cash,bank_transfer,card,qr_code',
                'transaction_reference' => 'nullable|string',
                'status' => 'sometimes|in:pending,confirmed,cancelled',
                'notes' => 'nullable|string'
            ]);

            $payment->update($validated);

            return response()->json([
                'success' => true,
                'data' => $payment->load(['enrollment.student', 'enrollment.courseItem']),
                'message' => 'Thanh toán đã được cập nhật thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi cập nhật thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Xóa thanh toán
     */
    public function destroy(Payment $payment)
    {
        try {
            $payment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Thanh toán đã được xóa thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xóa thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Xác nhận thanh toán
     */
    public function confirm(Payment $payment)
    {
        try {
            $payment->update(['status' => 'confirmed']);

            return response()->json([
                'success' => true,
                'data' => $payment->load(['enrollment.student', 'enrollment.courseItem']),
                'message' => 'Thanh toán đã được xác nhận'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xác nhận thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Hủy thanh toán
     */
    public function cancel(Payment $payment)
    {
        try {
            $payment->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'data' => $payment->load(['enrollment.student', 'enrollment.courseItem']),
                'message' => 'Thanh toán đã được hủy'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi hủy thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Hoàn tiền
     */
    public function refund(Payment $payment)
    {
        try {
            // Logic hoàn tiền tùy thuộc vào payment gateway
            $payment->update([
                'status' => 'cancelled',
                'notes' => ($payment->notes ?? '') . ' [REFUNDED: ' . now() . ']'
            ]);

            return response()->json([
                'success' => true,
                'data' => $payment->load(['enrollment.student', 'enrollment.courseItem']),
                'message' => 'Thanh toán đã được hoàn tiền'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi hoàn tiền: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Thanh toán theo học viên
     */
    public function byStudent(Student $student)
    {
        try {
            $payments = Payment::whereHas('enrollment', function ($query) use ($student) {
                $query->where('student_id', $student->id);
            })->with(['enrollment.courseItem'])->orderBy('payment_date', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $payments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thanh toán theo học viên: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Thanh toán theo khóa học
     */
    public function byCourse(CourseItem $courseItem)
    {
        try {
            $payments = Payment::whereHas('enrollment', function ($query) use ($courseItem) {
                $query->where('course_item_id', $courseItem->id);
            })->with(['enrollment.student'])->orderBy('payment_date', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $payments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thanh toán theo khóa học: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lịch sử thanh toán của ghi danh
     */
    public function history(Enrollment $enrollment)
    {
        try {
            $payments = $enrollment->payments()->orderBy('payment_date', 'desc')->get();

            $totalPaid = $payments->where('status', 'confirmed')->sum('amount');
            $remaining = $enrollment->final_fee - $totalPaid;

            return response()->json([
                'success' => true,
                'data' => [
                    'payments' => $payments,
                    'summary' => [
                        'total_fee' => $enrollment->final_fee,
                        'total_paid' => $totalPaid,
                        'remaining' => $remaining,
                        'is_fully_paid' => $remaining <= 0
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy lịch sử thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Thao tác hàng loạt
     */
    public function bulkAction(Request $request)
    {
        try {
            $validated = $request->validate([
                'action' => 'required|in:confirm,cancel,delete',
                'payment_ids' => 'required|array',
                'payment_ids.*' => 'exists:payments,id'
            ]);

            $payments = Payment::whereIn('id', $validated['payment_ids'])->get();

            foreach ($payments as $payment) {
                switch ($validated['action']) {
                    case 'confirm':
                        $payment->update(['status' => 'confirmed']);
                        break;
                    case 'cancel':
                        $payment->update(['status' => 'cancelled']);
                        break;
                    case 'delete':
                        $payment->delete();
                        break;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Thao tác hàng loạt đã được thực hiện thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi thực hiện thao tác hàng loạt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Gửi nhắc nhở thanh toán
     */
    public function sendReminder(Request $request)
    {
        try {
            $validated = $request->validate([
                'enrollment_ids' => 'required|array',
                'enrollment_ids.*' => 'exists:enrollments,id',
                'message' => 'nullable|string'
            ]);

            $enrollments = Enrollment::with(['student', 'courseItem'])
                ->whereIn('id', $validated['enrollment_ids'])
                ->get();

            $successCount = 0;
            $failedCount = 0;

            foreach ($enrollments as $enrollment) {
                try {
                    // Tính số tiền còn thiếu
                    $totalPaid = $enrollment->getTotalPaidAmount();
                    $remaining = $enrollment->final_fee - $totalPaid;

                    if ($remaining > 0) {
                        $this->paymentService->sendPaymentReminder($enrollment);
                        $successCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Đã gửi nhắc nhở cho {$successCount} học viên. Thất bại: {$failedCount}",
                'data' => [
                    'success_count' => $successCount,
                    'failed_count' => $failedCount
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi gửi nhắc nhở: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Tạo biên lai hàng loạt
     */
    public function bulkReceipt(Request $request)
    {
        try {
            $validated = $request->validate([
                'payment_ids' => 'required|array',
                'payment_ids.*' => 'exists:payments,id'
            ]);

            $payments = Payment::whereIn('id', $validated['payment_ids'])
                ->with(['enrollment.student', 'enrollment.courseItem'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => $payments,
                'message' => 'Biên lai hàng loạt đã được tạo'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo biên lai hàng loạt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Xuất dữ liệu thanh toán
     */
    public function export(Request $request)
    {
        try {
            $request->validate([
                'columns' => 'array',
                'columns.*' => 'string',
                'search' => 'nullable|string',
                'status' => 'nullable|in:pending,confirmed,cancelled',
                'payment_method' => 'nullable|in:cash,bank_transfer,card,qr_code,sepay',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date'
            ]);

            $columns = $request->input('columns', [
                'student_name', 'student_phone', 'course_name',
                'payment_date', 'amount', 'payment_method', 'status'
            ]);

            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'payment_method' => $request->input('payment_method'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date')
            ];

            $fileName = 'danh_sach_thanh_toan_' . date('Y_m_d_H_i_s') . '.xlsx';

            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\PaymentExport($columns, $filters),
                $fileName
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xuất dữ liệu thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Tạo biên lai
     */
    public function generateReceipt(Payment $payment)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $payment->load(['enrollment.student', 'enrollment.courseItem']),
                'message' => 'Biên lai đã được tạo'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo biên lai: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Báo cáo thanh toán hàng tháng
     */
    public function monthlyReport(Request $request)
    {
        try {
            $year = $request->input('year', date('Y'));
            $month = $request->input('month', date('m'));

            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $payments = Payment::where('status', 'confirmed')
                ->whereBetween('payment_date', [$startDate, $endDate])
                ->with(['enrollment.student', 'enrollment.courseItem'])
                ->get();

            $summary = [
                'total_amount' => $payments->sum('amount'),
                'total_payments' => $payments->count(),
                'by_method' => $payments->groupBy('payment_method')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'amount' => $group->sum('amount')
                    ];
                }),
                'by_course' => $payments->groupBy('enrollment.courseItem.name')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'amount' => $group->sum('amount')
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'payments' => $payments,
                    'summary' => $summary,
                    'period' => [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d')
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo hàng tháng: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy danh sách batch của user
     */
    public function getUserBatches(Request $request)
    {
        try {
            // Placeholder - sẽ implement sau khi có bảng reminder_batches
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Danh sách batch trống'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách batch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Thống kê batch
     */
    public function getBatchStats($batchId)
    {
        try {
            // Placeholder - sẽ implement sau khi có bảng reminder_batches
            return response()->json([
                'success' => true,
                'data' => [
                    'batch_id' => $batchId,
                    'total_sent' => 0,
                    'success_count' => 0,
                    'failed_count' => 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê batch: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy danh sách ghi danh chưa thanh toán đủ
     */
    public function getUnpaidEnrollments(Request $request)
    {
        try {
            $query = Enrollment::with(['student', 'courseItem', 'payments'])
                ->whereIn('status', [EnrollmentStatus::ACTIVE])
                ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") < enrollments.final_fee');

            // Filters
            if ($request->has('course_id')) {
                $query->where('course_item_id', $request->course_id);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('student', function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->has('min_amount')) {
                $minAmount = $request->min_amount;
                $query->whereRaw('(enrollments.final_fee - (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed")) >= ?', [$minAmount]);
            }

            $enrollments = $query->orderBy('enrollment_date', 'desc')
                ->paginate($request->input('per_page', 15));

            // Transform data để thêm thông tin thanh toán
            $enrollments->getCollection()->transform(function ($enrollment) {
                $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                $remaining = $enrollment->final_fee - $totalPaid;

                return [
                    'id' => $enrollment->id,
                    'student' => [
                        'id' => $enrollment->student->id,
                        'full_name' => $enrollment->student->full_name,
                        'phone' => $enrollment->student->phone,
                        'email' => $enrollment->student->email,
                    ],
                    'course_item' => [
                        'id' => $enrollment->courseItem->id,
                        'name' => $enrollment->courseItem->name,
                        'path' => $enrollment->courseItem->path ?? $enrollment->courseItem->name,
                    ],
                    'enrollment_date' => $enrollment->enrollment_date->format('d/m/Y'),
                    'final_fee' => $enrollment->final_fee,
                    'total_paid' => $totalPaid,
                    'remaining_amount' => $remaining,
                    'payment_percentage' => $enrollment->final_fee > 0 ? round(($totalPaid / $enrollment->final_fee) * 100, 2) : 0,
                    'status' => $enrollment->status,
                    'last_payment_date' => $enrollment->payments->where('status', 'confirmed')->sortByDesc('payment_date')->first()?->payment_date?->format('d/m/Y'),
                    'days_since_enrollment' => $enrollment->enrollment_date->diffInDays(now()),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $enrollments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách chưa thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Thống kê tổng quan thanh toán
     */
    public function getPaymentOverview(Request $request)
    {
        try {
            // Tổng số ghi danh đang hoạt động
            $totalActiveEnrollments = Enrollment::where('status', EnrollmentStatus::ACTIVE)->count();

            // Tổng học phí cần thu
            $totalFees = Enrollment::where('status', EnrollmentStatus::ACTIVE)->sum('final_fee');

            // Tổng đã thu
            $totalPaid = Payment::where('status', 'confirmed')
                ->whereHas('enrollment', function($q) {
                    $q->where('status', EnrollmentStatus::ACTIVE);
                })->sum('amount');

            // Số lượng chưa thanh toán đủ
            $unpaidCount = Enrollment::where('status', EnrollmentStatus::ACTIVE)
                ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") < enrollments.final_fee')
                ->count();

            // Số lượng đã thanh toán đủ
            $fullyPaidCount = $totalActiveEnrollments - $unpaidCount;

            // Tổng còn thiếu
            $totalRemaining = $totalFees - $totalPaid;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_active_enrollments' => $totalActiveEnrollments,
                    'total_fees' => $totalFees,
                    'total_paid' => $totalPaid,
                    'total_remaining' => $totalRemaining,
                    'unpaid_count' => $unpaidCount,
                    'fully_paid_count' => $fullyPaidCount,
                    'payment_percentage' => $totalFees > 0 ? round(($totalPaid / $totalFees) * 100, 2) : 0,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê tổng quan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Khởi tạo thanh toán SePay
     */
    public function initiateSePayPayment(Request $request)
    {
        try {
            // Get enrollment first for validation
            $enrollment = Enrollment::with(['student', 'courseItem'])->findOrFail($request->input('enrollment_id'));

            $validated = $request->validate([
                'enrollment_id' => 'required|exists:enrollments,id',
                'amount' => [
                    'required',
                    'numeric',
                    new ValidPaymentAmount($enrollment)
                ],
                'redirect_url' => [
                    'nullable',
                    'url',
                    new WhitelistedDomain()
                ]
            ]);

            // Use database transaction for consistency
            return DB::transaction(function () use ($validated, $enrollment) {
                // Lock enrollment for update to prevent race conditions
                $enrollment = Enrollment::with(['student', 'courseItem'])
                    ->lockForUpdate()
                    ->findOrFail($validated['enrollment_id']);

                // Double-check remaining amount after lock
                $remaining = $enrollment->getRemainingAmount();

                if ($remaining <= 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Học viên đã thanh toán đủ học phí'
                    ], 400);
                }

                if ($validated['amount'] > $remaining) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Số tiền thanh toán không được vượt quá số tiền còn thiếu: ' . number_format($remaining, 0, ',', '.') . ' VND'
                    ], 400);
                }

                // Generate idempotency key
                $idempotencyKey = 'sepay_' . $enrollment->id . '_' . time() . '_' . rand(1000, 9999);

                // Tạo payment record
                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'amount' => $validated['amount'],
                    'payment_method' => 'sepay',
                    'payment_date' => now(),
                    'status' => 'pending',
                    'transaction_reference' => $idempotencyKey,
                    'notes' => 'Thanh toán qua SePay - Đang chờ xác nhận'
                ]);

                // Tạo QR code thông qua SePay service
                $sePayService = app(\App\Services\SePayService::class);
                $qrResult = $sePayService->generateQR($payment);

                if (!$qrResult['success']) {
                    throw new \Exception('Không thể tạo mã QR thanh toán');
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'payment_id' => $payment->id,
                        'qr_data' => $qrResult['data'],
                        'enrollment' => [
                            'id' => $enrollment->id,
                            'student_name' => $enrollment->student->full_name,
                            'course_name' => $enrollment->courseItem->name,
                            'final_fee' => $enrollment->final_fee,
                            'total_paid' => $enrollment->getTotalPaidAmount(),
                            'remaining_amount' => $remaining,
                        ]
                    ],
                    'message' => 'Đã tạo mã QR thanh toán thành công'
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi khởi tạo thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Kiểm tra trạng thái thanh toán SePay
     */
    public function checkSePayPaymentStatus($paymentId)
    {
        try {
            $payment = Payment::with(['enrollment.student', 'enrollment.courseItem'])->findOrFail($paymentId);

            if ($payment->payment_method !== 'sepay') {
                return response()->json([
                    'success' => false,
                    'message' => 'Đây không phải thanh toán qua SePay'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_id' => $payment->id,
                    'status' => $payment->status,
                    'amount' => $payment->amount,
                    'payment_date' => $payment->payment_date->format('d/m/Y H:i:s'),
                    'enrollment' => [
                        'id' => $payment->enrollment->id,
                        'student_name' => $payment->enrollment->student->full_name,
                        'course_name' => $payment->enrollment->courseItem->name,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi kiểm tra trạng thái thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Thống kê thanh toán
     */
    public function stats(Request $request)
    {
        try {
            // Tổng số thanh toán
            $totalPayments = Payment::count();

            // Tổng doanh thu
            $totalRevenue = Payment::where('status', 'confirmed')->sum('amount');

            // Thanh toán hôm nay
            $todayPayments = Payment::whereDate('payment_date', today())->count();
            $todayRevenue = Payment::whereDate('payment_date', today())
                ->where('status', 'confirmed')
                ->sum('amount');

            // Thanh toán tháng này
            $thisMonthPayments = Payment::whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->count();
            $thisMonthRevenue = Payment::whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->where('status', 'confirmed')
                ->sum('amount');

            // Thanh toán chờ xác nhận
            $pendingPayments = Payment::where('status', 'pending')->count();
            $pendingAmount = Payment::where('status', 'pending')->sum('amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_payments' => $totalPayments,
                    'total_revenue' => $totalRevenue,
                    'today_payments' => $todayPayments,
                    'today_revenue' => $todayRevenue,
                    'this_month_payments' => $thisMonthPayments,
                    'this_month_revenue' => $thisMonthRevenue,
                    'pending_payments' => $pendingPayments,
                    'pending_amount' => $pendingAmount,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy thống kê thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy danh sách khóa học có học viên chưa thanh toán đủ
     */
    public function getCoursesWithUnpaidStudents(Request $request)
    {
        try {
            $query = CourseItem::with(['enrollments.student', 'enrollments.payments'])
                ->whereHas('enrollments', function($q) {
                    $q->whereIn('status', [EnrollmentStatus::ACTIVE])
                      ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.enrollment_id = enrollments.id AND payments.status = "confirmed") < enrollments.final_fee');
                });

            // Filters
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('path', 'like', "%{$search}%");
                });
            }

            // Pagination parameters
            $page = $request->input('page', 1);
            $limit = $request->input('per_page', $request->input('limit', 15));

            // Get all courses first, then transform and filter
            $allCourses = $query->get();

            // Transform data để thêm thống kê
            $coursesData = $allCourses->map(function ($course) {
                $unpaidEnrollments = $course->enrollments->filter(function($enrollment) {
                    if ($enrollment->status !== EnrollmentStatus::ACTIVE) return false;

                    $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                    return $totalPaid < $enrollment->final_fee;
                });

                $totalUnpaidStudents = $unpaidEnrollments->count();
                $totalDebt = $unpaidEnrollments->sum(function($enrollment) {
                    $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                    return $enrollment->final_fee - $totalPaid;
                });

                $totalStudents = $course->enrollments->where('status', EnrollmentStatus::ACTIVE)->count();

                return [
                    'id' => $course->id,
                    'name' => $course->name,
                    'path' => $course->path ?? $course->name,
                    'total_students' => $totalStudents,
                    'unpaid_students_count' => $totalUnpaidStudents,
                    'total_debt' => $totalDebt,
                    'payment_percentage' => $totalStudents > 0 ? round((($totalStudents - $totalUnpaidStudents) / $totalStudents) * 100, 2) : 0,
                ];
            })->filter(function($course) {
                return $course['unpaid_students_count'] > 0;
            })->values();

            // Manual pagination on filtered data
            $total = $coursesData->count();
            $offset = ($page - 1) * $limit;
            $paginatedData = $coursesData->slice($offset, $limit)->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $paginatedData,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'totalPages' => ceil($total / $limit)
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách khóa học: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy danh sách học viên chưa thanh toán đủ theo khóa học
     */
    public function getCourseUnpaidStudents(Request $request, $courseId)
    {
        try {
            $course = CourseItem::with(['enrollments.student', 'enrollments.payments'])
                ->findOrFail($courseId);

            $unpaidEnrollments = $course->enrollments->filter(function($enrollment) {
                if ($enrollment->status !== EnrollmentStatus::ACTIVE) return false;

                $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                return $totalPaid < $enrollment->final_fee;
            });

            // Transform data
            $studentsData = $unpaidEnrollments->map(function ($enrollment) {
                $totalPaid = $enrollment->payments->where('status', 'confirmed')->sum('amount');
                $remaining = $enrollment->final_fee - $totalPaid;

                return [
                    'id' => $enrollment->id,
                    'student' => [
                        'id' => $enrollment->student->id,
                        'full_name' => $enrollment->student->full_name,
                        'phone' => $enrollment->student->phone,
                        'email' => $enrollment->student->email,
                    ],
                    'enrollment_date' => $enrollment->enrollment_date->format('d/m/Y'),
                    'final_fee' => $enrollment->final_fee,
                    'total_paid' => $totalPaid,
                    'remaining_amount' => $remaining,
                    'payment_percentage' => $enrollment->final_fee > 0 ? round(($totalPaid / $enrollment->final_fee) * 100, 2) : 0,
                    'status' => $enrollment->status,
                    'last_payment_date' => $enrollment->payments->where('status', 'confirmed')->sortByDesc('payment_date')->first()?->payment_date?->format('d/m/Y'),
                    'days_since_enrollment' => $enrollment->enrollment_date->diffInDays(now()),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => [
                        'id' => $course->id,
                        'name' => $course->name,
                        'path' => $course->path ?? $course->name,
                    ],
                    'students' => $studentsData
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy danh sách học viên: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Gửi nhắc nhở hàng loạt
     */
    public function sendBulkReminders(Request $request)
    {
        try {
            $validated = $request->validate([
                'enrollment_ids' => 'required|array|min:1',
                'enrollment_ids.*' => 'exists:enrollments,id',
                'batch_name' => 'nullable|string|max:255'
            ]);

            $reminderService = app(\App\Services\ReminderService::class);
            $result = $reminderService->sendBulkReminders(
                $validated['enrollment_ids'],
                $validated['batch_name'] ?? null
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi gửi nhắc nhở: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Gửi nhắc nhở cho toàn khóa học
     */
    public function sendCourseReminders(Request $request)
    {
        try {
            $validated = $request->validate([
                'course_id' => 'required|exists:course_items,id',
                'enrollment_ids' => 'nullable|array',
                'enrollment_ids.*' => 'exists:enrollments,id'
            ]);

            $reminderService = app(\App\Services\ReminderService::class);
            $result = $reminderService->sendCourseReminders(
                $validated['course_id'],
                $validated['enrollment_ids'] ?? []
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi gửi nhắc nhở khóa học: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Lấy payment metrics cho dashboard
     */
    public function getPaymentMetrics(Request $request)
    {
        try {
            $metrics = PaymentMetricsService::getDashboardMetrics();

            return response()->json([
                'success' => true,
                'data' => $metrics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi lấy payment metrics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Confirm payment manually với audit logging
     */
    public function confirmPayment(Request $request, Payment $payment)
    {
        try {
            if ($payment->status === 'confirmed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Thanh toán đã được xác nhận trước đó'
                ], 400);
            }

            $validated = $request->validate([
                'notes' => 'nullable|string|max:1000'
            ]);

            DB::transaction(function () use ($payment, $validated) {
                $payment->confirm(auth()->id(), [
                    'manual_confirmation' => true,
                    'notes' => $validated['notes'] ?? null
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Đã xác nhận thanh toán thành công',
                'data' => $payment->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi xác nhận thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Cancel payment manually với audit logging
     */
    public function cancelPayment(Request $request, Payment $payment)
    {
        try {
            if ($payment->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Thanh toán đã được hủy trước đó'
                ], 400);
            }

            $validated = $request->validate([
                'reason' => 'required|string|max:500'
            ]);

            DB::transaction(function () use ($payment, $validated) {
                $payment->cancel($validated['reason'], auth()->id());
            });

            return response()->json([
                'success' => true,
                'message' => 'Đã hủy thanh toán thành công',
                'data' => $payment->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi hủy thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Reconcile payments với bank statements
     */
    public function reconcilePayments(Request $request)
    {
        try {
            $validated = $request->validate([
                'date' => 'required|date',
                'bank_statements' => 'required|array',
                'bank_statements.*.amount' => 'required|numeric',
                'bank_statements.*.date' => 'required|date',
                'bank_statements.*.reference' => 'nullable|string'
            ]);

            $reconciliationService = app(PaymentReconciliationService::class);
            $results = $reconciliationService->reconcilePayments(
                $validated['bank_statements'],
                Carbon::parse($validated['date'])
            );

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Đối soát thanh toán hoàn tất'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi đối soát thanh toán: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Auto reconcile SePay transactions
     */
    public function autoReconcileSePayTransactions(Request $request)
    {
        try {
            $validated = $request->validate([
                'date' => 'nullable|date'
            ]);

            $reconciliationService = app(PaymentReconciliationService::class);
            $results = $reconciliationService->autoReconcileSePayTransactions(
                $validated['date'] ? Carbon::parse($validated['date']) : null
            );

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Tự động đối soát SePay hoàn tất'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tự động đối soát SePay: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Generate reconciliation report
     */
    public function getReconciliationReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date'
            ]);

            $reconciliationService = app(PaymentReconciliationService::class);
            $report = $reconciliationService->generateReconciliationReport(
                Carbon::parse($validated['start_date']),
                Carbon::parse($validated['end_date'])
            );

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => 'Báo cáo đối soát đã được tạo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo báo cáo đối soát: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Detect suspicious payment patterns
     */
    public function detectSuspiciousPatterns(Request $request)
    {
        try {
            $validated = $request->validate([
                'date' => 'nullable|date'
            ]);

            $reconciliationService = app(PaymentReconciliationService::class);
            $patterns = $reconciliationService->detectSuspiciousPatterns(
                $validated['date'] ? Carbon::parse($validated['date']) : null
            );

            return response()->json([
                'success' => true,
                'data' => $patterns,
                'message' => 'Phân tích pattern hoàn tất'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi phân tích pattern: ' . $e->getMessage()
            ], 500);
        }
    }
}
