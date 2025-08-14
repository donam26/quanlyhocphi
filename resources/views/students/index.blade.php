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
/* Đảm bảo modal chỉnh sửa ghi danh có z-index cao nhất */
#editEnrollmentModal {
    z-index: 1080 !important;
}
#editEnrollmentModal .modal-backdrop {
    z-index: 1079 !important;
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
                        <select id="student_search" name="search" class="form-control" style="width: 100%;">
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
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary w-100" data-student-action="create">
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
            <button class="btn btn-sm btn-outline-primary" onclick="showImportModal()">
                <i class="fas fa-file-upload me-1"></i>Import Excel
            </button>
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
                            <th width="15%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                        <tr class="student-row" data-student-action="view" data-student-id="{{ $student->id }}" style="cursor: pointer;">
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
                                            <small class="text-muted">{{ $enrollment->formatted_enrollment_date ?: 'N/A' }}</small>
                                        </div>
                                    @endforeach
                                    @if($student->enrollments->count() > 2)
                                        <small class="text-muted">+{{ $student->enrollments->count() - 2 }} khóa khác</small>
                                    @endif
                                @else
                                    <span class="text-muted">Chưa có khóa học</span>
                                @endif
                            </td>
                          
                            <td onclick="event.stopPropagation()">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-student-action="view" data-student-id="{{ $student->id }}"
                                            title="Chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                            data-student-action="edit" data-student-id="{{ $student->id }}"
                                            title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success"
                                            data-student-action="enroll" data-student-id="{{ $student->id }}"
                                            title="Ghi danh">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-student-action="delete" data-student-id="{{ $student->id }}"
                                            data-student-name="{{ $student->full_name }}"
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
                <p class="text-muted">Hãy thêm học viên đầu tiên để bắt đầu</p>
                <button type="button" class="btn btn-primary" data-student-action="create">
                    <i class="fas fa-plus me-2"></i>Thêm học viên mới
                </button>
            </div>
        @endif
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
                            <input type="text" name="date_of_birth_from" class="form-control" placeholder="dd/mm/yyyy" 
                                   pattern="\d{2}/\d{2}/\d{4}" title="Nhập ngày theo định dạng dd/mm/yyyy">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Đến ngày sinh</label>
                            <input type="text" name="date_of_birth_to" class="form-control" placeholder="dd/mm/yyyy" 
                                   pattern="\d{2}/\d{2}/\d{4}" title="Nhập ngày theo định dạng dd/mm/yyyy">
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

<!-- Modal Import Excel -->
<div class="modal fade" id="importStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-upload me-2"></i>Import danh sách học viên từ Excel
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Hướng dẫn:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Hệ thống sẽ kiểm tra theo <strong>email</strong> học viên</li>
                        <li>Nếu email đã tồn tại: <strong>cập nhật</strong> thông tin mới</li>
                        <li>Nếu email chưa tồn tại: <strong>tạo mới</strong> học viên</li>
                        <li>File Excel phải có định dạng .xlsx, .xls hoặc .csv</li>
                        <li>Kích thước file tối đa: 10MB</li>
                    </ul>
                </div>

                <form id="importStudentForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="import-file" class="form-label">Chọn file Excel <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="import-file" name="file"
                               accept=".xlsx,.xls,.csv" required>
                        <div class="invalid-feedback" id="import-file-error"></div>
                        <div class="form-text">
                            Chấp nhận file: .xlsx, .xls, .csv (tối đa 10MB)
                        </div>
                    </div>



                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="downloadImportTemplate()">
                            <i class="fas fa-download me-1"></i>Tải mẫu Excel
                        </button>
                        <small class="text-muted ms-2">Tải file mẫu để xem định dạng cột</small>
                    </div>
                </form>

                <!-- Progress bar -->
                <div id="import-progress" class="d-none">
                    <div class="progress mb-3">
                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                             role="progressbar" style="width: 0%"></div>
                    </div>
                    <div class="text-center">
                        <small class="text-muted">Đang xử lý file...</small>
                    </div>
                </div>

                <!-- Import results -->
                <div id="import-results" class="d-none">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle me-2"></i>Kết quả import:</h6>
                        <ul class="mb-0">
                            <li>Tạo mới: <span id="created-count">0</span> học viên</li>
                            <li>Cập nhật: <span id="updated-count">0</span> học viên</li>
                            <li>Bỏ qua: <span id="skipped-count">0</span> dòng</li>
                        </ul>
                    </div>
                    <div id="import-errors" class="d-none">
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle me-2"></i>Lỗi trong quá trình import:</h6>
                            <div id="error-list"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="execute-import-btn" onclick="executeImport()">
                    <i class="fas fa-upload me-1"></i>Bắt đầu Import
                </button>
            </div>
        </div>
    </div>
</div>

@include('components.student-modals')
@endsection

