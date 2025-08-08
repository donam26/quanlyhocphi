@extends('layouts.app')
@section('page-title', 'Điểm danh')

@section('breadcrumb')
<li class="breadcrumb-item active">Điểm danh</li>
@endsection

@section('page-actions')
<button class="btn btn-sm btn-info me-2" id="expand-all">
    <i class="fas fa-expand-arrows-alt"></i> Mở rộng tất cả
</button>
<button class="btn btn-sm btn-secondary me-2" id="collapse-all">
    <i class="fas fa-compress-arrows-alt"></i> Thu gọn tất cả
</button>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/course-tree.css') }}">
<style>
.attendance-count {
    background-color: #28a745 !important;
    color: #fff !important;
    font-weight: bold;
    min-width: 20px;
    text-align: center;
}

.attendance-tree .tree-item {
    cursor: pointer;
    transition: all 0.3s ease;
}

.attendance-tree .tree-item:hover {
    background-color: #e3f2fd;
    transform: translateX(5px);
}

.attendance-tree .tree-item.selected {
    background-color: #bbdefb;
    border-left-color: #2196f3;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
}

/* Layout 2 cột cho trang attendance */
.attendance-layout {
    display: flex;
    gap: 20px;
    min-height: 500px;
}

.attendance-tree-column {
    flex: 0 0 400px;
    max-width: 400px;
}

.attendance-form-column {
    flex: 1;
    min-width: 0;
}

.attendance-tree-card {
    height: 600px;
    display: flex;
    flex-direction: column;
}

.attendance-tree-card .card-body {
    flex: 1;
    overflow: hidden;
    padding: 15px;
}

.attendance-tree-container {
    height: 100%;
    overflow-y: auto;
    padding-right: 10px;
}

.attendance-form-card {
    height: 600px;
    display: flex;
    flex-direction: column;
}

.attendance-form-card .card-body {
    flex: 1;
    overflow: hidden;
    padding: 15px;
}

.attendance-form-container {
    height: 100%;
    overflow-y: auto;
    padding-right: 10px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.empty-state i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 20px;
}

/* Responsive cho layout 2 cột */
@media (max-width: 1200px) {
    .attendance-tree-column {
        flex: 0 0 350px;
        max-width: 350px;
    }
}

@media (max-width: 992px) {
    .attendance-layout {
        flex-direction: column;
    }
    
    .attendance-tree-column {
        flex: none;
        max-width: none;
    }
    
    .attendance-tree-card,
    .attendance-form-card {
        height: 400px;
    }
}

/* Attendance form styles */
.attendance-table {
    font-size: 0.9rem;
}

.attendance-table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
}

.attendance-table td {
    vertical-align: middle;
}

.status-select {
    min-width: 120px;
}

