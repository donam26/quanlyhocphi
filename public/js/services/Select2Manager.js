/**
 * Select2 Manager Service
 * Quản lý tất cả Select2 initialization và configuration
 */
class Select2Manager {
    constructor() {
        this.defaultConfig = {
            theme: 'bootstrap-5',
            width: '100%',
            allowClear: true
        };
        
        this.ajaxConfig = {
            delay: 250,
            cache: true,
            minimumInputLength: 2
        };
        
        this.initialized = false;
    }

    /**
     * Initialize Select2 Manager
     */
    init() {
        if (this.initialized) {
            return;
        }

        // Đợi jQuery và Select2 load
        this.waitForDependencies().then(() => {
            this.setupGlobalConfig();
            this.autoInitialize();
            this.initialized = true;
            console.log('✅ Select2 Manager initialized');
        });
    }

    /**
     * Đợi jQuery và Select2 load
     */
    async waitForDependencies() {
        return new Promise((resolve) => {
            const checkDependencies = () => {
                if (window.$ && window.$.fn.select2) {
                    resolve();
                } else {
                    setTimeout(checkDependencies, 100);
                }
            };
            checkDependencies();
        });
    }

    /**
     * Setup global Select2 configuration
     */
    setupGlobalConfig() {
        // Set default config cho tất cả Select2
        if (window.$.fn.select2.defaults) {
            window.$.fn.select2.defaults.set('theme', 'bootstrap-5');
            window.$.fn.select2.defaults.set('width', '100%');
        }
    }

    /**
     * Auto initialize tất cả Select2 elements
     */
    autoInitialize() {
        // Basic Select2
        this.initializeBasicSelects();
        
        // AJAX Select2
        this.initializeAjaxSelects();
        
        // Student search Select2
        this.initializeStudentSearch();
    }

    /**
     * Initialize basic Select2 elements
     */
    initializeBasicSelects() {
        const selects = document.querySelectorAll('.select2:not(.select2-ajax):not(.student-search)');
        selects.forEach(select => {
            if (!window.$(select).hasClass('select2-hidden-accessible')) {
                window.$(select).select2(this.defaultConfig);
            }
        });
    }

    /**
     * Initialize AJAX Select2 elements
     */
    initializeAjaxSelects() {
        const ajaxSelects = document.querySelectorAll('.select2-ajax');
        ajaxSelects.forEach(select => {
            if (!window.$(select).hasClass('select2-hidden-accessible')) {
                const config = {
                    ...this.defaultConfig,
                    ...this.ajaxConfig,
                    placeholder: select.dataset.placeholder || 'Tìm kiếm...',
                    ajax: {
                        url: select.dataset.url,
                        dataType: 'json',
                        ...this.ajaxConfig,
                        data: function(params) {
                            return {
                                q: params.term,
                                page: params.page || 1
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results || data,
                                pagination: {
                                    more: data.pagination ? data.pagination.more : false
                                }
                            };
                        }
                    }
                };
                
                window.$(select).select2(config);
            }
        });
    }

    /**
     * Initialize Student Search Select2
     */
    initializeStudentSearch() {
        const studentSearches = document.querySelectorAll('.student-search, #student_search');
        studentSearches.forEach(select => {
            if (!window.$(select).hasClass('select2-hidden-accessible')) {
                const config = {
                    ...this.defaultConfig,
                    placeholder: 'Tìm theo tên, SĐT, CCCD...',
                    minimumInputLength: 2,
                    ajax: {
                        url: '/api/students/search',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.map(function(item) {
                                    return {
                                        id: item.id,
                                        text: item.text
                                    };
                                })
                            };
                        },
                        cache: true
                    }
                };
                
                window.$(select).select2(config);
                
                // Handle selection
                window.$(select).on('select2:select', function(e) {
                    const studentId = e.params.data.id;
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('student_id', studentId);
                    window.location.href = currentUrl.toString();
                });

                // Handle clear
                window.$(select).on('select2:clear', function(e) {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.delete('student_id');
                    window.location.href = currentUrl.toString();
                });
            }
        });
    }

    /**
     * Initialize Select2 trong modal
     */
    initializeInModal(modalElement) {
        const selects = modalElement.querySelectorAll('select:not(.select2-hidden-accessible)');
        selects.forEach(select => {
            const $select = window.$(select);
            
            if ($select.hasClass('select2-ajax')) {
                // AJAX Select2
                const config = {
                    ...this.defaultConfig,
                    ...this.ajaxConfig,
                    dropdownParent: window.$(modalElement),
                    placeholder: select.dataset.placeholder || 'Tìm kiếm...',
                    ajax: {
                        url: select.dataset.url,
                        dataType: 'json',
                        ...this.ajaxConfig,
                        data: function(params) {
                            return {
                                q: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: data.results || data
                            };
                        }
                    }
                };
                $select.select2(config);
            } else {
                // Basic Select2
                $select.select2({
                    ...this.defaultConfig,
                    dropdownParent: window.$(modalElement)
                });
            }
        });
    }

    /**
     * Destroy Select2 instance
     */
    destroy(selector) {
        const $element = window.$(selector);
        if ($element.hasClass('select2-hidden-accessible')) {
            $element.select2('destroy');
        }
    }

    /**
     * Refresh Select2 options
     */
    refresh(selector) {
        const $element = window.$(selector);
        if ($element.hasClass('select2-hidden-accessible')) {
            $element.trigger('change');
        }
    }

    /**
     * Set value cho Select2
     */
    setValue(selector, value) {
        const $element = window.$(selector);
        if ($element.hasClass('select2-hidden-accessible')) {
            $element.val(value).trigger('change');
        }
    }

    /**
     * Clear value của Select2
     */
    clearValue(selector) {
        const $element = window.$(selector);
        if ($element.hasClass('select2-hidden-accessible')) {
            $element.val(null).trigger('change');
        }
    }

    /**
     * Re-initialize tất cả Select2 elements
     */
    reinitialize() {
        // Destroy existing instances
        window.$('.select2-hidden-accessible').select2('destroy');
        
        // Re-initialize
        this.autoInitialize();
    }
}

// Export instance
window.Select2Manager = new Select2Manager();

// Auto-initialize when DOM ready
document.addEventListener('DOMContentLoaded', function() {
    window.Select2Manager.init();
});

// Re-initialize when new content is added
document.addEventListener('DOMNodeInserted', function(e) {
    if (e.target.nodeType === 1) { // Element node
        const selects = e.target.querySelectorAll ? e.target.querySelectorAll('select') : [];
        if (selects.length > 0) {
            setTimeout(() => {
                window.Select2Manager.autoInitialize();
            }, 100);
        }
    }
});
