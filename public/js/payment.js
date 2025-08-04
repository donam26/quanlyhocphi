// Biến toàn cục
let currentPaymentId = null;

// Khởi tạo khi tài liệu đã sẵn sàng
document.addEventListener('DOMContentLoaded', function() {
    console.log('Payment.js loaded');
    
    // Khởi tạo Select2 cho tìm kiếm học viên
    setTimeout(function() {
        initStudentSearch();
    }, 500);

    // Xử lý sự kiện cho nút tạo thanh toán mới
    document.querySelectorAll('.create-payment-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Create payment button clicked');
            createPayment();
        });
    });
    
    // Xử lý sự kiện cho nút tạo thanh toán từ ghi danh
    document.querySelectorAll('.btn-create-payment').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Create payment from enrollment button clicked');
            const enrollmentId = this.getAttribute('data-enrollment-id');
            createPayment(enrollmentId);
        });
    });
});

// Khởi tạo Select2 cho tìm kiếm học viên
function initStudentSearch() {
    if ($('#student_search').length > 0) {
        $('#student_search').select2({
            placeholder: 'Nhập tên hoặc SĐT học viên...',
            minimumInputLength: 2,
            dropdownParent: $('#createPaymentModal'),
            ajax: {
                url: '/api/search/autocomplete',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    return {
                        results: data
                    };
                },
                cache: true
            }
        });

        // Xử lý sự kiện khi chọn học viên
        $('#student_search').on('select2:select', function (e) {
            var data = e.params.data;
            if (data.enrollments && data.enrollments.length > 0) {
                var detailsHtml = '<div class="alert alert-info mb-3">Chọn ghi danh để thanh toán:</div><div class="list-group mb-3">';
                data.enrollments.forEach(function(enrollment) {
                    detailsHtml += `
                        <a href="#" class="list-group-item list-group-item-action select-enrollment" data-id="${enrollment.id}">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">${enrollment.course_name}</h5>
                                <small class="text-danger fw-bold">${formatCurrency(enrollment.remaining_fee)} VNĐ còn thiếu</small>
                            </div>
                            <p class="mb-1">Học phí: ${formatCurrency(enrollment.final_fee)} VNĐ</p>
                        </a>
                    `;
                });
                detailsHtml += '</div>';
                $('#enrollment_details').html(detailsHtml);
                $('#enrollment_info').show();
            } else {
                $('#enrollment_details').html('<div class="alert alert-warning">Học viên này chưa ghi danh khóa học nào hoặc đã thanh toán đủ.</div>');
                $('#enrollment_info').show();
                $('#payment_form').hide();
            }
        });
    }

    // Xử lý sự kiện khi chọn ghi danh
    $(document).on('click', '.select-enrollment', function(e) {
        e.preventDefault();
        var enrollmentId = $(this).data('id');
        $('#enrollment_id').val(enrollmentId);
        
        $('.select-enrollment').removeClass('active');
        $(this).addClass('active');
        
        // Suggest remaining fee amount
        var remainingFee = $(this).find('small').text().replace(/[^\d]/g, '');
        $('#amount').val(remainingFee);
        
        // Show payment form
        $('#payment_form').show();
    });
}

// Hiển thị modal tạo thanh toán mới
function createPayment(enrollmentId = null) {
    // Reset form
    $('#createPaymentForm')[0].reset();
    $('#enrollment_info').hide();
    $('#payment_form').hide();
    
    // Nếu có enrollmentId, thiết lập giá trị
    if (enrollmentId) {
        $('#enrollment_id').val(enrollmentId);
        // Tải thông tin ghi danh
        loadEnrollmentInfo(enrollmentId);
    }
    
    // Hiển thị modal
    $('#createPaymentModal').modal('show');
}

