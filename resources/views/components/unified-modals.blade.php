{{-- 
    Unified Modal System - Thay thế tất cả modal cũ
    Chỉ cần include component này là có đủ tất cả modal cần thiết
--}}

{{-- Modal container - Tất cả modal sẽ được tạo động bằng JavaScript --}}
<div id="modal-container"></div>

{{-- Include JavaScript --}}
@push('scripts')
<script>
/**
 * Initialize Unified Modal System khi DOM ready
 */
document.addEventListener('DOMContentLoaded', function() {
    // Đợi app ready
    document.addEventListener('app:ready', function() {
        // Initialize Unified Modal System
        if (window.UnifiedModalSystem) {
            window.unifiedModals = new window.UnifiedModalSystem();
            console.log('✅ Unified Modal System initialized');
        }
    });
});

/**
 * Backward compatibility functions
 * Để các view cũ vẫn hoạt động
 */

// Student functions
window.viewStudent = function(id) {
    if (window.unifiedModals) {
        window.unifiedModals.showStudentDetail(id);
    } else {
        console.error('Unified Modal System not initialized');
    }
};

window.editStudent = function(id) {
    if (window.unifiedModals) {
        window.unifiedModals.showStudentForm(id);
    } else {
        console.error('Unified Modal System not initialized');
    }
};

window.addStudent = function() {
    if (window.unifiedModals) {
        window.unifiedModals.showStudentForm();
    } else {
        console.error('Unified Modal System not initialized');
    }
};

// Course functions
window.viewCourse = function(id) {
    if (window.unifiedModals) {
        window.unifiedModals.showCourseDetail(id);
    } else {
        console.error('Unified Modal System not initialized');
    }
};

// Enrollment functions
window.enrollStudent = function(studentId, courseId) {
    if (window.unifiedModals) {
        window.unifiedModals.showEnrollmentForm(studentId, courseId);
    } else {
        console.error('Unified Modal System not initialized');
    }
};

// Payment functions
window.addPayment = function(enrollmentId) {
    if (window.unifiedModals) {
        window.unifiedModals.showPaymentForm(enrollmentId);
    } else {
        console.error('Unified Modal System not initialized');
    }
};

// Attendance functions
window.openAttendanceModal = function(courseId) {
    if (window.unifiedModals) {
        window.unifiedModals.showAttendanceModal(courseId);
    } else {
        console.error('Unified Modal System not initialized');
    }
};

// Delete confirmation
window.confirmDeleteStudent = function(id, name) {
    if (window.unifiedModals) {
        window.unifiedModals.confirmDelete(id, name, 'student');
    } else {
        console.error('Unified Modal System not initialized');
    }
};

window.confirmDeleteCourse = function(id, name) {
    if (window.unifiedModals) {
        window.unifiedModals.confirmDelete(id, name, 'course');
    } else {
        console.error('Unified Modal System not initialized');
    }
};

window.confirmDeleteEnrollment = function(id, name) {
    if (window.unifiedModals) {
        window.unifiedModals.confirmDelete(id, name, 'enrollment');
    } else {
        console.error('Unified Modal System not initialized');
    }
};

window.confirmDeletePayment = function(id, name) {
    if (window.unifiedModals) {
        window.unifiedModals.confirmDelete(id, name, 'payment');
    } else {
        console.error('Unified Modal System not initialized');
    }
};

/**
 * Legacy function mappings
 * Để tương thích với code cũ
 */
window.showStudentDetail = window.viewStudent;
window.showStudentHistory = function(id) {
    if (window.unifiedModals) {
        window.unifiedModals.showStudentHistory(id);
    }
};
window.showStudentForm = function(id = null) {
    if (id) {
        window.editStudent(id);
    } else {
        window.addStudent();
    }
};
window.showCourseDetail = window.viewCourse;
window.showEnrollmentForm = window.enrollStudent;
window.showPaymentForm = window.addPayment;
window.showAttendanceModal = window.openAttendanceModal;

// Confirm delete legacy
window.confirmDelete = function(id, name, type) {
    if (window.unifiedModals) {
        window.unifiedModals.confirmDelete(id, name, type);
    }
};

/**
 * Search functionality
 */
window.searchStudents = async function(term) {
    if (!term || term.length < 2) {
        return [];
    }

    try {
        const response = await fetch(`/api/students/search?term=${encodeURIComponent(term)}`);
        const data = await response.json();
        
        if (data.success) {
            return data.students;
        } else {
            console.error('Search failed:', data.message);
            return [];
        }
    } catch (error) {
        console.error('Search error:', error);
        return [];
    }
};

/**
 * Quick actions
 */
window.quickEnroll = function(studentId) {
    // Show course selection modal first, then enrollment form
    if (window.unifiedModals) {
        window.unifiedModals.showCourseSelection(studentId);
    }
};

window.quickPayment = function(studentId) {
    // Show enrollment selection modal first, then payment form
    if (window.unifiedModals) {
        window.unifiedModals.showEnrollmentSelection(studentId);
    }
};

/**
 * Bulk operations
 */
window.bulkDeleteStudents = function(ids) {
    if (window.unifiedModals) {
        window.unifiedModals.confirmBulkDelete(ids, 'student');
    }
};

window.bulkDeleteEnrollments = function(ids) {
    if (window.unifiedModals) {
        window.unifiedModals.confirmBulkDelete(ids, 'enrollment');
    }
};

/**
 * Export functions
 */
window.exportStudents = function(filters = {}) {
    const params = new URLSearchParams(filters);
    window.open(`/students/export?${params.toString()}`, '_blank');
};

window.exportPayments = function(filters = {}) {
    const params = new URLSearchParams(filters);
    window.open(`/payments/export?${params.toString()}`, '_blank');
};

/**
 * Print functions
 */
window.printStudentList = function(filters = {}) {
    const params = new URLSearchParams(filters);
    window.open(`/students/print?${params.toString()}`, '_blank');
};

window.printPaymentReceipt = function(paymentId) {
    window.open(`/payments/${paymentId}/receipt`, '_blank');
};

/**
 * Utility functions
 */
window.formatCurrency = function(amount) {
    if (!amount) return '0 ₫';
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
};

window.formatDate = function(dateString) {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('vi-VN');
};

window.formatDateTime = function(dateString) {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('vi-VN');
};

/**
 * Status helpers
 */
window.getStatusBadge = function(type, value) {
    if (window.StatusFactory) {
        return window.StatusFactory.createBadge(type, value);
    }
    return `<span class="badge bg-secondary">${value}</span>`;
};

window.getStatusLabel = function(type, value) {
    if (window.StatusFactory) {
        return window.StatusFactory.getLabel(type, value);
    }
    return value;
};

/**
 * Form helpers
 */
window.resetForm = function(formSelector) {
    const form = document.querySelector(formSelector);
    if (form) {
        form.reset();
        // Clear validation errors
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    }
};

window.populateForm = function(formSelector, data) {
    const form = document.querySelector(formSelector);
    if (!form) return;

    Object.keys(data).forEach(key => {
        const field = form.querySelector(`[name="${key}"]`);
        if (field) {
            if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = field.value === data[key];
            } else {
                field.value = data[key] || '';
            }
        }
    });
};

console.log('📋 Unified Modal System functions loaded');
</script>
@endpush
