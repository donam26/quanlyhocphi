@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Cấu trúc cây khóa học</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('course-items.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4>Cây ngành - khóa học - lớp học</h4>
                <div>
                    <button class="btn btn-sm btn-info me-2" id="expand-all">
                        <i class="fas fa-expand-arrows-alt"></i> Mở rộng tất cả
                    </button>
                    <button class="btn btn-sm btn-secondary me-2" id="collapse-all">
                        <i class="fas fa-compress-arrows-alt"></i> Thu gọn tất cả
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addRootItemModal">
                        <i class="fas fa-plus"></i> Thêm ngành mới
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($rootItems->isEmpty())
                <div class="alert alert-info">
                    Chưa có ngành học nào được tạo. Hãy tạo ngành học đầu tiên.
                </div>
            @else
                <!-- Tab navigation -->
                <ul class="nav nav-tabs mb-3" id="courseTab" role="tablist">
                    @foreach($rootItems as $index => $rootItem)
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $index === 0 ? 'active' : '' }}" id="tab-{{ $rootItem->id }}" data-bs-toggle="tab" data-bs-target="#content-{{ $rootItem->id }}" type="button" role="tab" aria-controls="content-{{ $rootItem->id }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                {{ $rootItem->name }}
                            </button>
                        </li>
                    @endforeach
                </ul>
                
                <!-- Tab content -->
                <div class="tab-content" id="courseTabContent">
                    @foreach($rootItems as $index => $rootItem)
                        <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="content-{{ $rootItem->id }}" role="tabpanel" aria-labelledby="tab-{{ $rootItem->id }}">
                            <div class="tree-container">
                                <ul class="course-tree">
                                    <li>
                                        <div class="tree-item level-1 {{ $rootItem->active ? 'active' : 'inactive' }}" data-id="{{ $rootItem->id }}">
                                            <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#tab-children-{{ $rootItem->id }}">
                                                <i class="fas fa-minus-circle"></i>
                                            </span>
                                            <span class="item-icon"><i class="fas fa-graduation-cap"></i></span>
                                            <a href="{{ route('course-items.show', $rootItem->id) }}">{{ $rootItem->name }}</a>
                                            <div class="item-actions">
                                                <form action="{{ route('course-items.toggle-active', $rootItem->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $rootItem->active ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $rootItem->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                                        <i class="fas {{ $rootItem->active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                                    </button>
                                                </form>
                                                <button type="button" class="btn btn-sm btn-success" title="Thêm khóa học" 
                                                    onclick="setupAddModal({{ $rootItem->id }}, '{{ $rootItem->name }}')">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary" title="Chỉnh sửa"
                                                    onclick="setupEditModal({{ $rootItem->id }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" title="Xóa" onclick="confirmDelete({{ $rootItem->id }}, '{{ $rootItem->name }}')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>

                                        @include('course-items.partials.children-tree', ['children' => $rootItem->children, 'parentId' => $rootItem->id, 'tabPrefix' => 'tab-'])
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa <strong id="delete-item-name"></strong>?</p>
                <p class="text-danger">Lưu ý: Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form id="delete-form" action="" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal thêm khóa học mới -->
<div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm khóa học mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="add-item-form" action="{{ route('course-items.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="parent_id" id="parent-id-input">
                    
                    <div class="mb-3">
                        <label for="item-name" class="form-label">Tên khóa học <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="item-name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is-leaf" name="is_leaf" value="1">
                            <input type="hidden" name="is_leaf" value="0">
                            <label class="form-check-label" for="is-leaf">Là khóa học cuối (có thể tạo lớp)</label>
                        </div>
                    </div>
                    
                    <div id="leaf-options" style="display: none;">
                        <div class="mb-3">
                            <label for="item-fee" class="form-label">Học phí</label>
                            <input type="number" class="form-control" id="item-fee" name="fee" min="0" value="0">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="has-online" name="has_online" value="1">
                                <input type="hidden" name="has_online" value="0">
                                <label class="form-check-label" for="has-online">Có lớp online</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="has-offline" name="has_offline" value="1">
                                <input type="hidden" name="has_offline" value="0">
                                <label class="form-check-label" for="has-offline">Có lớp offline</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="item-active" name="active" value="1" checked>
                            <input type="hidden" name="active" value="0">
                            <label class="form-check-label" for="item-active">Hoạt động</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal thêm ngành mới -->
<div class="modal fade" id="addRootItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm ngành mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('course-items.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="root-item-name" class="form-label">Tên ngành <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="root-item-name" name="name" required>
                    </div>
                    
                    <!-- Thêm trường is_leaf -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="root-is-leaf" name="is_leaf" value="1">
                            <input type="hidden" name="is_leaf" value="0">
                            <label class="form-check-label" for="root-is-leaf">Là khóa học cuối (có thể tạo lớp)</label>
                        </div>
                    </div>
                    
                    <!-- Thêm trường has_online và has_offline -->
                    <div class="mb-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="root-has-online" name="has_online" value="1">
                            <input type="hidden" name="has_online" value="0">
                            <label class="form-check-label" for="root-has-online">Có lớp online</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="root-has-offline" name="has_offline" value="1">
                            <input type="hidden" name="has_offline" value="0">
                            <label class="form-check-label" for="root-has-offline">Có lớp offline</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="root-item-active" name="active" value="1" checked>
                            <input type="hidden" name="active" value="0">
                            <label class="form-check-label" for="root-item-active">Hoạt động</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.tree-container {
    padding: 15px;
    overflow-x: auto;
    margin-bottom: 20px;
}
.course-tree, .course-tree ul {
    list-style-type: none;
    padding-left: 25px;
}
.course-tree li {
    position: relative;
    padding: 5px 0;
    border-left: 1px dashed #ccc;
}
.course-tree li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 15px;
    width: 15px;
    height: 1px;
    background-color: #ccc;
}
.tree-item {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 6px;
    background-color: #f8f9fa;
    margin-left: 15px;
    border-left: 4px solid #6c757d;
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    min-width: 300px;
}
.tree-item:hover {
    background-color: #e9ecef;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.tree-item.active {
    background-color: #f8f9fa;
    border-left-color: #28a745;
}
.tree-item.inactive {
    background-color: #f1f1f1;
    border-left-color: #dc3545;
    opacity: 0.7;
}
.tree-item.leaf {
    border-left-color: #17a2b8;
}
.level-1 {
    font-size: 1.2rem;
    font-weight: bold;
    background-color: #f0f8ff;
}
.level-2 {
    font-size: 1.1rem;
    background-color: #f5f5f5;
}
.level-3 {
    font-size: 1rem;
    background-color: #f9f9f9;
}
.level-4 {
    font-size: 0.95rem;
    background-color: #fcfcfc;
}
.level-5 {
    font-size: 0.9rem;
    background-color: #ffffff;
}
.toggle-icon {
    margin-right: 8px;
    width: 20px;
    text-align: center;
    cursor: pointer;
    color: #6c757d;
    transition: color 0.2s;
}
.toggle-icon:hover {
    color: #0d6efd;
}
.item-icon {
    margin-right: 10px;
    width: 20px;
    text-align: center;
    color: #495057;
}
.tree-item a {
    color: #212529;
    text-decoration: none;
    flex-grow: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.tree-item a:hover {
    color: #0d6efd;
}
.item-actions {
    margin-left: auto;
    opacity: 0.3;
    transition: opacity 0.3s;
    white-space: nowrap;
    display: flex;
    gap: 3px;
}
.tree-item:hover .item-actions {
    opacity: 1;
}
.badge {
    margin-left: 5px;
    font-weight: 500;
    padding: 0.35em 0.65em;
}
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}
.highlight {
    animation: highlight-animation 3s;
}
@keyframes highlight-animation {
    0% { background-color: #fff3cd; box-shadow: 0 0 10px rgba(255, 193, 7, 0.8); }
    70% { background-color: #fff3cd; box-shadow: 0 0 10px rgba(255, 193, 7, 0.8); }
    100% { background-color: inherit; box-shadow: inherit; }
}
</style>

@push('scripts')
<script>
    $(function() {
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
        
        // Xử lý form chỉnh sửa bằng AJAX
        $('#edit-item-form').submit(function(e) {
            e.preventDefault();
            
            const form = $(this);
            const id = $('#edit-item-id').val();
            const formData = form.serialize();
            const formAction = form.attr('action');
            
            // Tạo Toast thông báo
            function showToast(message, type) {
                const toastHTML = `
                    <div class="toast align-items-center text-white bg-${type}" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="d-flex">
                            <div class="toast-body">${message}</div>
                            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
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
            
            $.ajax({
                url: formAction,
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Đóng modal
                        $('#editItemModal').modal('hide');
                        
                        // Cập nhật thông tin hiển thị của item đã chỉnh sửa
                        const item = $(`div.tree-item[data-id="${id}"]`);
                        
                        // Cập nhật tên
                        item.find('a').contents().first().text($('#edit-item-name').val());
                        
                        // Cập nhật trạng thái active
                        if ($('#edit-item-active').is(':checked')) {
                            item.removeClass('inactive').addClass('active');
                            item.find('button[title="Vô hiệu hóa"]').removeClass('btn-outline-success').addClass('btn-outline-danger');
                            item.find('button[title="Vô hiệu hóa"] i').removeClass('fa-eye').addClass('fa-eye-slash');
                        } else {
                            item.removeClass('active').addClass('inactive');
                            item.find('button[title="Kích hoạt"]').removeClass('btn-outline-danger').addClass('btn-outline-success');
                            item.find('button[title="Kích hoạt"] i').removeClass('fa-eye-slash').addClass('fa-eye');
                        }
                        
                        // Highlight item đã chỉnh sửa
                        item.addClass('highlight');
                        setTimeout(() => {
                            item.removeClass('highlight');
                        }, 3000);
                        
                        // Hiển thị thông báo thành công
                        showToast('Đã cập nhật thành công!', 'success');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Đã xảy ra lỗi khi cập nhật.';
                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        errorMessage = Object.values(xhr.responseJSON.errors).join('<br>');
                    }
                    showToast(errorMessage, 'danger');
                }
            });
        });
    });
    
    // Xác nhận xóa khóa học
    function confirmDelete(id, name) {
        $('#delete-item-name').text(name);
        $('#delete-form').attr('action', "{{ url('/course-items') }}/" + id);
        $('#deleteModal').modal('show');
    }
    
    // Xác nhận xóa lớp học
    function confirmDeleteClass(id, name) {
        $('#delete-item-name').text('lớp học ' + name);
        $('#delete-form').attr('action', "{{ url('/classes') }}/" + id);
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
        const item = $(`div.tree-item[data-id="${id}"]`);
        if (!item.length) return;
        
        // Lấy thông tin khóa học
        const name = item.find('a').contents().first().text().trim();
        const isLeaf = item.hasClass('leaf');
        const isActive = item.hasClass('active');
        const hasOnline = item.find('.badge:contains("Online")').length > 0;
        const hasOffline = item.find('.badge:contains("Offline")').length > 0;
        
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
        
        // Điền thông tin vào form
        $('#edit-item-id').val(id);
        $('#edit-parent-id').val(parentId);
        $('#edit-item-name').val(name);
        $('#edit-is-leaf').prop('checked', isLeaf);
        $('#edit-item-active').prop('checked', isActive);
        $('#edit-has-online').prop('checked', hasOnline);
        $('#edit-has-offline').prop('checked', hasOffline);
        $('#edit-item-fee').val(fee);
        
        // Cập nhật action của form
        $('#edit-item-form').attr('action', "{{ url('/course-items') }}/" + id);
        
        // Hiển thị/ẩn các tùy chọn cho nút lá
        if (isLeaf) {
            $('#edit-leaf-options').slideDown();
        } else {
            $('#edit-leaf-options').slideUp();
        }
        
        $('#editItemModal').modal('show');
    }
</script>
@endpush

<!-- Modal chỉnh sửa khóa học -->
<div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chỉnh sửa khóa học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="edit-item-form" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <input type="hidden" id="edit-item-id" name="id" value="">
                    <input type="hidden" id="edit-parent-id" name="parent_id" value="">
                    
                    <div class="mb-3">
                        <label for="edit-item-name" class="form-label">Tên khóa học <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit-item-name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit-is-leaf" name="is_leaf" value="1">
                            <input type="hidden" name="is_leaf" value="0">
                            <label class="form-check-label" for="edit-is-leaf">Là khóa học cuối (có thể tạo lớp)</label>
                        </div>
                    </div>
                    
                    <div id="edit-leaf-options">
                        <div class="mb-3">
                            <label for="edit-item-fee" class="form-label">Học phí</label>
                            <input type="number" class="form-control" id="edit-item-fee" name="fee" min="0" value="0">
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="edit-has-online" name="has_online" value="1">
                                <input type="hidden" name="has_online" value="0">
                                <label class="form-check-label" for="edit-has-online">Có lớp online</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="edit-has-offline" name="has_offline" value="1">
                                <input type="hidden" name="has_offline" value="0">
                                <label class="form-check-label" for="edit-has-offline">Có lớp offline</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit-item-active" name="active" value="1" checked>
                            <input type="hidden" name="active" value="0">
                            <label class="form-check-label" for="edit-item-active">Hoạt động</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection 