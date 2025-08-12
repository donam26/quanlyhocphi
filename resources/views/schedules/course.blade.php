@extends('layouts.app')

@section('page-title', 'Lịch học: ' . $courseItem->name . ' - ' . $currentMonth->format('m/Y'))

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2>Lịch học: {{ $courseItem->name }}</h2>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('course-items.tree') }}">Khóa học</a></li>
                    <li class="breadcrumb-item active">Lịch học</li>
                </ol>
            </nav>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('schedules.create', ['course_item_id' => $courseItem->id]) }}" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Tạo lịch học mới
            </a>
            <a href="{{ route('course-items.tree') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <!-- Tìm kiếm khóa học -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Tìm kiếm khóa học khác</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="course_search">Tìm khóa học</label>
                        <select class="form-control select2" id="course_search"></select>
                        <div class="form-text text-muted">Nhập tên khóa học để tìm kiếm</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Thông tin khóa học -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Thông tin khóa học</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Tên khóa học:</strong> {{ $courseItem->name }}</p>
                    <p><strong>Học phí:</strong> {{ number_format($courseItem->fee) }} VNĐ</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Số học viên đăng ký:</strong> {{ $enrollmentCount }}</p>
                    <p><strong>Trạng thái:</strong> 
                        @if($courseItem->active)
                            <span class="badge bg-success">Đang hoạt động</span>
                        @else
                            <span class="badge bg-danger">Không hoạt động</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Điều hướng tháng -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('course-items.schedules', ['courseItem' => $courseItem->id, 'month' => $currentMonth->copy()->subMonth()->month, 'year' => $currentMonth->copy()->subMonth()->year]) }}" class="btn btn-outline-primary">
                    <i class="fas fa-chevron-left"></i> Tháng trước
                </a>
                
                <h4 class="mb-0">{{ $currentMonth->format('m/Y') }}</h4>
                
                <a href="{{ route('course-items.schedules', ['courseItem' => $courseItem->id, 'month' => $currentMonth->copy()->addMonth()->month, 'year' => $currentMonth->copy()->addMonth()->year]) }}" class="btn btn-outline-primary">
                    Tháng sau <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Lịch tháng -->
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Lịch học tháng {{ $currentMonth->format('m/Y') }}</h5>
            
            @if(empty($scheduleDates) || count($scheduleDates) === 0)
                <div class="alert alert-warning mb-0 py-1 px-2">
                    <small><i class="fas fa-exclamation-triangle"></i> Chưa có lịch học nào được thiết lập</small>
                </div>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered calendar-table">
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
                                    <td class="calendar-day {{ !$day['is_current_month'] ? 'text-muted bg-light' : '' }} 
                                        {{ $day['is_today'] ? 'bg-info-subtle' : '' }}">
                                        <div class="day-header">
                                            <span class="day-number">{{ $day['date']->format('j') }}</span>
                                            
                                            @if($day['is_current_month'])
                                                <a href="{{ route('schedules.create', ['course_item_id' => $courseItem->id, 'date' => $day['formatted_date']]) }}" class="day-add-btn" title="Thêm lịch học">
                                                    <i class="fas fa-plus-circle"></i>
                                                </a>
                                            @endif
                                        </div>
                                        
                                        <div class="day-content">
                                            @if(isset($scheduleDates[$day['formatted_date']]))
                                                @foreach($scheduleDates[$day['formatted_date']] as $schedule)
                                                    <div class="schedule-item">
                                                        <div class="schedule-info">
                                                            @if($schedule->is_recurring)
                                                                <span class="badge bg-info">Định kỳ</span>
                                                            @endif
                                                        </div>
                                                        <div class="schedule-actions">
                                                            <a href="{{ route('schedules.edit', $schedule->id) }}" class="btn btn-sm btn-link p-0 me-1" title="Chỉnh sửa">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <form action="{{ route('schedules.destroy', $schedule->id) }}" method="POST" class="d-inline delete-form">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Xóa">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endforeach
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

    <!-- Danh sách lịch học -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Danh sách lịch học</h5>
        </div>
        <div class="card-body">
            @if($schedules->isEmpty())
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Chưa có lịch học nào được thiết lập cho khóa học này.
                </div>
                <a href="{{ route('schedules.create', ['course_item_id' => $courseItem->id]) }}" class="btn btn-primary">
                    <i class="fas fa-plus-circle"></i> Tạo lịch học mới
                </a>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Loại lịch</th>
                                <th>Ngày học</th>
                                <th>Ghi chú</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($schedules->sortBy('start_date') as $schedule)
                                <tr>
                                    <td>
                                        @if($schedule->is_recurring)
                                            <span class="badge bg-primary">Định kỳ</span>
                                        @else
                                            <span class="badge bg-secondary">Đơn lẻ</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($schedule->is_recurring)
                                            <div>Từ: {{ $schedule->start_date->format('d/m/Y') }}</div>
                                            <div>Đến: {{ $schedule->end_date ? $schedule->end_date->format('d/m/Y') : 'Không giới hạn' }}</div>
                                            <div>Các ngày: {{ $schedule->recurring_days_text }}</div>
                                        @else
                                            {{ $schedule->start_date->format('d/m/Y') }} ({{ $schedule->day_of_week }})
                                        @endif
                                    </td>
                                    <td>{{ $schedule->notes ?: '--' }}</td>
                                    <td>
                                        <a href="{{ route('schedules.edit', $schedule->id) }}" class="btn btn-sm btn-outline-primary me-1">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('schedules.destroy', $schedule->id) }}" method="POST" class="d-inline delete-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    .calendar-table {
        table-layout: fixed;
    }
    .calendar-day {
        height: 120px;
        vertical-align: top;
        padding: 5px;
    }
    .day-header {
        margin-bottom: 5px;
        display: flex;
        justify-content: space-between;
    }
    .day-number {
        font-weight: 500;
    }
    .day-add-btn {
        font-size: 0.8rem;
        color: #0d6efd;
        opacity: 0.5;
    }
    .day-add-btn:hover {
        opacity: 1;
    }
    .day-content {
        overflow-y: auto;
        max-height: 90px;
    }
    .schedule-item {
        margin-bottom: 5px;
        padding: 3px;
        border-radius: 3px;
        background-color: #e3f2fd;
        font-size: 0.8rem;
    }
    .schedule-time {
        font-weight: bold;
    }
    .schedule-info {
        margin-top: 2px;
    }
    .schedule-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 2px;
    }
</style>

@push('scripts')
<script>
    $(document).ready(function() {
        // Khởi tạo Select2 cho tìm kiếm khóa học
        $('#course_search').select2({
            placeholder: 'Nhập tên khóa học...',
            minimumInputLength: 0, // Cho phép hiển thị data ngay khi mở dropdown
            ajax: {
                url: '{{ route("api.course-items.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || '',
                        limit: 20
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.map(function(item) {
                            return {
                                id: item.id,
                                text: item.path ? item.text + ' (' + item.path + ')' : item.text
                            };
                        })
                    };
                },
                cache: true
            }
        });

        // Xử lý khi chọn khóa học
        $('#course_search').on('select2:select', function (e) {
            var data = e.params.data;
            window.location.href = '{{ url("/course-items") }}/' + data.id + '/schedules';
        });

        // Xác nhận xóa lịch học
        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            if (confirm('Bạn có chắc chắn muốn xóa lịch học này không?')) {
                this.submit();
            }
        });
    });
</script>
@endpush
@endsection 