@push('scripts')
<script src="{{ asset('js/student-form-component.js') }}"></script>
<script src="{{ asset('js/student-list.js') }}"></script>
<script src="{{ asset('js/enrollment.js') }}"></script>
<script>
$(document).ready(function() {
    // Debug: Kiểm tra xem modal có tồn tại không
    console.log('Edit enrollment modal exists:', $('#editEnrollmentModal').length);
    console.log('Edit enrollment form exists:', $('#edit-enrollment-form').length);
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

function showImportModal() {
    $('#importStudentModal').modal('show');

    // Reset form khi mở modal
    $('#importStudentForm')[0].reset();
    $('#import-progress').addClass('d-none');
    $('#import-results').addClass('d-none');
    $('#import-errors').addClass('d-none');
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
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
        console.log('Initializing select2 for create modal...');
        initProvinceSelect2();
        initLocationSelect2();
        initEthnicitySelect2();
        console.log('Select2 initialization completed');
    }, 100);
});

$(document).on('shown.bs.modal', '#editStudentModal', function() {
    setTimeout(function() {
        initProvinceSelect2();
        initLocationSelect2();
        initEthnicitySelect2();
    }, 100);
});

// Khởi tạo select2 cho khóa học khi mở modal enrollment
$(document).on('shown.bs.modal', '#enrollStudentModal', function() {
    setTimeout(function() {
        initCourseSelect2();
        // Gọi setupFeeCalculation để thiết lập event handlers cho chiết khấu
        if (typeof setupFeeCalculation === 'function') {
            setupFeeCalculation();
        }
    }, 100);
});

// Xử lý submit form ghi danh
$(document).on('submit', '#enrollmentForm', function(e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);
    const submitBtn = $('#enroll-btn');

    // Kiểm tra dữ liệu bắt buộc
    const courseId = $('#course_item_id').val();
    const studentId = $('#enroll-student-id').val();

    if (!courseId) {
        alert('Vui lòng chọn khóa học');
        return;
    }

    if (!studentId) {
        alert('Không tìm thấy thông tin học viên');
        return;
    }

    // Disable button và hiển thị loading
    submitBtn.prop('disabled', true);
    submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Đang đăng ký...');

    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');

    $.ajax({
        url: '/api/enrollments',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#enrollStudentModal').modal('hide');

                // Hiển thị thông báo thành công
                if (typeof showToast === 'function') {
                    showToast('Ghi danh học viên thành công!', 'success');
                } else {
                    alert('Ghi danh học viên thành công!');
                }

                // Reset form
                form.reset();
                $('#course_item_id').val(null).trigger('change');

                // Reload trang sau 1.5s
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            } else {
                alert(response.message || 'Có lỗi xảy ra khi ghi danh');
                // Reset button
                submitBtn.prop('disabled', false);
                submitBtn.html('<i class="fas fa-user-plus me-1"></i>Đăng ký');
            }
        },
        error: function(xhr) {
            console.error('Enrollment error:', xhr);

            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                // Hiển thị lỗi validation
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(key => {
                    const errorMessage = errors[key][0];
                    const inputField = $(`[name="${key}"]`);
                    const errorField = inputField.siblings('.invalid-feedback');

                    inputField.addClass('is-invalid');
                    if (errorField.length) {
                        errorField.text(errorMessage);
                    }
                });

                if (typeof showToast === 'function') {
                    showToast('Vui lòng kiểm tra lại thông tin', 'error');
                } else {
                    alert('Vui lòng kiểm tra lại thông tin');
                }
            } else {
                const errorMessage = xhr.responseJSON?.message || 'Có lỗi xảy ra khi ghi danh học viên';
                if (typeof showToast === 'function') {
                    showToast(errorMessage, 'error');
                } else {
                    alert(errorMessage);
                }
            }

            // Reset button
            submitBtn.prop('disabled', false);
            submitBtn.html('<i class="fas fa-user-plus me-1"></i>Đăng ký');
        }
    });
});

// Reset form khi đóng modal
$(document).on('hidden.bs.modal', '#enrollStudentModal', function() {
    // Reset các trường học phí
    $('#final_fee_display').val('');
    $('#final_fee').val('');
    $('#discount_percentage').val('');
    $('#discount_amount').val('');

    // Clear warnings
    $('#fee-warning-enroll').remove();
});

