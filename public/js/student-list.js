// Biến lưu ID học viên đang xem chi tiết
let currentStudentId = null;

// Hiển thị chi tiết học viên trong modal popup
window.showStudentDetails = function(studentId) {
    currentStudentId = studentId;

    // Hiển thị modal
    $('#viewStudentModal').modal('show');

    // Hiển thị loading và ẩn nội dung
    $('#student-loading').show();
    $('#student-details').hide();

    // Lấy thông tin học viên qua AJAX
    $.ajax({
        url: `/api/students/${studentId}/info`,
        method: 'GET',
        success: function(data) {
            if (!data.success) {
                showToast('Không thể tải thông tin học viên', 'error');
                return;
            }

            const student = data.data;

            // Cập nhật thông tin cơ bản
            $('#student-name').text(student.full_name);
            $('#student-id').text(student.id);

            // Thông tin cá nhân
            $('#student-gender').text(formatGender(student.gender));
            $('#student-dob').text(student.formatted_date_of_birth || 'Chưa có');
            $('#student-phone').text(student.phone);
            $('#student-email').text(student.email || 'Chưa có');

            // Tỉnh thành
            let addressText = '';
            if (student.province) {
                addressText = student.province.name + ' (' + getRegionName(student.province.region) + ')';
            }
            $('#student-address').text(addressText || 'Chưa có');

            // Thông tin bổ sung
            $('#student-workplace').text(student.current_workplace || 'Chưa có');
            $('#student-experience').text(student.accounting_experience_years
                ? student.accounting_experience_years + ' năm' : 'Chưa có');

            $('#student-place-of-birth').text(student.place_of_birth || 'Chưa có');
            $('#student-nation').text(student.nation || 'Chưa có');


            // Hiển thị ghi chú
            if (student.notes) {
                $('#student-notes-section').show();
                $('#student-notes').text(student.notes);
            } else {
                $('#student-notes-section').hide();
            }

            // Xử lý các trường thông tin đặc biệt
            let customFieldsHtml = '<table class="table table-sm">';

            // Hiển thị trường Hồ sơ bản cứng
            customFieldsHtml += `<tr>
                <th width="40%">Hồ sơ bản cứng:</th>
                <td>${student.hard_copy_documents === 'submitted' ? 'Đã nộp' : student.hard_copy_documents === 'not_submitted' ? 'Chưa nộp' : '-'}</td>
            </tr>`;

            // Hiển thị trường Bằng cấp
            let educationText = '-';
            if (student.education_level) {
                switch (student.education_level) {
                    case 'vocational': educationText = 'Trung cấp'; break;
                    case 'associate': educationText = 'Cao đẳng'; break;
                    case 'bachelor': educationText = 'Đại học'; break;
                    case 'master': educationText = 'Thạc sĩ'; break;
                    case 'secondary': educationText = 'VB2'; break;
                    default: educationText = student.education_level;
                }
            }
            customFieldsHtml += `<tr>
                <th width="40%">Bằng cấp:</th>
                <td>${educationText}</td>
            </tr>`;

            // Hiển thị trường Chuyên môn đào tạo
            customFieldsHtml += `<tr>
                <th width="40%">Chuyên môn đào tạo:</th>
                <td>${student.training_specialization || '-'}</td>
            </tr>`;

         
            customFieldsHtml += '</table>';

            $('#student-custom-fields').html(customFieldsHtml);
            $('#student-custom-fields-section').show();

            // Hiển thị khóa học đã đăng ký
            if (student.enrollments && student.enrollments.length > 0) {
                let enrollmentsHtml = '<div class="list-group">';
                student.enrollments.forEach(enrollment => {
                    const statusBadge = getEnrollmentStatusBadge(enrollment.status);
                    const paymentBadge = enrollment.is_fully_paid
                        ? '<span class="badge bg-success ms-1">Đã thanh toán</span>'
                        : '<span class="badge bg-danger ms-1">Chưa thanh toán</span>';

                    enrollmentsHtml += `<div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-1">${enrollment.course_item.name}</h6>
                            <div class="d-flex align-items-center">
                                ${statusBadge}
                                ${paymentBadge}
                                <!-- Action buttons -->
                                <div class="btn-group btn-group-sm ms-2" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" 
                                            onclick="editEnrollmentInStudentPopup(${enrollment.id})"
                                            title="Chỉnh sửa ghi danh">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    ${enrollment.status !== 'cancelled' ? 
                                        `<button type="button" class="btn btn-outline-danger btn-sm"
                                                onclick="cancelEnrollmentInPopup(${enrollment.id})"
                                                title="Hủy ghi danh">
                                            <i class="fas fa-times"></i>
                                        </button>` : ''
                                    }
                                </div>
                            </div>
                        </div>
                        <p class="mb-1 small">Ngày đăng ký: ${enrollment.formatted_enrollment_date}</p>
                        <p class="mb-1 small">Học phí: ${formatCurrency(enrollment.final_fee)} đ</p>
                        ${enrollment.discount_percentage > 0 ? 
                            `<p class="mb-1 small text-success">Chiết khấu: ${enrollment.discount_percentage}%</p>` : 
                            enrollment.discount_amount > 0 ? 
                            `<p class="mb-1 small text-success">Chiết khấu: ${formatCurrency(enrollment.discount_amount)} đ</p>` : ''
                        }
                        ${enrollment.notes ? 
                            `<p class="mb-0 small text-muted"><i>Ghi chú: ${enrollment.notes}</i></p>` : ''
                        }
                    </div>`;
                });
                enrollmentsHtml += '</div>';

                $('#student-enrollments').html(enrollmentsHtml);
                $('#student-enrollments-section').show();
            } else {
                $('#student-enrollments').html('<p class="text-muted">Học viên chưa đăng ký khóa học nào.</p>');
                $('#student-enrollments-section').show();
            }

            // Cập nhật các nút hành động
            $('#btn-add-enrollment').attr('href', `/enrollments/create?student_id=${studentId}`);

            // Ẩn loading và hiện nội dung
            $('#student-loading').hide();
            $('#student-details').show();
        },
        error: function() {
            showToast('Không thể tải thông tin học viên', 'error');
            $('#student-loading').hide();
        }
    });
};

