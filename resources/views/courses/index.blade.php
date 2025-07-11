@extends('layouts.app')

@section('page-title', 'Quản lý khóa học')

@section('breadcrumb')
<li class="breadcrumb-item active">Khóa học</li>
@endsection

@section('content')
<!-- Filter & Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('courses.index') }}">
            <div class="row">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Tìm theo tên khóa học..." 
                               value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="major_id" class="form-select">
                        <option value="">Tất cả ngành</option>
                        @foreach($majors as $major)
                            <option value="{{ $major->id }}" {{ request('major_id') == $major->id ? 'selected' : '' }}>
                                {{ $major->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Hoạt động</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Không hoạt động</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('courses.create') }}" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-2"></i>Thêm mới
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Courses List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-graduation-cap me-2"></i>
            Danh sách khóa học
            <span class="badge bg-primary ms-2">{{ $courses->total() }} khóa học</span>
        </h5>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-success" onclick="exportCourses()">
                <i class="fas fa-file-excel me-1"></i>Xuất Excel
            </button>
            <button class="btn btn-sm btn-outline-info" onclick="printCourses()">
                <i class="fas fa-print me-1"></i>In danh sách
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        @if($courses->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="30%">Khóa học</th>
                            <th width="15%">Ngành</th>
                            <th width="15%">Sub-courses</th>
                            <th width="10%">Học phí</th>
                            <th width="10%">Lớp học</th>
                            <th width="10%">Trạng thái</th>
                            <th width="5%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($courses as $index => $course)
                        <tr>
                            <td>{{ $courses->firstItem() + $index }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                        {{ substr($course->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div class="fw-medium">{{ $course->name }}</div>
                                        @if($course->description)
                                            <small class="text-muted">{{ Str::limit($course->description, 50) }}</small>
                                        @endif
                                        <div class="small text-muted">
                                            <i class="fas fa-clock me-1"></i>{{ $course->duration_hours ?? 'N/A' }} giờ
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $course->major->name }}</span>
                            </td>
                            <td>
                                @if($course->subCourses->count() > 0)
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-success me-2">{{ $course->subCourses->count() }} khóa con</span>
                                        <button class="btn btn-sm btn-outline-primary" onclick="showSubCourses({{ $course->id }})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="collapse mt-2" id="subCourses{{ $course->id }}">
                                        @foreach($course->subCourses as $subCourse)
                                            <div class="small">
                                                <i class="fas fa-angle-right me-1"></i>{{ $subCourse->name }}
                                                <span class="badge bg-light text-dark ms-1">{{ number_format($subCourse->price) }}đ</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-muted">Không có</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-medium">{{ number_format($course->price) }}đ</div>
                                @if($course->subCourses->count() > 0)
                                    <small class="text-muted">
                                        Tổng: {{ number_format($course->subCourses->sum('price')) }}đ
                                    </small>
                                @endif
                            </td>
                            <td>
                                @php
                                    $totalClasses = $course->classes->count();
                                    $activeClasses = $course->classes->where('status', 'active')->count();
                                @endphp
                                <div>
                                    <span class="fw-medium">{{ $totalClasses }}</span> lớp
                                </div>
                                @if($activeClasses > 0)
                                    <small class="text-success">{{ $activeClasses }} đang mở</small>
                                @endif
                            </td>
                            <td>
                                @if($course->is_active)
                                    <span class="badge bg-success">Hoạt động</span>
                                @else
                                    <span class="badge bg-secondary">Tạm dừng</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('courses.show', $course) }}">
                                                <i class="fas fa-eye me-2"></i>Chi tiết
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('courses.edit', $course) }}">
                                                <i class="fas fa-edit me-2"></i>Chỉnh sửa
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        @if($course->subCourses->count() > 0)
                                            <li>
                                                <a class="dropdown-item" href="{{ route('courses.sub-courses', $course) }}">
                                                    <i class="fas fa-list me-2"></i>Quản lý sub-courses
                                                </a>
                                            </li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item" href="{{ route('course-classes.index', ['course_id' => $course->id]) }}">
                                                <i class="fas fa-chalkboard-teacher me-2"></i>Quản lý lớp học
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item" onclick="duplicateCourse({{ $course->id }})">
                                                <i class="fas fa-copy me-2"></i>Nhân bản
                                            </button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item" onclick="toggleStatus({{ $course->id }})">
                                                <i class="fas fa-{{ $course->is_active ? 'pause' : 'play' }} me-2"></i>
                                                {{ $course->is_active ? 'Tạm dừng' : 'Kích hoạt' }}
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="card-footer">
                {{ $courses->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Chưa có khóa học nào</h5>
                <p class="text-muted">Hãy thêm khóa học đầu tiên để bắt đầu</p>
                <a href="{{ route('courses.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Thêm khóa học mới
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Quick Stats -->
@if($courses->count() > 0)
<div class="row mt-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $courses->total() }}</p>
                    <p class="stats-label">Tổng khóa học</p>
                </div>
                <i class="fas fa-graduation-cap fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $courses->where('is_active', true)->count() }}</p>
                    <p class="stats-label">Đang hoạt động</p>
                </div>
                <i class="fas fa-play fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $totalClasses ?? 0 }}</p>
                    <p class="stats-label">Tổng lớp học</p>
                </div>
                <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card danger">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $courses->sum(function($c) { return $c->subCourses->count(); }) }}</p>
                    <p class="stats-label">Sub-courses</p>
                </div>
                <i class="fas fa-list fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Sub-courses Modal -->
<div class="modal fade" id="subCoursesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết Sub-courses</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="subCoursesContent">
                <!-- Content loaded by JavaScript -->
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function showSubCourses(courseId) {
    loadSubCoursesModal(courseId);
}

function exportCourses() {
    alert('Chức năng xuất Excel đang được phát triển');
}

function printCourses() {
    window.print();
}

function duplicateCourse(courseId) {
    if (confirm('Bạn có muốn nhân bản khóa học này?')) {
        $.post('/api/courses/' + courseId + '/duplicate', {
            _token: $('meta[name="csrf-token"]').attr('content')
        }).done(function(response) {
            alert('Nhân bản thành công!');
            location.reload();
        }).fail(function() {
            alert('Có lỗi xảy ra!');
        });
    }
}

function toggleStatus(courseId) {
    $.post('/api/courses/' + courseId + '/toggle-status', {
        _token: $('meta[name="csrf-token"]').attr('content')
    }).done(function(response) {
        alert('Cập nhật trạng thái thành công!');
        location.reload();
    }).fail(function() {
        alert('Có lỗi xảy ra!');
    });
}

// Auto-submit form when select changes
$(document).ready(function() {
    $('select[name="major_id"], select[name="status"]').change(function() {
        $(this).closest('form').submit();
    });
});

// Load sub-courses modal
function loadSubCoursesModal(courseId) {
    $.get('/api/courses/' + courseId + '/sub-courses')
        .done(function(data) {
            var html = '<div class="table-responsive">';
            html += '<table class="table table-bordered table-hover">';
            html += '<thead><tr>';
            html += '<th>STT</th>';
            html += '<th>Tên khóa con</th>';
            html += '<th>Mã</th>';
            html += '<th>Học phí</th>';
            html += '<th>Thứ tự</th>';
            html += '<th>Hình thức</th>';
            html += '<th>Trạng thái</th>';
            html += '</tr></thead>';
            html += '<tbody>';
            
            if (data.subCourses && data.subCourses.length > 0) {
                data.subCourses.forEach(function(subCourse, index) {
                    html += '<tr>';
                    html += '<td>' + (index + 1) + '</td>';
                    html += '<td>' + subCourse.name + '</td>';
                    html += '<td>' + (subCourse.code || 'N/A') + '</td>';
                    html += '<td>' + subCourse.formatted_fee + '</td>';
                    html += '<td>' + subCourse.order + '</td>';
                    
                    // Hình thức
                    html += '<td>';
                    if (subCourse.has_online && subCourse.has_offline) {
                        html += '<span class="badge bg-primary">Online & Offline</span>';
                    } else if (subCourse.has_online) {
                        html += '<span class="badge bg-success">Online</span>';
                    } else if (subCourse.has_offline) {
                        html += '<span class="badge bg-warning">Offline</span>';
                    } else {
                        html += '<span class="badge bg-secondary">N/A</span>';
                    }
                    html += '</td>';
                    
                    // Trạng thái
                    html += '<td>';
                    if (subCourse.active) {
                        html += '<span class="badge bg-success">Hoạt động</span>';
                    } else {
                        html += '<span class="badge bg-secondary">Không hoạt động</span>';
                    }
                    html += '</td>';
                    
                    html += '</tr>';
                });
            } else {
                html += '<tr><td colspan="7" class="text-center">Không có khóa con nào</td></tr>';
            }
            
            html += '</tbody></table></div>';
            
            // Thêm footer với link xem chi tiết
            html += '<div class="mt-3 text-end">';
            html += '<a href="/courses/' + data.course.id + '/sub-courses" class="btn btn-primary btn-sm">';
            html += '<i class="fas fa-list me-1"></i>Xem chi tiết khóa con';
            html += '</a>';
            html += '</div>';
            
            $('#subCoursesContent').html(html);
            $('#subCoursesModal').modal('show');
        })
        .fail(function() {
            alert('Không thể tải thông tin khóa con');
        });
}
</script>
@endsection 