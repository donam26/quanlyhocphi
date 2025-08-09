@extends('layouts.app')

@section('page-title', 'Quản lý lịch học')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row">
        <div class="col-md-8">
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('schedules.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Tạo lịch học mới
            </a>
        </div>
    </div>

    <!-- Filters và View Toggle -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="{{ route('schedules.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="course_item_id" class="form-label">Khóa học</label>
                            <select name="course_item_id" id="course_item_id" class="form-select">
                                <option value="">-- Tất cả khóa học --</option>
                                @foreach($parentCourses as $course)
                                    <option value="{{ $course->id }}" {{ $courseItemId == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="view" class="form-label">Kiểu hiển thị</label>
                            <select name="view" id="view" class="form-select">
                                <option value="calendar" {{ $viewType == 'calendar' ? 'selected' : '' }}>Calendar</option>
                                <option value="list" {{ $viewType == 'list' ? 'selected' : '' }}>Danh sách</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="fas fa-filter me-1"></i>Lọc
                            </button>
                            <a href="{{ route('schedules.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>Xóa bộ lọc
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($viewType == 'calendar')
        <!-- Calendar View -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Lịch học</h5>
                    </div>
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- List View -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách lịch học</h5>
                    </div>
                    <div class="card-body">
                        @if($schedules->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Khóa học</th>
                                            <th>Ngày học</th>
                                            <th>Thời gian</th>
                                            <th>Trạng thái</th>
                                            <th>Loại</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($schedules as $schedule)
                                            <tr>
                                                <td>
                                                    <strong>{{ $schedule->courseItem->name }}</strong>
                                                    @if($schedule->courseItem->path)
                                                        <br><small class="text-muted">{{ $schedule->courseItem->path }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">{{ $schedule->days_of_week_names }}</span>
                                                </td>
                                                <td>
                                                    <i class="fas fa-calendar-day me-1"></i>{{ $schedule->start_date->format('d/m/Y') }}
                                                    @if($schedule->end_date)
                                                        <br><i class="fas fa-arrow-right me-1"></i>{{ $schedule->end_date->format('d/m/Y') }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($schedule->active)
                                                        <span class="badge bg-success">Hoạt động</span>
                                                    @else
                                                        <span class="badge bg-secondary">Tạm dừng</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($schedule->is_inherited)
                                                        <span class="badge bg-warning">Kế thừa</span>
                                                    @else
                                                        <span class="badge bg-primary">Gốc</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('schedules.show', $schedule) }}" 
                                                           class="btn btn-sm btn-outline-info" title="Xem chi tiết">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @if(!$schedule->is_inherited)
                                                            <a href="{{ route('schedules.edit', $schedule) }}" 
                                                               class="btn btn-sm btn-outline-warning" title="Chỉnh sửa">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-danger" 
                                                                    title="Xóa"
                                                                    onclick="confirmDelete({{ $schedule->id }})">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <div class="d-flex justify-content-center">
                                {{ $schedules->appends(request()->query())->links() }}
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Chưa có lịch học nào</h5>
                                <p class="text-muted">Hãy tạo lịch học đầu tiên cho khóa học của bạn</p>
                                <a href="{{ route('schedules.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i>Tạo lịch học mới
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa lịch học này?</p>
                <p class="text-danger"><strong>Lưu ý:</strong> Việc xóa lịch gốc sẽ xóa tất cả lịch con kế thừa!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Event Detail Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết buổi học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="eventDetails">
                    <!-- Event details will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" id="attendanceBtn" class="btn btn-success" style="display: none;">
                    <i class="fas fa-user-check me-1"></i>Điểm danh
                </button>
                <a href="#" id="editScheduleBtn" class="btn btn-primary" style="display: none;">
                    <i class="fas fa-edit me-1"></i>Chỉnh sửa lịch
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Modal -->
<div class="modal fade" id="attendanceModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-check me-2"></i>Điểm danh buổi học
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="attendanceContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Đang tải...</span>
                        </div>
                        <p class="mt-2">Đang tải thông tin buổi học...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" id="saveAttendanceBtn" class="btn btn-success" disabled>
                    <i class="fas fa-save me-1"></i>Lưu điểm danh
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<style>
.fc-event {
    cursor: pointer;
}
.fc-event:hover {
    opacity: 0.8;
}
.fc-daygrid-event {
    font-size: 0.85em;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales/vi.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var viewType = '{{ $viewType }}';
    if (viewType === 'calendar') {
        initializeCalendar();
    }
});

function initializeCalendar() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'vi',
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        height: 'auto',
        events: function(fetchInfo, successCallback, failureCallback) {
            var courseItemId = document.getElementById('course_item_id').value;
            var url = '{{ route("schedules.calendar-events") }}';
            
            // Build URL with parameters
            var params = new URLSearchParams({
                start: fetchInfo.startStr,
                end: fetchInfo.endStr,
                course_item_id: courseItemId
            });
            
            fetch(url + '?' + params.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    if (response.status === 401 || response.status === 419) {
                        window.location.href = '/login';
                        return;
                    }
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Calendar events loaded:', data.length);
                successCallback(data);
            })
            .catch(error => {
                console.error('Error loading events:', error);
                // Show user-friendly error
                alert('Không thể tải lịch học. Vui lòng thử lại hoặc đăng nhập lại.');
                failureCallback(error);
            });
        },
        eventClick: function(info) {
            showEventDetails(info.event);
        },
        eventDidMount: function(info) {
            // Add tooltip
            info.el.setAttribute('title', info.event.title + '\n' + info.event.extendedProps.course_path);
        }
    });
    
    calendar.render();
    
    // Refresh calendar when filter changes
    document.getElementById('course_item_id').addEventListener('change', function() {
        calendar.refetchEvents();
    });
}

