/**
 * AttendanceTreeComponent - Component quản lý điểm danh theo cây khóa học
 * Extends BaseComponent và tích hợp với Unified Modal System
 */
class AttendanceTreeComponent extends BaseComponent {
    constructor(element, options = {}) {
        super(element, options);
        this.selectedCourseId = null;
        this.selectedCourseName = '';
        this.currentDate = null;
    }

    getDefaultOptions() {
        return {
            dateSelector: '#attendance-date',
            treeContainer: '.attendance-tree-container',
            formContainer: '#attendance-form-container',
            selectedCourseNameElement: '#selected-course-name',
            expandAllButton: '#expand-all',
            collapseAllButton: '#collapse-all',
            refreshButton: '#refresh-attendance',
            saveButton: '#save-attendance'
        };
    }

    initializeElements() {
        this.dateInput = this.find(this.options.dateSelector);
        this.treeContainer = this.find(this.options.treeContainer);
        this.formContainer = this.find(this.options.formContainer);
        this.selectedCourseNameElement = this.find(this.options.selectedCourseNameElement);
        this.expandAllButton = this.find(this.options.expandAllButton);
        this.collapseAllButton = this.find(this.options.collapseAllButton);
        this.refreshButton = this.find(this.options.refreshButton);
        this.saveButton = this.find(this.options.saveButton);

        // Set initial date
        this.currentDate = this.dateInput ? this.dateInput.value : new Date().toISOString().split('T')[0];
    }

    bindEvents() {
        // Expand/Collapse all buttons
        if (this.expandAllButton) {
            this.addEventListener(this.expandAllButton, 'click', this.expandAll.bind(this));
        }
        
        if (this.collapseAllButton) {
            this.addEventListener(this.collapseAllButton, 'click', this.collapseAll.bind(this));
        }

        // Date change
        if (this.dateInput) {
            this.addEventListener(this.dateInput, 'change', this.onDateChange.bind(this));
        }

        // Tree item clicks
        this.addDelegatedEventListener('.tree-item[data-id]', 'click', this.onTreeItemClick.bind(this));

        // Collapse/expand toggle
        this.addDelegatedEventListener('.toggle-icon', 'click', this.onToggleClick.bind(this));

        // Form submission
        this.addDelegatedEventListener('#attendance-form', 'submit', this.onFormSubmit.bind(this));

        // Status change
        this.addDelegatedEventListener('.status-select', 'change', this.onStatusChange.bind(this));

        // Mark all status buttons
        this.addDelegatedEventListener('.mark-all-status', 'click', this.onMarkAllStatus.bind(this));

        // Refresh button
        if (this.refreshButton) {
            this.addEventListener(this.refreshButton, 'click', this.refreshAttendance.bind(this));
        }
    }

    afterInit() {
        // Load initial attendance counts
        this.loadAllAttendanceCounts();
    }

    expandAll() {
        const collapses = this.findAll('.collapse');
        collapses.forEach(collapse => {
            collapse.classList.add('show');
        });
        
        const toggleIcons = this.findAll('.toggle-icon');
        toggleIcons.forEach(icon => {
            icon.classList.remove('fa-chevron-right');
            icon.classList.add('fa-chevron-down');
        });
    }

    collapseAll() {
        const collapses = this.findAll('.collapse');
        collapses.forEach(collapse => {
            collapse.classList.remove('show');
        });
        
        const toggleIcons = this.findAll('.toggle-icon');
        toggleIcons.forEach(icon => {
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-right');
        });
    }

    onDateChange(e) {
        this.currentDate = e.target.value;
        this.loadAllAttendanceCounts();
        
        if (this.selectedCourseId) {
            this.loadAttendanceForm(this.selectedCourseId);
        }
    }

    onTreeItemClick(e) {
        e.stopPropagation();
        
        // Remove selection from all items
        const allItems = this.findAll('.tree-item');
        allItems.forEach(item => item.classList.remove('selected'));
        
        // Add selection to clicked item
        e.currentTarget.classList.add('selected');
        
        this.selectedCourseId = e.currentTarget.dataset.id;
        this.selectedCourseName = e.currentTarget.dataset.courseName;
        
        if (this.selectedCourseNameElement) {
            this.selectedCourseNameElement.textContent = this.selectedCourseName;
        }
        
        this.loadAttendanceForm(this.selectedCourseId);
    }

