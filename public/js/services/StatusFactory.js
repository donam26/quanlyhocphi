/**
 * StatusFactory JavaScript - Tạo status objects và badges phía client
 * Tuân thủ Factory Pattern và Open/Closed Principle
 */
class StatusFactory {
    constructor() {
        this.statusDefinitions = {
            payment: {
                pending: { label: 'Chờ xác nhận', color: 'warning', icon: 'fas fa-clock' },
                confirmed: { label: 'Đã xác nhận', color: 'success', icon: 'fas fa-check' },
                cancelled: { label: 'Đã hủy', color: 'danger', icon: 'fas fa-times' },
                refunded: { label: 'Đã hoàn tiền', color: 'secondary', icon: 'fas fa-undo' }
            },
            attendance: {
                present: { label: 'Có mặt', color: 'success', icon: 'fas fa-check' },
                absent: { label: 'Vắng mặt', color: 'danger', icon: 'fas fa-times' },
                late: { label: 'Đi muộn', color: 'warning', icon: 'fas fa-clock' },
                excused: { label: 'Có phép', color: 'info', icon: 'fas fa-info' }
            },
            payment_method: {
                cash: { label: 'Tiền mặt', color: 'success', icon: 'fas fa-money-bill' },
                bank_transfer: { label: 'Chuyển khoản', color: 'primary', icon: 'fas fa-university' },
                card: { label: 'Thẻ', color: 'info', icon: 'fas fa-credit-card' },
                qr_code: { label: 'Mã QR', color: 'warning', icon: 'fas fa-qrcode' },
                sepay: { label: 'SePay', color: 'danger', icon: 'fas fa-mobile-alt' },
                other: { label: 'Khác', color: 'secondary', icon: 'fas fa-ellipsis-h' }
            },
            student: {
                active: { label: 'Đang học', color: 'success', icon: 'fas fa-user-check' },
                inactive: { label: 'Tạm nghỉ', color: 'warning', icon: 'fas fa-user-clock' },
                graduated: { label: 'Đã tốt nghiệp', color: 'primary', icon: 'fas fa-graduation-cap' },
                suspended: { label: 'Đình chỉ', color: 'danger', icon: 'fas fa-user-times' }
            },
            enrollment: {
                waiting: { label: 'Danh sách chờ', color: 'warning text-dark', icon: 'fas fa-clock' },
                active: { label: 'Đang học', color: 'success', icon: 'fas fa-play' },
                completed: { label: 'Đã hoàn thành', color: 'success', icon: 'fas fa-check' },
                cancelled: { label: 'Đã hủy', color: 'danger', icon: 'fas fa-times' }
            },
            course: {
                active: { label: 'Đang học', color: 'success', icon: 'fas fa-play' },
                completed: { label: 'Đã kết thúc', color: 'secondary', icon: 'fas fa-check' }
            }
        };
    }

    /**
     * Tạo status object
     * @param {string} type - Loại status
     * @param {string} value - Giá trị status
     * @returns {Object|null} Status object
     */
    create(type, value) {
        if (!this.statusDefinitions[type] || !value) {
            return null;
        }

        const statusDef = this.statusDefinitions[type][value];
        if (!statusDef) {
            return null;
        }

        return {
            type: type,
            value: value,
            label: statusDef.label,
            color: statusDef.color,
            icon: statusDef.icon,
            badge: () => this.createBadge(type, value),
            isValid: () => true
        };
    }

    /**
     * Tạo badge HTML
     * @param {string} type - Loại status
     * @param {string} value - Giá trị status
     * @returns {string} HTML badge
     */
    createBadge(type, value) {
        const status = this.create(type, value);
        
        if (!status) {
            return this.createFallbackBadge(value);
        }

        return `<span class="badge bg-${status.color}">
            <i class="${status.icon} me-1"></i>${status.label}
        </span>`;
    }

    /**
     * Lấy label của status
     * @param {string} type - Loại status
     * @param {string} value - Giá trị status
     * @returns {string} Label
     */
    getLabel(type, value) {
        const status = this.create(type, value);
        return status ? status.label : (value || 'Không xác định');
    }

    /**
     * Lấy màu của status
     * @param {string} type - Loại status
     * @param {string} value - Giá trị status
     * @returns {string} Color class
     */
    getColor(type, value) {
        const status = this.create(type, value);
        return status ? status.color : 'secondary';
    }

