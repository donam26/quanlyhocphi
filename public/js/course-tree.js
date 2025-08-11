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
            console.log('Không tìm thấy khóa học với ID:', courseId);
            return;
        }
        
        // Xóa highlight cũ
        $('.tree-item').removeClass('search-highlighted');
        
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
        
        // Highlight node được tìm thấy
        $node.addClass('search-highlighted');
        
        // Cuộn tới vị trí node
        if ($node[0]) {
            setTimeout(() => {
                $node[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300); // Delay để đợi tab và collapse mở xong
        }
        
        // Tự động xóa highlight sau 3 giây
        setTimeout(() => {
            $node.removeClass('search-highlighted');
        }, 3000);
    };
}

// Đặt hàm showCourseDetails vào đối tượng window để có thể gọi từ mọi nơi
window.showCourseDetails = function(courseId) {
    console.log("showCourseDetails được gọi với ID:", courseId);
    currentCourseId = courseId;
    
    // Kiểm tra modal có tồn tại không
    if ($('#viewCourseModal').length === 0) {
        console.error("Không tìm thấy modal #viewCourseModal");
        showToast("Lỗi: Không tìm thấy modal để hiển thị thông tin khóa học", "error");
        return;
    }
    
    // Hiển thị modal
    try {
        $('#viewCourseModal').modal('show');
        console.log("Modal đã được gọi để hiển thị");
    } catch (error) {
        console.error("Lỗi khi hiển thị modal:", error);
        showToast("Có lỗi khi hiển thị thông tin khóa học", "error");
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
            
            // Hiển thị trạng thái khóa học (ưu tiên status enum, fallback sang active)
            if (data.status_badge) {
                $('#course-status').html(data.status_badge);
            } else {
                $('#course-status').html(data.active 
                    ? '<span class="badge bg-success">Hoạt động</span>' 
                    : '<span class="badge bg-danger">Không hoạt động</span>');
            }
            
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
            
            // Load learning paths để kiểm tra có lộ trình hay không
            loadCourseLearningPathsStatus(courseId);
            
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

    // Nút cài đặt lộ trình trong modal chi tiết
    $(document).on('click', '#btn-learning-path', function() {
        $('#viewCourseModal').modal('hide');
        openLearningPathModal(currentCourseId);
    });

    // Nút xem lộ trình trong modal chi tiết
    $(document).on('click', '#btn-view-learning-path', function() {
        // Chỉ hiển thị danh sách lộ trình, không cho chỉnh sửa
        showLearningPathsList(currentCourseId);
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
    
    // Học phí sẽ được lấy từ AJAX call, khởi tạo = 0
    let fee = 0;
    
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
    // Fee sẽ được set từ AJAX response
    
    // Trigger event để sync trạng thái
    $(document).trigger('editModalDataLoaded');

    // Xây dựng danh sách chọn khoá cha dạng tree
    buildParentSelectOptions(item.closest('.tab-pane'), id, parentId);
    
    // Cập nhật action của form
    $('#edit-item-form').attr('action', `/course-items/${id}`);
    
    // Hiển thị/ẩn các tùy chọn cho nút lá
    if (isLeaf) {
        $('#edit-leaf-options').show();
        $('#edit-item-fee').prop('required', true);
    } else {
        $('#edit-leaf-options').hide();
        $('#edit-item-fee').prop('required', false);
    }
    
    // Lấy thông tin về khóa học đặc biệt và các trường tùy chỉnh
    $.ajax({
        url: `/api/course-items/${id}`,
        method: "GET",
        success: function(response) {
            console.log('Course data loaded:', response);
            
            // Thiết lập học phí
            $('#edit-item-fee').val(response.fee || 0);
            
            // Thiết lập checkbox khóa học đặc biệt
            $('#edit-is-special').prop('checked', response.is_special);
            
            // Trigger sync state sau khi set checkbox
            $(document).trigger('editModalSpecialDataLoaded');
            
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
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-primary btn-sm" id="addStudentBtn" title="Thêm học viên">
                    <i class="fas fa-user-plus me-1"></i>Thêm học viên
                  </button>
                  <button type="button" class="btn btn-success btn-sm" id="importExcelBtn" title="Import Excel">
                    <i class="fas fa-file-excel me-1"></i>Import Excel
                  </button>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
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
                        <button class="btn btn-sm btn-primary" onclick="openEditStudentModal(${s.student.id})" title="Chỉnh sửa học viên"><i class="fas fa-user-edit"></i></button>
                        <button class="btn btn-sm btn-warning" onclick="openEditEnrollmentModal(${s.enrollment_id})" title="Chỉnh sửa đăng ký"><i class="fas fa-graduation-cap"></i></button>
                    </div>
                </td>
            </tr>`;
        });

        const html = `<div class="mb-2"><strong>${res.course.name}</strong> - Tổng học viên: ${res.total_students}</div>
            <div class="table-responsive"><table class="table table-striped"><thead>${thead}</thead><tbody>${tbody}</tbody></table></div>`;
        $content.html(html).show();
        $loading.hide();

        // Xử lý nút Import Excel
        $('#importExcelBtn').off('click').on('click', function() {
            openImportExcelModal(courseId);
        });
    }).fail(function(){
        $content.html('<div class="alert alert-danger">Lỗi tải dữ liệu.</div>').show();
        $loading.hide();
    });

    // Event handler cho nút "Thêm học viên"
    $(document).off('click', '#addStudentBtn').on('click', '#addStudentBtn', function(){
        openAddStudentModal(courseId);
    });
}

// ========== NEW: Modal thêm học viên ==========
function openAddStudentModal(courseId) {
    // Tạo modal nếu chưa có
    if ($('#addStudentModal').length === 0) {
        $('body').append(`
        <div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Thêm học viên vào khóa học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="text-center p-3" id="addStudentModalLoading">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                  </div>
                </div>
                <form id="addStudentForm" style="display:none">
                  <div class="row">
                    <div class="col-12">
                      <div class="mb-3">
                        <label for="student_select" class="form-label">Chọn học viên <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="student_select" name="student_id" required>
                          <option value="">-- Chọn học viên --</option>
                        </select>
                        <div class="form-text">Chọn học viên chưa đăng ký khóa học này</div>
                      </div>
                    </div>
                  </div>
                  
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="enrollment_date" class="form-label">Ngày ghi danh <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="enrollment_date" name="enrollment_date" required 
                               placeholder="dd/mm/yyyy" pattern="\\d{2}/\\d{2}/\\d{4}" 
                               title="Nhập ngày theo định dạng dd/mm/yyyy">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-3">
                        <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                        <select class="form-control" id="status" name="status" required>
                          <option value="active">Đang học</option>
                          <option value="waiting">Danh sách chờ</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  
                  <div class="row">
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label for="discount_percentage" class="form-label">Giảm giá (%)</label>
                        <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" min="0" max="100" value="0">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label for="discount_amount" class="form-label">Giảm giá (VND)</label>
                        <input type="number" class="form-control" id="discount_amount" name="discount_amount" min="0" value="0">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-3">
                        <label for="final_fee" class="form-label">Học phí cuối <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="final_fee" name="final_fee" min="0" required readonly>
                      </div>
                    </div>
                  </div>
                  
                  <div class="mb-3">
                    <label for="notes" class="form-label">Ghi chú</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                  </div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="saveStudentBtn" style="display:none">
                  <i class="fas fa-save me-1"></i>Thêm học viên
                </button>
              </div>
            </div>
          </div>
        </div>`);
    }

    // Lưu courseId và hiển thị modal
    $('#addStudentModal').data('course-id', courseId);
    $('#addStudentModal').modal('show');

    // Reset form và clear warnings
    $('#addStudentForm')[0].reset();
    $('#fee-warning').remove(); // Xóa cảnh báo học phí cũ
    $('#addStudentModalLoading').show();
    $('#addStudentForm').hide();
    $('#saveStudentBtn').hide();

    // Đặt ngày ghi danh mặc định là hôm nay (dd/mm/yyyy)
    $('#enrollment_date').val(getCurrentDate());

    // Khởi tạo select2 cho dropdown học viên
    initAddStudentSelect2();

    // Tải danh sách học viên có thể thêm
    loadAvailableStudents(courseId);
}

// Khởi tạo select2 cho dropdown học viên
function initAddStudentSelect2() {
    try {
        $('#student_select').select2('destroy');
    } catch (e) {
        // Không làm gì nếu select2 chưa được áp dụng
    }

    $('#student_select').select2({
        theme: 'bootstrap-5',
        placeholder: 'Tìm kiếm và chọn học viên...',
        allowClear: true,
        dropdownParent: $('#addStudentModal'),
        width: '100%',
        minimumInputLength: 0
    });
}

// Tải danh sách học viên chưa đăng ký khóa học
function loadAvailableStudents(courseId) {
    $.ajax({
        url: `/api/students/`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                // Lấy danh sách học viên đã đăng ký khóa học này
                $.ajax({
                    url: `/course-items/${courseId}/students-json`,
                    method: 'GET',
                    success: function(enrolledResponse) {
                        const enrolledStudentIds = enrolledResponse.students ? 
                            enrolledResponse.students.map(s => s.student.id) : [];
                        
                        // Lọc ra các học viên chưa đăng ký
                        const availableStudents = response.data.filter(student => 
                            !enrolledStudentIds.includes(student.id)
                        );
                        
                        // Populate select options
                        const select = $('#student_select');
                        select.empty().append('<option value="">-- Chọn học viên --</option>');
                        
                        if (availableStudents.length === 0) {
                            select.append('<option value="">Không có học viên nào khả dụng</option>');
                            $('#addStudentForm .form-text').text('Tất cả học viên đã đăng ký khóa học này');
                        } else {
                            availableStudents.forEach(student => {
                                select.append(`<option value="${student.id}">${student.full_name} - ${student.phone}</option>`);
                            });
                        }
                        
                        // Trigger change để select2 cập nhật
                        select.trigger('change');
                        
                        // Lấy thông tin khóa học để tính học phí
                        loadCourseInfo(courseId);
                        
                        $('#addStudentModalLoading').hide();
                        $('#addStudentForm').show();
                        $('#saveStudentBtn').show();
                    },
                    error: function() {
                        showErrorInAddStudentModal('Không thể tải danh sách học viên đã đăng ký');
                    }
                });
            } else {
                showErrorInAddStudentModal('Không thể tải danh sách học viên');
            }
        },
        error: function() {
            showErrorInAddStudentModal('Lỗi kết nối khi tải danh sách học viên');
        }
    });
}

// Tải thông tin khóa học để tính học phí
function loadCourseInfo(courseId) {
    // Gọi API để lấy thông tin khóa học
    $.ajax({
        url: `/api/course-items/${courseId}`,
        method: 'GET',
        success: function(response) {
            // API trả về với wrapper success/data
            if (response.success && response.data) {
                const baseFee = parseFloat(response.data.fee) || 0;
                $('#final_fee').val(baseFee);
                
                console.log('Loaded course fee:', baseFee, 'for course:', response.data.name);
                
                // Kiểm tra học phí > 0
                if (baseFee <= 0) {
                    // Hiển thị cảnh báo và disable form
                    $('#addStudentForm').prepend(`
                        <div class="alert alert-warning alert-dismissible fade show" role="alert" id="fee-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Lưu ý:</strong> Khóa học "${response.data.name}" chưa được thiết lập học phí. Không thể thêm học viên vào khóa học này.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    
                    // Disable submit button
                    $('#saveStudentBtn').prop('disabled', true).html('<i class="fas fa-ban me-1"></i>Không thể thêm học viên');
                    
                    return;
                } else {
                    // Xóa cảnh báo nếu có
                    $('#fee-warning').remove();
                    $('#saveStudentBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Thêm học viên');
                }
                
                // Setup event listeners cho tính toán học phí
                $('#discount_percentage, #discount_amount').off('input').on('input', function() {
                    calculateFinalFee(baseFee);
                });
            } else {
                console.log('No fee found in course data:', response);
                // Fallback: thử tìm trong DOM
                loadCourseInfoFromDOM(courseId);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading course info:', error);
            // Fallback: thử tìm trong DOM
            loadCourseInfoFromDOM(courseId);
        }
    });
}

// Fallback: Load thông tin học phí từ DOM
function loadCourseInfoFromDOM(courseId) {
    const $courseItem = $(`.tree-item[data-id="${courseId}"]`);
    if ($courseItem.length) {
        const feeText = $courseItem.find('.text-muted').text();
        const feeMatch = feeText.match(/(\d[\d,]*)\s*VND/);
        if (feeMatch) {
            const baseFee = parseFloat(feeMatch[1].replace(/,/g, '')) || 0;
            $('#final_fee').val(baseFee);
            
            console.log('Loaded course fee from DOM:', baseFee);
            
            // Kiểm tra học phí > 0 (fallback từ DOM)
            if (baseFee <= 0) {
                $('#addStudentForm').prepend(`
                    <div class="alert alert-warning alert-dismissible fade show" role="alert" id="fee-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Lưu ý:</strong> Khóa học này chưa được thiết lập học phí. Không thể thêm học viên vào khóa học này.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `);
                $('#saveStudentBtn').prop('disabled', true).html('<i class="fas fa-ban me-1"></i>Không thể thêm học viên');
                return;
            }
            
            $('#discount_percentage, #discount_amount').off('input').on('input', function() {
                calculateFinalFee(baseFee);
            });
        } else {
            console.log('No fee found in DOM for course:', courseId);
            // Đặt giá trị mặc định
            $('#final_fee').val(0);
        }
    } else {
        console.log('Course item not found in DOM:', courseId);
        $('#final_fee').val(0);
    }
}

// Tìm thông tin khóa học trong tree
function findCourseInTree(courseId, treeData) {
    for (const item of treeData) {
        if (item.id === courseId) {
            return item;
        }
        if (item.children && item.children.length > 0) {
            const found = findCourseInTree(courseId, item.children);
            if (found) return found;
        }
    }
    return null;
}

// Tính toán học phí cuối
function calculateFinalFee(baseFee) {
    const discountPercentage = parseFloat($('#discount_percentage').val()) || 0;
    const discountAmount = parseFloat($('#discount_amount').val()) || 0;
    
    const percentageDiscount = baseFee * (discountPercentage / 100);
    const finalFee = Math.max(0, baseFee - percentageDiscount - discountAmount);
    
    $('#final_fee').val(Math.round(finalFee));
}

// Hiển thị lỗi trong modal thêm học viên
function showErrorInAddStudentModal(message) {
    $('#addStudentModalLoading').hide();
    $('#addStudentForm').html(`<div class="alert alert-danger">${message}</div>`).show();
}

// Cập nhật nội dung modal danh sách học viên
function updateStudentsModalContent(res) {
    const $content = $('#studentsModalContent');
    
    if (!res.success) {
        $content.html('<div class="alert alert-danger">Không thể tải danh sách học viên.</div>');
        return;
    }

    const isSpecial = !!(res.course && res.course.is_special);
    const customFields = (res.course && res.course.custom_fields) ? Object.keys(res.course.custom_fields) : [];

    let thead = `<tr>
        <th>Họ tên</th><th>SĐT</th><th>Email</th><th>Khoá</th><th>Học phí</th>`;
    if (isSpecial && customFields.length) {
        customFields.forEach(k => { thead += `<th>${k}</th>`; });
    }
    thead += `<th>Ghi chú</th><th>Thao tác</th></tr>`;

    let tbody = '';
    res.students.forEach(function(s) {
        let notesBtn = s.has_notes 
            ? `<button type="button" class="btn btn-sm btn-info" onclick="viewNotes('${s.student.full_name}', '${JSON.stringify(s.payment_notes).replace(/'/g, '\\\'')}')" title="Xem ghi chú"><i class="fas fa-sticky-note"></i></button>`
            : '<span class="text-muted">-</span>';
        
        let statusBadge = '';
        if (s.status === 'active') statusBadge = '<span class="badge bg-success">Đang học</span>';
        else if (s.status === 'waiting') statusBadge = '<span class="badge bg-warning">Chờ</span>';
        else if (s.status === 'completed') statusBadge = '<span class="badge bg-info">Hoàn thành</span>';
        else if (s.status === 'cancelled') statusBadge = '<span class="badge bg-danger">Đã hủy</span>';

        tbody += `<tr>
            <td>${s.student.full_name}</td>
            <td>${s.student.phone}</td>
            <td>${s.student.email || '-'}</td>
            <td>${statusBadge}</td>
            <td>${s.final_fee ? (parseInt(s.final_fee).toLocaleString() + ' VND') : '-'}</td>`;
        
        if (isSpecial && customFields.length) {
            customFields.forEach(k => {
                tbody += `<td>${s.custom_fields && s.custom_fields[k] ? s.custom_fields[k] : '-'}</td>`;
            });
        }
        
        tbody += `<td>${notesBtn}</td>
            <td><button type="button" class="btn btn-sm btn-outline-primary" onclick="openEditEnrollmentModal(${s.enrollment_id})" title="Chỉnh sửa"><i class="fas fa-edit"></i></button></td>
        </tr>`;
    });

    $content.html(`
        <div class="mb-3">
            <span class="badge bg-primary">Tổng học viên: ${res.students.length}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-dark">${thead}</thead>
                <tbody>${tbody}</tbody>
            </table>
        </div>
    `);
}

// Event handler cho nút lưu
$(document).off('click', '#saveStudentBtn').on('click', '#saveStudentBtn', function() {
    const courseId = $('#addStudentModal').data('course-id');
    const formData = new FormData(document.getElementById('addStudentForm'));
    
    // Validate
    if (!$('#student_select').val()) {
        showToast('Vui lòng chọn học viên', 'warning');
        return;
    }
    
    // Disable button và show loading
    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Đang thêm...');
    
    // Debug request data
    console.log('Submitting form data:');
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    console.log('Course ID:', courseId);
    console.log('URL:', `/course-items/${courseId}/add-student`);
    console.log('CSRF Token:', $('meta[name="csrf-token"]').attr('content'));

    // Submit form
    $.ajax({
        url: `/course-items/${courseId}/add-student`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            console.log('Add student response:', response);
            
            if (response.success) {
                // Đóng modal
                $('#addStudentModal').modal('hide');
                
                // Refresh danh sách học viên trong modal chính
                const mainCourseId = $('#studentsModal').data('course-id');
                if (mainCourseId) {
                    // Reload danh sách học viên
                    $.get(`/course-items/${mainCourseId}/students-json`).done(function(res) {
                        if (res.success) {
                            updateStudentsModalContent(res);
                        }
                    });
                }
                
                // Hiển thị thông báo thành công
                showToast('Thêm học viên thành công!', 'success');
            } else {
                // Hiển thị thông báo lỗi từ server
                console.error('Server returned error:', response.message);
                showToast(response.message || 'Có lỗi xảy ra khi thêm học viên', 'error');
                
                // Nếu showToast không hoạt động, sử dụng alert backup
                if (typeof showToast !== 'function' || !window.showToast) {
                    alert(response.message || 'Có lỗi xảy ra khi thêm học viên');
                }
            }
        },
        error: function(xhr) {
            console.error('Error adding student:', xhr);
            
            if (xhr.status === 422) {
                // Validation errors
                const response = xhr.responseJSON;
                if (response && response.errors) {
                    let errorMessage = 'Lỗi validation:\n';
                    Object.keys(response.errors).forEach(key => {
                        errorMessage += '- ' + response.errors[key][0] + '\n';
                    });
                    showToast(errorMessage, 'error');
                } else if (response && response.message) {
                    showToast(response.message, 'error');
                } else {
                    showToast('Lỗi validation. Vui lòng kiểm tra lại thông tin.', 'error');
                }
            } else if (xhr.status === 500) {
                const response = xhr.responseJSON;
                const message = response && response.message ? response.message : 'Lỗi server';
                showToast('Lỗi hệ thống: ' + message, 'error');
            } else {
                showToast('Có lỗi xảy ra khi thêm học viên. Status: ' + xhr.status, 'error');
            }
        },
        complete: function() {
            // Reset button
            $('#saveStudentBtn').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Thêm học viên');
        }
    });
});

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
                      <input type="text" class="form-control" name="enrollment_date" required
                             placeholder="dd/mm/yyyy" pattern="\\d{2}/\\d{2}/\\d{4}"
                             title="Nhập ngày theo định dạng dd/mm/yyyy">
                    </div>
                    <div class="col-6">
                      <label class="form-label">Trạng thái</label>
                      <select class="form-select" name="status" required id="ee-status-select">
                        <option value="active">Đang học</option>
                        <option value="waiting">Danh sách chờ</option>
                        <option value="completed">Đã hoàn thành</option>
                        <option value="cancelled">Đã hủy</option>
                      </select>
                    </div>
                  </div>
                  <!-- Khóa học chờ - chỉ hiển thị khi trạng thái là waiting -->
                  <div class="row g-2 mt-2" id="ee-waiting-course-row" style="display: none;">
                    <div class="col-12">
                      <label class="form-label">Khóa học muốn chuyển về <span class="text-muted">(tùy chọn - để trống sẽ giữ khóa hiện tại)</span></label>
                      <select class="form-select" name="waiting_course_id" id="ee-waiting-course-select">
                        <option value="">-- Giữ khóa hiện tại --</option>
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
            showToast(res.message || 'Không tải được thông tin ghi danh', 'error');
            return;
        }
        const d = res.data;
        // Hiển thị thông tin
        $('#ee-student-name').text(d.student ? d.student.full_name : '');
        $('#ee-course-name').text(d.course_item ? d.course_item.name : (d.courseItem ? d.courseItem.name : ''));

        // Format ngày về dd/mm/yyyy cho input text
        let dateVal = '';
        if(d.enrollment_date){
            // Nếu nhận được formatted date (dd/mm/yyyy) hoặc ISO date
            if(typeof d.enrollment_date === 'string'){
                dateVal = d.formatted_enrollment_date || formatDate(d.enrollment_date);
            } else if(d.enrollment_date.date){
                dateVal = formatDate(d.enrollment_date.date);
            }
        }
        const $form = $('#editEnrollmentForm');
        $form.find('[name="enrollment_date"]').val(dateVal || getCurrentDate());
        $form.find('[name="status"]').val(d.status || 'active');
        $form.find('[name="discount_percentage"]').val(d.discount_percentage || 0);
        $form.find('[name="discount_amount"]').val(d.discount_amount || 0);
        $form.find('[name="final_fee"]').val(d.final_fee || 0);
        $form.find('[name="notes"]').val(d.notes || '');

        // Lưu thông tin phụ để refresh danh sách sau khi lưu
        $('#editEnrollmentModal').data('enrollment-id', d.id);
        $('#editEnrollmentModal').data('course-id', (d.course_item_id || (d.courseItem ? d.courseItem.id : null)));

        // Khởi tạo Select2 cho waiting course select
        initWaitingCourseSelect2ForEdit();
        
        // Setup event handler cho status change
        setupStatusChangeHandler();
        
        // Trigger initial status change để hiển thị/ẩn waiting course field
        $form.find('[name="status"]').trigger('change');

        $('#editEnrollLoading').hide();
        $form.show();
    }).fail(function(xhr){
        $('#editEnrollLoading').hide();
        showToast('Lỗi tải dữ liệu: ' + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Không xác định'), 'error');
    });

    // Xử lý lưu
    $('#btnSaveEnrollment').off('click').on('click', function(){
        const $form = $('#editEnrollmentForm');
        
        // Validate form trước khi submit
        const enrollmentDate = $form.find('[name="enrollment_date"]').val();
        if (!enrollmentDate) {
            showToast('Vui lòng chọn ngày ghi danh', 'warning');
            return;
        }
        
        const finalFee = parseFloat($form.find('[name="final_fee"]').val()) || 0;
        if (finalFee <= 0) {
            showToast('Học phí cuối phải lớn hơn 0', 'warning');
            return;
        }
        
        const payload = {
            enrollment_date: enrollmentDate,
            status: $form.find('[name="status"]').val(),
            discount_percentage: parseFloat($form.find('[name="discount_percentage"]').val()) || 0,
            discount_amount: parseFloat($form.find('[name="discount_amount"]').val()) || 0,
            final_fee: finalFee,
            notes: $form.find('[name="notes"]').val() || '',
            waiting_course_id: $form.find('[name="waiting_course_id"]').val() || null
        };
        
        console.log('Saving enrollment with payload:', payload);
        
        const eid = $('#editEnrollmentModal').data('enrollment-id');

        // Disable nút lưu để tránh click nhiều lần
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Đang lưu...');

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
                    showToast(msg, 'error');
                }
            },
            error: function(xhr){
                console.log('Error response:', xhr.responseJSON);
                let msg = 'Có lỗi xảy ra';
                if(xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors){
                    // Gộp thông báo lỗi validation
                    const errs = xhr.responseJSON.errors;
                    msg = Object.keys(errs).map(k => errs[k]).join('\n');
                } else if(xhr.responseJSON && xhr.responseJSON.message){
                    msg = xhr.responseJSON.message;
                }
                showToast(msg, 'error');
            },
            complete: function() {
                // Reset nút lưu
                $('#btnSaveEnrollment').prop('disabled', false).html('Lưu');
            }
        });
    });
}

