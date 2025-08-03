// Biến lưu ID khóa học đang xem chi tiết
let currentCourseId = null;

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
            $('#btn-students').attr('href', `/course-items/${courseId}/students`);
            $('#btn-attendance').attr('href', `/course-items/${courseId}/attendance`);
            $('#btn-payments').attr('href', `/payments/course/${courseId}`);
            
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
    // Kích hoạt tab đầu tiên khi tải trang
    const firstTab = document.querySelector('#courseTab .nav-link');
    if (firstTab) {
        new bootstrap.Tab(firstTab).show();
    }

    // Xử lý sự kiện click cho các liên kết khóa học
    $(document).on('click', '.course-link', function(e) {
        e.preventDefault();
        const courseId = $(this).data('id');
        console.log("Đã click vào khóa học có ID:", courseId);
        showCourseDetails(courseId);
    });
    
    // Xử lý sự kiện click cho nút chỉnh sửa trong modal chi tiết
    $(document).on('click', '#btn-edit-from-modal', function() {
        // Đóng modal chi tiết và mở modal chỉnh sửa
        $('#viewCourseModal').modal('hide');
        setupEditModal(currentCourseId);
    });
    
    // Kiểm tra modal và Bootstrap
    console.log("Bootstrap có sẵn:", typeof bootstrap !== 'undefined');
    console.log("Bootstrap.Modal có sẵn:", typeof bootstrap !== 'undefined' && typeof bootstrap.Modal !== 'undefined');
    console.log("Modal có tồn tại:", $('#viewCourseModal').length > 0);
    
    // Cập nhật URL khi chuyển tab
    $('.course-tabs .nav-link').on('shown.bs.tab', function (e) {
        const rootId = $(this).attr('id').replace('tab-', '');
        const url = new URL(window.location);
        url.searchParams.set('root_id', rootId);
        window.history.pushState({}, '', url);
        
        // Xóa kết quả tìm kiếm khi chuyển tab
        clearSearch();
    });
    
    // Mở rộng tất cả
    $('#expand-all').click(function() {
        $('.course-tree .collapse').collapse('show');
        // Chỉ thay đổi icon của các phần tử có collapse
        $('.toggle-icon').each(function() {
            if($(this).closest('.tree-item').siblings('.collapse').length > 0) {
                $(this).find('i').removeClass('fa-plus-circle').addClass('fa-minus-circle');
            }
        });
    });
    
    // Thu gọn tất cả
    $('#collapse-all').click(function() {
        $('.course-tree .collapse').collapse('hide');
        // Chỉ thay đổi icon của các phần tử có collapse
        $('.toggle-icon').each(function() {
            if($(this).closest('.tree-item').siblings('.collapse').length > 0) {
                $(this).find('i').removeClass('fa-minus-circle').addClass('fa-plus-circle');
            }
        });
    });
    
    // Thay đổi icon khi mở/đóng
    $('.collapse').on('show.bs.collapse', function() {
        // Chỉ thay đổi icon của phần tử được click
        $(this).siblings('.tree-item').find('.toggle-icon > i').removeClass('fa-plus-circle').addClass('fa-minus-circle');
    });
    
    $('.collapse').on('hide.bs.collapse', function() {
        // Chỉ thay đổi icon của phần tử được click
        $(this).siblings('.tree-item').find('.toggle-icon > i').removeClass('fa-minus-circle').addClass('fa-plus-circle');
    });
    
    // Thu gọn các cấp sâu hơn khi tải trang
    $('.course-tree li li li ul').collapse('hide');
    // Chỉ thay đổi icon của các phần tử có collapse
    $('.course-tree li li li .tree-item').each(function() {
        if($(this).siblings('.collapse').length > 0) {
            $(this).find('.toggle-icon > i').removeClass('fa-minus-circle').addClass('fa-plus-circle');
        }
    });
    
    // Kiểm tra URL có chứa tham số newly_added_id không
    const urlParams = new URLSearchParams(window.location.search);
    const newlyAddedId = urlParams.get('newly_added_id');
    
    if (newlyAddedId) {
        console.log("Tìm phần tử với ID:", newlyAddedId);
        // Tìm phần tử mới thêm và mở rộng tất cả các nhánh cha
        const newItem = $(`div.tree-item[data-id="${newlyAddedId}"]`).first();
        if (newItem.length) {
            console.log("Đã tìm thấy phần tử:", newItem);
            // Mở tất cả các nhánh cha
            const parentCollapses = newItem.parents('ul.collapse');
            parentCollapses.each(function() {
                $(this).collapse('show');
                console.log("Mở rộng phần tử cha:", $(this).attr('id'));
            });
            
            // Chỉ thay đổi icon của các phần tử cha trực tiếp
            newItem.parents('li').each(function() {
                const toggleIcon = $(this).children('.tree-item').find('.toggle-icon > i');
                toggleIcon.removeClass('fa-plus-circle').addClass('fa-minus-circle');
                console.log("Thay đổi icon cho phần tử:", $(this).children('.tree-item').text().trim());
            });
            
            // Cuộn đến phần tử mới
            setTimeout(() => {
                $('html, body').animate({
                    scrollTop: newItem.offset().top - 100
                }, 500);
                
                // Highlight phần tử mới
                newItem.addClass('highlight');
                setTimeout(() => {
                    newItem.removeClass('highlight');
                }, 3000);
            }, 500); // Đợi 500ms để các collapse hoàn thành
        } else {
            console.log("Không tìm thấy phần tử với ID:", newlyAddedId);
        }
    }
    
    // Xử lý hiển thị/ẩn các tùy chọn cho nút lá
    $('#is-leaf').change(function() {
        if ($(this).is(':checked')) {
            $('#leaf-options').slideDown();
            // Vô hiệu hóa hidden input khi checkbox được chọn
            $(this).next('input[type=hidden]').prop('disabled', true);
        } else {
            $('#leaf-options').slideUp();
            // Kích hoạt hidden input khi checkbox không được chọn
            $(this).next('input[type=hidden]').prop('disabled', false);
        }
    });
    
    // Xử lý hiển thị/ẩn các tùy chọn cho nút lá trong form chỉnh sửa
    $('#edit-is-leaf').change(function() {
        if ($(this).is(':checked')) {
            $('#edit-leaf-options').slideDown();
            // Vô hiệu hóa hidden input khi checkbox được chọn
            $(this).next('input[type=hidden]').prop('disabled', true);
        } else {
            $('#edit-leaf-options').slideUp();
            // Kích hoạt hidden input khi checkbox không được chọn
            $(this).next('input[type=hidden]').prop('disabled', false);
        }
    });
    
    // Xử lý các checkbox khác
    $('input[type=checkbox]').change(function() {
        if ($(this).is(':checked')) {
            // Vô hiệu hóa hidden input khi checkbox được chọn
            $(this).next('input[type=hidden]').prop('disabled', true);
        } else {
            // Kích hoạt hidden input khi checkbox không được chọn
            $(this).next('input[type=hidden]').prop('disabled', false);
        }
    });
    
    // Kích hoạt sự kiện change cho tất cả checkbox khi trang tải
    $('input[type=checkbox]').trigger('change');

    // -----------------------------
    // Xử lý tìm kiếm khóa học
    // -----------------------------
    
    let searchTimeout;
    let currentSearchResults = [];
    
    // Xử lý sự kiện nhập vào ô tìm kiếm
    $('#course-search').on('input', function() {
        const searchTerm = $(this).val().trim();
        
        if (searchTerm.length < 2) {
            hideSearchResults();
            $('.search-clear').hide();
            return;
        }
        
        $('.search-clear').show();
        
        // Tránh gửi quá nhiều request khi đang nhập
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(searchTerm);
        }, 300);
    });
    
    // Xử lý khi click nút xóa
    $('.search-clear').on('click', function() {
        clearSearch();
    });
    
    // Xử lý click bên ngoài để đóng kết quả tìm kiếm
    $(document).on('click', function(event) {
        if (!$(event.target).closest('.search-container').length) {
            hideSearchResults();
        }
    });
    
    // Hàm thực hiện tìm kiếm
    function performSearch(term) {
        // Lấy ID của tab đang active để chỉ tìm trong phạm vi của tab đó
        const activeTabPane = $('.tab-pane.active');
        const rootId = activeTabPane.data('root-id');
        
        $.ajax({
            url: '/api/course-items/search',
            method: 'GET',
            data: {
                q: term,
                root_id: rootId
            },
            beforeSend: function() {
                // Hiển thị trạng thái đang tìm kiếm
                const searchResults = $('.search-results');
                searchResults.html('<div class="p-3 text-center text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Đang tìm kiếm...</div>');
                searchResults.addClass('show');
            },
            success: function(data) {
                currentSearchResults = data;
                renderSearchResults(data);
            },
            error: function(error) {
                console.error('Lỗi khi tìm kiếm:', error);
                $('.search-results').html('<div class="p-3 text-center text-danger">Có lỗi xảy ra khi tìm kiếm</div>');
            }
        });
    }
    
    // Hiển thị kết quả tìm kiếm
    function renderSearchResults(results) {
        const searchResults = $('.search-results');
        
        if (results.length === 0) {
            searchResults.html('<div class="p-3 text-center text-muted">Không tìm thấy khóa học nào</div>');
            searchResults.addClass('show');
            return;
        }
        
        let html = '';
        
        results.forEach(function(item) {
            let pathHtml = item.path ? `<div class="search-result-path">${item.path}</div>` : '';
            let feeHtml = item.fee > 0 ? `<div class="search-result-fee">${formatCurrency(item.fee)} đ</div>` : '';
            
            html += `
                <div class="search-result-item" data-id="${item.id}">
                    <div class="search-result-name">${item.text}</div>
                    ${pathHtml}
                    ${feeHtml}
                </div>
            `;
        });
        
        searchResults.html(html);
        searchResults.addClass('show');
        
        // Xử lý khi click vào một kết quả
        $('.search-result-item').on('click', function() {
            const courseId = $(this).data('id');
            navigateToCourse(courseId);
        });
    }
    
    // Di chuyển đến khóa học được chọn
    function navigateToCourse(courseId) {
        const courseItem = $(`div.tree-item[data-id="${courseId}"]`).first();
        
        if (courseItem.length) {
            // Mở tất cả các nhánh cha
            const parentCollapses = courseItem.parents('ul.collapse');
            parentCollapses.each(function() {
                $(this).collapse('show');
            });
            
            // Thay đổi icon của các phần tử cha
            courseItem.parents('li').each(function() {
                const toggleIcon = $(this).children('.tree-item').find('.toggle-icon > i');
                toggleIcon.removeClass('fa-plus-circle').addClass('fa-minus-circle');
            });
            
            // Xóa highlight trước đó nếu có
            $('.tree-item.highlight').removeClass('highlight');
            
            // Cuộn đến phần tử và highlight
            setTimeout(() => {
                $('html, body').animate({
                    scrollTop: courseItem.offset().top - 100
                }, 500, function() {
                    // Highlight phần tử sau khi cuộn hoàn tất
                    courseItem.addClass('highlight');
                    
                    // Xóa class highlight sau 3 giây
                    setTimeout(() => {
                        courseItem.removeClass('highlight');
                    }, 3000);
                });
            }, 300);
            
            // Đóng kết quả tìm kiếm
            hideSearchResults();
        } else {
            // Nếu không tìm thấy trong DOM, có thể là ở tab khác
            // Tìm khóa học này trong kết quả tìm kiếm
            const courseResult = currentSearchResults.find(item => item.id === courseId);
            if (courseResult) {
                // Đóng kết quả tìm kiếm
                hideSearchResults();
                
                // Hiển thị thông báo
                showToast(`Đang chuyển đến ${courseResult.text}...`, 'info');
                
                // Redirect đến trang chi tiết khóa học
                window.location.href = `/course-items/${courseId}`;
            }
        }
    }
    
    // Ẩn kết quả tìm kiếm
    function hideSearchResults() {
        $('.search-results').removeClass('show');
    }
    
    // Xóa nội dung tìm kiếm
    function clearSearch() {
        $('#course-search').val('');
        hideSearchResults();
        $('.search-clear').hide();
    }
    
    // Xử lý phím tắt
    $('#course-search').on('keydown', function(e) {
        // ESC: Đóng kết quả tìm kiếm
        if (e.keyCode === 27) {
            clearSearch();
            $(this).blur();
        }
        
        // Enter: Chọn kết quả đầu tiên
        if (e.keyCode === 13) {
            const firstResult = $('.search-result-item').first();
            if (firstResult.length) {
                firstResult.click();
            }
        }
    });
    
    // Cho phép kéo thả các mục cấp root
    $(".course-tree.sortable-tree").sortable({
        items: "> li",
        handle: ".sort-handle",
        placeholder: "tree-item ui-state-highlight",
        update: function(event, ui) {
            const items = [];
            $(this).children("li").each(function(index) {
                const id = $(this).find("> .tree-item").data("id");
                items.push({
                    id: id,
                    order: index + 1
                });
            });

            // Gửi Ajax để cập nhật thứ tự
            updateItemsOrder(items);
        }
    });

    // Cho phép kéo thả các mục cùng cấp (con)
    $(".collapse.show").each(function() {
        $(this).sortable({
            items: "> li",
            handle: ".sort-handle",
            placeholder: "tree-item ui-state-highlight",
            connectWith: ".collapse.show",
            update: function(event, ui) {
                // Chỉ xử lý một lần khi kết thúc kéo thả
                if (this === ui.item.parent()[0]) {
                    const items = [];
                    $(this).children("li").each(function(index) {
                        const id = $(this).find("> .tree-item").data("id");
                        items.push({
                            id: id,
                            order: index + 1
                        });
                    });

                    // Cập nhật parent_id nếu đã di chuyển sang nhóm khác
                    const newParentId = $(this).attr('id').replace(/^(tab-)?children-/, '');
                    
                    // Gửi Ajax để cập nhật thứ tự và parent nếu cần
                    updateItemsOrder(items, newParentId);
                }
            }
        });
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
    $('#parent-id-input').val(parentId);
    $('#addItemModal .modal-title').text('Thêm khóa học con cho: ' + parentName);
    $('#addItemModal').modal('show');
}

// Thiết lập modal chỉnh sửa khóa học
function setupEditModal(id) {
    // Ẩn modal xem chi tiết nếu đang mở
    if ($('#viewCourseModal').hasClass('show')) {
        $('#viewCourseModal').modal('hide');
    }
    
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
    $('#edit-parent-id').val(parentId);
    $('#edit-item-name').val(name);
    $('#edit-is-leaf').prop('checked', isLeaf);
    $('#edit-item-active').prop('checked', isActive);
    $('#edit-item-fee').val(fee);
    
    // Đặt trạng thái checkbox "Đặt làm khóa chính"
    if ($('#edit-make-root').length > 0) {
        $('#edit-make-root').prop('checked', !parentId);
        handleEditRootToggle();
    }
    
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

// Xử lý tương tác giữa checkbox "Đặt làm khóa chính" và dropdown chọn cha trong modal
function handleEditRootToggle() {
    const makeRootChecked = $('#edit-make-root').is(':checked');
    if (makeRootChecked) {
        // Nếu đặt làm khóa chính, vô hiệu hóa dropdown chọn cha
        $('#edit-parent-id').prop('disabled', true);
        $('#edit-parent-id').val('');
        $('#edit-root-info').slideDown();
    } else {
        // Nếu không đặt làm khóa chính, kích hoạt dropdown chọn cha
        $('#edit-parent-id').prop('disabled', false);
        $('#edit-root-info').slideUp();
    }
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