// Chắc chắn rằng select2 được áp dụng khi trang load
$(document).ready(function() {
    // Không khởi tạo select2 ở đây vì sẽ được khởi tạo khi modal mở
    // initProvinceSelect2();
    // initLocationSelect2();
    // initEthnicitySelect2();

    // Debug: Kiểm tra StudentManager
    console.log('StudentManager available:', typeof window.studentManager);
    console.log('StudentManager instance:', window.studentManager);

    // Fallback: Nếu StudentManager chưa sẵn sàng, bind events trực tiếp
    if (!window.studentManager) {
        console.log('StudentManager not available, setting up fallback event handlers...');

        // Bind events trực tiếp cho các nút với priority cao hơn
        $(document).off('click.studentActions').on('click.studentActions', '[data-student-action]', function(e) {
            console.log('Button clicked!', $(this).data('student-action'));
            e.preventDefault();
            e.stopImmediatePropagation();

            const action = $(this).data('student-action');
            const studentId = $(this).data('student-id');

            console.log('Executing action:', action, 'ID:', studentId);

            // Thử sử dụng StudentManager nếu có
            if (window.studentManager && typeof window.studentManager.handleAction === 'function') {
                console.log('Using StudentManager');
                window.studentManager.handleAction(action, studentId);
                return;
            }

            console.log('Using fallback functions');

            // Fallback functions
            switch(action) {
                case 'view':
                    console.log('Opening view modal for student:', studentId);
                    $('#viewStudentModal').modal('show');
                    loadStudentForView(studentId);
                    break;
                case 'edit':
                    console.log('Opening edit modal for student:', studentId);
                    $('#editStudentModal').modal('show');
                    loadStudentForEdit(studentId);
                    break;
                case 'create':
                    console.log('Opening create modal');
                    $('#createStudentModal').modal('show');
                    break;
                case 'enroll':
                    console.log('Opening enroll modal for student:', studentId);
                    $('#enrollStudentModal').modal('show');
                    loadStudentForEnroll(studentId);
                    break;
                case 'delete':
                    const studentName = $(this).data('student-name');
                    console.log('Deleting student:', studentId, studentName);
                    if (confirm(`Bạn có chắc chắn muốn xóa học viên "${studentName}"?`)) {
                        deleteStudent(studentId);
                    }
                    break;
                default:
                    console.warn('Unknown action:', action);
            }
        });

        // Thêm event listener backup trực tiếp trên body
        $('body').off('click.studentBackup').on('click.studentBackup', '[data-student-action]', function(e) {
            console.log('Body event triggered for:', $(this).data('student-action'));
        });

        // Thử lại sau 2 giây
        setTimeout(function() {
            console.log('Retrying StudentManager check...');
            console.log('StudentManager available after delay:', typeof window.studentManager);
        }, 2000);
    }

    // Thêm event listener trực tiếp cho tất cả các nút hiện tại
    $('[data-student-action]').each(function() {
        const $btn = $(this);
        const action = $btn.data('student-action');
        const studentId = $btn.data('student-id');

        $btn.off('click.direct').on('click.direct', function(e) {
            console.log('Direct click event:', action, studentId);
            e.preventDefault();
            e.stopPropagation();

            switch(action) {
                case 'view':
                    $('#viewStudentModal').modal('show');
                    loadStudentForView(studentId);
                    break;
                case 'edit':
                    $('#editStudentModal').modal('show');
                    loadStudentForEdit(studentId);
                    break;
                case 'create':
                    $('#createStudentModal').modal('show');
                    break;
                case 'enroll':
                    $('#enrollStudentModal').modal('show');
                    loadStudentForEnroll(studentId);
                    break;
                case 'delete':
                    const studentName = $btn.data('student-name');
                    if (confirm(`Bạn có chắc chắn muốn xóa học viên "${studentName}"?`)) {
                        deleteStudent(studentId);
                    }
                    break;
            }
        });
    });
});

// Helper functions để load dữ liệu học viên
function loadStudentForView(studentId) {
    $('#student-loading').show();
    $('#student-details').hide();

    $.ajax({
        url: `/api/students/${studentId}/info`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                populateViewModal(response.data);
                $('#student-loading').hide();
                $('#student-details').show();
            } else {
                alert('Không thể tải thông tin học viên');
                $('#viewStudentModal').modal('hide');
            }
        },
        error: function() {
            alert('Có lỗi xảy ra khi tải thông tin học viên');
            $('#viewStudentModal').modal('hide');
        }
    });
}

function loadStudentForEdit(studentId) {
    $('#edit-student-loading').show();
    $('#edit-student-form').hide();

    $.ajax({
        url: `/api/students/${studentId}/info`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                populateEditForm(response.data);
                $('#edit-student-loading').hide();
                $('#edit-student-form').show();
            } else {
                alert('Không thể tải thông tin học viên');
                $('#editStudentModal').modal('hide');
            }
        },
        error: function() {
            alert('Có lỗi xảy ra khi tải thông tin học viên');
            $('#editStudentModal').modal('hide');
        }
    });
}

