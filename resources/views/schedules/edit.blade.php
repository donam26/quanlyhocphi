@extends('layouts.app')

@section('page-title', 'Chỉnh sửa lịch học')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Chỉnh sửa lịch học</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('course-items.show', $schedule->courseItem->id) }}">{{ $schedule->courseItem->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('course-items.schedules', $schedule->courseItem->id) }}">Lịch học</a></li>
                    <li class="breadcrumb-item active">Chỉnh sửa</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('course-items.schedules', $schedule->courseItem->id) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Thông tin lịch học</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('schedules.update', $schedule->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="course_item_id" class="form-label">Khóa học <span class="text-danger">*</span></label>
                        <select name="course_item_id" id="course_item_id" class="form-control select2 @error('course_item_id') is-invalid @enderror" required>
                            <option value="">-- Chọn khóa học --</option>
                            @foreach($courseItems as $item)
                                <option value="{{ $item->id }}" {{ (old('course_item_id', $schedule->course_item_id) == $item->id) ? 'selected' : '' }}>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('course_item_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_recurring" id="is_recurring" class="form-check-input" value="1" {{ old('is_recurring', $schedule->is_recurring) ? 'checked' : '' }}>
                        <label for="is_recurring" class="form-check-label">Lịch học định kỳ</label>
                    </div>
                </div>
                
                <div id="recurring_options" class="mb-3 card p-3 bg-light" style="{{ old('is_recurring', $schedule->is_recurring) ? '' : 'display: none;' }}">
                    <div class="mb-3">
                        <label class="form-label">Các ngày học trong tuần <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap">
                            @php
                                $recurringDays = old('recurring_days', $schedule->recurring_days ?? []);
                            @endphp
                            <div class="form-check me-3">
                                <input type="checkbox" name="recurring_days[]" id="day_monday" class="form-check-input" value="monday" {{ in_array('monday', $recurringDays) ? 'checked' : '' }}>
                                <label for="day_monday" class="form-check-label">Thứ 2</label>
                            </div>
                            <div class="form-check me-3">
                                <input type="checkbox" name="recurring_days[]" id="day_tuesday" class="form-check-input" value="tuesday" {{ in_array('tuesday', $recurringDays) ? 'checked' : '' }}>
                                <label for="day_tuesday" class="form-check-label">Thứ 3</label>
                            </div>
                            <div class="form-check me-3">
                                <input type="checkbox" name="recurring_days[]" id="day_wednesday" class="form-check-input" value="wednesday" {{ in_array('wednesday', $recurringDays) ? 'checked' : '' }}>
                                <label for="day_wednesday" class="form-check-label">Thứ 4</label>
                            </div>
                            <div class="form-check me-3">
                                <input type="checkbox" name="recurring_days[]" id="day_thursday" class="form-check-input" value="thursday" {{ in_array('thursday', $recurringDays) ? 'checked' : '' }}>
                                <label for="day_thursday" class="form-check-label">Thứ 5</label>
                            </div>
                            <div class="form-check me-3">
                                <input type="checkbox" name="recurring_days[]" id="day_friday" class="form-check-input" value="friday" {{ in_array('friday', $recurringDays) ? 'checked' : '' }}>
                                <label for="day_friday" class="form-check-label">Thứ 6</label>
                            </div>
                            <div class="form-check me-3">
                                <input type="checkbox" name="recurring_days[]" id="day_saturday" class="form-check-input" value="saturday" {{ in_array('saturday', $recurringDays) ? 'checked' : '' }}>
                                <label for="day_saturday" class="form-check-label">Thứ 7</label>
                            </div>
                            <div class="form-check">
                                <input type="checkbox" name="recurring_days[]" id="day_sunday" class="form-check-input" value="sunday" {{ in_array('sunday', $recurringDays) ? 'checked' : '' }}>
                                <label for="day_sunday" class="form-check-label">Chủ nhật</label>
                            </div>
                        </div>
                        @error('recurring_days')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="start_date" class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', $schedule->start_date->format('Y-m-d')) }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label">Ngày kết thúc</label>
                        <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', $schedule->end_date ? $schedule->end_date->format('Y-m-d') : '') }}">
                        <small class="form-text text-muted">Để trống nếu không có ngày kết thúc cụ thể</small>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="notes" class="form-label">Ghi chú</label>
                    <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes', $schedule->notes) }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="d-flex justify-content-end mt-4">
                    <a href="{{ route('course-items.schedules', $schedule->courseItem->id) }}" class="btn btn-secondary me-2">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Cập nhật lịch học
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Khởi tạo Select2
        $('.select2').select2({
            width: '100%'
        });
        
        // Hiển thị/ẩn tùy chọn lịch học định kỳ
        $('#is_recurring').change(function() {
            if ($(this).is(':checked')) {
                $('#recurring_options').slideDown();
                $('#end_date').attr('required', true);
            } else {
                $('#recurring_options').slideUp();
                $('#end_date').attr('required', false);
            }
        });
    });
</script>
@endpush
@endsection 