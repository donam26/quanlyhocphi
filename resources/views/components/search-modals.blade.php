<!-- Modal Chi tiết Học viên -->
<div class="modal fade" id="studentDetailModal" tabindex="-1" aria-labelledby="studentDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentDetailModalLabel">
                    <i class="fas fa-user me-2"></i>Chi tiết học viên
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentDetailContent">
                <!-- Nội dung sẽ được load bằng JavaScript -->
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" id="editStudentBtn">
                    <i class="fas fa-edit me-1"></i>Chỉnh sửa
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lịch sử Học viên -->
<div class="modal fade" id="studentHistoryModal" tabindex="-1" aria-labelledby="studentHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentHistoryModalLabel">
                    <i class="fas fa-history me-2"></i>Lịch sử học viên
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentHistoryContent">
                <!-- Nội dung sẽ được load bằng JavaScript -->
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-info" id="printHistoryBtn">
                    <i class="fas fa-print me-1"></i>In lịch sử
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm/Sửa Học viên -->
<div class="modal fade" id="studentFormModal" tabindex="-1" aria-labelledby="studentFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentFormModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Thêm học viên
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="studentFormContent">
                <!-- Form sẽ được load bằng JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Thêm Thanh toán -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPaymentModalLabel">
                    <i class="fas fa-money-bill-wave me-2"></i>Thêm thanh toán
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="addPaymentContent">
                <!-- Form thanh toán sẽ được load bằng JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Đăng ký Khóa học -->
<div class="modal fade" id="enrollmentModal" tabindex="-1" aria-labelledby="enrollmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="enrollmentModalLabel">
                    <i class="fas fa-graduation-cap me-2"></i>Đăng ký khóa học
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="enrollmentContent">
                <!-- Form đăng ký sẽ được load bằng JavaScript -->
            </div>
        </div>
    </div>
</div>

<style>
.modal-xl {
    max-width: 95%;
}

@media (max-width: 768px) {
    .modal-xl {
        max-width: 100%;
        margin: 0.5rem;
    }
}

.modal-body .table-responsive {
    max-height: 400px;
    overflow-y: auto;
}

.modal .nav-tabs {
    border-bottom: 1px solid #dee2e6;
}

.modal .tab-content {
    border: 1px solid #dee2e6;
    border-top: none;
    padding: 1rem;
    border-radius: 0 0 0.375rem 0.375rem;
}
</style>