// ========== HELPER FUNCTIONS FOR EDIT ENROLLMENT MODAL ==========

// Khởi tạo Select2 cho waiting course select trong edit modal
function initWaitingCourseSelect2ForEdit() {
    $('#ee-waiting-course-select').select2({
        theme: 'bootstrap-5',
        placeholder: 'Tìm kiếm khóa học...',
        allowClear: true,
        dropdownParent: $('#editEnrollmentModal'),
        width: '100%',
        minimumInputLength: 0,
        ajax: {
            url: '/api/course-items/search',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || ''
                };
            },
            processResults: function (response) {
                if (Array.isArray(response)) {
                    return {
                        results: response.map(function(course) {
                            return {
                                id: course.id,
                                text: course.name + (course.path ? ' (' + course.path + ')' : ''),
                                name: course.name,
                                path: course.path
                            };
                        })
                    };
                }
                return { results: [] };
            },
            cache: true
        }
    });
}

// Setup event handler cho status change
function setupStatusChangeHandler() {
    $('#ee-status-select').off('change').on('change', function() {
        const status = $(this).val();
        const $waitingRow = $('#ee-waiting-course-row');
        
        if (status === 'waiting') {
            $waitingRow.slideDown();
        } else {
            $waitingRow.slideUp();
            // Reset select2 khi ẩn
            $('#ee-waiting-course-select').val('').trigger('change');
        }
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
                showToast(res.message || 'Đã lưu điểm danh', 'success');
            } else {
                showToast(res.message || 'Không thể lưu', 'error');
            }
        },
        error: function(xhr){
            const msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Có lỗi xảy ra';
            showToast(msg, 'error');
        }
    });
});

