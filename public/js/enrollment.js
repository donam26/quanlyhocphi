// Biến lưu ID ghi danh đang xem chi tiết
let currentEnrollmentId = null;

// Khởi tạo Select2 cho tìm kiếm học viên
function initStudentSearch() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        console.log('Initializing student search select2...');
        $('#student_search').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Tìm học viên theo tên, số điện thoại...',
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: '/api/search/autocomplete',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    console.log('Search params:', params);
                    return {
                        q: params.term
                    };
                },
                processResults: function (data) {
                    console.log('API response data:', data);
                    return {
                        results: $.map(data, function(item) {
                            return {
                                id: item.id,
                                text: item.text
                            };
                        })
                    };
                },
                cache: true
            }
        });

        // Auto-submit khi search select2 thay đổi
        $('#student_search').on('select2:select', function(e) {
            console.log('Selected:', e.params.data);
            var studentId = e.params.data.id;
            window.location.href = '/enrollments?student_id=' + studentId;
        });

        // Xóa tìm kiếm khi clear
        $('#student_search').on('select2:clear', function(e) {
            window.location.href = '/enrollments';
        });
    } else {
        console.error('jQuery or Select2 not loaded');
    }
}

// Hiển thị chi tiết ghi danh trong modal popup
window.showEnrollmentDetails = function(enrollmentId) {
    currentEnrollmentId = enrollmentId;

    // Hiển thị modal
    $('#viewEnrollmentModal').modal('show');

    // Hiển thị loading và ẩn nội dung
    $('#enrollment-loading').show();
    $('#enrollment-details').hide();

    // Cập nhật ID hiện tại vào modal
    $('#enrollment-id').text(enrollmentId);

    // Lấy thông tin ghi danh qua AJAX
    $.ajax({
        url: `/api/enrollments/${enrollmentId}`,
        method: 'GET',
        success: function(response) {
            if (!response.success) {
                showToast('Không thể tải thông tin ghi danh', 'error');
                return;
            }

            const enrollment = response.data;

            // Cập nhật thông tin cơ bản
            $('#enrollment-student').text(enrollment.student.full_name + ' - ' + enrollment.student.phone);
            $('#enrollment-course').text(enrollment.course_item.name);
            $('#enrollment-date').text(enrollment.formatted_enrollment_date || 'N/A');
            $('#enrollment-status').html(getEnrollmentStatusBadge(enrollment.status));

            // Cập nhật thông tin thanh toán
            $('#enrollment-fee').text(formatCurrency(enrollment.final_fee) + ' đ');
            $('#enrollment-paid').text(formatCurrency(enrollment.total_paid) + ' đ');

            const remaining = enrollment.remaining_amount;
            $('#enrollment-remaining').text(formatCurrency(remaining) + ' đ');
            $('#enrollment-remaining').toggleClass('text-danger', remaining > 0);
            $('#enrollment-remaining').toggleClass('text-success', remaining <= 0);

            $('#enrollment-payment-status').html(enrollment.is_fully_paid ?
                '<span class="badge bg-success">Đã thanh toán đủ</span>' :
                '<span class="badge bg-warning">Còn thiếu</span>');

            // Cập nhật ghi chú
            if (enrollment.notes) {
                $('#enrollment-notes').text(enrollment.notes);
                $('#enrollment-notes-section').show();
            } else {
                $('#enrollment-notes-section').hide();
            }

            // Cập nhật thanh toán
            if (enrollment.payments && enrollment.payments.length > 0) {
                let paymentsHtml = '<div class="table-responsive"><table class="table table-sm">';
                paymentsHtml += '<thead><tr><th>Ngày</th><th>Số tiền</th><th>Phương thức</th><th>Trạng thái</th></tr></thead><tbody>';

                enrollment.payments.forEach(function(payment) {
                    const formattedDate = payment.payment_date ? formatDate(payment.payment_date) : 'N/A';
                    const formattedAmount = formatCurrency(payment.amount) + ' đ';
                    let methodText = '';
                    switch(payment.payment_method) {
                        case 'cash': methodText = 'Tiền mặt'; break;
                        case 'bank_transfer': methodText = 'Chuyển khoản'; break;
                        case 'sepay': methodText = 'SEPAY'; break;
                        default: methodText = payment.payment_method;
                    }

                    let statusBadge = '';
                    switch(payment.status) {
                        case 'confirmed': statusBadge = '<span class="badge bg-success">Đã xác nhận</span>'; break;
                        case 'pending': statusBadge = '<span class="badge bg-warning">Đang chờ</span>'; break;
                        case 'cancelled': statusBadge = '<span class="badge bg-danger">Đã hủy</span>'; break;
                        case 'refunded': statusBadge = '<span class="badge bg-info">Đã hoàn tiền</span>'; break;
                        default: statusBadge = `<span class="badge bg-secondary">${payment.status}</span>`;
                    }

                    paymentsHtml += `<tr>
                        <td>${formattedDate}</td>
                        <td>${formattedAmount}</td>
                        <td>${methodText}</td>
                        <td>${statusBadge}</td>
                    </tr>`;
                });

                paymentsHtml += '</tbody></table></div>';
                $('#enrollment-payments').html(paymentsHtml);
                $('#enrollment-payments-section').show();

                // Cập nhật link thêm thanh toán
                $('#btn-add-payment').attr('href', `/payments/create?enrollment_id=${enrollmentId}`);
            } else {
                $('#enrollment-payments').html('<p class="text-muted">Chưa có thanh toán nào.</p>');
                $('#enrollment-payments-section').show();
            }

            // Cập nhật nút chỉnh sửa
            $('#btn-edit-enrollment').attr('onclick', `editEnrollment(${enrollmentId})`);

            // Ẩn loading và hiện nội dung
            $('#enrollment-loading').hide();
            $('#enrollment-details').show();
        },
        error: function(xhr) {
            console.error('Lỗi API:', xhr.responseText);
            showToast('Không thể tải thông tin ghi danh', 'error');
            $('#enrollment-loading').hide();
        }
    });
};

