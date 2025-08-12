/**
 * UnifiedModalSystem - Hệ thống modal thống nhất thay thế tất cả modal cũ
 * Sử dụng ModalManager và StatusFactory
 */
class UnifiedModalSystem {
    constructor() {
        this.modalManager = window.ModalManager;
        this.statusFactory = window.StatusFactory;
        this.formHandler = window.FormHandler;
        
        this.init();
    }

    init() {
        // Bind global functions để backward compatibility
        window.showStudentDetail = (id) => this.showStudentDetail(id);
        window.showStudentHistory = (id) => this.showStudentHistory(id);
        window.showStudentForm = (id = null) => this.showStudentForm(id);
        window.showCourseDetail = (id) => this.showCourseDetail(id);
        window.showEnrollmentForm = (studentId, courseId) => this.showEnrollmentForm(studentId, courseId);
        window.showPaymentForm = (enrollmentId) => this.showPaymentForm(enrollmentId);
        window.confirmDelete = (id, name, type) => this.confirmDelete(id, name, type);
        window.showAttendanceModal = (courseId) => this.showAttendanceModal(courseId);
    }

    /**
     * Hiển thị chi tiết học viên
     */
    async showStudentDetail(studentId) {
        const modal = this.modalManager.show('studentDetailModal', {
            title: '<i class="fas fa-user me-2"></i>Chi tiết học viên',
            body: this.getLoadingContent('Đang tải thông tin học viên...'),
            config: { size: 'modal-xl' }
        });

        try {
            const response = await fetch(`/api/students/${studentId}/details`);
            const data = await response.json();

            if (data.success) {
                this.modalManager.setBody('studentDetailModal', this.generateStudentDetailView(data.student));
            } else {
                this.modalManager.setBody('studentDetailModal', this.getErrorContent('Không tìm thấy thông tin học viên'));
            }
        } catch (error) {
            this.modalManager.setBody('studentDetailModal', this.getErrorContent('Có lỗi xảy ra khi tải thông tin'));
        }
    }

    /**
     * Hiển thị lịch sử học viên
     */
    async showStudentHistory(studentId) {
        const modal = this.modalManager.show('studentHistoryModal', {
            title: '<i class="fas fa-history me-2"></i>Lịch sử học viên',
            body: this.getLoadingContent('Đang tải lịch sử...'),
            config: { size: 'modal-xl' }
        });

        try {
            const response = await fetch(`/students/${studentId}/history`);
            const data = await response.json();

            if (data.success) {
                this.modalManager.setBody('studentHistoryModal', this.generateStudentHistoryView(data.student));
            } else {
                this.modalManager.setBody('studentHistoryModal', this.getErrorContent('Không tìm thấy lịch sử học viên'));
            }
        } catch (error) {
            this.modalManager.setBody('studentHistoryModal', this.getErrorContent('Có lỗi xảy ra khi tải lịch sử'));
        }
    }

    /**
     * Hiển thị form học viên (tạo mới hoặc chỉnh sửa)
     */
    async showStudentForm(studentId = null) {
        const isEdit = studentId !== null;
        const title = isEdit ? 
            '<i class="fas fa-user-edit me-2"></i>Chỉnh sửa học viên' : 
            '<i class="fas fa-user-plus me-2"></i>Thêm học viên mới';

        const modal = this.modalManager.show('studentFormModal', {
            title: title,
            body: this.getLoadingContent('Đang tải form...'),
            config: { size: 'modal-lg' }
        });

        try {
            let studentData = null;
            if (isEdit) {
                const response = await fetch(`/api/students/${studentId}/details`);
                const data = await response.json();
                if (data.success) {
                    studentData = data.student;
                } else {
                    throw new Error('Không tìm thấy thông tin học viên');
                }
            }

            const formHtml = await this.generateStudentForm(studentData, isEdit);
            this.modalManager.setBody('studentFormModal', formHtml);
            
            // Initialize form component
            this.initializeFormInModal('studentFormModal');

        } catch (error) {
            this.modalManager.setBody('studentFormModal', this.getErrorContent(error.message));
        }
    }

    /**
     * Hiển thị chi tiết khóa học
     */
    async showCourseDetail(courseId) {
        const modal = this.modalManager.show('courseDetailModal', {
            title: '<i class="fas fa-book me-2"></i>Chi tiết khóa học',
            body: this.getLoadingContent('Đang tải thông tin khóa học...'),
            config: { size: 'modal-lg' }
        });

        try {
            const response = await fetch(`/api/course-items/${courseId}/details`);
            const data = await response.json();

            if (data.success) {
                this.modalManager.setBody('courseDetailModal', this.generateCourseDetailView(data.course));
            } else {
                this.modalManager.setBody('courseDetailModal', this.getErrorContent('Không tìm thấy thông tin khóa học'));
            }
        } catch (error) {
            this.modalManager.setBody('courseDetailModal', this.getErrorContent('Có lỗi xảy ra khi tải thông tin'));
        }
    }

