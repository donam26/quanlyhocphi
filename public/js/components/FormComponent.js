/**
 * FormComponent - Component cho form handling
 * Extends BaseComponent và sử dụng FormHandler service
 */
class FormComponent extends BaseComponent {
    getDefaultOptions() {
        return {
            autoSubmit: true,
            validateOnChange: true,
            validateOnBlur: true,
            resetOnSuccess: true,
            showLoading: true,
            successMessage: 'Thao tác thành công',
            errorMessage: 'Có lỗi xảy ra',
            submitButtonSelector: '[type="submit"]',
            requiredFieldSelector: '[required]',
            fieldSelector: 'input, select, textarea',
            errorClass: 'is-invalid',
            errorMessageClass: 'invalid-feedback'
        };
    }

    initializeElements() {
        this.form = this.element.tagName === 'FORM' ? this.element : this.find('form');
        this.submitButton = this.find(this.getOption('submitButtonSelector'));
        this.fields = this.findAll(this.getOption('fieldSelector'));
        this.requiredFields = this.findAll(this.getOption('requiredFieldSelector'));
        
        if (!this.form) {
            console.error('FormComponent: Form element not found');
            return;
        }
    }

    bindEvents() {
        if (!this.form) return;

        // Form submit event
        if (this.getOption('autoSubmit')) {
            this.addEventListener(this.form, 'submit', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });
        }

        // Field validation events
        if (this.getOption('validateOnChange')) {
            this.fields.forEach(field => {
                this.addEventListener(field, 'change', () => {
                    this.validateField(field);
                });
            });
        }

        if (this.getOption('validateOnBlur')) {
            this.fields.forEach(field => {
                this.addEventListener(field, 'blur', () => {
                    this.validateField(field);
                });
            });
        }

