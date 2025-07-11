@extends('layouts.app')

@section('page-title', 'Khóa con của ' . $course->name)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('courses.index') }}">Khóa học</a></li>
<li class="breadcrumb-item"><a href="{{ route('courses.show', $course) }}">{{ $course->name }}</a></li>
<li class="breadcrumb-item active">Khóa con</li>
@endsection

@section('content')
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-list me-2"></i>
            Danh sách khóa con của {{ $course->name }}
        </h5>
        <a href="{{ route('courses.sub-courses.create', $course) }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i>Thêm khóa con mới
        </a>
    </div>

    <div class="card-body">
        @if($course->subCourses->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="5%">STT</th>
                            <th width="30%">Tên khóa con</th>
                            <th width="15%">Mã</th>
                            <th width="10%">Học phí</th>
                            <th width="10%">Thứ tự</th>
                            <th width="15%">Hình thức</th>
                            <th width="10%">Trạng thái</th>
                            <th width="5%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($course->subCourses as $index => $subCourse)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-medium">{{ $subCourse->name }}</div>
                                @if($subCourse->description)
                                    <small class="text-muted">{{ Str::limit($subCourse->description, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ $subCourse->code ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <span class="fw-medium">{{ number_format($subCourse->fee) }}đ</span>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $subCourse->order }}</span>
                            </td>
                            <td>
                                @if($subCourse->has_online && $subCourse->has_offline)
                                    <span class="badge bg-primary">Online & Offline</span>
                                @elseif($subCourse->has_online)
                                    <span class="badge bg-success">Online</span>
                                @elseif($subCourse->has_offline)
                                    <span class="badge bg-warning">Offline</span>
                                @else
                                    <span class="badge bg-secondary">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($subCourse->active)
                                    <span class="badge bg-success">Hoạt động</span>
                                @else
                                    <span class="badge bg-secondary">Không hoạt động</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            type="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('courses.sub-courses.edit', [$course, $subCourse]) }}">
                                                <i class="fas fa-edit me-2"></i>Chỉnh sửa
                                            </a>
                                        </li>
                                        <li>
                                            <form action="{{ route('courses.sub-courses.destroy', [$course, $subCourse]) }}" method="POST" class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fas fa-trash me-2"></i>Xóa
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-list fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Chưa có khóa con nào</h5>
                <p class="text-muted">Hãy thêm khóa con đầu tiên cho khóa học này</p>
                <a href="{{ route('courses.sub-courses.create', $course) }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Thêm khóa con mới
                </a>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-info-circle me-2"></i>
            Thông tin khóa học
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-medium">Tên khóa học</label>
                    <div>{{ $course->name }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Ngành</label>
                    <div>{{ $course->major->name }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label fw-medium">Học phí</label>
                    <div>{{ number_format($course->fee) }}đ</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Loại khóa học</label>
                    <div>
                        @if($course->is_complex)
                            <span class="badge bg-primary">Phức tạp</span>
                        @else
                            <span class="badge bg-success">Tiêu chuẩn</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('courses.show', $course) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Quay lại chi tiết khóa học
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.delete-form').submit(function(e) {
            if (!confirm('Bạn có chắc muốn xóa khóa con này không?')) {
                e.preventDefault();
            }
        });
    });
</script>
@endsection 