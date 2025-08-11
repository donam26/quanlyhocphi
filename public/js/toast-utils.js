/**
 * Toast Utilities
 * Thống nhất function showToast cho toàn bộ ứng dụng
 */

// Global toast function
window.showToast = function(message, type = 'success') {
    // Ensure we have a toast container
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '1055';
        document.body.appendChild(toastContainer);
    }
    
    // Map type to Bootstrap classes
    const typeClasses = {
        'success': 'bg-success',
        'error': 'bg-danger',
        'danger': 'bg-danger',
        'warning': 'bg-warning',
        'info': 'bg-info',
        'primary': 'bg-primary',
        'secondary': 'bg-secondary'
    };
    
    const bgClass = typeClasses[type] || 'bg-primary';
    const textClass = ['warning'].includes(type) ? 'text-dark' : 'text-white';
    
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center ${bgClass} ${textClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${getIconByType(type)} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close ${textClass === 'text-white' ? 'btn-close-white' : ''} me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    // Add to container
    toastContainer.insertAdjacentHTML('beforeend', toastHTML);
    
    // Initialize and show toast
    const toastElement = document.getElementById(toastId);
    if (toastElement && window.bootstrap) {
        const toast = new bootstrap.Toast(toastElement, {
            autohide: true,
            delay: 5000
        });
        toast.show();
        
        // Clean up after hiding
        toastElement.addEventListener('hidden.bs.toast', function() {
            toastElement.remove();
        });
    }
    
    // Fallback for debugging
    console.log(`Toast ${type}: ${message}`);
};

// Helper function to get icon by type
function getIconByType(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-triangle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle',
        'primary': 'info-circle',
        'secondary': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Alias cho backward compatibility
window.toast = window.showToast;

// Export for modules if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { showToast: window.showToast };
}
