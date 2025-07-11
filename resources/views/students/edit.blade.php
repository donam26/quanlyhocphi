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
                            <label class="form-label">Nơi sinh</label>
                            <input type="text" name="place_of_birth" class="form-control @error('place_of_birth') is-invalid @enderror"
                                   value="{{ old('place_of_birth', $student->place_of_birth) }}">
                            @error('place_of_birth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Số CCCD/CMND</label>
                            <input type="text" name="citizen_id" class="form-control @error('citizen_id') is-invalid @enderror"
                                   value="{{ old('citizen_id', $student->citizen_id) }}">
                            @error('citizen_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Dân tộc</label>
                            <input type="text" name="ethnicity" class="form-control @error('ethnicity') is-invalid @enderror"
                                   value="{{ old('ethnicity', $student->ethnicity) }}">
                            @error('ethnicity')
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

                    <!-- Thông tin nghề nghiệp -->
                    @php
                        $hasProfessionalInfo = $student->current_workplace ||
                                             $student->accounting_experience_years ||
                                             $student->education_level ||
                                             $student->major_studied;
                    @endphp
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h6 class="mb-0">
                                    <i class="fas fa-briefcase me-2"></i>Thông tin nghề nghiệp
                                    <small class="text-muted">(Dành cho lớp Kế toán trưởng)</small>
                                </h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="showProfessionalInfo"
                                           {{ $hasProfessionalInfo ? 'checked' : '' }}>
                                    <label class="form-check-label" for="showProfessionalInfo">
                                        Hiển thị
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="professionalInfoFields" class="col-12" style="{{ $hasProfessionalInfo ? '' : 'display: none;' }}">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Nơi công tác hiện tại</label>
                                    <input type="text" name="current_workplace" class="form-control @error('current_workplace') is-invalid @enderror"
                                           value="{{ old('current_workplace', $student->current_workplace) }}">
                                    @error('current_workplace')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Số năm kinh nghiệm làm kế toán</label>
                                    <input type="number" name="accounting_experience_years" class="form-control @error('accounting_experience_years') is-invalid @enderror"
                                           value="{{ old('accounting_experience_years', $student->accounting_experience_years) }}" min="0">
                                    @error('accounting_experience_years')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Bằng cấp</label>
                                    <select name="education_level" class="form-select @error('education_level') is-invalid @enderror">
                                        <option value="">Chọn bằng cấp</option>
                                        <option value="trung_cap" {{ old('education_level', $student->education_level) == 'trung_cap' ? 'selected' : '' }}>Trung cấp</option>
                                        <option value="cao_dang" {{ old('education_level', $student->education_level) == 'cao_dang' ? 'selected' : '' }}>Cao đẳng</option>
                                        <option value="dai_hoc" {{ old('education_level', $student->education_level) == 'dai_hoc' ? 'selected' : '' }}>Đại học</option>
                                        <option value="thac_si" {{ old('education_level', $student->education_level) == 'thac_si' ? 'selected' : '' }}>Thạc sĩ</option>
                                        <option value="vb2" {{ old('education_level', $student->education_level) == 'vb2' ? 'selected' : '' }}>VB2</option>
                                    </select>
                                    @error('education_level')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Ngành học</label>
                                    <input type="text" name="major_studied" class="form-control @error('major_studied') is-invalid @enderror"
                                           value="{{ old('major_studied', $student->major_studied) }}">
                                    @error('major_studied')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Trạng thái -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-toggle-on me-2"></i>Trạng thái
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Trạng thái học viên <span class="text-danger">*</span></label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                <option value="potential" {{ old('status', $student->status) == 'potential' ? 'selected' : '' }}>Tiềm năng</option>
                                <option value="active" {{ old('status', $student->status) == 'active' ? 'selected' : '' }}>Đang học</option>
                                <option value="inactive" {{ old('status', $student->status) == 'inactive' ? 'selected' : '' }}>Đã nghỉ</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
});
</script>
@endsection 