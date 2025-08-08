@extends('layouts.app')

@section('page-title', 'Chỉnh sửa lịch học')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-edit me-2"></i>Chỉnh sửa lịch học</h2>
            <p class="text-muted">{{ $schedule->courseItem->name }}</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('schedules.show', $schedule) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Chỉnh sửa thông tin lịch học</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('schedules.update', $schedule) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <!-- Khóa học (readonly) -->
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">
                                    <i class="fas fa-graduation-cap me-1"></i>Khóa học
                                </label>
                                <input type="text" class="form-control" value="{{ $schedule->courseItem->name }}" readonly>
                                <div class="form-text">Không thể thay đổi khóa học sau khi tạo lịch</div>
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
                                        $selectedDays = old('days_of_week', $schedule->days_of_week ?? []);
                                    @endphp
                                    @foreach($days as $value => $label)
                                        <div class="col-md-3 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="days_of_week[]" value="{{ $value }}" 
                                                       id="day_{{ $value }}"
                                                       {{ in_array($value, $selectedDays) ? 'checked' : '' }}>
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
                                <div class="form-text">Thay đổi sẽ áp dụng cho tất cả {{ $schedule->childSchedules->count() }} lịch con</div>
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
                                       value="{{ old('start_date', $schedule->start_date->format('Y-m-d')) }}" 
                                       required>
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Ngày bắt đầu khóa học</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="fas fa-calendar-check me-1"></i>Ngày kết thúc (tự động)
                                </label>
                                <input type="text" class="form-control" 
                                       value="{{ $schedule->end_date->format('d/m/Y') }} (12 tuần)" readonly>
                                <div class="form-text">Ngày kết thúc sẽ được tính tự động sau 12 tuần</div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="row">
                            <div class="col-md-12">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('schedules.show', $schedule) }}" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>Hủy
                                    </a>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-save me-1"></i>Cập nhật lịch học
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
            <!-- Thông tin hiện tại -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Thông tin hiện tại</h6>
                </div>
                <div class="card-body">
                    <h6>Ngày học hiện tại:</h6>
                    <p><span class="badge bg-info">{{ $schedule->days_of_week_names }}</span></p>
                    
                    <h6>Thời gian hiện tại:</h6>
                    <p>
                        <strong>Từ:</strong> {{ $schedule->start_date->format('d/m/Y') }}<br>
                        <strong>Đến:</strong> {{ $schedule->end_date->format('d/m/Y') }}
                    </p>
                    
                    <h6>Trạng thái:</h6>
                    <p>
                        @if($schedule->active)
                            <span class="badge bg-success">Đang hoạt động</span>
                        @else
                            <span class="badge bg-secondary">Tạm dừng</span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Cảnh báo -->
            <div class="card mt-3">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Cảnh báo quan trọng</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <ul class="mb-0">
                            <li>Thay đổi sẽ áp dụng cho <strong>{{ $schedule->childSchedules->count() }} lịch con</strong></li>
                            <li>Học viên đã đăng ký có thể bị ảnh hưởng</li>
                            <li>Nên thông báo trước khi thay đổi</li>
                        </ul>
                    </div>
                    
                    @if($schedule->childSchedules->count() > 0)
                        <h6>Các khóa con sẽ bị ảnh hưởng:</h6>
                        <ul class="list-unstyled">
                            @foreach($schedule->childSchedules->take(5) as $child)
                                <li><small class="text-muted">• {{ $child->courseItem->name }}</small></li>
                            @endforeach
                            @if($schedule->childSchedules->count() > 5)
                                <li><small class="text-muted">• Và {{ $schedule->childSchedules->count() - 5 }} khóa khác...</small></li>
                            @endif
                        </ul>
                    @endif
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
        
        // Confirm changes
        if (!confirm('Bạn có chắc chắn muốn cập nhật lịch học? Thay đổi sẽ áp dụng cho tất cả lịch con.')) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endpush 