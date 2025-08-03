@extends('layouts.app')

@section('title', 'Điểm danh khóa học: ' . $courseItem->name)

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2>Điểm danh: {{ $courseItem->name }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('course-items.tree') }}">Khóa học</a></li>
                    <li class="breadcrumb-item active">Điểm danh</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('course-items.tree') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Thông tin khóa học -->
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Thông tin điểm danh</h5>
            <div>
                <form action="{{ route('course-items.attendance', $courseItem->id) }}" method="GET" class="d-inline">
                    <div class="input-group">
                        <input type="date" name="attendance_date" class="form-control" value="{{ $attendanceDate }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-alt"></i> Đổi ngày
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p><strong>Ngày điểm danh:</strong> {{ \Carbon\Carbon::parse($attendanceDate)->format('d/m/Y') }}</p>
                    <p><strong>Tổng số khóa học:</strong> {{ $childCourseItems->count() }}</p>
                    <p><strong>Tổng số học viên:</strong> {{ $enrollments->count() }}</p>
                    
                    @if(!$courseItem->is_leaf)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Đang xem điểm danh cho tất cả khóa học con
                        </div>
                    @endif
                </div>
                <div class="col-md-8">
                    <!-- Hiển thị lịch tháng -->
                    <h4>Lịch điểm danh tháng {{ $currentMonth->format('m/Y') }}</h4>
                    <div class="calendar mb-4">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th class="text-center">T2</th>
                                    <th class="text-center">T3</th>
                                    <th class="text-center">T4</th>
                                    <th class="text-center">T5</th>
                                    <th class="text-center">T6</th>
                                    <th class="text-center">T7</th>
                                    <th class="text-center">CN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($calendar as $week)
                                    <tr>
                                        @foreach($week as $day)
                                            @php
                                                $dateStr = $day['date']->toDateString();
                                                $stats = $attendanceStats[$dateStr] ?? null;
                                                $presentCount = $stats['present'] ?? 0;
                                                $totalCount = $stats['total'] ?? 0;
                                                $hasAttendance = $totalCount > 0;
                                            @endphp
                                            <td class="calendar-day {{ !$day['is_current_month'] ? 'text-muted bg-light' : '' }} 
                                                {{ $day['is_today'] ? 'bg-info-subtle' : '' }} 
                                                {{ $day['date']->toDateString() === $attendanceDate ? 'bg-primary-subtle' : '' }}">
                                                <div class="d-flex justify-content-between">
                                                    <a href="{{ $day['url'] }}" class="day-number {{ $hasAttendance ? 'text-success fw-bold' : '' }}">
                                                        {{ $day['date']->format('j') }}
                                                    </a>
                                                    @if($hasAttendance)
                                                        <span class="badge bg-success" title="Đã điểm danh: {{ $presentCount }}/{{ $totalCount }}">
                                                            {{ $presentCount }}/{{ $totalCount }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Điểm danh học viên -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Điểm danh học viên - {{ \Carbon\Carbon::parse($attendanceDate)->format('d/m/Y') }}</h5>
        </div>
        <div class="card-body">
            @if($enrollments->isEmpty())
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Không có học viên nào đăng ký các khóa học này.
                </div>
            @else
                <form action="{{ route('course-items.attendance.store', $courseItem->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="attendance_date" value="{{ $attendanceDate }}">
                    
                    <div class="mb-3">
                        <button type="button" class="btn btn-success" id="markAllPresent">
                            <i class="fas fa-check"></i> Tất cả có mặt
                        </button>
                        <button type="button" class="btn btn-danger" id="markAllAbsent">
                            <i class="fas fa-times"></i> Tất cả vắng mặt
                        </button>
                        <button type="submit" class="btn btn-primary float-end">
                            <i class="fas fa-save"></i> Lưu điểm danh
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>STT</th>
                                    <th>Học viên</th>
                                    <th>Khóa học</th>
                                    <th>Trạng thái</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($enrollments as $index => $enrollment)
                                    @php
                                        $existingAttendance = $existingAttendances[$enrollment->id] ?? null;
                                        $status = $existingAttendance ? $existingAttendance->status : 'present';
                                        $notes = $existingAttendance ? $existingAttendance->notes : '';
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <input type="hidden" name="attendances[{{ $index }}][enrollment_id]" value="{{ $enrollment->id }}">
                                            <strong>{{ $enrollment->student->full_name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $enrollment->student->phone }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $enrollment->courseItem->name }}</span>
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input attendance-checkbox" type="checkbox" 
                                                    id="attendance-{{ $index }}" 
                                                    name="attendances[{{ $index }}][status]" 
                                                    value="present" 
                                                    {{ $status === 'present' ? 'checked' : '' }}
                                                    data-index="{{ $index }}">
                                                <label class="form-check-label" for="attendance-{{ $index }}">
                                                    <span class="attendance-status-text {{ $status === 'present' ? 'text-success' : 'text-danger' }}">
                                                        {{ $status === 'present' ? 'Có mặt' : 'Vắng mặt' }}
                                                    </span>
                                                </label>
                                                <input type="hidden" name="attendances[{{ $index }}][status]" value="{{ $status }}" class="attendance-status-input">
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" name="attendances[{{ $index }}][notes]" class="form-control" placeholder="Ghi chú" value="{{ $notes }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Lưu điểm danh
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

<style>
    .calendar-day {
        height: 60px;
        vertical-align: top;
        padding: 5px;
    }
    .day-number {
        font-weight: 500;
        text-decoration: none;
    }
    .calendar {
        max-width: 100%;
    }
</style>

@push('scripts')
<script>
    $(document).ready(function() {
        // Xử lý khi nhấp vào checkbox điểm danh
        $('.attendance-checkbox').change(function() {
            const index = $(this).data('index');
            const isChecked = $(this).prop('checked');
            
            // Cập nhật giá trị status input
            const statusInput = $(this).closest('td').find('.attendance-status-input');
            statusInput.val(isChecked ? 'present' : 'absent');
            
            // Cập nhật text hiển thị
            const statusText = $(this).closest('td').find('.attendance-status-text');
            statusText.text(isChecked ? 'Có mặt' : 'Vắng mặt');
            statusText.removeClass('text-success text-danger');
            statusText.addClass(isChecked ? 'text-success' : 'text-danger');
        });
        
        // Đánh dấu tất cả có mặt
        $('#markAllPresent').click(function() {
            $('.attendance-checkbox').prop('checked', true).trigger('change');
        });
        
        // Đánh dấu tất cả vắng mặt
        $('#markAllAbsent').click(function() {
            $('.attendance-checkbox').prop('checked', false).trigger('change');
        });
    });
</script>
@endpush
@endsection 