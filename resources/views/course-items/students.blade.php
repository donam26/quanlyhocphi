@extends('layouts.app')

@section('page-title', 'Danh sách học viên - ' . $courseItem->name)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('course-items.tree') }}">Khóa học</a></li>
<li class="breadcrumb-item active">{{ $courseItem->name }}</li>
@endsection

@section('page-actions')
<button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#importExcelModal">
    <i class="fas fa-file-excel"></i> Import Excel
</button>
<a href="{{ route('course-items.waiting-list', $courseItem->id) }}" class="btn btn-warning">
    <i class="fas fa-user-clock"></i> Danh sách chờ
</a>
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

<!-- Modal Chuyển sang danh sách chờ -->
<div class="modal fade" id="moveToWaitingList" tabindex="-1" aria-labelledby="moveToWaitingListLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="moveToWaitingListLabel">Chuyển học viên sang danh sách chờ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('enrollments.move-to-waiting') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" id="enrollment_id" name="enrollment_id">
                    <p>Bạn đang chuyển học viên <strong id="student-name"></strong> sang danh sách chờ.</p>

                    <div class="mb-3">
                        <label for="reason" class="form-label">Lý do chuyển sang danh sách chờ <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                        <div class="form-text">Lý do sẽ được ghi vào hồ sơ của học viên.</div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Lưu ý: Sau khi chuyển sang danh sách chờ, học viên sẽ không thể tham gia các hoạt động của lớp học nữa.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-warning">Chuyển sang danh sách chờ</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

<!-- Modal Import Excel -->
<div class="modal fade" id="importExcelModal" tabindex="-1" aria-labelledby="importExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importExcelModalLabel">Import học viên từ Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('course-items.import-students', $courseItem->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">File Excel <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" required accept=".xlsx,.xls,.csv">
                        <div class="form-text">
                            Chỉ chấp nhận file Excel (.xlsx, .xls) hoặc CSV
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="discount_percentage" class="form-label">Giảm giá (%)</label>
                        <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" min="0" max="100" value="0">
                        <div class="form-text">
                            Áp dụng % giảm giá cho tất cả học viên import trong file này
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h6 class="mb-2">Định dạng file Excel:</h6>
                        <p class="mb-1">- Dòng đầu tiên là tiêu đề cột</p>
                        <p class="mb-1">- Các cột bắt buộc: <strong>ho_ten</strong>, <strong>so_dien_thoai</strong></p>
                        <p class="mb-1">- Các cột tùy chọn: email, ngay_sinh (dd/mm/yyyy), gioi_tinh, dia_chi, noi_cong_tac, kinh_nghiem, ghi_chu</p>
                        <div class="mt-2">
                            <a href="{{ route('course-items.download-template') }}" class="btn btn-sm btn-success">
                                <i class="fas fa-download"></i> Tải xuống template
                            </a>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Xem Ghi Chú -->
<div class="modal fade" id="viewNotesModal" tabindex="-1" aria-labelledby="viewNotesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewNotesModalLabel">Ghi chú thanh toán - <span id="note-student-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Số tiền</th>
                                <th>Phương thức</th>
                                <th>Trạng thái</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody id="notes-table-body">
                            <!-- Nội dung sẽ được thêm bằng JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
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
