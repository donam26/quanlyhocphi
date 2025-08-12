/**
 * ModalComponent - Component cho modal handling
 * Extends BaseComponent và sử dụng ModalManager service
 */
class ModalComponent extends BaseComponent {
    getDefaultOptions() {
        return {
            backdrop: 'static',
            keyboard: false,
            focus: true,
            show: false,
            autoDestroy: false,
            size: '', // '', 'modal-sm', 'modal-lg', 'modal-xl'
            centered: false,
            scrollable: false,
            fullscreen: false // false, true, 'sm-down', 'md-down', 'lg-down', 'xl-down', 'xxl-down'
        };
    }

    initializeElements() {
        this.modalId = this.element.id || 'modal-' + Date.now();
        this.element.id = this.modalId;
        
        this.modal = null;
        this.header = this.find('.modal-header');
        this.title = this.find('.modal-title');
        this.body = this.find('.modal-body');
        this.footer = this.find('.modal-footer');
        this.closeButtons = this.findAll('[data-bs-dismiss="modal"]');
    }

    bindEvents() {
        // Bootstrap modal events
        this.addEventListener(this.element, 'show.bs.modal', (e) => {
            this.emit('modal:show', { originalEvent: e });
        });

        this.addEventListener(this.element, 'shown.bs.modal', (e) => {
            this.emit('modal:shown', { originalEvent: e });
        });

        this.addEventListener(this.element, 'hide.bs.modal', (e) => {
            this.emit('modal:hide', { originalEvent: e });
        });

        this.addEventListener(this.element, 'hidden.bs.modal', (e) => {
            this.emit('modal:hidden', { originalEvent: e });
            
            if (this.getOption('autoDestroy')) {
                this.destroy();
            }
        });

        // Custom events
        this.on('modal:open', (e) => this.open(e.detail));
        this.on('modal:close', () => this.close());
        this.on('modal:toggle', () => this.toggle());
        this.on('modal:update-content', (e) => this.updateContent(e.detail));
    }

    afterInit() {
        // Khởi tạo Bootstrap modal
        this.modal = window.ModalManager.create(this.modalId, this.options);
        
        // Apply additional options
        this.applyModalOptions();
    }

    /**
     * Apply modal options to DOM
     */
    applyModalOptions() {
        const dialog = this.find('.modal-dialog');
        if (!dialog) return;

        // Size
        const size = this.getOption('size');
        if (size) {
            dialog.classList.add(size);
        }

        // Centered
        if (this.getOption('centered')) {
            dialog.classList.add('modal-dialog-centered');
        }

        // Scrollable
        if (this.getOption('scrollable')) {
            dialog.classList.add('modal-dialog-scrollable');
        }

        // Fullscreen
        const fullscreen = this.getOption('fullscreen');
        if (fullscreen === true) {
            dialog.classList.add('modal-fullscreen');
        } else if (fullscreen) {
            dialog.classList.add(`modal-fullscreen-${fullscreen}`);
        }
    }

    /**
     * Open modal
     */
    open(options = {}) {
        if (options.title) {
            this.setTitle(options.title);
        }
        
        if (options.body) {
            this.setBody(options.body);
        }
        
        if (options.footer) {
            this.setFooter(options.footer);
        }

        if (options.size) {
            this.setSize(options.size);
        }

        window.ModalManager.show(this.modalId, options);
        return this;
    }

    /**
     * Close modal
     */
    close() {
        window.ModalManager.hide(this.modalId);
        return this;
    }

    /**
     * Toggle modal
     */
    toggle() {
        if (this.isOpen()) {
            this.close();
        } else {
            this.open();
        }
        return this;
    }

    /**
     * Check if modal is open
     */
    isOpen() {
        return window.ModalManager.isShown(this.modalId);
    }

    /**
     * Set modal title
     */
    setTitle(title) {
        window.ModalManager.setTitle(this.modalId, title);
        this.emit('modal:title-changed', { title });
        return this;
    }