    /**
     * Lấy icon của status
     * @param {string} type - Loại status
     * @param {string} value - Giá trị status
     * @returns {string} Icon class
     */
    getIcon(type, value) {
        const status = this.create(type, value);
        return status ? status.icon : 'fas fa-circle';
    }

    /**
     * Lấy tất cả options cho một loại status
     * @param {string} type - Loại status
     * @returns {Array} Array of options
     */
    getOptions(type) {
        if (!this.statusDefinitions[type]) {
            return [];
        }

        return Object.keys(this.statusDefinitions[type]).map(value => ({
            value: value,
            label: this.statusDefinitions[type][value].label,
            color: this.statusDefinitions[type][value].color,
            icon: this.statusDefinitions[type][value].icon
        }));
    }

    /**
     * Kiểm tra status có hợp lệ không
     * @param {string} type - Loại status
     * @param {string} value - Giá trị status
     * @returns {boolean}
     */
    isValid(type, value) {
        return this.create(type, value) !== null;
    }

    /**
     * Lấy tất cả status types có sẵn
     * @returns {Array} Array of types
     */
    getAvailableTypes() {
        return Object.keys(this.statusDefinitions);
    }

    /**
     * Tạo select options HTML
     * @param {string} type - Loại status
     * @param {string} selectedValue - Giá trị được chọn
     * @returns {string} HTML options
     */
    createSelectOptions(type, selectedValue = null) {
        const options = this.getOptions(type);
        
        return options.map(option => {
            const selected = option.value === selectedValue ? 'selected' : '';
            return `<option value="${option.value}" ${selected}>${option.label}</option>`;
        }).join('');
    }

    /**
     * Tạo radio buttons HTML
     * @param {string} type - Loại status
     * @param {string} name - Name attribute
     * @param {string} selectedValue - Giá trị được chọn
     * @returns {string} HTML radio buttons
     */
    createRadioButtons(type, name, selectedValue = null) {
        const options = this.getOptions(type);
        
        return options.map(option => {
            const checked = option.value === selectedValue ? 'checked' : '';
            const id = `${name}_${option.value}`;
            
            return `
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="${name}" 
                           id="${id}" value="${option.value}" ${checked}>
                    <label class="form-check-label" for="${id}">
                        <i class="${option.icon} me-1"></i>${option.label}
                    </label>
                </div>
            `;
        }).join('');
    }

    /**
     * Tạo status filter buttons
     * @param {string} type - Loại status
     * @param {string} activeValue - Giá trị active
     * @returns {string} HTML filter buttons
     */
    createFilterButtons(type, activeValue = null) {
        const options = this.getOptions(type);
        
        let html = `<button type="button" class="btn btn-outline-secondary ${!activeValue ? 'active' : ''}" 
                           data-filter="all">Tất cả</button>`;
        
        html += options.map(option => {
            const active = option.value === activeValue ? 'active' : '';
            return `
                <button type="button" class="btn btn-outline-${option.color.split(' ')[0]} ${active}" 
                        data-filter="${option.value}">
                    <i class="${option.icon} me-1"></i>${option.label}
                </button>
            `;
        }).join('');
        
        return html;
    }

    /**
     * Đăng ký loại status mới
     * @param {string} type - Loại status
     * @param {Object} definitions - Định nghĩa status
     */
    registerStatusType(type, definitions) {
        this.statusDefinitions[type] = definitions;
    }

    /**
     * Cập nhật định nghĩa status
     * @param {string} type - Loại status
     * @param {string} value - Giá trị status
     * @param {Object} definition - Định nghĩa mới
     */
    updateStatusDefinition(type, value, definition) {
        if (this.statusDefinitions[type]) {
            this.statusDefinitions[type][value] = definition;
        }
    }

    /**
     * Tạo fallback badge
     * @param {string} value - Giá trị
     * @returns {string} HTML badge
     */
    createFallbackBadge(value) {
        const displayValue = value || 'Không xác định';
        return `<span class="badge bg-secondary">${this.escapeHtml(displayValue)}</span>`;
    }

    /**
     * Escape HTML
     * @param {string} text - Text cần escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Load status definitions từ server
     * @param {string} endpoint - API endpoint
     * @returns {Promise}
     */
    async loadFromServer(endpoint = '/api/status-definitions') {
        try {
            const response = await fetch(endpoint);
            const data = await response.json();
            
            if (data.success) {
                this.statusDefinitions = { ...this.statusDefinitions, ...data.definitions };
            }
        } catch (error) {
            console.error('Failed to load status definitions:', error);
        }
    }
}

// Export instance
window.StatusFactory = new StatusFactory();
