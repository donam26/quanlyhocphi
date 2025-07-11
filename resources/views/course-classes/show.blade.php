@extends('layouts.app')

@section('page-title', 'Chi tiết lớp: ' . $courseClass->name)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('course-classes.index') }}">Lớp học</a></li>
<li class="breadcrumb-item active">{{ $courseClass->name }}</li>
@endsection

@section('content')
<!-- Class Header -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <div class="avatar-lg bg-{{ $courseClass->type == 'online' ? 'info' : 'primary' }} text-white rounded-circle d-flex align-items-center justify-content-center me-4">
                        <i class="fas fa-{{ $courseClass->type == 'online' ? 'laptop' : 'chalkboard-teacher' }} fa-2x"></i>
                    </div>
                    <div>
                        <h4 class="mb-1">{{ $courseClass->name }}</h4>
                        <div class="d-flex align-items-center gap-3 text-muted">
                            <span><i class="fas fa-book me-1"></i>{{ $courseClass->course->name }}</span>
                            <span><i class="fas fa-graduation-cap me-1"></i>{{ $courseClass->course->major->name }}</span>
                            <span>
                                @if($courseClass->type == 'online')
                                    <span class="badge bg-info">
                                        <i class="fas fa-laptop me-1"></i>Online
                                    </span>
                                @else
                                    <span class="badge bg-primary">
                                        <i class="fas fa-chalkboard-teacher me-1"></i>Offline
                                    </span>
                                @endif
                            </span>
                        </div>
                        @if($courseClass->description)
                            <p class="text-muted mt-2 mb-0">{{ $courseClass->description }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <div class="d-flex flex-column align-items-end">
                    <span class="badge bg-success mb-2">{{ $courseClass->status }}</span>

                    <div class="text-primary h5 mb-1">{{ number_format($courseClass->course->fee) }} VNĐ</div>
                    <small class="text-muted">Học phí</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card card-body text-center">
            <h4 class="mb-1">{{ $stats['total_enrolled'] }} / {{ $courseClass->max_students }}</h4>
            <p class="text-muted mb-0">Sĩ số</p>
            <div class="progress mt-2" style="height: 5px;">
                <div class="progress-bar" role="progressbar" style="width: {{ $stats['occupancy_rate'] }}%;" aria-valuenow="{{ $stats['occupancy_rate'] }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body text-center">
            <h4 class="mb-1 text-success">{{ number_format($stats['paid_revenue']) }}</h4>
            <p class="text-muted mb-0">Doanh thu đã thu</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body text-center">
            <h4 class="mb-1 text-danger">{{ number_format($stats['total_revenue'] - $stats['paid_revenue']) }}</h4>
            <p class="text-muted mb-0">Doanh thu còn nợ</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-body text-center">
            <h4 class="mb-1">{{ $stats['unpaid_students'] }}</h4>
            <p class="text-muted mb-0">Học viên còn nợ</p>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="row">
    <!-- Left Column -->
    <div class="col-md-8">
        <!-- Students List -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">
                    <i class="fas fa-users me-2"></i>
                    Danh sách học viên
                    <span class="badge bg-primary ms-2">{{ $stats['total_enrolled'] }}</span>
                </h6>
                <div class="btn-group">
                    <a href="{{ route('enrollments.create', ['course_class_id' => $courseClass->id]) }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i>Thêm học viên
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                @if($courseClass->enrollments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Học viên</th>
                                    <th>Ngày đăng ký</th>
                                    <th>Trạng thái thanh toán</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($courseClass->enrollments->take(10) as $enrollment)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                                {{ strtoupper(substr($enrollment->student->full_name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <a href="{{ route('students.show', $enrollment->student) }}" class="fw-medium">{{ $enrollment->student->full_name }}</a>
                                                <div class="text-muted">{{ $enrollment->student->phone }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $enrollment->enrollment_date->format('d/m/Y') }}</td>
                                    <td>
                                        @if($enrollment->getRemainingAmount() <= 0)
                                            <span class="badge bg-success">Đã thanh toán</span>
                                        @elseif($enrollment->getTotalPaidAmount() > 0)
                                            <span class="badge bg-warning">Thanh toán 1 phần</span>
                                        @else
                                            <span class="badge bg-danger">Chưa thanh toán</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                    type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('enrollments.show', $enrollment) }}">
                                                        <i class="fas fa-eye me-2"></i>Chi tiết ghi danh
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('payments.create', ['enrollment_id' => $enrollment->id]) }}">
                                                        <i class="fas fa-money-bill me-2"></i>Thu học phí
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($courseClass->enrollments->count() > 10)
                        <div class="card-footer text-center">
                            <a href="{{ route('course-classes.students', $courseClass) }}" class="btn btn-outline-primary">
                                Xem tất cả {{ $courseClass->enrollments->count() }} học viên
                            </a>
                        </div>
                    @endif
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Chưa có học viên nào</h6>
                        <p class="text-muted">Thêm học viên đầu tiên cho lớp này</p>
                        <a href="{{ route('enrollments.create', ['course_class_id' => $courseClass->id]) }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Thêm học viên
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Thông tin chi tiết</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Ngày khai giảng:</span>
                        <strong>{{ $courseClass->start_date ? $courseClass->start_date->format('d/m/Y') : 'Chưa có' }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Ngày kết thúc:</span>
                        <strong>{{ $courseClass->end_date ? $courseClass->end_date->format('d/m/Y') : 'Chưa có' }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Lịch học:</span>
                        <strong>{{ $courseClass->schedule ?: 'Chưa có' }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Địa điểm:</span>
                        <strong>{{ $courseClass->location ?: 'N/A' }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Giảng viên:</span>
                        <strong>{{ $courseClass->instructor ?: 'Chưa có' }}</strong>
                    </li>
                </ul>
            </div>
            <div class="card-footer d-grid">
                <a href="{{ route('course-classes.edit', $courseClass) }}" class="btn btn-warning">
                    <i class="fas fa-edit me-2"></i>Chỉnh sửa lớp học
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function duplicateClass() {
    if (confirm('Nhân bản lớp học này? Các thông tin cơ bản sẽ được sao chép.')) {
        $.post(`/api/course-classes/{{ $courseClass->id }}/duplicate`, {
            _token: $('meta[name="csrf-token"]').attr('content')
        }).done(function(response) {
            alert('Nhân bản thành công!');
            window.location.href = response.redirect_url;
        }).fail(function() {
            alert('Có lỗi xảy ra!');
        });
    }
}

function confirmDelete() {
    $('#deleteModal').modal('show');
}

function exportStudents() {
    window.location.href = '{{ route("course-classes.students", $courseClass) }}?export=excel';
}
</script>
@endsection 