// Chỉnh sửa ghi danh
window.editEnrollment = function(enrollmentId) {
    currentEnrollmentId = enrollmentId;

    // Đóng các modal khác nếu đang mở
    $('#viewEnrollmentModal').modal('hide');
    $('#studentDetailModal').modal('hide'); // Đóng modal học viên nếu đang mở

    // Mở modal chỉnh sửa
    $('#editEnrollmentModal').modal('show');

    // Hiển thị loading và ẩn form
    $('#edit-enrollment-loading').show();
    $('#enrollmentEditForm').hide();

    // Lấy thông tin ghi danh
    $.ajax({
        url: `/api/enrollments/${enrollmentId}`,
        method: 'GET',
        success: function(response) {
            if (!response.success) {
                showToast('Không thể tải thông tin ghi danh', 'error');
                return;
            }

            const enrollment = response.data;

            // Cập nhật ID vào form
            $('#edit-enrollment-id').val(enrollment.id);
            $('#edit-student-name').text(enrollment.student.full_name);
            $('#edit-course-item-id').val(enrollment.course_item_id);
            $('#edit-course-fee').val(enrollment.course_item.fee);

            // Format và set ngày ghi danh
            if (enrollment.enrollment_date) {
                const date = new Date(enrollment.enrollment_date);
                const year = date.getFullYear();
                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                const day = date.getDate().toString().padStart(2, '0');
                $('#edit-enrollment-date').val(`${year}-${month}-${day}`);
            } else {
                $('#edit-enrollment-date').val('');
            }

            // Cập nhật trạng thái
            $('#edit-status').val(enrollment.status);

            // Cập nhật học phí và giảm giá
            $('#edit-discount-percentage').val(enrollment.discount_percentage || 0);
            $('#edit-discount-amount').val(enrollment.discount_amount || 0);
            $('#edit-final-fee').val(enrollment.final_fee);
            $('#edit-final-fee-display').val(formatCurrency(enrollment.final_fee));
            $('#edit-notes').val(enrollment.notes);

            // Thiết lập tính toán học phí
            setupFeeCalculation();

            // Ẩn loading và hiện form
            $('#edit-enrollment-loading').hide();
            $('#enrollmentEditForm').show();
        },
        error: function() {
            showToast('Không thể tải thông tin ghi danh', 'error');
            $('#edit-enrollment-loading').hide();
        }
    });
};

// Tạo ghi danh mới
window.createEnrollment = function() {
    // Mở modal tạo ghi danh
    $('#createEnrollmentModal').modal('show');
};

// Lưu thay đổi ghi danh
$(document).on('click', '#save-enrollment-btn', function() {
    // Disable nút để tránh click nhiều lần
    resetSaveButton(true);

    const enrollmentId = $('#edit-enrollment-id').val();
    const formData = new FormData(document.getElementById('enrollmentEditForm'));

    $.ajax({
        url: `/api/enrollments/${enrollmentId}`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Đóng modal và hiển thị thông báo
                $('#editEnrollmentModal').modal('hide');
                showToast('Cập nhật ghi danh thành công');

                // Reload trang sau 1s
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                showToast(response.message || 'Có lỗi xảy ra khi cập nhật', 'error');
                resetSaveButton();
            }
        },
        error: function(xhr) {
            if (xhr.status === 422 && xhr.responseJSON) {
                const errors = xhr.responseJSON?.errors;

                // Reset lỗi trước đó
                $('.is-invalid').removeClass('is-invalid');

                Object.keys(errors).forEach(key => {
                    const errorMessage = errors[key][0];
                    const inputField = $(`#edit-${key.replace('_', '-')}`);
                    const errorField = $(`#edit-${key.replace('_', '-')}-error`);

                    inputField.addClass('is-invalid');
                    errorField.text(errorMessage);
                });

                showToast('Vui lòng kiểm tra lại thông tin', 'error');
            } else {
                showToast('Có lỗi xảy ra khi cập nhật ghi danh', 'error');
            }

            resetSaveButton();
        }
    });
});