function showEventDetails(event) {
    var details = `
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-graduation-cap me-2"></i>Khóa học</h6>
                <p>${event.extendedProps.course_name}</p>
                
                <h6><i class="fas fa-calendar-day me-2"></i>Ngày học</h6>
                <p>${event.start.toLocaleDateString('vi-VN')}</p>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-clock me-2"></i>Thời gian</h6>
                <p>${event.start.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'})} - 
                   ${event.end.toLocaleTimeString('vi-VN', {hour: '2-digit', minute: '2-digit'})}</p>
                
                <h6><i class="fas fa-info-circle me-2"></i>Loại lịch</h6>
                <p>
                    ${event.extendedProps.is_inherited ? 
                        '<span class="badge bg-warning">Lịch kế thừa</span>' : 
                        '<span class="badge bg-primary">Lịch gốc</span>'
                    }
                </p>
            </div>
        </div>
        
        ${event.extendedProps.course_path ? `
            <div class="row mt-3">
                <div class="col-12">
                    <h6><i class="fas fa-sitemap me-2"></i>Đường dẫn khóa học</h6>
                    <p class="text-muted">${event.extendedProps.course_path}</p>
                </div>
            </div>
        ` : ''}
    `;
    
    document.getElementById('eventDetails').innerHTML = details;
    
    // Show edit button for non-inherited schedules
    var editBtn = document.getElementById('editScheduleBtn');
    var attendanceBtn = document.getElementById('attendanceBtn');
    
    if (!event.extendedProps.is_inherited) {
        editBtn.style.display = 'inline-block';
        editBtn.href = '/schedules/' + event.extendedProps.schedule_id + '/edit';
    } else {
        editBtn.style.display = 'none';
    }
    
    // Show attendance button for all events
    attendanceBtn.style.display = 'inline-block';
    attendanceBtn.onclick = function() {
        console.log('Event object:', event);
        console.log('Schedule ID:', event.extendedProps ? event.extendedProps.schedule_id : event.schedule_id);
        console.log('Date:', event.start.toISOString().split('T')[0]);
        
        const scheduleId = event.extendedProps ? event.extendedProps.schedule_id : event.schedule_id;
        openAttendanceModal(scheduleId, event.start.toISOString().split('T')[0]);
    };
    
    var modal = new bootstrap.Modal(document.getElementById('eventModal'));
    modal.show();
}

