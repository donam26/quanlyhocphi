@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Quản lý lớp học</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('classes.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm lớp học mới
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4>Danh sách lớp học</h4>
                <div class="d-flex">
                    <form action="{{ route('classes.index') }}" method="GET" class="d-flex me-2">
                        <select name="status" class="form-select me-2" onchange="this.form.submit()">
                            <option value="">-- Trạng thái --</option>
                            <option value="planned" {{ request('status') == 'planned' ? 'selected' : '' }}>Dự kiến</option>
                            <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>Đang tuyển sinh</option>
                            <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>Đang học</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Đã kết thúc</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                        <select name="type" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Loại lớp --</option>
                            <option value="online" {{ request('type') == 'online' ? 'selected' : '' }}>Online</option>
                            <option value="offline" {{ request('type') == 'offline' ? 'selected' : '' }}>Offline</option>
                            <option value="hybrid" {{ request('type') == 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($classes->isEmpty())
                <div class="alert alert-info">
                    Không có lớp học nào. Hãy tạo lớp học đầu tiên.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Tên lớp</th>
                                <th>Khóa học</th>
                                <th>Loại</th>
                                <th>Số lượng học viên</th>
                                <th>Ngày bắt đầu</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($classes as $class)
                                <tr>
                                    <td>
                                        <a href="{{ route('classes.show', $class->id) }}">{{ $class->name }}</a>
                                    </td>
                                    <td>
                                        <a href="{{ route('course-items.show', $class->courseItem->id) }}">{{ $class->courseItem->name }}</a>
                                    </td>
                                    <td>
                                        <span class="badge {{ $class->type == 'online' ? 'bg-info' : ($class->type == 'hybrid' ? 'bg-warning' : 'bg-secondary') }}">
                                            {{ $class->type == 'online' ? 'Online' : ($class->type == 'hybrid' ? 'Hybrid' : 'Offline') }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $class->student_count ?? 0 }} / {{ $class->max_students ?? 'Không giới hạn' }}
                                    </td>
                                    <td>
                                        {{ $class->start_date ? $class->start_date->format('d/m/Y') : 'Chưa xác định' }}
                                    </td>
                                    <td>
                                        @switch($class->status)
                                            @case('planned')
                                                <span class="badge bg-info">Dự kiến</span>
                                                @break
                                            @case('open')
                                                <span class="badge bg-success">Đang tuyển sinh</span>
                                                @break
                                            @case('in_progress')
                                                <span class="badge bg-primary">Đang học</span>
                                                @break
                                            @case('completed')
                                                <span class="badge bg-secondary">Đã kết thúc</span>
                                                @break
                                            @case('cancelled')
                                                <span class="badge bg-danger">Đã hủy</span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('classes.show', $class->id) }}" class="btn btn-sm btn-info" title="Chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('classes.edit', $class->id) }}" class="btn btn-sm btn-primary" title="Chỉnh sửa">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" title="Xóa" onclick="confirmDelete({{ $class->id }}, '{{ $class->name }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    {{ $classes->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal xác nhận xóa -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xóa lớp học <strong id="delete-class-name"></strong>?</p>
                <p class="text-danger">Lưu ý: Hành động này không thể hoàn tác và chỉ có thể xóa lớp học chưa có học viên.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form id="delete-form" action="" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function confirmDelete(id, name) {
        document.getElementById('delete-class-name').textContent = name;
        document.getElementById('delete-form').action = '/classes/' + id;
        var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    }
</script>
@endpush
@endsection 