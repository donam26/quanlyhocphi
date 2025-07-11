@extends('layouts.app')

@section('page-title', 'Đăng ký học viên')

@section('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
@endsection


@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('enrollments.index') }}">Đăng ký học</a></li>
    <li class="breadcrumb-item active">Đăng ký mới</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-plus me-2"></i>
                    Đăng ký học viên vào lớp
                </h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('enrollments.store') }}" method="POST" id="enrollmentForm">
                    @csrf
                    
                    <!-- Chọn học viên -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-user me-2"></i>Thông tin học viên
                            </h6>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="studentSelect" class="form-label">Chọn học viên (có thể chọn nhiều) <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2">
                                    <select name="student_ids[]" id="studentSelect" class="form-select" required multiple="multiple">
                                        @foreach($students as $s)
                                            <option value="{{ $s->id }}" 
                                                    {{ (collect(old('student_ids'))->contains($s->id)) ? 'selected' : '' }}>
                                                {{ $s->full_name }} ({{ $s->phone }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <a href="{{ route('students.create') }}" class="btn btn-outline-primary" target="_blank" title="Thêm học viên mới">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chọn Lớp học -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-chalkboard-teacher me-2"></i>Thông tin lớp học
                            </h6>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="courseClassSelect" class="form-label">Chọn lớp học <span class="text-danger">*</span></label>
                                <select name="course_class_id" id="courseClassSelect" class="form-select" required>
                                    <option value="">Chọn lớp học</option>
                                    @foreach($courseClasses as $class)
                                        <option value="{{ $class->id }}" 
                                                data-fee="{{ $class->course->fee }}" 
                                                {{ old('course_class_id', request('course_class_id')) == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }} - {{ $class->course->name }} (Khai giảng: {{ $class->start_date->format('d/m/Y') }}) - {{number_format($class->course->fee)}}đ
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thông tin ghi danh -->
                    <div class="row mb-4">
                        <div class="col-12">
                             <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-file-invoice-dollar me-2"></i>Thông tin ghi danh & học phí
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="enrollment_date" class="form-label">Ngày ghi danh <span class="text-danger">*</span></label>
                                <input type="date" name="enrollment_date" id="enrollment_date" class="form-control" value="{{ old('enrollment_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                             <div class="mb-3">
                                <label for="final_fee" class="form-label">Học phí cuối cùng</label>
                                <input type="text" id="final_fee_display" class="form-control" readonly>
                                <input type="hidden" name="final_fee" id="final_fee">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chiết khấu -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="discount_percentage" class="form-label">Chiết khấu (%)</label>
                                <input type="number" name="discount_percentage" id="discount_percentage" class="form-control" value="{{ old('discount_percentage') }}" min="0" max="100" step="0.1">
                            </div>
                        </div>
                        <div class="col-md-6">
                             <div class="mb-3">
                                <label for="discount_amount" class="form-label">Chiết khấu (VND)</label>
                                <input type="number" name="discount_amount" id="discount_amount" class="form-control" value="{{ old('discount_amount') }}" min="0" step="1000">
                            </div>
                        </div>
                    </div>

                    <!-- Ghi chú -->
                    <div class="mb-4">
                        <label for="notes" class="form-label">Ghi chú</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('enrollments.index') }}" class="btn btn-secondary me-2">Hủy</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>
                            Lưu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Khởi tạo Select2 cho ô chọn nhiều học viên
        $('#studentSelect').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Gõ để tìm kiếm và chọn học viên...',
            closeOnSelect: false, // Giữ dropdown mở để chọn nhiều
        });

        // Khởi tạo Select2 cho ô chọn lớp học
        $('#courseClassSelect').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Chọn một lớp học',
        });

        function formatCurrency(number) {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(number);
        }

        function calculateFinalFee() {
            const selectedClass = $('#courseClassSelect').find(':selected');
            // Nếu chưa chọn lớp, xóa thông tin học phí
            if (!selectedClass.val()) {
                $('#final_fee_display').val('');
                $('#final_fee').val('');
                return;
            }

            const baseFee = parseFloat(selectedClass.data('fee')) || 0;
            let finalFee = baseFee;

            const discountPercent = parseFloat($('#discount_percentage').val());
            const discountAmount = parseFloat($('#discount_amount').val());

            if (!isNaN(discountPercent) && discountPercent >= 0) {
                finalFee -= baseFee * (discountPercent / 100);
            } else if (!isNaN(discountAmount) && discountAmount >= 0) {
                finalFee -= discountAmount;
            }
            
            finalFee = Math.max(0, finalFee);

            $('#final_fee').val(finalFee);
            $('#final_fee_display').val(formatCurrency(finalFee));
        }

        // Gán sự kiện
        $('#courseClassSelect').on('change', calculateFinalFee);

        $('#discount_percentage').on('input', function() {
            $('#discount_amount').val(''); // Xóa trường chiết khấu còn lại
            calculateFinalFee();
        });

        $('#discount_amount').on('input', function() {
            $('#discount_percentage').val(''); // Xóa trường chiết khấu còn lại
            calculateFinalFee();
        });

        // Tính toán lần đầu khi tải trang
        calculateFinalFee();
    });
</script>
@endpush 