@extends('layouts.app')

@section('page-title', 'Chi tiết khóa học: ' . $course->name)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('courses.index') }}">Khóa học</a></li>
<li class="breadcrumb-item active">{{ $course->name }}</li>
@endsection

@section('content')
<div class="row">
    <!-- Course Info -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-graduation-cap me-2"></i>Thông tin khóa học
                </h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="avatar-lg bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center">
                        <span style="font-size: 2rem;">{{ substr($course->name, 0, 1) }}</span>
                    </div>
                    <h5 class="mt-2 mb-0">{{ $course->name }}</h5>
                    <p class="text-muted mb-0">{{ $course->major->name }}</p>
                </div>

                <div class="info-list">
                    <div class="info-item">
                        <i class="fas fa-money-bill text-muted"></i>
                        <span class="label">Học phí:</span>
                        <span class="value fw-medium">{{ number_format($course->price) }}đ</span>
                    </div>

                    @if($course->duration_hours)
                    <div class="info-item">
                        <i class="fas fa-clock text-muted"></i>
                        <span class="label">Thời lượng:</span>
                        <span class="value">{{ $course->duration_hours }} giờ</span>
                    </div>
                    @endif

                    <div class="info-item">
                        <i class="fas fa-list text-muted"></i>
                        <span class="label">Sub-courses:</span>
                        <span class="value">{{ $course->subCourses->count() }} khóa con</span>
                    </div>

                    <div class="info-item">
                        <i class="fas fa-chalkboard-teacher text-muted"></i>
                        <span class="label">Lớp học:</span>
                        <span class="value">{{ $course->courseClasses->count() }} lớp</span>
                    </div>

                    <div class="info-item">
                        <i class="fas fa-user-graduate text-muted"></i>
                        <span class="label">Học viên:</span>
                        <span class="value">{{ $totalEnrollments }} học viên</span>
                    </div>

                    <div class="info-item">
                        <i class="fas fa-{{ $course->is_active ? 'check-circle text-success' : 'pause-circle text-warning' }} text-muted"></i>
                        <span class="label">Trạng thái:</span>
                        <span class="value">
                            @if($course->is_active)
                                <span class="badge bg-success">Hoạt động</span>
                            @else
                                <span class="badge bg-warning">Tạm dừng</span>
                            @endif
                        </span>
                    </div>
                </div>

                @if($course->description)
                <hr>
                <h6 class="mb-2">Mô tả</h6>
                <p class="text-muted">{{ $course->description }}</p>
                @endif

                <!-- Actions -->
                <div class="d-grid gap-2 mt-3">
                    <a href="{{ route('courses.edit', $course) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>Chỉnh sửa
                    </a>
                    <a href="{{ route('course-classes.create', ['course_id' => $course->id]) }}" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Tạo lớp học
                    </a>
                    <a href="{{ route('courses.report', $course) }}" class="btn btn-info">
                        <i class="fas fa-chart-bar me-2"></i>Báo cáo
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Details -->
    <div class="col-md-8">
        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="text-center">
                        <h4 class="stats-number">{{ $course->subCourses->count() }}</h4>
                        <p class="stats-label">Sub-courses</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card success">
                    <div class="text-center">
                        <h4 class="stats-number">{{ $course->courseClasses->count() }}</h4>
                        <p class="stats-label">Lớp học</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card warning">
                    <div class="text-center">
                        <h4 class="stats-number">{{ $totalEnrollments }}</h4>
                        <p class="stats-label">Học viên</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card danger">
                    <div class="text-center">
                        <h4 class="stats-number">{{ number_format($totalRevenue) }}đ</h4>
                        <p class="stats-label">Doanh thu</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sub-courses -->
        @if($course->subCourses->count() > 0)
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-list me-2"></i>Sub-courses
                </h5>
                <a href="{{ route('courses.sub-courses', $course) }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-cog me-1"></i>Quản lý
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Thứ tự</th>
                                <th>Tên khóa con</th>
                                <th>Học phí</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($course->subCourses->sortBy('order') as $subCourse)
                            <tr>
                                <td><span class="badge bg-light text-dark">{{ $subCourse->order }}</span></td>
                                <td>
                                    <div class="fw-medium">{{ $subCourse->name }}</div>
                                    @if($subCourse->description)
                                        <small class="text-muted">{{ $subCourse->description }}</small>
                                    @endif
                                </td>
                                <td class="fw-medium">{{ number_format($subCourse->price) }}đ</td>
                                <td>
                                    @if($subCourse->is_active)
                                        <span class="badge bg-success">Hoạt động</span>
                                    @else
                                        <span class="badge bg-secondary">Tạm dừng</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" title="Chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" title="Chỉnh sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <!-- Course Classes -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Lớp học
                </h5>
                <a href="{{ route('course-classes.create', ['course_id' => $course->id]) }}" class="btn btn-sm btn-success">
                    <i class="fas fa-plus me-1"></i>Tạo lớp mới
                </a>
            </div>
            <div class="card-body p-0">
                @if($course->courseClasses->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Tên lớp</th>
                                    <th>Loại</th>
                                    <th>Sĩ số</th>
                                    <th>Lịch học</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($course->courseClasses as $class)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $class->name }}</div>
                                        <small class="text-muted">{{ $class->description }}</small>
                                    </td>
                                    <td>
                                        @if($class->type == 'online')
                                            <span class="badge bg-info">Online</span>
                                        @else
                                            <span class="badge bg-primary">Offline</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $class->enrollments->count() }}/{{ $class->max_students ?? '∞' }}</div>
                                        @php
                                            $occupancy = $class->max_students ? ($class->enrollments->count() / $class->max_students * 100) : 0;
                                        @endphp
                                        @if($occupancy > 0)
                                            <div class="progress" style="height: 4px;">
                                                                                         @php
                                             $progressClass = $occupancy >= 90 ? 'danger' : ($occupancy >= 70 ? 'warning' : 'success');
                                             $progressWidth = min($occupancy, 100);
                                         @endphp
                                         <div class="progress-bar bg-{{ $progressClass }}" 
                                              style="width: {{ $progressWidth }}%"></div>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($class->start_date)
                                            <div>{{ $class->start_date->format('d/m/Y') }}</div>
                                            @if($class->schedule)
                                                <small class="text-muted">{{ $class->schedule }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">Chưa xác định</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($class->status == 'active')
                                            <span class="badge bg-success">Đang mở</span>
                                        @elseif($class->status == 'full')
                                            <span class="badge bg-warning">Đầy</span>
                                        @elseif($class->status == 'completed')
                                            <span class="badge bg-info">Hoàn thành</span>
                                        @else
                                            <span class="badge bg-secondary">Tạm dừng</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('course-classes.show', $class) }}" 
                                               class="btn btn-sm btn-outline-primary" title="Chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('course-classes.edit', $class) }}" 
                                               class="btn btn-sm btn-outline-warning" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ route('course-classes.students', $class) }}" 
                                               class="btn btn-sm btn-outline-info" title="Học viên">
                                                <i class="fas fa-users"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Chưa có lớp học nào</h6>
                        <a href="{{ route('course-classes.create', ['course_id' => $course->id]) }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tạo lớp học đầu tiên
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.avatar-lg {
    width: 80px;
    height: 80px;
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
    min-width: 80px;
}

.info-item .value {
    flex: 1;
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
 