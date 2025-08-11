/**
 * Date Utilities - Hàm hỗ trợ xử lý ngày tháng với format dd/mm/yyyy
 */

/**
 * Format date từ string hoặc Date object sang dd/mm/yyyy
 * @param {string|Date} dateValue 
 * @returns {string}
 */
function formatDate(dateValue) {
    if (!dateValue) return '';
    
    let date;
    if (typeof dateValue === 'string') {
        // Nếu đã là định dạng dd/mm/yyyy, trả về luôn
        if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateValue)) {
            return dateValue;
        }
        date = new Date(dateValue);
    } else {
        date = dateValue;
    }
    
    if (isNaN(date.getTime())) return '';
    
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    
    return `${day}/${month}/${year}`;
}

/**
 * Format datetime từ string hoặc Date object sang dd/mm/yyyy HH:mm
 * @param {string|Date} dateValue 
 * @returns {string}
 */
function formatDateTime(dateValue) {
    if (!dateValue) return '';
    
    let date;
    if (typeof dateValue === 'string') {
        date = new Date(dateValue);
    } else {
        date = dateValue;
    }
    
    if (isNaN(date.getTime())) return '';
    
    const day = date.getDate().toString().padStart(2, '0');
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const year = date.getFullYear();
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

/**
 * Parse date từ dd/mm/yyyy sang Date object
 * @param {string} dateString 
 * @returns {Date|null}
 */
function parseDate(dateString) {
    if (!dateString) return null;
    
    // Nếu đã là định dạng ISO hoặc các format khác, parse trực tiếp
    if (!/^\d{2}\/\d{2}\/\d{4}$/.test(dateString)) {
        const date = new Date(dateString);
        return isNaN(date.getTime()) ? null : date;
    }
    
    const parts = dateString.split('/');
    if (parts.length !== 3) return null;
    
    const day = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1; // Month is 0-indexed
    const year = parseInt(parts[2], 10);
    
    const date = new Date(year, month, day);
    
    // Validate the date
    if (date.getFullYear() !== year || 
        date.getMonth() !== month || 
        date.getDate() !== day) {
        return null;
    }
    
    return date;
}

/**
 * Chuyển đổi date từ dd/mm/yyyy sang yyyy-mm-dd (cho input type="date")
 * @param {string} dateString 
 * @returns {string}
 */
function dateToInputValue(dateString) {
    if (!dateString) return '';
    
    const date = parseDate(dateString);
    if (!date) return '';
    
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    
    return `${year}-${month}-${day}`;
}

/**
 * Chuyển đổi từ input date value (yyyy-mm-dd) sang dd/mm/yyyy
 * @param {string} inputValue 
 * @returns {string}
 */
function inputValueToDate(inputValue) {
    if (!inputValue) return '';
    
    const date = new Date(inputValue);
    if (isNaN(date.getTime())) return '';
    
    return formatDate(date);
}

/**
 * Get current date in dd/mm/yyyy format
 * @returns {string}
 */
function getCurrentDate() {
    return formatDate(new Date());
}

/**
 * Get current datetime in dd/mm/yyyy HH:mm format
 * @returns {string}
 */
function getCurrentDateTime() {
    return formatDateTime(new Date());
}

// Make functions available globally
window.formatDate = formatDate;
window.formatDateTime = formatDateTime;
window.parseDate = parseDate;
window.dateToInputValue = dateToInputValue;
window.inputValueToDate = inputValueToDate;
window.getCurrentDate = getCurrentDate;
window.getCurrentDateTime = getCurrentDateTime;