// Chỉnh sửa học viên
window.editStudent = function(studentId) {
    currentStudentId = studentId;

    // Đóng modal xem chi tiết nếu đang mở
    $('#viewStudentModal').modal('hide');

    // Mở modal chỉnh sửa
    $('#editStudentModal').modal('show');

    // Hiển thị loading và ẩn form
    $('#edit-student-loading').show();
    $('#edit-student-form').hide();

    // Lấy thông tin học viên
    $.ajax({
        url: `/api/students/${studentId}/info`,
        method: 'GET',
        success: function(data) {
            if (!data.success) {
                showToast('Không thể tải thông tin học viên', 'error');
                return;
            }

            const student = data.data;

            // Điền thông tin vào form
            $('#edit-student-id').val(student.id);
            $('#edit-first-name').val(student.first_name || '');
            $('#edit-last-name').val(student.last_name || '');
            $('#edit-phone').val(student.phone);
            $('#edit-email').val(student.email);
            $('#edit-gender').val(student.gender || '');

            if (student.date_of_birth) {
                // Sử dụng formatDate để đảm bảo định dạng dd/mm/yyyy
                $('#edit-date-of-birth').val(student.formatted_date_of_birth || formatDate(student.date_of_birth) || '');
            }

            $('#edit-notes').val(student.notes);
            // Nơi công tác hiện tại (input text)
            $('#edit-current-workplace').val(student.current_workplace || '');
            $('#edit-experience').val(student.accounting_experience_years);

            // Cập nhật các trường mới
            $('#edit-hard-copy-documents').val(student.hard_copy_documents || '');
            $('#edit-education-level').val(student.education_level || '');
            $('#edit-workplace').val(student.workplace || '');
            $('#edit-experience-years').val(student.experience_years || '');

            // Dân tộc
            $('#edit-nation').val(student.nation || '');

            // Nơi sinh (hiển thị bằng select2, submit qua hidden)
            $('#edit-place-of-birth').val(student.place_of_birth || '');
            if (student.place_of_birth) {
                const optPOB = new Option(student.place_of_birth, student.place_of_birth, true, true);
                $('#edit-place-of-birth-select').empty().append(optPOB).trigger('change');
            } else {
                $('#edit-place-of-birth-select').empty().val(null).trigger('change');
            }

            // Xử lý tỉnh thành
            setTimeout(function() {
                if (student.province) {
                    // Tạo option mới cho province và thêm vào select
                    const option = new Option(
                        student.province.name + ' (' + getRegionName(student.province.region) + ')',
                        student.province.id,
                        true,
                        true
                    );

                    // Chờ select2 được khởi tạo xong
                    $('#edit-province').empty().append(option).trigger('change');
                } else {
                    $('#edit-province').empty().val(null).trigger('change');
                }
            }, 300);

            // Ẩn loading và hiện form
            $('#edit-student-loading').hide();
            $('#edit-student-form').show();
        },
        error: function() {
            showToast('Không thể tải thông tin học viên', 'error');
            $('#edit-student-loading').hide();
        }
    });
};