<script>
// Global modal functions
window.SearchModals = {
    showStudentDetail: function(studentId) {
        const modal = new bootstrap.Modal(document.getElementById('studentDetailModal'));
        const content = $('#studentDetailContent');
        
        // Reset content
        content.html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="mt-2">Đang tải thông tin chi tiết...</p>
            </div>
        `);
        
        modal.show();
        
        // Load chi tiết học viên
        $.ajax({
            url: '{{ route("api.search.details") }}',
            method: 'GET',
            data: { student_id: studentId },
            success: function(response) {
                if (response.success && response.data.studentsDetails.length > 0) {
                    const detail = response.data.studentsDetails[0];
                    content.html(SearchModals.generateDetailedStudentView(detail));
                } else {
                    content.html('<div class="alert alert-warning">Không tìm thấy thông tin học viên</div>');
                }
            },
            error: function() {
                content.html('<div class="alert alert-danger">Có lỗi xảy ra khi tải thông tin</div>');
            }
        });
    },

    showStudentHistory: function(studentId) {
        const modal = new bootstrap.Modal(document.getElementById('studentHistoryModal'));
        const content = $('#studentHistoryContent');
        
        // Reset content
        content.html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="mt-2">Đang tải lịch sử...</p>
            </div>
        `);
        
        modal.show();
        
        // Load lịch sử học viên
        $.ajax({
            url: `{{ route("api.search.student-history", ":id") }}`.replace(':id', studentId),
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    content.html(SearchModals.generateStudentHistoryView(response.data));
                } else {
                    content.html('<div class="alert alert-warning">Không tìm thấy lịch sử học viên</div>');
                }
            },
            error: function() {
                content.html('<div class="alert alert-danger">Có lỗi xảy ra khi tải lịch sử</div>');
            }
        });
    },

    showStudentForm: function(studentId = null) {
        const modal = new bootstrap.Modal(document.getElementById('studentFormModal'));
        const content = $('#studentFormContent');
        const title = $('#studentFormModalLabel');
        
        title.html(studentId ? 
            '<i class="fas fa-user-edit me-2"></i>Chỉnh sửa học viên' : 
            '<i class="fas fa-user-plus me-2"></i>Thêm học viên'
        );
        
        // Reset content
        content.html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="mt-2">Đang tải form...</p>
            </div>
        `);
        
        modal.show();
        
        if (studentId) {
            // Load student data for editing
            $.ajax({
                url: `/api/students/${studentId}/details`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        content.html(SearchModals.generateStudentForm(response.data, true));
                        SearchModals.initializeFormComponents();
                    } else {
                        content.html('<div class="alert alert-danger">Không tìm thấy thông tin học viên</div>');
                    }
                },
                error: function() {
                    content.html('<div class="alert alert-danger">Có lỗi xảy ra khi tải thông tin học viên</div>');
                }
            });
        } else {
            // Show form for new student
            content.html(SearchModals.generateStudentForm(null, false));
            SearchModals.initializeFormComponents();
        }
    },

    showAddPayment: function(studentId, enrollmentId = null) {
        const modal = new bootstrap.Modal(document.getElementById('addPaymentModal'));
        const content = $('#addPaymentContent');
        
        // Reset content
        content.html(`
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="mt-2">Đang tải form thanh toán...</p>
            </div>
        `);
        
        modal.show();
        
        // Generate payment form
        content.html(SearchModals.generatePaymentForm(studentId, enrollmentId));
        SearchModals.initializeFormComponents();
    },

    generateDetailedStudentView: function(detail) {
        const student = detail.student;
        const enrollments = detail.enrollments || [];
        
        return `
            <div class="row">
                <div class="col-md-6">
                    <h6 class="border-bottom pb-2 mb-3">Thông tin cá nhân</h6>
                    <table class="table table-borderless">
                        <tr><td><strong>Họ và tên:</strong></td><td>${student.full_name}</td></tr>
                        <tr><td><strong>Ngày sinh:</strong></td><td>${SearchModals.formatDate(student.date_of_birth)}</td></tr>
                        <tr><td><strong>Giới tính:</strong></td><td>${SearchModals.getGenderText(student.gender)}</td></tr>
                        <tr><td><strong>Số điện thoại:</strong></td><td>${student.phone}</td></tr>
                        <tr><td><strong>Email:</strong></td><td>${student.email || 'Chưa cập nhật'}</td></tr>
                        <tr><td><strong>Địa chỉ:</strong></td><td>${student.address || 'Chưa cập nhật'}</td></tr>
                        ${student.current_workplace ? `<tr><td><strong>Nơi công tác:</strong></td><td>${student.current_workplace}</td></tr>` : ''}
                        ${student.accounting_experience_years ? `<tr><td><strong>Kinh nghiệm:</strong></td><td>${student.accounting_experience_years} năm</td></tr>` : ''}
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="border-bottom pb-2 mb-3">Thông tin tài chính</h6>
                    <div class="card">
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <h5 class="text-primary">${SearchModals.formatCurrency(detail.total_fee)}</h5>
                                    <small class="text-muted">Tổng học phí</small>
                                </div>
                                <div class="col-4">
                                    <h5 class="text-success">${SearchModals.formatCurrency(detail.total_paid)}</h5>
                                    <small class="text-muted">Đã thanh toán</small>
                                </div>
                                <div class="col-4">
                                    <h5 class="text-danger">${SearchModals.formatCurrency(detail.remaining)}</h5>
                                    <small class="text-muted">Còn thiếu</small>
                                </div>
                            </div>
                            <div class="progress mt-3" style="height: 10px;">
                                <div class="progress-bar bg-success" style="width: ${detail.total_fee > 0 ? (detail.total_paid / detail.total_fee * 100) : 0}%"></div>
                            </div>
                            <div class="text-center mt-2">
                                <small class="text-muted">${detail.total_fee > 0 ? Math.round(detail.total_paid / detail.total_fee * 100) : 0}% hoàn thành</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <h6 class="mb-3">Danh sách khóa học (${enrollments.length})</h6>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Khóa học</th>
                            <th>Ngày đăng ký</th>
                            <th>Trạng thái</th>
                            <th>Học phí</th>
                            <th>Đã trả</th>
                            <th>Còn lại</th>
                            <th>Thanh toán</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${enrollments.map(enrollment => `
                            <tr>
                                <td><strong>${enrollment.course_item.name}</strong></td>
                                <td>${enrollment.enrollment_date}</td>
                                <td><span class="badge bg-info">${enrollment.status}</span></td>
                                <td>${SearchModals.formatCurrency(enrollment.final_fee)}</td>
                                <td class="text-success">${SearchModals.formatCurrency(enrollment.total_paid)}</td>
                                <td class="text-danger">${SearchModals.formatCurrency(enrollment.remaining_amount)}</td>
                                <td>
                                    <span class="badge ${enrollment.remaining_amount <= 0 ? 'bg-success' : 'bg-warning'}">
                                        ${enrollment.payment_status}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        ${enrollment.remaining_amount > 0 ? `
                                        <button class="btn btn-success btn-sm" onclick="SearchModals.showAddPayment(${student.id}, ${enrollment.id})" title="Thêm thanh toán">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        ` : ''}
                                        <button class="btn btn-info btn-sm" onclick="SearchModals.showEnrollmentDetail(${enrollment.id})" title="Chi tiết đăng ký">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    },

    generateStudentHistoryView: function(data) {
        const student = data.student;
        const payments = data.payments || [];
        const attendances = data.attendances || [];
        
        return `
            <div class="row mb-4">
                <div class="col-md-12">
                    <h5><i class="fas fa-user me-2"></i>${student.full_name}</h5>
                    <p class="text-muted">${student.phone} | ${student.email || 'Không có email'}</p>
                </div>
            </div>
            
            <ul class="nav nav-tabs" id="historyTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments" type="button" role="tab">
                        <i class="fas fa-money-bill-wave me-1"></i>Lịch sử thanh toán (${payments.length})
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="attendances-tab" data-bs-toggle="tab" data-bs-target="#attendances" type="button" role="tab">
                        <i class="fas fa-calendar-check me-1"></i>Lịch sử điểm danh (${attendances.length})
                    </button>
                </li>
            </ul>
            
            <div class="tab-content mt-3" id="historyTabsContent">
                <div class="tab-pane fade show active" id="payments" role="tabpanel">
                    ${payments.length > 0 ? `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Ngày thanh toán</th>
                                    <th>Khóa học</th>
                                    <th>Số tiền</th>
                                    <th>Phương thức</th>
                                    <th>Trạng thái</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${payments.map(payment => `
                                    <tr>
                                        <td>${SearchModals.formatDate(payment.payment_date)}</td>
                                        <td>${payment.enrollment.course_item.name}</td>
                                        <td class="text-success"><strong>${SearchModals.formatCurrency(payment.amount)}</strong></td>
                                        <td>${SearchModals.getPaymentMethodText(payment.payment_method)}</td>
                                        <td>
                                            <span class="badge ${payment.status === 'confirmed' ? 'bg-success' : 'bg-warning'}">
                                                ${SearchModals.getPaymentStatusText(payment.status)}
                                            </span>
                                        </td>
                                        <td>${payment.notes || '-'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    ` : '<div class="alert alert-info">Chưa có lịch sử thanh toán</div>'}
                </div>
                
                <div class="tab-pane fade" id="attendances" role="tabpanel">
                    ${attendances.length > 0 ? `
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Ngày điểm danh</th>
                                    <th>Khóa học</th>
                                    <th>Trạng thái</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${attendances.map(attendance => `
                                    <tr>
                                        <td>${SearchModals.formatDate(attendance.attendance_date)}</td>
                                        <td>${attendance.course_item ? attendance.course_item.name : 'N/A'}</td>
                                        <td>
                                            <span class="badge ${SearchModals.getAttendanceStatusClass(attendance.status)}">
                                                ${SearchModals.getAttendanceStatusText(attendance.status)}
                                            </span>
                                        </td>
                                        <td>${attendance.notes || '-'}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                    ` : '<div class="alert alert-info">Chưa có lịch sử điểm danh</div>'}
                </div>
            </div>
        `;
    },

    generateStudentForm: function(studentData = null, isEdit = false) {
        const student = studentData || {};
        const formAction = isEdit ? `/api/students/${student.id}/update` : '/api/students/create';
        const method = isEdit ? 'POST' : 'POST';
        
        return `
            <form id="studentForm" data-action="${formAction}" data-method="${method}">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="full_name" name="full_name" value="${student.full_name || ''}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="phone" name="phone" value="${student.phone || ''}" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="${student.email || ''}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="date_of_birth" class="form-label">Ngày sinh</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="${student.date_of_birth || ''}">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="gender" class="form-label">Giới tính</label>
                            <select class="form-control" id="gender" name="gender">
                                <option value="">Chọn giới tính</option>
                                <option value="male" ${student.gender === 'male' ? 'selected' : ''}>Nam</option>
                                <option value="female" ${student.gender === 'female' ? 'selected' : ''}>Nữ</option>
                                <option value="other" ${student.gender === 'other' ? 'selected' : ''}>Khác</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="province_id" class="form-label">Tỉnh thành</label>
                            <select class="form-control select2" id="province_id" name="province_id">
                                <option value="">Chọn tỉnh thành</option>
                                <!-- Provinces will be loaded via AJAX -->
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="address" class="form-label">Địa chỉ</label>
                    <textarea class="form-control" id="address" name="address" rows="2">${student.address || ''}</textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="current_workplace" class="form-label">Nơi công tác</label>
                            <input type="text" class="form-control" id="current_workplace" name="current_workplace" value="${student.current_workplace || ''}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="accounting_experience_years" class="form-label">Số năm kinh nghiệm</label>
                            <input type="number" class="form-control" id="accounting_experience_years" name="accounting_experience_years" value="${student.accounting_experience_years || ''}" min="0">
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="notes" class="form-label">Ghi chú</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3">${student.notes || ''}</textarea>
                </div>
                
                <div class="text-end">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>${isEdit ? 'Cập nhật' : 'Thêm mới'}
                    </button>
                </div>
            </form>
        `;
    },

    generatePaymentForm: function(studentId, enrollmentId = null) {
        return `
            <form id="paymentForm" data-action="/api/payments" data-method="POST">
                <input type="hidden" name="student_id" value="${studentId}">
                ${enrollmentId ? `<input type="hidden" name="enrollment_id" value="${enrollmentId}">` : ''}
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Số tiền <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="amount" name="amount" required min="0" step="1000">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="payment_date" class="form-label">Ngày thanh toán <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="payment_date" name="payment_date" value="${new Date().toISOString().split('T')[0]}" required>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Phương thức thanh toán <span class="text-danger">*</span></label>
                            <select class="form-control" id="payment_method" name="payment_method" required>
                                <option value="">Chọn phương thức</option>
                                <option value="cash">Tiền mặt</option>
                                <option value="bank_transfer">Chuyển khoản</option>
                                <option value="card">Thẻ</option>
                                <option value="qr_code">QR Code</option>
                                <option value="sepay">SEPAY</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="transaction_reference" class="form-label">Mã giao dịch</label>
                            <input type="text" class="form-control" id="transaction_reference" name="transaction_reference">
                        </div>
                    </div>
                </div>
                
                ${!enrollmentId ? `
                <div class="mb-3">
                    <label for="enrollment_id_select" class="form-label">Khóa học <span class="text-danger">*</span></label>
                    <select class="form-control select2" id="enrollment_id_select" name="enrollment_id" required>
                        <option value="">Chọn khóa học</option>
                        <!-- Enrollments will be loaded via AJAX -->
                    </select>
                </div>
                ` : ''}
                
                <div class="mb-3">
                    <label for="notes" class="form-label">Ghi chú</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                </div>
                
                <div class="text-end">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-money-bill-wave me-1"></i>Thêm thanh toán
                    </button>
                </div>
            </form>
        `;
    },

    initializeFormComponents: function() {
        // Reinitialize Select2, date pickers, etc.
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
        }
        
        // Initialize date pickers
        if (typeof flatpickr !== 'undefined') {
            flatpickr('.datepicker', {
                dateFormat: 'd/m/Y',
                locale: 'vn'
            });
        }
        
        // Load provinces for student form
        this.loadProvinces();
        
        // Load enrollments for payment form
        this.loadEnrollments();
        
        // Bind form submit events
        this.bindFormEvents();
    },

    loadProvinces: function() {
        if ($('#province_id').length > 0) {
            $.ajax({
                url: '/api/provinces',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const select = $('#province_id');
                        response.data.forEach(province => {
                            select.append(`<option value="${province.id}">${province.name}</option>`);
                        });
                    }
                }
            });
        }
    },

    loadEnrollments: function() {
        const studentId = $('input[name="student_id"]').val();
        if ($('#enrollment_id_select').length > 0 && studentId) {
            $.ajax({
                url: `/api/enrollments/student/${studentId}`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const select = $('#enrollment_id_select');
                        response.data.forEach(enrollment => {
                            select.append(`<option value="${enrollment.id}">${enrollment.course_item.name} - ${SearchModals.formatCurrency(enrollment.remaining_amount)} còn lại</option>`);
                        });
                    }
                }
            });
        }
    },

    bindFormEvents: function() {
        // Student form submit
        $(document).off('submit', '#studentForm').on('submit', '#studentForm', function(e) {
            e.preventDefault();
            SearchModals.submitStudentForm(this);
        });
        
        // Payment form submit
        $(document).off('submit', '#paymentForm').on('submit', '#paymentForm', function(e) {
            e.preventDefault();
            SearchModals.submitPaymentForm(this);
        });
    },

    submitStudentForm: function(form) {
        const $form = $(form);
        const formData = new FormData(form);
        const action = $form.data('action');
        
        $.ajax({
            url: action,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#studentFormModal').modal('hide');
                    SearchModals.showSuccess('Lưu thông tin học viên thành công!');
                    // Refresh search results if needed
                    if (typeof window.searchModalSystem !== 'undefined') {
                        window.searchModalSystem.performSearch();
                    }
                } else {
                    SearchModals.showFormErrors(response.errors || {});
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    SearchModals.showFormErrors(xhr.responseJSON.errors || {});
                } else {
                    SearchModals.showError('Có lỗi xảy ra khi lưu thông tin');
                }
            }
        });
    },

    submitPaymentForm: function(form) {
        const $form = $(form);
        const formData = new FormData(form);
        const action = $form.data('action');
        
        $.ajax({
            url: action,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#addPaymentModal').modal('hide');
                    SearchModals.showSuccess('Thêm thanh toán thành công!');
                    // Refresh search results if needed
                    if (typeof window.searchModalSystem !== 'undefined') {
                        window.searchModalSystem.performSearch();
                    }
                } else {
                    SearchModals.showFormErrors(response.errors || {});
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    SearchModals.showFormErrors(xhr.responseJSON.errors || {});
                } else {
                    SearchModals.showError('Có lỗi xảy ra khi thêm thanh toán');
                }
            }
        });
    },

    showFormErrors: function(errors) {
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        // Show new errors
        Object.keys(errors).forEach(field => {
            const input = $(`[name="${field}"]`);
            if (input.length > 0) {
                input.addClass('is-invalid');
                input.after(`<div class="invalid-feedback">${errors[field][0]}</div>`);
            }
        });
    },

    showSuccess: function(message) {
        // You can implement a toast notification here
        alert(message);
    },

    showError: function(message) {
        // You can implement a toast notification here
        alert(message);
    },

    // Utility functions
    formatCurrency: function(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount || 0);
    },

    formatDate: function(dateString) {
        if (!dateString) return 'Chưa cập nhật';
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    },

    getGenderText: function(gender) {
        switch(gender) {
            case 'male': return 'Nam';
            case 'female': return 'Nữ';
            case 'other': return 'Khác';
            default: return 'Chưa cập nhật';
        }
    },

    getPaymentMethodText: function(method) {
        switch(method) {
            case 'cash': return 'Tiền mặt';
            case 'bank_transfer': return 'Chuyển khoản';
            case 'card': return 'Thẻ';
            case 'qr_code': return 'QR Code';
            case 'sepay': return 'SEPAY';
            default: return method;
        }
    },

    getPaymentStatusText: function(status) {
        switch(status) {
            case 'confirmed': return 'Đã xác nhận';
            case 'pending': return 'Chờ xác nhận';
            case 'cancelled': return 'Đã hủy';
            default: return status;
        }
    },

    getAttendanceStatusText: function(status) {
        switch(status) {
            case 'present': return 'Có mặt';
            case 'absent': return 'Vắng mặt';
            case 'late': return 'Muộn';
            default: return status;
        }
    },

    getAttendanceStatusClass: function(status) {
        switch(status) {
            case 'present': return 'bg-success';
            case 'absent': return 'bg-danger';
            case 'late': return 'bg-warning';
            default: return 'bg-secondary';
        }
    }
};

// Bind global functions for backward compatibility
window.showStudentDetail = SearchModals.showStudentDetail;
window.showStudentHistory = SearchModals.showStudentHistory;
window.showStudentForm = SearchModals.showStudentForm;
window.showAddPayment = SearchModals.showAddPayment;
</script> 