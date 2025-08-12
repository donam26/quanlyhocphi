@extends('layouts.app')

@section('title', 'Danh sách chờ')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock text-warning me-2"></i>Danh sách chờ
                    </h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" onclick="addStudentToWaiting()">
                            <i class="fas fa-plus me-1"></i>Thêm học viên
                        </button>
                        <button type="button" class="btn btn-info" onclick="importWaitingStudents()">
                            <i class="fas fa-file-excel me-1"></i>Import Excel
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    @if(isset($waitingStudents) && $waitingStudents->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="20%">Họ và tên</th>
                                        <th width="15%">Số điện thoại</th>
                                        <th width="15%">Email</th>
                                        <th width="15%">Khóa học quan tâm</th>
                                        <th width="10%">Ngày đăng ký</th>
                                        <th width="10%">Trạng thái</th>
                                        <th width="10%">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($waitingStudents as $index => $student)
                                        <tr>
                                            <td>{{ $waitingStudents->firstItem() + $index }}</td>
                                            <td>
                                                <strong>{{ $student->full_name }}</strong>
                                                @if($student->notes)
                                                    <br><small class="text-muted">{{ Str::limit($student->notes, 50) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="tel:{{ $student->phone }}" class="text-decoration-none">
                                                    {{ $student->phone }}
                                                </a>
                                            </td>
                                            <td>
                                                @if($student->email)
                                                    <a href="mailto:{{ $student->email }}" class="text-decoration-none">
                                                        {{ $student->email }}
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($student->interested_course)
                                                    <span class="badge bg-info">{{ $student->interested_course }}</span>
                                                @else
                                                    <span class="text-muted">Chưa xác định</span>
                                                @endif
                                            </td>
                                            <td>
                                                {{ \Carbon\Carbon::parse($student->created_at)->format('d/m/Y') }}
                                            </td>
                                            <td>
                                                @if($student->status === 'waiting')
                                                    <span class="badge bg-warning">Đang chờ</span>
                                                @elseif($student->status === 'contacted')
                                                    <span class="badge bg-info">Đã liên hệ</span>
                                                @elseif($student->status === 'confirmed')
                                                    <span class="badge bg-success">Đã xác nhận</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $student->status }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="showStudentDetail({{ $student->id }})"
                                                            title="Xem chi tiết">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-success" 
                                                            onclick="confirmStudent({{ $student->id }})"
                                                            title="Xác nhận ghi danh">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-warning" 
                                                            onclick="editWaitingStudent({{ $student->id }})"
                                                            title="Chỉnh sửa">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="removeFromWaiting({{ $student->id }}, '{{ $student->full_name }}')"
                                                            title="Xóa khỏi danh sách chờ">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Danh sách chờ trống</h5>
                            <p class="text-muted">Chưa có học viên nào trong danh sách chờ.</p>
                            <button type="button" class="btn btn-primary" onclick="addStudentToWaiting()">
                                <i class="fas fa-plus me-1"></i>Thêm học viên đầu tiên
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}

@push('scripts')
<script>
// Waiting Tree Page với Unified Modal System
document.addEventListener('app:ready', function() {
    console.log('Waiting Tree page ready with Unified Modal System');
});

// Functions để sử dụng Unified Modal System
function showStudentDetail(studentId) {
    if (window.unifiedModals) {
        window.unifiedModals.showStudentDetail(studentId);
    } else {
        alert('Chức năng xem chi tiết học viên đang được phát triển');
    }
}

function addStudentToWaiting() {
    if (window.unifiedModals) {
        window.unifiedModals.showWaitingStudentForm();
    } else {
        alert('Chức năng thêm học viên vào danh sách chờ đang được phát triển');
    }
}

function confirmStudent(studentId) {
    if (window.unifiedModals) {
        window.unifiedModals.showConfirmWaitingStudentModal(studentId);
    } else {
        alert('Chức năng xác nhận học viên đang được phát triển');
    }
}

// Legacy functions để tương thích
function viewStudent(studentId) {
    showStudentDetail(studentId);
}
</script>
@endpush

@endsection