    /**
     * Hiển thị form ghi danh
     */
    async showEnrollmentForm(studentId, courseId) {
        const modal = this.modalManager.show('enrollmentFormModal', {
            title: '<i class="fas fa-user-plus me-2"></i>Ghi danh học viên',
            body: this.getLoadingContent('Đang tải form ghi danh...'),
            config: { size: 'modal-lg' }
        });

        try {
            const formHtml = await this.generateEnrollmentForm(studentId, courseId);
            this.modalManager.setBody('enrollmentFormModal', formHtml);
            
            this.initializeFormInModal('enrollmentFormModal');

        } catch (error) {
            this.modalManager.setBody('enrollmentFormModal', this.getErrorContent('Có lỗi xảy ra khi tải form'));
        }
    }

    /**
     * Hiển thị form thanh toán
     */
    async showPaymentForm(enrollmentId) {
        const modal = this.modalManager.show('paymentFormModal', {
            title: '<i class="fas fa-money-bill me-2"></i>Thêm thanh toán',
            body: this.getLoadingContent('Đang tải form thanh toán...'),
            config: { size: 'modal-lg' }
        });

        try {
            const formHtml = await this.generatePaymentForm(enrollmentId);
            this.modalManager.setBody('paymentFormModal', formHtml);
            
            this.initializeFormInModal('paymentFormModal');

        } catch (error) {
            this.modalManager.setBody('paymentFormModal', this.getErrorContent('Có lỗi xảy ra khi tải form'));
        }
    }

    /**
     * Hiển thị modal điểm danh
     */
    async showAttendanceModal(courseId) {
        const modal = this.modalManager.show('attendanceModal', {
            title: '<i class="fas fa-check-square me-2"></i>Điểm danh',
            body: this.getLoadingContent('Đang tải danh sách học viên...'),
            config: { size: 'modal-xl' }
        });

        try {
            const formHtml = await this.generateAttendanceForm(courseId);
            this.modalManager.setBody('attendanceModal', formHtml);
            
            this.initializeFormInModal('attendanceModal');

        } catch (error) {
            this.modalManager.setBody('attendanceModal', this.getErrorContent('Có lỗi xảy ra khi tải danh sách'));
        }
    }

    /**
     * Xác nhận xóa
     */
    async confirmDelete(id, name, type) {
        const typeLabels = {
            'student': 'học viên',
            'course': 'khóa học',
            'enrollment': 'ghi danh',
            'payment': 'thanh toán'
        };

        const confirmed = await this.modalManager.confirm({
            title: 'Xác nhận xóa',
            message: `Bạn có chắc chắn muốn xóa ${typeLabels[type] || type} "${name}"?`,
            confirmText: 'Xóa',
            cancelText: 'Hủy',
            confirmClass: 'btn-danger'
        });

        if (confirmed) {
            this.performDelete(id, type);
        }
    }

    /**
     * Thực hiện xóa
     */
    async performDelete(id, type) {
        const routes = {
            'student': `/students/${id}`,
            'course': `/course-items/${id}`,
            'enrollment': `/enrollments/${id}`,
            'payment': `/payments/${id}`
        };

        try {
            const response = await fetch(routes[type], {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                window.showToast(data.message || 'Xóa thành công', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                window.showToast(data.message || 'Có lỗi xảy ra', 'error');
            }
        } catch (error) {
            window.showToast('Có lỗi xảy ra khi xóa', 'error');
        }
    }

    /**
     * Initialize form component trong modal
     */
    initializeFormInModal(modalId) {
        const modal = document.getElementById(modalId);
        const form = modal.querySelector('form[data-auto-submit="true"]');

        if (form && window.FormComponent) {
            new window.FormComponent(form);
        }

        // Initialize Select2 using Select2Manager
        if (window.Select2Manager) {
            window.Select2Manager.initializeInModal(modal);
        }
    }

    /**
     * Tạo nội dung loading
     */
    getLoadingContent(message = 'Đang tải...') {
        return `
            <div class="text-center py-4">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mb-0">${message}</p>
            </div>
        `;
    }

    /**
     * Tạo nội dung lỗi
     */
    getErrorContent(message) {
        return `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>${message}
            </div>
        `;
    }

    // Các method generate view sẽ được implement trong file riêng
    // để tránh file quá dài
}

// Export
window.UnifiedModalSystem = UnifiedModalSystem;
