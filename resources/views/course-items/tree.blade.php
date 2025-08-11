@extends('layouts.app')
@section('page-title', 'Cây khóa học')

@section('breadcrumb')
<li class="breadcrumb-item active">Khóa học</li>
@endsection

@section('page-actions')
<button class="btn btn-sm btn-info me-2" id="expand-all">
    <i class="fas fa-expand-arrows-alt"></i> Mở rộng tất cả
</button>
<button class="btn btn-sm btn-secondary me-2" id="collapse-all">
    <i class="fas fa-compress-arrows-alt"></i> Thu gọn tất cả
</button>
<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addRootItemModal">
    <i class="fas fa-plus"></i> Thêm ngành mới
</button>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/course-tree.css') }}">
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        @if($rootItems->isEmpty())
            <div class="alert alert-info">
                Chưa có ngành học nào được tạo. Hãy tạo ngành học đầu tiên.
            </div>
        @else
          
                <!-- Search box -->
                <div class="search-container">
                    <select id="course-search-select" class="form-select" style="width: 100%;">
                        <option></option>
                    </select>
                </div>
            <div class="tab-and-search-container">
                <!-- Tab navigation -->
                <div class="tab-container">
                    <ul class="nav nav-tabs course-tabs" id="courseTab" role="tablist">
                        @foreach($rootItems as $index => $rootItem)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link @if($activeRootId == $rootItem->id) active @endif" 
                                        id="tab-{{ $rootItem->id }}" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#content-{{ $rootItem->id }}" 
                                        type="button" 
                                        role="tab" 
                                        aria-controls="content-{{ $rootItem->id }}" 
                                        aria-selected="@if($activeRootId == $rootItem->id) true @else false @endif">
                                    {{ $rootItem->name }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
              
            </div>
            
            <!-- Tab content -->
            <div class="tab-content" id="courseTabContent">
                @foreach($rootItems as $index => $rootItem)
                    <div class="tab-pane fade @if($activeRootId == $rootItem->id) show active @endif" 
                         id="content-{{ $rootItem->id }}" 
                         role="tabpanel" 
                         aria-labelledby="tab-{{ $rootItem->id }}"
                         data-root-id="{{ $rootItem->id }}">
                        <div class="tree-container">
                            <ul class="course-tree sortable-tree">
                                {{-- Luôn hiển thị đầy đủ cả nút gốc và con --}}
                                    <li>
                                        <div class="tree-item level-1 {{ $rootItem->active ? 'active' : 'inactive' }} {{ $rootItem->is_leaf ? 'leaf' : '' }}" data-id="{{ $rootItem->id }}">
                                            <span class="sort-handle" title="Kéo để sắp xếp">
                                                <i class="fas fa-arrows-alt"></i>
                                            </span>
                                            <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#children-{{ $rootItem->id }}">
                                                <i class="fas fa-minus-circle"></i>
                                            </span>
                                            <span class="item-icon"><i class="fas fa-graduation-cap"></i></span>
                                            <a href="javascript:void(0)" class="course-link" data-id="{{ $rootItem->id }}">
                                                {{ $rootItem->name }}
                                                @if($rootItem->is_leaf)
                                                    {!! $rootItem->status_badge !!}
                                                @endif
                                            </a>
                                            <div class="item-actions">
                                                <button type="button" class="btn btn-sm btn-success open-add-child" title="Thêm khóa học"
                                                    data-parent-id="{{ $rootItem->id }}" data-parent-name="{{ $rootItem->name }}">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                              
                                                @if($rootItem->is_leaf)
                                                    @php $courseStatus = $rootItem->getStatusEnum(); @endphp
                                                    <button type="button" class="btn btn-sm btn-{{ $courseStatus === App\Enums\CourseStatus::ACTIVE ? 'warning' : 'success' }} toggle-course-status" 
                                                        data-course-id="{{ $rootItem->id }}" 
                                                        data-current-status="{{ $courseStatus->value }}"
                                                        title="{{ $courseStatus === App\Enums\CourseStatus::ACTIVE ? 'Kết thúc khóa học' : 'Mở lại khóa học' }}">
                                                        <i class="fas fa-{{ $courseStatus === App\Enums\CourseStatus::ACTIVE ? 'stop' : 'play' }}"></i>
                                                    </button>
                                                @endif
                                              
                                                <button type="button" class="btn btn-sm btn-info open-students-modal" data-course-id="{{ $rootItem->id }}" title="Xem học viên">
                                                    <i class="fas fa-user-graduate"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary" title="Chỉnh sửa"
                                                    onclick="setupEditModal({{ $rootItem->id }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger open-delete-item" title="Xóa" data-id="{{ $rootItem->id }}" data-name="{{ $rootItem->name }}">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>

                                        @include('course-items.partials.children-tree', ['children' => $rootItem->children, 'parentId' => $rootItem->id])
                                    </li>
                            </ul>
                          
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
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
                    <div class="mb-3">
                        <label for="add-parent-select" class="form-label">Khoá cha</label>
                        <select id="add-parent-select" name="parent_id" class="form-select">
                            <option value="">(Không có) — Đặt làm khoá chính</option>
                        </select>
                        <div class="form-text">Chọn khoá cha (hoặc để trống để tạo khoá chính).</div>
                    </div>
                    <input type="hidden" name="current_root_id" id="current-root-id-input">

                    <div class="mb-3">
                        <label for="item-name" class="form-label">Tên khóa học <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="item-name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is-leaf" value="1" checked>
                            <input type="hidden" id="is-leaf-hidden" name="is_leaf" value="1">
                            <label class="form-check-label" for="is-leaf">Là khóa học cuối (có thể tạo lớp)</label>
                        </div>
                    </div>
                    
                    <div id="leaf-options">
                        <div class="mb-3">
                            <label for="item-fee" class="form-label">Học phí <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="item-fee" name="fee" min="0" value="" 
                                   placeholder="Nhập học phí (VNĐ)" required>
                            <div class="form-text">Học phí cho khóa học này (đơn vị: VNĐ)</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is-special" value="1">
                            <input type="hidden" id="is-special-hidden" name="is_special" value="0">
                            <label class="form-check-label" for="is-special">
                                <strong class="text-warning">Khóa học đặc biệt</strong>
                            </label>
                        </div>
                        <div class="text-muted small mt-1">
                            Khóa học đặc biệt cho phép thêm các trường thông tin tùy chỉnh
                        </div>
                    </div>
                    
                    <div id="add-custom-fields-container" style="display: none;">
                        <hr>
                        <h6 class="mb-3">Thông tin tùy chỉnh</h6>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Các trường thông tin này sẽ được sao chép khi học viên đăng ký khóa học này.
                        </div>
                        
                        <div id="add-custom-fields-list">
                            <!-- Các trường thông tin tùy chỉnh sẽ được thêm vào đây -->
                        </div>
                        
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-custom-field-btn">
                                <i class="fas fa-plus"></i> Thêm trường thông tin
                            </button>
                        </div>
                    </div>
                    
                    <input type="hidden" name="active" value="1">
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
                    <!-- Thêm hidden field parent_id với giá trị null cho ngành gốc -->
                    <input type="hidden" name="parent_id" value="">
                    
                    <div class="mb-3">
                        <label for="root-item-name" class="form-label">Tên ngành <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="root-item-name" name="name" required>
                    </div>
                    
                    <!-- Thêm trường is_leaf -->
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="root-is-leaf" value="1">
                            <input type="hidden" id="root-is-leaf-hidden" name="is_leaf" value="0">
                            <label class="form-check-label" for="root-is-leaf">Là khóa học cuối (có thể tạo lớp)</label>
                        </div>
                    </div>
                    
                    <div id="root-leaf-options" style="display: none;">
                        <div class="mb-3">
                            <label for="root-item-fee" class="form-label">Học phí <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="root-item-fee" name="fee" min="0" value="" 
                                   placeholder="Nhập học phí (VNĐ)" required>
                            <div class="form-text">Học phí cho khóa học này (đơn vị: VNĐ)</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="root-is-special" value="1">
                            <input type="hidden" id="root-is-special-hidden" name="is_special" value="0">
                            <label class="form-check-label" for="root-is-special">
                                <strong class="text-warning">Khóa học đặc biệt</strong>
                            </label>
                        </div>
                        <div class="text-muted small mt-1">
                            Khóa học đặc biệt cho phép thêm các trường thông tin tùy chỉnh
                        </div>
                    </div>
                    
                    <div id="root-custom-fields-container" style="display: none;">
                        <hr>
                        <h6 class="mb-3">Thông tin tùy chỉnh</h6>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Các trường thông tin này sẽ được sao chép khi học viên đăng ký khóa học này.
                        </div>
                        
                        <div id="root-custom-fields-list">
                            <!-- Các trường thông tin tùy chỉnh sẽ được thêm vào đây -->
                        </div>
                        
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="root-add-custom-field-btn">
                                <i class="fas fa-plus"></i> Thêm trường thông tin
                            </button>
                        </div>
                    </div>
                    
                    <input type="hidden" name="active" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal hiển thị chi tiết khóa học -->
<div class="modal fade" id="viewCourseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết khóa học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center" id="course-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
                <div id="course-details" style="display: none;">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 id="course-name"></h4>
                            <p class="text-muted" id="course-path"></p>
                        </div>
                        <div class="col-md-4 text-end">
                            <span class="badge bg-success fs-6" id="course-fee"></span>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <i class="fas fa-info-circle"></i> Thông tin cơ bản
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Mã khóa học:</th>
                                            <td id="course-id"></td>
                                        </tr>
                                        <tr>
                                            <th>Loại:</th>
                                            <td id="course-type"></td>
                                        </tr>
                                        <tr>
                                            <th>Trạng thái:</th>
                                            <td id="course-status"></td>
                                        </tr>
                                        <tr>
                                            <th>Khóa đặc biệt:</th>
                                            <td id="course-special"></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <i class="fas fa-users"></i> Học viên & Lớp học
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="60%">Số học viên đã đăng ký:</th>
                                            <td id="enrollment-count"></td>
                                        </tr>
                                        <tr>
                                            <th>Tổng thu:</th>
                                            <td id="total-revenue"></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="course-custom-fields-card" class="card mb-3" style="display: none;">
                        <div class="card-header bg-light">
                            <i class="fas fa-list-alt"></i> Thông tin tùy chỉnh
                        </div>
                        <div class="card-body">
                            <div id="course-custom-fields"></div>
                        </div>
                    </div>
                    
                    <div id="learning-paths-section" class="card mb-3" style="display: none;">
                        <div class="card-header bg-light">
                            <i class="fas fa-road"></i> Lộ trình học tập
                        </div>
                        <div class="card-body">
                            <ul class="list-group" id="learning-paths-list"></ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="btn-group">
                    <a id="btn-students" href="#" class="btn btn-info">
                        <i class="fas fa-user-graduate"></i> Học viên
                    </a>
                 
                </div>
                <button type="button" class="btn btn-primary" id="btn-edit-from-modal">
                    <i class="fas fa-edit"></i> Chỉnh sửa
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

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
                    <input type="hidden" id="edit-current-root-id" name="current_root_id" value="">
                    
                    <div class="mb-3">
                        <label for="edit-parent-select" class="form-label">Khoá cha</label>
                        <select id="edit-parent-select" name="parent_id" class="form-select">
                            <option value="">(Không có) — Đặt làm khoá chính</option>
                        </select>
                        <div class="form-text">Chọn khoá cha (hoặc để trống để đặt làm khoá chính).</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-item-name" class="form-label">Tên khóa học <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit-item-name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit-is-leaf" value="1">
                            <input type="hidden" id="edit-is-leaf-hidden" name="is_leaf" value="0">
                            <label class="form-check-label" for="edit-is-leaf">Là khóa học cuối (có thể tạo lớp)</label>
                        </div>
                    </div>
                    
                    <div id="edit-leaf-options">
                        <div class="mb-3">
                            <label for="edit-item-fee" class="form-label">Học phí <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit-item-fee" name="fee" min="0" value="" 
                                   placeholder="Nhập học phí (VNĐ)" required>
                            <div class="form-text">Học phí cho khóa học này (đơn vị: VNĐ)</div>
                        </div>
                       
                    </div>
                    
                            <input type="hidden" name="active" value="1">
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit-is-special" value="1">
                            <input type="hidden" id="edit-is-special-hidden" name="is_special" value="0">
                            <label class="form-check-label" for="edit-is-special">
                                <strong class="text-warning">Khóa học đặc biệt</strong>
                            </label>
                        </div>
                        <div class="text-muted small mt-1">
                            Khóa học đặc biệt cho phép thêm các trường thông tin tùy chỉnh
                        </div>
                    </div>
                    
                    <div id="custom-fields-container" style="display: none;">
                        <hr>
                        <h6 class="mb-3">Thông tin tùy chỉnh</h6>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Các trường thông tin này sẽ được sao chép khi học viên đăng ký khóa học này.
                        </div>
                        
                        <div id="custom-fields-list">
                            <!-- Các trường thông tin tùy chỉnh sẽ được thêm vào đây -->
                        </div>
                        
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-custom-field">
                                <i class="fas fa-plus"></i> Thêm trường thông tin
                            </button>
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

@push('scripts')
<script src="{{ asset('js/course-tree.js') }}"></script>
<script>
$(document).ready(function() {
    // Khởi tạo trạng thái ban đầu cho modal thêm khóa học
    // Vì checkbox is-leaf được check mặc định, nên hiển thị trường học phí
    if ($('#is-leaf').is(':checked')) {
        $('#leaf-options').show();
        $('#item-fee').prop('required', true);
    }
    
    // Xử lý hiển thị/ẩn trường học phí khi tick checkbox "Là khóa học cuối"
    $('#is-leaf').change(function() {
        if ($(this).is(':checked')) {
            $('#leaf-options').slideDown();
            $('#item-fee').prop('required', true);
            $('#is-leaf-hidden').val('1'); // Set hidden input to 1
        } else {
            $('#leaf-options').slideUp();
            $('#item-fee').prop('required', false);
            $('#item-fee').val(''); // Clear fee khi untick
            $('#is-leaf-hidden').val('0'); // Set hidden input to 0
        }
    });
    
    // Khởi tạo khi mở modal edit
    $('#editItemModal').on('shown.bs.modal', function() {
        // Đảm bảo trường học phí hiển thị đúng theo trạng thái checkbox
        setTimeout(function() {
            syncEditIsLeafState();
            // syncEditIsSpecialState sẽ được gọi từ AJAX callback
        }, 100); // Delay để đảm bảo setupEditModal đã chạy xong
    });
    
    // Xử lý cho modal edit
    $('#edit-is-leaf').change(function() {
        if ($(this).is(':checked')) {
            $('#edit-leaf-options').slideDown();
            $('#edit-item-fee').prop('required', true);
            $('#edit-is-leaf-hidden').val('1'); // Set hidden input to 1
        } else {
            $('#edit-leaf-options').slideUp();
            $('#edit-item-fee').prop('required', false);
            $('#edit-item-fee').val(''); // Clear fee khi untick
            $('#edit-is-leaf-hidden').val('0'); // Set hidden input to 0
        }
    });
    
    // Function để sync trạng thái checkbox với hidden input
    function syncEditIsLeafState() {
        const isChecked = $('#edit-is-leaf').is(':checked');
        console.log('Syncing edit is_leaf state:', isChecked);
        
        if (isChecked) {
            $('#edit-leaf-options').show();
            $('#edit-item-fee').prop('required', true);
            $('#edit-is-leaf-hidden').val('1');
        } else {
            $('#edit-leaf-options').hide();
            $('#edit-item-fee').prop('required', false);
            $('#edit-is-leaf-hidden').val('0');
        }
    }
    
    // Gọi sync khi có thay đổi từ bên ngoài (như setupEditModal)
    $(document).on('editModalDataLoaded', function() {
        syncEditIsLeafState();
    });
    
    // Gọi sync khi AJAX load xong dữ liệu khóa học đặc biệt
    $(document).on('editModalSpecialDataLoaded', function() {
        syncEditIsSpecialState();
    });
    
    // ===== XỬ LÝ CHECKBOX KHÓA HỌC ĐẶC BIỆT =====
    // Xử lý checkbox khóa học đặc biệt
    $('#edit-is-special').change(function() {
        if ($(this).is(':checked')) {
            $('#custom-fields-container').slideDown();
            $('#edit-is-special-hidden').val('1');
        } else {
            $('#custom-fields-container').slideUp();
            $('#edit-is-special-hidden').val('0');
        }
    });
    
    // Function để sync trạng thái checkbox khóa học đặc biệt
    function syncEditIsSpecialState() {
        const isChecked = $('#edit-is-special').is(':checked');
        console.log('Syncing edit is_special state:', isChecked);
        
        if (isChecked) {
            $('#custom-fields-container').show();
            $('#edit-is-special-hidden').val('1');
        } else {
            $('#custom-fields-container').hide();
            $('#edit-is-special-hidden').val('0');
        }
    }
    
    // Khởi tạo khi mở modal thêm khóa học
    $('#addItemModal').on('shown.bs.modal', function() {
        // Đảm bảo trường học phí hiển thị đúng theo trạng thái checkbox
        if ($('#is-leaf').is(':checked')) {
            $('#leaf-options').show();
            $('#item-fee').prop('required', true);
            $('#is-leaf-hidden').val('1');
        } else {
            $('#leaf-options').hide();
            $('#item-fee').prop('required', false);
            $('#is-leaf-hidden').val('0');
        }
    });
    
    // Reset form khi đóng modal
    $('#addItemModal').on('hidden.bs.modal', function() {
        $('#is-leaf').prop('checked', true); // Mặc định tick
        $('#leaf-options').show();
        $('#item-fee').val('').prop('required', true);
        
        // Reset khóa học đặc biệt
        $('#is-special').prop('checked', false);
        $('#add-custom-fields-container').hide();
        $('#is-special-hidden').val('0');
        $('#add-custom-fields-list').empty();
    });
    
    // Reset form khi đóng modal edit
    $('#editItemModal').on('hidden.bs.modal', function() {
        $('#edit-is-leaf').prop('checked', false); // Reset về false
        $('#edit-leaf-options').hide();
        $('#edit-item-fee').val('').prop('required', false);
        $('#edit-is-leaf-hidden').val('0'); // Reset hidden input
        
        $('#edit-is-special').prop('checked', false); // Reset khóa học đặc biệt
        $('#custom-fields-container').hide();
        $('#edit-is-special-hidden').val('0'); // Reset hidden input
        $('#custom-fields-list').empty(); // Clear custom fields
        
        $('#edit-item-name').val('');
        $('#edit-item-id').val('');
        $('#edit-parent-select').html('<option value="">(Không có) — Đặt làm khoá chính</option>'); // Reset parent select
        $('#edit-item-form').attr('action', ''); // Reset form action
    });
    
    // ===== XỬ LÝ MODAL THÊM NGÀNH MỚI =====
    // Khởi tạo khi mở modal thêm ngành mới
    $('#addRootItemModal').on('shown.bs.modal', function() {
        // Đảm bảo trường học phí hiển thị đúng theo trạng thái checkbox
        if ($('#root-is-leaf').is(':checked')) {
            $('#root-leaf-options').show();
            $('#root-item-fee').prop('required', true);
            $('#root-is-leaf-hidden').val('1');
        } else {
            $('#root-leaf-options').hide();
            $('#root-item-fee').prop('required', false);
            $('#root-is-leaf-hidden').val('0');
        }
    });
    
    // Xử lý hiển thị/ẩn trường học phí cho modal thêm ngành mới
    $('#root-is-leaf').change(function() {
        if ($(this).is(':checked')) {
            $('#root-leaf-options').slideDown();
            $('#root-item-fee').prop('required', true);
            $('#root-is-leaf-hidden').val('1'); // Set hidden input to 1
        } else {
            $('#root-leaf-options').slideUp();
            $('#root-item-fee').prop('required', false);
            $('#root-item-fee').val(''); // Clear fee khi untick
            $('#root-is-leaf-hidden').val('0'); // Set hidden input to 0
        }
    });
    
    // Reset form khi đóng modal thêm ngành mới
    $('#addRootItemModal').on('hidden.bs.modal', function() {
        $('#root-is-leaf').prop('checked', false); // Reset về false
        $('#root-leaf-options').hide();
        $('#root-item-fee').val('').prop('required', false);
        $('#root-item-name').val('');
        
        // Reset khóa học đặc biệt
        $('#root-is-special').prop('checked', false);
        $('#root-custom-fields-container').hide();
        $('#root-is-special-hidden').val('0');
        $('#root-custom-fields-list').empty();
    });
    
    // ===== XỬ LÝ CUSTOM FIELDS =====
    // Thêm trường thông tin tùy chỉnh cho modal edit
    $(document).on('click', '#add-custom-field', function() {
        const fieldId = Date.now() + Math.floor(Math.random() * 1000);
        const fieldHtml = `
            <div class="custom-field-row mb-3" data-field-id="${fieldId}">
                <div class="row g-2">
                    <div class="col-10">
                        <input type="text" class="form-control form-control-sm field-key" 
                            placeholder="Tên trường (ví dụ: Số CMND, Địa chỉ...)" name="custom_field_keys[]" value="">
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
    
    // Thêm trường thông tin tùy chỉnh cho modal add item
    $(document).on('click', '#add-custom-field-btn', function() {
        const fieldId = Date.now() + Math.floor(Math.random() * 1000);
        const fieldHtml = `
            <div class="custom-field-row mb-3" data-field-id="${fieldId}">
                <div class="row g-2">
                    <div class="col-10">
                        <input type="text" class="form-control form-control-sm field-key" 
                            placeholder="Tên trường (ví dụ: Số CMND, Địa chỉ...)" name="custom_field_keys[]" value="">
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-field">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#add-custom-fields-list').append(fieldHtml);
    });
    
    // Thêm trường thông tin tùy chỉnh cho modal add root item
    $(document).on('click', '#root-add-custom-field-btn', function() {
        const fieldId = Date.now() + Math.floor(Math.random() * 1000);
        const fieldHtml = `
            <div class="custom-field-row mb-3" data-field-id="${fieldId}">
                <div class="row g-2">
                    <div class="col-10">
                        <input type="text" class="form-control form-control-sm field-key" 
                            placeholder="Tên trường (ví dụ: Số CMND, Địa chỉ...)" name="custom_field_keys[]" value="">
                    </div>
                    <div class="col-2">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-field">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#root-custom-fields-list').append(fieldHtml);
    });
    
    // Xóa trường thông tin tùy chỉnh
    $(document).on('click', '.remove-field', function() {
        $(this).closest('.custom-field-row').remove();
    });
    
    // ===== XỬ LÝ CHECKBOX KHÓA HỌC ĐẶC BIỆT CHO MODAL ADD ITEM =====
    $('#is-special').change(function() {
        if ($(this).is(':checked')) {
            $('#add-custom-fields-container').slideDown();
            $('#is-special-hidden').val('1');
        } else {
            $('#add-custom-fields-container').slideUp();
            $('#is-special-hidden').val('0');
        }
    });
    
    // ===== XỬ LÝ CHECKBOX KHÓA HỌC ĐẶC BIỆT CHO MODAL ADD ROOT ITEM =====
    $('#root-is-special').change(function() {
        if ($(this).is(':checked')) {
            $('#root-custom-fields-container').slideDown();
            $('#root-is-special-hidden').val('1');
        } else {
            $('#root-custom-fields-container').slideUp();
            $('#root-is-special-hidden').val('0');
        }
    });
});
</script>
@endpush 