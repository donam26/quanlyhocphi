// Biến lưu ID khóa học đang xem chi tiết
let currentCourseId = null;

// Hàm lấy root_id hiện tại từ tab đang active
function getCurrentRootId() {
    // Ưu tiên lấy từ tab đang active
    const activeTab = $('.course-tabs .nav-link.active');
    if (activeTab.length) {
        return activeTab.attr('id').replace('tab-', '');
    }
    
    // Fallback: lấy từ URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const rootIdFromUrl = urlParams.get('root_id');
    if (rootIdFromUrl) {
        return rootIdFromUrl;
    }
    
    // Fallback cuối: lấy tab đầu tiên
    const firstTab = $('.course-tabs .nav-link').first();
    if (firstTab.length) {
        return firstTab.attr('id').replace('tab-', '');
    }
    
    return null;
}

// ========== Fallback: điều hướng đến khóa trong cây nếu chưa có hàm ==========
if (typeof window.navigateToCourse !== 'function') {
    window.navigateToCourse = function(courseId) {
        // Tìm node trong DOM (trong bất kỳ tab nào)
        let $node = $(`.tree-item[data-id="${courseId}"]`).first();
        if (!$node.length) {
            // Không tìm thấy → chỉ mở chi tiết như một fallback tối thiểu
            return window.showCourseDetails ? window.showCourseDetails(courseId) : null;
        }
        // Chuyển sang tab chứa node
        const $pane = $node.closest('.tab-pane');
        if ($pane.length) {
            const rootId = $pane.data('root-id');
            const $tab = $(`#tab-${rootId}`);
            if ($tab.length) {
                new bootstrap.Tab($tab[0]).show();
            }
        }
        // Mở tất cả collapse tổ tiên để hiện node
        $node.parents('.collapse').each(function(){
            try { $(this).collapse('show'); } catch(e) {}
        });
        // Cuộn tới vị trí node
        if ($node[0]) {
            $node[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        // Mở modal chi tiết
        if (typeof window.showCourseDetails === 'function') {
            window.showCourseDetails(courseId);
        }
    };
}

// Đặt hàm showCourseDetails vào đối tượng window để có thể gọi từ mọi nơi
window.showCourseDetails = function(courseId) {
    console.log("showCourseDetails được gọi với ID:", courseId);
    currentCourseId = courseId;
    
    // Kiểm tra modal có tồn tại không
    if ($('#viewCourseModal').length === 0) {
        console.error("Không tìm thấy modal #viewCourseModal");
        alert("Lỗi: Không tìm thấy modal để hiển thị thông tin khóa học");
        return;
    }
    
    // Hiển thị modal
    try {
        $('#viewCourseModal').modal('show');
        console.log("Modal đã được gọi để hiển thị");
    } catch (error) {
        console.error("Lỗi khi hiển thị modal:", error);
        alert("Có lỗi khi hiển thị thông tin khóa học");
        return;
    }
    
    // Hiển thị loading và ẩn nội dung
    $('#course-loading').show();
    $('#course-details').hide();
    
    // Lấy thông tin khóa học qua AJAX
    console.log("Gửi AJAX request đến:", `/api/course-items/${courseId}`);
    $.ajax({
        url: `/api/course-items/${courseId}`,
        method: 'GET',
        success: function(data) {
            console.log("Nhận dữ liệu thành công:", data);
            
            // Cập nhật các nút hành động
            // Chuyển sang hành vi mở modal thay vì điều hướng sang trang khác
            $('#btn-students').off('click').on('click', function(e){
                e.preventDefault();
                openStudentsModal(courseId);
            });
            $('#btn-attendance').off('click').on('click', function(e){
                e.preventDefault();
                openAttendanceModal(courseId);
            });
            $('#btn-payments').off('click').on('click', function(e){
                e.preventDefault();
                openPaymentsModal(courseId);
            });
            
            // Cập nhật thông tin cơ bản
            $('#course-name').text(data.name);
            $('#course-id').text(data.id);
            $('#course-status').html(data.active 
                ? '<span class="badge bg-success">Hoạt động</span>' 
                : '<span class="badge bg-danger">Không hoạt động</span>');
            $('#course-special').html(data.is_special 
                ? '<span class="badge bg-warning">Có</span>' 
                : '<span class="badge bg-secondary">Không</span>');
            
            // Hiển thị loại khóa học
            const courseType = data.is_leaf 
                ? '<span class="badge bg-info">Khóa học cuối</span>' 
                : '<span class="badge bg-secondary">Khóa học cha</span>';
            $('#course-type').html(courseType);
            
            // Hiển thị học phí nếu có
            if (data.fee > 0) {
                $('#course-fee').text(formatCurrency(data.fee) + ' đ').show();
            } else {
                $('#course-fee').hide();
            }
            
            // Hiển thị đường dẫn
            if (data.path) {
                $('#course-path').text(data.path).show();
            } else {
                $('#course-path').hide();
            }
            
            // Hiển thị số lượng học viên và doanh thu
            $('#enrollment-count').text(data.enrollment_count || 0);
            $('#total-revenue').text(formatCurrency(data.total_revenue || 0) + ' đ');
            
            // Xử lý các trường thông tin tùy chỉnh
            if (data.is_special && data.custom_fields && Object.keys(data.custom_fields).length > 0) {
                let customFieldsHtml = '<table class="table table-sm">';
                Object.keys(data.custom_fields).forEach(key => {
                    customFieldsHtml += `<tr>
                        <th width="40%">${key}:</th>
                        <td>${data.custom_fields[key] || '-'}</td>
                    </tr>`;
                });
                customFieldsHtml += '</table>';
                
                $('#course-custom-fields').html(customFieldsHtml);
                $('#course-custom-fields-card').show();
            } else {
                $('#course-custom-fields-card').hide();
            }
            
            // Hiển thị lộ trình học tập nếu có
            if (data.learning_paths && data.learning_paths.length > 0) {
                let pathsHtml = '';
                data.learning_paths.forEach((path, index) => {
                    pathsHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><strong>${index + 1}. ${path.title}</strong>
                            ${path.description ? '<br><small class="text-muted">' + path.description + '</small>' : ''}
                        </span>
                    </li>`;
                });
                
                $('#learning-paths-list').html(pathsHtml);
                $('#learning-paths-section').show();
            } else {
                $('#learning-paths-section').hide();
            }
            
            // Ẩn loading và hiển thị nội dung
            $('#course-loading').hide();
            $('#course-details').show();
        },
        error: function(xhr, status, error) {
            console.error("Lỗi AJAX:", error);
            console.error("Status:", status);
            console.error("Response:", xhr.responseText);
            
            $('#course-loading').hide();
            $('#course-details').html(`<div class="alert alert-danger">
                Có lỗi xảy ra khi tải thông tin khóa học: ${error}<br>
                Status: ${xhr.status} - ${xhr.statusText}
            </div>`).show();
        }
    });
};

$(function() {
    // Kích hoạt tab đúng dựa trên server state
    const activeTab = document.querySelector('#courseTab .nav-link.active');
    if (activeTab) {
        new bootstrap.Tab(activeTab).show();
    } else {
        // Fallback: kích hoạt tab đầu tiên nếu không có tab nào active
        const firstTab = document.querySelector('#courseTab .nav-link');
        if (firstTab) {
            new bootstrap.Tab(firstTab).show();
        }
    }

    // Xử lý sự kiện click cho các liên kết khóa học
    $(document).on('click', '.course-link', function(e) {
        e.preventDefault();
        const courseId = $(this).data('id');
        console.log("Đã click vào khóa học có ID:", courseId);
        showCourseDetails(courseId);
    });
    
    // Nút chỉnh sửa trong modal chi tiết
    $(document).on('click', '#btn-edit-from-modal', function() {
        $('#viewCourseModal').modal('hide');
        setupEditModal(currentCourseId);
    });

    // Delegated handlers for new modal triggers
    $(document).on('click', '.open-students-modal', function(){
        const courseId = $(this).data('course-id');
        openStudentsModal(courseId);
    });
    $(document).on('click', '.open-attendance-modal', function(){
        const courseId = $(this).data('course-id');
        openAttendanceModal(courseId);
    });
    $(document).on('click', '.open-add-child', function(){
        const parentId = $(this).data('parent-id');
        const parentName = $(this).data('parent-name');
        setupAddModal(parentId, parentName);
    });
    $(document).on('click', '.open-delete-item', function(){
        const id = $(this).data('id');
        const name = $(this).data('name');
        confirmDelete(id, name);
    });

    // Khi mở modal thêm ngành mới
    $('[data-bs-target="#addRootItemModal"]').on('click', function() {
        const currentRootId = getCurrentRootId();
        $('#root-current-root-id-input').val(currentRootId);
    });

    // Cập nhật URL khi chuyển tab
    $('.course-tabs .nav-link').on('shown.bs.tab', function () {
        const rootId = $(this).attr('id').replace('tab-', '');
        const url = new URL(window.location);
        url.searchParams.set('root_id', rootId);
        window.history.pushState({}, '', url);
    });

    // Mở rộng tất cả
    $('#expand-all').click(function() {
        $('.course-tree .collapse').collapse('show');
        $('.toggle-icon').each(function() {
            if($(this).closest('.tree-item').siblings('.collapse').length > 0) {
                $(this).find('i').removeClass('fa-plus-circle').addClass('fa-minus-circle');
            }
        });
    });

    // Thu gọn tất cả
    $('#collapse-all').click(function() {
        $('.course-tree .collapse').collapse('hide');
        $('.toggle-icon i').removeClass('fa-minus-circle').addClass('fa-plus-circle');
    });

    // Toggle icon +/- theo collapse sự kiện
    $(document).on('show.bs.collapse', '.course-tree .collapse', function () {
        $(this).closest('li').find('> .tree-item .toggle-icon i')
            .removeClass('fa-plus-circle').addClass('fa-minus-circle');
    });
    $(document).on('hide.bs.collapse', '.course-tree .collapse', function () {
        $(this).closest('li').find('> .tree-item .toggle-icon i')
            .removeClass('fa-minus-circle').addClass('fa-plus-circle');
    });
});

// Xác nhận xóa khóa học
function confirmDelete(id, name) {
    $('#delete-item-name').text(name);
    $('#delete-form').attr('action', `/course-items/${id}`);
    $('#deleteModal').modal('show');
}

// Thiết lập modal thêm khóa học
function setupAddModal(parentId, parentName) {
    const currentRootId = getCurrentRootId();
    
    // Xây options cho dropdown Khoá cha trong modal tạo mới
    let $pane;
    if (parentId) {
        const $parentItem = $(`div.tree-item[data-id="${parentId}"]`);
        $pane = $parentItem.length ? $parentItem.closest('.tab-pane') : $('.tab-pane.active');
    } else {
        $pane = $('.tab-pane.active');
    }
    buildAddParentSelectOptions($pane, parentId);
    $('#current-root-id-input').val(currentRootId);
    $('#addItemModal .modal-title').text('Thêm khóa học');
    $('#addItemModal').modal('show');
}

// Thiết lập modal chỉnh sửa khóa học
function setupEditModal(id) {
    // Ẩn modal xem chi tiết nếu đang mở
    if ($('#viewCourseModal').hasClass('show')) {
        $('#viewCourseModal').modal('hide');
    }
    
    const currentRootId = getCurrentRootId();
    
    const item = $(`div.tree-item[data-id="${id}"]`);
    if (!item.length) return;
    
    // Lấy thông tin khóa học
    const name = item.find('a').contents().first().text().trim();
    const isLeaf = item.hasClass('leaf');
    const isActive = item.hasClass('active');
    
    // Lấy học phí nếu có
    let fee = 0;
    const feeText = item.find('.badge.bg-success').text();
    if (feeText) {
        fee = parseInt(feeText.replace(/\D/g, ''));
    }
    
    // Tìm parent_id từ cấu trúc DOM
    let parentId = null;
    const parentItem = item.closest('li').parent('ul').closest('li').find('.tree-item').first();
    if (parentItem.length) {
        parentId = parentItem.data('id');
    }
    
    // Kiểm tra xem có phải là khóa chính không
    const isRoot = item.closest('.level-1').length > 0;
    
    // Điền thông tin vào form
    $('#edit-item-id').val(id);
    $('#edit-current-root-id').val(currentRootId);
    $('#edit-item-name').val(name);
    $('#edit-is-leaf').prop('checked', isLeaf);
    $('#edit-item-active').prop('checked', isActive);
    $('#edit-item-fee').val(fee);

    // Xây dựng danh sách chọn khoá cha dạng tree
    buildParentSelectOptions(item.closest('.tab-pane'), id, parentId);
    
    // Cập nhật action của form
    $('#edit-item-form').attr('action', `/course-items/${id}`);
    
    // Hiển thị/ẩn các tùy chọn cho nút lá
    if (isLeaf) {
        $('#edit-leaf-options').slideDown();
    } else {
        $('#edit-leaf-options').slideUp();
    }
    
    // Lấy thông tin về khóa học đặc biệt và các trường tùy chỉnh
    $.ajax({
        url: `/api/course-items/${id}`,
        method: "GET",
        success: function(response) {
            // Thiết lập checkbox khóa học đặc biệt
            $('#edit-is-special').prop('checked', response.is_special);
            
            // Xử lý các trường thông tin tùy chỉnh
            $('#custom-fields-list').empty();
            
            if (response.is_special && response.custom_fields) {
                $('#custom-fields-container').show();
                
                // Hiển thị các trường đã có
                $.each(response.custom_fields, function(key, value) {
                    const fieldId = Date.now() + Math.floor(Math.random() * 1000);
                    const fieldHtml = `
                        <div class="custom-field-row mb-3" data-field-id="${fieldId}">
                            <div class="row g-2">
                                <div class="col-10">
                                    <input type="text" class="form-control form-control-sm field-key" 
                                        placeholder="Tên trường" name="custom_field_keys[]" value="${key}">
                                </div>
                                <div class="col-2">
                                    <button type="button" class="btn btn-sm btn-outline-danger remove-field">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    $('#custom-fields-list').append(fieldHtml);
                });
            } else {
                $('#custom-fields-container').hide();
            }
        },
        error: function(error) {
            console.error('Không thể tải thông tin khóa học:', error);
        }
    });
    
    $('#editItemModal').modal('show');
}

// Helper: lấy danh sách ID con (mọi cấp) của một node
function getDescendantIdsOf(id){
    const ids = new Set();
    const $container = $(`#children-${id}`);
    if ($container.length){
        $container.find('.tree-item').each(function(){
            const cid = $(this).data('id');
            if (cid) ids.add(cid);
        });
    }
    return ids;
}

// Helper: xây options cho select cha, loại trừ chính nó và các con
function buildParentSelectOptions($pane, currentId, selectedParentId){
    const descendants = getDescendantIdsOf(currentId);
    let options = '<option value="">(Không có) — Đặt làm khoá chính</option>';

    // Duyệt theo thứ tự DOM để giữ thứ tự tự nhiên
    $pane.find('.tree-item').each(function(){
        const cid = $(this).data('id');
        if (!cid || cid === currentId) return;
        if (descendants.has(cid)) return; // không cho chọn con của chính nó
        const depth = $(this).closest('li').parents('ul').length; // root ~1
        const indent = Array(Math.max(0, depth - 1)).fill('— ').join('');
        const label = $(this).find('a').contents().first().text().trim();
        const sel = (String(cid) === String(selectedParentId)) ? 'selected' : '';
        options += `<option value="${cid}" ${sel}>${indent}${label}</option>`;
    });

    $('#edit-parent-select').html(options).prop('disabled', false);
}

// Helper: xây options cho select cha trong modal tạo mới
function buildAddParentSelectOptions($pane, selectedParentId){
    let options = '<option value="">(Không có) — Đặt làm khoá chính</option>';
    $pane.find('.tree-item').each(function(){
        const cid = $(this).data('id');
        if (!cid) return;
        const depth = $(this).closest('li').parents('ul').length; // root ~1
        const indent = Array(Math.max(0, depth - 1)).fill('— ').join('');
        const label = $(this).find('a').contents().first().text().trim();
        const sel = (String(cid) === String(selectedParentId)) ? 'selected' : '';
        options += `<option value="${cid}" ${sel}>${indent}${label}</option>`;
    });
    $('#add-parent-select').html(options);
}

// Hiển thị thông báo toast
function showToast(message, type = 'success') {
    const toastHTML = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    const toastContainer = $('.toast-container');
    if (!toastContainer.length) {
        $('body').append('<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
    }
    
    const toast = $(toastHTML).appendTo('.toast-container');
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Tự động xóa toast sau 5 giây
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

// Hàm cập nhật thứ tự qua AJAX
function updateItemsOrder(items, newParentId = null) {
    $.ajax({
        url: '/course-items/update-order',
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            items: items,
            parent_id: newParentId
        },
        success: function(response) {
            if (response.success) {
                showToast('Đã cập nhật thứ tự hiển thị', 'success');
                
                // Reload trang sau 1 giây để cập nhật dữ liệu
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                showToast('Có lỗi xảy ra khi cập nhật thứ tự', 'danger');
            }
        },
        error: function() {
            showToast('Có lỗi xảy ra khi cập nhật thứ tự', 'danger');
        }
    });
}

// Định dạng số tiền
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount);
}

// Khởi tạo Select2 cho tìm kiếm khóa học
$(document).ready(function() {
    // Khởi tạo Select2 cho tìm kiếm khóa học
    $('#course-search-select').select2({
        placeholder: 'Nhập tên khóa học để tìm kiếm...',
        minimumInputLength: 2,
        allowClear: true,
        theme: 'bootstrap-5',
        ajax: {
            url: '/api/course-items/search',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                // Lấy root_id từ tab đang active
                const activeTabPane = $('.tab-pane.active');
                const rootId = activeTabPane.data('root-id');
                
                return {
                    q: params.term,
                    root_id: rootId
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.path ? item.text + ' (' + item.path + ')' : item.text,
                            data: item
                        };
                    })
                };
            },
            cache: true
        }
    });

    // Xử lý khi chọn khóa học từ Select2
    $('#course-search-select').on('select2:select', function (e) {
        const data = e.params.data;
        const courseId = data.id;
        
        // Sử dụng hàm navigateToCourse đã có sẵn
        window.navigateToCourse(courseId);
        
        // Clear selection sau khi navigate
        $(this).val(null).trigger('change');
    });

    // Clear Select2 khi chuyển tab
    $('.course-tabs .nav-link').on('shown.bs.tab', function (e) {
        $('#course-search-select').val(null).trigger('change');
    });
}); 