// ========== LEARNING PATH MODAL ==========
function openLearningPathModal(courseId) {
    console.log('Opening learning path modal for course:', courseId);
    
    // Show modal
    $('#learningPathModal').modal('show');
    $('#learning-path-loading').show();
    $('#learning-path-content').hide();
    
    // Load course info and existing learning paths
    loadLearningPaths(courseId);
}

function loadLearningPaths(courseId) {
    $.ajax({
        url: `/api/course-items/${courseId}/learning-paths`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                // Display course name
                $('#course-name-display').text(response.course_name || `Khóa học #${courseId}`);
                
                // Clear existing paths
                $('#paths-container').empty();
                
                // Add existing paths
                if (response.paths && response.paths.length > 0) {
                    response.paths.forEach((path, index) => {
                        addLearningPathItem(path, index);
                    });
                } else {
                    // Add one empty path by default
                    addLearningPathItem(null, 0);
                }
                
                $('#learning-path-loading').hide();
                $('#learning-path-content').show();
            } else {
                showToast('Không thể tải thông tin lộ trình: ' + (response.message || 'Lỗi không xác định'), 'error');
                $('#learningPathModal').modal('hide');
            }
        },
        error: function(xhr) {
            console.error('Error loading learning paths:', xhr);
            showToast('Có lỗi xảy ra khi tải lộ trình học tập', 'error');
            $('#learningPathModal').modal('hide');
        }
    });
}

