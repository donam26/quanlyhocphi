/**
 * Modal System - Hệ thống modal tổng hợp thay thế các file JS cũ
 * Tích hợp với Blade components để giảm thiểu code trùng lặp
 */

class ModalSystem {
    constructor() {
        this.apiRoutes = {
            students: {
                search: '/api/search/autocomplete',
                details: '/api/students/:id',
                create: '/api/students',
                update: '/api/students/:id'
            },
            enrollments: {
                details: '/api/enrollments/:id',
                create: '/api/enrollments',
                update: '/api/enrollments/:id',
                student: '/api/enrollments/student/:id'
            },
            payments: {
                details: '/api/payments/:id',
                create: '/api/payments',
                update: '/api/payments/:id',
                history: '/api/enrollments/:id/payments'
            },
            courseItems: {
                search: '/api/course-items/search',
                details: '/api/course-items/:id'
            },
            provinces: '/api/provinces',
            ethnicities: '/api/ethnicities'
        };
        
        this.init();
    }

    init() {
        this.bindGlobalEvents();
        this.initializeSelect2Defaults();
    }

    bindGlobalEvents() {
        // Bind các sự kiện toàn cục
        $(document).on('click', '[data-modal-action]', this.handleModalAction.bind(this));
        $(document).on('click', '[data-confirm-action]', this.handleConfirmAction.bind(this));
        $(document).on('shown.bs.modal', '.modal', this.onModalShown.bind(this));
        $(document).on('hidden.bs.modal', '.modal', this.onModalHidden.bind(this));
    }

    handleModalAction(e) {
        e.preventDefault();
        const element = e.currentTarget;
        const action = element.dataset.modalAction;
        const target = element.dataset.modalTarget;
        const params = this.parseDataParams(element);

        switch (action) {
            case 'show-student-detail':
                this.showStudentDetail(params.id);
                break;
            case 'show-student-form':
                this.showStudentForm(params.id);
                break;
            case 'show-enrollment-detail':
                this.showEnrollmentDetail(params.id);
                break;
            case 'show-enrollment-form':
                this.showEnrollmentForm(params.id, params.studentId, params.courseItemId);
                break;
            case 'show-payment-detail':
                this.showPaymentDetail(params.id);
                break;
            case 'show-payment-form':
                this.showPaymentForm(params.id, params.enrollmentId, params.studentId);
                break;
            case 'show-payment-history':
                this.showPaymentHistory(params.enrollmentId);
                break;
            default:
                console.warn('Unknown modal action:', action);
        }
    }

    handleConfirmAction(e) {
        e.preventDefault();
        const element = e.currentTarget;
        const message = element.dataset.confirmMessage || 'Bạn có chắc chắn muốn thực hiện hành động này?';
        const action = element.dataset.confirmAction;
        const params = this.parseDataParams(element);

        this.showConfirm(message, () => {
            this.executeAction(action, params, element);
        });
    }

    parseDataParams(element) {
        const params = {};
        Array.from(element.attributes).forEach(attr => {
            if (attr.name.startsWith('data-param-')) {
                const key = attr.name.replace('data-param-', '').replace(/-([a-z])/g, (g) => g[1].toUpperCase());
                params[key] = attr.value;
            }
        });
        return params;
    }

    onModalShown(e) {
        const modal = e.target;
        const modalId = modal.id;
        
        // Khởi tạo Select2 cho modal
        this.initializeModalSelect2(modalId);
        
        // Focus vào input đầu tiên
        const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }

