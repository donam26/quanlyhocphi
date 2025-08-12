@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Lịch sử học viên</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('search.index') }}">Tìm kiếm</a></li>
        <li class="breadcrumb-item active">Lịch sử học viên</li>
    </ol>
    
    <!-- Thông tin học viên -->
    <div class="card mb-4">
       
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <th style="width: 150px;">Họ và tên:</th>
                                <td>{{ $student->full_name }}</td>
                            </tr>
                            <tr>
                                <th>Số điện thoại:</th>
                                <td>{{ $student->phone }}</td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $student->email ?? 'Không có' }}</td>
                            </tr>
                            <tr>
                                <th>Địa chỉ:</th>
                                <td>{{ $student->address ?? 'Không có' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <th style="width: 150px;">Giới tính:</th>
                                <td>
                                    @if($student->gender == 'male')
                                        Nam
                                    @elseif($student->gender == 'female')
                                        Nữ
                                    @else
                                        Khác
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Ngày sinh:</th>
                                <td>{{ $student->formatted_date_of_birth ?: 'Không có' }}</td>
                            </tr>
                            <tr>
                                <th>Nơi công tác:</th>
                                <td>{{ $student->current_workplace ?? 'Không có' }}</td>
                            </tr>
                            <tr>
                                <th>Kinh nghiệm:</th>
                                <td>{{ $student->accounting_experience_years ?? '0' }} năm</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Thông tin tổng quan -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Tổng khóa học</div>
                            <div class="h3 mb-0">{{ $student->enrollments->count() }}</div>
                        </div>
                        <div>
                            <i class="fas fa-graduation-cap fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Tổng thanh toán</div>
                            <div class="h3 mb-0">{{ number_format($student->getTotalPaidAmount(), 0, ',', '.') }} đ</div>
                        </div>
                        <div>
                            <i class="fas fa-money-bill fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Tổng học phí</div>
                            <div class="h3 mb-0">{{ number_format($student->getTotalFeeAmount(), 0, ',', '.') }} đ</div>
                        </div>
                        <div>
                            <i class="fas fa-file-invoice fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small">Còn thiếu</div>
                            <div class="h3 mb-0">{{ number_format($student->getRemainingAmount(), 0, ',', '.') }} đ</div>
                        </div>
                        <div>
                            <i class="fas fa-exclamation-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabs điều hướng -->
    <div class="card mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="studentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="enrollments-tab" data-bs-toggle="tab" data-bs-target="#enrollments" type="button" role="tab" aria-controls="enrollments" aria-selected="true">
                        <i class="fas fa-graduation-cap me-1"></i> Khóa học đã ghi danh
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab" aria-controls="payments" aria-selected="false">
                        <i class="fas fa-money-bill me-1"></i> Lịch sử thanh toán
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="attendances-tab" data-bs-toggle="tab" data-bs-target="#attendances" type="button" role="tab" aria-controls="attendances" aria-selected="false">
                        <i class="fas fa-calendar-check me-1"></i> Lịch sử điểm danh
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="waiting-tab" data-bs-toggle="tab" data-bs-target="#waiting" type="button" role="tab" aria-controls="waiting" aria-selected="false">
                        <i class="fas fa-clock me-1"></i> Danh sách chờ
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="studentTabContent">
                <!-- Tab khóa học đã ghi danh -->
                <div class="tab-pane fade show active" id="enrollments" role="tabpanel" aria-labelledby="enrollments-tab">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-primary">
                                <tr>
                                    <th>Khóa học</th>
                                    <th>Ngày ghi danh</th>
                                    <th>Học phí</th>
                                    <th>Đã nộp</th>
                                    <th>Còn thiếu</th>
                                    <th>Trạng thái</th>
                                    <th>Thanh toán</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($student->enrollments as $enrollment)
                                    @php
                                        $totalPaid = $enrollment->getTotalPaidAmount();
                                        $remainingAmount = $enrollment->getRemainingAmount();
                                        $paymentStatus = $enrollment->isFullyPaid() ? 'Đã thanh toán đủ' : 'Còn thiếu';
                                    @endphp
                                    <tr>
                                        <td>{{ $enrollment->courseItem->name ?? 'N/A' }}</td>
                                        <td>{{ $enrollment->formatted_enrollment_date }}</td>
                                        <td class="text-end">{{ number_format($enrollment->final_fee, 0, ',', '.') }} đ</td>
                                        <td class="text-end">{{ number_format($totalPaid, 0, ',', '.') }} đ</td>
                                        <td class="text-end">{{ number_format($remainingAmount, 0, ',', '.') }} đ</td>
                                        <td>
                                            {!! $enrollment->status->badge() !!}
                                        </td>
                                        <td>
                                            @if($paymentStatus == 'Đã thanh toán đủ')
                                                <span class="badge bg-success">Đã thanh toán đủ</span>
                                            @else
                                                <span class="badge bg-danger">Còn thiếu</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('payments.quick', $enrollment->id) }}" class="btn btn-primary" title="Thanh toán nhanh">
                                                    <i class="fas fa-money-bill"></i>
                                                </a>
                                                <a href="{{ route('enrollments.show', $enrollment->id) }}" class="btn btn-info" title="Xem chi tiết ghi danh">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Không có dữ liệu ghi danh</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tab lịch sử thanh toán -->
                <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-success">
                                <tr>
                                    <th>#</th>
                                    <th>Ngày thanh toán</th>
                                    <th>Khóa học</th>
                                    <th>Số tiền</th>
                                    <th>Phương thức</th>
                                    <th>Trạng thái</th>
                                    <th>Ghi chú</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $index => $payment)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $payment->formatted_payment_date }}</td>
                                        <td>{{ $payment->enrollment->courseItem->name ?? 'N/A' }}</td>
                                        <td class="text-end">{{ number_format($payment->amount, 0, ',', '.') }} đ</td>
                                        <td>
                                            @if($payment->payment_method == 'cash')
                                                <span class="badge bg-success">Tiền mặt</span>
                                            @elseif($payment->payment_method == 'bank_transfer')
                                                <span class="badge bg-primary">Chuyển khoản</span>
                                            @elseif($payment->payment_method == 'sepay')
                                                <span class="badge bg-info">SEPAY</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $payment->payment_method }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($payment->status == 'confirmed')
                                                <span class="badge bg-success">Đã xác nhận</span>
                                            @elseif($payment->status == 'pending')
                                                <span class="badge bg-warning text-dark">Chờ xác nhận</span>
                                            @elseif($payment->status == 'rejected')
                                                <span class="badge bg-danger">Đã từ chối</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $payment->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $payment->notes ?? 'Không có ghi chú' }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('payments.show', $payment->id) }}" class="btn btn-info" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($payment->status == 'confirmed')
                                                    <a href="{{ route('payments.receipt', $payment->id) }}" class="btn btn-success" title="In biên lai">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Không có dữ liệu thanh toán</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tab lịch sử điểm danh -->
                <div class="tab-pane fade" id="attendances" role="tabpanel" aria-labelledby="attendances-tab">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-info">
                                <tr>
                                    <th>#</th>
                                    <th>Ngày điểm danh</th>
                                    <th>Khóa học</th>
                                    <th>Trạng thái</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendances as $index => $attendance)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $attendance->attendance_date->format(config('app.date_format', 'd/m/Y')) }}</td>
                                        <td>{{ $attendance->courseItem->name ?? 'N/A' }}</td>
                                        <td>
                                            @if($attendance->status == 'present')
                                                <span class="badge bg-success">Có mặt</span>
                                            @elseif($attendance->status == 'absent')
                                                <span class="badge bg-danger">Vắng mặt</span>
                                            @elseif($attendance->status == 'late')
                                                <span class="badge bg-warning text-dark">Đi muộn</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $attendance->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $attendance->notes ?? 'Không có ghi chú' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Không có dữ liệu điểm danh</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Tab danh sách chờ -->
                <div class="tab-pane fade" id="waiting" role="tabpanel" aria-labelledby="waiting-tab">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="table-warning">
                                <tr>
                                    <th>#</th>
                                    <th>Khóa học</th>
                                    <th>Ngày đăng ký</th>
                                    <th>Trạng thái</th>
                                    <th>Ghi chú</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($student->waitingLists as $index => $waitingList)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $waitingList->courseItem->name ?? 'N/A' }}</td>
                                        <td>{{ $waitingList->formatted_request_date ?: $waitingList->formatted_enrollment_date }}</td>
                                        <td>
                                            <span class="badge bg-warning text-dark">Đang chờ</span>
                                        </td>
                                        <td>{{ $waitingList->notes ?? 'Không có ghi chú' }}</td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('enrollments.confirm-waiting', $waitingList->id) }}" class="btn btn-success" title="Chuyển sang ghi danh">
                                                    <i class="fas fa-user-plus"></i>
                                                </a>
                                                <a href="{{ route('enrollments.show', $waitingList->id) }}" class="btn btn-info" title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">Không có dữ liệu danh sách chờ</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}


@push('scripts')
<script>
// Page ready với Unified Modal System
document.addEventListener('app:ready', function() {
    console.log('Page ready with Unified Modal System');
});
</script>
@endpush

@endsection 