function addLearningPathItem(pathData = null, index = 0) {
    const pathItem = `
        <div class="path-item mb-4 p-3 border rounded" data-index="${index}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">
                    <i class="fas fa-grip-vertical me-2 text-muted"></i>
                    Lộ trình ${index + 1}
                </h6>
                <button type="button" class="btn btn-outline-danger btn-sm remove-path-btn" ${index === 0 ? 'style="display:none"' : ''}>
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            
            <input type="hidden" name="paths[${index}][id]" value="${pathData?.id || ''}" class="path-id">
            
            <div class="mb-3">
                <label class="form-label">Tên lộ trình <span class="text-danger">*</span></label>
                <input type="text" class="form-control path-title" name="paths[${index}][title]" 
                       value="${pathData?.title || ''}" placeholder="Nhập tên lộ trình..." required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Mô tả</label>
                <textarea class="form-control path-description" name="paths[${index}][description]" 
                          rows="3" placeholder="Mô tả chi tiết về lộ trình này...">${pathData?.description || ''}</textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Thứ tự</label>
                    <input type="number" class="form-control path-order" name="paths[${index}][order]" 
                           value="${pathData?.order || (index + 1)}" min="1" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Trạng thái</label>
                    <select class="form-select path-required" name="paths[${index}][is_required]">
                        <option value="1" ${pathData?.is_required !== false ? 'selected' : ''}>Bắt buộc</option>
                        <option value="0" ${pathData?.is_required === false ? 'selected' : ''}>Tùy chọn</option>
                    </select>
                </div>
            </div>
        </div>
    `;
    
    $('#paths-container').append(pathItem);
    updatePathIndexes();
}

function updatePathIndexes() {
    $('#paths-container .path-item').each(function(index) {
        $(this).attr('data-index', index);
        $(this).find('h6').html(`<i class="fas fa-grip-vertical me-2 text-muted"></i>Lộ trình ${index + 1}`);
        $(this).find('.path-id').attr('name', `paths[${index}][id]`);
        $(this).find('.path-title').attr('name', `paths[${index}][title]`);
        $(this).find('.path-description').attr('name', `paths[${index}][description]`);
        $(this).find('.path-order').attr('name', `paths[${index}][order]`).val(index + 1);
        $(this).find('.path-required').attr('name', `paths[${index}][is_required]`);
        
        // Hide delete button for first item
        $(this).find('.remove-path-btn').toggle(index > 0);
    });
}

// Event handlers for learning path modal
$(document).on('click', '#add-new-path', function() {
    const currentCount = $('#paths-container .path-item').length;
    addLearningPathItem(null, currentCount);
});

$(document).on('click', '.remove-path-btn', function() {
    $(this).closest('.path-item').remove();
    updatePathIndexes();
});