// Modal: Danh sách học viên theo khoá
function openStudentsModal(courseId){
    // Tạo modal nếu chưa có
    if($('#studentsModal').length === 0){
        $('body').append(`
        <div class="modal fade" id="studentsModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-xl">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Học viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="text-center p-3" id="studentsModalLoading"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>
                <div id="studentsModalContent" style="display:none"></div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
              </div>
            </div>
          </div>
        </div>`);
    }

    const $loading = $('#studentsModalLoading');
    const $content = $('#studentsModalContent');
    $loading.show();
    $content.hide().empty();

    // Lưu courseId để có thể refresh sau khi chỉnh sửa ghi danh
    $('#studentsModal').data('course-id', courseId);

    $('#studentsModal').modal('show');

    $.get(`/course-items/${courseId}/students-json`, function(res){
        if(!res.success){
            $content.html('<div class="alert alert-danger">Không tải được danh sách học viên.</div>').show();
            $loading.hide();
            return;
        }

        const isSpecial = !!(res.course && res.course.is_special);
        const customFields = (res.course && res.course.custom_fields) ? Object.keys(res.course.custom_fields) : [];

        let thead = `<tr>
            <th>Họ tên</th><th>SĐT</th><th>Email</th><th>Khoá</th><th>Học phí</th>`;
        if(isSpecial && customFields.length){
            customFields.forEach(k=>{ thead += `<th>${k}</th>`; });
        }
        thead += `<th>Ghi chú</th><th>Thao tác</th></tr>`;

        let tbody = '';
        res.students.forEach(function(s){
            let notesBtn = s.has_notes ? `<button type="button" class="btn btn-sm btn-info" data-notes='${JSON.stringify(s.payment_notes)}' data-student='${s.student.full_name}' onclick="showPaymentNotes(this)"><i class="fas fa-sticky-note"></i> Xem</button>` : '<span class="text-muted">Không có</span>';
            let customCols = '';
            if(isSpecial && customFields.length){
                customFields.forEach(k=>{
                    const val = (s.custom_fields && s.custom_fields[k]) ? s.custom_fields[k] : '-';
                    customCols += `<td>${val}</td>`;
                });
            }
            tbody += `<tr>
                <td>${s.student.full_name}</td>
                <td>${s.student.phone || ''}</td>
                <td>${s.student.email || ''}</td>
                <td>${s.course_item}</td>
                <td>${formatCurrency(s.final_fee||0)} VND</td>
                ${customCols}
                <td>${notesBtn}</td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-warning" onclick="openEditEnrollmentModal(${s.enrollment_id})" title="Chỉnh sửa đăng ký"><i class="fas fa-user-edit"></i></button>
                        ${s.payment_status !== 'Đã đóng đủ' ? `<button class="btn btn-sm btn-success" onclick="openQuickPaymentModal(${s.enrollment_id})" title="Thanh toán nhanh"><i class=\"fas fa-money-bill\"></i></button>` : ''}
                    </div>
                </td>
            </tr>`;
        });

        const html = `<div class="mb-2"><strong>${res.course.name}</strong> - Tổng học viên: ${res.total_students}</div>
            <div class="table-responsive"><table class="table table-striped"><thead>${thead}</thead><tbody>${tbody}</tbody></table></div>`;
        $content.html(html).show();
        $loading.hide();
    }).fail(function(){
        $content.html('<div class="alert alert-danger">Lỗi tải dữ liệu.</div>').show();
        $loading.hide();
    });
}

// ========== NEW: Modal chỉnh sửa ghi danh ==========
function openEditEnrollmentModal(enrollmentId){
    // Tạo modal nếu chưa có
    if($('#editEnrollmentModal').length === 0){
        $('body').append(`
        <div class="modal fade" id="editEnrollmentModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Chỉnh sửa đăng ký</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div id="editEnrollLoading" class="text-center py-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>
                <form id="editEnrollmentForm" style="display:none">
                  <div class="mb-2"><strong>Học viên:</strong> <span id="ee-student-name"></span></div>
                  <div class="mb-2"><strong>Khoá:</strong> <span id="ee-course-name"></span></div>
                  <div class="row g-2">
                    <div class="col-6">
                      <label class="form-label">Ngày ghi danh</label>
                      <input type="date" class="form-control" name="enrollment_date" required>
                    </div>
                    <div class="col-6">
                      <label class="form-label">Trạng thái</label>
                      <select class="form-select" name="status" required>
                        <option value="active">Đang học</option>
                        <option value="waiting">Danh sách chờ</option>
                        <option value="completed">Đã hoàn thành</option>
                        <option value="cancelled">Đã hủy</option>
                      </select>
                    </div>
                  </div>
                  <div class="row g-2 mt-2">
                    <div class="col-4">
                      <label class="form-label">Chiết khấu (%)</label>
                      <input type="number" min="0" max="100" step="0.01" class="form-control" name="discount_percentage">
                    </div>
                    <div class="col-4">
                      <label class="form-label">Chiết khấu (VND)</label>
                      <input type="number" min="0" step="0.01" class="form-control" name="discount_amount">
                    </div>
                    <div class="col-4">
                      <label class="form-label">Học phí cuối</label>
                      <input type="number" min="0" step="0.01" class="form-control" name="final_fee" required>
                    </div>
                  </div>
                  <div class="mt-2">
                    <label class="form-label">Ghi chú</label>
                    <textarea class="form-control" rows="3" name="notes"></textarea>
                  </div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" id="btnSaveEnrollment" class="btn btn-primary">Lưu</button>
              </div>
            </div>
          </div>
        </div>`);
    }

    // Mở modal và load dữ liệu
    $('#editEnrollmentModal').modal('show');
    $('#editEnrollLoading').show();
    $('#editEnrollmentForm').hide()[0].reset();

    $.get(`/api/enrollments/${enrollmentId}`, function(res){
        if(!res.success){
            $('#editEnrollLoading').hide();
            $('#editEnrollmentForm').hide();
            alert(res.message || 'Không tải được thông tin ghi danh');
            return;
        }
        const d = res.data;
        // Hiển thị thông tin
        $('#ee-student-name').text(d.student ? d.student.full_name : '');
        $('#ee-course-name').text(d.course_item ? d.course_item.name : (d.courseItem ? d.courseItem.name : ''));

        // Chuẩn hoá ngày về YYYY-MM-DD
        let dateVal = '';
        if(d.enrollment_date){
            if(typeof d.enrollment_date === 'string'){
                dateVal = d.enrollment_date.substring(0,10); // 2025-07-20...
            } else if(d.enrollment_date.date){
                dateVal = d.enrollment_date.date.substring(0,10);
            }
        }
        const $form = $('#editEnrollmentForm');
        $form.find('[name="enrollment_date"]').val(dateVal || new Date().toISOString().substring(0,10));
        $form.find('[name="status"]').val(d.status || 'active');
        $form.find('[name="discount_percentage"]').val(d.discount_percentage || 0);
        $form.find('[name="discount_amount"]').val(d.discount_amount || 0);
        $form.find('[name="final_fee"]').val(d.final_fee || 0);
        $form.find('[name="notes"]').val(d.notes || '');

        // Lưu thông tin phụ để refresh danh sách sau khi lưu
        $('#editEnrollmentModal').data('enrollment-id', d.id);
        $('#editEnrollmentModal').data('course-id', (d.course_item_id || (d.courseItem ? d.courseItem.id : null)));

        $('#editEnrollLoading').hide();
        $form.show();
    }).fail(function(xhr){
        $('#editEnrollLoading').hide();
        alert('Lỗi tải dữ liệu: ' + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Không xác định'));
    });

    // Xử lý lưu
    $('#btnSaveEnrollment').off('click').on('click', function(){
        const $form = $('#editEnrollmentForm');
        const payload = {
            enrollment_date: $form.find('[name="enrollment_date"]').val(),
            status: $form.find('[name="status"]').val(),
            discount_percentage: $form.find('[name="discount_percentage"]').val() || 0,
            discount_amount: $form.find('[name="discount_amount"]').val() || 0,
            final_fee: $form.find('[name="final_fee"]').val() || 0,
            notes: $form.find('[name="notes"]').val() || ''
        };
        const eid = $('#editEnrollmentModal').data('enrollment-id');

        $.ajax({
            url: `/api/enrollments/${eid}`,
            method: 'POST', // Theo routes/api.php
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : ''
            },
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function(res){
                if(res && res.success){
                    // Đóng modal chỉnh sửa
                    $('#editEnrollmentModal').modal('hide');
                    // Refresh danh sách học viên nếu đang mở
                    const cid = $('#studentsModal').data('course-id') || $('#editEnrollmentModal').data('course-id');
                    if($('#studentsModal').hasClass('show') && cid){
                        // Reload nội dung danh sách
                        openStudentsModal(cid);
                    }
                    // Thông báo
                    if(window.toastr && toastr.success){
                        toastr.success(res.message || 'Đã cập nhật đăng ký');
                    } else {
                        showToast(res.message || 'Đã cập nhật đăng ký', 'success');
                    }
                } else {
                    const msg = (res && res.message) ? res.message : 'Không thể cập nhật đăng ký';
                    if(window.toastr && toastr.error){ toastr.error(msg); } else { alert(msg); }
                }
            },
            error: function(xhr){
                let msg = 'Có lỗi xảy ra';
                if(xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors){
                    // Gộp thông báo lỗi validation
                    const errs = xhr.responseJSON.errors;
                    msg = Object.keys(errs).map(k => errs[k]).join('\n');
                } else if(xhr.responseJSON && xhr.responseJSON.message){
                    msg = xhr.responseJSON.message;
                }
                if(window.toastr && toastr.error){ toastr.error(msg); } else { alert(msg); }
            }
        });
    });
}

// Hiển thị ghi chú thanh toán
window.showPaymentNotes = function(btn){
    const notes = $(btn).data('notes');
    const student = $(btn).data('student');
    let rows = '';
    (notes||[]).forEach(n=>{
        const statusBadge = n.status==='confirmed' ? '<span class="badge bg-success">Đã xác nhận</span>' : (n.status==='pending' ? '<span class="badge bg-warning text-dark">Chờ xác nhận</span>' : '<span class="badge bg-danger">Đã hủy</span>');
        rows += `<tr><td>${n.date}</td><td>${formatCurrency(n.amount)} VND</td><td>${n.method}</td><td>${statusBadge}</td><td>${n.notes||''}</td></tr>`;
    });
    const content = `<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Ngày</th><th>Số tiền</th><th>Phương thức</th><th>Trạng thái</th><th>Ghi chú</th></tr></thead><tbody>${rows}</tbody></table></div>`;
    $('#studentsModal .modal-title').text('Ghi chú thanh toán - ' + student);
    $('#studentsModalContent').html(content).show();
    $('#studentsModalLoading').hide();
}

// Điểm danh: mở modal và load danh sách qua API hiện có
function openAttendanceModal(courseId){
    // Dùng modal đã có trong Blade: #attendanceModal
    const $modal = $('#attendanceModal');
    if ($modal.length === 0) {
        // Fallback tạm thời
        window.location.href = `/course-items/${courseId}/attendance`;
        return;
    }
    $modal.data('course-id', courseId);
    $modal.modal('show');

    // Tải danh sách mặc định theo ngày hiện trong input
    loadAttendanceStudents();
}

function loadAttendanceStudents(){
    const $modal = $('#attendanceModal');
    const courseId = $modal.data('course-id');
    const date = $('#attendance-date').val();

    $('#attendance-loading').show();
    $('#attendance-table tbody').empty();

    $.get(`/course-items/${courseId}/attendance-students`, { date }, function(res){
        $('#attendance-loading').hide();
        if(!res.success){
            $('#attendance-table tbody').html('<tr><td colspan="4" class="text-danger">Không tải được dữ liệu</td></tr>');
            return;
        }
        $('#attendance-total').text(res.total_students || 0);
        const rows = res.students.map(s => {
            return `<tr data-enrollment-id="${s.enrollment_id}">
                <td>${s.student_name}</td>
                <td>${s.student_phone || ''}</td>
                <td>
                    <select class="form-select form-select-sm attendance-status">
                        <option value="present" ${s.current_status==='present'?'selected':''}>Có mặt</option>
                        <option value="absent" ${s.current_status==='absent'?'selected':''}>Vắng</option>
                        <option value="late" ${s.current_status==='late'?'selected':''}>Đi trễ</option>
                        <option value="excused" ${s.current_status==='excused'?'selected':''}>Có phép</option>
                    </select>
                </td>
                <td><input type="text" class="form-control form-control-sm attendance-notes" value="${s.current_notes||''}" /></td>
            </tr>`;
        }).join('');
        $('#attendance-table tbody').html(rows || '<tr><td colspan="4">Không có học viên</td></tr>');
    }).fail(function(){
        $('#attendance-loading').hide();
        $('#attendance-table tbody').html('<tr><td colspan="4" class="text-danger">Lỗi tải dữ liệu</td></tr>');
    });
}

// Bind nút tải danh sách và lưu điểm danh trong modal
$(document).on('click', '#btn-load-attendance', function(){
    loadAttendanceStudents();
});

$(document).on('click', '#btn-save-attendance', function(){
    const $modal = $('#attendanceModal');
    const courseId = $modal.data('course-id');
    const date = $('#attendance-date').val();

    const attendances = [];
    $('#attendance-table tbody tr').each(function(){
        const enrollmentId = $(this).data('enrollment-id');
        const status = $(this).find('.attendance-status').val();
        const notes = $(this).find('.attendance-notes').val();
        attendances.push({ enrollment_id: enrollmentId, status, notes });
    });

    $.ajax({
        url: '/attendance/save-from-tree',
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
        contentType: 'application/json',
        data: JSON.stringify({ course_item_id: courseId, date, attendances }),
        success: function(res){
            if(res.success){
                toastr && toastr.success ? toastr.success(res.message || 'Đã lưu điểm danh') : alert(res.message || 'Đã lưu điểm danh');
            } else {
                toastr && toastr.error ? toastr.error(res.message || 'Không thể lưu') : alert(res.message || 'Không thể lưu');
            }
        },
        error: function(xhr){
            const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Có lỗi xảy ra';
            toastr && toastr.error ? toastr.error(msg) : alert(msg);
        }
    });
});

// Placeholder: mở modal thanh toán theo khoá
function openPaymentsModal(courseId){
    window.location.href = `/payments/course/${courseId}`; // TODO: chuyển sang modal sau khi có API phù hợp
} 