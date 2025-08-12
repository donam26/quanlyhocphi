/**
 * BaseComponent - Base class cho tất cả components
 * Tuân thủ Open/Closed Principle và Template Method Pattern
 */
class BaseComponent {
    constructor(element, options = {}) {
        this.element = typeof element === 'string' ? document.querySelector(element) : element;
        this.options = { ...this.getDefaultOptions(), ...options };
        this.isInitialized = false;
        this.eventListeners = [];
        
        if (this.element) {
            this.init();
        }
    }

    /**
     * Template method - Khởi tạo component
     */
    init() {
        if (this.isInitialized) {
            return;
        }

        this.beforeInit();
        this.initializeElements();
        this.bindEvents();
        this.afterInit();
        
        this.isInitialized = true;
    }

    /**
     * Hook: Trước khi khởi tạo
     */
    beforeInit() {
        // Override trong subclass nếu cần
    }

    /**
     * Hook: Khởi tạo các elements
     */
    initializeElements() {
        // Override trong subclass
    }

    /**
     * Hook: Bind events
     */
    bindEvents() {
        // Override trong subclass
    }

    /**
     * Hook: Sau khi khởi tạo
     */
    afterInit() {
        // Override trong subclass nếu cần
    }

    /**
     * Lấy default options
     */
    getDefaultOptions() {
        return {};
    }

    /**
     * Thêm event listener và track để có thể remove sau
     */
    addEventListener(element, event, handler, options = {}) {
        const targetElement = typeof element === 'string' ? document.querySelector(element) : element;
        
        if (targetElement) {
            targetElement.addEventListener(event, handler, options);
            this.eventListeners.push({ element: targetElement, event, handler, options });
        }
    }

    /**
     * Thêm delegated event listener
     */
    addDelegatedEventListener(selector, event, handler) {
        const delegatedHandler = (e) => {
            const target = e.target.closest(selector);
            if (target && this.element.contains(target)) {
                handler.call(target, e);
            }
        };

        this.addEventListener(this.element, event, delegatedHandler);
    }

    /**
     * Emit custom event
     */
    emit(eventName, detail = {}) {
        const event = new CustomEvent(eventName, {
            detail,
            bubbles: true,
            cancelable: true
        });
        
        this.element.dispatchEvent(event);
    }

    /**
     * Listen to custom event
     */
    on(eventName, handler) {
        this.addEventListener(this.element, eventName, handler);
    }

    /**
     * Show element
     */
    show() {
        if (this.element) {
            this.element.style.display = '';
            this.element.classList.remove('d-none');
            this.emit('component:show');
        }
    }

    /**
     * Hide element
     */
    hide() {
        if (this.element) {
            this.element.classList.add('d-none');
            this.emit('component:hide');
        }
    }

    /**
     * Toggle visibility
     */
    toggle() {
        if (this.isVisible()) {
            this.hide();
        } else {
            this.show();
        }
    }

    /**
     * Check if element is visible
     */
    isVisible() {
        return this.element && !this.element.classList.contains('d-none');
    }

    /**
     * Enable component
     */
    enable() {
        if (this.element) {
            this.element.classList.remove('disabled');
            this.element.removeAttribute('disabled');
            this.emit('component:enable');
        }
    }

    /**
     * Disable component
     */
    disable() {
        if (this.element) {
            this.element.classList.add('disabled');
            this.element.setAttribute('disabled', 'disabled');
            this.emit('component:disable');
        }
    }

    /**
     * Check if component is enabled
     */
    isEnabled() {
        return this.element && !this.element.classList.contains('disabled');
    }

    /**
     * Set loading state
     */
    setLoading(loading = true) {
        if (loading) {
            this.element.classList.add('loading');
            this.disable();
        } else {
            this.element.classList.remove('loading');
            this.enable();
        }
        
        this.emit('component:loading', { loading });
    }

    /**
     * Update options
     */
    updateOptions(newOptions) {
        this.options = { ...this.options, ...newOptions };
        this.emit('component:options-updated', { options: this.options });
    }

    /**
     * Get option value
     */
    getOption(key, defaultValue = null) {
        return this.options[key] !== undefined ? this.options[key] : defaultValue;
    }

    /**
     * Set option value
     */
    setOption(key, value) {
        this.options[key] = value;
        this.emit('component:option-changed', { key, value });
    }

    /**
     * Find element within component
     */
    find(selector) {
        return this.element ? this.element.querySelector(selector) : null;
    }

    /**
     * Find all elements within component
     */
    findAll(selector) {
        return this.element ? this.element.querySelectorAll(selector) : [];
    }

    /**
     * Validate component state
     */
    validate() {
        // Override trong subclass
        return true;
    }

    /**
     * Reset component to initial state
     */
    reset() {
        // Override trong subclass
        this.emit('component:reset');
    }

    /**
     * Refresh/reload component
     */
    refresh() {
        // Override trong subclass
        this.emit('component:refresh');
    }

    /**
     * Destroy component và cleanup
     */
    destroy() {
        this.beforeDestroy();
        
        // Remove all event listeners
        this.eventListeners.forEach(({ element, event, handler, options }) => {
            element.removeEventListener(event, handler, options);
        });
        this.eventListeners = [];
        
        // Remove from DOM if needed
        this.afterDestroy();
        
        this.isInitialized = false;
        this.emit('component:destroy');
    }

    /**
     * Hook: Trước khi destroy
     */
    beforeDestroy() {
        // Override trong subclass nếu cần
    }

    /**
     * Hook: Sau khi destroy
     */
    afterDestroy() {
        // Override trong subclass nếu cần
    }

    /**
     * Static method để tạo instance từ selector
     */
    static create(selector, options = {}) {
        const elements = document.querySelectorAll(selector);
        const instances = [];
        
        elements.forEach(element => {
            instances.push(new this(element, options));
        });
        
        return instances.length === 1 ? instances[0] : instances;
    }

    /**
     * Static method để auto-init từ data attributes
     */
    static autoInit(selector = '[data-component]') {
        const elements = document.querySelectorAll(selector);
        
        elements.forEach(element => {
            const componentName = element.dataset.component;
            const ComponentClass = window[componentName];
            
            if (ComponentClass && typeof ComponentClass === 'function') {
                new ComponentClass(element);
            }
        });
    }
}

// Export
window.BaseComponent = BaseComponent;