function confirmDelete(scheduleId) {
    document.getElementById('deleteForm').action = '/schedules/' + scheduleId;
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Global variables for attendance
var currentSessionData = null;
var attendanceModal = null;

function openAttendanceModal(scheduleId, date) {
    // Close event modal first
    var eventModal = bootstrap.Modal.getInstance(document.getElementById('eventModal'));
    if (eventModal) {
        eventModal.hide();
    }
    
    // Show attendance modal
    attendanceModal = new bootstrap.Modal(document.getElementById('attendanceModal'));
    attendanceModal.show();
    
    // Load session info
    loadSessionInfo(scheduleId, date);
}

function loadSessionInfo(scheduleId, date) {
    console.log('Loading session info for schedule:', scheduleId, 'date:', date);
    
    fetch('{{ route("schedules.session-info") }}?' + new URLSearchParams({
        schedule_id: scheduleId,
        date: date
    }), {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Session data:', data);
        if (data.success) {
            currentSessionData = data;
            renderAttendanceForm(data);
        } else {
            showAttendanceError('Không thể tải thông tin buổi học: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error loading session info:', error);
        showAttendanceError('Có lỗi xảy ra khi tải thông tin buổi học: ' + error.message);
    });
}

function renderAttendanceForm(data) {
    var session = data.session;
    var students = data.students;
    
    var html = `
        <div class="row mb-4">
            <div class="col-md-8">
                <h6><i class="fas fa-graduation-cap me-2"></i>Thông tin buổi học</h6>
                <p class="mb-1"><strong>Khóa học:</strong> ${session.course_name}</p>
                <p class="mb-1"><strong>Ngày học:</strong> ${session.day_name}, ${session.formatted_date}</p>
                ${session.course_path ? `<p class="mb-0 text-muted">${session.course_path}</p>` : ''}
            </div>
            <div class="col-md-4 text-end">
                <div class="d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-sm btn-success" onclick="markAllPresent()">
                        <i class="fas fa-check-circle me-1"></i>Tất cả có mặt
                    </button>
                    <button type="button" class="btn btn-sm btn-warning" onclick="markAllAbsent()">
                        <i class="fas fa-times-circle me-1"></i>Tất cả vắng
                    </button>
                </div>
            </div>
        </div>
        
        ${students.length === 0 ? `
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Chưa có học viên nào ghi danh vào khóa học này.
            </div>
        ` : `
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th width="5%">#</th>
                            <th width="25%">Họ tên</th>
                            <th width="15%">Số điện thoại</th>
                            <th width="20%">Trạng thái</th>
                            <th width="35%">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${students.map((student, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>
                                    <strong>${student.student_name}</strong>
                                    <input type="hidden" name="attendances[${index}][enrollment_id]" value="${student.enrollment_id}">
                                </td>
                                <td>${student.student_phone || ''}</td>
                                <td>
                                    <select name="attendances[${index}][status]" class="form-select form-select-sm attendance-status">
                                        <option value="present" ${student.current_status === 'present' ? 'selected' : ''}>Có mặt</option>
                                        <option value="absent" ${student.current_status === 'absent' ? 'selected' : ''}>Vắng mặt</option>
                                        <option value="late" ${student.current_status === 'late' ? 'selected' : ''}>Đi muộn</option>
                                        <option value="excused" ${student.current_status === 'excused' ? 'selected' : ''}>Có phép</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" name="attendances[${index}][notes]" 
                                           class="form-control form-control-sm" 
                                           placeholder="Ghi chú..."
                                           value="${student.current_notes || ''}">
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Tổng số học viên:</strong> ${students.length} | 
                        ${data.attendance_exists ? 
                            '<span class="text-warning">Đã có điểm danh trước đó, dữ liệu sẽ được cập nhật</span>' : 
                            '<span class="text-success">Chưa có điểm danh cho buổi học này</span>'
                        }
                    </div>
                </div>
            </div>
        `}
    `;
    
    document.getElementById('attendanceContent').innerHTML = html;
    document.getElementById('saveAttendanceBtn').disabled = students.length === 0;
}

function showAttendanceError(message) {
    document.getElementById('attendanceContent').innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
        </div>
    `;
    document.getElementById('saveAttendanceBtn').disabled = true;
}

function markAllPresent() {
    document.querySelectorAll('.attendance-status').forEach(select => {
        select.value = 'present';
    });
}

function markAllAbsent() {
    document.querySelectorAll('.attendance-status').forEach(select => {
        select.value = 'absent';
    });
}

// Save attendance
document.getElementById('saveAttendanceBtn').addEventListener('click', function() {
    if (!currentSessionData) return;
    
    // Collect attendance data
    var attendances = [];
    var attendanceInputs = document.querySelectorAll('input[name*="[enrollment_id]"]');
    
    attendanceInputs.forEach((input, index) => {
        var enrollmentId = input.value;
        var status = document.querySelector(`select[name="attendances[${index}][status]"]`).value;
        var notes = document.querySelector(`input[name="attendances[${index}][notes]"]`).value;
        
        attendances.push({
            enrollment_id: enrollmentId,
            status: status,
            notes: notes
        });
    });
    
    // Disable button and show loading
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';
    
    fetch('{{ route("schedules.save-attendance") }}', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            schedule_id: currentSessionData.session.schedule_id,
            date: currentSessionData.session.date,
            attendances: attendances
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            alert(`Điểm danh đã được lưu thành công!\nTổng: ${data.total_students} học viên\nCó mặt: ${data.present_count}\nVắng mặt: ${data.absent_count}`);
            
            // Close modal
            attendanceModal.hide();
            
            // Refresh calendar
            if (typeof calendar !== 'undefined') {
                calendar.refetchEvents();
            }
        } else {
            alert('Có lỗi xảy ra: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error saving attendance:', error);
    })
    .finally(() => {
        // Re-enable button
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-save me-1"></i>Lưu điểm danh';
    });
});
</script>
@endpush