$(document).on('click', '#save-learning-paths', function() {
    const courseId = currentCourseId;
    const formData = new FormData(document.getElementById('learningPathForm'));
    
    // Validate required fields
    let isValid = true;
    $('#paths-container .path-title[required]').each(function() {
        if (!$(this).val().trim()) {
            $(this).addClass('is-invalid');
            isValid = false;
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    if (!isValid) {
        showToast('Vui lòng điền đầy đủ thông tin bắt buộc', 'warning');
        return;
    }
    
    // Disable button
    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Đang lưu...');
    
    $.ajax({
        url: `/api/course-items/${courseId}/learning-paths`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showToast('Đã lưu lộ trình học tập thành công!', 'success');
                $('#learningPathModal').modal('hide');
                
                // Reload learning paths status để cập nhật nút
                if (currentCourseId) {
                    loadCourseLearningPathsStatus(currentCourseId);
                    
                    // Cập nhật modal xem lộ trình nếu đang mở
                    if ($('#viewLearningPathModal').hasClass('show')) {
                        // Reload modal content to reflect changes
                        setTimeout(() => {
                            showLearningPathsList(currentCourseId);
                        }, 1000);
                    }
                }
            } else {
                showToast(response.message || 'Có lỗi xảy ra khi lưu lộ trình', 'error');
            }
        },
        error: function(xhr) {
            console.error('Error saving learning paths:', xhr);
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                let errorMessage = 'Lỗi validation:\n';
                Object.keys(xhr.responseJSON.errors).forEach(key => {
                    errorMessage += '- ' + xhr.responseJSON.errors[key][0] + '\n';
                });
                showToast(errorMessage, 'error');
            } else {
                showToast('Có lỗi xảy ra khi lưu lộ trình học tập', 'error');
            }
        },
        complete: function() {
            // Reset button
            $('#save-learning-paths').prop('disabled', false).html('<i class="fas fa-save me-1"></i>Lưu lộ trình');
        }
    });
});

// Load trạng thái lộ trình để hiển thị nút phù hợp
function loadCourseLearningPathsStatus(courseId) {
    $.ajax({
        url: `/api/course-items/${courseId}/learning-paths`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const hasLearningPaths = response.paths && response.paths.length > 0;
                
                if (hasLearningPaths) {
                    // Có lộ trình → hiện nút "Lộ trình"
                    $('#btn-learning-path').hide();
                    $('#btn-view-learning-path').show();
                } else {
                    // Chưa có lộ trình → hiện nút "Cài đặt lộ trình"
                    $('#btn-learning-path').show();
                    $('#btn-view-learning-path').hide();
                }
            } else {
                // Lỗi hoặc chưa có lộ trình → hiện nút cài đặt
                $('#btn-learning-path').show();
                $('#btn-view-learning-path').hide();
            }
        },
        error: function() {
            // Lỗi → hiện nút cài đặt
            $('#btn-learning-path').show();
            $('#btn-view-learning-path').hide();
        }
    });
}