function loadStudentForEnroll(studentId) {
    $('#enroll-student-loading').show();
    $('#enroll-student-form').hide();

    $.ajax({
        url: `/api/students/${studentId}/info`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                populateEnrollForm(response.data);
                $('#enroll-student-loading').hide();
                $('#enroll-student-form').show();
            } else {
                alert('Không thể tải thông tin học viên');
                $('#enrollStudentModal').modal('hide');
            }
        },
        error: function() {
            alert('Có lỗi xảy ra khi tải thông tin học viên');
            $('#enrollStudentModal').modal('hide');
        }
    });
}

function deleteStudent(studentId) {
    $.ajax({
        url: `/students/${studentId}`,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            alert('Xóa học viên thành công');
            window.location.reload();
        },
        error: function(xhr) {
            let message = 'Có lỗi xảy ra khi xóa học viên';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            alert(message);
        }
    });
}

// Helper functions để populate dữ liệu vào modal
function populateViewModal(student) {
    // Thông tin cá nhân
    $('#view-first-name').text(student.first_name || 'Chưa có');
    $('#view-last-name').text(student.last_name || 'Chưa có');
    $('#view-phone').text(student.phone || 'Chưa có');
    $('#view-email').text(student.email || 'Chưa có');
    $('#view-gender').text(formatGender(student.gender));
    $('#view-date-of-birth').text(student.formatted_date_of_birth || 'Chưa có');
    $('#view-address').text(student.address || 'Chưa có');

    // Tỉnh thành
    let provinceText = '';
    if (student.province) {
        provinceText = student.province.name + ' (' + getRegionName(student.province.region) + ')';
    }
    $('#view-province').text(provinceText || 'Chưa có');

    // Thông tin bổ sung
    $('#view-workplace').text(student.current_workplace || 'Chưa có');
    $('#view-experience').text(student.accounting_experience_years
        ? student.accounting_experience_years + ' năm' : 'Chưa có');
    $('#view-place-of-birth').text(student.place_of_birth || 'Chưa có');
    $('#view-nation').text(student.nation || 'Chưa có');
    $('#view-id-number').text(student.id_number || 'Chưa có');
    $('#view-education-level').text(student.education_level || 'Chưa có');
    $('#view-training-specialization').text(student.training_specialization || 'Chưa có');
    $('#view-hard-copy-documents').text(student.hard_copy_documents || 'Chưa có');

    // Ghi chú
    $('#view-notes').text(student.notes || 'Chưa có');

    // Thông tin hóa đơn
    $('#view-company-name').text(student.company_name || 'Chưa có');
    $('#view-tax-code').text(student.tax_code || 'Chưa có');
    $('#view-invoice-email').text(student.invoice_email || 'Chưa có');
    $('#view-company-address').text(student.company_address || 'Chưa có');

    // Cập nhật student ID cho các nút action
    $('[data-student-action="edit"]').attr('data-student-id', student.id);
    $('[data-student-action="enroll"]').attr('data-student-id', student.id);
}

// Function để load thông tin ghi danh khi click vào tab
function loadEnrollmentInfo(studentId) {
    $('#enrollment-loading').show();
    $('#enrollment-content').hide();

    $.ajax({
        url: `/api/students/${studentId}/enrollments`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                populateEnrollmentTab(response.data, studentId);
                $('#enrollment-loading').hide();
                $('#enrollment-content').show();
            } else {
                alert('Không thể tải thông tin ghi danh');
            }
        },
        error: function() {
            alert('Có lỗi xảy ra khi tải thông tin ghi danh');
            $('#enrollment-loading').hide();
        }
    });
}

// Function để populate dữ liệu ghi danh vào tab
function populateEnrollmentTab(enrollments, studentId) {
    const $enrollmentList = $('#enrollment-list');
    const $noEnrollments = $('#no-enrollments');

    // Cập nhật student ID cho các nút thêm ghi danh
    $('[data-student-action="enroll"]').attr('data-student-id', studentId);

    if (enrollments.length === 0) {
        $enrollmentList.hide();
        $noEnrollments.show();
        return;
    }

    $noEnrollments.hide();
    $enrollmentList.show();

    let html = '';
    enrollments.forEach(function(enrollment) {
        const statusBadge = getEnrollmentStatusBadge(enrollment.status);
        const formattedFee = formatCurrency(enrollment.final_fee);
        const totalPaid = enrollment.total_paid || 0;
        const remaining = enrollment.final_fee - totalPaid;

        html += `
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="card-title mb-1">
                                <i class="fas fa-book me-2"></i>${enrollment.course_item ? enrollment.course_item.name : 'N/A'}
                            </h6>
                            <p class="text-muted mb-1">
                                <i class="fas fa-calendar me-1"></i>Ngày ghi danh: ${enrollment.formatted_enrollment_date || 'N/A'}
                            </p>
                            <p class="mb-0">${statusBadge}</p>
                        </div>
                        <div class="col-md-4">
                            <div class="text-end">
                                <p class="mb-1"><strong>Học phí: ${formattedFee}</strong></p>
                                <p class="mb-1 text-success">Đã thanh toán: ${formatCurrency(totalPaid)}</p>
                                <p class="mb-0 ${remaining > 0 ? 'text-danger' : 'text-success'}">
                                    Còn lại: ${formatCurrency(remaining)}
                                </p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="btn-group-vertical w-100">
                                <button type="button" class="btn btn-sm btn-outline-primary mb-1"
                                        onclick="editEnrollment(${enrollment.id})" title="Chỉnh sửa">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success"
                                        onclick="viewPayments(${enrollment.id})" title="Thanh toán">
                                    <i class="fas fa-money-bill"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    ${enrollment.notes ? `
                        <div class="mt-2">
                            <small class="text-muted">
                                <i class="fas fa-sticky-note me-1"></i>Ghi chú: ${enrollment.notes}
                            </small>
                        </div>
                    ` : ''}
                </div>
            </div>
        `;
    });

    $enrollmentList.html(html);
}

