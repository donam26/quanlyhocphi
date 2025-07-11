@extends('layouts.app')

@section('page-title', 'Thêm khóa học mới')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('courses.index') }}">Khóa học</a></li>
<li class="breadcrumb-item active">Thêm mới</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-graduation-cap me-2"></i>
                    Thông tin khóa học mới
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('courses.store') }}" method="POST" id="courseForm">
                    @csrf
                    
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
                                   value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Ngành <span class="text-danger">*</span></label>
                            <select name="major_id" class="form-select @error('major_id') is-invalid @enderror" required>
                                <option value="">Chọn ngành</option>
                                @foreach($majors as $major)
                                    <option value="{{ $major->id }}" {{ old('major_id') == $major->id ? 'selected' : '' }}>
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
                                      rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Học phí (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control @error('price') is-invalid @enderror" 
                                   value="{{ old('price') }}" min="0" required>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Thời lượng (giờ)</label>
                            <input type="number" name="duration_hours" class="form-control @error('duration_hours') is-invalid @enderror" 
                                   value="{{ old('duration_hours') }}" min="1">
                            @error('duration_hours')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Trạng thái</label>
                            <select name="is_active" class="form-select @error('is_active') is-invalid @enderror">
                                <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Hoạt động</option>
                                <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Tạm dừng</option>
                            </select>
                            @error('is_active')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Sub-courses (Khóa con) -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h6 class="mb-0">
                                    <i class="fas fa-list me-2"></i>Khóa con (Sub-courses)
                                    <small class="text-muted">- Dành cho "Đào tạo nghề kế toán"</small>
                                </h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="hasSubCourses">
                                    <label class="form-check-label" for="hasSubCourses">
                                        Có khóa con
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12" id="subCoursesContainer" style="display: none;">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Lưu ý:</strong> Khóa "Đào tạo nghề kế toán" bao gồm 10 khóa con cố định. 
                                Tích chọn để tự động thêm các khóa con này.
                            </div>
                            
                            <div class="row" id="predefinedSubCourses">
                                <div class="col-12 mb-3">
                                    <button type="button" class="btn btn-primary btn-sm" onclick="addPredefinedSubCourses()">
                                        <i class="fas fa-magic me-1"></i>Thêm 10 khóa con chuẩn
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm ms-2" onclick="addCustomSubCourse()">
                                        <i class="fas fa-plus me-1"></i>Thêm khóa con tùy chỉnh
                                    </button>
                                </div>
                            </div>
                            
                            <div id="subCoursesList">
                                <!-- Sub-courses will be added here -->
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('courses.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Hủy
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Lưu khóa học
                                </button>
                                <button type="submit" name="action" value="save_and_add_class" class="btn btn-success">
                                    <i class="fas fa-chalkboard-teacher me-2"></i>Lưu và tạo lớp
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preview Card -->
        <div class="card mt-4" id="previewCard" style="display: none;">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-eye me-2"></i>Xem trước khóa học
                </h6>
            </div>
            <div class="card-body">
                <div id="coursePreview">
                    <!-- Preview content will be generated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sub-course Template -->
<template id="subCourseTemplate">
    <div class="sub-course-item border rounded p-3 mb-3">
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">Tên khóa con</label>
                <input type="text" name="sub_courses[INDEX][name]" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Học phí (VNĐ)</label>
                <input type="number" name="sub_courses[INDEX][price]" class="form-control" min="0" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Thứ tự</label>
                <input type="number" name="sub_courses[INDEX][order]" class="form-control" min="1" value="1">
            </div>
            <div class="col-md-2">
                <label class="form-label">Trạng thái</label>
                <select name="sub_courses[INDEX][is_active]" class="form-select">
                    <option value="1">Hoạt động</option>
                    <option value="0">Tạm dừng</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-danger w-100" onclick="removeSubCourse(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="col-12 mt-2">
                <label class="form-label">Mô tả</label>
                <textarea name="sub_courses[INDEX][description]" class="form-control" rows="2"></textarea>
            </div>
        </div>
    </div>
</template>
@endsection

@section('scripts')
<script>
let subCourseIndex = 0;

// Predefined sub-courses for accounting training
const predefinedSubCourses = [
    { name: 'ĐTN 2.5T', price: 500000, order: 1, description: 'Đào tạo nghề kế toán 2.5 tháng' },
    { name: 'ĐTN 3T', price: 600000, order: 2, description: 'Đào tạo nghề kế toán 3 tháng' },
    { name: 'ĐTN 4T', price: 800000, order: 3, description: 'Đào tạo nghề kế toán 4 tháng' },
    { name: 'ĐTN 5T', price: 1000000, order: 4, description: 'Đào tạo nghề kế toán 5 tháng' },
    { name: 'KTTH', price: 750000, order: 5, description: 'Kế toán tổng hợp' },
    { name: 'KT sổ', price: 450000, order: 6, description: 'Kế toán sổ sách' },
    { name: 'KT máy', price: 550000, order: 7, description: 'Kế toán máy tính' },
    { name: 'KT Thuế', price: 650000, order: 8, description: 'Kế toán thuế' },
    { name: 'BCTC', price: 700000, order: 9, description: 'Báo cáo tài chính' }
];

$(document).ready(function() {
    // Toggle sub-courses
    $('#hasSubCourses').change(function() {
        if ($(this).is(':checked')) {
            $('#subCoursesContainer').show();
        } else {
            $('#subCoursesContainer').hide();
            $('#subCoursesList').empty();
            subCourseIndex = 0;
        }
        updatePreview();
    });

    // Real-time preview
    $('#courseForm input, #courseForm textarea, #courseForm select').on('input change', function() {
        updatePreview();
    });

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

        // Validate price
        const price = parseInt($('input[name="price"]').val());
        if (price < 0) {
            $('input[name="price"]').addClass('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            alert('Vui lòng kiểm tra lại thông tin đã nhập!');
        }
    });
});

function addPredefinedSubCourses() {
    if (confirm('Thêm 9 khóa con chuẩn cho "Đào tạo nghề kế toán"?')) {
        predefinedSubCourses.forEach(function(subCourse) {
            addSubCourse(subCourse);
        });
        updatePreview();
    }
}

function addCustomSubCourse() {
    addSubCourse();
    updatePreview();
}

function addSubCourse(data = null) {
    const template = document.getElementById('subCourseTemplate');
    const clone = template.content.cloneNode(true);
    
    // Replace INDEX placeholder
    const html = clone.innerHTML.replace(/INDEX/g, subCourseIndex);
    
    const div = document.createElement('div');
    div.innerHTML = html;
    const subCourseElement = div.firstElementChild;
    
    // Fill data if provided
    if (data) {
        subCourseElement.querySelector('input[name$="[name]"]').value = data.name;
        subCourseElement.querySelector('input[name$="[price]"]').value = data.price;
        subCourseElement.querySelector('input[name$="[order]"]').value = data.order;
        subCourseElement.querySelector('textarea[name$="[description]"]').value = data.description || '';
    }
    
    document.getElementById('subCoursesList').appendChild(subCourseElement);
    subCourseIndex++;
}

function removeSubCourse(button) {
    if (confirm('Xóa khóa con này?')) {
        $(button).closest('.sub-course-item').remove();
        updatePreview();
    }
}

function updatePreview() {
    const name = $('input[name="name"]').val();
    const majorId = $('select[name="major_id"]').val();
    const majorName = $('select[name="major_id"] option:selected').text();
    const price = $('input[name="price"]').val();
    const description = $('textarea[name="description"]').val();
    const hasSubCourses = $('#hasSubCourses').is(':checked');
    
    if (name || price) {
        let preview = `
            <div class="course-preview">
                <h5 class="mb-3">${name || 'Tên khóa học'}</h5>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Ngành:</strong> ${majorName || 'Chưa chọn'}</p>
                        <p><strong>Học phí:</strong> ${price ? numberFormat(price) + 'đ' : 'Chưa nhập'}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Mô tả:</strong> ${description || 'Chưa có mô tả'}</p>
                    </div>
                </div>
        `;
        
        if (hasSubCourses) {
            const subCourses = $('.sub-course-item');
            if (subCourses.length > 0) {
                preview += '<h6 class="mt-3 mb-2">Khóa con:</h6><ul class="list-group">';
                subCourses.each(function() {
                    const subName = $(this).find('input[name$="[name]"]').val();
                    const subPrice = $(this).find('input[name$="[price]"]').val();
                    if (subName) {
                        preview += `<li class="list-group-item d-flex justify-content-between">
                            <span>${subName}</span>
                            <span class="text-primary">${subPrice ? numberFormat(subPrice) + 'đ' : '0đ'}</span>
                        </li>`;
                    }
                });
                preview += '</ul>';
            }
        }
        
        preview += '</div>';
        
        $('#coursePreview').html(preview);
        $('#previewCard').show();
    } else {
        $('#previewCard').hide();
    }
}

function numberFormat(num) {
    return parseInt(num).toLocaleString('vi-VN');
}
</script>
@endsection 
 