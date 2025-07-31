@extends('layouts.app')

@section('page-title', 'Chỉnh sửa học viên: ' . $student->full_name)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('students.index') }}">Học viên</a></li>
<li class="breadcrumb-item"><a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a></li>
<li class="breadcrumb-item active">Chỉnh sửa</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-edit me-2"></i>
                    Chỉnh sửa thông tin học viên
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('students.update', $student) }}" method="POST" id="studentForm">
                    @csrf
                    @method('PUT')

                    <!-- Thông tin cơ bản -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-user me-2"></i>Thông tin cơ bản
                            </h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                                   value="{{ old('full_name', $student->full_name) }}" required>
                            @error('full_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $student->phone) }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ngày sinh</label>
                            <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
                                   value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}">
                            @error('date_of_birth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $student->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Giới tính</label>
                            <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                                <option value="">Chọn giới tính</option>
                                <option value="male" {{ old('gender', $student->gender) == 'male' ? 'selected' : '' }}>Nam</option>
                                <option value="female" {{ old('gender', $student->gender) == 'female' ? 'selected' : '' }}>Nữ</option>
                                <option value="other" {{ old('gender', $student->gender) == 'other' ? 'selected' : '' }}>Khác</option>
                            </select>
                            @error('gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Địa chỉ</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror"
                                      rows="2">{{ old('address', $student->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Thông tin tùy chỉnh -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-cog me-2"></i>Thông tin tùy chỉnh
                            </h6>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Thêm các trường thông tin tùy chỉnh theo nhu cầu.
                            </div>
                        </div>
                        
                        <div class="col-12" id="custom-fields-container">
                            @if(!empty($student->custom_fields) && is_array($student->custom_fields))
                                @foreach($student->custom_fields as $key => $value)
                                <div class="row mb-3 custom-field-row">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="custom_field_keys[]" value="{{ $key }}" placeholder="Tên trường">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control" name="custom_field_values[]" value="{{ $value }}" placeholder="Giá trị">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger btn-sm remove-field"><i class="fas fa-times"></i></button>
                                    </div>
                                </div>
                                @endforeach
                            @endif
                        </div>
                        
                        <div class="col-12 mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="add-custom-field">
                                <i class="fas fa-plus me-1"></i> Thêm trường thông tin
                            </button>
                        </div>
                    </div>

                    <!-- Ghi chú -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror"
                                      rows="3">{{ old('notes', $student->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <a href="{{ route('students.show', $student) }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Quay lại
                                    </a>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Cập nhật
                                    </button>
                                    <button type="submit" name="action" value="save_and_enroll" class="btn btn-success">
                                        <i class="fas fa-user-plus me-2"></i>Cập nhật và ghi danh
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Thao tác nhanh
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <div class="d-grid">
                            <a href="{{ route('enrollments.create', ['student_id' => $student->id]) }}" class="btn btn-outline-success">
                                <i class="fas fa-user-plus me-2"></i>Ghi danh
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="d-grid">
                            <a href="{{ route('payments.create', ['student_id' => $student->id]) }}" class="btn btn-outline-primary">
                                <i class="fas fa-money-bill me-2"></i>Thu học phí
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="d-grid">
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteStudentModal">
                                <i class="fas fa-trash-alt me-2"></i>Xóa học viên
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteStudentModal" tabindex="-1" aria-labelledby="deleteStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteStudentModalLabel">Xác nhận xóa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Bạn có chắc chắn muốn xóa học viên <strong>{{ $student->full_name }}</strong> không? <br>
                Hành động này không thể hoàn tác và sẽ xóa tất cả dữ liệu liên quan.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <form action="{{ route('students.destroy', $student) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Xóa</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Toggle professional info fields
    $('#showProfessionalInfo').on('change', function() {
        if ($(this).is(':checked')) {
            $('#professionalInfoFields').slideDown();
        } else {
            $('#professionalInfoFields').slideUp();
        }
    });

    // Phone number formatting
    $('input[name="phone"]').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length >= 10) {
            value = value.replace(/(\d{4})(\d{3})(\d{3})/, '$1 $2 $3');
            $(this).val(value.slice(0, 12));
        }
    });

    // ID number formatting
    $('input[name="citizen_id"]').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        $(this).val(value.slice(0, 12));
    });

    // Xử lý thêm trường thông tin tùy chỉnh
    $('#add-custom-field').click(function() {
        const fieldId = Date.now();
        const fieldHtml = `
            <div class="row mb-3 custom-field-row" data-field-id="${fieldId}">
                <div class="col-md-5">
                    <input type="text" class="form-control" name="custom_field_keys[]" placeholder="Tên trường">
                </div>
                <div class="col-md-5">
                    <input type="text" class="form-control" name="custom_field_values[]" placeholder="Giá trị">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger btn-sm remove-field"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `;
        
        $('#custom-fields-container').append(fieldHtml);
    });
    
    // Xóa trường thông tin tùy chỉnh
    $(document).on('click', '.remove-field', function() {
        $(this).closest('.custom-field-row').fadeOut(300, function() {
            $(this).remove();
        });
    });
});
</script>
@endsection 