// Lưu ghi danh mới
$(document).on('click', '#save-new-enrollment-btn', function() {
    // Disable nút để tránh click nhiều lần
    resetCreateButton(true);

    const formData = new FormData(document.getElementById('enrollmentCreateForm'));

    $.ajax({
        url: '/api/enrollments',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Đóng modal và hiển thị thông báo
                $('#createEnrollmentModal').modal('hide');
                showToast('Tạo ghi danh mới thành công');

                // Reset form
                document.getElementById('enrollmentCreateForm').reset();

                // Reload trang sau 1s
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                showToast(response.message || 'Có lỗi xảy ra khi tạo ghi danh', 'error');
                resetCreateButton();
            }
        },
        error: function(xhr) {
            if (xhr.status === 422 && xhr.responseJSON) {
                const errors = xhr.responseJSON?.errors;

                // Reset lỗi trước đó
                $('.is-invalid').removeClass('is-invalid');

                Object.keys(errors).forEach(key => {
                    const errorMessage = errors[key][0];
                    const inputField = $(`#create-${key.replace('_', '-')}`);
                    const errorField = $(`#create-${key.replace('_', '-')}-error`);

                    inputField.addClass('is-invalid');
                    errorField.text(errorMessage);
                });

                showToast('Vui lòng kiểm tra lại thông tin', 'error');
            } else {
                showToast('Có lỗi xảy ra khi tạo ghi danh mới', 'error');
            }

            resetCreateButton();
        }
    });
});

// Thiết lập tính toán học phí
function setupFeeCalculation() {
    // Lấy giá gốc từ trường ẩn
    const baseFee = parseFloat($('#edit-course-fee').val()) || 0;

    function calculateFinalFee() {
        let finalFee = baseFee;

        const discountPercent = parseFloat($('#edit-discount-percentage').val()) || 0;
        const discountAmount = parseFloat($('#edit-discount-amount').val()) || 0;

        if (discountPercent > 0) {
            finalFee = finalFee * (1 - discountPercent / 100);
        } else if (discountAmount > 0) {
            finalFee = finalFee - discountAmount;
        }

        // Đảm bảo không âm
        finalFee = Math.max(0, finalFee);

        // Cập nhật các trường hiển thị
        $('#edit-final-fee').val(finalFee);
        $('#edit-final-fee-display').val(formatCurrency(finalFee));
    }

    // Gán sự kiện
    $('#edit-discount-percentage').on('input', function() {
        $('#edit-discount-amount').val(0);
        calculateFinalFee();
    });

    $('#edit-discount-amount').on('input', function() {
        $('#edit-discount-percentage').val(0);
        calculateFinalFee();
    });

    // Tính toán ban đầu
    calculateFinalFee();
}

// Reset nút Save
function resetSaveButton(isLoading = false) {
    if (isLoading) {
        $('#save-enrollment-btn').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang lưu...');
        $('#save-enrollment-btn').prop('disabled', true);
    } else {
        $('#save-enrollment-btn').html('<i class="fas fa-save me-1"></i> Lưu thay đổi');
        $('#save-enrollment-btn').prop('disabled', false);
    }
}

// Reset nút Create
function resetCreateButton(isLoading = false) {
    if (isLoading) {
        $('#save-new-enrollment-btn').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang lưu...');
        $('#save-new-enrollment-btn').prop('disabled', true);
    } else {
        $('#save-new-enrollment-btn').html('<i class="fas fa-save me-1"></i> Tạo ghi danh');
        $('#save-new-enrollment-btn').prop('disabled', false);
    }
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

// Lấy badge trạng thái ghi danh
function getEnrollmentStatusBadge(status) {
    switch (status) {
        case 'active':
            return '<span class="badge bg-success">Đang học</span>';
        case 'waiting':
            return '<span class="badge bg-warning text-dark">Danh sách chờ</span>';
        case 'completed':
            return '<span class="badge bg-info">Đã hoàn thành</span>';
        case 'cancelled':
            return '<span class="badge bg-danger">Đã hủy</span>';
        default:
            return `<span class="badge bg-secondary">${status}</span>`;
    }
}

// Format tiền tệ
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount);
}

// Sử dụng showToast global function nếu có, nếu không thì định nghĩa
if (typeof window.showToast !== 'function') {
    window.showToast = function(message, type = 'success') {
        const toast = $(`
            <div class="toast align-items-center text-white bg-${type === 'error' ? 'danger' : type}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `);

        $('.toast-container').append(toast);
        const bsToast = new bootstrap.Toast(toast, {
            delay: 3000
        });

        bsToast.show();

        // Xóa toast sau khi ẩn
        toast.on('hidden.bs.toast', function() {
            $(this).remove();
        });
    };
}

// Alias để backward compatibility
function showToast(message, type = 'success') {
    if (typeof window.showToast === 'function') {
        window.showToast(message, type);
    }
}

// Khởi tạo Select2 khi trang đã tải
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM fully loaded, initializing enrollment.js');

    // Đợi 500ms để đảm bảo jQuery và Select2 đã tải xong
    setTimeout(function() {
        initStudentSearch();
    }, 500);
});