// Function để lấy badge trạng thái ghi danh
function getEnrollmentStatusBadge(status) {
    const statusMap = {
        'active': '<span class="badge bg-success">Đang học</span>',
        'waiting': '<span class="badge bg-warning">Danh sách chờ</span>',
        'completed': '<span class="badge bg-primary">Hoàn thành</span>',
        'cancelled': '<span class="badge bg-danger">Đã hủy</span>'
    };
    return statusMap[status] || `<span class="badge bg-secondary">${status}</span>`;
}

// Function để format tiền tệ
function formatCurrency(amount) {
    if (!amount) return '0 VNĐ';
    return new Intl.NumberFormat('vi-VN').format(amount) + ' VNĐ';
}

// Function để chỉnh sửa ghi danh
function editEnrollment(enrollmentId) {
    console.log('editEnrollment called with ID:', enrollmentId);

    // Test xem modal có tồn tại không
    console.log('Modal exists:', $('#editEnrollmentModal').length);
    console.log('Form exists:', $('#edit-enrollment-form').length);
    console.log('Loading exists:', $('#edit-enrollment-loading').length);

    // Test hiển thị modal trước
    $('#editEnrollmentModal').modal('show');

    // Đợi modal hiển thị xong rồi mới load data
    setTimeout(function() {
        console.log('Modal should be visible now, loading data...');
        console.log('Modal content:', $('#editEnrollmentModal .modal-body').html().substring(0, 200));
        loadEnrollmentForEdit(enrollmentId);
    }, 500);
}

// Function để xem thanh toán
function viewPayments(enrollmentId) {
    // TODO: Implement payment view functionality
    alert('Chức năng xem thanh toán sẽ được triển khai sau');
}

