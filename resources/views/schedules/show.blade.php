@extends('layouts.app')

@section('page-title', 'Chi tiết lịch học')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2><i class="fas fa-calendar-alt me-2"></i>Chi tiết lịch học</h2>
            <p class="text-muted">{{ $schedule->courseItem->name }}</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('schedules.index') }}" class="btn btn-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i>Quay lại
            </a>
            @if(!$schedule->is_inherited)
                <a href="{{ route('schedules.edit', $schedule) }}" class="btn btn-warning">
                    <i class="fas fa-edit me-1"></i>Chỉnh sửa
                </a>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Thông tin chính -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Thông tin lịch học</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-graduation-cap me-2"></i>Khóa học</h6>
                            <p class="mb-3">{{ $schedule->courseItem->name }}</p>
                            
                            <h6><i class="fas fa-calendar-week me-2"></i>Ngày học trong tuần</h6>
                            <p class="mb-3">
                                <span class="badge bg-info fs-6">{{ $schedule->days_of_week_names }}</span>
                            </p>
                            
                            <h6><i class="fas fa-info-circle me-2"></i>Loại lịch</h6>
                            <p class="mb-3">
                                @if($schedule->is_inherited)
                                    <span class="badge bg-warning">Lịch kế thừa</span>
                                    <br><small class="text-muted">Kế thừa từ lịch gốc của khóa cha</small>
                                @else
                                    <span class="badge bg-primary">Lịch gốc</span>
                                    <br><small class="text-muted">Lịch chính, áp dụng cho tất cả khóa con</small>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-calendar-day me-2"></i>Thời gian</h6>
                            <p class="mb-3">
                                <strong>Bắt đầu:</strong> {{ $schedule->start_date->format('d/m/Y') }}<br>
                                <strong>Kết thúc:</strong> {{ $schedule->end_date->format('d/m/Y') }}<br>
                                <small class="text-muted">Thời gian: {{ $schedule->start_date->diffInWeeks($schedule->end_date) + 1 }} tuần</small>
                            </p>
                            
                            <h6><i class="fas fa-toggle-on me-2"></i>Trạng thái</h6>
                            <p class="mb-3">
                                @if($schedule->active)
                                    <span class="badge bg-success">Đang hoạt động</span>
                                @else
                                    <span class="badge bg-secondary">Tạm dừng</span>
                                @endif
                            </p>
                            
                            <h6><i class="fas fa-clock me-2"></i>Thời gian tạo</h6>
                            <p class="mb-3">
                                <small class="text-muted">{{ $schedule->created_at->format('d/m/Y H:i') }}</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lịch con kế thừa -->
            @if(!$schedule->is_inherited && $schedule->childSchedules->count() > 0)
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-sitemap me-2"></i>Lịch con kế thừa ({{ $schedule->childSchedules->count() }})</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead>
                                    <tr>
                                        <th>Khóa học con</th>
                                        <th>Đường dẫn</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($schedule->childSchedules as $childSchedule)
                                        <tr>
                                            <td>{{ $childSchedule->courseItem->name }}</td>
                                            <td><small class="text-muted">{{ $childSchedule->courseItem->path }}</small></td>
                                            <td>
                                                @if($childSchedule->active)
                                                    <span class="badge bg-success">Hoạt động</span>
                                                @else
                                                    <span class="badge bg-secondary">Tạm dừng</span>
                                                @endif
                                            </td>
                                            <td><small>{{ $childSchedule->created_at->format('d/m/Y') }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Preview các buổi học -->
            @if(isset($sampleEvents) && $sampleEvents->count() > 0)
                <div class="card mt-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Preview các buổi học (tháng này)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($sampleEvents->take(12) as $event)
                                <div class="col-md-3 mb-2">
                                    <div class="card border-left-primary">
                                        <div class="card-body p-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                {{ \Carbon\Carbon::parse($event['date'])->format('d/m/Y') }}
                                            </div>
                                            <div class="text-xs text-gray-900">
                                                {{ \Carbon\Carbon::parse($event['date'])->locale('vi')->dayName }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($sampleEvents->count() > 12)
                            <div class="text-center mt-3">
                                <small class="text-muted">Và {{ $sampleEvents->count() - 12 }} buổi học khác...</small>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Thao tác -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-cogs me-2"></i>Thao tác</h6>
                </div>
                <div class="card-body">
                    @if(!$schedule->is_inherited)
                        <div class="d-grid gap-2">
                            <a href="{{ route('schedules.edit', $schedule) }}" class="btn btn-warning">
                                <i class="fas fa-edit me-1"></i>Chỉnh sửa lịch
                            </a>
                            
                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete()">
                                <i class="fas fa-trash me-1"></i>Xóa lịch học
                            </button>
                            
                            <button type="button" class="btn btn-outline-secondary" onclick="toggleActive()">
                                @if($schedule->active)
                                    <i class="fas fa-pause me-1"></i>Tạm dừng
                                @else
                                    <i class="fas fa-play me-1"></i>Kích hoạt
                                @endif
                            </button>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Lịch kế thừa</strong><br>
                            Không thể chỉnh sửa trực tiếp. Vui lòng chỉnh sửa lịch gốc.
                        </div>
                        @if($schedule->parentSchedule)
                            <a href="{{ route('schedules.show', $schedule->parentSchedule) }}" class="btn btn-primary">
                                <i class="fas fa-arrow-up me-1"></i>Xem lịch gốc
                            </a>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Thống kê -->
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Thống kê</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-primary">{{ $schedule->childSchedules->count() }}</h4>
                                <small class="text-muted">Lịch con</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-success">{{ count($schedule->days_of_week) }}</h4>
                            <small class="text-muted">Ngày/tuần</small>
                        </div>
                    </div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-12">
                            <h5 class="text-info">
                                {{ $schedule->start_date->diffInWeeks($schedule->end_date) + 1 }}
                            </h5>
                            <small class="text-muted">Tổng số tuần</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa lịch học này?</p>
                <div class="alert alert-danger">
                    <strong>Cảnh báo:</strong> Việc xóa lịch gốc sẽ xóa tất cả {{ $schedule->childSchedules->count() }} lịch con kế thừa!
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form action="{{ route('schedules.destroy', $schedule) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDelete() {
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

function toggleActive() {
    if (confirm('Bạn có chắc chắn muốn thay đổi trạng thái lịch học này?')) {
        fetch('{{ route("schedules.toggle-active", $schedule) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Có lỗi xảy ra: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi thay đổi trạng thái');
        });
    }
}
</script>
@endpush 