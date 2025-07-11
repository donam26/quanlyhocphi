@extends('layouts.app')

@section('page-title', 'Chi tiết học viên: ' . $student->full_name)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('students.index') }}">Học viên</a></li>
<li class="breadcrumb-item active">{{ $student->full_name }}</li>
@endsection

@section('content')
<div class="row">
    <!-- Thông tin học viên -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user me-2"></i>Thông tin cá nhân
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="avatar-lg bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center">
                        <span style="font-size: 2rem;">{{ substr($student->full_name, 0, 1) }}</span>
                    </div>
                    <h5 class="mt-2 mb-0">{{ $student->full_name }}</h5>
                    <p class="text-muted mb-0">{{ $student->phone }}</p>
                </div>

                <div class="info-list">
                    @if($student->birth_date)
                    <div class="info-item">
                        <i class="fas fa-birthday-cake text-muted"></i>
                        <span class="label">Ngày sinh:</span>
                        <span class="value">{{ $student->birth_date->format('d/m/Y') }}</span>
                    </div>
                    @endif

                    @if($student->birth_place)
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt text-muted"></i>
                        <span class="label">Nơi sinh:</span>
                        <span class="value">{{ $student->birth_place }}</span>
                    </div>
                    @endif

                    @if($student->id_number)
                    <div class="info-item">
                        <i class="fas fa-id-card text-muted"></i>
                        <span class="label">CCCD/CMND:</span>
                        <span class="value">{{ $student->id_number }}</span>
                    </div>
                    @endif

                    @if($student->ethnicity)
                    <div class="info-item">
                        <i class="fas fa-users text-muted"></i>
                        <span class="label">Dân tộc:</span>
                        <span class="value">{{ $student->ethnicity }}</span>
                    </div>
                    @endif

                    @if($student->email)
                    <div class="info-item">
                        <i class="fas fa-envelope text-muted"></i>
                        <span class="label">Email:</span>
                        <span class="value">{{ $student->email }}</span>
                    </div>
                    @endif

                    @if($student->gender)
                    <div class="info-item">
                        <i class="fas fa-venus-mars text-muted"></i>
                        <span class="label">Giới tính:</span>
                        <span class="value">
                            @if($student->gender == 'male') Nam
                            @elseif($student->gender == 'female') Nữ
                            @else Khác @endif
                        </span>
                    </div>
                    @endif

                    @if($student->address)
                    <div class="info-item">
                        <i class="fas fa-home text-muted"></i>
                        <span class="label">Địa chỉ:</span>
                        <span class="value">{{ $student->address }}</span>
                    </div>
                    @endif
                </div>

                <!-- Thông tin nghề nghiệp (nếu có) -->
                @if($student->current_workplace || $student->accounting_experience_years || $student->education_level || $student->study_major)
                <hr>
                <h6 class="mb-3">
                    <i class="fas fa-briefcase me-2"></i>Thông tin nghề nghiệp
                </h6>
                <div class="info-list">
                    @if($student->current_workplace)
                    <div class="info-item">
                        <i class="fas fa-building text-muted"></i>
                        <span class="label">Nơi công tác:</span>
                        <span class="value">{{ $student->current_workplace }}</span>
                    </div>
                    @endif

                    @if($student->accounting_experience_years)
                    <div class="info-item">
                        <i class="fas fa-clock text-muted"></i>
                        <span class="label">Kinh nghiệm KT:</span>
                        <span class="value">{{ $student->accounting_experience_years }} năm</span>
                    </div>
                    @endif

                    @if($student->education_level)
                    <div class="info-item">
                        <i class="fas fa-graduation-cap text-muted"></i>
                        <span class="label">Bằng cấp:</span>
                        <span class="value">{{ ucfirst(str_replace('_', ' ', $student->education_level)) }}</span>
                    </div>
                    @endif

                    @if($student->study_major)
                    <div class="info-item">
                        <i class="fas fa-book text-muted"></i>
                        <span class="label">Ngành học:</span>
                        <span class="value">{{ $student->study_major }}</span>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Actions -->
                <div class="d-grid gap-2 mt-3">
                    <a href="{{ route('students.edit', $student) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Chỉnh sửa
                    </a>
                    <a href="{{ route('enrollments.create', ['student_id' => $student->id]) }}" class="btn btn-success">
                        <i class="fas fa-user-plus me-2"></i>Ghi danh khóa học
                    </a>
                    <a href="{{ route('payments.create', ['student_id' => $student->id]) }}" class="btn btn-primary">
                        <i class="fas fa-money-bill me-2"></i>Thu học phí
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Khóa học và thanh toán -->
    <div class="col-md-8">
        <!-- Thống kê nhanh -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="text-center">
                        <h4 class="stats-number">{{ $student->enrollments->count() }}</h4>
                        <p class="stats-label">Khóa học</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card success">
                    <div class="text-center">
                        <h4 class="stats-number">{{ $student->enrollments->where('status', 'enrolled')->count() }}</h4>
                        <p class="stats-label">Đang học</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card warning">
                    <div class="text-center">
                        <h4 class="stats-number">{{ $student->payments->count() }}</h4>
                        <p class="stats-label">Thanh toán</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card danger">
                    <div class="text-center">
                        <h4 class="stats-number">{{ number_format($student->payments->sum('amount')) }}đ</h4>
                        <p class="stats-label">Tổng đã TT</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Danh sách khóa học -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-graduation-cap me-2"></i>Khóa học đã tham gia
                </h5>
                <a href="{{ route('enrollments.create', ['student_id' => $student->id]) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus me-1"></i>Thêm khóa học
                </a>
            </div>
            <div class="card-body p-0">
                @if($student->enrollments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Khóa học</th>
                                    <th>Lớp</th>
                                    <th>Ngày ghi danh</th>
                                    <th>Trạng thái</th>
                                    <th>Học phí</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($student->enrollments as $enrollment)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $enrollment->courseClass->course->name }}</div>
                                        <small class="text-muted">{{ $enrollment->courseClass->course->major->name }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $enrollment->courseClass->name }}</div>
                                        <small class="text-muted">
                                            {{ $enrollment->courseClass->type == 'online' ? 'Online' : 'Offline' }}
                                        </small>
                                    </td>
                                    <td>{{ $enrollment->enrolled_at->format('d/m/Y') }}</td>
                                    <td>
                                        @if($enrollment->status == 'enrolled')
                                            <span class="badge bg-success">Đang học</span>
                                        @elseif($enrollment->status == 'completed')
                                            <span class="badge bg-info">Hoàn thành</span>
                                        @elseif($enrollment->status == 'dropped')
                                            <span class="badge bg-danger">Bỏ học</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $enrollment->status }}</span>
                                        @endif

                                        @if($enrollment->payment_status == 'paid')
                                            <br><span class="badge bg-success mt-1">Đã TT</span>
                                        @elseif($enrollment->payment_status == 'partial')
                                            <br><span class="badge bg-warning mt-1">TT 1 phần</span>
                                        @else
                                            <br><span class="badge bg-danger mt-1">Chưa TT</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-medium">{{ number_format($enrollment->total_fee) }}đ</div>
                                        @if($enrollment->discount_amount > 0)
                                            <small class="text-success">
                                                Giảm: {{ number_format($enrollment->discount_amount) }}đ
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('enrollments.show', $enrollment) }}" 
                                               class="btn btn-sm btn-outline-primary" title="Chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($enrollment->payment_status != 'paid')
                                                <a href="{{ route('payments.create', ['enrollment_id' => $enrollment->id]) }}" 
                                                   class="btn btn-sm btn-outline-success" title="Thu học phí">
                                                    <i class="fas fa-money-bill"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Chưa có khóa học nào</h6>
                        <a href="{{ route('enrollments.create', ['student_id' => $student->id]) }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Ghi danh khóa học đầu tiên
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Lịch sử thanh toán -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-money-bill-wave me-2"></i>Lịch sử thanh toán
                </h5>
                <a href="{{ route('payments.create', ['student_id' => $student->id]) }}" class="btn btn-sm btn-success">
                    <i class="fas fa-plus me-1"></i>Thu học phí
                </a>
            </div>
            <div class="card-body p-0">
                @if($student->payments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Khóa học</th>
                                    <th>Số tiền</th>
                                    <th>Phương thức</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($student->payments->sortByDesc('payment_date') as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                    <td>
                                        @if($payment->enrollment)
                                            <div class="fw-medium">{{ $payment->enrollment->courseClass->course->name }}</div>
                                            <small class="text-muted">{{ $payment->enrollment->courseClass->name }}</small>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="fw-medium">{{ number_format($payment->amount) }}đ</td>
                                    <td>
                                        @if($payment->payment_method == 'cash')
                                            <span class="badge bg-success">Tiền mặt</span>
                                        @elseif($payment->payment_method == 'bank_transfer')
                                            <span class="badge bg-info">Chuyển khoản</span>
                                        @elseif($payment->payment_method == 'card')
                                            <span class="badge bg-warning">Thẻ</span>
                                        @elseif($payment->payment_method == 'qr')
                                            <span class="badge bg-primary">QR Code</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->status == 'paid')
                                            <span class="badge bg-success">Đã thanh toán</span>
                                        @elseif($payment->status == 'pending')
                                            <span class="badge bg-warning">Chờ xử lý</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $payment->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('payments.show', $payment) }}" 
                                           class="btn btn-sm btn-outline-primary" title="Chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-money-bill-wave fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Chưa có thanh toán nào</h6>
                        <a href="{{ route('payments.create', ['student_id' => $student->id]) }}" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Thu học phí đầu tiên
                        </a>
                    </div>
                @endif
            </div>
        </div>

        @if($student->notes)
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-sticky-note me-2"></i>Ghi chú
                </h5>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $student->notes }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('styles')
<style>
.avatar-lg {
    width: 80px;
    height: 80px;
    font-size: 2rem;
}

.info-list {
    list-style: none;
    padding: 0;
}

.info-item {
    display: flex;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f1f3f4;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item i {
    width: 20px;
    margin-right: 10px;
}

.info-item .label {
    font-weight: 500;
    margin-right: 10px;
    min-width: 100px;
}

.info-item .value {
    flex: 1;
    color: #333;
}

.stats-card {
    background: white;
    border: 1px solid #e3e6f0;
    border-radius: 0.35rem;
    padding: 1rem;
    height: 100%;
}

.stats-number {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: var(--bs-primary);
}

.stats-label {
    font-size: 0.875rem;
    color: #5a5c69;
    margin-bottom: 0;
}

.stats-card.success .stats-number {
    color: var(--bs-success);
}

.stats-card.warning .stats-number {
    color: var(--bs-warning);
}

.stats-card.danger .stats-number {
    color: var(--bs-danger);
}
</style>
@endsection 
 