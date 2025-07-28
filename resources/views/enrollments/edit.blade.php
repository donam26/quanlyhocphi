@extends('layouts.app')

@section('page-title', 'Chỉnh sửa ghi danh')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('enrollments.index') }}">Đăng ký học</a></li>
    <li class="breadcrumb-item"><a href="{{ route('enrollments.show', $enrollment) }}">Chi tiết ghi danh</a></li>
    <li class="breadcrumb-item active">Chỉnh sửa</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-edit me-2"></i>
                    Chỉnh sửa thông tin ghi danh
                </h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('enrollments.update', $enrollment) }}" method="POST" id="enrollmentForm">
                    @csrf
                    @method('PUT')
                    
                    <!-- Chọn học viên -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-user me-2"></i>Thông tin học viên
                            </h6>
                        </div>
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="studentSelect" class="form-label">Học viên <span class="text-danger">*</span></label>
                                <div class="d-flex gap-2">
                                    <select name="student_id" id="studentSelect" class="form-select select2" required>
                                        @foreach($students as $s)
                                            <option value="{{ $s->id }}" 
                                                {{ (old('student_id', $enrollment->student_id) == $s->id) ? 'selected' : '' }}>
                                                {{ $s->full_name }} ({{ $s->phone }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chọn Lớp học -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h5 class="border-bottom pb-2 mb-3">2. Thông tin khóa học</h5>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="courseClassSelect" class="form-label">Khóa học <span class="text-danger">*</span></label>
                                <select name="course_item_id" id="courseClassSelect" class="form-select select2" required>
                                    @foreach ($courseItems as $class)
                                        <option value="{{ $class->id }}" 
                                            data-fee="{{ $class->fee }}"
                                            {{ old('course_item_id', $enrollment->course_item_id) == $class->id ? 'selected' : '' }}>
                                            {{ $class->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="enrollment_date" class="form-label">Ngày ghi danh <span class="text-danger">*</span></label>
                                <input type="date" name="enrollment_date" id="enrollment_date" class="form-control" 
                                       value="{{ old('enrollment_date', $enrollment->enrollment_date->format('Y-m-d')) }}" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Thông tin ghi danh -->
                    <div class="row mb-4">
                        <div class="col-12">
                             <h6 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-file-invoice-dollar me-2"></i>Thông tin học phí
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="final_fee" class="form-label">Học phí cuối cùng</label>
                                <input type="text" id="final_fee_display" class="form-control" 
                                       value="{{ number_format($enrollment->final_fee) }}" readonly>
                                <input type="hidden" name="final_fee" id="final_fee" 
                                       value="{{ old('final_fee', $enrollment->final_fee) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control select2" required>
                                    <option value="enrolled" {{ old('status', $enrollment->status) == 'enrolled' ? 'selected' : '' }}>Đang học</option>
                                    <option value="completed" {{ old('status', $enrollment->status) == 'completed' ? 'selected' : '' }}>Đã hoàn thành</option>
                                    <option value="dropped" {{ old('status', $enrollment->status) == 'dropped' ? 'selected' : '' }}>Đã bỏ học</option>
                                    <option value="transferred" {{ old('status', $enrollment->status) == 'transferred' ? 'selected' : '' }}>Đã chuyển lớp</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chiết khấu -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="discount_percentage" class="form-label">Chiết khấu (%)</label>
                                <input type="number" name="discount_percentage" id="discount_percentage" class="form-control" 
                                       value="{{ old('discount_percentage', $enrollment->discount_percentage) }}" 
                                       min="0" max="100" step="0.1">
                            </div>
                        </div>
                        <div class="col-md-6">
                             <div class="mb-3">
                                <label for="discount_amount" class="form-label">Chiết khấu (VND)</label>
                                <input type="number" name="discount_amount" id="discount_amount" class="form-control" 
                                       value="{{ old('discount_amount', $enrollment->discount_amount) }}" 
                                       min="0" step="1000">
                            </div>
                        </div>
                    </div>

                    <!-- Ghi chú -->
                    <div class="mb-4">
                        <label for="notes" class="form-label">Ghi chú</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3">{{ old('notes', $enrollment->notes) }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="{{ route('enrollments.show', $enrollment) }}" class="btn btn-secondary">Quay lại</a>
                        </div>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                Cập nhật
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
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

            if (!isNaN(discountPercent) && discountPercent > 0) {
                finalFee -= baseFee * (discountPercent / 100);
            } else if (!isNaN(discountAmount) && discountAmount > 0) {
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