// Lưu thay đổi thông tin học viên
$(document).on('click', '#save-student-btn', function() {
    const studentId = $('#edit-student-id').val();

    // Thu thập dữ liệu từ form
    const formData = new FormData(document.getElementById('studentEditForm'));

    // Không cần thu thập trường tùy chỉnh nữa vì đã có các trường cụ thể

    // Debug formData
    console.log('Form data to be sent:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }

    // Hiển thị loading
    $('#save-student-btn').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang lưu...');
    $('#save-student-btn').prop('disabled', true);

    // Gửi request cập nhật
    $.ajax({
        url: `/api/students/${studentId}/update`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Đóng modal và hiển thị thông báo
                $('#editStudentModal').modal('hide');
                showToast('Cập nhật thông tin học viên thành công');

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
            // Hiển thị lỗi validation
            if (xhr.status === 422 && xhr.responseJSON) {
                const errors = xhr.responseJSON.errors;

                // Reset các lỗi trước đó
                $('.is-invalid').removeClass('is-invalid');

                // Hiển thị lỗi mới
                Object.keys(errors).forEach(key => {
                    const errorMessage = errors[key][0];
                    const inputField = $(`#edit-${key.replace('_', '-')}`);
                    const errorField = $(`#edit-${key.replace('_', '-')}-error`);

                    inputField.addClass('is-invalid');
                    errorField.text(errorMessage);
                });

                showToast('Vui lòng kiểm tra lại thông tin', 'error');
            } else {
                showToast('Có lỗi xảy ra khi cập nhật học viên', 'error');
            }

            resetSaveButton();
        }
    });
});

// Thêm trường tùy chỉnh vào form chỉnh sửa
$('#edit-add-custom-field').on('click', function() {
    addCustomField();
});

// Xóa trường tùy chỉnh
$(document).on('click', '.remove-field', function() {
    $(this).closest('.custom-field-row').fadeOut(300, function() {
        $(this).remove();
    });
});

// Hàm thêm trường tùy chỉnh
function addCustomField(key = '', value = '') {
    const fieldId = Date.now();
    const fieldHtml = `
        <div class="row mb-3 custom-field-row" data-field-id="${fieldId}">
            <div class="col-md-5">
                <input type="text" class="form-control" name="custom_field_keys[]" value="${key}" placeholder="Tên trường">
            </div>
            <div class="col-md-5">
                <input type="text" class="form-control" name="custom_field_values[]" value="${value}" placeholder="Giá trị">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm remove-field"><i class="fas fa-times"></i></button>
            </div>
        </div>
    `;

    $('#edit-custom-fields-container').append(fieldHtml);
}

// Reset nút Save
function resetSaveButton() {
    $('#save-student-btn').html('<i class="fas fa-save me-1"></i> Lưu thay đổi');
    $('#save-student-btn').prop('disabled', false);
}

