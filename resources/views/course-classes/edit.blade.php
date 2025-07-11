@extends('layouts.app')

@section('page-title', 'Chỉnh sửa lớp: ' . $class->name)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('course-classes.index') }}">Lớp học</a></li>
<li class="breadcrumb-item"><a href="{{ route('course-classes.show', $class) }}">{{ $class->name }}</a></li>
<li class="breadcrumb-item active">Chỉnh sửa</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>
                    Chỉnh sửa lớp học
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('course-classes.update', $class) }}" method="POST" id="classForm">
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
                            <label class="form-label">Khóa học <span class="text-danger">*</span></label>
                            <select name="course_id" id="courseSelect" class="form-select @error('course_id') is-invalid @enderror" required>
                                <option value="">Chọn khóa học</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" 
                                            data-price="{{ $course->price }}"
                                            data-sub-courses="{{ $course->subCourses->toJson() }}"
                                            {{ old('course_id', $class->course_id) == $course->id ? 'selected' : '' }}>
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
                                <option value="online" {{ old('type', $class->type) == 'online' ? 'selected' : '' }}>Online</option>
                                <option value="offline" {{ old('type', $class->type) == 'offline' ? 'selected' : '' }}>Offline</option>
                            </select>
                            @error('type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Tên lớp <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $class->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Mô tả lớp</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="3">{{ old('description', $class->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Sub-courses -->
                    <div class="row mb-4" id="subCoursesSection">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-list-ul me-2"></i>Khóa con
                                <span class="badge bg-info ms-2" id="subCoursesCount">{{ $class->subCourses->count() }}</span>
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
                                @foreach($class->course->subCourses as $subCourse)
                                <div class="form-check mb-2">
                                    <input class="form-check-input sub-course-checkbox" type="checkbox" 
                                           name="sub_course_ids[]" value="{{ $subCourse->id }}" 
                                           data-price="{{ $subCourse->price }}" 
                                           id="subCourse{{ $subCourse->id }}"
                                           {{ $class->subCourses->contains($subCourse->id) ? 'checked' : '' }}>
                                    <label class="form-check-label d-flex justify-content-between w-100" for="subCourse{{ $subCourse->id }}">
                                        <span>
                                            <strong>{{ $subCourse->name }}</strong>
                                            @if($subCourse->description)
                                                <br><small class="text-muted">{{ $subCourse->description }}</small>
                                            @endif
                                        </span>
                                        <span class="badge bg-primary">{{ number_format($subCourse->price) }} VNĐ</span>
                                    </label>
                                </div>
                                @endforeach
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
                                   value="{{ old('price', $class->price) }}" min="0" required>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text" id="priceNote">
                                Học phí hiện tại: {{ number_format($class->price) }} VNĐ
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Sĩ số tối đa</label>
                            <input type="number" name="max_students" class="form-control @error('max_students') is-invalid @enderror" 
                                   value="{{ old('max_students', $class->max_students) }}" min="1" max="100">
                            @error('max_students')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                Hiện có {{ $class->enrollments->count() }} học viên
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="active" {{ old('status', $class->status) == 'active' ? 'selected' : '' }}>Đang mở</option>
                                <option value="full" {{ old('status', $class->status) == 'full' ? 'selected' : '' }}>Đầy</option>
                                <option value="completed" {{ old('status', $class->status) == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                                <option value="inactive" {{ old('status', $class->status) == 'inactive' ? 'selected' : '' }}>Tạm dừng</option>
                                <option value="cancelled" {{ old('status', $class->status) == 'cancelled' ? 'selected' : '' }}>Hủy</option>
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
                                   value="{{ old('start_date', $class->start_date?->format('Y-m-d')) }}">
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Ngày kết thúc dự kiến</label>
                            <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                                   value="{{ old('end_date', $class->end_date?->format('Y-m-d')) }}">
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Lịch học</label>
                            <textarea name="schedule" class="form-control @error('schedule') is-invalid @enderror" 
                                      rows="2">{{ old('schedule', $class->schedule) }}</textarea>
                            @error('schedule')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12" id="locationField" style="{{ $class->type == 'offline' ? '' : 'display: none;' }}">
                            <label class="form-label">Địa điểm học</label>
                            <textarea name="location" class="form-control @error('location') is-invalid @enderror" 
                                      rows="2">{{ old('location', $class->location) }}</textarea>
                            @error('location')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12" id="onlineField" style="{{ $class->type == 'online' ? '' : 'display: none;' }}">
                            <label class="form-label">Link học online</label>
                            <input type="url" name="online_link" class="form-control @error('online_link') is-invalid @enderror" 
                                   value="{{ old('online_link', $class->online_link) }}">
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
                                   value="{{ old('instructor_name', $class->instructor_name) }}">
                            @error('instructor_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Số điện thoại giảng viên</label>
                            <input type="tel" name="instructor_phone" class="form-control @error('instructor_phone') is-invalid @enderror" 
                                   value="{{ old('instructor_phone', $class->instructor_phone) }}">
                            @error('instructor_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Email giảng viên</label>
                            <input type="email" name="instructor_email" class="form-control @error('instructor_email') is-invalid @enderror" 
                                   value="{{ old('instructor_email', $class->instructor_email) }}">
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
                                    <a href="{{ route('course-classes.show', $class) }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                                    </a>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Cập nhật lớp học
                                    </button>
                                    <button type="submit" name="action" value="save_and_view" class="btn btn-success">
                                        <i class="fas fa-eye me-2"></i>Cập nhật và xem
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danger Zone -->
        @if($class->enrollments->count() == 0)
        <div class="card mt-4 border-danger">
            <div class="card-header bg-danger text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Vùng nguy hiểm
                </h6>
            </div>
            <div class="card-body">
                <p class="text-muted">Các thao tác trong vùng này không thể hoàn tác.</p>
                <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                    <i class="fas fa-trash me-2"></i>Xóa lớp học này
                </button>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa lớp học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa lớp học <strong>{{ $class->name }}</strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Hành động này sẽ xóa vĩnh viễn lớp học và không thể hoàn tác.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form action="{{ route('course-classes.destroy', $class) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Xóa lớp học</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
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
    
    // Sub-course price calculation
    $('.sub-course-checkbox').change(function() {
        updateTotalPrice();
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
});

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

function confirmDelete() {
    $('#deleteModal').modal('show');
}
</script>
@endsection 
 