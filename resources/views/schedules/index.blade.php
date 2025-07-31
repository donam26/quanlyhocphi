@extends('layouts.app')

@section('page-title', 'Lịch học - ' . $currentMonth->format('m/Y'))

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h2>Lịch học tháng {{ $currentMonth->format('m/Y') }}</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('schedules.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Tạo lịch học mới
            </a>
        </div>
    </div>

    <!-- Tìm kiếm khóa học -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Tìm kiếm khóa học</h5>
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

    <!-- Điều hướng tháng -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <a href="{{ route('schedules.index', ['month' => $currentMonth->copy()->subMonth()->month, 'year' => $currentMonth->copy()->subMonth()->year]) }}" class="btn btn-outline-primary">
                    <i class="fas fa-chevron-left"></i> Tháng trước
                </a>
                
                <h4 class="mb-0">{{ $currentMonth->format('m/Y') }}</h4>
                
                <a href="{{ route('schedules.index', ['month' => $currentMonth->copy()->addMonth()->month, 'year' => $currentMonth->copy()->addMonth()->year]) }}" class="btn btn-outline-primary">
                    Tháng sau <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Lịch tháng -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Lịch học</h5>
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
                                        </div>
                                        
                                        <div class="day-content">
                                            @if(isset($scheduleDates[$day['formatted_date']]))
                                                @foreach($scheduleDates[$day['formatted_date']] as $schedule)
                                                    <div class="schedule-item">
                                                        <a href="{{ route('course-items.schedules', $schedule->course_item_id) }}" class="schedule-link">
                                                            <div class="schedule-course">{{ $schedule->courseItem->name }}</div>
                                                            @if($schedule->is_recurring)
                                                                <span class="badge bg-info">Định kỳ</span>
                                                            @endif
                                                        </a>
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

    <!-- Danh sách khóa học -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">Khóa học</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($courseItems as $courseItem)
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">{{ $courseItem->name }}</h5>
                                <p class="card-text">
                                    <small class="text-muted">
                                        {{ $courseItem->enrollments->count() }} học viên
                                    </small>
                                </p>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="{{ route('course-items.schedules', $courseItem->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-calendar-alt"></i> Xem lịch học
                                </a>
                                <a href="{{ route('schedules.create', ['course_item_id' => $courseItem->id]) }}" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-plus-circle"></i> Tạo lịch
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
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
    }
    .day-number {
        font-weight: 500;
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
    .schedule-link {
        display: block;
        text-decoration: none;
        color: #0d6efd;
    }
    .schedule-course {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
@push('scripts')
<script>
    $(document).ready(function() {
        // Khởi tạo Select2 cho tìm kiếm khóa học
        $('#course_search').select2({
            placeholder: 'Nhập tên khóa học...',
            minimumInputLength: 2,
            ajax: {
                url: '{{ route("api.course-items.search") }}',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term
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
    });
</script>
@endpush
@endsection 