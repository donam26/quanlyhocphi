/**
 * StudentManager - Quản lý thống nhất tất cả các thao tác với học viên
 * Giải quyết vấn đề không đồng bộ và trùng lặp code
 */
class StudentManager {
    constructor() {
        this.currentStudentId = null;
        this.modals = {
            view: '#viewStudentModal',
            edit: '#editStudentModal',
            create: '#createStudentModal',
            enroll: '#enrollStudentModal'
        };
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeComponents();
    }

    bindEvents() {
        // Đảm bảo jQuery đã sẵn sàng
        if (typeof $ === 'undefined') {
            console.warn('jQuery chưa được load, delay bind events');
            setTimeout(() => this.bindEvents(), 100);
            return;
        }

        // Bind các sự kiện chung
        $(document).on('click', '[data-student-action]', (e) => {
            e.preventDefault();
            const action = $(e.currentTarget).data('student-action');
            const studentId = $(e.currentTarget).data('student-id');
            this.handleAction(action, studentId);
        });

        // Bind form submissions
        $(document).on('submit', '#studentEditForm', (e) => {
            e.preventDefault();
            this.handleEditSubmit(e.target);
        });

        $(document).on('submit', '#studentCreateForm', (e) => {
            e.preventDefault();
            this.handleCreateSubmit(e.target);
        });
    }

    handleAction(action, studentId = null) {
        switch(action) {
            case 'view':
                this.showDetails(studentId);
                break;
            case 'edit':
                this.showEditForm(studentId);
                break;
            case 'create':
                this.showCreateForm();
                break;
            case 'enroll':
                this.showEnrollForm(studentId);
                break;
            default:
                console.warn('Unknown action:', action);
        }
    }

    // Hiển thị chi tiết học viên
    showDetails(studentId) {
        this.currentStudentId = studentId;
        const modal = $(this.modals.view);

        modal.modal('show');
        this.showLoading('#student-loading', '#student-details');

        $.ajax({
            url: `/api/students/${studentId}/info`,
            method: 'GET',
            success: (data) => {
                if (!data.success) {
                    this.showToast('Không thể tải thông tin học viên', 'error');
                    return;
                }
                this.renderStudentDetails(data.data);
                this.hideLoading('#student-loading', '#student-details');
            },
            error: () => {
                this.showToast('Không thể tải thông tin học viên', 'error');
                this.hideLoading('#student-loading', '#student-details');
            }
        });
    }

