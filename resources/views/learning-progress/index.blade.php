@extends('layouts.app')

@section('page-title', 'Quản lý tiến độ học tập')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Quản lý tiến độ học tập</h5>
                </div>
                <div class="card-body">
                    <h6 class="text-muted mb-3">Danh sách các khóa học đang có lộ trình chưa hoàn thành</h6>

                    @if(count($incompleteCoursesData) === 0)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Không có khóa học nào chưa hoàn thành lộ trình.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach($incompleteCoursesData as $courseData)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">{{ $courseData['course']->name }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Tiến độ hoàn thành</span>
                                <span>{{ $courseData['progress_percentage'] }}%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: {{ $courseData['progress_percentage'] }}%;" 
                                     aria-valuenow="{{ $courseData['progress_percentage'] }}" 
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-users text-primary me-2"></i>
                                    <div>
                                        <div class="small text-muted">Học viên</div>
                                        <div class="fw-bold">{{ $courseData['total_students'] }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-list-check text-warning me-2"></i>
                                    <div>
                                        <div class="small text-muted">Lộ trình</div>
                                        <div class="fw-bold">{{ $courseData['completed_pathways'] }}/{{ $courseData['total_pathways'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-primary" onclick="showCourseProgress({{ $courseData['course']->id }})">
                                <i class="fas fa-eye me-1"></i> Xem chi tiết
                            </button>
                            <a href="{{ route('learning-paths.edit', $courseData['course']->id) }}" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-edit me-1"></i> Chỉnh sửa lộ trình
                            </a>
                        </div>
                    </div>
                    <div class="card-footer text-muted">
                        <small><i class="fas fa-money-bill-wave me-1"></i> Học phí: {{ number_format($courseData['course']->fee) }} đồng</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modal hiển thị chi tiết tiến độ học tập của khóa học -->
<div class="modal fade" id="courseProgressModal" tabindex="-1" aria-labelledby="courseProgressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="courseProgressModalLabel">Tiến độ học tập: <span id="modal-course-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Phần loading -->
                <div id="course-progress-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2">Đang tải thông tin tiến độ học tập...</p>
                </div>
                
                <!-- Nội dung chi tiết -->
                <div id="course-progress-content" style="display: none;">
                    <div class="mb-4">
                        <div id="course-info" class="mb-3"></div>
                        
                        <div id="success-message" class="alert alert-success" style="display: none;">
                            Đã cập nhật tiến độ học tập thành công!
                        </div>
                        
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Lộ trình học tập</h5>
                                <span class="me-3">Tổng số lộ trình: <span id="total-paths-count">0</span></span>
                            </div>
                            <div class="card-body">
                                <div id="learning-paths-list"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="edit-learning-path-btn" class="btn btn-primary">
                    <i class="fas fa-edit me-1"></i> Chỉnh sửa lộ trình
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal hiển thị chi tiết tiến độ học tập của học viên -->
<div class="modal fade" id="studentProgressModal" tabindex="-1" aria-labelledby="studentProgressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentProgressModalLabel">Tiến độ học tập: <span id="modal-student-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Phần loading -->
                <div id="student-progress-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2">Đang tải thông tin tiến độ học tập...</p>
                </div>
                
                <!-- Nội dung chi tiết -->
                <div id="student-progress-content" style="display: none;">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-2 text-center">
                                            <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                                                <span id="student-initial"></span>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <p><strong>Họ và tên:</strong> <span id="student-fullname"></span></p>
                                            <p><strong>Số điện thoại:</strong> <span id="student-phone"></span></p>
                                            <p><strong>Email:</strong> <span id="student-email"></span></p>
                                        </div>
                                        <div class="col-md-5">
                                            <p><strong>Ngày sinh:</strong> <span id="student-dob"></span></p>
                                            <p><strong>Địa chỉ:</strong> <span id="student-address"></span></p>
                                            <p><strong>Số khóa học đã đăng ký:</strong> <span id="student-courses-count"></span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Khóa học đã đăng ký</h5>
                                </div>
                                <div class="card-body">
                                    <div id="enrollment-select-container"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="selected-enrollment-progress" style="display: none;">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Tiến độ học tập: <span id="selected-course-name"></span></h5>
                                        <div>
                                            <button class="btn btn-success" id="save-progress-btn">
                                                <i class="fas fa-save"></i> Lưu thay đổi
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="enrollment-progress-container"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="view-student-btn" class="btn btn-outline-info">
                    <i class="fas fa-user me-1"></i> Hồ sơ học viên
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
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
        font-size: 32px;
    }
    .learning-path-item {
        margin-bottom: 10px;
        padding: 15px;
        border-radius: 5px;
        background-color: #f8f9fa;
        transition: background-color 0.3s ease;
    }
    .learning-path-item.completed {
        background-color: #e8f5e9;
    }
    .form-check-input {
        width: 20px;
        height: 20px;
        margin-top: 2px;
        cursor: pointer;
    }
    .form-check-label {
        margin-left: 8px;
    }
    .progress {
        border-radius: 0.5rem;
    }
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
    }
</style>
@endsection

@push('scripts')
<script>
// Biến lưu trữ các thay đổi
let changedProgress = [];
let currentCourseId = null;
let currentStudentId = null;

// Hiển thị chi tiết tiến độ học tập của khóa học
function showCourseProgress(courseId) {
    currentCourseId = courseId;
    
    // Hiển thị modal
    const modal = new bootstrap.Modal(document.getElementById('courseProgressModal'));
    modal.show();
    
    // Hiển thị loading và ẩn nội dung
    $('#course-progress-loading').show();
    $('#course-progress-content').hide();
    $('#success-message').hide();
    
    // Gọi API để lấy thông tin tiến độ
    $.ajax({
        url: `/api/learning-progress/course/${courseId}`,
        method: 'GET',
        success: function(response) {
            if (!response.success) {
                alert('Không thể tải thông tin tiến độ học tập');
                return;
            }
            
            const data = response.data;
            const courseItem = data.courseItem;
            const learningPaths = data.learningPaths;
            const pathCompletionStats = data.pathCompletionStats;
            
            // Cập nhật tiêu đề modal
            $('#modal-course-name').text(courseItem.name);
            
            // Cập nhật thông tin khóa học
            $('#course-info').html(`
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Cấp độ trong cây:</strong> ${courseItem.level}</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <p><strong>Học phí:</strong> ${new Intl.NumberFormat('vi-VN').format(courseItem.fee)} đồng</p>
                    </div>
                </div>
            `);
            
            // Cập nhật link chỉnh sửa lộ trình
            $('#edit-learning-path-btn').attr('href', `/course-items/${courseItem.id}/learning-paths/edit`);
            
            // Cập nhật danh sách lộ trình
            if (learningPaths.length === 0) {
                $('#learning-paths-list').html(`
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Khóa học này chưa có lộ trình học tập nào.
                    </div>
                `);
            } else {
                $('#total-paths-count').text(learningPaths.length);
                
                let pathsHtml = '';
                
                learningPaths.forEach((path, index) => {
                    const stats = pathCompletionStats[path.id] || null;
                    const isCompleted = stats && stats.completed_count > 0;
                    
                    pathsHtml += `
                        <div class="learning-path-item ${isCompleted ? 'completed' : ''}" id="path-item-${path.id}">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input progress-checkbox" 
                                    id="path-${path.id}" 
                                    data-path-id="${path.id}"
                                    data-course-id="${courseItem.id}"
                                    ${isCompleted ? 'checked' : ''}>
                                <label class="form-check-label" for="path-${path.id}">
                                    <div class="fw-bold">Buổi ${index + 1}</div>
                                    <div class="text-muted">${path.title || path.id}</div>
                                    <div class="text-success completion-status" id="status-${path.id}" style="${isCompleted ? '' : 'display: none;'}">
                                        <i class="fas fa-check-circle"></i> Đã hoàn thành
                                    </div>
                                </label>
                            </div>
                        </div>
                    `;
                });
                
                $('#learning-paths-list').html(pathsHtml);
                
                // Kích hoạt các sự kiện cho các checkbox
                $('.progress-checkbox').change(function() {
                    const pathId = $(this).data('path-id');
                    const courseId = $(this).data('course-id');
                    const isCompleted = $(this).is(':checked');
                    const pathItem = $('#path-item-' + pathId);
                    const completionStatus = $('#status-' + pathId);
                    
                    // Hiển thị loading
                    $(this).prop('disabled', true);
                    
                    // Gửi AJAX request để cập nhật trạng thái
                    $.ajax({
                        url: '/api/learning-progress/update-path-status',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            path_id: pathId,
                            course_id: courseId,
                            is_completed: isCompleted ? 1 : 0
                        },
                        success: function(response) {
                            if (response.success) {
                                // Cập nhật giao diện
                                if (isCompleted) {
                                    pathItem.addClass('completed');
                                    completionStatus.show();
                                } else {
                                    pathItem.removeClass('completed');
                                    completionStatus.hide();
                                }
                                
                                // Kiểm tra xem tất cả các lộ trình đã hoàn thành chưa
                                const totalCheckboxes = $('.progress-checkbox').length;
                                const checkedCheckboxes = $('.progress-checkbox:checked').length;
                                
                                if (totalCheckboxes === checkedCheckboxes) {
                                    // Nếu tất cả lộ trình đã hoàn thành, ẩn khóa học trên giao diện chính
                                    $('#success-message').html('<i class="fas fa-check-circle"></i> Đã hoàn thành tất cả lộ trình học tập!').fadeIn();
                                    
                                    // Đợi thông báo hiển thị rồi đóng modal và ẩn card khóa học
                                    setTimeout(function() {
                                        // Đóng modal sau 1 giây
                                        setTimeout(function() {
                                            $('.btn-close').click();
                                            // Tìm card chứa khóa học này và ẩn đi với hiệu ứng
                                            $(`.card:has(button[onclick="showCourseProgress(${courseId})")`).closest('.col-md-4').fadeOut('slow', function() {
                                                $(this).remove();
                                                
                                                // Kiểm tra nếu không còn khóa học nào hiển thị
                                                if ($('.col-md-4:visible').length === 0) {
                                                    $('.row:has(.col-md-4)').after(`
                                                        <div class="alert alert-info mt-3">
                                                            <i class="fas fa-info-circle me-2"></i> Không có khóa học nào chưa hoàn thành lộ trình.
                                                        </div>
                                                    `);
                                                }
                                            });
                                        }, 1000);
                                    }, 1500);
                                } else {
                                    // Hiển thị thông báo thành công thông thường
                                    $('#success-message').fadeIn().delay(2000).fadeOut();
                                }
                            } else {
                                alert('Có lỗi xảy ra: ' + response.message);
                                // Khôi phục trạng thái checkbox nếu có lỗi
                                $('.progress-checkbox[data-path-id="' + pathId + '"]').prop('checked', !isCompleted);
                            }
                        },
                        error: function() {
                            alert('Có lỗi xảy ra khi cập nhật tiến độ học tập');
                            // Khôi phục trạng thái checkbox nếu có lỗi
                            $('.progress-checkbox[data-path-id="' + pathId + '"]').prop('checked', !isCompleted);
                        },
                        complete: function() {
                            // Bỏ disabled cho checkbox
                            $('.progress-checkbox[data-path-id="' + pathId + '"]').prop('disabled', false);
                        }
                    });
                });
            }
            
            // Ẩn loading và hiện nội dung
            $('#course-progress-loading').hide();
            $('#course-progress-content').show();
        },
        error: function() {
            alert('Có lỗi xảy ra khi tải thông tin tiến độ học tập');
            $('#course-progress-loading').hide();
        }
    });
}

// Hiển thị chi tiết tiến độ học tập của học viên
function showStudentProgress(studentId, enrollmentId = null) {
    currentStudentId = studentId;
    
    // Hiển thị modal
    const modal = new bootstrap.Modal(document.getElementById('studentProgressModal'));
    modal.show();
    
    // Hiển thị loading và ẩn nội dung
    $('#student-progress-loading').show();
    $('#student-progress-content').hide();
    $('#selected-enrollment-progress').hide();
    
    // Xóa mảng thay đổi
    changedProgress = [];
    
    // Gọi API để lấy thông tin tiến độ
    $.ajax({
        url: `/api/learning-progress/student/${studentId}`,
        method: 'GET',
        data: enrollmentId ? { enrollment_id: enrollmentId } : {},
        success: function(response) {
            if (!response.success) {
                alert('Không thể tải thông tin tiến độ học tập');
                return;
            }
            
            const data = response.data;
            const student = data.student;
            const enrollments = data.enrollments;
            const selectedEnrollment = data.selectedEnrollment;
            const learningPaths = data.learningPaths;
            const progress = data.progress;
            
            // Cập nhật tiêu đề modal và thông tin học viên
            $('#modal-student-name').text(student.full_name);
            $('#student-initial').text(student.full_name.charAt(0));
            $('#student-fullname').text(student.full_name);
            $('#student-phone').text(student.phone);
            $('#student-email').text(student.email || 'Không có');
            $('#student-dob').text(student.formatted_date_of_birth || 'Không có');
            $('#student-address').text(student.address || 'Không có');
            $('#student-courses-count').text(enrollments.length);
            
            // Cập nhật URL cho hồ sơ học viên
            $('#view-student-btn').attr('href', `/students/${student.id}`);
            
            // Cập nhật dropdown khóa học
            if (enrollments.length === 0) {
                $('#enrollment-select-container').html(`
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Học viên này chưa đăng ký khóa học nào.
                    </div>
                `);
            } else {
                let selectHtml = `
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="enrollment_id" class="form-label">Chọn khóa học</label>
                            <select name="enrollment_id" id="enrollment_id" class="form-select">
                                <option value="">-- Chọn khóa học --</option>
                `;
                
                enrollments.forEach(enrollment => {
                    selectHtml += `
                        <option value="${enrollment.id}" ${selectedEnrollment && selectedEnrollment.id == enrollment.id ? 'selected' : ''}>
                            ${enrollment.course_item.name}
                        </option>
                    `;
                });
                
                selectHtml += `
                            </select>
                        </div>
                    </div>
                `;
                
                $('#enrollment-select-container').html(selectHtml);
                
                // Kích hoạt sự kiện cho dropdown
                $('#enrollment_id').on('change', function() {
                    const selectedId = $(this).val();
                    if (selectedId) {
                        showStudentProgress(studentId, selectedId);
                    } else {
                        $('#selected-enrollment-progress').hide();
                    }
                });
            }
            
            // Hiển thị tiến độ học tập của khóa học đã chọn
            if (selectedEnrollment) {
                $('#selected-course-name').text(selectedEnrollment.course_item.name);
                
                // Hiển thị phần tiến độ học tập
                if (learningPaths.length === 0) {
                    $('#enrollment-progress-container').html(`
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Khóa học này chưa có lộ trình học tập nào.
                        </div>
                    `);
                } else {
                    // Tính toán phần trăm hoàn thành
                    const totalPaths = learningPaths.length;
                    const completedPaths = Object.values(progress).filter(p => p.is_completed).length;
                    const progressPercent = Math.round((completedPaths / totalPaths) * 100);
                    
                    let progressHtml = `
                        <div class="progress mb-4" style="height: 25px;">
                            <div class="progress-bar ${progressPercent >= 100 ? 'bg-success' : (progressPercent >= 50 ? 'bg-info' : 'bg-warning')}" 
                                role="progressbar" 
                                style="width: ${progressPercent}%;" 
                                aria-valuenow="${progressPercent}" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                ${progressPercent}% hoàn thành
                            </div>
                        </div>
                        
                        <div class="learning-paths-list">
                    `;
                    
                    learningPaths.forEach((path, index) => {
                        const pathProgress = progress[path.id] || null;
                        const isCompleted = pathProgress ? pathProgress.is_completed : false;
                        const completedDate = pathProgress && pathProgress.completed_at ? new Date(pathProgress.completed_at).toLocaleDateString('vi-VN') : null;
                        
                        progressHtml += `
                            <div class="learning-path-item ${isCompleted ? 'completed' : ''} p-3 mb-3 border rounded">
                                <div class="row align-items-center">
                                    <div class="col-md-1">
                                        <span class="badge bg-primary rounded-pill">${index + 1}</span>
                                    </div>
                                    <div class="col-md-7">
                                        <h5 class="mb-1">${path.title || 'Buổi ' + (index + 1)}</h5>
                                        ${path.description ? `<p class="text-muted mb-0">${path.description}</p>` : ''}
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input student-progress-toggle" 
                                                type="checkbox" 
                                                id="progress-${path.id}" 
                                                data-path-id="${path.id}" 
                                                data-enrollment-id="${selectedEnrollment.id}" 
                                                ${isCompleted ? 'checked' : ''}>
                                            <label class="form-check-label" for="progress-${path.id}">
                                                ${isCompleted ? 'Đã hoàn thành' : 'Chưa hoàn thành'}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        ${completedDate ? `<small class="text-muted">Hoàn thành: ${completedDate}</small>` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    progressHtml += `</div>`;
                    
                    $('#enrollment-progress-container').html(progressHtml);
                    
                    // Kích hoạt sự kiện cho các toggle
                    $('.student-progress-toggle').change(function() {
                        const pathId = $(this).data('path-id');
                        const enrollmentId = $(this).data('enrollment-id');
                        const isCompleted = $(this).is(':checked');
                        const pathItem = $(this).closest('.learning-path-item');
                        const statusLabel = $(this).next('label');
                        
                        // Cập nhật trạng thái hiển thị
                        if (isCompleted) {
                            pathItem.addClass('completed');
                            statusLabel.text('Đã hoàn thành');
                        } else {
                            pathItem.removeClass('completed');
                            statusLabel.text('Chưa hoàn thành');
                        }
                        
                        // Thêm vào mảng thay đổi
                        const existingIndex = changedProgress.findIndex(item => 
                            item.learning_path_id === pathId && item.enrollment_id === enrollmentId
                        );
                        
                        if (existingIndex !== -1) {
                            changedProgress[existingIndex].is_completed = isCompleted;
                        } else {
                            changedProgress.push({
                                learning_path_id: pathId,
                                enrollment_id: enrollmentId,
                                is_completed: isCompleted
                            });
                        }
                        
                        // Cập nhật thanh tiến độ
                        updateProgressBar();
                    });
                    
                    // Cập nhật sự kiện lưu thay đổi
                    $('#save-progress-btn').off('click').on('click', function() {
                        saveStudentProgress();
                    });
                }
                
                // Hiển thị phần tiến độ học tập
                $('#selected-enrollment-progress').show();
            } else {
                $('#selected-enrollment-progress').hide();
            }
            
            // Ẩn loading và hiện nội dung
            $('#student-progress-loading').hide();
            $('#student-progress-content').show();
        },
        error: function() {
            alert('Có lỗi xảy ra khi tải thông tin tiến độ học tập');
            $('#student-progress-loading').hide();
        }
    });
}

// Cập nhật thanh tiến độ
function updateProgressBar() {
    const totalPaths = $('.student-progress-toggle').length;
    const completedPaths = $('.student-progress-toggle:checked').length;
    const progressPercent = Math.round((completedPaths / totalPaths) * 100);
    
    const progressBar = $('.progress-bar');
    progressBar.css('width', `${progressPercent}%`);
    progressBar.attr('aria-valuenow', progressPercent);
    progressBar.text(`${progressPercent}% hoàn thành`);
    
    // Cập nhật màu sắc
    progressBar.removeClass('bg-success bg-info bg-warning');
    if (progressPercent >= 100) {
        progressBar.addClass('bg-success');
    } else if (progressPercent >= 50) {
        progressBar.addClass('bg-info');
    } else {
        progressBar.addClass('bg-warning');
    }
}

// Lưu tiến độ học tập
function saveStudentProgress() {
    if (changedProgress.length === 0) {
        alert('Không có thay đổi nào để lưu');
        return;
    }
    
    // Hiển thị nút loading
    const button = $('#save-progress-btn');
    const originalText = button.html();
    button.html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...');
    button.prop('disabled', true);
    
    // Gửi request AJAX để cập nhật
    $.ajax({
        url: '/api/learning-progress/update-bulk',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            progress_data: changedProgress
        },
        success: function(response) {
            if (response.success) {
                // Hiển thị thông báo thành công
                alert('Đã cập nhật tiến độ học tập thành công');
                // Xóa mảng thay đổi
                changedProgress = [];
            } else {
                alert('Có lỗi xảy ra: ' + response.message);
            }
        },
        error: function(xhr) {
            alert('Có lỗi xảy ra khi cập nhật tiến độ học tập');
            console.error(xhr);
        },
        complete: function() {
            // Khôi phục nút
            button.html(originalText);
            button.prop('disabled', false);
        }
    });
}
</script>
@endpush 