@extends('layouts.app')

@section('page-title', 'Tạo lớp học mới')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('course-classes.index') }}">Lớp học</a></li>
<li class="breadcrumb-item active">Tạo mới</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus me-2"></i>
                    Tạo lớp học mới
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('course-classes.store') }}" method="POST" id="classForm">
                    @csrf
                    
                    <!-- Thông tin cơ bản -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-info-circle me-2"></i>Thông tin cơ bản
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Khóa học <span class="text-danger">*</span></label>
                            <select name="course_id" id="courseSelect" class="form-select @error('course_id') is-invalid @enderror" required>
                                <option value="">Chọn khóa học</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" 
                                            data-price="{{ $course->price }}"
                                            data-sub-courses="{{ $course->subCourses->toJson() }}"
                                            {{ old('course_id', request('course_id')) == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }} ({{ $course->major->name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('course_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Loại lớp <span class="text-danger">*</span></label>
                            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                <option value="">Chọn loại lớp</option>
                                <option value="online" {{ old('type') == 'online' ? 'selected' : '' }}>
                                    <i class="fas fa-laptop me-1"></i>Online
                                </option>
                                <option value="offline" {{ old('type') == 'offline' ? 'selected' : '' }}>
                                    <i class="fas fa-chalkboard-teacher me-1"></i>Offline
                                </option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Tên lớp <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name') }}" placeholder="VD: Kế toán K1 - Online" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                <i class="fas fa-lightbulb me-1"></i>
                                Gợi ý: Tên sẽ được tự động tạo dựa trên khóa học và loại lớp đã chọn
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Mô tả lớp</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="3" placeholder="Mô tả chi tiết về lớp học...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Sub-courses -->
                    <div class="row mb-4" id="subCoursesSection" style="display: none;">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-list-ul me-2"></i>Khóa con
                                <span class="badge bg-info ms-2" id="subCoursesCount">0</span>
                            </h6>
                        </div>
                        
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Chọn các khóa con mà lớp này sẽ dạy. Học phí sẽ được tính dựa trên tổng các khóa con đã chọn.
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div id="subCoursesList">
                                <!-- Sub-courses will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- Học phí và sĩ số -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-money-bill-wave me-2"></i>Học phí và sĩ số
                            </h6>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Học phí (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" name="price" id="priceInput" class="form-control @error('price') is-invalid @enderror" 
                                   value="{{ old('price') }}" min="0" required>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text" id="priceNote">
                                Học phí sẽ được tự động cập nhật dựa trên khóa con đã chọn
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Sĩ số tối đa</label>
                            <input type="number" name="max_students" class="form-control @error('max_students') is-invalid @enderror" 
                                   value="{{ old('max_students', 30) }}" min="1" max="100">
                            @error('max_students')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Để trống nếu không giới hạn sĩ số</div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Trạng thái ban đầu</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Đang mở</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Tạm dừng</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Lịch học và địa điểm -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-calendar-alt me-2"></i>Lịch học và địa điểm
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Ngày bắt đầu</label>
                            <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" 
                                   value="{{ old('start_date') }}" min="{{ date('Y-m-d') }}">
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Ngày kết thúc dự kiến</label>
                            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                                   value="{{ old('end_date') }}">
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Lịch học</label>
                            <textarea name="schedule" class="form-control @error('schedule') is-invalid @enderror" 
                                      rows="2" placeholder="VD: Thứ 2, 4, 6 - 19:00-21:00">{{ old('schedule') }}</textarea>
                            @error('schedule')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12" id="locationField" style="display: none;">
                            <label class="form-label">Địa điểm học</label>
                            <textarea name="location" class="form-control @error('location') is-invalid @enderror" 
                                      rows="2" placeholder="Địa chỉ chi tiết nơi tổ chức lớp học...">{{ old('location') }}</textarea>
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12" id="onlineField" style="display: none;">
                            <label class="form-label">Link học online</label>
                            <input type="url" name="online_link" class="form-control @error('online_link') is-invalid @enderror" 
                                   value="{{ old('online_link') }}" placeholder="https://zoom.us/j/...">
                            @error('online_link')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Giảng viên -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-user-tie me-2"></i>Thông tin giảng viên
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Tên giảng viên</label>
                            <input type="text" name="instructor_name" class="form-control @error('instructor_name') is-invalid @enderror" 
                                   value="{{ old('instructor_name') }}" placeholder="Họ và tên giảng viên">
                            @error('instructor_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Số điện thoại giảng viên</label>
                            <input type="tel" name="instructor_phone" class="form-control @error('instructor_phone') is-invalid @enderror" 
                                   value="{{ old('instructor_phone') }}" placeholder="0123456789">
                            @error('instructor_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Email giảng viên</label>
                            <input type="email" name="instructor_email" class="form-control @error('instructor_email') is-invalid @enderror" 
                                   value="{{ old('instructor_email') }}" placeholder="gvien@email.com">
                            @error('instructor_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <a href="{{ route('course-classes.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                                    </a>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Tạo lớp học
                                    </button>
                                    <button type="submit" name="action" value="create_and_add_students" class="btn btn-success">
                                        <i class="fas fa-users me-2"></i>Tạo và thêm học viên
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Templates -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-magic me-2"></i>Mẫu nhanh
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-primary" onclick="useTemplate('online')">
                                <i class="fas fa-laptop me-2"></i>Lớp Online
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-success" onclick="useTemplate('offline')">
                                <i class="fas fa-chalkboard-teacher me-2"></i>Lớp Offline
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-info" onclick="useTemplate('hybrid')">
                                <i class="fas fa-layer-group me-2"></i>Lớp Kết hợp
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Auto-generate class name
    $('#courseSelect, select[name="type"]').change(function() {
        updateClassName();
        loadSubCourses();
    });
    
    // Show/hide fields based on class type
    $('select[name="type"]').change(function() {
        const type = $(this).val();
        if (type === 'online') {
            $('#onlineField').show();
            $('#locationField').hide();
        } else if (type === 'offline') {
            $('#locationField').show();
            $('#onlineField').hide();
        } else {
            $('#onlineField, #locationField').hide();
        }
    });
    
    // Form validation
    $('#classForm').on('submit', function(e) {
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
        
        // Check date validation
        const startDate = new Date($('input[name="start_date"]').val());
        const endDate = new Date($('input[name="end_date"]').val());
        
        if (startDate && endDate && startDate >= endDate) {
            alert('Ngày kết thúc phải sau ngày bắt đầu!');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Vui lòng kiểm tra lại thông tin đã nhập!');
        }
    });
    
    // Initialize
    updateClassName();
    loadSubCourses();
});

function updateClassName() {
    const courseSelect = $('#courseSelect');
    const typeSelect = $('select[name="type"]');
    const nameInput = $('input[name="name"]');
    
    if (!nameInput.val() || nameInput.val().includes(' - ')) { // Only auto-update if empty or was auto-generated
        const courseName = courseSelect.find('option:selected').text().split(' (')[0];
        const type = typeSelect.val();
        
        if (courseName && type) {
            const typeText = type === 'online' ? 'Online' : 'Offline';
            nameInput.val(`${courseName} - ${typeText}`);
        }
    }
}

function loadSubCourses() {
    const courseSelect = $('#courseSelect');
    const selectedOption = courseSelect.find('option:selected');
    const subCourses = selectedOption.data('sub-courses');
    const basePrice = selectedOption.data('price');
    
    if (subCourses && subCourses.length > 0) {
        $('#subCoursesSection').show();
        $('#subCoursesCount').text(subCourses.length);
        
        let html = '';
        subCourses.forEach((subCourse, index) => {
            html += `
                <div class="form-check mb-2">
                    <input class="form-check-input sub-course-checkbox" type="checkbox" 
                           name="sub_course_ids[]" value="${subCourse.id}" 
                           data-price="${subCourse.price}" 
                           id="subCourse${subCourse.id}" checked>
                    <label class="form-check-label d-flex justify-content-between w-100" for="subCourse${subCourse.id}">
                        <span>
                            <strong>${subCourse.name}</strong>
                            ${subCourse.description ? '<br><small class="text-muted">' + subCourse.description + '</small>' : ''}
                        </span>
                        <span class="badge bg-primary">${new Intl.NumberFormat('vi-VN').format(subCourse.price)} VNĐ</span>
                    </label>
                </div>
            `;
        });
        
        $('#subCoursesList').html(html);
        updateTotalPrice();
        
        // Bind price calculation
        $('.sub-course-checkbox').change(function() {
            updateTotalPrice();
        });
    } else {
        $('#subCoursesSection').hide();
        $('#priceInput').val(basePrice || '');
        $('#priceNote').text('Nhập học phí cho lớp học này');
    }
}

function updateTotalPrice() {
    let totalPrice = 0;
    $('.sub-course-checkbox:checked').each(function() {
        totalPrice += parseInt($(this).data('price')) || 0;
    });
    
    $('#priceInput').val(totalPrice);
    
    const checkedCount = $('.sub-course-checkbox:checked').length;
    const totalCount = $('.sub-course-checkbox').length;
    
    $('#priceNote').html(`
        <i class="fas fa-calculator me-1"></i>
        Tổng học phí từ ${checkedCount}/${totalCount} khóa con đã chọn: 
        <strong>${new Intl.NumberFormat('vi-VN').format(totalPrice)} VNĐ</strong>
    `);
}

function useTemplate(type) {
    if (type === 'online') {
        $('select[name="type"]').val('online').trigger('change');
        $('input[name="max_students"]').val(50);
        $('textarea[name="schedule"]').val('Thứ 2, 4, 6 - 19:00-21:00');
        $('input[name="online_link"]').val('https://zoom.us/j/');
    } else if (type === 'offline') {
        $('select[name="type"]').val('offline').trigger('change');
        $('input[name="max_students"]').val(30);
        $('textarea[name="schedule"]').val('Thứ 3, 5, 7 - 18:30-20:30');
        $('textarea[name="location"]').val('Tầng 2, Tòa nhà ABC, Quận 1, TP.HCM');
    } else if (type === 'hybrid') {
        $('select[name="type"]').val('online').trigger('change');
        $('input[name="max_students"]').val(40);
        $('textarea[name="schedule"]').val('Online: T2,T4,T6 19:00-21:00\nOffline: T7 14:00-17:00');
    }
    
    updateClassName();
}
</script>
@endsection 
 