    /**
     * Set modal body
     */
    setBody(body) {
        window.ModalManager.setBody(this.modalId, body);
        this.emit('modal:body-changed', { body });
        return this;
    }

    /**
     * Set modal footer
     */
    setFooter(footer) {
        window.ModalManager.setFooter(this.modalId, footer);
        this.emit('modal:footer-changed', { footer });
        return this;
    }

    /**
     * Update modal content
     */
    updateContent(content) {
        if (content.title) this.setTitle(content.title);
        if (content.body) this.setBody(content.body);
        if (content.footer) this.setFooter(content.footer);
        
        this.emit('modal:content-updated', { content });
        return this;
    }

    /**
     * Set modal size
     */
    setSize(size) {
        const dialog = this.find('.modal-dialog');
        if (!dialog) return this;

        // Remove existing size classes
        dialog.classList.remove('modal-sm', 'modal-lg', 'modal-xl');
        
        // Add new size class
        if (size && size !== '') {
            dialog.classList.add(size);
        }

        this.setOption('size', size);
        this.emit('modal:size-changed', { size });
        return this;
    }

    /**
     * Set loading state
     */
    setLoading(loading = true, message = 'Đang tải...') {
        if (loading) {
            const loadingContent = `
                <div class="text-center p-4">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mb-0">${message}</p>
                </div>
            `;
            this.setBody(loadingContent);
            
            // Hide footer during loading
            if (this.footer) {
                this.footer.style.display = 'none';
            }
        } else {
            // Show footer again
            if (this.footer) {
                this.footer.style.display = '';
            }
        }

        super.setLoading(loading);
        return this;
    }

    /**
     * Load content from URL
     */
    async loadContent(url, options = {}) {
        try {
            this.setLoading(true, options.loadingMessage || 'Đang tải nội dung...');
            
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html,application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const contentType = response.headers.get('content-type');
            let content;

            if (contentType && contentType.includes('application/json')) {
                const data = await response.json();
                content = data.html || data.content || JSON.stringify(data);
            } else {
                content = await response.text();
            }

            this.setBody(content);
            this.setLoading(false);
            
            this.emit('modal:content-loaded', { url, content });
            
        } catch (error) {
            this.setBody(`
                <div class="alert alert-danger">
                    <h6>Lỗi tải nội dung</h6>
                    <p class="mb-0">${error.message}</p>
                </div>
            `);
            this.setLoading(false);
            
            this.emit('modal:content-error', { url, error });
        }

        return this;
    }

    /**
     * Show confirmation dialog
     */
    static confirm(options = {}) {
        return window.ModalManager.confirm(options);
    }

    /**
     * Show loading modal
     */
    static showLoading(message = 'Đang tải...') {
        window.ModalManager.showLoading(message);
    }

    /**
     * Hide loading modal
     */
    static hideLoading() {
        window.ModalManager.hideLoading();
    }

    /**
     * Create modal from template
     */
    static createFromTemplate(template, options = {}) {
        const modalElement = document.createElement('div');
        modalElement.innerHTML = template;
        document.body.appendChild(modalElement.firstElementChild);
        
        return new ModalComponent(modalElement.firstElementChild, options);
    }

    /**
     * Create simple modal
     */
    static create(options = {}) {
        const modalId = options.id || 'modal-' + Date.now();
        const template = `
            <div class="modal fade" id="${modalId}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${options.title || ''}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            ${options.body || ''}
                        </div>
                        <div class="modal-footer">
                            ${options.footer || '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>'}
                        </div>
                    </div>
                </div>
            </div>
        `;

        return this.createFromTemplate(template, options);
    }

    /**
     * Destroy modal
     */
    destroy() {
        if (this.modal) {
            window.ModalManager.destroy(this.modalId);
        }
        
        super.destroy();
    }
}

// Export
window.ModalComponent = ModalComponent;
