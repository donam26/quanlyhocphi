@extends('layouts.app')

@section('page-title', 'Thêm học viên mới')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('students.index') }}">Học viên</a></li>
<li class="breadcrumb-item active">Thêm mới</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    Thông tin học viên mới
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('students.store') }}" method="POST" id="studentForm">
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
                            <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror" 
                                   value="{{ old('full_name') }}" required>
                            @error('full_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                   value="{{ old('phone') }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Ngày sinh</label>
                            <input type="date" name="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" 
                                   value="{{ old('date_of_birth') }}">
                            @error('date_of_birth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                   value="{{ old('email') }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Giới tính</label>
                            <select name="gender" class="form-select @error('gender') is-invalid @enderror">
                                <option value="">Chọn giới tính</option>
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Nam</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Nữ</option>
                                <option value="other" {{ old('gender') == 'other' ? 'selected' : '' }}>Khác</option>
                            </select>
                            @error('gender')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Địa chỉ</label>
                            <textarea name="address" class="form-control @error('address') is-invalid @enderror" 
                                      rows="2">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Thông tin nghề nghiệp (cho lớp Kế toán trưởng) -->
                    <div class="row mb-4" id="professionalInfo" style="display: none;">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-briefcase me-2"></i>Thông tin nghề nghiệp 
                                <small class="text-muted">(Dành cho lớp Kế toán trưởng)</small>
                            </h6>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Nơi công tác hiện tại</label>
                            <input type="text" name="current_workplace" class="form-control @error('current_workplace') is-invalid @enderror" 
                                   value="{{ old('current_workplace') }}">
                            @error('current_workplace')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Số năm kinh nghiệm làm kế toán</label>
                            <input type="number" name="accounting_experience_years" class="form-control @error('accounting_experience_years') is-invalid @enderror" 
                                   value="{{ old('accounting_experience_years') }}" min="0">
                            @error('accounting_experience_years')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Ngành học</label>
                            <input type="text" name="major_studied" class="form-control @error('major_studied') is-invalid @enderror" 
                                   value="{{ old('major_studied') }}">
                            @error('major_studied')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Ghi chú -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <label class="form-label">Ghi chú</label>
                            <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" 
                                      rows="3">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="{{ route('students.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>Hủy
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Lưu học viên
                                </button>
                                <button type="submit" name="action" value="save_and_enroll" class="btn btn-success">
                                    <i class="fas fa-user-plus me-2"></i>Lưu và ghi danh
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Quick Add to Waiting List Modal -->
<div class="modal fade" id="quickWaitingListModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm vào danh sách chờ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có muốn thêm học viên này vào danh sách chờ không?</p>
                <div class="mb-3">
                    <label class="form-label">Khóa học quan tâm</label>
                    <select class="form-select" id="waitingCourse">
                        <option value="">Chọn khóa học</option>
                        <!-- Will be populated by JavaScript -->
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Mức độ quan tâm</label>
                    <select class="form-select" id="interestLevel">
                        <option value="medium">Trung bình</option>
                        <option value="high">Cao</option>
                        <option value="low">Thấp</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Không</button>
                <button type="button" class="btn btn-primary" onclick="addToWaitingList()">Thêm vào danh sách chờ</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Show/hide professional info based on course selection
    // This will be enhanced when integrating with course selection
    
    // Phone number formatting
    $('input[name="phone"]').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length >= 10) {
            value = value.replace(/(\d{4})(\d{3})(\d{3})/, '$1 $2 $3');
        }
        $(this).val(value);
    });

    // ID number formatting
    $('input[name="citizen_id"]').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length >= 9) {
            if (value.length === 9) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})/, '$1 $2 $3');
            } else if (value.length === 12) {
                value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{3})/, '$1 $2 $3 $4');
            }
        }
        $(this).val(value);
    });

    // Form validation
    $('#studentForm').on('submit', function(e) {
        let isValid = true;
        
        // Check required fields
        $('input[required]').each(function() {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        // Validate phone number
        const phone = $('input[name="phone"]').val().replace(/\D/g, '');
        if (phone.length < 10) {
            $('input[name="phone"]').addClass('is-invalid');
            isValid = false;
        }

        // Validate email if provided
        const email = $('input[name="email"]').val();
        if (email && !isValidEmail(email)) {
            $('input[name="email"]').addClass('is-invalid');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
            alert('Vui lòng kiểm tra lại thông tin đã nhập!');
        }
    });
});

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showProfessionalInfo() {
    $('#professionalInfo').show();
    $('#professionalInfo input, #professionalInfo select').prop('required', true);
}

function hideProfessionalInfo() {
    $('#professionalInfo').hide();
    $('#professionalInfo input, #professionalInfo select').prop('required', false);
}

function addToWaitingList() {
    alert('Chức năng thêm vào danh sách chờ đang được phát triển');
    $('#quickWaitingListModal').modal('hide');
}

// Auto-fill from existing data (if coming from search)
$(document).ready(function() {
    var phone = '{{ request("phone") ?? "" }}';
    var name = '{{ request("name") ?? "" }}';
    
    if (phone) {
        $('input[name="phone"]').val(phone);
    }
    
    if (name) {
        $('input[name="full_name"]').val(name);
    }
});
</script>
@endsection 