    onToggleClick(e) {
        e.stopPropagation();
        
        const icon = e.currentTarget;
        const targetId = icon.dataset.bsTarget;
        
        if (targetId) {
            const target = document.querySelector(targetId);
            if (target) {
                if (target.classList.contains('show')) {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-right');
                } else {
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-down');
                }
            }
        }
    }

    onFormSubmit(e) {
        e.preventDefault();
        this.saveAttendance(e.currentTarget);
    }

    onStatusChange(e) {
        const row = e.currentTarget.closest('tr');
        const status = e.currentTarget.value;
        
        // Remove all status classes
        row.classList.remove('status-present', 'status-absent', 'status-late', 'status-excused');
        
        // Add new status class
        row.classList.add(`status-${status}`);
    }

    onMarkAllStatus(e) {
        const status = e.currentTarget.dataset.status;
        const statusSelects = this.findAll('.status-select');
        
        statusSelects.forEach(select => {
            select.value = status;
            select.dispatchEvent(new Event('change'));
        });
    }

    refreshAttendance() {
        if (this.selectedCourseId) {
            this.loadAttendanceForm(this.selectedCourseId);
        }
    }

    async loadAllAttendanceCounts() {
        const treeItems = this.findAll('.tree-item[data-id]');
        
        for (const item of treeItems) {
            const courseId = item.dataset.id;
            if (courseId) {
                try {
                    const response = await fetch(`/course-items/${courseId}/attendance-students?date=${this.currentDate}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        this.updateAttendanceCount(courseId, data.students.length);
                    }
                } catch (error) {
                    console.error(`Error loading count for course ${courseId}:`, error);
                }
            }
        }
    }

    updateAttendanceCount(courseId, count) {
        const countElement = this.find(`#count-${courseId}`);
        if (countElement) {
            countElement.textContent = count;
        }
    }

    async loadAttendanceForm(courseId) {
        if (!this.formContainer) return;
        
        this.formContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="mt-2">Đang tải danh sách học viên...</p>
            </div>
        `;
        
        try {
            const response = await fetch(`/course-items/${courseId}/attendance-students?date=${this.currentDate}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            const data = await response.json();
            if (data.success) {
                this.displayAttendanceForm(data);
                this.updateAttendanceCount(courseId, data.students.length);
            } else {
                this.showError('Không thể tải danh sách học viên');
            }
        } catch (error) {
            console.error('Error loading attendance students:', error);
            this.showError('Có lỗi xảy ra khi tải danh sách học viên: ' + error.message);
        }
    }

    displayAttendanceForm(data) {
        const course = data.course;
        const students = data.students;
        const canTakeAttendance = data.can_take_attendance;

        if (students.length === 0) {
            this.formContainer.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-user-times"></i>
                    <h5>Không có học viên</h5>
                    <p class="text-muted">Chưa có học viên nào ghi danh vào khóa học này</p>
                </div>
            `;
            return;
        }

        let html = `
            <form id="attendance-form" data-course-id="${course.id}">
                <input type="hidden" name="course_item_id" value="${course.id}">
                <input type="hidden" name="date" value="${data.date}">

                <!-- Course info -->
                <div class="alert alert-info">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1">${course.name}</h6>
                            <small class="text-muted">${course.path || ''}</small>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-primary">
                                <i class="fas fa-calendar me-1"></i>${data.formatted_date || ''}
                            </span>
                            <br>
                            <span class="badge bg-info mt-1">
                                <i class="fas fa-users me-1"></i>${students.length} học viên
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Quick actions -->
                ${canTakeAttendance ? `
                <div class="mb-3">
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-success mark-all-status" data-status="present">
                            <i class="fas fa-check-circle me-1"></i>Tất cả có mặt
                        </button>
                        <button type="button" class="btn btn-warning mark-all-status" data-status="absent">
                            <i class="fas fa-times-circle me-1"></i>Tất cả vắng mặt
                        </button>
                        <button type="button" class="btn btn-info mark-all-status" data-status="late">
                            <i class="fas fa-clock me-1"></i>Tất cả đi muộn
                        </button>
                    </div>
                </div>
                ` : ''}

                <!-- Attendance Table -->
                <div class="table-responsive">
                    <table class="table table-striped attendance-table">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="25%">Họ tên</th>
                                <th width="15%">Số điện thoại</th>
                                <th width="20%">Trạng thái</th>
                                <th width="35%">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

        students.forEach((student, index) => {
            html += `
                <tr class="status-${student.current_status}" data-enrollment-id="${student.enrollment_id}">
                    <td>${index + 1}</td>
                    <td>
                        <strong>${student.student_name}</strong>
                        <input type="hidden" name="attendances[${index}][enrollment_id]" value="${student.enrollment_id}">
                    </td>
                    <td>${student.student_phone || ''}</td>
                    <td>
                        <select name="attendances[${index}][status]" class="form-select form-select-sm status-select" ${!canTakeAttendance ? 'disabled' : ''}>
                            <option value="present" ${student.current_status === 'present' ? 'selected' : ''}>Có mặt</option>
                            <option value="absent" ${student.current_status === 'absent' ? 'selected' : ''}>Vắng mặt</option>
                            <option value="late" ${student.current_status === 'late' ? 'selected' : ''}>Đi muộn</option>
                            <option value="excused" ${student.current_status === 'excused' ? 'selected' : ''}>Có phép</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="attendances[${index}][notes]"
                               class="form-control form-control-sm"
                               placeholder="Ghi chú..."
                               value="${student.current_notes || ''}"
                               ${!canTakeAttendance ? 'readonly' : ''}>
                    </td>
                </tr>
            `;
        });

        html += `
                        </tbody>
                    </table>
                </div>

                <!-- Save Button -->
                ${canTakeAttendance ? `
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted">
                        ${data.attendance_exists ?
                            '<i class="fas fa-info-circle me-1"></i>Đã có điểm danh trước đó, dữ liệu sẽ được cập nhật' :
                            '<i class="fas fa-plus-circle me-1"></i>Điểm danh mới cho buổi học này'
                        }
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Lưu điểm danh
                    </button>
                </div>
                ` : `
                <div class="text-center mt-3">
                    <div class="text-muted">
                        <i class="fas fa-eye me-1"></i>Chế độ xem - Không thể chỉnh sửa điểm danh
                    </div>
                </div>
                `}
            </form>
        `;

        this.formContainer.innerHTML = html;

        // Show save button if can take attendance
        if (canTakeAttendance && this.saveButton) {
            this.saveButton.style.display = 'block';
        }
    }

    async saveAttendance(form) {
        const formData = new FormData(form);

        // Convert attendances to JSON
        const attendances = [];
        const attendanceInputs = form.querySelectorAll('input[name*="[enrollment_id]"]');

        attendanceInputs.forEach((input, index) => {
            const enrollmentId = input.value;
            const status = form.querySelector(`select[name="attendances[${index}][status]"]`).value;
            const notes = form.querySelector(`input[name="attendances[${index}][notes]"]`).value;

            attendances.push({
                enrollment_id: enrollmentId,
                status: status,
                notes: notes
            });
        });

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...';

        try {
            const response = await fetch('/attendance/save-from-tree', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    course_item_id: formData.get('course_item_id'),
                    date: formData.get('date'),
                    attendances: attendances
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert('success', `Điểm danh đã được lưu thành công!\n\nTổng: ${data.total_students} học viên\nCó mặt: ${data.present_count}\nVắng mặt: ${data.absent_count}\nĐi muộn: ${data.late_count}\nCó phép: ${data.excused_count}`);

                // Reload form to show updated data
                if (this.selectedCourseId) {
                    this.loadAttendanceForm(this.selectedCourseId);
                }
            } else {
                this.showAlert('error', 'Có lỗi xảy ra: ' + data.message);
            }
        } catch (error) {
            this.showAlert('error', 'Có lỗi xảy ra: ' + error.message);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    }

    showError(message) {
        if (this.formContainer) {
            this.formContainer.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                </div>
            `;
        }
    }

    showAlert(type, message) {
        if (window.showToast) {
            window.showToast(message, type === 'success' ? 'success' : 'error');
        } else {
            // Fallback alert
            alert(message);
        }
    }
}

// Export
window.AttendanceTreeComponent = AttendanceTreeComponent;