// Format giới tính
function formatGender(gender) {
    if (!gender) return 'Không xác định';

    const genders = {
        'male': 'Nam',
        'female': 'Nữ',
        'other': 'Khác'
    };

    return genders[gender] || 'Không xác định';
}

// Lấy tên miền theo mã
function getRegionName(region) {
    switch(region) {
        case 'north': return 'Miền Bắc';
        case 'central': return 'Miền Trung';
        case 'south': return 'Miền Nam';
        default: return 'Không xác định';
    }
}

// Lấy badge trạng thái ghi danh
function getEnrollmentStatusBadge(status) {
    switch (status) {
        case 'waiting':
            return '<span class="badge bg-warning text-dark">Danh sách chờ</span>';
        case 'active':
        case 'enrolled':
            return '<span class="badge bg-success">Đang học</span>';
        case 'completed':
            return '<span class="badge bg-success">Đã hoàn thành</span>';
        case 'cancelled':
            return '<span class="badge bg-danger">Đã hủy</span>';
        default:
            return `<span class="badge bg-secondary">${status}</span>`;
    }
}

// Format số tiền
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount);
}

// Hiển thị thông báo toast
function showToast(message, type = 'success') {
    const toast = $(`
        <div class="toast align-items-center text-white bg-${type}" role="alert" aria-live="assertive" aria-atomic="true">
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
}

// Ghi danh học viên
window.enrollStudent = function(studentId) {
    currentStudentId = studentId;

    // Đóng modal xem chi tiết nếu đang mở
    $('#viewStudentModal').modal('hide');

    // Mở modal ghi danh
    $('#enrollStudentModal').modal('show');

    // Hiển thị loading và ẩn form
    $('#enroll-student-loading').show();
    $('#enroll-student-form').hide();

    // Lấy thông tin học viên
    $.ajax({
        url: `/api/students/${studentId}/info`,
        method: 'GET',
        success: function(data) {
            if (!data.success) {
                showToast('Không thể tải thông tin học viên', 'error');
                return;
            }

            const student = data.data;

            // Điền thông tin vào form
            $('#enroll-student-id').val(student.id);
            $('#enroll-student-name').text(student.full_name);

            // Tải danh sách khóa học
            // Khởi tạo Select2 cho course dropdown
            initCourseSelect2();
            
            // Ẩn loading và hiện form
            $('#enroll-student-loading').hide();
            $('#enroll-student-form').show();

            // Thiết lập sự kiện tính học phí
            setupFeeCalculation();
        },
        error: function() {
            showToast('Không thể tải thông tin học viên', 'error');
            $('#enroll-student-loading').hide();
        }
    });
};

// Helper function format tiền tệ (global)
function formatCurrency(number) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(number);
}

// Tính toán học phí cuối cùng (global function)
function calculateFinalFee() {
    const selectedCourse = $('#course_item_id').find(':selected');
    // Nếu chưa chọn khóa học, xóa thông tin học phí
    if (!selectedCourse.val()) {
        $('#final_fee_display').val('');
        $('#final_fee').val('');
        return;
    }

    const baseFee = parseFloat(selectedCourse.data('fee')) || 0;
    
    // Kiểm tra học phí > 0 trước khi cho phép đăng ký
    if (baseFee <= 0) {
        // Hiển thị cảnh báo
        $('#enroll-course-form').prepend(`
            <div class="alert alert-warning alert-dismissible fade show" role="alert" id="fee-warning-enroll">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Lưu ý:</strong> Khóa học "${selectedCourse.text()}" chưa được thiết lập học phí. Không thể đăng ký vào khóa học này.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        
        // Disable submit button
        $('#enroll-btn').prop('disabled', true).html('<i class="fas fa-ban me-1"></i>Không thể đăng ký');
        return;
    } else {
        // Xóa cảnh báo nếu có và enable button
        $('#fee-warning-enroll').remove();
        $('#enroll-btn').prop('disabled', false).html('<i class="fas fa-user-plus me-1"></i> Đăng ký');
    }
    
    let finalFee = baseFee;

    const discountPercent = parseFloat($('#discount_percentage').val());
    const discountAmount = parseFloat($('#discount_amount').val());

    if (!isNaN(discountPercent) && discountPercent > 0) {
        finalFee -= baseFee * (discountPercent / 100);
    } else if (!isNaN(discountAmount) && discountAmount > 0) {
        finalFee -= discountAmount;
    }

    finalFee = Math.max(0, finalFee);

    $('#final_fee').val(finalFee);
    $('#final_fee_display').val(formatCurrency(finalFee));
}