        // Custom events
        this.on('form:submit', (e) => this.handleSubmit(e.detail));
        this.on('form:reset', () => this.reset());
        this.on('form:validate', () => this.validate());
    }

    /**
     * Handle form submission
     */
    async handleSubmit(customData = {}) {
        if (!this.form) return;

        try {
            // Validate form trước khi submit
            if (!this.validate()) {
                this.emit('form:validation-failed');
                return;
            }

            this.emit('form:submit-start');

            // Chuẩn bị config cho FormHandler
            const config = {
                url: customData.url || this.form.action,
                method: customData.method || this.form.method,
                showLoading: this.getOption('showLoading'),
                resetOnSuccess: this.getOption('resetOnSuccess'),
                successMessage: customData.successMessage || this.getOption('successMessage'),
                errorMessage: customData.errorMessage || this.getOption('errorMessage'),
                onSuccess: (response) => {
                    this.handleSuccess(response);
                },
                onError: (error) => {
                    this.handleError(error);
                }
            };

            // Sử dụng FormHandler để submit
            await window.FormHandler.submitForm(this.form, config);

        } catch (error) {
            this.handleError(error);
        }
    }

    /**
     * Handle successful submission
     */
    handleSuccess(response) {
        this.clearErrors();
        this.emit('form:submit-success', { response });
        
        if (this.getOption('resetOnSuccess')) {
            this.reset();
        }
    }

    /**
     * Handle submission error
     */
    handleError(error) {
        this.emit('form:submit-error', { error });
        
        // Hiển thị validation errors nếu có
        if (error.status === 422 && error.data && error.data.errors) {
            this.showValidationErrors(error.data.errors);
        }
    }

    /**
     * Validate entire form
     */
    validate() {
        let isValid = true;
        
        this.clearErrors();
        
        // Validate required fields
        this.requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });

        // Custom validation
        if (!this.customValidation()) {
            isValid = false;
        }

        this.emit('form:validated', { isValid });
        return isValid;
    }

    /**
     * Validate single field
     */
    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Required validation
        if (field.hasAttribute('required') && !value) {
            isValid = false;
            errorMessage = 'Trường này là bắt buộc';
        }

        // Email validation
        if (isValid && field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Email không hợp lệ';
            }
        }

        // Phone validation
        if (isValid && field.type === 'tel' && value) {
            const phoneRegex = /^[0-9]{10,11}$/;
            if (!phoneRegex.test(value.replace(/\D/g, ''))) {
                isValid = false;
                errorMessage = 'Số điện thoại không hợp lệ';
            }
        }

        // Custom field validation
        const customError = this.validateCustomField(field, value);
        if (customError) {
            isValid = false;
            errorMessage = customError;
        }

        // Show/hide error
        if (isValid) {
            this.clearFieldError(field);
        } else {
            this.showFieldError(field, errorMessage);
        }

        this.emit('form:field-validated', { field, isValid, errorMessage });
        return isValid;
    }

    /**
     * Custom validation - override trong subclass
     */
    customValidation() {
        return true;
    }

    /**
     * Custom field validation - override trong subclass
     */
    validateCustomField(field, value) {
        return null;
    }

    /**
     * Show validation errors
     */
    showValidationErrors(errors) {
        Object.keys(errors).forEach(fieldName => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            const errorMessage = Array.isArray(errors[fieldName]) ? errors[fieldName][0] : errors[fieldName];
            
            if (field) {
                this.showFieldError(field, errorMessage);
            }
        });
    }

    /**
     * Show field error
     */
    showFieldError(field, message) {
        field.classList.add(this.getOption('errorClass'));
        
        // Tìm hoặc tạo error element
        let errorElement = field.parentNode.querySelector('.' + this.getOption('errorMessageClass'));
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = this.getOption('errorMessageClass');
            field.parentNode.appendChild(errorElement);
        }
        
        errorElement.textContent = message;
    }

    /**
     * Clear field error
     */
    clearFieldError(field) {
        field.classList.remove(this.getOption('errorClass'));
        
        const errorElement = field.parentNode.querySelector('.' + this.getOption('errorMessageClass'));
        if (errorElement) {
            errorElement.remove();
        }
    }

    /**
     * Clear all errors
     */
    clearErrors() {
        this.findAll('.' + this.getOption('errorClass')).forEach(field => {
            field.classList.remove(this.getOption('errorClass'));
        });
        
        this.findAll('.' + this.getOption('errorMessageClass')).forEach(errorElement => {
            errorElement.remove();
        });
    }

    /**
     * Reset form
     */
    reset() {
        if (this.form) {
            this.form.reset();
            this.clearErrors();
            this.emit('form:reset');
        }
    }

    /**
     * Get form data as object
     */
    getFormData() {
        if (!this.form) return {};
        
        const formData = new FormData(this.form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        return data;
    }

    /**
     * Set form data
     */
    setFormData(data) {
        Object.keys(data).forEach(key => {
            const field = this.form.querySelector(`[name="${key}"]`);
            if (field) {
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = field.value === data[key];
                } else {
                    field.value = data[key];
                }
            }
        });
        
        this.emit('form:data-set', { data });
    }

    /**
     * Enable/disable form
     */
    setEnabled(enabled = true) {
        this.fields.forEach(field => {
            field.disabled = !enabled;
        });
        
        if (this.submitButton) {
            this.submitButton.disabled = !enabled;
        }
        
        this.emit('form:enabled-changed', { enabled });
    }

    /**
     * Set loading state
     */
    setLoading(loading = true) {
        super.setLoading(loading);
        this.setEnabled(!loading);
        
        if (this.submitButton && loading) {
            const originalText = this.submitButton.innerHTML;
            this.submitButton.dataset.originalText = originalText;
            this.submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...';
        } else if (this.submitButton && !loading && this.submitButton.dataset.originalText) {
            this.submitButton.innerHTML = this.submitButton.dataset.originalText;
            delete this.submitButton.dataset.originalText;
        }
    }
}

// Export
window.FormComponent = FormComponent;
