@extends('layouts.app')

@section('page-title', 'Quản lý lớp học')

@section('breadcrumb')
<li class="breadcrumb-item active">Lớp học</li>
@endsection

@section('content')
<!-- Filter & Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('course-classes.index') }}">
            <div class="row">
                <div class="col-md-3">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Tìm theo tên lớp..." 
                               value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="course_id" class="form-select">
                        <option value="">Tất cả khóa học</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">Tất cả loại</option>
                        <option value="online" {{ request('type') == 'online' ? 'selected' : '' }}>Online</option>
                        <option value="offline" {{ request('type') == 'offline' ? 'selected' : '' }}>Offline</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang mở</option>
                        <option value="full" {{ request('status') == 'full' ? 'selected' : '' }}>Đầy</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tạm dừng</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <a href="{{ route('course-classes.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tạo lớp mới
                        </a>
                        <button type="button" class="btn btn-success" onclick="bulkActions()">
                            <i class="fas fa-tasks me-2"></i>Thao tác hàng loạt
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Classes List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-chalkboard-teacher me-2"></i>
            Danh sách lớp học
            <span class="badge bg-primary ms-2">{{ $classes->total() }} lớp</span>
        </h5>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-success" onclick="exportClasses()">
                <i class="fas fa-file-excel me-1"></i>Xuất Excel
            </button>
            <button class="btn btn-sm btn-outline-info" onclick="printClasses()">
                <i class="fas fa-print me-1"></i>In danh sách
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        @if($classes->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="3%">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th width="25%">Thông tin lớp</th>
                            <th width="15%">Khóa học</th>
                            <th width="10%">Loại</th>
                            <th width="15%">Sĩ số</th>
                            <th width="15%">Lịch học</th>
                            <th width="10%">Trạng thái</th>
                            <th width="7%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($classes as $index => $class)
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_classes[]" value="{{ $class->id }}" class="class-checkbox">
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-{{ $class->type == 'online' ? 'info' : 'primary' }} text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                        <i class="fas fa-{{ $class->type == 'online' ? 'laptop' : 'chalkboard-teacher' }}"></i>
                                    </div>
                                    <div>
                                        <div class="fw-medium">{{ $class->name }}</div>
                                        @if($class->description)
                                            <small class="text-muted">{{ Str::limit($class->description, 50) }}</small>
                                        @endif
                                        <div class="small text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ $class->start_date ? $class->start_date->format('d/m/Y') : 'Chưa xác định' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium">{{ $class->course->name }}</div>
                                <small class="text-muted">{{ $class->course->major->name }}</small>
                            </td>
                            <td>
                                @if($class->type == 'online')
                                    <span class="badge bg-info">
                                        <i class="fas fa-laptop me-1"></i>Online
                                    </span>
                                @else
                                    <span class="badge bg-primary">
                                        <i class="fas fa-chalkboard-teacher me-1"></i>Offline
                                    </span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $currentStudents = $class->enrollments->count();
                                    $maxStudents = $class->max_students;
                                    $occupancy = $maxStudents ? ($currentStudents / $maxStudents * 100) : 0;
                                @endphp
                                
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <span class="fw-medium">{{ $currentStudents }}</span>
                                        <span class="text-muted">/{{ $maxStudents ?? '∞' }}</span>
                                    </div>
                                    @if($maxStudents)
                                        <div class="flex-grow-1">
                                            <div class="progress" style="height: 6px;">
                                                @php
                                                    $progressClass = $occupancy >= 90 ? 'danger' : ($occupancy >= 70 ? 'warning' : 'success');
                                                @endphp
                                                <div class="progress-bar bg-{{ $progressClass }}" 
                                                     style="width: {{ min($occupancy, 100) }}%"></div>
                                            </div>
                                            <small class="text-muted">{{ round($occupancy) }}%</small>
                                        </div>
                                    @endif
                                </div>
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
                                
                                @if($class->location && $class->type == 'offline')
                                    <div class="small text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        {{ Str::limit($class->location, 20) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($class->status == 'active')
                                    <span class="badge bg-success">Đang mở</span>
                                @elseif($class->status == 'full')
                                    <span class="badge bg-warning">Đầy</span>
                                @elseif($class->status == 'completed')
                                    <span class="badge bg-info">Hoàn thành</span>
                                @elseif($class->status == 'cancelled')
                                    <span class="badge bg-danger">Hủy</span>
                                @else
                                    <span class="badge bg-secondary">Tạm dừng</span>
                                @endif
                                
                                @if($class->start_date && $class->start_date->isPast() && $class->status == 'active')
                                    <br><span class="badge bg-primary mt-1">Đang diễn ra</span>
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
                                            <a class="dropdown-item" href="{{ route('course-classes.show', $class) }}">
                                                <i class="fas fa-eye me-2"></i>Chi tiết
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('course-classes.edit', $class) }}">
                                                <i class="fas fa-edit me-2"></i>Chỉnh sửa
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('course-classes.students', $class) }}">
                                                <i class="fas fa-users me-2"></i>Học viên ({{ $currentStudents }})
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('attendance.show-class', $class) }}">
                                                <i class="fas fa-clipboard-check me-2"></i>Điểm danh
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('course-classes.financial-report', $class) }}">
                                                <i class="fas fa-chart-line me-2"></i>Báo cáo tài chính
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <button class="dropdown-item" onclick="changeStatus({{ $class->id }})">
                                                <i class="fas fa-exchange-alt me-2"></i>Đổi trạng thái
                                            </button>
                                        </li>
                                        <li>
                                            <button class="dropdown-item" onclick="duplicateClass({{ $class->id }})">
                                                <i class="fas fa-copy me-2"></i>Nhân bản
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
                {{ $classes->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-chalkboard-teacher fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Chưa có lớp học nào</h5>
                <p class="text-muted">Hãy tạo lớp học đầu tiên để bắt đầu</p>
                <a href="{{ route('course-classes.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Tạo lớp học mới
                </a>
            </div>
        @endif
    </div>
</div>

<!-- Quick Stats -->
@if($classes->count() > 0)
<div class="row mt-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $classes->total() }}</p>
                    <p class="stats-label">Tổng lớp học</p>
                </div>
                <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $classes->where('status', 'active')->count() }}</p>
                    <p class="stats-label">Đang mở</p>
                </div>
                <i class="fas fa-play fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $classes->where('type', 'online')->count() }}</p>
                    <p class="stats-label">Online</p>
                </div>
                <i class="fas fa-laptop fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card danger">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $classes->where('type', 'offline')->count() }}</p>
                    <p class="stats-label">Offline</p>
                </div>
                <i class="fas fa-building fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Bulk Actions Modal -->