// Hiển thị danh sách lộ trình (chỉ xem)
function showLearningPathsList(courseId) {
    // Show modal
    $('#viewLearningPathModal').modal('show');
    $('#view-learning-path-loading').show();
    $('#view-learning-path-content').hide();
    
    // Setup edit button
    $('#btn-edit-paths').off('click').on('click', function() {
        $('#viewLearningPathModal').modal('hide');
        openLearningPathModal(courseId);
    });
    
    $.ajax({
        url: `/api/course-items/${courseId}/learning-paths`,
        method: 'GET',
        success: function(response) {
            if (response.success && response.paths && response.paths.length > 0) {
                // Display course name
                $('#view-course-name-display').text(response.course_name || `Khóa học #${courseId}`);
                
                let pathsList = '';
                response.paths.forEach((path, index) => {
                    // Lấy trạng thái hoàn thành từ API
                    const isCompleted = path.is_completed || false;
                    
                    pathsList += `
                        <div class="learning-path-item ${isCompleted ? 'completed' : ''} p-3 mb-3 border rounded" id="view-path-item-${path.id}">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input view-progress-checkbox" 
                                    id="view-path-${path.id}" 
                                    data-path-id="${path.id}"
                                    data-course-id="${courseId}"
                                    ${isCompleted ? 'checked' : ''}>
                                <label class="form-check-label" for="view-path-${path.id}">
                                    <div class="fw-bold learning-path-title">Buổi ${index + 1}</div>
                                    <div class="text-muted">${path.title}</div>
                                    ${path.description ? `<div class="text-muted small">${path.description}</div>` : ''}

                                    <div class="text-success completion-status" id="view-status-${path.id}" style="${isCompleted ? '' : 'display: none;'}">
                                        <i class="fas fa-check-circle me-1"></i> Đã hoàn thành
                                    </div>
                                </label>
                            </div>
                            <div class="mt-2">
                                <span class="badge bg-${path.is_required ? 'warning' : 'secondary'}">
                                    ${path.is_required ? 'Bắt buộc' : 'Tùy chọn'}
                                </span>
                            </div>
                        </div>
                    `;
                });
                
                // Thêm event listener cho checkbox
                setTimeout(() => {
                    $('.view-progress-checkbox').change(function() {
                        const pathId = $(this).data('path-id');
                        const courseId = $(this).data('course-id');
                        const isCompleted = $(this).is(':checked');
                        const pathItem = $('#view-path-item-' + pathId);
                        const completionStatus = $('#view-status-' + pathId);
                        const checkbox = $(this);
                        
                        // Disable checkbox để tránh click liên tục
                        checkbox.prop('disabled', true);
                        pathItem.addClass('loading');
                        
                        // Gọi API để cập nhật
                        $.ajax({
                            url: `/api/learning-progress/toggle-path-completion/${pathId}`,
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: JSON.stringify({
                                course_id: parseInt(courseId),
                                is_completed: Boolean(isCompleted)
                            }),
                            success: function(response) {
                                if (response.success) {
                                    // Cập nhật giao diện
                                    if (isCompleted) {
                                        pathItem.removeClass('incomplete').addClass('completed');
                                        completionStatus.show();
                                    } else {
                                        pathItem.removeClass('completed').addClass('incomplete');
                                        completionStatus.hide();
                                    }
                                    
                                    // Hiển thị thông báo
                                    showToast(response.message, 'success');
                                } else {
                                    // Revert checkbox state
                                    checkbox.prop('checked', !isCompleted);
                                    showToast(response.message || 'Có lỗi xảy ra', 'error');
                                }
                            },
                            error: function(xhr) {
                                // Revert checkbox state
                                checkbox.prop('checked', !isCompleted);
                                
                                let errorMessage = 'Có lỗi xảy ra khi cập nhật tiến độ';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                }
                                showToast(errorMessage, 'error');
                            },
                            complete: function() {
                                // Re-enable checkbox và ẩn loading
                                checkbox.prop('disabled', false);
                                pathItem.removeClass('loading');
                            }
                        });
                    });
                }, 100);
                
                $('#view-paths-list').html(pathsList);
                $('#view-learning-path-loading').hide();
                $('#view-learning-path-content').show();
            } else {
                $('#view-learning-path-loading').hide();
                $('#view-learning-path-content').html(`
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Khóa học này chưa có lộ trình học tập.
                    </div>
                `).show();
            }
        },
        error: function() {
            $('#view-learning-path-loading').hide();
            $('#view-learning-path-content').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Có lỗi xảy ra khi tải lộ trình học tập.
                </div>
            `).show();
        }
    });
}

// Placeholder: mở modal thanh toán theo khoá
function openPaymentsModal(courseId){
    window.location.href = `/payments/course/${courseId}`; // TODO: chuyển sang modal sau khi có API phù hợp
}

// Modal chỉnh sửa học viên
function openEditStudentModal(studentId) {
    // Tạo modal nếu chưa có
    if ($('#editStudentModal').length === 0) {
        $('body').append(`
        <div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Chỉnh sửa thông tin học viên</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="editStudentForm">
                        <div class="modal-body">
                            <div id="editStudentLoading" class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <div id="editStudentContent" style="display:none">
                                <!-- Thông tin cơ bản -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="border-bottom pb-2 mb-3">
                                            <i class="fas fa-user me-2"></i>Thông tin cơ bản
                                        </h6>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Họ <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" id="edit-first-name" class="form-control" required>
                                        <div class="invalid-feedback" id="edit-first-name-error"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tên <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" id="edit-name" class="form-control" required>
                                        <div class="invalid-feedback" id="edit-name-error"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                        <input type="tel" name="phone" id="edit-phone" class="form-control" required>
                                        <div class="invalid-feedback" id="edit-phone-error"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Ngày sinh</label>
                                        <input type="text" name="date_of_birth" id="edit-date-of-birth" class="form-control" 
                                               placeholder="dd/mm/yyyy" pattern="\\d{2}/\\d{2}/\\d{4}" 
                                               title="Nhập ngày theo định dạng dd/mm/yyyy">
                                        <div class="invalid-feedback" id="edit-date-of-birth-error"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nơi sinh</label>
                                        <input type="hidden" name="place_of_birth" id="edit-place-of-birth">
                                        <select id="edit-place-of-birth-select" class="form-select" data-placeholder="Chọn nơi sinh (tỉnh/thành)">
                                            <option value="">-- Chọn nơi sinh --</option>
                                        </select>
                                        <div class="invalid-feedback" id="edit-place-of-birth-error"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Dân tộc</label>
                                        <input type="hidden" name="nation" id="edit-nation">
                                        <select id="edit-nation-select" class="form-select" data-placeholder="Chọn dân tộc">
                                            <option value="">-- Chọn dân tộc --</option>
                                        </select>
                                        <div class="invalid-feedback" id="edit-nation-error"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" id="edit-email" class="form-control">
                                        <div class="invalid-feedback" id="edit-email-error"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Giới tính</label>
                                        <select name="gender" id="edit-gender" class="form-select">
                                            <option value="">Chọn giới tính</option>
                                            <option value="male">Nam</option>
                                            <option value="female">Nữ</option>
                                            <option value="other">Khác</option>
                                        </select>
                                        <div class="invalid-feedback" id="edit-gender-error"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Tỉnh/Thành phố</label>
                                        <select name="province_id" id="edit-province" class="form-select">
                                            <option value="">-- Chọn tỉnh thành --</option>
                                        </select>
                                        <div class="invalid-feedback" id="edit-province-error"></div>
                                    </div>

                                   
                                </div>

                                <!-- Thông tin bổ sung -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <h6 class="border-bottom pb-2 mb-3">
                                            <i class="fas fa-briefcase me-2"></i>Thông tin bổ sung
                                        </h6>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nơi công tác hiện tại</label>
                                        <input type="text" name="current_workplace" id="edit-current-workplace" class="form-control" placeholder="Nhập nơi công tác hiện tại">
                                        <div class="invalid-feedback" id="edit-current-workplace-error"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Số năm kinh nghiệm kế toán</label>
                                        <input type="number" name="accounting_experience_years" id="edit-experience" class="form-control" min="0" max="50">
                                        <div class="invalid-feedback" id="edit-experience-error"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Hồ sơ bản cứng</label>
                                        <select name="hard_copy_documents" id="edit-hard-copy-documents" class="form-select">
                                            <option value="">-- Chọn trạng thái --</option>
                                            <option value="submitted">Đã nộp</option>
                                            <option value="not_submitted">Chưa nộp</option>
                                        </select>
                                        <div class="invalid-feedback" id="edit-hard-copy-documents-error"></div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Bằng cấp</label>
                                        <select name="education_level" id="edit-education-level" class="form-select">
                                            <option value="">-- Chọn bằng cấp --</option>
                                            <option value="secondary">VB2</option>
                                            <option value="vocational">Trung cấp</option>
                                            <option value="associate">Cao đẳng</option>
                                            <option value="bachelor">Đại học</option>
                                            <option value="master">Thạc sĩ</option>
                                        </select>
                                        <div class="invalid-feedback" id="edit-education-level-error"></div>
                                    </div>
                                </div>

                                <!-- Ghi chú -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <label class="form-label">Ghi chú</label>
                                        <textarea name="notes" id="edit-notes" class="form-control" rows="3" placeholder="Nhập ghi chú về học viên (nếu có)"></textarea>
                                        <div class="invalid-feedback" id="edit-notes-error"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="button" id="saveEditStudentBtn" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Cập nhật học viên
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>`);
    }

    // Mở modal và load dữ liệu
    $('#editStudentModal').modal('show');
    $('#editStudentLoading').show();
    $('#editStudentContent').hide();
    
    // Clear previous errors
    $('#editStudentModal .is-invalid').removeClass('is-invalid');
    $('#editStudentModal .invalid-feedback').text('');

    // Load student data
    $.ajax({
        url: `/api/students/${studentId}/details`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const student = response.data;
                
                // Store student data for later use
                $('#editStudentModal').data('student-data', student);
                $('#editStudentModal').data('student-id', studentId);

                // Populate basic form fields first
                $('#edit-first-name').val(student.first_name || '');
                $('#edit-name').val(student.last_name || '');
                $('#edit-phone').val(student.phone || '');
                $('#edit-date-of-birth').val(student.formatted_date_of_birth || formatDate(student.date_of_birth) || '');
                $('#edit-email').val(student.email || '');
                $('#edit-gender').val(student.gender || '');
                $('#edit-address').val(student.address || '');
                $('#edit-current-workplace').val(student.current_workplace || '');
                $('#edit-experience').val(student.accounting_experience_years || '');
                $('#edit-hard-copy-documents').val(student.hard_copy_documents || '');
                $('#edit-education-level').val(student.education_level || '');
                $('#edit-notes').val(student.notes || '');

                // Initialize select2 and then set values
                setTimeout(function() {
                    initEditStudentSelect2(student);
                }, 100);

                $('#editStudentLoading').hide();
                $('#editStudentContent').show();
                
                // Auto focus vào trường đầu tiên
                setTimeout(function() {
                    $('#edit-first-name').focus();
                }, 200);
            } else {
                showToast('Không thể tải thông tin học viên: ' + (response.message || 'Lỗi không xác định'), 'error');
                $('#editStudentModal').modal('hide');
            }
        },
        error: function(xhr) {
            showToast('Có lỗi xảy ra khi tải thông tin học viên', 'error');
            $('#editStudentModal').modal('hide');
        }
    });

    // Handle save button
    $('#saveEditStudentBtn').off('click').on('click', function() {
        const button = $(this);
        const form = $('#editStudentForm');
        const formData = new FormData(form[0]);
        const studentId = $('#editStudentModal').data('student-id');
        
        // Disable button và hiển thị loading
        button.prop('disabled', true);
        button.html('<i class="fas fa-spinner fa-spin me-1"></i>Đang cập nhật...');
        
        // Clear previous errors
        $('#editStudentModal .is-invalid').removeClass('is-invalid');
        $('#editStudentModal .invalid-feedback').text('');
        
        // Show loading overlay
        $('#editStudentModal .modal-body').append('<div id="saveLoadingOverlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.8); z-index: 1000; display: flex; align-items: center; justify-content: center;"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Đang lưu...</span></div></div>');
        
        $.ajax({
                         url: `/api/students/${studentId}/update`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            success: function(response) {
                if (response.success) {
                    // Hiển thị thông báo thành công
                    if (window.toastr && toastr.success) {
                        toastr.success(response.message || 'Cập nhật học viên thành công!');
                    } else {
                        showToast(response.message || 'Cập nhật học viên thành công!', 'success');
                    }
                    
                    // Đóng modal
                    $('#editStudentModal').modal('hide');
                    
                    // Refresh danh sách học viên nếu modal students đang mở
                    const courseId = $('#studentsModal').data('course-id');
                    if ($('#studentsModal').hasClass('show') && courseId) {
                        openStudentsModal(courseId);
                    }
                } else {
                    if (window.toastr && toastr.error) {
                        toastr.error(response.message || 'Có lỗi xảy ra!');
                    } else {
                        showToast(response.message || 'Có lỗi xảy ra!', 'error');
                    }
                }
            },
            error: function(xhr) {
                                 if (xhr.status === 422) {
                     // Validation errors
                     const errors = xhr.responseJSON.errors;
                     for (const field in errors) {
                         const input = $(`#editStudentModal [name="${field}"]`);
                         input.addClass('is-invalid');
                         $(`#edit-${field.replace('_', '-')}-error`).text(errors[field][0]);
                     }
                    if (window.toastr && toastr.error) {
                        toastr.error('Vui lòng kiểm tra lại thông tin!');
                    } else {
                        showToast('Vui lòng kiểm tra lại thông tin!', 'warning');
                    }
                } else {
                    if (window.toastr && toastr.error) {
                        toastr.error('Có lỗi xảy ra khi cập nhật học viên!');
                    } else {
                        showToast('Có lỗi xảy ra khi cập nhật học viên!', 'error');
                    }
                }
            },
                         complete: function() {
                 // Reset button
                 button.prop('disabled', false);
                 button.html('<i class="fas fa-save me-1"></i> Cập nhật học viên');
                 
                 // Remove loading overlay
                 $('#saveLoadingOverlay').remove();
             }
        });
    });
}

