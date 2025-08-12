/**
 * ModalManager Service - Quản lý modal thống nhất
 * Tuân thủ Single Responsibility Principle
 */
class ModalManager {
    constructor() {
        this.modals = new Map();
        this.defaultConfig = {
            backdrop: 'static',
            keyboard: false,
            focus: true,
            show: true
        };
    }

    /**
     * Tạo và hiển thị modal
     * @param {string} id - ID của modal
     * @param {Object} config - Cấu hình modal
     */
    create(id, config = {}) {
        const finalConfig = { ...this.defaultConfig, ...config };
        
        // Tạo modal element nếu chưa có
        let modalElement = document.getElementById(id);
        if (!modalElement) {
            modalElement = this.createModalElement(id, finalConfig);
        }

        // Khởi tạo Bootstrap modal
        const modal = new bootstrap.Modal(modalElement, finalConfig);
        
        // Lưu vào cache
        this.modals.set(id, {
            element: modalElement,
            instance: modal,
            config: finalConfig
        });

        return modal;
    }

    /**
     * Hiển thị modal
     * @param {string} id - ID của modal
     * @param {Object} options - Tùy chọn hiển thị
     */
    show(id, options = {}) {
        let modal = this.modals.get(id);
        
        if (!modal) {
            modal = this.create(id, options.config || {});
        }

        // Cập nhật nội dung nếu có
        if (options.title) {
            this.setTitle(id, options.title);
        }
        
        if (options.body) {
            this.setBody(id, options.body);
        }
        
        if (options.footer) {
            this.setFooter(id, options.footer);
        }

        // Hiển thị modal
        modal.instance.show();
        
        return modal.instance;
    }

    /**
     * Ẩn modal
     * @param {string} id - ID của modal
     */
    hide(id) {
        const modal = this.modals.get(id);
        if (modal) {
            modal.instance.hide();
        }
    }

    /**
     * Xóa modal
     * @param {string} id - ID của modal
     */
    destroy(id) {
        const modal = this.modals.get(id);
        if (modal) {
            modal.instance.dispose();
            modal.element.remove();
            this.modals.delete(id);
        }
    }

    /**
     * Cập nhật title của modal
     * @param {string} id - ID của modal
     * @param {string} title - Title mới
     */
    setTitle(id, title) {
        const modal = this.modals.get(id);
        if (modal) {
            const titleElement = modal.element.querySelector('.modal-title');
            if (titleElement) {
                titleElement.innerHTML = title;
            }
        }
    }

    /**
     * Cập nhật body của modal
     * @param {string} id - ID của modal
     * @param {string|HTMLElement} body - Nội dung body
     */
    setBody(id, body) {
        const modal = this.modals.get(id);
        if (modal) {
            const bodyElement = modal.element.querySelector('.modal-body');
            if (bodyElement) {
                if (typeof body === 'string') {
                    bodyElement.innerHTML = body;
                } else {
                    bodyElement.innerHTML = '';
                    bodyElement.appendChild(body);
                }
            }
        }
    }

    /**
     * Cập nhật footer của modal
     * @param {string} id - ID của modal
     * @param {string|HTMLElement} footer - Nội dung footer
     */
    setFooter(id, footer) {
        const modal = this.modals.get(id);
        if (modal) {
            const footerElement = modal.element.querySelector('.modal-footer');
            if (footerElement) {
                if (typeof footer === 'string') {
                    footerElement.innerHTML = footer;
                } else {
                    footerElement.innerHTML = '';
                    footerElement.appendChild(footer);
                }
            }
        }
    }

    /**
     * Hiển thị modal xác nhận
     * @param {Object} options - Tùy chọn
     */
    confirm(options = {}) {
        const defaultOptions = {
            title: 'Xác nhận',
            message: 'Bạn có chắc chắn muốn thực hiện hành động này?',
            confirmText: 'Xác nhận',
            cancelText: 'Hủy',
            confirmClass: 'btn-primary',
            cancelClass: 'btn-secondary'
        };

        const finalOptions = { ...defaultOptions, ...options };
        const modalId = 'confirmModal';

        return new Promise((resolve) => {
            const body = `<p>${finalOptions.message}</p>`;
            const footer = `
                <button type="button" class="btn ${finalOptions.cancelClass}" data-bs-dismiss="modal">
                    ${finalOptions.cancelText}
                </button>
                <button type="button" class="btn ${finalOptions.confirmClass}" id="confirmButton">
                    ${finalOptions.confirmText}
                </button>
            `;

            this.show(modalId, {
                title: finalOptions.title,
                body: body,
                footer: footer,
                config: {
                    backdrop: 'static',
                    keyboard: false
                }
            });

            // Xử lý sự kiện
            const modal = this.modals.get(modalId);
            const confirmButton = modal.element.querySelector('#confirmButton');
            
            const handleConfirm = () => {
                this.hide(modalId);
                resolve(true);
                cleanup();
            };

            const handleCancel = () => {
                this.hide(modalId);
                resolve(false);
                cleanup();
            };

            const cleanup = () => {
                confirmButton.removeEventListener('click', handleConfirm);
                modal.element.removeEventListener('hidden.bs.modal', handleCancel);
            };

            confirmButton.addEventListener('click', handleConfirm);
            modal.element.addEventListener('hidden.bs.modal', handleCancel, { once: true });
        });
    }

    /**
     * Hiển thị modal loading
     * @param {string} message - Thông điệp loading
     */
    showLoading(message = 'Đang tải...') {
        const modalId = 'loadingModal';
        const body = `
            <div class="text-center">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>${message}</p>
            </div>
        `;

        this.show(modalId, {
            title: 'Đang xử lý',
            body: body,
            config: {
                backdrop: 'static',
                keyboard: false
            }
        });

        // Ẩn footer cho loading modal
        const modal = this.modals.get(modalId);
        const footer = modal.element.querySelector('.modal-footer');
        if (footer) {
            footer.style.display = 'none';
        }
    }

    /**
     * Ẩn modal loading
     */
    hideLoading() {
        this.hide('loadingModal');
    }

    /**
     * Tạo modal element cơ bản
     * @param {string} id - ID của modal
     * @param {Object} config - Cấu hình
     */
    createModalElement(id, config) {
        const modalHTML = `
            <div class="modal fade" id="${id}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog ${config.size || ''}">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        return document.getElementById(id);
    }

    /**
     * Lấy instance modal
     * @param {string} id - ID của modal
     */
    getInstance(id) {
        const modal = this.modals.get(id);
        return modal ? modal.instance : null;
    }

    /**
     * Kiểm tra modal có đang hiển thị không
     * @param {string} id - ID của modal
     */
    isShown(id) {
        const modal = this.modals.get(id);
        return modal ? modal.element.classList.contains('show') : false;
    }

    /**
     * Đóng tất cả modal
     */
    hideAll() {
        this.modals.forEach((modal, id) => {
            this.hide(id);
        });
    }
}

// Export instance
window.ModalManager = new ModalManager();
