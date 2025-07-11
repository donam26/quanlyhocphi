@extends('layouts.app')

@section('page-title', 'Chỉnh sửa khóa học: ' . $course->name)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('courses.index') }}">Khóa học</a></li>
<li class="breadcrumb-item"><a href="{{ route('courses.show', $course) }}">{{ $course->name }}</a></li>
<li class="breadcrumb-item active">Chỉnh sửa</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>
                    Chỉnh sửa thông tin khóa học
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('courses.update', $course) }}" method="POST" id="courseForm">
                    @csrf
                    @method('PUT')
                    
                    <!-- Thông tin cơ bản -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Tên khóa học <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $course->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Ngành <span class="text-danger">*</span></label>
                            <select name="major_id" class="form-select @error('major_id') is-invalid @enderror" required>
                                <option value="">Chọn ngành</option>
                                @foreach($majors as $major)
                                    <option value="{{ $major->id }}" {{ old('major_id', $course->major_id) == $major->id ? 'selected' : '' }}>
                                        {{ $major->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('major_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Mô tả khóa học</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="3">{{ old('description', $course->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Học phí (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" 
                                   value="{{ old('price', $course->price) }}" min="0" required>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Thời lượng (giờ)</label>
                            <input type="number" name="duration_hours" class="form-control @error('duration_hours') is-invalid @enderror" 
                                   value="{{ old('duration_hours', $course->duration_hours) }}" min="1">
                            @error('duration_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Trạng thái</label>
                            <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                                <option value="1" {{ old('is_active', $course->is_active) == '1' ? 'selected' : '' }}>Hoạt động</option>
                                <option value="0" {{ old('is_active', $course->is_active) == '0' ? 'selected' : '' }}>Tạm dừng</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Sub-courses hiện có -->
                    @if($course->subCourses->count() > 0)
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-list me-2"></i>Sub-courses hiện có
                                <span class="badge bg-primary ms-2">{{ $course->subCourses->count() }}</span>
                            </h6>
                        </div>
                        
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Thứ tự</th>
                                            <th>Tên</th>
                                            <th>Học phí</th>
                                            <th>Trạng thái</th>
                                            <th>Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($course->subCourses->sortBy('order') as $subCourse)
                                        <tr>
                                            <td>
                                                <input type="hidden" name="existing_sub_courses[{{ $subCourse->id }}][id]" value="{{ $subCourse->id }}">
                                                <input type="number" name="existing_sub_courses[{{ $subCourse->id }}][order]" 
                                                       class="form-control form-control-sm" style="width: 60px;" 
                                                       value="{{ $subCourse->order }}" min="1">
                                            </td>
                                            <td>
                                                <input type="text" name="existing_sub_courses[{{ $subCourse->id }}][name]" 
                                                       class="form-control form-control-sm" 
                                                       value="{{ $subCourse->name }}" required>
                                            </td>
                                            <td>
                                                <input type="number" name="existing_sub_courses[{{ $subCourse->id }}][price]" 
                                                       class="form-control form-control-sm" 
                                                       value="{{ $subCourse->price }}" min="0" required>
                                            </td>
                                            <td>
                                                <select name="existing_sub_courses[{{ $subCourse->id }}][is_active]" class="form-select form-select-sm">
                                                    <option value="1" {{ $subCourse->is_active ? 'selected' : '' }}>Hoạt động</option>
                                                    <option value="0" {{ !$subCourse->is_active ? 'selected' : '' }}>Tạm dừng</option>
                                                </select>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="markForDeletion({{ $subCourse->id }})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Thêm sub-courses mới -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h6 class="mb-0">
                                    <i class="fas fa-plus me-2"></i>Thêm sub-courses mới
                                </h6>
                                <button type="button" class="btn btn-sm btn-success" onclick="addNewSubCourse()">
                                    <i class="fas fa-plus me-1"></i>Thêm khóa con
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div id="newSubCoursesList">
                                <!-- New sub-courses will be added here -->
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <a href="{{ route('courses.show', $course) }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                                    </a>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Cập nhật khóa học
                                    </button>
                                    <button type="submit" name="action" value="save_and_manage_classes" class="btn btn-success">
                                        <i class="fas fa-chalkboard-teacher me-2"></i>Cập nhật và quản lý lớp
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Thao tác nhanh
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="d-grid">
                            <a href="{{ route('course-classes.create', ['course_id' => $course->id]) }}" class="btn btn-outline-success">
                                <i class="fas fa-plus me-2"></i>Tạo lớp học
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-primary" onclick="duplicateCourse()">
                                <i class="fas fa-copy me-2"></i>Nhân bản khóa học
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid">
                            <a href="{{ route('courses.report', $course) }}" class="btn btn-outline-info">
                                <i class="fas fa-chart-bar me-2"></i>Xem báo cáo
                            </a>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                                <i class="fas fa-trash me-2"></i>Xóa khóa học
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa khóa học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa khóa học <strong>{{ $course->name }}</strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Cảnh báo:</strong> Hành động này sẽ xóa tất cả:
                    <ul class="mb-0 mt-2">
                        <li>{{ $course->subCourses->count() }} sub-courses</li>
                        <li>{{ $course->courseClasses->count() }} lớp học</li>
                        <li>Tất cả dữ liệu liên quan</li>
                    </ul>
                </div>
                
                @if($course->courseClasses->count() > 0)
                    <div class="alert alert-danger">
                        <i class="fas fa-ban me-2"></i>
                        <strong>Không thể xóa:</strong> Khóa học này đã có lớp học. 
                        Vui lòng xóa tất cả lớp học trước khi xóa khóa học.
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                @if($course->courseClasses->count() == 0)
                    <form action="{{ route('courses.destroy', $course) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Xóa khóa học</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- New Sub-course Template -->
<template id="newSubCourseTemplate">
    <div class="new-sub-course border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Tên khóa con</label>
                <input type="text" name="new_sub_courses[INDEX][name]" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Học phí</label>
                <input type="number" name="new_sub_courses[INDEX][price]" class="form-control" min="0" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Thứ tự</label>
                <input type="number" name="new_sub_courses[INDEX][order]" class="form-control" min="1" value="1">
            </div>
            <div class="col-md-2">
                <label class="form-label">Trạng thái</label>
                <select name="new_sub_courses[INDEX][is_active]" class="form-select">
                    <option value="1">Hoạt động</option>
                    <option value="0">Tạm dừng</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-danger w-100" onclick="removeNewSubCourse(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="col-12 mt-2">
                <label class="form-label">Mô tả</label>
                <textarea name="new_sub_courses[INDEX][description]" class="form-control" rows="2"></textarea>
            </div>
        </div>
    </div>
</template>
@endsection

@section('scripts')
<script>
let newSubCourseIndex = 0;
let deletedSubCourses = [];

$(document).ready(function() {
    // Form validation
    $('#courseForm').on('submit', function(e) {
        let isValid = true;
        
        // Check required fields
        $('input[required], select[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        // Add deleted sub-courses to form
        deletedSubCourses.forEach(function(id) {
            $('<input>').attr({
                type: 'hidden',
                name: 'deleted_sub_courses[]',
                value: id
            }).appendTo('#courseForm');
        });

        if (!isValid) {
            e.preventDefault();
            alert('Vui lòng kiểm tra lại thông tin đã nhập!');
        }
    });
});

function addNewSubCourse() {
    const template = document.getElementById('newSubCourseTemplate');
    const clone = template.content.cloneNode(true);
    
    // Replace INDEX placeholder
    const html = clone.innerHTML.replace(/INDEX/g, newSubCourseIndex);
    
    const div = document.createElement('div');
    div.innerHTML = html;
    
    document.getElementById('newSubCoursesList').appendChild(div.firstElementChild);
    newSubCourseIndex++;
}

function removeNewSubCourse(button) {
    if (confirm('Xóa khóa con này?')) {
        $(button).closest('.new-sub-course').remove();
    }
}

function markForDeletion(subCourseId) {
    if (confirm('Xóa sub-course này? Hành động này không thể hoàn tác.')) {
        // Add to deleted list
        deletedSubCourses.push(subCourseId);
        
        // Hide the row
        $(`input[name="existing_sub_courses[${subCourseId}][id]"]`).closest('tr').hide();
        
        // Add a hidden input to mark as deleted
        $('<input>').attr({
            type: 'hidden',
            name: `existing_sub_courses[${subCourseId}][_delete]`,
            value: '1'
        }).appendTo('#courseForm');
    }
}

function duplicateCourse() {
    if (confirm('Nhân bản khóa học này? Tất cả sub-courses cũng sẽ được sao chép.')) {
        $.post('/api/courses/{{ $course->id }}/duplicate', {
            _token: $('meta[name="csrf-token"]').attr('content')
        }).done(function(response) {
            alert('Nhân bản thành công!');
            window.location.href = response.redirect_url;
        }).fail(function() {
            alert('Có lỗi xảy ra khi nhân bản!');
        });
    }
}

function confirmDelete() {
    $('#deleteModal').modal('show');
}
</script>
@endsection 
 