    renderStudentDetails(student) {
        // Cập nhật thông tin cơ bản
        $('#student-name').text(student.full_name);
        $('#student-id').text(student.id);

        // Thông tin cá nhân
        $('#student-gender').text(this.formatGender(student.gender));
        $('#student-dob').text(student.formatted_date_of_birth || 'Chưa có');
        $('#student-phone').text(student.phone);
        $('#student-email').text(student.email || 'Chưa có');

        // Tỉnh thành
        let addressText = '';
        if (student.province) {
            addressText = student.province.name + ' (' + this.getRegionName(student.province.region) + ')';
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
        this.renderCustomFields(student);

        // Hiển thị khóa học đã đăng ký
        this.renderEnrollments(student);

        // Cập nhật các nút hành động
        $('#btn-add-enrollment').attr('href', `/enrollments/create?student_id=${student.id}`);
    }

    renderCustomFields(student) {
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
    }

    renderEnrollments(student) {
        if (student.enrollments && student.enrollments.length > 0) {
            let enrollmentsHtml = '<div class="list-group">';
            student.enrollments.forEach(enrollment => {
                const statusBadge = this.getEnrollmentStatusBadge(enrollment.status);
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
                    <p class="mb-1 small">Học phí: ${this.formatCurrency(enrollment.final_fee)} đ</p>
                    ${enrollment.discount_percentage > 0 ?
                        `<p class="mb-1 small text-success">Chiết khấu: ${enrollment.discount_percentage}%</p>` :
                        enrollment.discount_amount > 0 ?
                        `<p class="mb-1 small text-success">Chiết khấu: ${this.formatCurrency(enrollment.discount_amount)} đ</p>` : ''
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
    }

    // Hiển thị form chỉnh sửa học viên
    showEditForm(studentId) {
        this.currentStudentId = studentId;
        const modal = $(this.modals.edit);

        // Đóng modal xem chi tiết nếu đang mở
        $(this.modals.view).modal('hide');

        modal.modal('show');
        this.showLoading('#edit-student-loading', '#edit-student-form');

        $.ajax({
            url: `/api/students/${studentId}/info`,
            method: 'GET',
            success: (data) => {
                if (!data.success) {
                    this.showToast('Không thể tải thông tin học viên', 'error');
                    return;
                }
                this.populateEditForm(data.data);
                this.hideLoading('#edit-student-loading', '#edit-student-form');
            },
            error: () => {
                this.showToast('Không thể tải thông tin học viên', 'error');
                this.hideLoading('#edit-student-loading', '#edit-student-form');
            }
        });
    }

    populateEditForm(student) {
        // Điền thông tin vào form
        $('#edit-student-id').val(student.id);
        $('#edit-first-name').val(student.first_name || '');
        $('#edit-last-name').val(student.last_name || '');
        $('#edit-phone').val(student.phone);
        $('#edit-email').val(student.email);
        $('#edit-gender').val(student.gender || '');

        if (student.date_of_birth) {
            $('#edit-date-of-birth').val(student.formatted_date_of_birth || this.formatDate(student.date_of_birth) || '');
        }

        $('#edit-notes').val(student.notes);
        $('#edit-current-workplace').val(student.current_workplace || '');
        $('#edit-experience').val(student.accounting_experience_years);

        // Cập nhật các trường mới
        $('#edit-hard-copy-documents').val(student.hard_copy_documents || '');
        $('#edit-education-level').val(student.education_level || '');
        $('#edit-workplace').val(student.workplace || '');
        $('#edit-experience-years').val(student.experience_years || '');
        $('#edit-training-specialization').val(student.training_specialization || '');

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
        setTimeout(() => {
            if (student.province) {
                const option = new Option(
                    student.province.name + ' (' + this.getRegionName(student.province.region) + ')',
                    student.province.id,
                    true,
                    true
                );
                $('#edit-province').empty().append(option).trigger('change');
            } else {
                $('#edit-province').empty().val(null).trigger('change');
            }
        }, 300);
    }

    // Xử lý submit form chỉnh sửa
    handleEditSubmit(form) {
        const studentId = $('#edit-student-id').val();
        const formData = new FormData(form);

        this.showButtonLoading('#save-student-btn', 'Đang lưu...');

        $.ajax({
            url: `/api/students/${studentId}/update`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                if (response.success) {
                    $(this.modals.edit).modal('hide');
                    this.showToast('Cập nhật thông tin học viên thành công');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    this.showToast(response.message || 'Có lỗi xảy ra khi cập nhật', 'error');
                    this.resetButton('#save-student-btn', 'Lưu thay đổi');
                }
            },
            error: (xhr) => {
                this.handleFormErrors(xhr, 'edit');
                this.resetButton('#save-student-btn', 'Lưu thay đổi');
            }
        });
    }

    // Hiển thị form tạo mới học viên
    showCreateForm() {
        const modal = $(this.modals.create);
        modal.modal('show');
        this.resetForm('#studentCreateForm');
    }

    // Xử lý submit form tạo mới
    handleCreateSubmit(form) {
        const formData = new FormData(form);

        this.showButtonLoading('#save-new-student-btn', 'Đang lưu...');

        $.ajax({
            url: '/api/students/create',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                if (response.success) {
                    $(this.modals.create).modal('hide');
                    this.showToast('Tạo học viên mới thành công');
                    this.resetForm('#studentCreateForm');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    this.showToast(response.message || 'Có lỗi xảy ra khi tạo học viên', 'error');
                    this.resetButton('#save-new-student-btn', 'Lưu học viên');
                }
            },
            error: (xhr) => {
                this.handleFormErrors(xhr, 'create');
                this.resetButton('#save-new-student-btn', 'Lưu học viên');
            }
        });
    }

    // Utility methods
    showLoading(loadingSelector, contentSelector) {
        $(loadingSelector).show();
        $(contentSelector).hide();
    }

    hideLoading(loadingSelector, contentSelector) {
        $(loadingSelector).hide();
        $(contentSelector).show();
    }

    showButtonLoading(buttonSelector, text) {
        $(buttonSelector).html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${text}`);
        $(buttonSelector).prop('disabled', true);
    }

    resetButton(buttonSelector, text) {
        $(buttonSelector).html(`<i class="fas fa-save me-1"></i> ${text}`);
        $(buttonSelector).prop('disabled', false);
    }

    resetForm(formSelector) {
        $(formSelector)[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }

    handleFormErrors(xhr, prefix) {
        if (xhr.status === 422 && xhr.responseJSON) {
            const errors = xhr.responseJSON.errors;
            $('.is-invalid').removeClass('is-invalid');

            Object.keys(errors).forEach(key => {
                const errorMessage = errors[key][0];
                const inputField = $(`#${prefix}-${key.replace('_', '-')}`);
                const errorField = $(`#${prefix}-${key.replace('_', '-')}-error`);

                inputField.addClass('is-invalid');
                errorField.text(errorMessage);
            });

            this.showToast('Vui lòng kiểm tra lại thông tin', 'error');
        } else {
            this.showToast('Có lỗi xảy ra khi xử lý yêu cầu', 'error');
        }
    }