// Thiết lập tính toán học phí
function setupFeeCalculation() {
    // Clear previous warnings khi setup
    $('#fee-warning-enroll').remove();

    // Ngăn chặn form submit trực tiếp
    $('#enrollmentForm').on('submit', function(e) {
        e.preventDefault();
        return false;
    });

    // Gán sự kiện
    $('#course_item_id').on('change', function(e) {
        if (e && e.preventDefault) e.preventDefault(); // Ngăn chặn việc submit form
        if (e && e.stopPropagation) e.stopPropagation(); // Ngăn chặn sự kiện lan truyền
        calculateFinalFee();
        return false;
    });

    $('#discount_percentage').on('input', function(e) {
        if (e && e.preventDefault) e.preventDefault(); // Ngăn chặn việc submit form
        $('#discount_amount').val(''); // Xóa trường chiết khấu còn lại
        calculateFinalFee();
        return false;
    });

    $('#discount_amount').on('input', function(e) {
        if (e && e.preventDefault) e.preventDefault(); // Ngăn chặn việc submit form
        $('#discount_percentage').val(''); // Xóa trường chiết khấu còn lại
        calculateFinalFee();
        return false;
    });

    // Tính toán lần đầu khi form hiển thị
    calculateFinalFee();
}

// Lưu ghi danh mới
$(document).on('click', '#save-enrollment-btn', function() {
    // Thu thập dữ liệu từ form
    const formData = new FormData(document.getElementById('enrollmentForm'));

    // Hiển thị loading
    $('#save-enrollment-btn').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...');
    $('#save-enrollment-btn').prop('disabled', true);

    // Gửi request tạo ghi danh mới
    $.ajax({
        url: '/api/enrollments',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Đóng modal và hiển thị thông báo
                $('#enrollStudentModal').modal('hide');
                showToast('Đăng ký khóa học thành công');

                // Reload trang sau 1s
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                showToast(response.message || 'Có lỗi xảy ra khi đăng ký', 'error');
                resetEnrollButton();
            }
        },
        error: function(xhr) {
            // Hiển thị lỗi validation
            if (xhr.status === 422 && xhr.responseJSON) {
                const errors = xhr.responseJSON.errors;

                // Reset các lỗi trước đó
                $('.is-invalid').removeClass('is-invalid');

                Object.keys(errors).forEach(key => {
                    const errorMessage = errors[key][0];
                    showToast(errorMessage, 'error');
                });
            } else {
                showToast('Có lỗi xảy ra khi đăng ký khóa học', 'error');
            }

            resetEnrollButton();
        }
    });
});

// Reset nút đăng ký
function resetEnrollButton() {
    $('#save-enrollment-btn').html('<i class="fas fa-user-plus me-1"></i> Đăng ký khóa học');
    $('#save-enrollment-btn').prop('disabled', false);
}

// Tạo học viên mới
$(document).ready(function() {
    // Xử lý thêm trường thông tin tùy chỉnh mới
    $('#create-add-custom-field').click(function() {
        addCreateCustomField();
    });

    // Xử lý nút lưu học viên mới
    $('#save-new-student-btn').click(function() {
        createNewStudent();
    });
});

// Thêm trường tùy chỉnh cho form tạo mới
function addCreateCustomField(key = '', value = '') {
    const fieldId = Date.now();
    const fieldHtml = `
        <div class="row mb-3 create-custom-field-row" data-field-id="${fieldId}">
            <div class="col-md-5">
                <input type="text" class="form-control" name="custom_field_keys[]" value="${key}" placeholder="Tên trường">
            </div>
            <div class="col-md-5">
                <input type="text" class="form-control" name="custom_field_values[]" value="${value}" placeholder="Giá trị">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm remove-field"><i class="fas fa-times"></i></button>
            </div>
        </div>
    `;

    $('#create-custom-fields-container').append(fieldHtml);
}