// Initialize select2 for edit student modal
function initEditStudentSelect2(student) {
    // Initialize provinces select2
    $('#edit-province').select2({
        theme: 'bootstrap-5',
        placeholder: 'Chọn tỉnh/thành phố',
        allowClear: true,
        dropdownParent: $('#editStudentModal'),
        width: '100%',
        ajax: {
            url: '/api/provinces',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { keyword: params.term || '' };
            },
            processResults: function(response) {
                if (response && response.success && Array.isArray(response.data)) {
                    return {
                        results: response.data.map(function(item) {
                            return { 
                                id: item.id, 
                                text: item.name + ' (' + getRegionName(item.region) + ')' 
                            };
                        })
                    };
                }
                return { results: [] };
            }
        }
    });

    // Initialize place of birth select2
    $('#edit-place-of-birth-select').select2({
        theme: 'bootstrap-5',
        placeholder: 'Chọn nơi sinh (tỉnh/thành)',
        allowClear: true,
        dropdownParent: $('#editStudentModal'),
        width: '100%',
        ajax: {
            url: '/api/provinces',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { keyword: params.term || '' };
            },
            processResults: function(response) {
                if (response && response.success && Array.isArray(response.data)) {
                    return {
                        results: response.data.map(function(item) {
                            return { 
                                id: item.id, 
                                text: item.name + ' (' + getRegionName(item.region) + ')' 
                            };
                        })
                    };
                }
                return { results: [] };
            }
        }
    });

    // Initialize ethnicity select2
    $('#edit-nation-select').select2({
        theme: 'bootstrap-5',
        placeholder: 'Chọn dân tộc',
        allowClear: true,
        dropdownParent: $('#editStudentModal'),
        width: '100%',
        ajax: {
            url: '/api/ethnicities',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { keyword: params.term || '' };
            },
            processResults: function(response) {
                if (response && response.success && Array.isArray(response.data)) {
                    return {
                        results: response.data.map(function(item) {
                            return { id: item.id, text: item.name };
                        })
                    };
                }
                return { results: [] };
            }
        }
    });

    // Sync select2 with hidden inputs
    $('#edit-place-of-birth-select').on('select2:select', function(e) {
        const text = e.params.data && e.params.data.text ? e.params.data.text : '';
        $('#edit-place-of-birth').val(text);
    }).on('select2:clear', function() {
        $('#edit-place-of-birth').val('');
    });

    $('#edit-nation-select').on('select2:select', function(e) {
        const text = e.params.data && e.params.data.text ? e.params.data.text : '';
        $('#edit-nation').val(text);
    }).on('select2:clear', function() {
        $('#edit-nation').val('');
    });

    // Set values after initialization if student data is provided
    if (student) {
        // Set province value
        if (student.province_id) {
            // Load province data and set it
            $.get(`/api/provinces/${student.province_id}`, function(response) {
                if (response.success) {
                    const province = response.data;
                    const option = new Option(province.name + ' (' + getRegionName(province.region) + ')', province.id, true, true);
                    $('#edit-province').append(option).trigger('change');
                }
            });
        }

        // Set place of birth
        if (student.place_of_birth) {
            $('#edit-place-of-birth').val(student.place_of_birth);
            // Try to find matching province for place of birth
            $.get('/api/provinces', { keyword: student.place_of_birth }, function(response) {
                if (response.success && response.data.length > 0) {
                    const province = response.data[0];
                    const option = new Option(province.name + ' (' + getRegionName(province.region) + ')', province.id, true, true);
                    $('#edit-place-of-birth-select').append(option).trigger('change');
                }
            });
        }

        // Set nation/ethnicity
        if (student.nation) {
            $('#edit-nation').val(student.nation);
            // Try to find matching ethnicity
            $.get('/api/ethnicities', { keyword: student.nation }, function(response) {
                if (response.success && response.data.length > 0) {
                    const ethnicity = response.data[0];
                    const option = new Option(ethnicity.name, ethnicity.id, true, true);
                    $('#edit-nation-select').append(option).trigger('change');
                }
            });
        }
    }
}

// Helper function for region names
function getRegionName(region) {
    switch(region) {
        case 'north': return 'Miền Bắc';
        case 'central': return 'Miền Trung';
        case 'south': return 'Miền Nam';
        default: return 'Không xác định';
    }
}

