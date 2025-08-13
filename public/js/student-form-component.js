/**
 * StudentFormComponent - Component form thống nhất cho tạo mới và chỉnh sửa học viên
 * Giải quyết vấn đề trùng lặp form và không đồng bộ
 */
class StudentFormComponent {
    constructor() {
        this.formTemplates = {
            create: this.getCreateFormTemplate(),
            edit: this.getEditFormTemplate()
        };
        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeSelect2Components();
    }

    bindEvents() {
        // Đảm bảo jQuery đã sẵn sàng
        if (typeof $ === 'undefined') {
            console.warn('jQuery chưa được load, delay bind events');
            setTimeout(() => this.bindEvents(), 100);
            return;
        }

        // Bind events cho modal show
        $(document).on('shown.bs.modal', '#createStudentModal', () => {
            this.initializeFormComponents('create');
        });

        $(document).on('shown.bs.modal', '#editStudentModal', () => {
            this.initializeFormComponents('edit');
        });

        // Bind events cho select2 changes
        $(document).on('change', '#create-place-of-birth-select', (e) => {
            const selectedText = $(e.target).find(':selected').text();
            $('#create-place-of-birth').val(selectedText);
        });

        $(document).on('change', '#edit-place-of-birth-select', (e) => {
            const selectedText = $(e.target).find(':selected').text();
            $('#edit-place-of-birth').val(selectedText);
        });

        $(document).on('change', '#create-nation-select', (e) => {
            const selectedText = $(e.target).find(':selected').text();
            $('#create-nation').val(selectedText);
        });

        $(document).on('change', '#edit-nation-select', (e) => {
            const selectedText = $(e.target).find(':selected').text();
            $('#edit-nation').val(selectedText);
        });
    }

    initializeFormComponents(type) {
        setTimeout(() => {
            this.initProvinceSelect2(type);
            this.initLocationSelect2(type);
            this.initEthnicitySelect2(type);
        }, 100);
    }

    initProvinceSelect2(type) {
        const selector = `#${type}-province`;
        
        try {
            $(selector).select2('destroy');
        } catch (e) {
            // Ignore if not initialized
        }

        $(selector).select2({
            theme: 'bootstrap-5',
            placeholder: 'Tìm kiếm tỉnh thành...',
            allowClear: true,
            dropdownParent: $(`#${type}StudentModal`),
            width: '100%',
            minimumInputLength: 0,
            ajax: {
                url: '/api/provinces',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term || '',
                        keyword: params.term || ''
                    };
                },
                processResults: function(response) {
                    if (response && response.success && response.data && Array.isArray(response.data)) {
                        return {
                            results: response.data.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.name + ' (' + StudentFormComponent.getRegionName(item.region) + ')',
                                    region: item.region
                                };
                            })
                        };
                    } else if (Array.isArray(response)) {
                        return {
                            results: response.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.text || item.name
                                };
                            })
                        };
                    }
                    return { results: [] };
                },
                cache: false
            }
        });
    }

    initLocationSelect2(type) {
        const selector = `#${type}-place-of-birth-select`;
        const hiddenSelector = `#${type}-place-of-birth`;
        
        try {
            $(selector).select2('destroy');
        } catch (e) {
            // Ignore if not initialized
        }

        $(selector).select2({
            theme: 'bootstrap-5',
            placeholder: 'Chọn nơi sinh (tỉnh/thành)',
            allowClear: true,
            dropdownParent: $(`#${type}StudentModal`),
            width: '100%',
            ajax: {
                url: '/api/provinces',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || '',
                        keyword: params.term || ''
                    };
                },
                processResults: function (response) {
                    if (response && response.success && response.data && Array.isArray(response.data)) {
                        return {
                            results: response.data.map(function(item) {
                                return {
                                    id: item.name,
                                    text: item.name
                                };
                            })
                        };
                    }
                    return { results: [] };
                },
                cache: true
            }
        });
    }

    initEthnicitySelect2(type) {
        const selector = `#${type}-nation-select`;
        const hiddenSelector = `#${type}-nation`;
        
        try {
            $(selector).select2('destroy');
        } catch (e) {
            // Ignore if not initialized
        }

        $(selector).select2({
            theme: 'bootstrap-5',
            placeholder: 'Chọn dân tộc',
            allowClear: true,
            dropdownParent: $(`#${type}StudentModal`),
            width: '100%',
            ajax: {
                url: '/api/ethnicities',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || '',
                        keyword: params.term || ''
                    };
                },
                processResults: function (response) {
                    if (response && response.success && response.data && Array.isArray(response.data)) {
                        return {
                            results: response.data.map(function(item) {
                                return {
                                    id: item.name,
                                    text: item.name
                                };
                            })
                        };
                    }
                    return { results: [] };
                },
                cache: true
            }
        });
    }

    initializeSelect2Components() {
        // Khởi tạo các component select2 chung
        if (typeof $.fn.select2 !== 'undefined') {
            $('.select2').select2({
                theme: 'bootstrap-5'
            });
        }
    }

    getCreateFormTemplate() {
        // Template sẽ được định nghĩa trong view
        return null;
    }

    getEditFormTemplate() {
        // Template sẽ được định nghĩa trong view
        return null;
    }

    // Static utility methods
    static getRegionName(region) {
        switch(region) {
            case 'north': return 'Miền Bắc';
            case 'central': return 'Miền Trung';
            case 'south': return 'Miền Nam';
            default: return 'Không xác định';
        }
    }

    static formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('vi-VN');
    }

    // Validate form data
    validateForm(formData, type) {
        const errors = {};

        // Required fields validation
        if (!formData.get('first_name')) {
            errors.first_name = 'Họ là bắt buộc';
        }

        if (!formData.get('last_name')) {
            errors.last_name = 'Tên là bắt buộc';
        }

        if (!formData.get('phone')) {
            errors.phone = 'Số điện thoại là bắt buộc';
        } else {
            // Phone format validation
            const phonePattern = /^[0-9]{10,11}$/;
            if (!phonePattern.test(formData.get('phone'))) {
                errors.phone = 'Số điện thoại không hợp lệ';
            }
        }

        // Email validation if provided
        const email = formData.get('email');
        if (email) {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                errors.email = 'Email không hợp lệ';
            }
        }

        return errors;
    }

    // Clear form errors
    clearFormErrors(type) {
        $(`#${type}StudentModal .is-invalid`).removeClass('is-invalid');
        $(`#${type}StudentModal .invalid-feedback`).text('');
    }

    // Show form errors
    showFormErrors(errors, type) {
        this.clearFormErrors(type);

        Object.keys(errors).forEach(field => {
            const input = $(`#${type}-${field.replace('_', '-')}`);
            const errorDiv = $(`#${type}-${field.replace('_', '-')}-error`);
            
            if (input.length > 0) {
                input.addClass('is-invalid');
                if (errorDiv.length > 0) {
                    errorDiv.text(errors[field]);
                }
            }
        });
    }
}



// Khởi tạo component khi DOM ready
$(document).ready(function() {
    window.studentFormComponent = new StudentFormComponent();
    console.log('StudentFormComponent initialized');
});

// Export for global access
window.StudentFormComponent = StudentFormComponent;
