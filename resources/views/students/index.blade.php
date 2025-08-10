@extends('layouts.app')

@section('page-title', 'Danh sách học viên')

@section('breadcrumb')
<li class="breadcrumb-item active">Học viên</li>
@endsection

@section('styles')
<style>
.student-row {
    cursor: pointer;
}
.student-row:hover {
    background-color: #f8f9fa;
}
/* Ẩn nút in danh sách nếu vẫn hiển thị */
.btn:contains("In danh sách") {
    display: none !important;
}
/* Ngăn chặn auto-submit cho modal export */
#exportStudentModal .form-select {
    pointer-events: auto;
}
</style>
@endsection

@section('content')
<!-- Toast container -->
<div class="toast-container position-fixed top-0 end-0 p-3"></div>

<!-- Filter & Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('students.index') }}" id="studentSearchForm">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <select id="student_search" name="search" class="form-control select2-ajax" style="width: 100%;">
                            @if(request('search'))
                                <option value="{{ request('search') }}" selected>{{ request('search') }}</option>
                            @endif
                        </select>
                    </div>
                    @if(request('search'))
                    <small class="form-text text-muted mt-1">
                        <i class="fas fa-info-circle"></i>
                        Kết quả tìm kiếm cho "{{ request('search') }}" -
                        @if(preg_match('/^\d+$/', request('search')))
                            Đang tìm theo số điện thoại
                        @else
                            Đang tìm theo tên, SĐT hoặc CCCD
                        @endif
                    </small>
                    @endif
                </div>
                <div class="col-md-3">
                    <select name="course_item_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Tất cả khóa học</option>
                        @php
                            $courseItems = \App\Models\CourseItem::whereNull('parent_id')->get();
                        @endphp
                        @foreach($courseItems as $courseItem)
                            <option value="{{ $courseItem->id }}" {{ request('course_item_id') == $courseItem->id ? 'selected' : '' }}>
                                {{ $courseItem->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang học</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Không hoạt động</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#createStudentModal">
                        <i class="fas fa-plus me-2"></i>Thêm mới
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Students List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-user-graduate me-2"></i>
            Danh sách học viên
            <span class="badge bg-primary ms-2">{{ $students->total() }} học viên</span>
        </h5>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-success" onclick="showExportModal()">
                <i class="fas fa-file-excel me-1"></i>Xuất Excel
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        @if($students->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="25%">Thông tin học viên</th>
                            <th width="20%">Liên hệ</th>
                            <th width="20%">Khóa học</th>
                            <th width="15%">Trạng thái</th>
                            <th width="15%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                        <tr class="student-row" onclick="showStudentDetails({{ $student->id }})">
                            <td>{{ $students->firstItem() + $index }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <div class="fw-medium">{{ $student->full_name }}</div>
                                        <small class="text-muted">
                                            <i class="fas fa-birthday-cake me-1"></i>
                                            {{ $student->date_of_birth ? $student->getFormattedDateOfBirthAttribute() : 'N/A' }}
                                        </small>
                                        @if($student->id_number)
                                            <br><small class="text-muted">CCCD: {{ $student->id_number }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <i class="fas fa-phone me-1"></i>
                                    <strong>{{ $student->phone }}</strong>
                                </div>
                                @if($student->email)
                                    <div class="text-muted small">
                                        <i class="fas fa-envelope me-1"></i>
                                        {{ $student->email }}
                                    </div>
                                @endif
                                @if($student->address)
                                    <div class="text-muted small">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        {{ Str::limit($student->address, 30) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($student->enrollments->count() > 0)
                                    @foreach($student->enrollments->take(2) as $enrollment)
                                        <div class="mb-1">
                                            <span class="fw-medium">{{ $enrollment->courseItem ? $enrollment->courseItem->name : 'N/A' }}</span>
                                            <br>
                                            <small class="text-muted">{{ $enrollment->enrollment_date ? $enrollment->enrollment_date->format('d/m/Y') : 'N/A' }}</small>
                                        </div>
                                    @endforeach
                                    @if($student->enrollments->count() > 2)
                                        <small class="text-muted">+{{ $student->enrollments->count() - 2 }} khóa khác</small>
                                    @endif
                                @else
                                    <span class="text-muted">Chưa có khóa học</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $activeEnrollments = $student->enrollments->whereIn('status', ['enrolled', 'active']);
                                    $completedEnrollments = $student->enrollments->where('status', 'completed');
                                    $waitingEnrollments = $student->enrollments->where('status', 'waiting');
                                @endphp

                                @if($activeEnrollments->count() > 0)
                                    <span class="badge bg-success">Đang học ({{ $activeEnrollments->count() }})</span>
                                @elseif($completedEnrollments->count() > 0)
                                    <span class="badge bg-success">Đã hoàn thành ({{ $completedEnrollments->count() }})</span>
                                @elseif($waitingEnrollments->count() > 0)
                                    <span class="badge bg-warning text-dark">Danh sách chờ ({{ $waitingEnrollments->count() }})</span>
                                @else
                                    <span class="badge bg-secondary">Chưa học</span>
                                @endif

                                @php
                                    $unpaidCount = $student->enrollments->filter(function($e) {
                                        return $e->getRemainingAmount() > 0;
                                    })->count();
                                @endphp

                                @if($unpaidCount > 0)
                                    <br><span class="badge bg-warning mt-1">Chưa TT: {{ $unpaidCount }}</span>
                                @endif
                            </td>
                            <td onclick="event.stopPropagation()">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            onclick="showStudentDetails({{ $student->id }})"
                                            title="Chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                            onclick="editStudent({{ $student->id }})"
                                            title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success"
                                            onclick="enrollStudent({{ $student->id }})"
                                            title="Ghi danh">
                                        <i class="fas fa-user-plus"></i>
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
                <p class="text-muted">Hãy thêm học viên đầu tiên để bắt đầu</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStudentModal">
                    <i class="fas fa-plus me-2"></i>Thêm học viên mới
                </button>
            </div>
        @endif
    </div>
</div>

<!-- Modal hiển thị chi tiết học viên -->
<div class="modal fade" id="viewStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết học viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Phần loading -->
                <div class="text-center" id="student-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>

                <!-- Chi tiết học viên -->
                <div id="student-details" style="display: none;">
                    <div class="row">
                        <div class="col-md-8">
                            <h4 id="student-name"></h4>
                            <p class="text-muted">Mã học viên: <span id="student-id"></span></p>
                        </div>
                    </div>

                    <hr>

                    <div class="row mt-3">
                        <!-- Thông tin cá nhân -->
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <i class="fas fa-user-circle"></i> Thông tin cá nhân
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Giới tính:</th>
                                            <td id="student-gender"></td>
                                        </tr>
                                        <tr>
                                            <th>Ngày sinh:</th>
                                            <td id="student-dob"></td>
                                        </tr>
                                        <tr>
                                            <th>Số điện thoại:</th>
                                            <td id="student-phone"></td>
                                        </tr>
                                        <tr>
                                            <th>Email:</th>
                                            <td id="student-email"></td>
                                        </tr>
                                        <tr>
                                            <th>Địa chỉ:</th>
                                            <td id="student-address"></td>
                                        </tr>
                                        <tr>
                                            <th>
                                                Dân tộc:
                                            </th>
                                            <td id="student-nation"></td>
                                        </tr>
                                        <tr>
                                            <th>Nơi sinh:</th>
                                            <td id="student-place-of-birth"></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Ghi chú -->
                    <div id="student-notes-section" class="card mb-3">
                        <div class="card-header bg-light">
                            <i class="fas fa-sticky-note"></i> Ghi chú
                        </div>
                        <div class="card-body">
                            <p id="student-notes"></p>
                        </div>
                    </div>

                    <!-- Trường tùy chỉnh -->
                    <div id="student-custom-fields-section" class="card mb-3">
                        <div class="card-header bg-light">
                            <i class="fas fa-list-alt"></i> Thông tin tùy chỉnh
                        </div>
                        <div class="card-body" id="student-custom-fields">
                            <!-- Các trường tùy chỉnh sẽ được thêm vào đây -->
                        </div>
                    </div>

                    <!-- Khóa học đã đăng ký -->
                    <div id="student-enrollments-section" class="card mb-3">
                        <div class="card-header bg-light">
                            <i class="fas fa-book"></i> Khóa học đã đăng ký
                        </div>
                        <div class="card-body">
                            <div id="student-enrollments">
                                <!-- Danh sách khóa học sẽ được thêm vào đây -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" onclick="editStudent(currentStudentId)">
                    <i class="fas fa-edit me-1"></i> Chỉnh sửa
                </button>
                <button type="button" class="btn btn-success" onclick="enrollStudent(currentStudentId)">
                    <i class="fas fa-user-plus me-1"></i> Ghi danh
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal chỉnh sửa học viên -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chỉnh sửa thông tin học viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading spinner -->
                <div class="text-center" id="edit-student-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>

                <!-- Edit form -->
                <div id="edit-student-form" style="display: none;">
                    <form id="studentEditForm" method="POST">
                        @csrf
                        <!-- Sử dụng POST method -->
                        <input type="hidden" id="edit-student-id" name="id">

                        <!-- Thông tin cơ bản -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-user me-2"></i>Thông tin cơ bản
                                </h6>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Họ <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="edit-full-name" class="form-control" required>
                                <div class="invalid-feedback" id="edit-first-name-error"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tên</label>
                                <input type="text" name="name" id="edit-name" class="form-control">
                                <div class="invalid-feedback" id="edit-name-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="edit-phone" class="form-control" required>
                                <div class="invalid-feedback" id="edit-phone-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngày sinh</label>
                                <input type="date" name="date_of_birth" id="edit-date-of-birth" class="form-control">
                                <div class="invalid-feedback" id="edit-date-of-birth-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nơi sinh</label>
                                <!-- Hidden field to submit the selected province name -->
                                <input type="hidden" name="place_of_birth" id="edit-place-of-birth">
                                <!-- Visible select2 to pick a province -->
                                <select id="edit-place-of-birth-select" class="form-select" data-placeholder="Chọn nơi sinh (tỉnh/thành)"></select>
                                <div class="invalid-feedback" id="edit-place-of-birth-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dân tộc</label>
                                <input type="text" name="nation" id="edit-nation" class="form-control">
                                <div class="invalid-feedback" id="edit-nation-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="edit-email" class="form-control">
                                <div class="invalid-feedback" id="edit-email-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Giới tính</label>
                                <select name="gender" id="edit-gender" class="form-select">
                                    <option value="">Chọn giới tính</option>
                                    <option value="male">Nam</option>
                                    <option value="female">Nữ</option>
                                    <option value="other">Khác</option>
                                </select>
                                <div class="invalid-feedback" id="edit-gender-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tỉnh/Thành phố</label>
                                <select name="province_id" id="edit-province" class="form-select">
                                    <option value="">-- Chọn tỉnh thành --</option>
                                </select>
                                <div class="invalid-feedback" id="edit-province-error"></div>
                            </div>
                        </div>

                        <!-- Thông tin bổ sung -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-briefcase me-2"></i>Thông tin bổ sung
                                </h6>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nơi công tác hiện tại</label>
                                <!-- Hidden field to submit the selected province name -->
                                <input type="hidden" name="current_workplace" id="edit-current-workplace">
                                <!-- Visible select2 to pick a province -->
                                <select id="edit-current-workplace-select" class="form-select" data-placeholder="Chọn nơi công tác (tỉnh/thành)"></select>
                                <div class="invalid-feedback" id="edit-current-workplace-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số năm kinh nghiệm kế toán</label>
                                <input type="number" name="accounting_experience_years" id="edit-experience" class="form-control" min="0">
                                <div class="invalid-feedback" id="edit-experience-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hồ sơ bản cứng</label>
                                <select name="hard_copy_documents" id="edit-hard-copy-documents" class="form-select">
                                    <option value="">-- Chọn trạng thái --</option>
                                    <option value="submitted">Đã nộp</option>
                                    <option value="not_submitted">Chưa nộp</option>
                                </select>
                                <div class="invalid-feedback" id="edit-hard-copy-documents-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bằng cấp</label>
                                <select name="education_level" id="edit-education-level" class="form-select">
                                    <option value="">-- Chọn bằng cấp --</option>
                                    <option value="secondary">VB2</option>
                                    <option value="vocational">Trung cấp</option>
                                    <option value="associate">Cao đẳng</option>
                                    <option value="bachelor">Đại học</option>
                                    <option value="master">Thạc sĩ</option>
                                </select>
                                <div class="invalid-feedback" id="edit-education-level-error"></div>
                            </div>

                      
                        </div>

                        <!-- Ghi chú -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label">Ghi chú</label>
                                <textarea name="notes" id="edit-notes" class="form-control" rows="3"></textarea>
                                <div class="invalid-feedback" id="edit-notes-error"></div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="save-student-btn">
                    <i class="fas fa-save me-1"></i> Lưu thay đổi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal đăng ký khóa học -->
<div class="modal fade" id="enrollStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đăng ký học viên vào khóa học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading spinner -->
                <div class="text-center" id="enroll-student-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>

                <!-- Enrollment form -->
                <div id="enroll-student-form" style="display: none;">
                    <form id="enrollmentForm" method="POST" action="javascript:void(0);" onsubmit="return false;">
                        @csrf
                        <input type="hidden" id="enroll-student-id" name="student_id">

                        <!-- Thông tin học viên -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <strong>Học viên:</strong> <span id="enroll-student-name"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin khóa học -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-book me-2"></i>Thông tin khóa học
                                </h6>
                            </div>

                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="course_item_id" class="form-label">Chọn khóa học <span class="text-danger">*</span></label>
                                    <select name="course_item_id" id="course_item_id" class="form-select" required onchange="return false;">
                                        <option value="">-- Chọn khóa học --</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="enrollment_date" class="form-label">Ngày đăng ký <span class="text-danger">*</span></label>
                                    <input type="date" name="enrollment_date" id="enrollment_date" class="form-control"
                                           value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                    <select name="status" id="status" class="form-select" required>
                                        <option value="{{ \App\Enums\EnrollmentStatus::ACTIVE->value }}">{{ \App\Enums\EnrollmentStatus::ACTIVE->label() }}</option>
                                        <option value="{{ \App\Enums\EnrollmentStatus::WAITING->value }}">{{ \App\Enums\EnrollmentStatus::WAITING->label() }}</option>
                                        <option value="{{ \App\Enums\EnrollmentStatus::COMPLETED->value }}">{{ \App\Enums\EnrollmentStatus::COMPLETED->label() }}</option>
                                        <option value="{{ \App\Enums\EnrollmentStatus::CANCELLED->value }}">{{ \App\Enums\EnrollmentStatus::CANCELLED->label() }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Thông tin học phí -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-file-invoice-dollar me-2"></i>Học phí và chiết khấu
                                </h6>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="final_fee_display" class="form-label">Học phí cuối cùng</label>
                                    <input type="text" id="final_fee_display" class="form-control" readonly>
                                    <input type="hidden" name="final_fee" id="final_fee">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="discount_percentage" class="form-label">Chiết khấu (%)</label>
                                    <input type="number" name="discount_percentage" id="discount_percentage" class="form-control"
                                           min="0" max="100" step="0.1">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="discount_amount" class="form-label">Chiết khấu (VND)</label>
                                    <input type="number" name="discount_amount" id="discount_amount" class="form-control"
                                           min="0" step="1000">
                                </div>
                            </div>
                        </div>

                        <!-- Ghi chú -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Ghi chú</label>
                                    <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success" id="save-enrollment-btn">
                    <i class="fas fa-user-plus me-1"></i> Đăng ký khóa học
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal thêm học viên mới -->
<div class="modal fade" id="createStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm học viên mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading spinner -->
                <div class="text-center" id="create-student-loading" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>

                <!-- Create form -->
                <div id="create-student-form">
                    <form id="studentCreateForm" method="POST">
                        @csrf

                        <!-- Thông tin cơ bản -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-user me-2"></i>Thông tin cơ bản
                                </h6>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" id="create-full-name" class="form-control" required>
                                <div class="invalid-feedback" id="create-full-name-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="tel" name="phone" id="create-phone" class="form-control" required pattern="[0-9]{10,11}" placeholder="0123456789">
                                <div class="invalid-feedback" id="create-phone-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Ngày sinh</label>
                                <input type="date" name="date_of_birth" id="create-date-of-birth" class="form-control">
                                <div class="invalid-feedback" id="create-date-of-birth-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nơi sinh</label>
                                <!-- Hidden field to submit the selected province name -->
                                <input type="hidden" name="place_of_birth" id="create-place-of-birth">
                                <!-- Visible select2 to pick a province -->
                                <select id="create-place-of-birth-select" class="form-select" data-placeholder="Chọn nơi sinh (tỉnh/thành)">
                                    <option value="">-- Chọn nơi sinh --</option>
                                </select>
                                <div class="invalid-feedback" id="create-place-of-birth-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Dân tộc</label>
                                <input type="text" name="nation" id="create-nation" class="form-control" placeholder="Kinh">
                                <div class="invalid-feedback" id="create-nation-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="create-email" class="form-control" placeholder="example@email.com">
                                <div class="invalid-feedback" id="create-email-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Giới tính</label>
                                <select name="gender" id="create-gender" class="form-select">
                                    <option value="">Chọn giới tính</option>
                                    <option value="male">Nam</option>
                                    <option value="female">Nữ</option>
                                    <option value="other">Khác</option>
                                </select>
                                <div class="invalid-feedback" id="create-gender-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tỉnh/Thành phố</label>
                                <select name="province_id" id="create-province" class="form-select">
                                    <option value="">-- Chọn tỉnh thành --</option>
                                </select>
                                <div class="invalid-feedback" id="create-province-error"></div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">Địa chỉ chi tiết</label>
                                <textarea name="address" id="create-address" class="form-control" rows="3" placeholder="Nhập địa chỉ chi tiết (số nhà, tên đường, phường/xã, quận/huyện...)"></textarea>
                                <div class="invalid-feedback" id="create-address-error"></div>
                            </div>
                        </div>

                        <!-- Thông tin bổ sung -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-briefcase me-2"></i>Thông tin bổ sung
                                </h6>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nơi công tác hiện tại</label>
                                <!-- Hidden field to submit the selected province name -->
                                <input type="hidden" name="current_workplace" id="create-current-workplace">
                                <!-- Visible select2 to pick a province -->
                                <select id="create-current-workplace-select" class="form-select" data-placeholder="Chọn nơi công tác (tỉnh/thành)">
                                    <option value="">-- Chọn nơi công tác --</option>
                                </select>
                                <div class="invalid-feedback" id="create-current-workplace-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Số năm kinh nghiệm kế toán</label>
                                <input type="number" name="accounting_experience_years" id="create-experience" class="form-control" min="0" max="50" placeholder="0">
                                <div class="invalid-feedback" id="create-experience-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Hồ sơ bản cứng</label>
                                <select name="hard_copy_documents" id="create-hard-copy-documents" class="form-select">
                                    <option value="">-- Chọn trạng thái --</option>
                                    <option value="submitted">Đã nộp</option>
                                    <option value="not_submitted">Chưa nộp</option>
                                </select>
                                <div class="invalid-feedback" id="create-hard-copy-documents-error"></div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bằng cấp</label>
                                <select name="education_level" id="create-education-level" class="form-select">
                                    <option value="">-- Chọn bằng cấp --</option>
                                    <option value="secondary">VB2</option>
                                    <option value="vocational">Trung cấp</option>
                                    <option value="associate">Cao đẳng</option>
                                    <option value="bachelor">Đại học</option>
                                    <option value="master">Thạc sĩ</option>
                                </select>
                                <div class="invalid-feedback" id="create-education-level-error"></div>
                            </div>



                        </div>

                        <!-- Ghi chú -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label">Ghi chú</label>
                                <textarea name="notes" id="create-notes" class="form-control" rows="3" placeholder="Nhập ghi chú về học viên (nếu có)"></textarea>
                                <div class="invalid-feedback" id="create-notes-error"></div>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="save-new-student-btn">
                    <i class="fas fa-save me-1"></i> Lưu học viên
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Export Excel -->
<div class="modal fade" id="exportStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-excel me-2"></i>Xuất danh sách học viên
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="exportForm" onsubmit="return false;">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Khóa học</label>
                            <select name="course_item_id" class="form-select export-select">
                                <option value="">Tất cả khóa học</option>
                                @php
                                    $courseItems = \App\Models\CourseItem::whereNull('parent_id')->get();
                                @endphp
                                @foreach($courseItems as $courseItem)
                                    <option value="{{ $courseItem->id }}">{{ $courseItem->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="status" class="form-select export-select">
                                <option value="">Tất cả trạng thái</option>
                                <option value="active">Đang học</option>
                                <option value="completed">Hoàn thành</option>
                                <option value="waiting">Danh sách chờ</option>
                                <option value="inactive">Không hoạt động</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tỉnh/Thành phố</label>
                            <select name="province_id" class="form-select export-select">
                                <option value="">Tất cả tỉnh thành</option>
                                @php
                                    $provinces = \App\Models\Province::orderBy('name')->get();
                                @endphp
                                @foreach($provinces as $province)
                                    <option value="{{ $province->id }}">{{ $province->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Giới tính</label>
                            <select name="gender" class="form-select export-select">
                                <option value="">Tất cả giới tính</option>
                                <option value="male">Nam</option>
                                <option value="female">Nữ</option>
                                <option value="other">Khác</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Từ ngày sinh</label>
                            <input type="date" name="date_of_birth_from" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Đến ngày sinh</label>
                            <input type="date" name="date_of_birth_to" class="form-control">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Các cột cần xuất</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="full_name" id="col_name" checked>
                                        <label class="form-check-label" for="col_name">Họ và tên</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="phone" id="col_phone" checked>
                                        <label class="form-check-label" for="col_phone">Số điện thoại</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="email" id="col_email" checked>
                                        <label class="form-check-label" for="col_email">Email</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="date_of_birth" id="col_dob" checked>
                                        <label class="form-check-label" for="col_dob">Ngày sinh</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="gender" id="col_gender">
                                        <label class="form-check-label" for="col_gender">Giới tính</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="address" id="col_address">
                                        <label class="form-check-label" for="col_address">Địa chỉ</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="province" id="col_province">
                                        <label class="form-check-label" for="col_province">Tỉnh/Thành phố</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="workplace" id="col_workplace">
                                        <label class="form-check-label" for="col_workplace">Nơi làm việc</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="experience_years" id="col_exp">
                                        <label class="form-check-label" for="col_exp">Kinh nghiệm</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="columns[]" value="enrollments" id="col_enrollments" checked>
                                        <label class="form-check-label" for="col_enrollments">Khóa học đã đăng ký</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success" onclick="executeExport()">
                    <i class="fas fa-download me-1"></i>Xuất Excel
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/student-list.js') }}"></script>
<script>
$(document).ready(function() {
    // Cấu hình Select2 cho ô tìm kiếm học viên với AJAX
    $('#student_search').select2({
        theme: 'bootstrap-5',
        placeholder: 'Tìm theo tên, SĐT, CCCD...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '{{ route("api.search.autocomplete") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,  // Sử dụng ID của học viên
                            text: item.text
                        };
                    })
                };
            },
            cache: true
        }
    });

    // Auto-submit form khi select thay đổi (chỉ cho form chính, không phải modal export)
    $('#studentSearchForm select[name="course_item_id"], #studentSearchForm select[name="status"]').change(function() {
        $(this).closest('form').submit();
    });
    
    // Ngăn chặn auto-submit cho modal export
    $(document).on('change', '#exportStudentModal select', function(e) {
        e.stopPropagation();
        e.preventDefault();
        return false;
    });

    // Auto-submit khi search select2 thay đổi
    $('#student_search').on('select2:select', function(e) {
        var studentId = e.params.data.id;
        // Thay đổi URL để truy vấn theo ID học viên
        window.location.href = '{{ route("students.index") }}?student_id=' + studentId;
    });

    // Xóa tìm kiếm khi clear
    $('#student_search').on('select2:clear', function(e) {
        window.location.href = '{{ route("students.index") }}';
    });
});

function showExportModal() {
    $('#exportStudentModal').modal('show');
    
    // Ngăn chặn auto-submit cho các select trong modal export
    setTimeout(function() {
        $('.export-select').off('change').on('change', function(e) {
            e.stopPropagation();
            // Không làm gì cả, chỉ ngăn chặn submit
        });
    }, 100);
}

function executeExport() {
    // Lấy dữ liệu từ form
    const formData = new FormData(document.getElementById('exportForm'));
    
    // Tạo URL với query parameters
    const params = new URLSearchParams();
    
    // Xử lý các field thông thường
    for (let [key, value] of formData.entries()) {
        if (value && key !== 'columns[]') {
            params.append(key, value);
        }
    }
    
    // Xử lý checkbox columns[] riêng
    const checkedColumns = [];
    document.querySelectorAll('input[name="columns[]"]:checked').forEach(function(checkbox) {
        checkedColumns.push(checkbox.value);
    });
    
    if (checkedColumns.length > 0) {
        checkedColumns.forEach(function(column) {
            params.append('columns[]', column);
        });
    }
    
    // Tạo URL export
    const exportUrl = '{{ route("students.export") }}?' + params.toString();
    
    // Hiển thị loading
    const exportBtn = document.querySelector('#exportStudentModal .btn-success');
    const originalText = exportBtn.innerHTML;
    exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Đang xuất...';
    exportBtn.disabled = true;
    
    // Mở link download trong tab mới
    window.open(exportUrl, '_blank');
    
    // Reset button sau 2 giây
    setTimeout(function() {
        exportBtn.innerHTML = originalText;
        exportBtn.disabled = false;
        $('#exportStudentModal').modal('hide');
    }, 2000);
}

// Khởi tạo select2 cho tỉnh thành khi mở modal
$(document).on('shown.bs.modal', '#createStudentModal', function() {
    setTimeout(function() {
        initProvinceSelect2();
        initLocationSelect2();
    }, 100);
});

$(document).on('shown.bs.modal', '#editStudentModal', function() {
    setTimeout(function() {
        initProvinceSelect2();
        initLocationSelect2();
    }, 100);
});

// Chắc chắn rằng select2 được áp dụng khi trang load
$(document).ready(function() {
    // Đợi modal mở để khởi tạo select2
    initProvinceSelect2();
    initLocationSelect2();
});

// Hàm khởi tạo select2 cho tỉnh thành với AJAX
function initProvinceSelect2() {
    // Xóa các select2 hiện có để tránh lỗi
    try {
        $('#create-province, #edit-province').select2('destroy');
    } catch (e) {
        // Không làm gì nếu select2 chưa được áp dụng
    }

    // Áp dụng select2 cho từng element cụ thể
    $('#create-province, #edit-province').each(function() {
        $(this).select2({
            theme: 'bootstrap-5',
            placeholder: 'Tìm kiếm tỉnh thành...',
            allowClear: true,
            dropdownParent: $(this).closest('.modal'),
            width: '100%',
            language: {
                inputTooShort: function() {
                    return "Nhập ít nhất 1 ký tự để tìm kiếm...";
                },
                searching: function() {
                    return "Đang tìm kiếm...";
                },
                noResults: function() {
                    return "Không tìm thấy kết quả";
                }
            },
            ajax: {
                url: '/api/provinces',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        keyword: params.term || ''
                    };
                },
                processResults: function(response) {
                    console.log("API response:", response);
                    if (response && response.success && response.data && Array.isArray(response.data)) {
                        return {
                            results: response.data.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.name + ' (' + getRegionName(item.region) + ')',
                                    region: item.region
                                };
                            })
                        };
                    } else {
                        console.log("Invalid response format or empty data");
                        return { results: [] };
                    }
                },
                cache: false
            },
            minimumInputLength: 1
        });
    });
}

// Khởi tạo select2 cho các trường chọn địa điểm (nơi sinh, nơi công tác)
function initLocationSelect2(){
    try {
        $('#edit-place-of-birth-select, #edit-current-workplace-select, #create-place-of-birth-select, #create-current-workplace-select').select2('destroy');
    } catch (e) {}

    $('#edit-place-of-birth-select, #edit-current-workplace-select, #create-place-of-birth-select, #create-current-workplace-select').each(function(){
        let $hidden;
        if ($(this).attr('id') === 'edit-place-of-birth-select') {
            $hidden = $('#edit-place-of-birth');
        } else if ($(this).attr('id') === 'edit-current-workplace-select') {
            $hidden = $('#edit-current-workplace');
        } else if ($(this).attr('id') === 'create-place-of-birth-select') {
            $hidden = $('#create-place-of-birth');
        } else if ($(this).attr('id') === 'create-current-workplace-select') {
            $hidden = $('#create-current-workplace');
        }

        $(this).select2({
            theme: 'bootstrap-5',
            placeholder: $(this).data('placeholder') || 'Chọn tỉnh/thành',
            allowClear: true,
            dropdownParent: $(this).closest('.modal'),
            width: '100%',
            ajax: {
                url: '/api/provinces',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { keyword: params.term || '' };
                },
                processResults: function(response){
                    if (response && response.success && Array.isArray(response.data)){
                        return {
                            results: response.data.map(function(item){
                                return { id: item.id, text: item.name + ' (' + getRegionName(item.region) + ')' };
                            })
                        };
                    }
                    return { results: [] };
                }
            }
        });

        // Đồng bộ về hidden input khi chọn/clear
        $(this).on('select2:select', function(e){
            const text = e.params.data && e.params.data.text ? e.params.data.text : '';
            if ($hidden) $hidden.val(text);
        }).on('select2:clear', function(){
            if ($hidden) $hidden.val('');
        });
    });
}

// Lấy tên miền theo mã
function getRegionName(region) {
    switch(region) {
        case 'north': return 'Miền Bắc';
        case 'central': return 'Miền Trung';
        case 'south': return 'Miền Nam';
        default: return 'Không xác định';
    }
}

// Xử lý form tạo sinh viên mới
$(document).on('click', '#save-new-student-btn', function() {
    const form = $('#studentCreateForm');
    const formData = new FormData(form[0]);
    const button = $(this);
    
    // Disable button và hiển thị loading
    button.prop('disabled', true);
    button.html('<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...');
    
    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    $.ajax({
        url: '/api/students/create',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                // Hiển thị thông báo thành công
                toastr.success(response.message || 'Tạo học viên thành công!');
                
                // Đóng modal
                $('#createStudentModal').modal('hide');
                
                // Reset form
                form[0].reset();
                
                // Reload trang để hiển thị học viên mới
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                toastr.error(response.message || 'Có lỗi xảy ra!');
            }
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                // Validation errors
                const errors = xhr.responseJSON.errors;
                for (const field in errors) {
                    const input = $(`[name="${field}"]`);
                    input.addClass('is-invalid');
                    $(`#create-${field.replace('_', '-')}-error`).text(errors[field][0]);
                }
                toastr.error('Vui lòng kiểm tra lại thông tin!');
            } else {
                toastr.error('Có lỗi xảy ra khi tạo học viên!');
            }
        },
        complete: function() {
            // Reset button
            button.prop('disabled', false);
            button.html('<i class="fas fa-save me-1"></i> Lưu học viên');
        }
    });
});
</script>
@endpush
