/**
 * Search Modal System
 * Quản lý tìm kiếm và hiển thị modal cho học viên
 */

class SearchModalSystem {
    constructor() {
        this.apiRoutes = {
            autocomplete: '/api/search/autocomplete',
            details: '/api/search/details',
            studentHistory: '/api/search/student/:id/history'
        };
        
        this.init();
    }

    init() {
        this.initializeSelect2();
        this.bindEvents();
    }

    initializeSelect2() {
        $('#student_search').select2({
            theme: 'bootstrap-5',
            placeholder: 'Nhập tên hoặc số điện thoại học viên...',
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: this.apiRoutes.autocomplete,
                dataType: 'json',
                delay: 300,
                data: function(params) {
                    return { q: params.term };
                },
                processResults: function(data) {
                    return { results: data };
                },
                cache: true
            },
            templateResult: this.formatSearchResult,
            templateSelection: this.formatSearchSelection
        });
    }

    formatSearchResult(data) {
        if (data.loading) return data.text;
        
        return $(`
            <div class="d-flex justify-content-between">
                <div>
                    <strong>${data.full_name || data.text}</strong><br>
                    <small class="text-muted">${data.phone || ''}</small>
                </div>
                <div class="text-end">
                    <small class="text-muted">${data.email || ''}</small>
                </div>
            </div>
        `);
    }

    formatSearchSelection(data) {
        return data.full_name || data.text || 'Chọn học viên...';
    }

    bindEvents() {
        // Form tìm kiếm
        $('#search-form').on('submit', (e) => {
            e.preventDefault();
            this.performSearch();
        });

        // Chọn học viên từ dropdown
        $('#student_search').on('select2:select', (e) => {
            const data = e.params.data;
            if (data.id) {
                this.performSearch(data.id);
            }
        });

        // Edit student button trong modal
        $(document).on('click', '#editStudentBtn', () => {
            const studentId = $('#studentDetailModal').data('student-id');
            if (studentId) {
                SearchModals.showStudentForm(studentId);
            }
        });

        // Print history button
        $(document).on('click', '#printHistoryBtn', () => {
            window.print();
        });
    }

    performSearch(studentId = null) {
        const searchBtn = $('#search-btn');
        const loadingIndicator = $('#loading-indicator');
        const searchResults = $('#search-results');
        const termError = $('#term-error');
        
        // Reset error
        this.clearErrors();
        
        // Lấy giá trị tìm kiếm
        const term = studentId || $('#student_search').val();
        
        if (!term) {
            this.showError('Vui lòng nhập thông tin tìm kiếm', '#term-error');
            $('#student_search').addClass('is-invalid');
            return;
        }
        
        // Hiển thị loading
        this.showLoading(searchBtn, loadingIndicator, searchResults);
        
        // Gọi API
        const params = studentId ? { student_id: studentId } : { term: term };
        
        $.ajax({
            url: this.apiRoutes.details,
            method: 'GET',
            data: params,
            success: (response) => {
                if (response.success) {
                    this.displaySearchResults(response.data);
                } else {
                    this.showError('Có lỗi xảy ra khi tìm kiếm');
                }
            },
            error: (xhr) => {
                this.handleSearchError(xhr);
            },
            complete: () => {
                this.hideLoading(searchBtn, loadingIndicator);
            }
        });
    }

    showLoading(searchBtn, loadingIndicator, searchResults) {
        searchBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Đang tìm...');
        loadingIndicator.removeClass('d-none');
        searchResults.addClass('d-none');
    }

    hideLoading(searchBtn, loadingIndicator) {
        searchBtn.prop('disabled', false).html('<i class="fas fa-search me-1"></i> Tìm kiếm');
        loadingIndicator.addClass('d-none');
    }

    clearErrors() {
        $('#term-error').text('').hide();
        $('#student_search').removeClass('is-invalid');
    }

    showError(message, target = null) {
        if (target) {
            $(target).text(message).show();
        } else {
            const searchResults = $('#search-results');
            searchResults.html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                </div>
            `).removeClass('d-none');
        }
    }

    handleSearchError(xhr) {
        if (xhr.status === 400) {
            const error = xhr.responseJSON?.error || 'Thông tin tìm kiếm không hợp lệ';
            this.showError(error, '#term-error');
            $('#student_search').addClass('is-invalid');
        } else {
            this.showError('Có lỗi xảy ra khi tìm kiếm');
        }
    }

    displaySearchResults(data) {
        const searchResults = $('#search-results');
        const studentsDetails = data.studentsDetails || [];
        const searchTerm = data.searchTerm || '';
        
        if (studentsDetails.length === 0) {
            this.showNoResults(searchResults, searchTerm);
        } else {
            this.showResults(searchResults, studentsDetails, searchTerm);
        }
        
        searchResults.removeClass('d-none');
    }

    showNoResults(container, searchTerm) {
        container.html(`
            <div class="card">
                <div class="card-body text-center">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5>Không tìm thấy kết quả</h5>
                    <p class="text-muted">Không có học viên nào phù hợp với từ khóa "${searchTerm}"</p>
                </div>
            </div>
        `);
    }

    showResults(container, studentsDetails, searchTerm) {
        let html = `
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-check-circle me-1"></i>
                    Tìm thấy ${studentsDetails.length} kết quả${searchTerm ? ` cho từ khóa "${searchTerm}"` : ''}
                </div>
                <div class="card-body p-0">
        `;
        
        studentsDetails.forEach((detail, index) => {
            html += this.generateStudentCard(detail, index);
        });
        
        html += `
                </div>
            </div>
        `;
        
        container.html(html);
    }

    generateStudentCard(detail, index) {
        const student = detail.student;
        const enrollments = detail.enrollments || [];
        const waitingLists = detail.waiting_lists || [];
        const remaining = detail.remaining || 0;
        
        const paymentStatusBadge = remaining <= 0 ? 
            '<span class="badge bg-success">Đã thanh toán đủ</span>' :
            `<span class="badge bg-danger">Còn nợ: ${this.formatCurrency(remaining)}</span>`;
        
        return `
            <div class="search-result-card p-3 ${index > 0 ? 'border-top' : ''}">
                <!-- Header thông tin học viên -->
                <div class="row align-items-center mb-3">
                    <div class="col-md-3">
                        <h5 class="mb-1">
                            <i class="fas fa-user me-2 text-primary"></i>
                            ${student.full_name}
                        </h5>
                        <span class="badge bg-secondary">${detail.total_courses} khóa học</span>
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-phone-alt me-1 text-muted"></i> ${student.phone}
                    </div>
                    <div class="col-md-3">
                        <i class="fas fa-envelope me-1 text-muted"></i> ${student.email || 'Không có'}
                    </div>
                    <div class="col-md-3 text-end">
                        ${paymentStatusBadge}
                    </div>
                </div>
                
                <!-- Thông tin chi tiết -->
                <div class="row">
                    <div class="col-md-8">
                        ${this.generateStudentInfoGrid(student)}
                    </div>
                    <div class="col-md-4">
                        ${this.generateFinancialCard(detail)}
                    </div>
                </div>
                
                <!-- Danh sách khóa học -->
                ${this.generateEnrollmentsList(enrollments)}
                
                <!-- Danh sách chờ -->
                ${this.generateWaitingList(waitingLists)}
                
                <!-- Action buttons -->
                <div class="mt-3 text-end">
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="SearchModals.showStudentDetail(${student.id})">
                        <i class="fas fa-eye me-1"></i>Chi tiết
                    </button>
                    <button class="btn btn-outline-info btn-sm me-2" onclick="SearchModals.showStudentHistory(${student.id})">
                        <i class="fas fa-history me-1"></i>Lịch sử
                    </button>
                    <button class="btn btn-outline-secondary btn-sm me-2" onclick="SearchModals.showStudentForm(${student.id})">
                        <i class="fas fa-edit me-1"></i>Sửa
                    </button>
                    ${remaining > 0 ? `
                    <button class="btn btn-outline-success btn-sm" onclick="SearchModals.showAddPayment(${student.id})">
                        <i class="fas fa-plus me-1"></i>Thêm thanh toán
                    </button>
                    ` : ''}
                </div>
            </div>
        `;
    }

    generateStudentInfoGrid(student) {
        return `
            <div class="student-info-grid">
                <div class="info-item">
                    <strong>Ngày sinh:</strong><br>
                    <span>${this.formatDate(student.date_of_birth)}</span>
                </div>
                <div class="info-item">
                    <strong>Giới tính:</strong><br>
                    <span>${this.getGenderText(student.gender)}</span>
                </div>
                <div class="info-item">
                    <strong>Địa chỉ:</strong><br>
                    <span>${student.address || 'Chưa cập nhật'}</span>
                </div>
                ${student.current_workplace ? `
                <div class="info-item">
                    <strong>Nơi công tác:</strong><br>
                    <span>${student.current_workplace}</span>
                </div>
                ` : ''}
                ${student.accounting_experience_years ? `
                <div class="info-item">
                    <strong>Kinh nghiệm:</strong><br>
                    <span>${student.accounting_experience_years} năm</span>
                </div>
                ` : ''}
            </div>
        `;
    }

    generateFinancialCard(detail) {
        const progressPercent = detail.total_fee > 0 ? (detail.total_paid / detail.total_fee * 100) : 0;
        
        return `
            <div class="card border-primary">
                <div class="card-header bg-primary text-white py-2">
                    <i class="fas fa-chart-line me-1"></i> Thông tin tài chính
                </div>
                <div class="card-body py-2">
                    <div class="row text-center">
                        <div class="col-12 mb-2">
                            <strong>Tổng học phí:</strong><br>
                            <span class="text-primary">${this.formatCurrency(detail.total_fee)}</span>
                        </div>
                        <div class="col-12 mb-2">
                            <strong>Đã thanh toán:</strong><br>
                            <span class="text-success">${this.formatCurrency(detail.total_paid)}</span>
                        </div>
                        <div class="col-12">
                            <strong>Còn thiếu:</strong><br>
                            <span class="text-danger">${this.formatCurrency(detail.remaining)}</span>
                        </div>
                    </div>
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: ${progressPercent}%"></div>
                    </div>
                    <small class="text-muted">${Math.round(progressPercent)}% hoàn thành</small>
                </div>
            </div>
        `;
    }

    generateEnrollmentsList(enrollments) {
        if (enrollments.length === 0) return '';
        
        return `
            <div class="mt-3">
                <h6><i class="fas fa-graduation-cap me-2"></i>Khóa học đã đăng ký (${enrollments.length})</h6>
                <div class="row">
                    ${enrollments.map(enrollment => `
                        <div class="col-md-6 mb-2">
                            <div class="course-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>${enrollment.course_item.name}</strong><br>
                                        <small class="text-muted">Ngày đăng ký: ${enrollment.enrollment_date}</small><br>
                                        <span class="badge bg-info">${enrollment.status}</span>
                                    </div>
                                    <div class="text-end">
                                        <strong>${this.formatCurrency(enrollment.final_fee)}</strong><br>
                                        <small class="text-muted">Đã trả: ${this.formatCurrency(enrollment.total_paid)}</small><br>
                                        <span class="badge ${enrollment.remaining_amount <= 0 ? 'bg-success' : 'bg-warning'} payment-status-badge">
                                            ${enrollment.payment_status}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    generateWaitingList(waitingLists) {
        if (waitingLists.length === 0) return '';
        
        return `
            <div class="mt-3">
                <h6><i class="fas fa-clock me-2"></i>Danh sách chờ (${waitingLists.length})</h6>
                <div class="row">
                    ${waitingLists.map(waiting => `
                        <div class="col-md-6 mb-2">
                            <div class="course-card border-warning">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${waiting.course_item.name}</strong><br>
                                        <small class="text-muted">Ngày đăng ký: ${waiting.registration_date}</small>
                                    </div>
                                    <span class="badge bg-warning">Đang chờ</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    // Utility functions
    formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount || 0);
    }

    formatDate(dateString) {
        if (!dateString) return 'Chưa cập nhật';
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    }

    getGenderText(gender) {
        switch(gender) {
            case 'male': return 'Nam';
            case 'female': return 'Nữ';
            case 'other': return 'Khác';
            default: return 'Chưa cập nhật';
        }
    }
}

// Initialize when document is ready
$(document).ready(function() {
    window.searchModalSystem = new SearchModalSystem();
});

// Export for global access
window.SearchModalSystem = SearchModalSystem; 