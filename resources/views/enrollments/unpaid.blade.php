@extends('layouts.app')

@section('page-title', 'Lớp học chưa thanh toán đủ học phí')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Thanh toán</a></li>
    <li class="breadcrumb-item active">Lớp chưa thanh toán đủ</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
         <h5 class="card-title mb-0">Danh sách lớp học chưa thanh toán đủ học phí</h5>
    </div>
    <div class="card-body">
         <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <form action="{{ route('payments.send-reminder') }}" method="POST" id="bulk-reminder-form" class="d-inline">
                     @csrf
                     <div id="bulk-course-ids-container"></div>
                     <button type="submit" class="btn btn-info btn-sm" id="bulk-remind-btn" disabled>
                        <i class="fas fa-envelope me-1"></i> Gửi nhắc nhở đã chọn
                    </button>
                </form>
            </div>
            <div>
                <span class="text-muted">Tổng: {{ count($courseEnrollments) }} lớp học</span>
            </div>
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
            updateBulkButtons();
        });

        // Xử lý khi checkbox lớp học đơn lẻ thay đổi
        document.querySelectorAll('.course-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateBulkButtons();
            });
        });

        // Xử lý checkbox chọn tất cả học viên trong lớp
        document.querySelectorAll('.student-check-all').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const studentCheckboxes = document.querySelectorAll(`.student-checkbox[data-course="${this.dataset.course}"]`);
                studentCheckboxes.forEach(studentCheckbox => {
                    studentCheckbox.checked = this.checked;
                });
            });
        });

        // Xử lý khi checkbox học viên đơn lẻ thay đổi
        document.querySelectorAll('.student-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const courseId = this.dataset.course;
                const courseDetailsRow = document.getElementById(`course-details-${courseId}`);
                const studentCheckboxes = document.querySelectorAll(`.student-checkbox[data-course="${courseId}"]`);
                
                // Kiểm tra nếu tất cả học viên trong lớp đã được chọn
                if (studentCheckboxes.length === 0) {
                    courseDetailsRow.style.display = 'none'; // Ẩn lại nếu không có học viên
                } else {
                    const allStudentChecked = Array.from(studentCheckboxes).every(studentCheckbox => studentCheckbox.checked);
                    courseDetailsRow.style.display = allStudentChecked ? 'table-row' : 'none';
                }
            });
        });
        
        // Cập nhật trạng thái các nút hành động hàng loạt
        function updateBulkButtons() {
            const checkedCourseCount = document.querySelectorAll('.course-checkbox:checked').length;
            const bulkRemindBtn = document.getElementById('bulk-remind-btn');
            const container = document.getElementById('bulk-course-ids-container');
            
            if (checkedCourseCount > 0) {
                bulkRemindBtn.disabled = false;
                
                // Xóa các hidden input cũ
                container.innerHTML = '';
                
                // Tạo hidden input cho mỗi course_id được chọn
                document.querySelectorAll('.course-checkbox:checked').forEach(checkbox => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'course_ids[]';
                    input.value = checkbox.value;
                    container.appendChild(input);
                });
            } else {
                bulkRemindBtn.disabled = true;
                container.innerHTML = '';
            }
        }
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
        updateBulkButtons(); // Cập nhật trạng thái nút hàng loạt
    }

    // Hiển thị modal chi tiết lớp học
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
            
            <h6 class="mb-3">
                <i class="fas fa-users me-2"></i>
                Danh sách học viên chưa thanh toán đủ (${courseData.unpaid_students} học viên)
            </h6>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllStudents" onchange="toggleAllStudents(this)"></th>
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
        
        courseData.enrollments.forEach(enrollmentData => {
            if (enrollmentData.remaining > 0) {
                html += `
                    <tr>
                        <td>
                            <input type="checkbox" class="student-checkbox-modal" 
                                   value="${enrollmentData.enrollment.id}">
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
                                ` : ''}
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
                                        onclick="showPaymentModal(${enrollmentData.enrollment.id})"
                                        title="Thanh toán">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button type="button" class="btn btn-warning" 
                                        onclick="sendReminder(${enrollmentData.enrollment.id})"
                                        title="Gửi nhắc nhở">
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
        `;
        
        document.getElementById('courseDetailsContent').innerHTML = html;
        
        // Lưu courseItemId để sử dụng trong sendBulkReminder
        window.currentCourseId = courseItemId;
    }

    // Toggle tất cả checkbox học viên trong modal
    function toggleAllStudents(checkbox) {
        const studentCheckboxes = document.querySelectorAll('.student-checkbox-modal');
        studentCheckboxes.forEach(cb => {
            cb.checked = checkbox.checked;
        });
    }

    // Gửi nhắc nhở hàng loạt cho tất cả học viên đã chọn trong modal
    function sendBulkReminder() {
        const selectedStudents = document.querySelectorAll('.student-checkbox-modal:checked');
        
        if (selectedStudents.length === 0) {
            alert('Vui lòng chọn ít nhất một học viên để gửi nhắc nhở.');
            return;
        }
        
        const confirmed = confirm(`Bạn có chắc chắn muốn gửi email nhắc nhở cho ${selectedStudents.length} học viên đã chọn?`);
        if (!confirmed) {
            return;
        }
        
        const enrollmentIds = Array.from(selectedStudents).map(cb => cb.value);
        
        fetch('{{ route("payments.send-reminder") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ enrollment_ids: enrollmentIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Đã gửi email nhắc nhở thành công cho ${selectedStudents.length} học viên.`);
                // Đóng modal
                bootstrap.Modal.getInstance(document.getElementById('courseDetailsModal')).hide();
                // Reload trang để cập nhật dữ liệu
                window.location.reload();
            } else {
                alert('Đã xảy ra lỗi khi gửi email nhắc nhở: ' + (data.message || 'Lỗi không xác định'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Đã xảy ra lỗi khi gửi email nhắc nhở: ' + error.message);
        });
    }

    // Hàm gửi nhắc nhở
    function sendReminder(enrollmentId) {
        const confirmed = confirm('Bạn có chắc chắn muốn gửi email nhắc nhở cho học viên này?');
        if (!confirmed) {
            return;
        }

        fetch('{{ route("payments.send-reminder") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ payment_ids: [enrollmentId] })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Đã gửi email nhắc nhở thành công.');
            } else {
                alert('Đã xảy ra lỗi khi gửi email nhắc nhở.');
                console.error(data.message);
            }
        })
        .catch(error => {
            alert('Đã xảy ra lỗi khi gửi email nhắc nhở.');
            console.error(error);
        });
    }

    // Hàm xem lịch sử thanh toán
    function viewPaymentHistory(enrollmentId) {
        console.log('View payment history for enrollment', enrollmentId);
        
        // Hiển thị loading
        const modal = new bootstrap.Modal(document.getElementById('paymentHistoryModal'));
        document.getElementById('paymentHistoryContent').innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-2">Đang tải thông tin...</p>
            </div>
        `;
        modal.show();
        
        // Tải lịch sử thanh toán
        fetch(`/api/enrollments/${enrollmentId}/payments`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const enrollment = data.data.enrollment;
                    const payments = data.data.payments;
                    
                    let html = `
                        <div class="mb-4">
                            <h6>Thông tin ghi danh</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p><strong>Học viên:</strong> ${enrollment.student.full_name}</p>
                                    <p><strong>Khóa học:</strong> ${enrollment.course_item.name}</p>
                                    <p><strong>Học phí:</strong> ${formatCurrency(enrollment.final_fee)} đ</p>
                                    <p><strong>Trạng thái:</strong> 
                                        <span class="badge bg-${enrollment.status === 'active' ? 'success' : 'warning'}">${enrollment.status}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    if (payments && payments.length > 0) {
                        html += `
                            <h6>Lịch sử thanh toán (${payments.length} giao dịch)</h6>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Ngày thanh toán</th>
                                            <th>Số tiền</th>
                                            <th>Phương thức</th>
                                            <th>Trạng thái</th>
                                            <th>Ghi chú</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;
                        
                        payments.forEach(payment => {
                            html += `
                                <tr>
                                    <td>${formatDate(payment.payment_date)}</td>
                                    <td class="fw-bold">${formatCurrency(payment.amount)} đ</td>
                                    <td>${getPaymentMethodText(payment.payment_method)}</td>
                                    <td>
                                        <span class="badge bg-${payment.status === 'confirmed' ? 'success' : (payment.status === 'pending' ? 'warning' : 'danger')}">
                                            ${getPaymentStatusText(payment.status)}
                                        </span>
                                    </td>
                                    <td>${payment.notes || '-'}</td>
                                </tr>
                            `;
                        });
                        
                        html += `
                                    </tbody>
                                </table>
                            </div>
                        `;
                        
                        // Tính tổng đã thanh toán
                        const totalPaid = payments.filter(p => p.status === 'confirmed').reduce((sum, p) => sum + parseFloat(p.amount), 0);
                        const remaining = parseFloat(enrollment.final_fee) - totalPaid;
                        
                        html += `
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center">
                                            <h6>Tổng học phí</h6>
                                            <h5>${formatCurrency(enrollment.final_fee)} đ</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <h6>Đã thanh toán</h6>
                                            <h5>${formatCurrency(totalPaid)} đ</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-${remaining > 0 ? 'warning' : 'success'} text-white">
                                        <div class="card-body text-center">
                                            <h6>Còn lại</h6>
                                            <h5>${formatCurrency(remaining)} đ</h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        html += `
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Chưa có lịch sử thanh toán nào cho ghi danh này.
                            </div>
                        `;
                    }
                    
                    document.getElementById('paymentHistoryContent').innerHTML = html;
                } else {
                    document.getElementById('paymentHistoryContent').innerHTML = 
                        `<div class="alert alert-danger">Có lỗi xảy ra: ${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('paymentHistoryContent').innerHTML = 
                    `<div class="alert alert-danger">Có lỗi xảy ra khi tải dữ liệu: ${error.message}</div>`;
            });
    }

    // Hàm hiển thị modal thanh toán
    function showPaymentModal(enrollmentId) {
        window.location.href = `/payments/create?enrollment_id=${enrollmentId}`;
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