// Function để load thông tin ghi danh cho chỉnh sửa
function loadEnrollmentForEdit(enrollmentId) {
    console.log('loadEnrollmentForEdit called with ID:', enrollmentId);
    $('#edit-enrollment-loading').removeClass('d-none');
    $('#edit-enrollment-form').addClass('d-none');

    $.ajax({
        url: `/api/enrollments/${enrollmentId}`,
        method: 'GET',
        success: function(response) {
            console.log('Enrollment API response:', response);
            if (response.success) {
                populateEditEnrollmentForm(response.data);
                $('#edit-enrollment-loading').addClass('d-none');
                $('#edit-enrollment-form').removeClass('d-none');
                console.log('Form should be visible now');
            } else {
                console.error('API returned error:', response.message);
                alert('Không thể tải thông tin ghi danh: ' + (response.message || 'Unknown error'));
                $('#editEnrollmentModal').modal('hide');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', xhr, status, error);
            alert('Có lỗi xảy ra khi tải thông tin ghi danh: ' + error);
            $('#editEnrollmentModal').modal('hide');
        }
    });
}

// Function để populate form chỉnh sửa ghi danh
function populateEditEnrollmentForm(enrollment) {
    console.log('populateEditEnrollmentForm called with:', enrollment);

    // Kiểm tra xem các element có tồn tại không
    console.log('edit-enrollment-id element:', $('#edit-enrollment-id').length);
    console.log('edit-enrollment-form element:', $('#edit-enrollment-form').length);

    $('#edit-enrollment-id').val(enrollment.id);
    $('#edit-enrollment-course-name').text(enrollment.course_item ? enrollment.course_item.name : 'N/A');
    $('#edit-enrollment-student-name').text(enrollment.student ? enrollment.student.full_name : 'N/A');
    $('#edit-enrollment-date').val(enrollment.formatted_enrollment_date || '');
    $('#edit-status').val(enrollment.status || '');
    $('#edit-final-fee').val(enrollment.final_fee || 0);
    $('#edit-final-fee-display').val(formatCurrencyInput(enrollment.final_fee || 0));
    $('#edit-discount-percentage').val(enrollment.discount_percentage || 0);
    $('#edit-discount-amount').val(enrollment.discount_amount || 0);
    $('#edit-enrollment-notes').val(enrollment.notes || '');

    console.log('Form populated successfully');

    // Khởi tạo input mask cho ngày
    if (typeof Inputmask !== 'undefined') {
        Inputmask({
            mask: '99/99/9999',
            placeholder: 'dd/mm/yyyy',
            clearIncomplete: true
        }).mask('#edit-enrollment-date');
    }
}

// Function để format tiền tệ cho input (không có VNĐ)
function formatCurrencyInput(amount) {
    if (!amount) return '0';
    return new Intl.NumberFormat('vi-VN').format(amount);
}

// Event listener cho tab enrollment
$(document).on('shown.bs.tab', '#view-enrollment-tab', function() {
    const studentId = $('[data-student-action="edit"]').attr('data-student-id');
    if (studentId) {
        loadEnrollmentInfo(studentId);
    }
});

// Event listener cho form chỉnh sửa ghi danh
$(document).on('submit', '#enrollmentEditForm', function(e) {
    e.preventDefault();

    const form = this;
    const formData = new FormData(form);
    const enrollmentId = $('#edit-enrollment-id').val();
    const submitBtn = $('#save-enrollment-btn');

    if (!enrollmentId) {
        alert('Không tìm thấy thông tin ghi danh');
        return;
    }

    // Disable button và hiển thị loading
    submitBtn.prop('disabled', true);
    submitBtn.html('<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...');

    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');

    $.ajax({
        url: `/api/enrollments/${enrollmentId}`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                $('#editEnrollmentModal').modal('hide');

                // Hiển thị thông báo thành công
                if (typeof showToast === 'function') {
                    showToast('Cập nhật thông tin ghi danh thành công!', 'success');
                } else {
                    alert('Cập nhật thông tin ghi danh thành công!');
                }

                // Reload tab enrollment nếu đang mở
                const activeTab = $('#view-enrollment-tab');
                if (activeTab.hasClass('active')) {
                    const studentId = $('[data-student-action="edit"]').attr('data-student-id');
                    if (studentId) {
                        loadEnrollmentInfo(studentId);
                    }
                }
            } else {
                alert(response.message || 'Có lỗi xảy ra khi cập nhật ghi danh');
            }
        },
        error: function(xhr) {
            console.error('Enrollment update error:', xhr);

            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                // Hiển thị lỗi validation
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(key => {
                    const errorMessage = errors[key][0];
                    const inputField = $(`[name="${key}"]`);
                    const errorField = inputField.siblings('.invalid-feedback');

                    inputField.addClass('is-invalid');
                    if (errorField.length) {
                        errorField.text(errorMessage);
                    }
                });

                if (typeof showToast === 'function') {
                    showToast('Vui lòng kiểm tra lại thông tin', 'error');
                } else {
                    alert('Vui lòng kiểm tra lại thông tin');
                }
            } else {
                const errorMessage = xhr.responseJSON?.message || 'Có lỗi xảy ra khi cập nhật ghi danh';
                if (typeof showToast === 'function') {
                    showToast(errorMessage, 'error');
                } else {
                    alert(errorMessage);
                }
            }
        },
        complete: function() {
            // Reset button
            submitBtn.prop('disabled', false);
            submitBtn.html('<i class="fas fa-save me-1"></i>Lưu thay đổi');
        }
    });
});

function populateEditForm(student) {
    $('#edit-student-id').val(student.id);
    $('#edit-first-name').val(student.first_name || '');
    $('#edit-last-name').val(student.last_name || '');
    $('#edit-phone').val(student.phone || '');
    $('#edit-email').val(student.email || '');
    $('#edit-gender').val(student.gender || '');

    if (student.date_of_birth) {
        $('#edit-date-of-birth').val(student.formatted_date_of_birth || '');
    }

    $('#edit-address').val(student.address || '');
    $('#edit-notes').val(student.notes || '');
    $('#edit-current-workplace').val(student.current_workplace || '');
    $('#edit-experience').val(student.accounting_experience_years || '');
    $('#edit-hard-copy-documents').val(student.hard_copy_documents || '');
    $('#edit-education-level').val(student.education_level || '');
    $('#edit-training-specialization').val(student.training_specialization || '');
    $('#edit-nation').val(student.nation || '');
    $('#edit-place-of-birth').val(student.place_of_birth || '');

    // Populate thông tin hóa đơn
    $('#edit-company-name').val(student.company_name || '');
    $('#edit-tax-code').val(student.tax_code || '');
    $('#edit-invoice-email').val(student.invoice_email || '');
    $('#edit-company-address').val(student.company_address || '');

    // Xử lý tỉnh thành với Select2
    if (student.province) {
        const option = new Option(
            student.province.name + ' (' + getRegionName(student.province.region) + ')',
            student.province.id,
            true,
            true
        );
        $('#edit-province').empty().append(option).trigger('change');
    }
}