// Xử lý tạo học viên mới
function createNewStudent() {
    // Hiển thị loading
    $('#save-new-student-btn').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...');
    $('#save-new-student-btn').prop('disabled', true);

    // Thu thập dữ liệu từ form
    const formData = new FormData(document.getElementById('studentCreateForm'));

    // Không cần thu thập trường tùy chỉnh nữa vì đã có các trường cụ thể

    // Gửi request tạo học viên mới
    $.ajax({
        url: '/api/students/create',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                // Đóng modal và hiển thị thông báo
                $('#createStudentModal').modal('hide');
                showToast('Tạo học viên mới thành công');

                // Reset form
                document.getElementById('studentCreateForm').reset();
                $('#create-custom-fields-container').empty();

                // Reload trang sau 1s
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                showToast(response.message || 'Có lỗi xảy ra khi tạo học viên', 'error');
                resetCreateButton();
            }
        },
        error: function(xhr) {
            // Hiển thị lỗi validation
            if (xhr.status === 422 && xhr.responseJSON) {
                const errors = xhr.responseJSON.errors;

                // Reset các lỗi trước đó
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
                showToast('Có lỗi xảy ra khi tạo học viên mới', 'error');
            }

            resetCreateButton();
        }
    });
}

// Reset nút tạo mới
function resetCreateButton() {
    $('#save-new-student-btn').html('<i class="fas fa-save me-1"></i> Lưu học viên');
    $('#save-new-student-btn').prop('disabled', false);
}

// ============================================
// CÁC FUNCTION MỚI CHO EDIT ENROLLMENT TỪ POPUP
// ============================================

// Edit enrollment trực tiếp từ popup học viên
window.editEnrollmentInStudentPopup = function(enrollmentId) {
    console.log('editEnrollmentInStudentPopup called with ID:', enrollmentId);
    
    // Load enrollment.js nếu chưa có
    loadEnrollmentScript().then(() => {
        console.log('enrollment.js loaded, checking editEnrollment function...');
        if (typeof window.editEnrollment === 'function') {
            console.log('editEnrollment function found, calling with ID:', enrollmentId);
            window.editEnrollment(enrollmentId);
        } else {
            console.error('editEnrollment function not found');
            showToast('Không thể tải chức năng chỉnh sửa', 'error');
        }
    }).catch(error => {
        console.error('Error loading enrollment script:', error);
        showToast('Lỗi khi tải script enrollment', 'error');
    });
};

// Xem thanh toán của enrollment  
window.viewEnrollmentPayments = function(enrollmentId) {
    // Mở trang thanh toán trong tab mới với filter theo enrollment
    window.open(`/payments?enrollment_id=${enrollmentId}`, '_blank');
};

// Hủy enrollment với confirmation
window.cancelEnrollmentInPopup = function(enrollmentId) {
    if (!confirm('Bạn có chắc chắn muốn hủy ghi danh này?')) {
        return;
    }

    // Hiển thị loading trên nút
    const button = event.target.closest('button');
    const originalHtml = button.innerHTML;
    button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    button.disabled = true;

    $.ajax({
        url: `/api/enrollments/${enrollmentId}/cancel`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showToast('Hủy ghi danh thành công');
                // Reload popup để cập nhật trạng thái
                showStudentDetails(currentStudentId);
            } else {
                showToast('Không thể hủy ghi danh: ' + response.message, 'error');
                // Reset nút về trạng thái ban đầu
                button.innerHTML = originalHtml;
                button.disabled = false;
            }
        },
        error: function(xhr) {
            let errorMessage = 'Có lỗi xảy ra khi hủy ghi danh';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            showToast(errorMessage, 'error');
            
            // Reset nút về trạng thái ban đầu
            button.innerHTML = originalHtml;
            button.disabled = false;
        }
    });
};

