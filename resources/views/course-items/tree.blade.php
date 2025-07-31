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

@section('content')
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
        top: 15px;
        left: 0;
        width: 20px;
        height: 1px;
        background-color: #ccc;
    }
    .tree-item {
        display: flex;
        align-items: center;
        padding: 8px;
        border-radius: 4px;
        background-color: #f8f9fa;
        margin-bottom: 5px;
        transition: all 0.2s;
    }
    .tree-item.inactive {
        opacity: 0.6;
    }
    .tree-item.class-item {
        background-color: #e9ecef;
    }
    .tree-item.ui-sortable-helper {
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        background-color: #e9f2ff;
        border: 1px solid #bedcff;
    }
    .tree-item.ui-state-highlight {
        background-color: #fffde7;
        border: 1px dashed #ffc107;
        height: 40px;
    }
    .tree-item.highlight {
        animation: highlight-animation 3s ease forwards;
    }
    @keyframes highlight-animation {
        0% { background-color: #fff3cd; }
        70% { background-color: #fff3cd; }
        100% { background-color: #f8f9fa; }
    }
    .tree-item > * {
        margin-right: 8px;
    }
    .toggle-icon {
        cursor: pointer;
        width: 20px;
        text-align: center;
    }
    .item-icon {
        width: 20px;
        text-align: center;
    }
    .item-actions {
        margin-left: auto;
    }
    .class-list {
        list-style-type: none;
        padding-left: 40px;
    }
    .sort-handle {
        cursor: move;
        margin-right: 5px;
        padding: 2px 5px;
        border-radius: 3px;
    }
    .sort-handle:hover {
        background-color: #e9ecef;
    }
    .sort-handle i {
        color: #6c757d;
    }
    
    /* CSS cho ô tìm kiếm */
    .tab-and-search-container {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        width: 100%;
        margin-bottom: 1rem;
    }
    .tab-container {
        flex-grow: 1;
    }
    .search-container {
        position: relative;
        width: 300px;
        margin-left: 15px;
        margin-bottom: 0.5rem;
    }
    #course-search {
        width: 100%;
        padding-right: 35px;
        transition: all 0.3s ease;
        height: 38px;
    }
    .search-clear {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        cursor: pointer;
        display: none;
    }
    .search-results {
        position: absolute;
        top: calc(100% + 5px);
        left: 0;
        right: 0;
        background-color: white;
        border-radius: 5px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        max-height: 400px;
        overflow-y: auto;
        z-index: 1050;
        opacity: 0;
        transform: translateY(-10px);
        pointer-events: none;
        transition: all 0.3s ease;
    }
    .search-results.show {
        opacity: 1;
        transform: translateY(0);
        pointer-events: auto;
    }
    .search-result-item {
        padding: 10px 15px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .search-result-item:hover {
        background-color: #f8f9fa;
    }
    .search-result-item:last-child {
        border-bottom: none;
    }
    .search-result-name {
        font-weight: 500;
        margin-bottom: 3px;
        color: #212529;
    }
    .search-result-path {
        font-size: 0.8rem;
        color: #6c757d;
    }
    .search-result-fee {
        font-weight: bold;
        color: #28a745;
    }
</style>

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
                                <button class="nav-link @if(($currentRootItem && $currentRootItem->id == $rootItem->id) || (!$currentRootItem && $index === 0)) active @endif" 
                                        id="tab-{{ $rootItem->id }}" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#content-{{ $rootItem->id }}" 
                                        type="button" 
                                        role="tab" 
                                        aria-controls="content-{{ $rootItem->id }}" 
                                        aria-selected="@if(($currentRootItem && $currentRootItem->id == $rootItem->id) || (!$currentRootItem && $index === 0)) true @else false @endif">
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
                    <div class="tab-pane fade @if(($currentRootItem && $currentRootItem->id == $rootItem->id) || (!$currentRootItem && $index === 0)) show active @endif" 
                         id="content-{{ $rootItem->id }}" 
                         role="tabpanel" 
                         aria-labelledby="tab-{{ $rootItem->id }}"
                         data-root-id="{{ $rootItem->id }}">
                        <div class="tree-container">
                            <ul class="course-tree sortable-tree">
                                @if($currentRootItem && $currentRootItem->id == $rootItem->id)
                                    {{-- Nếu đang ở tab cụ thể, chỉ hiển thị các con trực tiếp --}}
                                    @foreach($rootItem->children->sortBy('order_index') as $childItem)
                                        <li>
                                            <div class="tree-item level-2 {{ $childItem->active ? 'active' : 'inactive' }}" data-id="{{ $childItem->id }}">
                                                <span class="sort-handle" title="Kéo để sắp xếp">
                                                    <i class="fas fa-arrows-alt"></i>
                                                </span>
                                                <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#children-{{ $childItem->id }}">
                                                    <i class="fas fa-minus-circle"></i>
                                                </span>
                                                <span class="item-icon"><i class="fas fa-book"></i></span>
                                                <a href="{{ route('course-items.show', $childItem->id) }}">{{ $childItem->name }}</a>
                                                <div class="item-actions">
                                                    {{-- <form action="{{ route('course-items.toggle-active', $childItem->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm {{ $childItem->active ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $childItem->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                                            <i class="fas {{ $childItem->active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                                        </button>
                                                    </form> --}}
                                                    <button type="button" class="btn btn-sm btn-success" title="Thêm khóa học" 
                                                        onclick="setupAddModal({{ $childItem->id }}, '{{ $childItem->name }}')">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                    <a href="{{ route('course-items.students', $childItem->id) }}" class="btn btn-sm btn-info" title="Xem học viên">
                                                        <i class="fas fa-user-graduate"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-primary" title="Chỉnh sửa"
                                                        onclick="setupEditModal({{ $childItem->id }})">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" title="Xóa" onclick="confirmDelete({{ $childItem->id }}, '{{ $childItem->name }}')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            @include('course-items.partials.children-tree', ['children' => $childItem->children, 'parentId' => $childItem->id])
                                        </li>
                                    @endforeach
                                @else
                                    {{-- Hiển thị đầy đủ cả nút gốc và con --}}
                                    <li>
                                        <div class="tree-item level-1 {{ $rootItem->active ? 'active' : 'inactive' }}" data-id="{{ $rootItem->id }}">
                                            <span class="sort-handle" title="Kéo để sắp xếp">
                                                <i class="fas fa-arrows-alt"></i>
                                            </span>
                                            <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#tab-children-{{ $rootItem->id }}">
                                                <i class="fas fa-minus-circle"></i>
                                            </span>
                                            <span class="item-icon"><i class="fas fa-graduation-cap"></i></span>
                                            <a href="{{ route('course-items.show', $rootItem->id) }}">{{ $rootItem->name }}</a>
                                            <div class="item-actions">
                                                {{-- <form action="{{ route('course-items.toggle-active', $rootItem->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $rootItem->active ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $rootItem->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                                                        <i class="fas {{ $rootItem->active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                                                    </button>
                                                </form> --}}
                                                <button type="button" class="btn btn-sm btn-success" title="Thêm khóa học" 
                                                    onclick="setupAddModal({{ $rootItem->id }}, '{{ $rootItem->name }}')">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                                @if($rootItem->is_leaf)
                                                <a href="{{ route('classes.create', ['course_item_id' => $rootItem->id]) }}" class="btn btn-sm btn-info" title="Thêm lớp học">
                                                    <i class="fas fa-users"></i>
                                                </a>
                                                @endif
                                                <a href="{{ route('course-items.students', $rootItem->id) }}" class="btn btn-sm btn-info" title="Xem học viên">
                                                    <i class="fas fa-user-graduate"></i>
                                                </a>
                                                <a href="{{ route('course-items.attendance', $rootItem->id) }}" class="btn btn-sm btn-warning" title="Điểm danh">
                                                    <i class="fas fa-clipboard-check"></i>
                                                </a>
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
                                @endif
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

/* Thêm CSS cho tabs */
.course-tabs {
    border-bottom: 1px solid #dee2e6;
}

.course-tabs .nav-link {
    margin-bottom: -1px;
    border: 1px solid transparent;
    border-top-left-radius: 0.25rem;
    border-top-right-radius: 0.25rem;
    padding: 0.5rem 1rem;
    color: #495057;
    background-color: #f8f9fa;
    transition: all 0.2s ease;
}

.course-tabs .nav-link:hover {
    border-color: #e9ecef #e9ecef #dee2e6;
    background-color: #e9ecef;
}

.course-tabs .nav-link.active {
    color: #0d6efd;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
    font-weight: 600;
}

.tab-content > .tab-pane {
    display: none;
}

.tab-content > .active {
    display: block;
}

.tab-content > .show {
    opacity: 1;
}

.tab-content > .fade {
    transition: opacity 0.15s linear;
}

.tab-content > .fade:not(.show) {
    opacity: 0;
}
</style>

@push('scripts')
<script>
    $(function() {
        // Kích hoạt tab đầu tiên khi tải trang
        const firstTab = document.querySelector('#courseTab .nav-link');
        if (firstTab) {
            new bootstrap.Tab(firstTab).show();
        }

        // Cập nhật URL khi chuyển tab
        $('.course-tabs .nav-link').on('shown.bs.tab', function (e) {
            const rootId = $(this).attr('id').replace('tab-', '');
            const url = new URL(window.location);
            url.searchParams.set('root_id', rootId);
            window.history.pushState({}, '', url);
            
            // Xóa kết quả tìm kiếm khi chuyển tab
            clearSearch();
        });
        
        // Xử lý checkbox "Khóa học đặc biệt"
        $('#edit-is-special').change(function() {
            if ($(this).is(':checked')) {
                $('#custom-fields-container').slideDown();
            } else {
                $('#custom-fields-container').slideUp();
            }
        });
        
        // Thêm trường thông tin tùy chỉnh
        $('#add-custom-field').click(function() {
            const fieldId = Date.now(); // ID độc nhất cho trường
            const fieldHtml = `
                <div class="custom-field-row mb-3" data-field-id="${fieldId}">
                    <div class="row g-2">
                        <div class="col-10">
                            <input type="text" class="form-control form-control-sm field-key" 
                                placeholder="Tên trường" name="custom_field_keys[]">
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
        
        // Xóa trường thông tin tùy chỉnh
        $(document).on('click', '.remove-field', function() {
            $(this).closest('.custom-field-row').fadeOut(300, function() {
                $(this).remove();
            });
        });
        
        // Xử lý checkbox "Đặt làm khóa chính" trong modal chỉnh sửa
        $('#edit-make-root').change(function() {
            handleEditRootToggle();
        });
        
        // Xử lý khi dropdown parent_id thay đổi trong modal chỉnh sửa
        $('#edit-parent-id').change(function() {
            if ($(this).val()) {
                $('#edit-make-root').prop('checked', false);
                $('#edit-root-info').slideUp();
            }
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
                url: '{{ route("api.course-items.search") }}',
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
        
        // Hàm định dạng tiền tệ
        function formatCurrency(amount) {
            return new Intl.NumberFormat('vi-VN').format(amount);
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
                    window.location.href = `{{ url('/course-items') }}/${courseId}`;
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
                        
                        // Reload trang sau 1 giây để cập nhật dữ liệu
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
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
        $('#edit-item-form').attr('action', "{{ url('/course-items') }}/" + id);
        
        // Hiển thị/ẩn các tùy chọn cho nút lá
        if (isLeaf) {
            $('#edit-leaf-options').slideDown();
        } else {
            $('#edit-leaf-options').slideUp();
        }
        
        // Lấy thông tin về khóa học đặc biệt và các trường tùy chỉnh
        $.ajax({
            url: "{{ url('/api/course-items') }}/" + id,
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
</script>

<!-- Kéo thả để sắp xếp -->
<script>
    $(function() {
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
                        const itemId = ui.item.find("> .tree-item").data("id");
                        
                        // Gửi Ajax để cập nhật thứ tự và parent nếu cần
                        updateItemsOrder(items, newParentId);
                    }
                }
            });
        });
    });

    // Hàm cập nhật thứ tự qua AJAX
    function updateItemsOrder(items, newParentId = null) {
        $.ajax({
            url: '{{ route("course-items.update-order") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
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