function populateEnrollForm(student) {
    $('#enroll-student-id').val(student.id);
    $('#enroll-student-name').text(student.full_name || '');
}

function formatGender(gender) {
    const genders = {
        'male': 'Nam',
        'female': 'Nữ',
        'other': 'Khác'
    };
    return genders[gender] || 'Không xác định';
}

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
                        q: params.term || '', // Sử dụng 'q' để tương thích với API đã sửa
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
                    } else if (Array.isArray(response)) {
                        // Trường hợp API trả về array trực tiếp (cho Select2 AJAX)
                        return {
                            results: response.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.text || item.name
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
            minimumInputLength: 0 // Cho phép hiển thị data ngay khi mở dropdown
        });
    });
}

// Khởi tạo select2 cho các trường chọn địa điểm (chỉ nơi sinh)
function initLocationSelect2(){
    try {
        $('#edit-place-of-birth-select, #create-place-of-birth-select').select2('destroy');
    } catch (e) {}

    $('#edit-place-of-birth-select, #create-place-of-birth-select').each(function(){
        let $hidden;
        if ($(this).attr('id') === 'edit-place-of-birth-select') {
            $hidden = $('#edit-place-of-birth');
        } else if ($(this).attr('id') === 'create-place-of-birth-select') {
            $hidden = $('#create-place-of-birth');
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
                    return {
                        q: params.term || '', // Sử dụng 'q' để tương thích với API đã sửa
                        keyword: params.term || ''
                    };
                },
                processResults: function(response){
                    if (response && response.success && Array.isArray(response.data)){
                        return {
                            results: response.data.map(function(item){
                                return { id: item.id, text: item.name + ' (' + getRegionName(item.region) + ')' };
                            })
                        };
                    } else if (Array.isArray(response)) {
                        // Trường hợp API trả về array trực tiếp (cho Select2 AJAX)
                        return {
                            results: response.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.text || item.name
                                };
                            })
                        };
                    }
                    return { results: [] };
                },
                cache: true
            },
            minimumInputLength: 0 // Cho phép hiển thị data ngay khi mở dropdown
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

