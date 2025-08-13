/**
 * JavaScript helper cho xử lý khóa học đặc biệt
 */

// Các trường mặc định cho khóa học đặc biệt
const DEFAULT_SPECIAL_FIELDS = [
    'Đơn vị công tác',
    'Bằng cấp', 
    'Chuyên môn công tác',
    'Số năm kinh nghiệm',
    'Hồ sơ bản cứng'
];

/**
 * Tự động thêm các trường mặc định khi đánh dấu khóa học là đặc biệt
 */
function handleSpecialCourseToggle(isSpecialCheckbox, customFieldsContainer, customFieldsList, addFieldButton) {
    if (isSpecialCheckbox.is(':checked')) {
        // Hiển thị container custom fields
        customFieldsContainer.show();
        
        // Xóa các trường hiện tại
        customFieldsList.empty();
        
        // Thêm các trường mặc định
        DEFAULT_SPECIAL_FIELDS.forEach(function(fieldName) {
            addDefaultField(customFieldsList, fieldName);
        });
        
        // Hiển thị nút thêm trường
        addFieldButton.show();
        
        // Thông báo
        showAlert('info', 'Đã tự động thêm các trường thông tin mặc định cho khóa học đặc biệt.');
    } else {
        // Ẩn container và xóa các trường
        customFieldsContainer.hide();
        customFieldsList.empty();
    }
}

/**
 * Thêm trường mặc định vào danh sách
 */
function addDefaultField(container, fieldName) {
    const fieldId = Date.now() + Math.floor(Math.random() * 1000);
    const fieldHtml = `
        <div class="custom-field-row mb-3" data-field-id="${fieldId}">
            <div class="row g-2">
                <div class="col-10">
                    <input type="text" class="form-control form-control-sm field-key" 
                        placeholder="Tên trường" name="custom_field_keys[]" value="${fieldName}" readonly>
                    <small class="text-muted">Trường mặc định cho khóa học đặc biệt</small>
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                        <i class="fas fa-lock"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.append(fieldHtml);
}

/**
 * Thêm trường tùy chỉnh (không phải mặc định)
 */
function addCustomField(container) {
    const fieldId = Date.now() + Math.floor(Math.random() * 1000);
    const fieldHtml = `
        <div class="custom-field-row mb-3" data-field-id="${fieldId}">
            <div class="row g-2">
                <div class="col-10">
                    <input type="text" class="form-control form-control-sm field-key" 
                        placeholder="Tên trường tùy chỉnh" name="custom_field_keys[]" value="">
                    <small class="text-muted">Trường tùy chỉnh bổ sung</small>
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-field">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    container.append(fieldHtml);
}

/**
 * Hiển thị thông tin custom fields trong modal chi tiết học viên
 */
function displayCustomFields(customFields, container) {
    if (!customFields || Object.keys(customFields).length === 0) {
        container.hide();
        return;
    }
    
    let html = '<div class="row">';
    let count = 0;
    
    Object.keys(customFields).forEach(function(key) {
        const value = customFields[key] || '-';
        const isDefaultField = DEFAULT_SPECIAL_FIELDS.includes(key);
        const badgeClass = isDefaultField ? 'bg-primary' : 'bg-secondary';
        
        html += `
            <div class="col-md-6 mb-2">
                <div class="d-flex justify-content-between align-items-center">
                    <strong>${key}:</strong>
                    <span class="badge ${badgeClass} ms-2">${isDefaultField ? 'Mặc định' : 'Tùy chỉnh'}</span>
                </div>
                <div class="text-muted">${value}</div>
            </div>
        `;
        count++;
        
        // Xuống dòng sau mỗi 2 trường
        if (count % 2 === 0) {
            html += '</div><div class="row">';
        }
    });
    
    html += '</div>';
    
    container.html(html).show();
}

/**
 * Kiểm tra xem khóa học có phải là đặc biệt không
 */
function isCourseSpecial(courseData) {
    return courseData && courseData.is_special === true;
}

/**
 * Lấy danh sách các trường custom fields từ form
 */
function getCustomFieldsFromForm(container) {
    const fields = {};
    
    container.find('.custom-field-row').each(function() {
        const key = $(this).find('.field-key').val().trim();
        if (key) {
            fields[key] = ''; // Giá trị mặc định rỗng
        }
    });
    
    return fields;
}

/**
 * Validate custom fields
 */
function validateCustomFields(container) {
    const errors = [];
    const usedKeys = [];
    
    container.find('.custom-field-row').each(function() {
        const key = $(this).find('.field-key').val().trim();
        
        if (!key) {
            errors.push('Tên trường không được để trống');
            return;
        }
        
        if (usedKeys.includes(key)) {
            errors.push(`Trường "${key}" bị trùng lặp`);
            return;
        }
        
        usedKeys.push(key);
    });
    
    return errors;
}

// Export functions for global use
window.SpecialCourse = {
    handleToggle: handleSpecialCourseToggle,
    addDefaultField: addDefaultField,
    addCustomField: addCustomField,
    displayCustomFields: displayCustomFields,
    isCourseSpecial: isCourseSpecial,
    getCustomFieldsFromForm: getCustomFieldsFromForm,
    validateCustomFields: validateCustomFields,
    DEFAULT_FIELDS: DEFAULT_SPECIAL_FIELDS
};
