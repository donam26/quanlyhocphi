/**
 * ViewGenerators - Tạo HTML views cho các modal
 * Sử dụng StatusFactory để hiển thị status nhất quán
 */
class ViewGenerators {
    constructor() {
        this.statusFactory = window.StatusFactory;
    }

    /**
     * Tạo view chi tiết học viên
     */
    generateStudentDetailView(student) {
        return `
            <div class="row">
                <div class="col-md-8">
                    <h4>${student.full_name}</h4>
                    <p class="text-muted">Mã học viên: #${student.id}</p>
                </div>
                <div class="col-md-4 text-end">
                    ${this.statusFactory.createBadge('student', student.status)}
                </div>
            </div>

            <nav>
                <div class="nav nav-tabs" id="nav-tab" role="tablist">
                    <button class="nav-link active" id="nav-info-tab" data-bs-toggle="tab" data-bs-target="#nav-info" type="button">
                        <i class="fas fa-info-circle me-1"></i>Thông tin cơ bản
                    </button>
                    <button class="nav-link" id="nav-enrollments-tab" data-bs-toggle="tab" data-bs-target="#nav-enrollments" type="button">
                        <i class="fas fa-graduation-cap me-1"></i>Khóa học (${student.enrollments?.length || 0})
                    </button>
                    <button class="nav-link" id="nav-payments-tab" data-bs-toggle="tab" data-bs-target="#nav-payments" type="button">
                        <i class="fas fa-money-bill me-1"></i>Thanh toán (${student.payments?.length || 0})
                    </button>
                </div>
            </nav>

            <div class="tab-content mt-3" id="nav-tabContent">
                <div class="tab-pane fade show active" id="nav-info">
                    ${this.generateStudentInfoTab(student)}
                </div>
                <div class="tab-pane fade" id="nav-enrollments">
                    ${this.generateStudentEnrollmentsTab(student.enrollments || [])}
                </div>
                <div class="tab-pane fade" id="nav-payments">
                    ${this.generateStudentPaymentsTab(student.payments || [])}
                </div>
            </div>
        `;
    }

    /**
     * Tạo tab thông tin cơ bản học viên
     */
    generateStudentInfoTab(student) {
        return `
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">Họ và tên:</td>
                            <td>${student.full_name}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Ngày sinh:</td>
                            <td>${this.formatDate(student.date_of_birth)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Giới tính:</td>
                            <td>${this.getGenderLabel(student.gender)}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Số điện thoại:</td>
                            <td><a href="tel:${student.phone}">${student.phone}</a></td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Email:</td>
                            <td>${student.email ? `<a href="mailto:${student.email}">${student.email}</a>` : '-'}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <td class="fw-bold">Địa chỉ:</td>
                            <td>${student.address || '-'}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Tỉnh/Thành:</td>
                            <td>${student.province?.name || '-'}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Nơi làm việc:</td>
                            <td>${student.current_workplace || '-'}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Kinh nghiệm KT:</td>
                            <td>${student.accounting_experience_years ? student.accounting_experience_years + ' năm' : '-'}</td>
                        </tr>
                        <tr>
                            <td class="fw-bold">Trình độ:</td>
                            <td>${student.education_level || '-'}</td>
                        </tr>
                    </table>
                </div>
            </div>
            ${student.notes ? `
                <div class="mt-3">
                    <h6>Ghi chú:</h6>
                    <div class="alert alert-info">${student.notes}</div>
                </div>
            ` : ''}
        `;
    }