// Khởi tạo select2 cho dân tộc
function initEthnicitySelect2(){
    try {
        $('#edit-nation-select, #create-nation-select').select2('destroy');
    } catch (e) {}

    $('#edit-nation-select, #create-nation-select').each(function(){
        let $hidden;
        if ($(this).attr('id') === 'edit-nation-select') {
            $hidden = $('#edit-nation');
        } else if ($(this).attr('id') === 'create-nation-select') {
            $hidden = $('#create-nation');
        }

        $(this).select2({
            theme: 'bootstrap-5',
            placeholder: $(this).data('placeholder') || 'Chọn dân tộc',
            allowClear: true,
            dropdownParent: $(this).closest('.modal'),
            width: '100%',
            ajax: {
                url: '/api/ethnicities',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term || '', // Sử dụng 'q' để tương thích với API đã sửa
                        keyword: params.term || ''
                    };
                },
                processResults: function(response){
                    if (response && response.success && Array.isArray(response.data)){
                        return {
                            results: response.data.map(function(item){
                                return { id: item.id, text: item.name };
                            })
                        };
                    } else if (Array.isArray(response)) {
                        // Trường hợp API trả về array trực tiếp (cho Select2 AJAX)
                        return {
                            results: response.map(function(item) {
                                return {
                                    id: item.id,
                                    text: item.text || item.name
                                };
                            })
                        };
                    }
                    return { results: [] };
                },
                cache: true
            },
            minimumInputLength: 0 // Cho phép hiển thị data ngay khi mở dropdown
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

// Khởi tạo Select2 cho course dropdown trong modal ghi danh
function initCourseSelect2() {
    $('#course_item_id').select2({
        theme: 'bootstrap-5',
        placeholder: 'Tìm kiếm và chọn khóa học...',
        allowClear: true,
        dropdownParent: $('#enrollStudentModal'),
        width: '100%',
        minimumInputLength: 0,
        ajax: {
            url: '/api/course-items/search-active',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term || '',
                    preload: params.term ? 'false' : 'true'
                };
            },
            processResults: function (response) {
                if (Array.isArray(response)) {
                    return {
                        results: response.map(function(course) {
                            return {
                                id: course.id,
                                text: course.name + (course.path ? ' (' + course.path + ')' : ''),
                                fee: course.fee || 0,
                                status: course.status,
                                status_label: course.status_label
                            };
                        })
                    };
                }
                return { results: [] };
            },
            cache: true
        }
    });

    // Event listener khi chọn khóa học để cập nhật học phí
    $('#course_item_id').on('select2:select', function(e) {
        const selectedData = e.params.data;
        const fee = selectedData.fee || 0;

        // Cập nhật học phí hiển thị
        $('#final_fee_display').val(formatCurrency(fee));
        $('#final_fee').val(fee);

        // Lưu base fee để tính toán discount
        $('#enrollStudentModal').data('base-fee', fee);

        // Reset discount fields
        $('#discount_percentage').val('');
        $('#discount_amount').val('');

        console.log('Selected course:', selectedData.text, 'Fee:', fee);
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



<script>
// Xử lý form chỉnh sửa học viên
$(document).on('click', '#save-student-btn', function() {
    const form = $('#studentEditForm');
    const formData = new FormData(form[0]);
    const button = $(this);
    const studentId = $('#edit-student-id').val();

    // Disable button và hiển thị loading
    button.prop('disabled', true);
    button.html('<i class="fas fa-spinner fa-spin me-1"></i>Đang lưu...');

    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');

    $.ajax({
        url: `/api/students/${studentId}/update`,
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
                toastr.success(response.message || 'Cập nhật học viên thành công!');

                // Đóng modal
                $('#editStudentModal').modal('hide');

                // Reload trang để hiển thị thay đổi
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
                    $(`#edit-${field.replace('_', '-')}-error`).text(errors[field][0]);
                }
                toastr.error('Vui lòng kiểm tra lại thông tin!');
            } else {
                toastr.error('Có lỗi xảy ra khi cập nhật học viên!');
            }
        },
        complete: function() {
            // Reset button
            button.prop('disabled', false);
            button.html('<i class="fas fa-save me-1"></i>Lưu thay đổi');
        }
    });
});

// Hàm tải mẫu Excel cho import
function downloadImportTemplate() {
    window.location.href = '{{ route("students.import.template") }}';
}

// Hàm thực hiện import Excel
function executeImport() {
    const form = $('#importStudentForm')[0];
    const formData = new FormData(form);
    const fileInput = $('#import-file')[0];
    const button = $('#execute-import-btn');

    // Validate file
    if (!fileInput.files.length) {
        $('#import-file').addClass('is-invalid');
        $('#import-file-error').text('Vui lòng chọn file Excel');
        return;
    }

    const file = fileInput.files[0];
    const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                         'application/vnd.ms-excel', 'text/csv'];

    if (!allowedTypes.includes(file.type)) {
        $('#import-file').addClass('is-invalid');
        $('#import-file-error').text('File phải có định dạng .xlsx, .xls hoặc .csv');
        return;
    }

    if (file.size > 10 * 1024 * 1024) { // 10MB
        $('#import-file').addClass('is-invalid');
        $('#import-file-error').text('File không được vượt quá 10MB');
        return;
    }

    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');

    // Show progress
    $('#import-progress').removeClass('d-none');
    $('#import-results').addClass('d-none');
    $('#import-errors').addClass('d-none');

    // Disable button
    button.prop('disabled', true);
    button.html('<i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý...');

    // Simulate progress
    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;
        $('.progress-bar').css('width', progress + '%');
    }, 200);

    $.ajax({
        url: '{{ route("students.import") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            clearInterval(progressInterval);
            $('.progress-bar').css('width', '100%');

            setTimeout(() => {
                $('#import-progress').addClass('d-none');

                if (response.success) {
                    // Hiển thị kết quả
                    $('#created-count').text(response.data.created_count || 0);
                    $('#updated-count').text(response.data.updated_count || 0);
                    $('#skipped-count').text(response.data.skipped_count || 0);
                    $('#import-results').removeClass('d-none');

                    // Hiển thị lỗi nếu có
                    if (response.data.errors && response.data.errors.length > 0) {
                        let errorHtml = '<ul class="mb-0">';
                        response.data.errors.forEach(error => {
                            errorHtml += `<li>${error}</li>`;
                        });
                        errorHtml += '</ul>';
                        $('#error-list').html(errorHtml);
                        $('#import-errors').removeClass('d-none');
                    }

                    toastr.success(response.message || 'Import thành công!');

                    // Reload trang sau 3 giây
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    toastr.error(response.message || 'Có lỗi xảy ra khi import!');
                }
            }, 500);
        },
        error: function(xhr) {
            clearInterval(progressInterval);
            $('#import-progress').addClass('d-none');

            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                for (const field in errors) {
                    $(`#import-${field.replace('_', '-')}`).addClass('is-invalid');
                    $(`#import-${field.replace('_', '-')}-error`).text(errors[field][0]);
                }
                toastr.error('Vui lòng kiểm tra lại thông tin!');
            } else {
                toastr.error('Có lỗi xảy ra khi import file!');
            }
        },
        complete: function() {
            // Re-enable button
            button.prop('disabled', false);
            button.html('<i class="fas fa-upload me-1"></i>Bắt đầu Import');
        }
    });
}
</script>