// Load enrollment.js dynamically
function loadEnrollmentScript() {
    return new Promise((resolve, reject) => {
        console.log('loadEnrollmentScript called');
        
        // Kiểm tra xem enrollment.js đã được load chưa
        if (window.editEnrollment && typeof window.editEnrollment === 'function') {
            console.log('enrollment.js already loaded');
            resolve();
            return;
        }
        
        // Kiểm tra xem script đã tồn tại chưa
        const existingScript = document.querySelector('script[src*="enrollment.js"]');
        if (existingScript) {
            console.log('enrollment.js script tag exists, waiting for function...');
            // Script đã có nhưng chưa load xong, đợi một chút
            setTimeout(() => {
                if (window.editEnrollment && typeof window.editEnrollment === 'function') {
                    console.log('enrollment.js function found after waiting');
                    resolve();
                } else {
                    console.error('Script loaded but function not available after waiting');
                    reject(new Error('Script loaded but function not available'));
                }
            }, 1000);
            return;
        }
        
        console.log('Creating new enrollment.js script tag');
        // Tạo script tag mới
        const script = document.createElement('script');
        script.src = '/js/enrollment.js';
        script.onload = () => {
            console.log('enrollment.js script loaded, waiting for function...');
            // Đợi một chút để đảm bảo script được execute
            setTimeout(() => {
                if (window.editEnrollment && typeof window.editEnrollment === 'function') {
                    console.log('enrollment.js function available');
                    resolve();
                } else {
                    console.error('Script loaded but editEnrollment function not found');
                    reject(new Error('Script loaded but function not available'));
                }
            }, 500);
        };
        script.onerror = (error) => {
            console.error('Error loading enrollment.js script:', error);
            reject(error);
        };
        document.head.appendChild(script);
    });
}

// Quick actions cho enrollment
window.quickChangeEnrollmentStatus = function(enrollmentId, newStatus) {
    $.ajax({
        url: `/api/enrollments/${enrollmentId}`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: {
            status: newStatus
        },
        success: function(response) {
            if (response.success) {
                showToast(`Đã chuyển trạng thái thành ${getStatusLabel(newStatus)}`);
                // Reload popup để cập nhật
                showStudentDetails(currentStudentId);
            } else {
                showToast('Không thể cập nhật trạng thái: ' + response.message, 'error');
            }
        },
        error: function(xhr) {
            showToast('Có lỗi xảy ra khi cập nhật trạng thái', 'error');
        }
    });
};

// Helper function để lấy label trạng thái
function getStatusLabel(status) {
    const labels = {
        'waiting': 'Danh sách chờ',
        'active': 'Đang học', 
        'completed': 'Đã hoàn thành',
        'cancelled': 'Đã hủy'
    };
    return labels[status] || status;
}

// Khởi tạo Select2 cho course dropdown
function initCourseSelect2() {
    $('#course_item_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Tìm kiếm và chọn khóa học...',
        allowClear: true,
        dropdownParent: $('#enrollStudentModal'),
        width: '100%',
        minimumInputLength: 0,
        ajax: {
            url: '/api/course-items/search-active', // API mới chỉ lấy khóa học đang học
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || ''
                };
            },
            processResults: function (response) {
                // API trả về array trực tiếp, không có wrapper
                if (Array.isArray(response)) {
                    return {
                        results: response.map(function(course) {
                            return {
                                id: course.id,
                                text: course.name + (course.path ? ' (' + course.path + ')' : ''),
                                fee: course.fee || 0,
                                status: course.status,
                                status_label: course.status_label
                            };
                        })
                    };
                }
                return { results: [] };
            },
            cache: true
        }
    });

    // Handle change event để tính học phí
    $('#course_item_id').on('select2:select', function (e) {
        const data = e.params.data;
        if (data && data.fee) {
            // Set fee data attribute cho calculateFinalFee function
            $(this).find(':selected').attr('data-fee', data.fee);
        }
        calculateFinalFee();
    });

    // Handle clear event
    $('#course_item_id').on('select2:clear', function (e) {
        calculateFinalFee();
    });
}