// Modal import Excel học viên
function openImportExcelModal(courseId) {
    // Tạo modal nếu chưa có
    if ($('#importExcelModal').length === 0) {
        $('body').append(`
        <div class="modal fade" id="importExcelModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Import Excel học viên</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="importExcelForm" enctype="multipart/form-data">
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Hướng dẫn:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Tải file template Excel mẫu để xem định dạng yêu cầu</li>
                                    <li>Điền thông tin học viên vào file Excel theo đúng định dạng</li>
                                    <li>Upload file Excel đã điền thông tin</li>
                                </ul>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Tải template Excel mẫu</label>
                                <div>
                                    <a href="/course-items/download-template" class="btn btn-outline-primary btn-sm" target="_blank">
                                        <i class="fas fa-download me-1"></i>Tải template Excel
                                    </a>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="excel_file" class="form-label">Chọn file Excel <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls" required>
                                <div class="form-text">Chỉ chấp nhận file Excel (.xlsx, .xls)</div>
                                <div class="invalid-feedback" id="excel-file-error"></div>
                            </div>

                            <div class="mb-3">
                                <label for="discount_percentage" class="form-label">Phần trăm giảm giá (%)</label>
                                <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" 
                                       min="0" max="100" step="0.01" value="0">
                                <div class="form-text">Áp dụng giảm giá cho tất cả học viên được import (tùy chọn)</div>
                                <div class="invalid-feedback" id="discount-percentage-error"></div>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Lưu ý:</strong> Quá trình import có thể mất vài phút tùy thuộc vào số lượng học viên.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                            <button type="submit" class="btn btn-success" id="importBtn">
                                <i class="fas fa-upload me-1"></i>Import Excel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>`);
    }

    // Store course ID
    $('#importExcelModal').data('course-id', courseId);

    // Reset form
    $('#importExcelForm')[0].reset();
    $('#importExcelModal .is-invalid').removeClass('is-invalid');
    $('#importExcelModal .invalid-feedback').text('');

    // Show modal
    $('#importExcelModal').modal('show');

    // Handle form submission
    $('#importExcelForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        const form = this;
        const formData = new FormData(form);
        const courseId = $('#importExcelModal').data('course-id');
        const submitBtn = $('#importBtn');
        
        // Validate file
        const fileInput = $('#excel_file')[0];
        if (!fileInput.files.length) {
            $('#excel_file').addClass('is-invalid');
            $('#excel-file-error').text('Vui lòng chọn file Excel');
            return;
        }

        // Check file type
        const fileName = fileInput.files[0].name;
        const fileExtension = fileName.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls'].includes(fileExtension)) {
            $('#excel_file').addClass('is-invalid');
            $('#excel-file-error').text('Chỉ chấp nhận file Excel (.xlsx, .xls)');
            return;
        }

        // Clear previous errors
        $('#importExcelModal .is-invalid').removeClass('is-invalid');
        $('#importExcelModal .invalid-feedback').text('');

        // Show loading state
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Đang import...');

        // Add loading overlay
        $('#importExcelModal .modal-body').append(`
            <div id="importLoadingOverlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.9); z-index: 1000; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                <div class="spinner-border text-success mb-3" role="status">
                    <span class="visually-hidden">Đang import...</span>
                </div>
                <div class="text-center">
                    <strong>Đang import học viên...</strong><br>
                    <small class="text-muted">Vui lòng không đóng cửa sổ này</small>
                </div>
            </div>
        `);

        // Submit form
        $.ajax({
            url: `/course-items/${courseId}/import-students`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            success: function(response) {
                // Close import modal
                $('#importExcelModal').modal('hide');
                
                // Show success message
                if (window.toastr && toastr.success) {
                    toastr.success('Import Excel thành công!');
                } else {
                    showToast('Import Excel thành công!', 'success');
                }
                
                // Refresh students list
                if ($('#studentsModal').hasClass('show')) {
                    openStudentsModal(courseId);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    for (const field in errors) {
                        const input = $(`#importExcelModal [name="${field}"]`);
                        input.addClass('is-invalid');
                        $(`#${field.replace('_', '-')}-error`).text(errors[field][0]);
                    }
                    
                    if (window.toastr && toastr.error) {
                        toastr.error('Vui lòng kiểm tra lại thông tin!');
                    } else {
                        showToast('Vui lòng kiểm tra lại thông tin!', 'warning');
                    }
                } else {
                    const message = xhr.responseJSON && xhr.responseJSON.message 
                        ? xhr.responseJSON.message 
                        : 'Có lỗi xảy ra khi import Excel';
                    
                    if (window.toastr && toastr.error) {
                        toastr.error(message);
                    } else {
                        showToast(message, 'error');
                    }
                }
            },
            complete: function() {
                // Reset button
                submitBtn.prop('disabled', false);
                submitBtn.html('<i class="fas fa-upload me-1"></i>Import Excel');
                
                // Remove loading overlay
                $('#importLoadingOverlay').remove();
            }
        });
    });
}

// ========== CHỨC NĂNG QUẢN LÝ TRẠNG THÁI KHÓA HỌC ==========

// Xử lý toggle trạng thái khóa học
$(document).on('click', '.toggle-course-status', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const $btn = $(this);
    const courseId = $btn.data('course-id');
    const currentStatus = $btn.data('current-status');
    
    // Xác nhận trước khi thay đổi
    const action = currentStatus === 'active' ? 'kết thúc' : 'mở lại';
    const message = currentStatus === 'active' 
        ? 'Bạn có chắc muốn kết thúc khóa học này?\nTất cả học viên đang học sẽ được chuyển sang trạng thái "Hoàn thành".'
        : 'Bạn có chắc muốn mở lại khóa học này?\nTất cả học viên đã hoàn thành sẽ được chuyển về trạng thái "Đang học".';
    
    if (!confirm(message)) {
        return;
    }
    
    // Disable button và hiển thị loading
    const originalHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
    
    // Gọi API
    $.ajax({
        url: `/course-items/${courseId}/toggle-status`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                // Hiển thị thông báo thành công
                if (window.toastr && toastr.success) {
                    toastr.success(response.message);
                } else {
                    showToast(response.message, 'success');
                }
                
                // Cập nhật UI
                const newStatus = response.data.status;
                const $courseLink = $btn.closest('.tree-item').find('.course-link');
                
                // Cập nhật badge trong tên khóa học
                const courseName = $courseLink.text().trim().split('\n')[0]; // Lấy tên khóa học không có badge
                $courseLink.html(courseName + ' ' + response.data.status_badge);
                
                // Cập nhật button
                $btn.removeClass('btn-warning btn-success')
                    .addClass(newStatus === 'active' ? 'btn-warning' : 'btn-success')
                    .attr('title', newStatus === 'active' ? 'Kết thúc khóa học' : 'Mở lại khóa học')
                    .attr('data-current-status', newStatus)
                    .html(`<i class="fas fa-${newStatus === 'active' ? 'stop' : 'play'}"></i>`);
                
                // Log cho debugging
                console.log('Course status updated:', response.data);
                
            } else {
                // Hiển thị lỗi
                if (window.toastr && toastr.error) {
                    toastr.error(response.message);
                } else {
                    showToast('Lỗi: ' + response.message, 'error');
                }
            }
        },
        error: function(xhr) {
            console.error('Toggle status error:', xhr.responseJSON);
            let errorMsg = 'Có lỗi xảy ra khi thay đổi trạng thái khóa học';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            
            if (window.toastr && toastr.error) {
                toastr.error(errorMsg);
            } else {
                showToast('Lỗi: ' + errorMsg, 'error');
            }
        },
        complete: function() {
            // Reset button state
            $btn.prop('disabled', false);
            if ($btn.html().includes('fa-spinner')) {
                $btn.html(originalHtml);
            }
        }
    });
});

// Chức năng kết thúc khóa học (có thể gọi riêng)
function completeCourse(courseId) {
    if (!confirm('Bạn có chắc muốn kết thúc khóa học này?\nTất cả học viên đang học sẽ được chuyển sang trạng thái "Hoàn thành".')) {
        return;
    }
    
    $.ajax({
        url: `/course-items/${courseId}/complete`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                if (window.toastr && toastr.success) {
                    toastr.success(response.message);
                } else {
                    showToast(response.message, 'success');
                }
                
                // Refresh trang hoặc cập nhật UI
                location.reload();
            } else {
                if (window.toastr && toastr.error) {
                    toastr.error(response.message);
                } else {
                    showToast('Lỗi: ' + response.message, 'error');
                }
            }
        },
        error: function(xhr) {
            console.error('Complete course error:', xhr.responseJSON);
            let errorMsg = 'Có lỗi xảy ra khi kết thúc khóa học';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            
            if (window.toastr && toastr.error) {
                toastr.error(errorMsg);
            } else {
                showToast('Lỗi: ' + errorMsg, 'error');
            }
        }
    });
}

// Chức năng mở lại khóa học (có thể gọi riêng)
function reopenCourse(courseId) {
    if (!confirm('Bạn có chắc muốn mở lại khóa học này?')) {
        return;
    }
    
    $.ajax({
        url: `/course-items/${courseId}/reopen`,
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                if (window.toastr && toastr.success) {
                    toastr.success(response.message);
                } else {
                    showToast(response.message, 'success');
                }
                
                // Refresh trang hoặc cập nhật UI
                location.reload();
            } else {
                if (window.toastr && toastr.error) {
                    toastr.error(response.message);
                } else {
                    showToast('Lỗi: ' + response.message, 'error');
                }
            }
        },
        error: function(xhr) {
            console.error('Reopen course error:', xhr.responseJSON);
            let errorMsg = 'Có lỗi xảy ra khi mở lại khóa học';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            }
            
            if (window.toastr && toastr.error) {
                toastr.error(errorMsg);
            } else {
                showToast('Lỗi: ' + errorMsg, 'error');
            }
        }
    });
} 