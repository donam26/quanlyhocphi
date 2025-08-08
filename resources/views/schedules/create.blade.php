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
                                <label class="form-label">
                                    <i class="fas fa-calendar-check me-1"></i>Ngày kết thúc (tự động)
                                </label>
                                <input type="text" class="form-control" value="Tự động tính (12 tuần)" readonly>
                                <div class="form-text">Ngày kết thúc sẽ được tính tự động sau 12 tuần</div>
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
    // Auto calculate end date when start date changes
    const startDateInput = document.getElementById('start_date');
    
    startDateInput.addEventListener('change', function() {
        if (this.value) {
            const startDate = new Date(this.value);
            const endDate = new Date(startDate);
            endDate.setDate(startDate.getDate() + (12 * 7) - 1); // 12 weeks - 1 day
            
            const endDateFormatted = endDate.toLocaleDateString('vi-VN');
            const endDateInput = document.querySelector('input[readonly]');
            endDateInput.value = `${endDateFormatted} (12 tuần)`;
        }
    });
    
    // Validate at least one day selected
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const checkedDays = document.querySelectorAll('input[name="days_of_week[]"]:checked');
        if (checkedDays.length === 0) {
            e.preventDefault();
            alert('Vui lòng chọn ít nhất một ngày trong tuần!');
            return false;
        }
    });
});
</script>
@endpush 