@extends('layouts.app')

@section('title', 'Quản lý học viên')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>Danh sách học viên
                    </h5>
                    <button type="button" class="btn btn-primary" onclick="showStudentForm()">
                        <i class="fas fa-plus me-1"></i>Thêm học viên
                    </button>
                </div>

                <!-- Students Table -->
                @if(isset($students) && $students->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="20%">Họ và tên</th>
                                    <th width="15%">Số điện thoại</th>
                                    <th width="15%">Email</th>
                                    <th width="10%">Ngày sinh</th>
                                    <th width="15%">Tỉnh/Thành</th>
                                    <th width="10%">Trạng thái</th>
                                    <th width="10%">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $index => $student)
                                    <tr>
                                        <td>{{ $students->firstItem() + $index }}</td>
                                        <td>
                                            <strong>{{ $student->full_name }}</strong>
                                            @if($student->enrollments_count > 0)
                                                <br><small class="text-muted">{{ $student->enrollments_count }} khóa học</small>
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
                                            @if($student->date_of_birth)
                                                {{ \Carbon\Carbon::parse($student->date_of_birth)->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>{{ $student->province->name ?? '-' }}</td>
                                        <td>
                                            @if($student->status === 'active')
                                                <span class="badge bg-success">Hoạt động</span>
                                            @else
                                                <span class="badge bg-secondary">Không hoạt động</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="showStudentDetail({{ $student->id }})"
                                                        title="Xem chi tiết">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-warning" 
                                                        onclick="showStudentForm({{ $student->id }})"
                                                        title="Chỉnh sửa">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="showEnrollmentForm({{ $student->id }})"
                                                        title="Ghi danh khóa học">
                                                    <i class="fas fa-graduation-cap"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="confirmDeleteStudent({{ $student->id }}, '{{ $student->full_name }}')"
                                                        title="Xóa học viên">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="card-footer">
                        {{ $students->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Chưa có học viên nào</h5>
                        <p class="text-muted">Hãy thêm học viên đầu tiên để bắt đầu quản lý.</p>
                        <button type="button" class="btn btn-primary" onclick="showStudentForm()">
                            <i class="fas fa-plus me-1"></i>Thêm học viên đầu tiên
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}

@push('scripts')
<script>
// Sử dụng Unified Modal System
document.addEventListener('app:ready', function() {
    console.log('Students page ready with Unified Modal System');
});

// Backward compatibility functions
function viewStudent(id) {
    showStudentDetail(id);
}

function editStudent(id) {
    showStudentForm(id);
}

function enrollStudent(id) {
    showEnrollmentForm(id);
}

function deleteStudent(id, name) {
    confirmDeleteStudent(id, name);
}

function addStudent() {
    showStudentForm();
}
</script>
@endpush

@endsection