    onModalHidden(e) {
        const modal = e.target;
        
        // Destroy Select2 instances
        $(modal).find('.select2').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });
        
        // Clear form data
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
            $(form).find('.is-invalid').removeClass('is-invalid');
            $(form).find('.invalid-feedback').remove();
        }
    }

    initializeSelect2Defaults() {
        // Cấu hình mặc định cho Select2
        if (typeof $.fn.select2 !== 'undefined') {
            $.fn.select2.defaults.set('theme', 'bootstrap-5');
            $.fn.select2.defaults.set('width', '100%');
        }
    }

    initializeModalSelect2(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        // Student search
        $(modal).find('select[name="student_search"], select[name="student_id"]').each((index, element) => {
            if (!$(element).hasClass('select2-hidden-accessible')) {
                $(element).select2({
                    dropdownParent: $(modal),
                    ajax: {
                        url: this.apiRoutes.students.search,
                        dataType: 'json',
                        delay: 250,
                        data: (params) => ({
                            q: params.term || '',
                            preload: params.term ? 'false' : 'true'
                        }),
                        processResults: (data) => ({ results: data })
                    },
                    placeholder: 'Nhập tên hoặc số điện thoại...',
                    allowClear: true,
                    minimumInputLength: 0
                });
            }
        });

        // Course search
        $(modal).find('select[name="course_item_id"]').each((index, element) => {
            if (!$(element).hasClass('select2-hidden-accessible')) {
                $(element).select2({
                    dropdownParent: $(modal),
                    ajax: {
                        url: this.apiRoutes.courseItems.search,
                        dataType: 'json',
                        delay: 250,
                        data: (params) => ({
                            q: params.term || '',
                            active_only: true
                        }),
                        processResults: (response) => {
                            if (response.success) {
                                return {
                                    results: response.data.map(item => ({
                                        id: item.id,
                                        text: `${item.name} - ${this.formatCurrency(item.fee)}`,
                                        fee: item.fee
                                    }))
                                };
                            }
                            return { results: [] };
                        }
                    },
                    placeholder: 'Tìm kiếm khóa học...',
                    allowClear: true
                });
            }
        });

        // Province search
        $(modal).find('select[name="province_id"]').each((index, element) => {
            if (!$(element).hasClass('select2-hidden-accessible')) {
                $(element).select2({
                    dropdownParent: $(modal),
                    ajax: {
                        url: this.apiRoutes.provinces,
                        dataType: 'json',
                        delay: 250,
                        data: (params) => ({
                            q: params.term || '',
                            keyword: params.term || ''
                        }),
                        processResults: (response) => {
                            if (response.success && response.data) {
                                return {
                                    results: response.data.map(item => ({
                                        id: item.id,
                                        text: `${item.name} (${item.region_name || 'N/A'})`
                                    }))
                                };
                            }
                            return { results: [] };
                        }
                    },
                    placeholder: 'Tìm kiếm tỉnh/thành phố...',
                    allowClear: true
                });
            }
        });
    }

    // Student methods
    showStudentDetail(studentId) {
        if (typeof window.loadDetailModal === 'function') {
            window.loadDetailModal('studentDetailModal', this.getUrl(this.apiRoutes.students.details, { id: studentId }));
        }
    }

    showStudentForm(studentId = null) {
        const modalId = studentId ? 'editStudentModal' : 'createStudentModal';
        const modal = $(`#${modalId}`);
        
        if (studentId) {
            this.loadDataIntoModal(modalId, this.getUrl(this.apiRoutes.students.details, { id: studentId }));
        }
        
        modal.modal('show');
    }

    // Enrollment methods
    showEnrollmentDetail(enrollmentId) {
        if (typeof window.loadDetailModal === 'function') {
            window.loadDetailModal('enrollmentDetailModal', this.getUrl(this.apiRoutes.enrollments.details, { id: enrollmentId }));
        }
    }

    showEnrollmentForm(enrollmentId = null, studentId = null, courseItemId = null) {
        const modalId = enrollmentId ? 'editEnrollmentModal' : 'createEnrollmentModal';
        const modal = $(`#${modalId}`);
        
        if (enrollmentId) {
            this.loadDataIntoModal(modalId, this.getUrl(this.apiRoutes.enrollments.details, { id: enrollmentId }));
        } else {
            if (studentId) modal.find('input[name="student_id"]').val(studentId);
            if (courseItemId) modal.find('input[name="course_item_id"]').val(courseItemId);
        }
        
        modal.modal('show');
    }

    // Payment methods
    showPaymentDetail(paymentId) {
        if (typeof window.loadDetailModal === 'function') {
            window.loadDetailModal('paymentDetailModal', this.getUrl(this.apiRoutes.payments.details, { id: paymentId }));
        }
    }

    showPaymentForm(paymentId = null, enrollmentId = null, studentId = null) {
        const modalId = paymentId ? 'editPaymentModal' : 'createPaymentModal';
        const modal = $(`#${modalId}`);
        
        if (paymentId) {
            this.loadDataIntoModal(modalId, this.getUrl(this.apiRoutes.payments.details, { id: paymentId }));
        } else {
            if (enrollmentId) modal.find('input[name="enrollment_id"]').val(enrollmentId);
            if (studentId) modal.find('input[name="student_id"]').val(studentId);
        }
        
        modal.modal('show');
    }

    showPaymentHistory(enrollmentId) {
        if (typeof window.loadDetailModal === 'function') {
            window.loadDetailModal('paymentHistoryModal', this.getUrl(this.apiRoutes.payments.history, { id: enrollmentId }));
        }
    }

    // Utility methods
    showConfirm(message, onConfirm) {
        if (typeof window.ModalManager !== 'undefined') {
            window.ModalManager.showConfirmDelete(message, onConfirm);
        } else {
            if (confirm(message)) {
                onConfirm();
            }
        }
    }

    showToast(message, type = 'success') {
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            console.log(`Toast ${type}: ${message}`);
        }
    }

    loadDataIntoModal(modalId, url) {
        const modal = $(`#${modalId}`);
        
        $.ajax({
            url: url,
            method: 'GET',
            success: (response) => {
                if (response.success && response.data) {
                    this.populateForm(modal, response.data);
                }
            },
            error: (xhr) => {
                console.error('Error loading data:', xhr);
                this.showToast('Không thể tải dữ liệu', 'error');
            }
        });
    }

    populateForm(modal, data) {
        Object.keys(data).forEach(key => {
            const input = modal.find(`[name="${key}"]`);
            if (input.length && data[key] !== null) {
                input.val(data[key]);
                
                // Trigger change event for Select2
                if (input.hasClass('select2-hidden-accessible')) {
                    input.trigger('change');
                }
            }
        });
    }

    executeAction(action, params, element) {
        // Implement specific actions here
        console.log('Executing action:', action, params);
    }

    getUrl(template, params) {
        let url = template;
        Object.keys(params).forEach(key => {
            url = url.replace(`:${key}`, params[key]);
        });
        return url;
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount || 0);
    }

    formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('vi-VN');
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.modalSystem = new ModalSystem();
    console.log('Modal System initialized');
});

// Export for global access
window.ModalSystem = ModalSystem;
