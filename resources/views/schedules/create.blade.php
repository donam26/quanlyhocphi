@extends('layouts.app')

@section('page-title', 'Tạo lịch học mới')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-plus-circle me-2"></i>Tạo lịch học mới</h2>
            <p class="text-muted">Tạo lịch học cho khóa học cha. Lịch sẽ tự động áp dụng cho tất cả khóa con.</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('schedules.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Thông tin lịch học</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('schedules.store') }}" method="POST">
                        @csrf
                        
                        <!-- Khóa học -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="course_item_id" class="form-label">
                                    <i class="fas fa-graduation-cap me-1"></i>Khóa học cha <span class="text-danger">*</span>
                                </label>
                                <select name="course_item_id" id="course_item_id" class="form-select @error('course_item_id') is-invalid @enderror" required>
                                    <option value="">-- Chọn khóa học cha --</option>
                                    @foreach($parentCourses as $course)
                                        <option value="{{ $course->id }}" {{ old('course_item_id') == $course->id ? 'selected' : '' }}>
                                            {{ $course->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('course_item_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Chỉ có thể tạo lịch cho khóa học cha (không phải khóa lá)</div>
                            </div>
                        </div>

                        <!-- Ngày học trong tuần -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">
                                    <i class="fas fa-calendar-week me-1"></i>Ngày học trong tuần <span class="text-danger">*</span>
                                </label>
                                <div class="row">
                                    @php
                                        $days = [
                                            1 => 'Thứ 2',
                                            2 => 'Thứ 3',
                                            3 => 'Thứ 4',
                                            4 => 'Thứ 5',
                                            5 => 'Thứ 6',
                                            6 => 'Thứ 7',
                                            7 => 'Chủ nhật'
                                        ];
                                        $oldDays = old('days_of_week', []);
                                    @endphp
                                    @foreach($days as $value => $label)
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="days_of_week[]" value="{{ $value }}" 
                                                       id="day_{{ $value }}"
                                                       {{ in_array($value, $oldDays) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="day_{{ $value }}">
                                                    {{ $label }}
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @error('days_of_week')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Chọn ít nhất một ngày trong tuần</div>
                            </div>
                        </div>

                        <!-- Ngày bắt đầu -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label">
                                    <i class="fas fa-calendar-day me-1"></i>Ngày bắt đầu <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="start_date" id="start_date" 
                                       class="form-control @error('start_date') is-invalid @enderror" 
                                       value="{{ old('start_date') }}" 
                                       min="{{ date('Y-m-d') }}" required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Ngày bắt đầu khóa học</div>
                            </div>
                            <div class="col-md-6">
                                <label for="end_type" class="form-label">
                                    <i class="fas fa-calendar-check me-1"></i>Kiểu kết thúc <span class="text-danger">*</span>
                                </label>
                                <select name="end_type" id="end_type" class="form-select @error('end_type') is-invalid @enderror" required>
                                    <option value="">-- Chọn kiểu kết thúc --</option>
                                    <option value="manual" {{ old('end_type') == 'manual' ? 'selected' : '' }}>Tự đóng khi hoàn thành</option>
                                    <option value="fixed" {{ old('end_type') == 'fixed' ? 'selected' : '' }}>Cố định ngày kết thúc</option>
                                </select>
                                @error('end_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Chọn cách thức kết thúc khóa học</div>
                            </div>
                        </div>

                        <!-- Ngày kết thúc cố định (ẩn mặc định) -->
                        <div class="row mb-3" id="fixed_end_date_row" style="display: none;">
                            <div class="col-md-6 offset-md-6">
                                <label for="end_date" class="form-label">
                                    <i class="fas fa-calendar-times me-1"></i>Ngày kết thúc <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="end_date" id="end_date" 
                                       class="form-control @error('end_date') is-invalid @enderror" 
                                       value="{{ old('end_date') }}">
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Ngày kết thúc cố định của khóa học</div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="row">
                            <div class="col-md-12">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('schedules.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>Hủy
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Tạo lịch học
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sidebar thông tin -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Thông tin quan trọng</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Lưu ý</h6>
                        <ul class="mb-0">
                            <li>Chỉ có thể tạo lịch cho khóa học cha</li>
                            <li>Lịch sẽ tự động áp dụng cho tất cả khóa con</li>
                            <li>Thời gian khóa học cố định 12 tuần</li>
                            <li>Mỗi khóa học chỉ có thể có một lịch hoạt động</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb me-2"></i>Mẹo</h6>
                        <ul class="mb-0">
                            <li>Chọn ngày học phù hợp với đối tượng học viên</li>
                            <li>Tránh chọn quá nhiều ngày trong tuần</li>
                            <li>Có thể chỉnh sửa lịch sau khi tạo</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const endTypeSelect = document.getElementById('end_type');
    const fixedEndDateRow = document.getElementById('fixed_end_date_row');
    const endDateInput = document.getElementById('end_date');
    const startDateInput = document.getElementById('start_date');
    
    // Handle end type change
    endTypeSelect.addEventListener('change', function() {
        if (this.value === 'fixed') {
            fixedEndDateRow.style.display = 'block';
            endDateInput.required = true;
            
            // Auto set minimum end date based on start date
            if (startDateInput.value) {
                const startDate = new Date(startDateInput.value);
                const minEndDate = new Date(startDate);
                minEndDate.setDate(startDate.getDate() + 7); // Minimum 1 week
                endDateInput.min = minEndDate.toISOString().split('T')[0];
            }
        } else {
            fixedEndDateRow.style.display = 'none';
            endDateInput.required = false;
            endDateInput.value = '';
        }
    });
    
    // Update minimum end date when start date changes
    startDateInput.addEventListener('change', function() {
        if (this.value && endTypeSelect.value === 'fixed') {
            const startDate = new Date(this.value);
            const minEndDate = new Date(startDate);
            minEndDate.setDate(startDate.getDate() + 7); // Minimum 1 week
            endDateInput.min = minEndDate.toISOString().split('T')[0];
            
            // Clear end date if it's before new minimum
            if (endDateInput.value && new Date(endDateInput.value) < minEndDate) {
                endDateInput.value = '';
            }
        }
    });
    
    // Validate form submission
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        // Check at least one day selected
        const checkedDays = document.querySelectorAll('input[name="days_of_week[]"]:checked');
        if (checkedDays.length === 0) {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất một ngày trong tuần!');
            return false;
        }
        
        // Check end date if fixed type
        if (endTypeSelect.value === 'fixed' && !endDateInput.value) {
            e.preventDefault();
            alert('Vui lòng chọn ngày kết thúc!');
            endDateInput.focus();
            return false;
        }
        
        // Check end date is after start date
        if (endTypeSelect.value === 'fixed' && startDateInput.value && endDateInput.value) {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);
            if (endDate <= startDate) {
                e.preventDefault();
                alert('Ngày kết thúc phải sau ngày bắt đầu!');
                endDateInput.focus();
                return false;
            }
        }
    });
    
    // Trigger change event on page load to handle old values
    if (endTypeSelect.value) {
        endTypeSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endpush 