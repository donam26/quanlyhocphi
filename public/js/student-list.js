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
                            <div>
                                ${statusBadge}
                                ${paymentBadge}
                            </div>
                        </div>
                        <p class="mb-1 small">Ngày đăng ký: ${enrollment.formatted_enrollment_date}</p>
                        <p class="mb-1 small">Học phí: ${formatCurrency(enrollment.final_fee)} đ</p>
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
            // Tách Họ và Tên: lấy từ cuối cùng làm "Tên", phần còn lại là "Họ"
            (function(){
                const full = (student.full_name || '').trim();
                if (!full) {
                    $('#edit-full-name').val('');
                    $('#edit-name').val('');
                    return;
                }
                const parts = full.split(/\s+/);
                const given = parts.pop();
                const family = parts.join(' ');
                $('#edit-full-name').val(family);
                $('#edit-name').val(given);
            })();
            $('#edit-phone').val(student.phone);
            $('#edit-email').val(student.email);
            $('#edit-gender').val(student.gender || '');

            if (student.date_of_birth) {
                const date = new Date(student.date_of_birth);
                const year = date.getFullYear();
                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                const day = date.getDate().toString().padStart(2, '0');
                $('#edit-date-of-birth').val(`${year}-${month}-${day}`);
            }

            $('#edit-notes').val(student.notes);
            // Nơi công tác hiện tại (hiển thị bằng select2, submit qua hidden)
            $('#edit-current-workplace').val(student.current_workplace || '');
            if (student.current_workplace) {
                const optCW = new Option(student.current_workplace, student.current_workplace, true, true);
                $('#edit-current-workplace-select').empty().append(optCW).trigger('change');
            } else {
                $('#edit-current-workplace-select').empty().val(null).trigger('change');
            }
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
            $.ajax({
                url: '/api/course-items/available',
                method: 'GET',
                success: function(courseData) {
                    if (courseData.success) {
                        const courseSelect = $('#course_item_id');
                        courseSelect.empty();
                        courseSelect.append('<option value="">-- Chọn khóa học --</option>');

                        courseData.data.forEach(function(course) {
                            courseSelect.append(`<option value="${course.id}" data-fee="${course.fee}">${course.name}</option>`);
                        });

                        // Ẩn loading và hiện form
                        $('#enroll-student-loading').hide();
                        $('#enroll-student-form').show();

                        // Thiết lập sự kiện tính học phí
                        setupFeeCalculation();
                    } else {
                        showToast('Không thể tải danh sách khóa học', 'error');
                    }
                },
                error: function() {
                    showToast('Không thể tải danh sách khóa học', 'error');
                }
            });
        },
        error: function() {
            showToast('Không thể tải thông tin học viên', 'error');
            $('#enroll-student-loading').hide();
        }
    });
};

// Thiết lập tính toán học phí
function setupFeeCalculation() {
    function formatCurrency(number) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(number);
    }

    function calculateFinalFee() {
        const selectedCourse = $('#course_item_id').find(':selected');
        // Nếu chưa chọn khóa học, xóa thông tin học phí
        if (!selectedCourse.val()) {
            $('#final_fee_display').val('');
            $('#final_fee').val('');
            return;
        }

        const baseFee = parseFloat(selectedCourse.data('fee')) || 0;
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
