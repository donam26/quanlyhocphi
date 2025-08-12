@extends('layouts.app')

@section('title', 'Quản lý khóa học')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-sitemap me-2"></i>Cây khóa học
                    </h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" onclick="addCourse()">
                            <i class="fas fa-plus me-1"></i>Thêm ngành
                        </button>
                        <button type="button" class="btn btn-primary" onclick="addCourse(1)">
                            <i class="fas fa-plus me-1"></i>Thêm khóa học
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    @if(isset($rootItems) && $rootItems->count() > 0)
                        <div class="tree-container">
                            @foreach($rootItems as $rootItem)
                                <div class="tree-item root-item mb-4">
                                    <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-folder-open text-primary me-2"></i>
                                            <div>
                                                <h6 class="mb-0">{{ $rootItem->name }}</h6>
                                                @if($rootItem->is_leaf)
                                                    <small class="text-success">
                                                        <i class="fas fa-graduation-cap me-1"></i>
                                                        Khóa học cuối - {{ number_format($rootItem->fee) }} VNĐ
                                                    </small>
                                                @else
                                                    <small class="text-muted">{{ $rootItem->children->count() }} khóa học con</small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewCourse({{ $rootItem->id }})"
                                                    title="Xem chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning" 
                                                    onclick="editCourse({{ $rootItem->id }})"
                                                    title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @if(!$rootItem->is_leaf)
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="addCourse({{ $rootItem->id }})"
                                                        title="Thêm khóa học con">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            @endif
                                            <button class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteCourse({{ $rootItem->id }}, '{{ $rootItem->name }}')"
                                                    title="Xóa">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Hiển thị khóa học con --}}
                                    @if($rootItem->children->count() > 0)
                                        <div class="children-container mt-3 ms-4">
                                            @foreach($rootItem->children as $child)
                                                <div class="tree-item child-item mb-2">
                                                    <div class="d-flex justify-content-between align-items-center p-2 border rounded bg-light">
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-file-alt text-info me-2"></i>
                                                            <div>
                                                                <span class="fw-bold">{{ $child->name }}</span>
                                                                @if($child->is_leaf)
                                                                    <small class="text-success ms-2">
                                                                        <i class="fas fa-graduation-cap me-1"></i>
                                                                        {{ number_format($child->fee) }} VNĐ
                                                                    </small>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="btn-group">
                                                            <button class="btn btn-sm btn-outline-primary" 
                                                                    onclick="viewCourse({{ $child->id }})"
                                                                    title="Xem chi tiết">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-outline-warning" 
                                                                    onclick="editCourse({{ $child->id }})"
                                                                    title="Chỉnh sửa">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            @if(!$child->is_leaf)
                                                                <button class="btn btn-sm btn-outline-success" 
                                                                        onclick="addCourse({{ $child->id }})"
                                                                        title="Thêm khóa học con">
                                                                    <i class="fas fa-plus"></i>
                                                                </button>
                                                            @endif
                                                            <button class="btn btn-sm btn-outline-danger" 
                                                                    onclick="deleteCourse({{ $child->id }}, '{{ $child->name }}')"
                                                                    title="Xóa">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-sitemap fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Chưa có khóa học nào</h5>
                            <p class="text-muted">Hãy thêm ngành đầu tiên để bắt đầu tạo cây khóa học.</p>
                            <button type="button" class="btn btn-primary" onclick="addCourse()">
                                <i class="fas fa-plus me-1"></i>Thêm ngành đầu tiên
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}

@push('scripts')
<script>
// Course Tree Page với Unified Modal System
document.addEventListener('app:ready', function() {
    console.log('Course Tree page ready with Unified Modal System');
});

// Backward compatibility functions
function viewCourse(id) {
    showCourseDetail(id);
}

function addCourse(parentId = null) {
    showCourseForm(null, parentId);
}

function editCourse(id) {
    showCourseForm(id);
}

function deleteCourse(id, name) {
    confirmDeleteCourse(id, name);
}

// Legacy functions để tương thích với code cũ
function showCourseDetails(courseId) {
    viewCourse(courseId);
}

function openAddItemModal(parentId = null) {
    addCourse(parentId);
}

function openAddRootItemModal() {
    addCourse(null);
}

function openEditModal(courseId) {
    editCourse(courseId);
}

function confirmDelete(courseId, courseName) {
    deleteCourse(courseId, courseName);
}
</script>
@endpush

@endsection
