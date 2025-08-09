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
                
                <!-- Search box -->
                <div class="search-container">
                    <input type="text" id="course-search" class="form-control" placeholder="Tìm kiếm khóa học..." autocomplete="off">
                    <span class="search-clear"><i class="fas fa-times-circle"></i></span>
                    <div class="search-results">
                        <!-- Kết quả tìm kiếm sẽ được hiển thị ở đây -->
                    </div>
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
                                        <div class="tree-item level-1 {{ $rootItem->active ? 'active' : 'inactive' }}" data-id="{{ $rootItem->id }}">
                                            <span class="sort-handle" title="Kéo để sắp xếp">
                                                <i class="fas fa-arrows-alt"></i>
                                            </span>
                                            <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#children-{{ $rootItem->id }}">
                                                <i class="fas fa-minus-circle"></i>
                                            </span>
                                            <span class="item-icon"><i class="fas fa-graduation-cap"></i></span>
                                            <a href="javascript:void(0)" class="course-link" data-id="{{ $rootItem->id }}">{{ $rootItem->name }}</a>
                                            <div class="item-actions">
                                                {{-- <form action="{{ route('course-items.toggle-active', $rootItem->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $rootItem->active ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $rootItem->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                                        <i class="fas {{ $rootItem->active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                                    </button>
                                                </form> --}}
                                                <button type="button" class="btn btn-sm btn-success open-add-child" title="Thêm khóa học"
                                                    data-parent-id="{{ $rootItem->id }}" data-parent-name="{{ $rootItem->name }}">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                @if($rootItem->is_leaf)
                                                <a href="{{ route('classes.create', ['course_item_id' => $rootItem->id]) }}" class="btn btn-sm btn-info" title="Thêm lớp học">
                                                    <i class="fas fa-users"></i>
                                                </a>
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
                            @if($rootItem->is_leaf)
                            <div class="mt-3">
                                <h5>Danh sách lớp học</h5>
                                <ul class="class-list">
                                    @php
                                        $classes = \App\Models\Classes::where('course_item_id', $rootItem->id)->orderBy('batch_number')->get();
                                    @endphp
                                    @forelse($classes as $class)
                                        <li>
                                            <div class="tree-item class-item {{ $class->status != 'cancelled' ? 'active' : 'inactive' }}">
                                                <span class="item-icon"><i class="fas fa-users"></i></span>
                                                <a href="{{ route('classes.show', $class->id) }}">{{ $class->name }}</a>
                                                <span class="badge {{ $class->status == 'in_progress' ? 'bg-success' : ($class->status == 'planned' ? 'bg-warning' : 'bg-secondary') }}">
                                                    {{ $class->status }}
                                                </span>
                                                <div class="item-actions">
                                                    <a href="{{ route('classes.edit', $class->id) }}" class="btn btn-sm btn-primary" title="Chỉnh sửa lớp">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="{{ route('course-items.students', $class->course_item_id) }}" class="btn btn-sm btn-info" title="Xem học viên">
                                                        <i class="fas fa-user-graduate"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </li>
                                    @empty
                                        <li>
                                            <div class="tree-item class-empty">
                                                <em>Chưa có lớp học nào</em>
                                                <a href="{{ route('classes.create', ['course_item_id' => $rootItem->id]) }}" class="btn btn-sm btn-success">
                                                    <i class="fas fa-plus"></i> Tạo lớp học mới
                                                </a>
                                            </div>
                                        </li>
                                    @endforelse
                                </ul>
                            </div>
                            @endif
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
                    <!-- Thêm hidden field parent_id với giá trị null cho ngành gốc -->
                    <input type="hidden" name="parent_id" value="">
                    
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
                    <a id="btn-attendance" href="#" class="btn btn-warning">
                        <i class="fas fa-clipboard-check"></i> Điểm danh
                    </a>
                    <a id="btn-payments" href="#" class="btn btn-success">
                        <i class="fas fa-money-bill"></i> Thanh toán
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
                        
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit-item-active" name="active" value="1" checked>
                            <input type="hidden" name="active" value="0">
                            <label class="form-check-label" for="edit-item-active">Hoạt động</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit-is-special" name="is_special" value="1">
                            <input type="hidden" name="is_special" value="0">
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
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit-make-root" name="make_root" value="1">
                            <label class="form-check-label" for="edit-make-root">
                                <strong class="text-primary">Đặt làm khóa chính</strong>
                            </label>
                        </div>
                        <div class="alert alert-info mt-2" id="edit-root-info" style="display: none;">
                            <i class="fas fa-info-circle"></i> Khi chọn làm khóa chính, khóa học này sẽ được đưa lên cấp cao nhất và hiển thị cùng cấp với các ngành học.
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
@endpush 