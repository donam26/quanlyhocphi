@extends('layouts.app')

@section('page-title', 'Thêm học viên vào khóa học ' . $courseItem->name)

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('course-items.tree') }}">Khóa học</a></li>
<li class="breadcrumb-item"><a href="{{ route('course-items.students', $courseItem->id) }}">{{ $courseItem->name }}</a></li>
<li class="breadcrumb-item active">Thêm học viên</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
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

        <form action="{{ route('course-items.store-student', $courseItem->id) }}" method="POST">
            @csrf
            <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Thông tin ghi danh</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="student_id" class="form-label">Học viên <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="student_id" name="student_id" required>
                                        <option value="">-- Chọn học viên --</option>
                                        @foreach($students as $student)
                                            <option value="{{ $student->id }}" {{ old('student_id') == $student->id ? 'selected' : '' }}>
                                                {{ $student->full_name }} - {{ $student->phone }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">
                                        <a href="{{ route('students.create') }}" target="_blank">
                                            <i class="fas fa-plus-circle"></i> Thêm học viên mới
                                        </a>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="enrollment_date" class="form-label">Ngày ghi danh <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="enrollment_date" name="enrollment_date" value="{{ old('enrollment_date', date('Y-m-d')) }}" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                    <select class="form-control" id="status" name="status" required>
                                        <option value="waiting" {{ old('status') == 'waiting' ? 'selected' : '' }}>Danh sách chờ</option>
                                        <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Đang học</option>
                                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Đã hoàn thành</option>
                                        <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Ghi chú</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Thông tin học phí</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="original_fee" class="form-label">Học phí gốc</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="original_fee" value="{{ number_format($courseItem->fee, 0, ',', '.') }}" readonly>
                                        <span class="input-group-text">VND</span>
                                    </div>
                                    <input type="hidden" id="base_fee" value="{{ $courseItem->fee }}">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="discount_percentage" class="form-label">Giảm giá (%)</label>
                                            <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" 
                                                   value="{{ old('discount_percentage', 0) }}" min="0" max="100" step="1">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="discount_amount" class="form-label">Giảm giá (VND)</label>
                                            <input type="number" class="form-control" id="discount_amount" name="discount_amount" 
                                                   value="{{ old('discount_amount', 0) }}" min="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="final_fee" class="form-label">Học phí cuối cùng <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="final_fee" name="final_fee" 
                                               value="{{ old('final_fee', $courseItem->fee) }}" min="0" required>
                                        <span class="input-group-text">VND</span>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="has_payment" checked>
                                        <label class="form-check-label fw-bold" for="has_payment">Thanh toán ngay</label>
                                    </div>
                                </div>
                                
                                <div id="payment_fields">
                                    <div class="mb-3">
                                        <label for="payment_amount" class="form-label">Số tiền thanh toán</label>
                                        <div class="input-group">
                                            <input type="number" class="form-control" id="payment_amount" name="payment_amount" 
                                                   value="{{ old('payment_amount') }}" min="0">
                                            <span class="input-group-text">VND</span>
                                        </div>
                                        <div class="form-text">
                                            <a href="#" id="pay_full_amount"><i class="fas fa-check-circle"></i> Thanh toán đủ</a>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="payment_method" class="form-label">Phương thức thanh toán</label>
                                        <select class="form-control" id="payment_method" name="payment_method">
                                            <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                                            <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                                            <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>Thẻ tín dụng</option>
                                            <option value="qr_code" {{ old('payment_method') == 'qr_code' ? 'selected' : '' }}>Quét QR</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="payment_date" class="form-label">Ngày thanh toán</label>
                                        <input type="date" class="form-control" id="payment_date" name="payment_date" value="{{ old('payment_date', date('Y-m-d')) }}">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="payment_notes" class="form-label">Ghi chú thanh toán</label>
                                        <textarea class="form-control" id="payment_notes" name="payment_notes" rows="2">{{ old('payment_notes', 'Thanh toán khi ghi danh') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu và thêm học viên
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Khởi tạo Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: 'Chọn học viên...',
            allowClear: true
        });
        
        // Xử lý tính toán học phí
        function calculateFee() {
            var baseFee = parseFloat($('#base_fee').val()) || 0;
            var discountPercentage = parseFloat($('#discount_percentage').val()) || 0;
            var discountAmount = parseFloat($('#discount_amount').val()) || 0;
            
            var percentageDiscount = baseFee * (discountPercentage / 100);
            var finalFee = Math.max(0, baseFee - percentageDiscount - discountAmount);
            
            $('#final_fee').val(Math.round(finalFee));
            
            // Nếu có thanh toán, cập nhật số tiền thanh toán tối đa
            if ($('#has_payment').is(':checked')) {
                $('#payment_amount').attr('max', finalFee);
            }
        }
        
        $('#discount_percentage, #discount_amount').on('input', calculateFee);
        
        // Xử lý ẩn/hiện trường thanh toán
        $('#has_payment').change(function() {
            if ($(this).is(':checked')) {
                $('#payment_fields').slideDown();
            } else {
                $('#payment_fields').slideUp();
                $('#payment_amount').val(0);
            }
        });
        
        // Xử lý nút thanh toán đủ
        $('#pay_full_amount').click(function(e) {
            e.preventDefault();
            var finalFee = parseFloat($('#final_fee').val()) || 0;
            $('#payment_amount').val(finalFee);
        });
        
        // Khởi tạo ban đầu
        calculateFee();
    });
</script>
@endpush 