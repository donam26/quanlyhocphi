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
            <button class="btn btn-sm btn-outline-success" onclick="exportStudents()">
                <i class="fas fa-file-excel me-1"></i>Xuất Excel
            </button>
            <button class="btn btn-sm btn-outline-info" onclick="printStudents()">
                <i class="fas fa-print me-1"></i>In danh sách
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
                <a href="{{ route('students.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Thêm học viên mới
                </a>
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
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Thông tin bổ sung -->
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <i class="fas fa-info-circle"></i> Thông tin bổ sung
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <th width="40%">Nơi làm việc:</th>
                                            <td id="student-workplace"></td>
                                        </tr>
                                        <tr>
                                            <th>Kinh nghiệm:</th>
                                            <td id="student-experience"></td>
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
                            
                            <div class="col-md-6">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" id="edit-full-name" class="form-control" required>
                                <div class="invalid-feedback" id="edit-full-name-error"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="edit-phone" class="form-control" required>
                                <div class="invalid-feedback" id="edit-phone-error"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Ngày sinh</label>
                                <input type="date" name="date_of_birth" id="edit-date-of-birth" class="form-control">
                                <div class="invalid-feedback" id="edit-date-of-birth-error"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="edit-email" class="form-control">
                                <div class="invalid-feedback" id="edit-email-error"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Giới tính</label>
                                <select name="gender" id="edit-gender" class="form-select">
                                    <option value="">Chọn giới tính</option>
                                    <option value="male">Nam</option>
                                    <option value="female">Nữ</option>
                                    <option value="other">Khác</option>
                                </select>
                                <div class="invalid-feedback" id="edit-gender-error"></div>
                            </div>
                            
                            <div class="col-md-6">
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
                            
                            <div class="col-md-6">
                                <label class="form-label">Nơi công tác hiện tại</label>
                                <input type="text" name="current_workplace" id="edit-current-workplace" class="form-control">
                                <div class="invalid-feedback" id="edit-current-workplace-error"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Số năm kinh nghiệm kế toán</label>
                                <input type="number" name="accounting_experience_years" id="edit-experience" class="form-control" min="0">
                                <div class="invalid-feedback" id="edit-experience-error"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Hồ sơ bản cứng</label>
                                <select name="hard_copy_documents" id="edit-hard-copy-documents" class="form-select">
                                    <option value="">-- Chọn trạng thái --</option>
                                    <option value="submitted">Đã nộp</option>
                                    <option value="not_submitted">Chưa nộp</option>
                                </select>
                                <div class="invalid-feedback" id="edit-hard-copy-documents-error"></div>
                            </div>

                            <div class="col-md-6">
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

                            <div class="col-md-6">
                                <label class="form-label">Đơn vị công tác</label>
                                <input type="text" name="workplace" id="edit-workplace" class="form-control">
                                <div class="invalid-feedback" id="edit-workplace-error"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Thời gian kinh nghiệm (năm)</label>
                                <input type="number" name="experience_years" id="edit-experience-years" class="form-control" min="0">
                                <div class="invalid-feedback" id="edit-experience-years-error"></div>
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
                            
                            <div class="col-md-6">
                                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" id="create-full-name" class="form-control" required>
                                <div class="invalid-feedback" id="create-full-name-error"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                                <input type="text" name="phone" id="create-phone" class="form-control" required>
                                <div class="invalid-feedback" id="create-phone-error"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Ngày sinh</label>
                                <input type="date" name="date_of_birth" id="create-date-of-birth" class="form-control">
                                <div class="invalid-feedback" id="create-date-of-birth-error"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="create-email" class="form-control">
                                <div class="invalid-feedback" id="create-email-error"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Giới tính</label>
                                <select name="gender" id="create-gender" class="form-select">
                                    <option value="">Chọn giới tính</option>
                                    <option value="male">Nam</option>
                                    <option value="female">Nữ</option>
                                    <option value="other">Khác</option>
                                </select>
                                <div class="invalid-feedback" id="create-gender-error"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Tỉnh/Thành phố</label>
                                <select name="province_id" id="create-province" class="form-select">
                                    <option value="">-- Chọn tỉnh thành --</option>
                                </select>
                                <div class="invalid-feedback" id="create-province-error"></div>
                            </div>
                        </div>
                        
                        <!-- Thông tin bổ sung -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="border-bottom pb-2 mb-3">
                                    <i class="fas fa-briefcase me-2"></i>Thông tin bổ sung
                                </h6>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Nơi công tác hiện tại</label>
                                <input type="text" name="current_workplace" id="create-current-workplace" class="form-control">
                                <div class="invalid-feedback" id="create-current-workplace-error"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Số năm kinh nghiệm kế toán</label>
                                <input type="number" name="accounting_experience_years" id="create-experience" class="form-control" min="0">
                                <div class="invalid-feedback" id="create-experience-error"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label">Hồ sơ bản cứng</label>
                                <select name="hard_copy_documents" id="create-hard-copy-documents" class="form-select">
                                    <option value="">-- Chọn trạng thái --</option>
                                    <option value="submitted">Đã nộp</option>
                                    <option value="not_submitted">Chưa nộp</option>
                                </select>
                                <div class="invalid-feedback" id="create-hard-copy-documents-error"></div>
                            </div>

                            <div class="col-md-6">
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

                            <div class="col-md-6">
                                <label class="form-label">Đơn vị công tác</label>
                                <input type="text" name="workplace" id="create-workplace" class="form-control">
                                <div class="invalid-feedback" id="create-workplace-error"></div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Thời gian kinh nghiệm (năm)</label>
                                <input type="number" name="experience_years" id="create-experience-years" class="form-control" min="0">
                                <div class="invalid-feedback" id="create-experience-years-error"></div>
                            </div>
                        </div>
                        
                        <!-- Ghi chú -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <label class="form-label">Ghi chú</label>
                                <textarea name="notes" id="create-notes" class="form-control" rows="3"></textarea>
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

    // Auto-submit form khi select thay đổi
    $('select[name="course_item_id"], select[name="status"]').change(function() {
        $(this).closest('form').submit();
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

function exportStudents() {
    alert('Chức năng xuất Excel đang được phát triển');
}

function printStudents() {
    window.print();
}

// Khởi tạo select2 cho tỉnh thành khi mở modal
$(document).on('shown.bs.modal', '#createStudentModal', function() {
    setTimeout(function() {
        initProvinceSelect2();
    }, 100);
});

$(document).on('shown.bs.modal', '#editStudentModal', function() {
    setTimeout(function() {
        initProvinceSelect2();
    }, 100);
});

// Chắc chắn rằng select2 được áp dụng khi trang load
$(document).ready(function() {
    // Đợi modal mở để khởi tạo select2
    initProvinceSelect2();
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

// Lấy tên miền theo mã
function getRegionName(region) {
    switch(region) {
        case 'north': return 'Miền Bắc';
        case 'central': return 'Miền Trung';
        case 'south': return 'Miền Nam';
        default: return 'Không xác định';
    }
}
</script>
@endpush 
 