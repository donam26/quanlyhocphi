@extends('layouts.app')

@section('page-title', 'Lớp học chưa thanh toán đủ học phí')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Thanh toán</a></li>
    <li class="breadcrumb-item active">Lớp chưa thanh toán đủ</li>
@endsection

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>
                            Lớp học chưa thanh toán đủ học phí
                        </h5>
                        <div>
                            <button type="button" id="sendReminderBtn" class="btn btn-warning">
                                <i class="fas fa-envelope me-1"></i>
                                Gửi nhắc nhở đã chọn
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Hiển thị {{ count($courseEnrollments) }} lớp học có học viên chưa thanh toán đủ.
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="check-all"></th>
                                    <th>Lớp học</th>
                                    <th class="text-center">Học viên</th>
                                    <th class="text-center">Chưa đóng đủ</th>
                                    <th class="text-end">Tổng học phí</th>
                                    <th class="text-end">Đã thu</th>
                                    <th class="text-end">Còn thiếu</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($courseEnrollments as $courseItemId => $courseData)
                                    <tr class="table-info">
                                        <td>
                                            <input type="checkbox" class="course-checkbox" value="{{ $courseItemId }}">
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-graduation-cap text-primary me-2"></i>
                                                <div>
                                                    <strong>{{ $courseData['course_item']->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-layer-group me-1"></i>
                                                        Level {{ $courseData['course_item']->level }}
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary">{{ $courseData['total_students'] }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning">{{ $courseData['unpaid_students'] }}</span>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ number_format($courseData['total_fee']) }} đ</strong>
                                        </td>
                                        <td class="text-end text-success">
                                            <strong>{{ number_format($courseData['total_paid']) }} đ</strong>
                                        </td>
                                        <td class="text-end text-danger">
                                            <strong>{{ number_format($courseData['total_remaining']) }} đ</strong>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="showCourseDetailsModal({{ $courseItemId }})"
                                                    title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <a href="{{ route('payments.course', $courseItemId) }}" 
                                               class="btn btn-sm btn-primary" title="Quản lý thanh toán">
                                                <i class="fas fa-credit-card"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                                <h5>Tất cả lớp học đã thanh toán đủ học phí!</h5>
                                                <p>Không có lớp học nào còn thiếu học phí.</p>
                                            </div>
                                        </td>
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