<div class="modal fade" id="bulkActionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thao tác hàng loạt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="bulkActionForm">
                    <div class="mb-3">
                        <label class="form-label">Chọn thao tác:</label>
                        <select name="bulk_action" class="form-select" required>
                            <option value="">Chọn thao tác</option>
                            <option value="change_status">Đổi trạng thái</option>
                            <option value="export">Xuất danh sách học viên</option>
                            <option value="send_notification">Gửi thông báo</option>
                        </select>
                    </div>
                    
                    <div id="statusSelect" class="mb-3" style="display: none;">
                        <label class="form-label">Trạng thái mới:</label>
                        <select name="new_status" class="form-select">
                            <option value="active">Đang mở</option>
                            <option value="full">Đầy</option>
                            <option value="completed">Hoàn thành</option>
                            <option value="inactive">Tạm dừng</option>
                        </select>
                    </div>
                    
                    <div id="notificationText" class="mb-3" style="display: none;">
                        <label class="form-label">Nội dung thông báo:</label>
                        <textarea name="notification_message" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="executeBulkAction()">Thực hiện</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.class-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function bulkActions() {
    const selected = document.querySelectorAll('.class-checkbox:checked');
    if (selected.length === 0) {
        alert('Vui lòng chọn ít nhất một lớp học!');
        return;
    }
    
    $('#bulkActionsModal').modal('show');
}

function executeBulkAction() {
    const selected = Array.from(document.querySelectorAll('.class-checkbox:checked')).map(cb => cb.value);
    const action = $('select[name="bulk_action"]').val();
    
    if (!action) {
        alert('Vui lòng chọn thao tác!');
        return;
    }
    
    const data = {
        _token: $('meta[name="csrf-token"]').attr('content'),
        class_ids: selected,
        action: action
    };
    
    if (action === 'change_status') {
        data.new_status = $('select[name="new_status"]').val();
    } else if (action === 'send_notification') {
        data.message = $('textarea[name="notification_message"]').val();
    }
    
    $.post('/api/course-classes/bulk-action', data)
        .done(function(response) {
            alert('Thao tác thành công!');
            location.reload();
        })
        .fail(function() {
            alert('Có lỗi xảy ra!');
        });
    
    $('#bulkActionsModal').modal('hide');
}

function changeStatus(classId) {
    const statuses = [
        {value: 'active', text: 'Đang mở'},
        {value: 'full', text: 'Đầy'},
        {value: 'completed', text: 'Hoàn thành'},
        {value: 'inactive', text: 'Tạm dừng'},
        {value: 'cancelled', text: 'Hủy'}
    ];
    
    let options = '';
    statuses.forEach(status => {
        options += `<option value="${status.value}">${status.text}</option>`;
    });
    
    const newStatus = prompt(`Chọn trạng thái mới:\n${statuses.map((s, i) => `${i+1}. ${s.text}`).join('\n')}`);
    
    if (newStatus && ['active', 'full', 'completed', 'inactive', 'cancelled'].includes(newStatus)) {
        $.post(`/api/course-classes/${classId}/change-status`, {
            _token: $('meta[name="csrf-token"]').attr('content'),
            status: newStatus
        }).done(function() {
            alert('Cập nhật trạng thái thành công!');
            location.reload();
        }).fail(function() {
            alert('Có lỗi xảy ra!');
        });
    }
}

function duplicateClass(classId) {
    if (confirm('Nhân bản lớp học này?')) {
        $.post(`/api/course-classes/${classId}/duplicate`, {
            _token: $('meta[name="csrf-token"]').attr('content')
        }).done(function(response) {
            alert('Nhân bản thành công!');
            location.reload();
        }).fail(function() {
            alert('Có lỗi xảy ra!');
        });
    }
}

function exportClasses() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'excel');
    window.location.href = '{{ route("course-classes.index") }}?' + params.toString();
}

function printClasses() {
    window.print();
}

// Auto-submit form when select changes
$('select[name="course_id"], select[name="type"], select[name="status"]').change(function() {
    $(this).closest('form').submit();
});

// Show/hide bulk action options
$('select[name="bulk_action"]').change(function() {
    const action = $(this).val();
    $('#statusSelect, #notificationText').hide();
    
    if (action === 'change_status') {
        $('#statusSelect').show();
    } else if (action === 'send_notification') {
        $('#notificationText').show();
    }
});
</script>
@endsection 
 