// Tải thông tin ghi danh
function loadEnrollmentInfo(enrollmentId) {
    $.ajax({
        url: `/api/enrollments/${enrollmentId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const enrollment = response.data;
                
                // Hiển thị thông tin ghi danh
                var enrollmentHtml = `
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Thông tin học viên</h5>
                            <p><strong>Họ tên:</strong> ${enrollment.student.full_name}</p>
                            <p><strong>SĐT:</strong> ${enrollment.student.phone}</p>
                            <p><strong>Email:</strong> ${enrollment.student.email || 'Không có'}</p>
                            
                            <h5 class="card-title mt-4">Thông tin khóa học</h5>
                            <p><strong>Khóa học:</strong> ${enrollment.course_item.name}</p>
                            <p><strong>Học phí:</strong> ${formatCurrency(enrollment.final_fee)} VNĐ</p>
                            <p><strong>Còn thiếu:</strong> <span class="text-danger fw-bold">${formatCurrency(enrollment.remaining_amount)} VNĐ</span></p>
                        </div>
                    </div>
                `;
                $('#enrollment_details').html(enrollmentHtml);
                $('#enrollment_info').show();
                
                // Set suggested amount
                $('#amount').val(enrollment.remaining_amount);
                
                // Show payment form
                $('#payment_form').show();
            }
        },
        error: function() {
            $('#enrollment_details').html('<div class="alert alert-danger">Không thể tải thông tin ghi danh.</div>');
            $('#enrollment_info').show();
        }
    });
}

// Hiển thị chi tiết thanh toán
function showPaymentDetails(paymentId) {
    currentPaymentId = paymentId;
    
    // Hiển thị loading
    $('#paymentDetailsContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Đang tải thông tin...</p></div>');
    $('#paymentDetailsModal').modal('show');
    
    // Tải thông tin thanh toán
    $.ajax({
        url: `/api/payments/${paymentId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const payment = response.data;
                
                // Hiển thị thông tin thanh toán
                var detailsHtml = `
                    <div class="card" id="payment-receipt">
                        <div class="card-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h4>Thông tin Học viên</h4>
                                    <p><strong>Họ tên:</strong> ${payment.enrollment.student.full_name}</p>
                                    <p><strong>SĐT:</strong> ${payment.enrollment.student.phone}</p>
                                    <p><strong>Email:</strong> ${payment.enrollment.student.email || 'Không có'}</p>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <h4>Thông tin Phiếu thu</h4>
                                    <p><strong>Mã phiếu thu:</strong> #${payment.id}</p>
                                    <p><strong>Ngày tạo:</strong> ${formatDate(payment.created_at)}</p>
                                    <p><strong>Ngày thanh toán:</strong> ${formatDate(payment.payment_date)}</p>
                                    <p><strong>Trạng thái:</strong> 
                                        <span class="badge bg-${payment.status == 'confirmed' ? 'success' : (payment.status == 'pending' ? 'warning' : 'danger')}">
                                            ${getPaymentStatusText(payment.status)}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <hr>

                            <h4>Chi tiết Thanh toán</h4>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Khóa học</th>
                                        <th>Phương thức</th>
                                        <th class="text-end">Số tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>${payment.enrollment.course_item.name}</td>
                                        <td>${getPaymentMethodText(payment.payment_method)}</td>
                                        <td class="text-end">${formatCurrency(payment.amount)} VNĐ</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2" class="text-end fw-bold">Tổng cộng</td>
                                        <td class="text-end fw-bold">${formatCurrency(payment.amount)} VNĐ</td>
                                    </tr>
                                </tfoot>
                            </table>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <strong>Ghi chú:</strong>
                                    <p>${payment.notes || 'Không có ghi chú.'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('#paymentDetailsContent').html(detailsHtml);
                
                // Cập nhật các nút thao tác
                $('#editPaymentBtn').attr('onclick', `editPayment(${payment.id})`);
                $('#printPaymentBtn').attr('onclick', `printPayment(${payment.id})`);
                
                if (payment.status === 'pending') {
                    $('#confirmPaymentBtn').show();
                    $('#confirmPaymentBtn').attr('onclick', `confirmPayment(${payment.id})`);
                } else {
                    $('#confirmPaymentBtn').hide();
                }
            } else {
                $('#paymentDetailsContent').html('<div class="alert alert-danger">Không thể tải thông tin thanh toán.</div>');
            }
        },
        error: function() {
            $('#paymentDetailsContent').html('<div class="alert alert-danger">Có lỗi xảy ra khi tải thông tin thanh toán.</div>');
        }
    });
}

// Hiển thị form chỉnh sửa thanh toán
function editPayment(paymentId) {
    currentPaymentId = paymentId;
    
    // Đóng modal chi tiết nếu đang mở
    $('#paymentDetailsModal').modal('hide');
    
    // Hiển thị loading
    $('#editPaymentContent').html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Đang tải thông tin...</p></div>');
    $('#editPaymentModal').modal('show');
    
    // Tải thông tin thanh toán
    $.ajax({
        url: `/api/payments/${paymentId}`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const payment = response.data;
                
                // Hiển thị form chỉnh sửa
                var formHtml = `
                    <form id="editPaymentForm">
                        <input type="hidden" name="_token" value="${$('meta[name="csrf-token"]').attr('content')}">
                        
                        <div class="mb-4 p-3 border rounded bg-light">
                            <h6>Thông tin ghi danh</h6>
                            <p><strong>Học viên:</strong> ${payment.enrollment.student.full_name}</p>
                            <p><strong>Khóa học:</strong> ${payment.enrollment.course_item.name}</p>
                            <p><strong>Học phí:</strong> ${formatCurrency(payment.enrollment.final_fee)} VNĐ</p>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_amount" class="form-label">Số tiền <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="edit_amount" class="form-control" value="${payment.amount}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="edit_payment_date" class="form-label">Ngày thanh toán <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" id="edit_payment_date" class="form-control" value="${payment.payment_date.split(' ')[0]}" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="edit_payment_method" class="form-label">Phương thức thanh toán <span class="text-danger">*</span></label>
                                <select name="payment_method" id="edit_payment_method" class="form-select" required>
                                    <option value="cash" ${payment.payment_method == 'cash' ? 'selected' : ''}>Tiền mặt</option>
                                    <option value="bank_transfer" ${payment.payment_method == 'bank_transfer' ? 'selected' : ''}>Chuyển khoản</option>
                                    <option value="card" ${payment.payment_method == 'card' ? 'selected' : ''}>Thẻ</option>
                                    <option value="other" ${payment.payment_method == 'other' ? 'selected' : ''}>Khác</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="edit_status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select name="status" id="edit_status" class="form-select" required>
                                    <option value="pending" ${payment.status == 'pending' ? 'selected' : ''}>Chờ xác nhận</option>
                                    <option value="confirmed" ${payment.status == 'confirmed' ? 'selected' : ''}>Đã xác nhận</option>
                                    <option value="cancelled" ${payment.status == 'cancelled' ? 'selected' : ''}>Đã hủy</option>
                                </select>
                            </div>

                            <div class="col-12 mb-3">
                                <label for="edit_notes" class="form-label">Ghi chú</label>
                                <textarea name="notes" id="edit_notes" class="form-control" rows="3">${payment.notes || ''}</textarea>
                            </div>
                        </div>
                    </form>
                `;
                $('#editPaymentContent').html(formHtml);
                
                // Kích hoạt nút lưu
                $('#savePaymentBtn').attr('onclick', `savePayment(${payment.id})`);
            } else {
                $('#editPaymentContent').html('<div class="alert alert-danger">Không thể tải thông tin thanh toán.</div>');
            }
        },
        error: function() {
            $('#editPaymentContent').html('<div class="alert alert-danger">Có lỗi xảy ra khi tải thông tin thanh toán.</div>');
        }
    });
}

// Lưu thông tin thanh toán đã chỉnh sửa
function savePayment(paymentId) {
    const formData = new FormData(document.getElementById('editPaymentForm'));
    
    // Hiển thị trạng thái đang lưu
    $('#savePaymentBtn').html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...').prop('disabled', true);
    
    $.ajax({
        url: `/api/payments/${paymentId}`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Đóng modal và hiển thị thông báo thành công
                $('#editPaymentModal').modal('hide');
                showToast('success', 'Cập nhật thành công', 'Thông tin thanh toán đã được cập nhật.');
                
                // Tải lại trang sau 1 giây
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                showToast('error', 'Lỗi', response.message || 'Có lỗi xảy ra khi cập nhật thanh toán.');
                $('#savePaymentBtn').html('<i class="fas fa-save me-2"></i>Lưu thay đổi').prop('disabled', false);
            }
        },
        error: function(xhr) {
            let errorMessage = 'Có lỗi xảy ra khi cập nhật thanh toán.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            showToast('error', 'Lỗi', errorMessage);
            $('#savePaymentBtn').html('<i class="fas fa-save me-2"></i>Lưu thay đổi').prop('disabled', false);
        }
    });
}

// Xác nhận thanh toán
function confirmPayment(paymentId) {
    if (!confirm('Bạn có chắc chắn muốn xác nhận thanh toán này không?')) {
        return;
    }
    
    $('#confirmPaymentBtn').html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...').prop('disabled', true);
    
    $.ajax({
        url: `/api/payments/${paymentId}/confirm`,
        method: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                // Đóng modal và hiển thị thông báo thành công
                $('#paymentDetailsModal').modal('hide');
                showToast('success', 'Xác nhận thành công', 'Thanh toán đã được xác nhận thành công.');
                
                // Tải lại trang sau 1 giây
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                showToast('error', 'Lỗi', response.message || 'Có lỗi xảy ra khi xác nhận thanh toán.');
                $('#confirmPaymentBtn').html('<i class="fas fa-check-circle me-2"></i>Xác nhận thanh toán').prop('disabled', false);
            }
        },
        error: function() {
            showToast('error', 'Lỗi', 'Có lỗi xảy ra khi xác nhận thanh toán.');
            $('#confirmPaymentBtn').html('<i class="fas fa-check-circle me-2"></i>Xác nhận thanh toán').prop('disabled', false);
        }
    });
}

// In phiếu thu
function printPayment(paymentId) {
    window.open(`/payments/receipt/${paymentId}`, '_blank');
}

// Lưu thanh toán mới
function saveNewPayment() {
    const formData = new FormData(document.getElementById('createPaymentForm'));
    
    // Hiển thị trạng thái đang lưu
    $('#saveNewPaymentBtn').html('<i class="fas fa-spinner fa-spin"></i> Đang lưu...').prop('disabled', true);
    
    $.ajax({
        url: '/api/payments',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Đóng modal và hiển thị thông báo thành công
                $('#createPaymentModal').modal('hide');
                showToast('success', 'Tạo thành công', 'Thanh toán mới đã được tạo thành công.');
                
                // Tải lại trang sau 1 giây
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                showToast('error', 'Lỗi', response.message || 'Có lỗi xảy ra khi tạo thanh toán.');
                $('#saveNewPaymentBtn').html('<i class="fas fa-save me-2"></i>Lưu thanh toán').prop('disabled', false);
            }
        },
        error: function(xhr) {
            let errorMessage = 'Có lỗi xảy ra khi tạo thanh toán.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            showToast('error', 'Lỗi', errorMessage);
            $('#saveNewPaymentBtn').html('<i class="fas fa-save me-2"></i>Lưu thanh toán').prop('disabled', false);
        }
    });
}

// Hiển thị thông báo toast
function showToast(type, title, message) {
    const toastClasses = {
        'success': 'bg-success text-white',
        'error': 'bg-danger text-white',
        'warning': 'bg-warning',
        'info': 'bg-info text-white'
    };
    
    const toast = `
        <div class="toast align-items-center ${toastClasses[type]} border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong>: ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    $('#toast-container').append(toast);
    
    // Tự động ẩn sau 5 giây
    setTimeout(function() {
        $('.toast').toast('hide');
    }, 5000);
}

// Định dạng tiền tệ
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount);
}

// Định dạng ngày tháng
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

// Lấy text trạng thái thanh toán
function getPaymentStatusText(status) {
    const statusMap = {
        'pending': 'Chờ xác nhận',
        'confirmed': 'Đã xác nhận',
        'cancelled': 'Đã hủy',
        'refunded': 'Đã hoàn tiền'
    };
    return statusMap[status] || status;
}

// Lấy text phương thức thanh toán
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

// Xuất Excel
function exportPayments(courseId) {
    window.location.href = `/payments/course/${courseId}/export`;
}

// Xem lịch sử thanh toán
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

// Chỉnh sửa học phí
function editEnrollmentFee(enrollmentId, currentFee) {
    console.log('Edit enrollment fee', enrollmentId, currentFee);
    
    // Thiết lập giá trị cho modal
    document.getElementById('enrollment_id').value = enrollmentId;
    document.getElementById('final_fee').value = currentFee;
    
    // Reset form
    document.getElementById('discount_type').value = 'custom';
    document.getElementById('discount_percentage_group').classList.add('d-none');
    document.getElementById('discount_amount_group').classList.add('d-none');
    document.getElementById('final_fee_group').classList.remove('d-none');
    
    // Hiển thị modal
    const modal = new bootstrap.Modal(document.getElementById('editFeeModal'));
    modal.show();
} 