    /**
     * Tạo tab khóa học của học viên
     */
    generateStudentEnrollmentsTab(enrollments) {
        if (!enrollments.length) {
            return '<div class="alert alert-info">Học viên chưa đăng ký khóa học nào.</div>';
        }

        return `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Khóa học</th>
                            <th>Ngày đăng ký</th>
                            <th>Học phí</th>
                            <th>Đã thanh toán</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${enrollments.map(enrollment => `
                            <tr>
                                <td>
                                    <strong>${enrollment.course_item?.name || 'N/A'}</strong>
                                    ${enrollment.course_item?.fee ? `<br><small class="text-muted">Học phí gốc: ${this.formatCurrency(enrollment.course_item.fee)}</small>` : ''}
                                </td>
                                <td>${this.formatDate(enrollment.enrollment_date)}</td>
                                <td>${this.formatCurrency(enrollment.final_fee)}</td>
                                <td>
                                    <span class="fw-bold ${enrollment.total_paid >= enrollment.final_fee ? 'text-success' : 'text-warning'}">
                                        ${this.formatCurrency(enrollment.total_paid || 0)}
                                    </span>
                                </td>
                                <td>${this.statusFactory.createBadge('enrollment', enrollment.status)}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="showPaymentForm(${enrollment.id})">
                                        <i class="fas fa-plus me-1"></i>Thanh toán
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    /**
     * Tạo tab thanh toán của học viên
     */
    generateStudentPaymentsTab(payments) {
        if (!payments.length) {
            return '<div class="alert alert-info">Chưa có thanh toán nào.</div>';
        }

        return `
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
                                <td>${this.formatDate(payment.payment_date)}</td>
                                <td>${payment.enrollment?.course_item?.name || 'N/A'}</td>
                                <td class="fw-bold">${this.formatCurrency(payment.amount)}</td>
                                <td>${this.statusFactory.createBadge('payment_method', payment.payment_method)}</td>
                                <td>${this.statusFactory.createBadge('payment', payment.status)}</td>
                                <td>${payment.notes || '-'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    /**
     * Tạo form học viên
     */
    async generateStudentForm(student = null, isEdit = false) {
        const provinces = await this.loadProvinces();
        const statusOptions = this.statusFactory.getOptions('student');

        return `
            <form data-auto-submit="true" 
                  action="${isEdit ? `/students/${student.id}` : '/students'}" 
                  method="POST"
                  data-success-message="${isEdit ? 'Cập nhật học viên thành công!' : 'Thêm học viên thành công!'}"
                  data-reload-on-success="true">
                ${isEdit ? '<input type="hidden" name="_method" value="PUT">' : ''}
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Họ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="first_name" 
                                   value="${student?.first_name || ''}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="last_name" 
                                   value="${student?.last_name || ''}" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Ngày sinh <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="date_of_birth" 
                                   value="${student?.date_of_birth || ''}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Giới tính</label>
                            <select class="form-select" name="gender">
                                <option value="">Chọn giới tính</option>
                                <option value="male" ${student?.gender === 'male' ? 'selected' : ''}>Nam</option>
                                <option value="female" ${student?.gender === 'female' ? 'selected' : ''}>Nữ</option>
                                <option value="other" ${student?.gender === 'other' ? 'selected' : ''}>Khác</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" name="phone" 
                                   value="${student?.phone || ''}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="${student?.email || ''}">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <input type="text" class="form-control" name="address" 
                                   value="${student?.address || ''}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Tỉnh/Thành</label>
                            <select class="form-select select2" name="province_id">
                                <option value="">Chọn tỉnh/thành</option>
                                ${provinces.map(province => `
                                    <option value="${province.id}" ${student?.province_id == province.id ? 'selected' : ''}>
                                        ${province.name}
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Nơi làm việc</label>
                            <input type="text" class="form-control" name="current_workplace" 
                                   value="${student?.current_workplace || ''}">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Kinh nghiệm kế toán (năm)</label>
                            <input type="number" class="form-control" name="accounting_experience_years" 
                                   value="${student?.accounting_experience_years || ''}" min="0">
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ghi chú</label>
                    <textarea class="form-control" name="notes" rows="3">${student?.notes || ''}</textarea>
                </div>

                <div class="text-end">
                    <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>${isEdit ? 'Cập nhật' : 'Thêm mới'}
                    </button>
                </div>
            </form>
        `;
    }

    /**
     * Utility methods
     */
    formatDate(dateString) {
        if (!dateString) return '-';
        return new Date(dateString).toLocaleDateString('vi-VN');
    }

    formatCurrency(amount) {
        if (!amount) return '0 ₫';
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    }

    getGenderLabel(gender) {
        const labels = {
            'male': 'Nam',
            'female': 'Nữ',
            'other': 'Khác'
        };
        return labels[gender] || '-';
    }

    async loadProvinces() {
        try {
            const response = await fetch('/api/provinces');
            const data = await response.json();
            return data.provinces || [];
        } catch (error) {
            console.error('Error loading provinces:', error);
            return [];
        }
    }
}

// Extend UnifiedModalSystem với ViewGenerators
if (window.UnifiedModalSystem) {
    Object.assign(window.UnifiedModalSystem.prototype, ViewGenerators.prototype);
}

// Export
window.ViewGenerators = ViewGenerators;