<!-- Modal Chi tiết lớp học -->
<div class="modal fade" id="courseDetailsModal" tabindex="-1" aria-labelledby="courseDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="courseDetailsModalLabel">
                    <i class="fas fa-graduation-cap me-2"></i>
                    Chi tiết lớp học chưa thanh toán đủ
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="courseDetailsContent">
                    <!-- Nội dung sẽ được load bằng JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-warning" id="bulkReminderBtn" onclick="sendBulkReminder()">
                    <i class="fas fa-envelope me-1"></i>
                    Gửi nhắc nhở tất cả
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lịch sử thanh toán -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1" aria-labelledby="paymentHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentHistoryModalLabel">
                    <i class="fas fa-history me-2"></i>
                    Lịch sử thanh toán
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="paymentHistoryContent">
                    <!-- Nội dung sẽ được load bằng JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Xử lý checkbox chọn tất cả lớp học
        document.getElementById('check-all').addEventListener('change', function() {
            const courseCheckboxes = document.querySelectorAll('.course-checkbox');
            courseCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateSendReminderButton();
        });

        // Xử lý khi checkbox lớp học đơn lẻ thay đổi
        document.querySelectorAll('.course-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSendReminderButton();
                
                // Kiểm tra nếu tất cả đều được chọn
                const allChecked = Array.from(document.querySelectorAll('.course-checkbox')).every(cb => cb.checked);
                document.getElementById('check-all').checked = allChecked;
            });
        });
        
        function updateSendReminderButton() {
            const sendReminderBtn = document.getElementById('sendReminderBtn');
            const checkedCount = document.querySelectorAll('.course-checkbox:checked').length;
            
            if (checkedCount > 0) {
                sendReminderBtn.innerHTML = `<i class="fas fa-envelope me-1"></i> Gửi nhắc nhở (${checkedCount} lớp)`;
                sendReminderBtn.classList.remove('btn-secondary');
                sendReminderBtn.classList.add('btn-warning');
                sendReminderBtn.disabled = false;
            } else {
                sendReminderBtn.innerHTML = `<i class="fas fa-envelope me-1"></i> Gửi nhắc nhở đã chọn`;
                sendReminderBtn.classList.remove('btn-warning');
                sendReminderBtn.classList.add('btn-secondary');
                sendReminderBtn.disabled = true;
            }
        }
        
        // Xử lý nút gửi nhắc nhở đã chọn
        document.getElementById('sendReminderBtn').addEventListener('click', function() {
            const selectedCourses = Array.from(document.querySelectorAll('.course-checkbox:checked')).map(cb => cb.value);
            
            if (selectedCourses.length === 0) {
                Swal.fire({
                    title: 'Chưa chọn lớp học',
                    text: 'Vui lòng chọn ít nhất một lớp học để gửi nhắc nhở.',
                    icon: 'warning',
                    confirmButtonText: 'Đóng'
                });
                return;
            }
            
            Swal.fire({
                title: 'Xác nhận gửi nhắc nhở',
                text: `Bạn có chắc chắn muốn gửi nhắc nhở cho học viên trong ${selectedCourses.length} lớp đã chọn?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Gửi nhắc nhở',
                cancelButtonText: 'Hủy bỏ'
            }).then((result) => {
                if (result.isConfirmed) {
                    sendCourseReminders(selectedCourses);
                }
            });
        });
        
        // Cập nhật trạng thái ban đầu của nút gửi nhắc nhở
        updateSendReminderButton();
    });

    // Hiển thị chi tiết lớp học
    function toggleCourseDetails(courseItemId) {
        const courseDetailsRow = document.getElementById(`course-details-${courseItemId}`);
        const courseCheckbox = document.querySelector(`.course-checkbox[value="${courseItemId}"]`);
        
        if (courseDetailsRow.style.display === 'none') {
            courseDetailsRow.style.display = 'table-row';
            courseCheckbox.checked = true; // Tự động chọn lớp học nếu mở chi tiết
        } else {
            courseDetailsRow.style.display = 'none';
            courseCheckbox.checked = false; // Tự động bỏ chọn lớp học nếu đóng chi tiết
        }
        updateSendReminderButton(); // Cập nhật trạng thái nút gửi nhắc nhở đã chọn
    }

    // Sửa lại nút thanh toán từ <a href> thành <button> để hiển thị modal
    function showCourseDetailsModal(courseItemId) {
        // Hiển thị loading
        const modal = new bootstrap.Modal(document.getElementById('courseDetailsModal'));
        document.getElementById('courseDetailsContent').innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Đang tải thông tin...</p>
            </div>
        `;
        modal.show();
        
        // Lấy dữ liệu từ PHP
        const courseEnrollments = @json($courseEnrollments);
        const courseData = courseEnrollments[courseItemId];
        
        if (!courseData) {
            document.getElementById('courseDetailsContent').innerHTML = `
                <div class="alert alert-danger">Không tìm thấy thông tin lớp học.</div>
            `;
            return;
        }
        
        // Tạo HTML hiển thị chi tiết
        let html = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-graduation-cap me-2"></i>
                                ${courseData.course_item.name}
                            </h5>
                            <p class="mb-1">
                                <i class="fas fa-layer-group me-1"></i>
                                Level ${courseData.course_item.level}
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-users me-1"></i>
                                ${courseData.total_students} học viên
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-6">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h6>Chưa đóng đủ</h6>
                                    <h4>${courseData.unpaid_students}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h6>Còn thiếu</h6>
                                    <h4>${formatCurrency(courseData.total_remaining)} đ</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">
                    <i class="fas fa-users me-2"></i>
                    Danh sách học viên chưa thanh toán đủ (${courseData.unpaid_students} học viên)
                </h6>
                <div>
                    <button type="button" class="btn btn-sm btn-warning" id="selectAllStudentsBtn">
                        <i class="fas fa-check-square me-1"></i> Chọn tất cả
                    </button>
                    <button type="button" class="btn btn-sm btn-info ms-2" id="bulkReminderBtn" onclick="sendBulkReminder()" disabled>
                        <i class="fas fa-envelope me-1"></i> Gửi nhắc nhở đã chọn
                    </button>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px"><input type="checkbox" id="selectAllStudents"></th>
                            <th>Học viên</th>
                            <th>Liên hệ</th>
                            <th class="text-end">Học phí</th>
                            <th class="text-end">Đã đóng</th>
                            <th class="text-end">Còn lại</th>
                            <th class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        // Biến đếm số học viên có email
        let studentsWithEmail = 0;
        
        courseData.enrollments.forEach(enrollmentData => {
            if (enrollmentData.remaining > 0) {
                const hasEmail = enrollmentData.student.email ? true : false;
                if (hasEmail) studentsWithEmail++;
                
                html += `
                    <tr>
                        <td>
                            <input type="checkbox" class="student-checkbox-modal" 
                                   value="${enrollmentData.enrollment.id}"
                                   ${!hasEmail ? 'disabled' : ''}>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                    ${enrollmentData.student.full_name.charAt(0).toUpperCase()}
                                </div>
                                <div>
                                    <strong>${enrollmentData.student.full_name}</strong>
                                    <br>
                                    <small class="text-muted">
                                        Ghi danh: ${formatDate(enrollmentData.enrollment.enrollment_date)}
                                    </small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>
                                ${enrollmentData.student.phone ? `
                                    <div>
                                        <i class="fas fa-phone text-success me-1"></i>
                                        <a href="tel:${enrollmentData.student.phone}">
                                            ${enrollmentData.student.phone}
                                        </a>
                                    </div>
                                ` : ''}
                                ${enrollmentData.student.email ? `
                                    <div>
                                        <i class="fas fa-envelope text-info me-1"></i>
                                        <small>${enrollmentData.student.email}</small>
                                    </div>
                                ` : '<div class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Không có email</div>'}
                            </div>
                        </td>
                        <td class="text-end">
                            ${formatCurrency(enrollmentData.fee)} đ
                        </td>
                        <td class="text-end text-success">
                            ${formatCurrency(enrollmentData.paid)} đ
                        </td>
                        <td class="text-end text-danger">
                            <strong>${formatCurrency(enrollmentData.remaining)} đ</strong>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-info" 
                                        onclick="viewPaymentHistory(${enrollmentData.enrollment.id})"
                                        title="Lịch sử">
                                    <i class="fas fa-history"></i>
                                </button>
                                <button type="button" class="btn btn-success" 
                                        onclick="showPaymentModal(${enrollmentData.enrollment.id}, '${enrollmentData.student.full_name}', ${enrollmentData.remaining})"
                                        title="Thanh toán">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button type="button" class="btn btn-warning ${!hasEmail ? 'disabled' : ''}" 
                                        onclick="${hasEmail ? `sendReminderToStudent(${enrollmentData.enrollment.id})` : ''}"
                                        title="${hasEmail ? 'Gửi nhắc nhở' : 'Không thể gửi (không có email)'}"
                                        ${!hasEmail ? 'disabled' : ''}>
                                    <i class="fas fa-bell"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }
        });
        
        html += `
                    </tbody>
                </table>
            </div>
            
            ${courseData.unpaid_students > 0 && studentsWithEmail === 0 ? `
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Không có học viên nào có địa chỉ email để gửi nhắc nhở.
                </div>
            ` : ''}
        `;
        
        document.getElementById('courseDetailsContent').innerHTML = html;
        
        // Khởi tạo sự kiện cho checkbox
        initializeModalCheckboxes();
        
        // Lưu courseItemId để sử dụng trong sendBulkReminder
        window.currentCourseId = courseItemId;
    }

    // Khởi tạo các sự kiện cho checkbox trong modal
    function initializeModalCheckboxes() {
        const selectAllCheckbox = document.getElementById('selectAllStudents');
        const studentCheckboxes = document.querySelectorAll('.student-checkbox-modal:not([disabled])');
        const bulkReminderBtn = document.getElementById('bulkReminderBtn');
        
        // Xử lý chọn tất cả
        selectAllCheckbox.addEventListener('change', function() {
            studentCheckboxes.forEach(cb => cb.checked = this.checked);
            updateBulkReminderButton();
        });
        
        // Xử lý từng checkbox
        studentCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkReminderButton);
        });
        
        // Cập nhật trạng thái nút gửi nhắc nhở hàng loạt
        function updateBulkReminderButton() {
            const selectedCount = document.querySelectorAll('.student-checkbox-modal:checked').length;
            bulkReminderBtn.disabled = selectedCount === 0;
            bulkReminderBtn.innerHTML = `<i class="fas fa-envelope me-1"></i> Gửi nhắc nhở (${selectedCount})`;
        }
        
        // Nút chọn tất cả
        const selectAllBtn = document.getElementById('selectAllStudentsBtn');
        selectAllBtn.addEventListener('click', function() {
            const isAnyUnchecked = Array.from(studentCheckboxes).some(cb => !cb.checked);
            studentCheckboxes.forEach(cb => cb.checked = isAnyUnchecked);
            selectAllCheckbox.checked = isAnyUnchecked;
            updateBulkReminderButton();
        });
    }

    // Hàm gửi nhắc nhở cho một học viên cụ thể
    function sendReminderToStudent(enrollmentId) {
        const confirmed = confirm('Bạn có chắc chắn muốn gửi email nhắc nhở cho học viên này?');
        if (!confirmed) return;
        
        // Lấy button hiện tại để có thể cập nhật trạng thái
        const btn = event.currentTarget;
        const originalContent = btn.innerHTML;
        
        // Hiển thị loading
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch('{{ route("payments.send-reminder") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ enrollment_id: enrollmentId })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Hiển thị thông báo thành công
                Swal.fire({
                    title: 'Thành công!',
                    text: data.message || 'Đã gửi email nhắc nhở thành công.',
                    icon: 'success',
                    confirmButtonText: 'Đóng'
                });
            } else {
                // Hiển thị thông báo lỗi
                Swal.fire({
                    title: 'Lỗi!',
                    text: data.message || 'Có lỗi xảy ra khi gửi nhắc nhở.',
                    icon: 'error',
                    confirmButtonText: 'Đóng'
                });
            }
            
            // Khôi phục nút
            btn.disabled = false;
            btn.innerHTML = originalContent;
        })
        .catch(error => {
            console.error('Error:', error);
            
            // Hiển thị thông báo lỗi
            Swal.fire({
                title: 'Lỗi!',
                text: 'Đã xảy ra lỗi khi gửi nhắc nhở: ' + error.message,
                icon: 'error',
                confirmButtonText: 'Đóng'
            });
            
            // Khôi phục nút
            btn.disabled = false;
            btn.innerHTML = originalContent;
        });
    }

    // Gửi nhắc nhở hàng loạt cho tất cả học viên đã chọn trong modal
    function sendBulkReminder() {
        const selectedStudents = document.querySelectorAll('.student-checkbox-modal:checked');
        
        if (selectedStudents.length === 0) {
            Swal.fire({
                title: 'Thông báo',
                text: 'Vui lòng chọn ít nhất một học viên để gửi nhắc nhở.',
                icon: 'info',
                confirmButtonText: 'Đóng'
            });
            return;
        }
        
        Swal.fire({
            title: 'Xác nhận',
            text: `Bạn có chắc chắn muốn gửi email nhắc nhở cho ${selectedStudents.length} học viên đã chọn?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Gửi nhắc nhở',
            cancelButtonText: 'Hủy bỏ'
        }).then((result) => {
            if (result.isConfirmed) {
                // Hiển thị loading
                const btn = document.getElementById('bulkReminderBtn');
                const originalContent = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Đang xử lý...';
                
                const enrollmentIds = Array.from(selectedStudents).map(cb => cb.value);
                
                fetch('{{ route("payments.send-reminder") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ enrollment_ids: enrollmentIds })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Thành công!',
                            text: data.message || `Đã gửi email nhắc nhở thành công cho ${selectedStudents.length} học viên.`,
                            icon: 'success',
                            confirmButtonText: 'Đóng'
                        }).then(() => {
                            // Đóng modal và reload trang
                            bootstrap.Modal.getInstance(document.getElementById('courseDetailsModal')).hide();
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Lỗi!',
                            text: data.message || 'Có lỗi xảy ra khi gửi email nhắc nhở.',
                            icon: 'error',
                            confirmButtonText: 'Đóng'
                        });
                        
                        // Khôi phục nút
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    Swal.fire({
                        title: 'Lỗi!',
                        text: 'Đã xảy ra lỗi khi gửi email nhắc nhở: ' + error.message,
                        icon: 'error',
                        confirmButtonText: 'Đóng'
                    });
                    
                    // Khôi phục nút
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                });
            }
        });
    }

    // Hàm xem lịch sử thanh toán của học viên
    function viewPaymentHistory(enrollmentId) {
        // Hiển thị loading
        Swal.fire({
            title: 'Đang tải...',
            html: 'Vui lòng chờ trong giây lát.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Lấy lịch sử thanh toán
        fetch(`/payments/history/${enrollmentId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'Không thể lấy lịch sử thanh toán');
            }
            
            const enrollment = data.data.enrollment;
            const payments = data.data.payments;
            const totalPaid = data.data.total_paid;
            const remaining = data.data.remaining;
            
            // Tạo HTML hiển thị lịch sử thanh toán
            let paymentHistoryHtml = `
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Thông tin ghi danh</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Học viên:</strong> ${enrollment.student.full_name}</p>
                                <p class="mb-1"><strong>Khóa học:</strong> ${enrollment.course_item.name}</p>
                                <p class="mb-1"><strong>Ngày ghi danh:</strong> ${formatDate(enrollment.enrollment_date)}</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Học phí:</strong> ${formatCurrency(enrollment.final_fee)} đ</p>
                                <p class="mb-1"><strong>Đã thanh toán:</strong> <span class="text-success">${formatCurrency(totalPaid)} đ</span></p>
                                <p class="mb-1"><strong>Còn thiếu:</strong> <span class="text-danger">${formatCurrency(remaining)} đ</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h5 class="mb-3">Lịch sử thanh toán</h5>
            `;
            
            if (payments.length === 0) {
                paymentHistoryHtml += `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Chưa có lịch sử thanh toán nào.
                    </div>
                `;
            } else {
                paymentHistoryHtml += `
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Ngày</th>
                                    <th>Phương thức</th>
                                    <th class="text-end">Số tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                payments.forEach(payment => {
                    const statusClass = {
                        'pending': 'badge bg-warning',
                        'confirmed': 'badge bg-success',
                        'cancelled': 'badge bg-danger',
                        'refunded': 'badge bg-secondary'
                    };
                    
                    const statusText = {
                        'pending': 'Chờ xác nhận',
                        'confirmed': 'Đã xác nhận',
                        'cancelled': 'Đã hủy',
                        'refunded': 'Đã hoàn tiền'
                    };
                    
                    const paymentMethod = {
                        'cash': 'Tiền mặt',
                        'bank_transfer': 'Chuyển khoản',
                        'card': 'Thẻ',
                        'qr_code': 'QR Code',
                        'sepay': 'SePay',
                        'other': 'Khác'
                    };
                    
                    paymentHistoryHtml += `
                        <tr>
                            <td>${formatDate(payment.payment_date || payment.created_at)}</td>
                            <td>${paymentMethod[payment.payment_method] || payment.payment_method}</td>
                            <td class="text-end">${formatCurrency(payment.amount)} đ</td>
                            <td><span class="${statusClass[payment.status] || 'badge bg-secondary'}">${statusText[payment.status] || payment.status}</span></td>
                            <td>${payment.note || ''}</td>
                        </tr>
                    `;
                });
                
                paymentHistoryHtml += `
                            </tbody>
                        </table>
                    </div>
                `;
            }
            
            // Hiển thị modal với lịch sử thanh toán
            Swal.fire({
                title: 'Lịch sử thanh toán',
                html: paymentHistoryHtml,
                width: '800px',
                confirmButtonText: 'Đóng',
                customClass: {
                    container: 'payment-history-modal'
                }
            });
        })
        .catch(error => {
            console.error('Error:', error);
            
            Swal.fire({
                title: 'Lỗi!',
                text: 'Đã xảy ra lỗi khi tải lịch sử thanh toán: ' + error.message,
                icon: 'error',
                confirmButtonText: 'Đóng'
            });
        });
    }

    // Thêm hàm hiển thị modal thanh toán
    function showPaymentModal(enrollmentId, studentName, remainingAmount) {
        // Lấy thông tin học viên và số tiền còn thiếu
        Swal.fire({
            title: 'Thanh toán học phí',
            html: `
                <div class="text-start">
                    <p><strong>Học viên:</strong> ${studentName}</p>
                    <p><strong>Số tiền còn thiếu:</strong> ${formatCurrency(remainingAmount)} đ</p>
                    
                    <div class="mb-3">
                        <label for="payment-amount" class="form-label">Số tiền thanh toán:</label>
                        <input type="number" class="form-control" id="payment-amount" value="${remainingAmount}">
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment-method" class="form-label">Phương thức thanh toán:</label>
                        <select class="form-select" id="payment-method">
                            <option value="cash">Tiền mặt</option>
                            <option value="bank_transfer">Chuyển khoản</option>
                            <option value="card">Thẻ</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment-note" class="form-label">Ghi chú:</label>
                        <textarea class="form-control" id="payment-note" rows="2"></textarea>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Xác nhận thanh toán',
            cancelButtonText: 'Hủy bỏ',
            focusConfirm: false,
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-secondary'
            },
            preConfirm: () => {
                const amount = document.getElementById('payment-amount').value;
                const method = document.getElementById('payment-method').value;
                const note = document.getElementById('payment-note').value;
                
                if (!amount || amount <= 0) {
                    Swal.showValidationMessage('Vui lòng nhập số tiền hợp lệ');
                    return false;
                }
                
                return { amount, method, note };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Hiển thị trạng thái đang xử lý
                Swal.fire({
                    title: 'Đang xử lý...',
                    html: 'Vui lòng chờ trong giây lát.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Gửi yêu cầu tạo thanh toán
                fetch('{{ route("payments.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        enrollment_id: enrollmentId,
                        amount: result.value.amount,
                        payment_method: result.value.method,
                        note: result.value.note,
                        status: 'confirmed' // Mặc định là đã xác nhận
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Thành công!',
                            text: 'Đã tạo thanh toán thành công.',
                            icon: 'success',
                            confirmButtonText: 'Đóng'
                        }).then(() => {
                            // Reload trang hoặc cập nhật dữ liệu
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Lỗi!',
                            text: data.message || 'Có lỗi xảy ra khi tạo thanh toán.',
                            icon: 'error',
                            confirmButtonText: 'Đóng'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    Swal.fire({
                        title: 'Lỗi!',
                        text: 'Đã xảy ra lỗi: ' + error.message,
                        icon: 'error',
                        confirmButtonText: 'Đóng'
                    });
                });
            }
        });
    }

    // Hàm định dạng tiền tệ
    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount);
    }
    
    // Hàm định dạng ngày tháng
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    }
    
    // Hàm lấy text phương thức thanh toán
    function getPaymentMethodText(method) {
        const methodMap = {
            'cash': 'Tiền mặt',
            'bank_transfer': 'Chuyển khoản',
            'card': 'Thẻ',
            'qr_code': 'Mã QR',
            'sepay': 'SePay',
            'other': 'Khác'
        };
        return methodMap[method] || method;
    }
    
    // Hàm lấy text trạng thái thanh toán
    function getPaymentStatusText(status) {
        const statusMap = {
            'pending': 'Chờ xác nhận',
            'confirmed': 'Đã xác nhận',
            'cancelled': 'Đã hủy',
            'refunded': 'Đã hoàn tiền'
        };
        return statusMap[status] || status;
    }

    // Hàm gửi nhắc nhở cho các lớp học đã chọn
    function sendCourseReminders(courseIds) {
        // Hiển thị loading
        Swal.fire({
            title: 'Đang xử lý...',
            text: 'Đang gửi yêu cầu nhắc nhở. Vui lòng chờ...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Gọi API gửi nhắc nhở
        fetch('{{ route("payments.send-reminder") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ course_ids: courseIds })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Thành công!',
                    text: data.message || 'Đã bắt đầu gửi nhắc nhở cho các học viên trong lớp đã chọn.',
                    icon: 'success',
                    confirmButtonText: 'Đóng'
                });
            } else {
                Swal.fire({
                    title: 'Lỗi!',
                    text: data.message || 'Có lỗi xảy ra khi gửi nhắc nhở.',
                    icon: 'error',
                    confirmButtonText: 'Đóng'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            
            Swal.fire({
                title: 'Lỗi!',
                text: 'Đã xảy ra lỗi khi gửi nhắc nhở: ' + error.message,
                icon: 'error',
                confirmButtonText: 'Đóng'
            });
        });
    }
</script>
@endpush 

<!-- Modal chi tiết ghi danh -->
<div class="modal fade" id="enrollmentDetailsModal" tabindex="-1" aria-labelledby="enrollmentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="enrollmentDetailsModalLabel">
                    <i class="fas fa-info-circle me-2"></i>Chi tiết ghi danh
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="enrollmentDetailsContent">
                <!-- Nội dung sẽ được thêm bằng JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div> 