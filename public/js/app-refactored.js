/**
 * App Refactored - Main application file sau khi refactor
 * Load tất cả services và components theo thứ tự đúng
 */

// Load order: Services trước, Components sau
const LOAD_ORDER = [
    // Services
    '/js/services/StatusFactory.js',
    '/js/services/FormHandler.js',
    '/js/services/ModalManager.js',
    '/js/services/Select2Manager.js',

    // Base Components
    '/js/components/BaseComponent.js',

    // Specific Components
    '/js/components/FormComponent.js',
    '/js/components/ModalComponent.js',
    '/js/components/AttendanceTreeComponent.js',

    // Unified System
    '/js/components/ViewGenerators.js',
    '/js/components/UnifiedModalSystem.js'
];

/**
 * Dynamic script loader
 */
class ScriptLoader {
    constructor() {
        this.loadedScripts = new Set();
        this.loadingPromises = new Map();
    }

    /**
     * Load single script
     */
    async loadScript(src) {
        // Nếu đã load rồi thì return
        if (this.loadedScripts.has(src)) {
            return Promise.resolve();
        }

        // Nếu đang load thì return promise hiện tại
        if (this.loadingPromises.has(src)) {
            return this.loadingPromises.get(src);
        }

        // Tạo promise mới để load script
        const promise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.async = false; // Đảm bảo load theo thứ tự
            
            script.onload = () => {
                this.loadedScripts.add(src);
                this.loadingPromises.delete(src);
                resolve();
            };
            
            script.onerror = () => {
                this.loadingPromises.delete(src);
                reject(new Error(`Failed to load script: ${src}`));
            };
            
            document.head.appendChild(script);
        });

        this.loadingPromises.set(src, promise);
        return promise;
    }

    /**
     * Load multiple scripts in order
     */
    async loadScripts(scripts) {
        for (const script of scripts) {
            await this.loadScript(script);
        }
    }

    /**
     * Load scripts in parallel
     */
    async loadScriptsParallel(scripts) {
        const promises = scripts.map(script => this.loadScript(script));
        await Promise.all(promises);
    }
}

/**
 * Application class
 */
class App {
    constructor() {
        this.scriptLoader = new ScriptLoader();
        this.isInitialized = false;
        this.components = new Map();
        this.services = new Map();
    }

    /**
     * Initialize application
     */
    async init() {
        if (this.isInitialized) {
            return;
        }

        try {
            console.log('🚀 Initializing refactored application...');
            
            // Load all scripts
            await this.loadScripts();
            
            // Initialize services
            this.initializeServices();
            
            // Initialize components
            this.initializeComponents();
            
            // Setup global event handlers
            this.setupGlobalEvents();
            
            this.isInitialized = true;
            console.log('✅ Application initialized successfully');
            
            // Emit ready event
            document.dispatchEvent(new CustomEvent('app:ready'));
            
        } catch (error) {
            console.error('❌ Failed to initialize application:', error);
            throw error;
        }
    }

    /**
     * Load all required scripts
     */
    async loadScripts() {
        console.log('📦 Loading scripts...');
        await this.scriptLoader.loadScripts(LOAD_ORDER);
        console.log('✅ All scripts loaded');
    }

    /**
     * Initialize services
     */
    initializeServices() {
        console.log('🔧 Initializing services...');
        
        // Services đã được khởi tạo tự động khi load script
        // Chỉ cần register vào app
        if (window.StatusFactory) {
            this.services.set('statusFactory', window.StatusFactory);
        }
        
        if (window.FormHandler) {
            this.services.set('formHandler', window.FormHandler);
        }
        
        if (window.ModalManager) {
            this.services.set('modalManager', window.ModalManager);
        }
        
        console.log('✅ Services initialized');
    }

    /**
     * Initialize components
     */
    initializeComponents() {
        console.log('🧩 Initializing components...');
        
        // Auto-initialize components từ data attributes
        if (window.BaseComponent) {
            window.BaseComponent.autoInit();
        }
        
        // Initialize specific components
        this.initializeFormComponents();
        this.initializeModalComponents();
        
        console.log('✅ Components initialized');
    }

    /**
     * Initialize form components
     */
    initializeFormComponents() {
        const forms = document.querySelectorAll('form[data-auto-submit="true"]');
        forms.forEach(form => {
            const component = new window.FormComponent(form);
            this.components.set(form.id || 'form-' + Date.now(), component);
        });
    }

    /**
     * Initialize modal components
     */
    initializeModalComponents() {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            const component = new window.ModalComponent(modal);
            this.components.set(modal.id, component);
        });
    }

    /**
     * Setup global event handlers
     */
    setupGlobalEvents() {
        // Global error handler
        window.addEventListener('error', (event) => {
            console.error('Global error:', event.error);
        });

        // Global unhandled promise rejection handler
        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection:', event.reason);
        });

        // CSRF token setup for AJAX requests
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            // Setup default headers for fetch
            const originalFetch = window.fetch;
            window.fetch = function(url, options = {}) {
                options.headers = {
                    'X-CSRF-TOKEN': csrfToken.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers
                };
                return originalFetch(url, options);
            };
        }
    }

    /**
     * Get service by name
     */
    getService(name) {
        return this.services.get(name);
    }

    /**
     * Get component by id
     */
    getComponent(id) {
        return this.components.get(id);
    }

    /**
     * Register new component
     */
    registerComponent(id, component) {
        this.components.set(id, component);
    }

    /**
     * Register new service
     */
    registerService(name, service) {
        this.services.set(name, service);
    }

    /**
     * Destroy application
     */
    destroy() {
        // Destroy all components
        this.components.forEach(component => {
            if (component.destroy) {
                component.destroy();
            }
        });
        
        this.components.clear();
        this.services.clear();
        this.isInitialized = false;
        
        console.log('🗑️ Application destroyed');
    }
}

// Create global app instance
window.App = new App();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.App.init().catch(console.error);
    });
} else {
    // DOM already loaded
    window.App.init().catch(console.error);
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = App;
}
