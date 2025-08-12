/**
 * Select2 Initialization Helper
 * Khởi tạo tất cả Select2 với cấu hình nhất quán
 */

class Select2Helper {
    constructor() {
        this.defaultConfig = {
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true,
            minimumInputLength: 0,
            language: {
                inputTooShort: function() {
                    return "Nhập ít nhất 1 ký tự để tìm kiếm...";
                },
                searching: function() {
                    return "Đang tìm kiếm...";
                },
                noResults: function() {
                    return "Không tìm thấy kết quả";
                },
                loadingMore: function() {
                    return "Đang tải thêm...";
                }
            }
        };
    }

    /**
     * Khởi tạo Select2 cho tìm kiếm học viên
     */
    initStudentSearch(selector = '#student_search', options = {}) {
        const config = {
            ...this.defaultConfig,
            placeholder: 'Tìm kiếm học viên theo tên, SĐT...',
            ajax: {
                url: '/api/search/autocomplete',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term || '',
                        type: 'student',
                        preload: params.term ? 'false' : 'true'
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.text,
                                full_name: item.full_name,
                                phone: item.phone,
                                email: item.email
                            };
                        })
                    };
                },
                cache: true
            },
            ...options
        };

        this.destroyAndInit(selector, config);
    }

    /**
     * Khởi tạo Select2 cho tìm kiếm khóa học
     */
    initCourseSearch(selector = '#course_search', options = {}) {
        const config = {
            ...this.defaultConfig,
            placeholder: 'Tìm kiếm khóa học...',
            ajax: {
                url: '/api/course-items/search',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term || '',
                        limit: 20
                    };
                },
                processResults: function(data) {
                    return {
                        results: data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.text || item.name,
                                path: item.path,
                                fee: item.fee
                            };
                        })
                    };
                },
                cache: true
            },
            ...options
        };

        this.destroyAndInit(selector, config);
    }

    /**
     * Khởi tạo Select2 cho tỉnh thành
     */
    initProvinceSearch(selector = '#province_select', options = {}) {
        const config = {
            ...this.defaultConfig,
            placeholder: 'Chọn tỉnh thành...',
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
                    let results = [];
                    if (response && response.success && Array.isArray(response.data)) {
                        results = response.data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.name,
                                region: item.region
                            };
                        });
                    } else if (Array.isArray(response)) {
                        results = response.map(function(item) {
                            return {
                                id: item.id,
                                text: item.text || item.name
                            };
                        });
                    }
                    return { results: results };
                },
                cache: true
            },
            ...options
        };

        this.destroyAndInit(selector, config);
    }

    /**
     * Khởi tạo Select2 cho dân tộc
     */
    initEthnicitySearch(selector = '#ethnicity_select', options = {}) {
        const config = {
            ...this.defaultConfig,
            placeholder: 'Chọn dân tộc...',
            ajax: {
                url: '/api/ethnicities',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term || '',
                        keyword: params.term || ''
                    };
                },
                processResults: function(response) {
                    let results = [];
                    if (response && response.success && Array.isArray(response.data)) {
                        results = response.data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.name
                            };
                        });
                    } else if (Array.isArray(response)) {
                        results = response.map(function(item) {
                            return {
                                id: item.id,
                                text: item.text || item.name
                            };
                        });
                    }
                    return { results: results };
                },
                cache: true
            },
            ...options
        };

        this.destroyAndInit(selector, config);
    }

    /**
     * Hủy và khởi tạo lại Select2
     */
    destroyAndInit(selector, config) {
        try {
            $(selector).select2('destroy');
        } catch (e) {
            // Không làm gì nếu select2 chưa được áp dụng
        }

        $(selector).select2(config);
    }

    /**
     * Khởi tạo tất cả Select2 cơ bản
     */
    initAll() {
        // Khởi tạo các Select2 cơ bản không có AJAX
        $('.select2').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%'
                });
            }
        });
    }
}

// Khởi tạo instance global
window.Select2Helper = new Select2Helper();

// Auto-init khi DOM ready
$(document).ready(function() {
    window.Select2Helper.initAll();
});