.status-present { background-color: #d4edda; }
.status-absent { background-color: #f8d7da; }
.status-late { background-color: #fff3cd; }
.status-excused { background-color: #d1ecf1; }

.date-selector {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
}
</style>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        @if($rootItems->isEmpty())
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Chưa có ngành học nào được tạo. Hãy tạo ngành học đầu tiên.
            </div>
        @else
            <div class="tab-and-search-container">
                <!-- Tab navigation -->
                <div class="tab-container">
                    <ul class="nav nav-tabs course-tabs" id="courseTab" role="tablist">
                        @foreach($rootItems as $index => $rootItem)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link @if(($currentRootItem && $currentRootItem->id == $rootItem->id) || (!$currentRootItem && $index === 0)) active @endif" 
                                        id="tab-{{ $rootItem->id }}" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#content-{{ $rootItem->id }}" 
                                        type="button" 
                                        role="tab" 
                                        aria-controls="content-{{ $rootItem->id }}" 
                                        aria-selected="@if(($currentRootItem && $currentRootItem->id == $rootItem->id) || (!$currentRootItem && $index === 0)) true @else false @endif">
                                    {{ $rootItem->name }}
                                    <span class="attendance-count badge ms-2" id="tab-count-{{ $rootItem->id }}">0</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
                
                <!-- Date selector -->
                <div class="search-container">
                    <input type="date" id="attendance-date" class="form-control" value="{{ now()->format('Y-m-d') }}">
                    <span class="search-clear"><i class="fas fa-calendar-alt"></i></span>
                </div>
            </div>
            
            <!-- Tab content -->
            <div class="tab-content" id="courseTabContent">
                @foreach($rootItems as $index => $rootItem)
                    <div class="tab-pane fade @if(($currentRootItem && $currentRootItem->id == $rootItem->id) || (!$currentRootItem && $index === 0)) show active @endif" 
                         id="content-{{ $rootItem->id }}" 
                         role="tabpanel" 
                         aria-labelledby="tab-{{ $rootItem->id }}"
                         data-root-id="{{ $rootItem->id }}">
                        
                        <div class="attendance-layout">
                            <!-- Cây khóa học bên trái -->
                            <div class="attendance-tree-column">
                                <div class="card attendance-tree-card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-sitemap"></i> 
                                            Khóa học - {{ $rootItem->name }}
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="attendance-tree-container">
                                            <ul class="course-tree attendance-tree">
                                                @if($currentRootItem && $currentRootItem->id == $rootItem->id)
                                                    {{-- Nếu đang ở tab cụ thể, chỉ hiển thị các con trực tiếp --}}
                                                    @foreach($rootItem->children->sortBy('order_index') as $childItem)
                                                        <li>
                                                            <div class="tree-item level-2 attendance-course-item {{ $childItem->active ? 'active' : 'inactive' }}" 
                                                                 data-id="{{ $childItem->id }}" 
                                                                 data-course-name="{{ $childItem->name }}">
                                                                <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#attendance-children-{{ $childItem->id }}">
                                                                    <i class="fas fa-minus-circle"></i>
                                                                </span>
                                                                <span class="item-icon"><i class="fas fa-book"></i></span>
                                                                <span class="course-link">{{ $childItem->name }}</span>
                                                                <span class="attendance-count badge ms-auto" id="attendance-count-{{ $childItem->id }}">0</span>
                                                            </div>
                                                            @include('attendance.partials.attendance-children-tree', ['children' => $childItem->children, 'parentId' => $childItem->id])
                                                        </li>
                                                    @endforeach
                                                @else
                                                    {{-- Hiển thị đầy đủ cả nút gốc và con --}}
                                                    <li>
                                                        <div class="tree-item level-1 attendance-course-item {{ $rootItem->active ? 'active' : 'inactive' }}" 
                                                             data-id="{{ $rootItem->id }}" 
                                                             data-course-name="{{ $rootItem->name }}">
                                                            <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#attendance-tab-children-{{ $rootItem->id }}">
                                                                <i class="fas fa-minus-circle"></i>
                                                            </span>
                                                            <span class="item-icon"><i class="fas fa-graduation-cap"></i></span>
                                                            <span class="course-link">{{ $rootItem->name }}</span>
                                                            <span class="attendance-count badge ms-auto" id="attendance-count-{{ $rootItem->id }}">0</span>
                                                        </div>
                                                        @include('attendance.partials.attendance-children-tree', ['children' => $rootItem->children, 'parentId' => $rootItem->id, 'tabPrefix' => 'attendance-tab-'])
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Form điểm danh bên phải -->
                            <div class="attendance-form-column">
                                <div class="card attendance-form-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <i class="fas fa-user-check"></i> 
                                            Điểm danh: <span id="selected-course-name-{{ $rootItem->id }}">Chọn khóa học</span>
                                        </h6>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary refresh-attendance-list" data-root-id="{{ $rootItem->id }}">
                                                <i class="fas fa-sync-alt"></i> Làm mới
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="attendance-form-container-{{ $rootItem->id }}" class="attendance-form-container">
                                            <div class="empty-state">
                                                <i class="fas fa-hand-pointer"></i>
                                                <h5>Chọn một khóa học</h5>
                                                <p class="text-muted">Chọn một khóa học từ cây bên trái để thực hiện điểm danh</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
.course-tree-container {
    max-height: 600px;
    overflow-y: auto;
}

.course-tree {
    list-style: none;
    padding: 0;
    margin: 0;
}

.course-tree li {
    margin: 0;
    padding: 0;
}

.tree-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.2s ease;
}

.tree-item:hover {
    background-color: #f8f9fa;
}

.tree-item.selected {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
}

.tree-item.level-2 {
    padding-left: 24px;
}

.tree-item.level-3 {
    padding-left: 36px;
}

.tree-item.level-4 {
    padding-left: 48px;
}

.toggle-icon {
    width: 20px;
    text-align: center;
    margin-right: 8px;
    cursor: pointer;
    color: #666;
}

.toggle-icon-placeholder {
    width: 20px;
    margin-right: 8px;
}

.item-icon {
    margin-right: 8px;
    color: #666;
}

.course-link {
    flex: 1;
    font-weight: 500;
}

.attendance-count {
    background-color: #28a745;
    color: white;
    font-size: 0.75rem;
    min-width: 20px;
    text-align: center;
}

.tree-item.inactive {
    opacity: 0.6;
}

.tree-item.inactive .course-link {
    text-decoration: line-through;
}

/* Attendance form styles */
.attendance-table {
    font-size: 0.9rem;
}

.attendance-table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
}

.attendance-table td {
    vertical-align: middle;
}

.status-select {
    min-width: 120px;
}

.status-present { background-color: #d4edda; }
.status-absent { background-color: #f8d7da; }
.status-late { background-color: #fff3cd; }
.status-excused { background-color: #d1ecf1; }

.quick-actions {
    gap: 0.5rem;
}

.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

.stats-card .card-body {
    padding: 1rem;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let selectedCourseId = null;
    let selectedCourseName = '';
    let currentRootId = null;
    let currentDate = $('#attendance-date').val();
    
    // Xử lý expand/collapse all
    $('#expand-all').on('click', function() {
        $('.collapse').addClass('show');
        $('.toggle-icon i').removeClass('fa-plus-circle').addClass('fa-minus-circle');
    });
    
    $('#collapse-all').on('click', function() {
        $('.collapse').removeClass('show');
        $('.toggle-icon i').removeClass('fa-minus-circle').addClass('fa-plus-circle');
    });
    
    // Xử lý toggle icon khi collapse/expand
    $(document).on('shown.bs.collapse', '.collapse', function() {
        $(this).prev().find('.toggle-icon i').removeClass('fa-plus-circle').addClass('fa-minus-circle');
    });
    
    $(document).on('hidden.bs.collapse', '.collapse', function() {
        $(this).prev().find('.toggle-icon i').removeClass('fa-minus-circle').addClass('fa-plus-circle');
    });
    
    // Load số lượng học viên cho tất cả khóa học khi trang load
    loadAllAttendanceCounts();
    
    // Xử lý click vào khóa học trong danh sách điểm danh
    $(document).on('click', '.attendance-course-item', function() {
        const rootId = $(this).closest('.tab-pane').data('root-id');
        
        // Bỏ chọn tất cả trong tab hiện tại
        $(`.tab-pane[data-root-id="${rootId}"] .attendance-course-item`).removeClass('selected');
        $(this).addClass('selected');
        
        selectedCourseId = $(this).data('id');
        selectedCourseName = $(this).data('course-name');
        currentRootId = rootId;
        
        $(`#selected-course-name-${rootId}`).text(selectedCourseName);
        loadAttendanceForm(selectedCourseId, rootId);
    });
    
    // Xử lý thay đổi ngày
    $('#attendance-date').on('change', function() {
        currentDate = $(this).val();
        if (selectedCourseId && currentRootId) {
            loadAttendanceForm(selectedCourseId, currentRootId);
        }
    });
    
    // Load form điểm danh
    function loadAttendanceForm(courseId, rootId) {
        $(`#attendance-form-container-${rootId}`).html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="mt-2">Đang tải danh sách học viên...</p>
            </div>
        `);
        
        $.ajax({
            url: `/course-items/${courseId}/attendance-students`,
            method: 'GET',
            data: { date: currentDate },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .done(function(response) {
                console.log('Response:', response);
                if (response.success) {
                    displayAttendanceForm(response, rootId);
                    updateAttendanceCount(courseId, response.students.length);
                } else {
                    showError('Không thể tải danh sách học viên', rootId);
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Error loading attendance students:', error);
                $(`#attendance-form-container-${rootId}`).html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Có lỗi xảy ra khi tải danh sách học viên: ${error}
                    </div>
                `);
            });
    }
    
    // Hiển thị form điểm danh
    function displayAttendanceForm(data, rootId) {
        const course = data.course;
        const students = data.students;
        
        if (students.length === 0) {
            $(`#attendance-form-container-${rootId}`).html(`
                <div class="empty-state">
                    <i class="fas fa-user-times"></i>
                    <h5>Không có học viên</h5>
                    <p class="text-muted">Chưa có học viên nào ghi danh vào khóa học này</p>
                </div>
            `);
            return;
        }
        
        let html = `
            <form id="attendance-form-${rootId}" data-root-id="${rootId}">
                <input type="hidden" name="course_item_id" value="${course.id}">
                <input type="hidden" name="date" value="${data.date}">
                
                <!-- Date info -->
                <div class="date-selector">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1">${course.name}</h6>
                            <small class="text-muted">${course.path || ''}</small>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-primary">
                                <i class="fas fa-calendar me-1"></i>${data.day_name}, ${data.formatted_date}
                            </span>
                            <br>
                            <span class="badge bg-info mt-1">
                                <i class="fas fa-users me-1"></i>${students.length} học viên
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Quick actions -->
                <div class="mb-3">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-success mark-all-status" data-status="present" data-root-id="${rootId}">
                            <i class="fas fa-check-circle me-1"></i>Tất cả có mặt
                        </button>
                        <button type="button" class="btn btn-warning mark-all-status" data-status="absent" data-root-id="${rootId}">
                            <i class="fas fa-times-circle me-1"></i>Tất cả vắng mặt
                        </button>
                        <button type="button" class="btn btn-info mark-all-status" data-status="late" data-root-id="${rootId}">
                            <i class="fas fa-clock me-1"></i>Tất cả đi muộn
                        </button>
                    </div>
                </div>

                <!-- Attendance Table -->
                <div class="table-responsive">
                    <table class="table table-striped attendance-table">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="25%">Họ tên</th>
                                <th width="15%">Số điện thoại</th>
                                <th width="20%">Trạng thái</th>
                                <th width="35%">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
        `;
        
        students.forEach(function(student, index) {
            html += `
                <tr class="status-${student.current_status}" data-enrollment-id="${student.enrollment_id}">
                    <td>${index + 1}</td>
                    <td>
                        <strong>${student.student_name}</strong>
                        <input type="hidden" name="attendances[${index}][enrollment_id]" value="${student.enrollment_id}">
                    </td>
                    <td>${student.student_phone || ''}</td>
                    <td>
                        <select name="attendances[${index}][status]" class="form-select form-select-sm status-select">
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
            `;
        });
        
        html += `
                        </tbody>
                    </table>
                </div>

                <!-- Save Button -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted">
                        ${data.attendance_exists ? 
                            '<i class="fas fa-info-circle me-1"></i>Đã có điểm danh trước đó, dữ liệu sẽ được cập nhật' : 
                            '<i class="fas fa-plus-circle me-1"></i>Điểm danh mới cho buổi học này'
                        }
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Lưu điểm danh
                    </button>
                </div>
            </form>
        `;
        
        $(`#attendance-form-container-${rootId}`).html(html);
    }
    
    // Cập nhật số lượng học viên
    function updateAttendanceCount(courseId, count) {
        $(`#attendance-count-${courseId}`).text(count);
        
        // Cập nhật tổng số cho tab
        updateTabCount();
    }
    
    // Cập nhật số lượng cho tab
    function updateTabCount() {
        $('.course-tabs .nav-link').each(function() {
            const tabId = $(this).attr('id').replace('tab-', '');
            let totalCount = 0;
            
            $(`.attendance-count[id^="attendance-count-"]`).each(function() {
                const courseId = $(this).attr('id').replace('attendance-count-', '');
                if ($(this).closest('.tab-pane').data('root-id') == tabId) {
                    totalCount += parseInt($(this).text()) || 0;
                }
            });
            
            $(`#tab-count-${tabId}`).text(totalCount);
        });
    }
    
    // Load số lượng học viên cho tất cả khóa học
    function loadAllAttendanceCounts() {
        $('.attendance-course-item').each(function() {
            const courseId = $(this).data('id');
            $.ajax({
                url: `/course-items/${courseId}/attendance-students`,
                method: 'GET',
                data: { date: currentDate },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .done(function(response) {
                    if (response.success) {
                        updateAttendanceCount(courseId, response.students.length);
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error(`Error loading count for course ${courseId}:`, error);
                });
        });
    }
    
    // Xử lý thay đổi trạng thái
    $(document).on('change', '.status-select', function() {
        const row = $(this).closest('tr');
        const status = $(this).val();
        
        // Remove all status classes
        row.removeClass('status-present status-absent status-late status-excused');
        
        // Add new status class
        row.addClass(`status-${status}`);
    });
    
    // Xử lý mark all status
    $(document).on('click', '.mark-all-status', function() {
        const status = $(this).data('status');
        const rootId = $(this).data('root-id');
        
        $(`#attendance-form-${rootId} .status-select`).each(function() {
            $(this).val(status).trigger('change');
        });
    });
    
    // Xử lý submit form
    $(document).on('submit', '[id^="attendance-form-"]', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const rootId = form.data('root-id');
        const formData = new FormData(form[0]);
        
        // Convert attendances to JSON
        const attendances = [];
        const attendanceInputs = form.find('input[name*="[enrollment_id]"]');
        
        attendanceInputs.each(function(index) {
            const enrollmentId = $(this).val();
            const status = form.find(`select[name="attendances[${index}][status]"]`).val();
            const notes = form.find(`input[name="attendances[${index}][notes]"]`).val();
            
            attendances.push({
                enrollment_id: enrollmentId,
                status: status,
                notes: notes
            });
        });

        const submitBtn = form.find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...');

        $.ajax({
            url: '{{ route("attendance.save-from-tree") }}',
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                _token: $('meta[name="csrf-token"]').attr('content'),
                course_item_id: formData.get('course_item_id'),
                date: formData.get('date'),
                attendances: attendances
            })
        })
        .done(function(response) {
            if (response.success) {
                showAlert('success', `Điểm danh đã được lưu thành công!\n\nTổng: ${response.total_students} học viên\nCó mặt: ${response.present_count}\nVắng mặt: ${response.absent_count}\nĐi muộn: ${response.late_count}\nCó phép: ${response.excused_count}`);
                
                // Reload form to show updated data
                if (selectedCourseId && currentRootId) {
                    loadAttendanceForm(selectedCourseId, currentRootId);
                }
            } else {
                showAlert('danger', 'Có lỗi xảy ra: ' + response.message);
            }
        })
        .fail(function(xhr) {
            showAlert('danger', 'Có lỗi xảy ra: ' + (xhr.responseJSON?.message || 'Lỗi không xác định'));
        })
        .finally(function() {
            submitBtn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Lưu điểm danh');
        });
    });
    
    // Làm mới danh sách
    $(document).on('click', '.refresh-attendance-list', function() {
        const rootId = $(this).data('root-id');
        if (selectedCourseId && currentRootId === rootId) {
            loadAttendanceForm(selectedCourseId, rootId);
        }
    });
    
    function showError(message, rootId) {
        $(`#attendance-form-container-${rootId}`).html(`
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            </div>
        `);
    }
    
    // Hiển thị thông báo
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('body').append(alertHtml);
        
        // Tự động ẩn sau 5 giây
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
});
</script>
@endpush 