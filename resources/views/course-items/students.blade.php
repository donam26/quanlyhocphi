@extends('layouts.app')

@section('page-title', 'Danh sách học viên - ' . $courseItem->name)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('course-items.tree') }}">Khóa học</a></li>
<li class="breadcrumb-item active">{{ $courseItem->name }}</li>
{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}

@endsection

@section('page-actions')
<button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#importExcelModal">
    <i class="fas fa-file-excel"></i> Import Excel
</button>
<a href="{{ route('course-items.waiting-list', $courseItem->id) }}" class="btn btn-warning">
    <i class="fas fa-user-clock"></i> Danh sách chờ
</a>
{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}

@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="me-3">
                            <div class="text-white-75 small">
                                Tổng số học viên
                                <h3 class="text-lg fw-bold">{{ $totalStudents }}</h3>
                            </div>
                        </div>
                            <i class="fas fa-users fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
<div class="card">
    <div class="card-body">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if($is_special)
            <div class="alert alert-info alert-dismissible fade show">
                <strong><i class="fas fa-info-circle"></i> Khóa học đặc biệt:</strong>
                Khóa học này có các trường thông tin tùy chỉnh bổ sung.
                @if($custom_fields && count($custom_fields) > 0)
                    <div class="mt-2">
                        <strong>Các trường thông tin tùy chỉnh:</strong>
                        @foreach($custom_fields as $field_key => $field_value)
                            <span class="badge bg-primary me-1">{{ $field_key }}</span>
                        @endforeach
                    </div>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Họ tên</th>
                        <th>Số điện thoại</th>
                        <th>Email</th>
                        <th>Khóa học</th>
                        <th>Học phí</th>
                        @if($is_special && $custom_fields)
                            @foreach($custom_fields as $field_key => $field_value)
                                <th>{{ $field_key }}</th>
                            @endforeach
                        @endif
                        <th>Ghi chú</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $student)
                    <tr>
                        <td>{{ $student['student']->full_name }}</td>
                        <td>{{ $student['student']->phone }}</td>
                        <td>{{ $student['student']->email }}</td>
                        <td>{{ $student['course_item'] }}</td>
                        <td>{{ number_format($student['final_fee'], 0, ',', '.') }} VND</td>
                        @if($is_special && $custom_fields)
                            @foreach($custom_fields as $field_key => $field_value)
                                <td>
                                    @if(isset($student['custom_fields'][$field_key]))
                                        {{ $student['custom_fields'][$field_key] }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            @endforeach
                        @endif
                        <td>
                            @if($student['has_notes'])
                            <button type="button" class="btn btn-sm btn-info"
                                   data-bs-toggle="modal"
                                   data-bs-target="#viewNotesModal"
                                   data-student-name="{{ $student['student']->full_name }}"
                                   data-notes="{{ json_encode($student['payment_notes']) }}">
                                <i class="fas fa-sticky-note"></i> Xem
                            </button>
                            @else
                            <span class="text-muted">Không có</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group">

                                <a href="{{ route('enrollments.edit', $student['enrollment_id']) }}" class="btn btn-sm btn-warning" title="Chỉnh sửa đăng ký">
                                    <i class="fas fa-user-edit"></i>
                                </a>
                                @if($student['payment_status'] != 'Đã đóng đủ')
                                <a href="{{ route('payments.quick', $student['enrollment_id']) }}" class="btn btn-sm btn-success" title="Thanh toán nhanh">
                                    <i class="fas fa-money-bill"></i>
                                </a>
                                @endif
                                <button type="button" class="btn btn-sm btn-warning"
                                        data-bs-toggle="modal"
                                        data-bs-target="#moveToWaitingList"
                                        data-enrollment-id="{{ $student['enrollment_id'] }}"
                                        data-student-name="{{ $student['student']->full_name }}"
                                        title="Chuyển sang danh sách chờ">
                                    <i class="fas fa-user-clock"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>


{{-- Tất cả modal đã được thay thế bằng Unified Modal System --}}

@endsection


                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>


</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Xử lý khi mở modal chuyển sang danh sách chờ
        $('#moveToWaitingList').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var enrollmentId = button.data('enrollment-id');
            var studentName = button.data('student-name');

            var modal = $(this);
            modal.find('#enrollment_id').val(enrollmentId);
            modal.find('#student-name').text(studentName);
        });

        // Xử lý khi mở modal xem ghi chú
        $('#viewNotesModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget);
            var studentName = button.data('student-name');
            var notes = button.data('notes');

            // Parse notes từ JSON nếu cần thiết
            if (typeof notes === 'string') {
                notes = JSON.parse(notes);
            }

            var modal = $(this);
            modal.find('#note-student-name').text(studentName);

            // Xóa dữ liệu cũ
            modal.find('#notes-table-body').empty();

            // Thêm dữ liệu mới
            notes.forEach(function(note) {
                var statusBadge = '';
                if (note.status === 'confirmed') {
                    statusBadge = '<span class="badge bg-success">Đã xác nhận</span>';
                } else if (note.status === 'pending') {
                    statusBadge = '<span class="badge bg-warning text-dark">Chờ xác nhận</span>';
                } else {
                    statusBadge = '<span class="badge bg-danger">Đã hủy</span>';
                }

                var row = '<tr>' +
                    '<td>' + note.date + '</td>' +
                    '<td>' + new Intl.NumberFormat('vi-VN').format(note.amount) + ' VND</td>' +
                    '<td>' + note.method + '</td>' +
                    '<td>' + statusBadge + '</td>' +
                    '<td>' + note.notes + '</td>' +
                    '</tr>';

                modal.find('#notes-table-body').append(row);
            });
        });
    });
</script>
@endpush
