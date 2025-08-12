/**
 * FormHandler Service - Xử lý form validation và submission thống nhất
 * Tuân thủ Single Responsibility Principle
 */
class FormHandler {
    constructor() {
        this.defaultConfig = {
            showLoading: true,
            resetOnSuccess: true,
            reloadOnSuccess: false,
            redirectOnSuccess: null,
            successMessage: 'Thao tác thành công',
            errorMessage: 'Có lỗi xảy ra',
            loadingText: 'Đang xử lý...',
            submitButtonSelector: '[type="submit"]'
        };
    }

    /**
     * Xử lý submit form với AJAX
     * @param {HTMLFormElement|string} form - Form element hoặc selector
     * @param {Object} config - Cấu hình
     */
    async submitForm(form, config = {}) {
        const formElement = typeof form === 'string' ? document.querySelector(form) : form;
        if (!formElement) {
            throw new Error('Form not found');
        }

        const finalConfig = { ...this.defaultConfig, ...config };
        const formData = new FormData(formElement);
        const submitButton = formElement.querySelector(finalConfig.submitButtonSelector);

        try {
            // Hiển thị loading state
            if (finalConfig.showLoading && submitButton) {
                this.setLoadingState(submitButton, finalConfig.loadingText);
            }

            // Clear previous errors
            this.clearErrors(formElement);

            // Gửi request
            const response = await this.sendRequest(formElement, formData, finalConfig);

            // Xử lý response thành công
            await this.handleSuccess(response, formElement, finalConfig);

        } catch (error) {
            // Xử lý lỗi
            this.handleError(error, formElement, finalConfig);
        } finally {
            // Reset loading state
            if (finalConfig.showLoading && submitButton) {
                this.resetLoadingState(submitButton);
            }
        }
    }

    /**
     * Gửi AJAX request
     */
    async sendRequest(formElement, formData, config) {
        const url = config.url || formElement.action;
        const method = config.method || formElement.method || 'POST';

        const requestConfig = {
            method: method.toUpperCase(),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            }
        };

        // Thêm body cho POST/PUT/PATCH
        if (['POST', 'PUT', 'PATCH'].includes(requestConfig.method)) {
            if (config.contentType === 'json') {
                requestConfig.headers['Content-Type'] = 'application/json';
                requestConfig.body = JSON.stringify(Object.fromEntries(formData));
            } else {
                requestConfig.body = formData;
            }
        }

        const response = await fetch(url, requestConfig);
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new ValidationError(response.status, errorData);
        }

        return await response.json();
    }

    /**
     * Xử lý response thành công
     */
    async handleSuccess(response, formElement, config) {
        // Hiển thị thông báo thành công
        if (response.message || config.successMessage) {
            this.showNotification(response.message || config.successMessage, 'success');
        }

        // Reset form nếu cần
        if (config.resetOnSuccess) {
            formElement.reset();
            this.clearErrors(formElement);
        }

        // Gọi callback nếu có
        if (config.onSuccess && typeof config.onSuccess === 'function') {
            await config.onSuccess(response, formElement);
        }

        // Redirect hoặc reload
        if (config.redirectOnSuccess) {
            setTimeout(() => {
                window.location.href = config.redirectOnSuccess;
            }, 1000);
        } else if (config.reloadOnSuccess) {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }
    }

    /**
     * Xử lý lỗi
     */
    handleError(error, formElement, config) {
        if (error instanceof ValidationError && error.status === 422) {
            // Hiển thị lỗi validation
            this.showValidationErrors(error.data.errors || {}, formElement);
            this.showNotification('Vui lòng kiểm tra lại thông tin', 'error');
        } else {
            // Hiển thị lỗi chung
            const message = error.data?.message || config.errorMessage;
            this.showNotification(message, 'error');
        }

        // Gọi callback lỗi nếu có
        if (config.onError && typeof config.onError === 'function') {
            config.onError(error, formElement);
        }
    }

    /**
     * Hiển thị lỗi validation
     */
    showValidationErrors(errors, formElement) {
        Object.keys(errors).forEach(fieldName => {
            const field = formElement.querySelector(`[name="${fieldName}"]`);
            const errorMessage = Array.isArray(errors[fieldName]) ? errors[fieldName][0] : errors[fieldName];
            
            if (field) {
                this.showFieldError(field, errorMessage);
            }
        });
    }

    /**
     * Hiển thị lỗi cho một field cụ thể
     */
    showFieldError(field, message) {
        // Thêm class invalid
        field.classList.add('is-invalid');
        
        // Tìm hoặc tạo element hiển thị lỗi
        let errorElement = field.parentNode.querySelector('.invalid-feedback');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'invalid-feedback';
            field.parentNode.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
    }

    /**
     * Xóa tất cả lỗi validation
     */
    clearErrors(formElement) {
        // Xóa class invalid
        formElement.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });
        
        // Xóa message lỗi
        formElement.querySelectorAll('.invalid-feedback').forEach(errorElement => {
            errorElement.remove();
        });
    }

    /**
     * Set loading state cho button
     */
    setLoadingState(button, loadingText) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${loadingText}`;
    }

    /**
     * Reset loading state cho button
     */
    resetLoadingState(button) {
        button.disabled = false;
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
            delete button.dataset.originalText;
        }
    }

    /**
     * Hiển thị notification (có thể override để sử dụng thư viện khác)
     */
    showNotification(message, type = 'info') {
        // Sử dụng hàm showToast nếu có, nếu không thì dùng alert
        if (typeof window.showToast === 'function') {
            window.showToast(message, type);
        } else {
            alert(message);
        }
    }

    /**
     * Khởi tạo auto-submit cho các form có data-auto-submit
     */
    initAutoSubmit() {
        document.querySelectorAll('form[data-auto-submit]').forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                
                const config = this.parseDataAttributes(form);
                this.submitForm(form, config);
            });
        });
    }

    /**
     * Parse data attributes thành config object
     */
    parseDataAttributes(element) {
        const config = {};
        const dataset = element.dataset;
        
        if (dataset.successMessage) config.successMessage = dataset.successMessage;
        if (dataset.errorMessage) config.errorMessage = dataset.errorMessage;
        if (dataset.loadingText) config.loadingText = dataset.loadingText;
        if (dataset.redirectOnSuccess) config.redirectOnSuccess = dataset.redirectOnSuccess;
        if (dataset.reloadOnSuccess !== undefined) config.reloadOnSuccess = dataset.reloadOnSuccess === 'true';
        if (dataset.resetOnSuccess !== undefined) config.resetOnSuccess = dataset.resetOnSuccess === 'true';
        
        return config;
    }
}

/**
 * Custom Error class cho validation errors
 */
class ValidationError extends Error {
    constructor(status, data) {
        super('Validation Error');
        this.status = status;
        this.data = data;
    }
}

// Export instance
window.FormHandler = new FormHandler();

// Auto-init khi DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.FormHandler.initAutoSubmit();
});