    // Format giới tính
    formatGender(gender) {
        if (!gender) return 'Không xác định';

        const genders = {
            'male': 'Nam',
            'female': 'Nữ',
            'other': 'Khác'
        };

        return genders[gender] || 'Không xác định';
    }

    // Lấy tên miền theo mã
    getRegionName(region) {
        switch(region) {
            case 'north': return 'Miền Bắc';
            case 'central': return 'Miền Trung';
            case 'south': return 'Miền Nam';
            default: return 'Không xác định';
        }
    }

    // Lấy badge trạng thái ghi danh
    getEnrollmentStatusBadge(status) {
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
    formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN').format(amount);
    }

    // Format ngày
    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    }

    // Hiển thị thông báo toast
    showToast(message, type = 'success') {
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
    }

    // Hiển thị form ghi danh học viên
    showEnrollForm(studentId) {
        this.currentStudentId = studentId;
        const modal = $(this.modals.enroll);

        // Đóng modal xem chi tiết nếu đang mở
        $(this.modals.view).modal('hide');

        modal.modal('show');
        this.showLoading('#enroll-student-loading', '#enroll-student-form');

        $.ajax({
            url: `/api/students/${studentId}/info`,
            method: 'GET',
            success: (data) => {
                if (!data.success) {
                    this.showToast('Không thể tải thông tin học viên', 'error');
                    return;
                }
                this.populateEnrollForm(data.data);
                this.hideLoading('#enroll-student-loading', '#enroll-student-form');
            },
            error: () => {
                this.showToast('Không thể tải thông tin học viên', 'error');
                this.hideLoading('#enroll-student-loading', '#enroll-student-form');
            }
        });
    }

    populateEnrollForm(student) {
        $('#enroll-student-id').val(student.id);
        $('#enroll-student-name').text(student.full_name);

        // Khởi tạo Select2 cho course dropdown
        this.initCourseSelect2();

        // Thiết lập sự kiện tính học phí
        this.setupFeeCalculation();
    }

    // Khởi tạo các component
    initializeComponents() {
        // Khởi tạo Select2 và các component khác khi cần
        this.initializeSelect2();
    }

    initializeSelect2() {
        // Khởi tạo Select2 cho các dropdown
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
        }
    }

    // Khởi tạo Select2 cho course dropdown
    initCourseSelect2() {
        $('#course_item_id').select2({
            theme: 'bootstrap-5',
            placeholder: 'Tìm kiếm và chọn khóa học...',
            allowClear: true,
            dropdownParent: $(this.modals.enroll),
            width: '100%',
            minimumInputLength: 0,
            ajax: {
                url: '/api/course-items/search-active',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || '',
                        preload: params.term ? 'false' : 'true'
                    };
                },
                processResults: function (response) {
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
        $('#course_item_id').on('select2:select', (e) => {
            const data = e.params.data;
            if (data && data.fee !== undefined) {
                $(e.target).find(':selected').attr('data-fee', data.fee);
                $(e.target).data('selected-fee', data.fee);
            }
            this.calculateFinalFee();
        });

        $('#course_item_id').on('select2:clear', () => {
            this.calculateFinalFee();
        });
    }

    // Thiết lập tính toán học phí
    setupFeeCalculation() {
        $('#fee-warning-enroll').remove();

        $('#enrollmentForm').off('submit.feeCalculation').on('submit.feeCalculation', (e) => {
            e.preventDefault();
            return false;
        });

        $('#course_item_id').off('change.feeCalculation').on('change.feeCalculation', (e) => {
            if (e && e.preventDefault) e.preventDefault();
            if (e && e.stopPropagation) e.stopPropagation();
            this.calculateFinalFee();
            return false;
        });

        $('#discount_percentage').off('input.feeCalculation').on('input.feeCalculation', (e) => {
            if (e && e.preventDefault) e.preventDefault();
            $('#discount_amount').val('');
            this.calculateFinalFee();
            return false;
        });

        $('#discount_amount').off('input.feeCalculation').on('input.feeCalculation', (e) => {
            if (e && e.preventDefault) e.preventDefault();
            $('#discount_percentage').val('');
            this.calculateFinalFee();
            return false;
        });

        this.calculateFinalFee();
    }

    // Tính toán học phí cuối cùng
    calculateFinalFee() {
        const courseSelect = $('#course_item_id');
        const selectedCourse = courseSelect.find(':selected');

        if (!selectedCourse.val()) {
            $('#final_fee_display').val('');
            $('#final_fee').val('');
            return;
        }

        let baseFee = parseFloat(selectedCourse.data('fee')) ||
                      parseFloat(courseSelect.data('selected-fee')) ||
                      parseFloat(selectedCourse.attr('data-fee')) || 0;

        if (baseFee <= 0) {
            $('#enroll-course-form').prepend(`
                <div class="alert alert-warning alert-dismissible fade show" role="alert" id="fee-warning-enroll">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Lưu ý:</strong> Khóa học "${selectedCourse.text()}" chưa được thiết lập học phí. Không thể đăng ký vào khóa học này.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `);

            $('#enroll-btn').prop('disabled', true).html('<i class="fas fa-ban me-1"></i>Không thể đăng ký');
            return;
        } else {
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
        $('#final_fee_display').val(this.formatCurrency(finalFee));
    }
}

// Khởi tạo StudentManager instance
let studentManager;

// Khởi tạo khi DOM ready
$(document).ready(function() {
    studentManager = new StudentManager();

    // Export global instance
    window.studentManager = studentManager;
});

// Backward compatibility functions
window.showStudentDetails = function(studentId) {
    if (studentManager) {
        studentManager.showDetails(studentId);
    }
};

window.editStudent = function(studentId) {
    if (studentManager) {
        studentManager.showEditForm(studentId);
    }
};

window.enrollStudent = function(studentId) {
    if (studentManager) {
        studentManager.showEnrollForm(studentId);
    }
};

// Global utility functions for backward compatibility
window.formatGender = function(gender) {
    return studentManager ? studentManager.formatGender(gender) : gender;
};

window.getRegionName = function(region) {
    return studentManager ? studentManager.getRegionName(region) : region;
};

window.getEnrollmentStatusBadge = function(status) {
    return studentManager ? studentManager.getEnrollmentStatusBadge(status) : status;
};

window.formatCurrency = function(amount) {
    return studentManager ? studentManager.formatCurrency(amount) : amount;
};

window.showToast = function(message, type = 'success') {
    if (studentManager) {
        studentManager.showToast(message, type);
    }
};










