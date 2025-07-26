@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Ghi danh học viên từ danh sách chờ</h4>
            <a href="{{ route('course-items.waiting-lists', $courseItem->id) }}" class="btn btn-secondary">Quay lại</a>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Bạn đang ghi danh học viên <strong>{{ $student->full_name }}</strong> từ danh sách chờ vào khóa học <strong>{{ $courseItem->name }}</strong>.
            </div>

            @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('enrollments.store-from-waiting') }}" method="POST">
                @csrf
                <input type="hidden" name="waiting_list_id" value="{{ $waitingList->id }}">
                <input type="hidden" name="student_id" value="{{ $student->id }}">
                <input type="hidden" name="course_item_id" value="{{ $courseItem->id }}">

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Thông tin học viên</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Họ tên:</strong> {{ $student->full_name }}
                                </div>
                                <div class="mb-2">
                                    <strong>Số điện thoại:</strong> {{ $student->phone }}
                                </div>
                                <div class="mb-2">
                                    <strong>Email:</strong> {{ $student->email ?: 'Chưa cung cấp' }}
                                </div>
                                @if($student->date_of_birth)
                                <div class="mb-2">
                                    <strong>Ngày sinh:</strong> {{ $student->date_of_birth->format('d/m/Y') }}
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Thông tin khóa học</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Khóa học:</strong> {{ $courseItem->name }}
                                </div>
                                <div class="mb-2">
                                    <strong>Học phí gốc:</strong> {{ number_format($initialFee, 0, ',', '.') }} VND
                                </div>
                                @if($waitingList->interest_level)
                                <div class="mb-2">
                                    <strong>Mức độ quan tâm:</strong> 
                                    @if($waitingList->interest_level == 'high')
                                    <span class="badge bg-success">Cao</span>
                                    @elseif($waitingList->interest_level == 'medium')
                                    <span class="badge bg-primary">Trung bình</span>
                                    @else
                                    <span class="badge bg-secondary">Thấp</span>
                                    @endif
                                </div>
                                @endif
                                @if($waitingList->contact_notes)
                                <div class="mb-2">
                                    <strong>Ghi chú trước đó:</strong>
                                    <div class="small bg-light p-2 mt-1 rounded">{{ $waitingList->contact_notes }}</div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <h5 class="mb-3">Thông tin đăng ký</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="enrollment_date">Ngày đăng ký <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="enrollment_date" name="enrollment_date" value="{{ old('enrollment_date', now()->toDateString()) }}" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="status">Trạng thái <span class="text-danger">*</span></label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="enrolled" selected>Đã ghi danh</option>
                                <option value="cancelled">Đã hủy</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="discount_type">Loại chiết khấu</label>
                            <select class="form-control" id="discount_type" name="discount_type">
                                <option value="none">Không có chiết khấu</option>
                                <option value="percentage">Chiết khấu theo %</option>
                                <option value="fixed">Chiết khấu theo số tiền cố định</option>
                                <option value="custom">Tùy chỉnh học phí cuối cùng</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-4 discount-field d-none" id="percentage_field">
                        <div class="form-group">
                            <label for="discount_percentage">Chiết khấu (%)</label>
                            <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" min="0" max="100" step="0.1" value="0">
                        </div>
                    </div>

                    <div class="col-md-4 discount-field d-none" id="amount_field">
                        <div class="form-group">
                            <label for="discount_amount">Số tiền chiết khấu (VND)</label>
                            <input type="number" class="form-control" id="discount_amount" name="discount_amount" min="0" value="0">
                        </div>
                    </div>

                    <div class="col-md-4 discount-field d-none" id="custom_field">
                        <div class="form-group">
                            <label for="final_fee">Học phí sau chiết khấu</label>
                            <input type="number" class="form-control" id="final_fee" name="final_fee" min="0" value="{{ $initialFee }}">
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card border-primary mb-3">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Học phí và thanh toán</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <strong>Học phí gốc:</strong> <span id="display_original_fee">{{ number_format($initialFee, 0, ',', '.') }}</span> VND
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <strong>Chiết khấu:</strong> <span id="display_discount">0</span> VND 
                                            (<span id="display_discount_percent">0</span>%)
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-2">
                                            <strong>Học phí sau chiết khấu:</strong> 
                                            <span id="display_final_fee" class="text-primary fw-bold">{{ number_format($initialFee, 0, ',', '.') }}</span> VND
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="has_payment" name="has_payment">
                                            <label class="form-check-label" for="has_payment">
                                                Tạo khoản thanh toán ban đầu
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div id="payment_fields" class="row g-3 d-none">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="initial_payment">Số tiền thanh toán <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="initial_payment" name="initial_payment" min="0" value="{{ $initialFee }}">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="payment_method">Phương thức thanh toán <span class="text-danger">*</span></label>
                                            <select class="form-control" id="payment_method" name="payment_method">
                                                @foreach($paymentMethods as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6 payment-field" id="transaction_reference_field">
                                        <div class="form-group">
                                            <label for="transaction_reference">Mã giao dịch</label>
                                            <input type="text" class="form-control" id="transaction_reference" name="transaction_reference">
                                        </div>
                                    </div>

                                    <div class="col-md-6 payment-field" id="collector_field">
                                        <div class="form-group">
                                            <label for="collector">Người thu tiền</label>
                                            <input type="text" class="form-control" id="collector" name="collector">
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="payment_notes">Ghi chú thanh toán</label>
                                            <textarea class="form-control" id="payment_notes" name="payment_notes" rows="2"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-group">
                            <label for="notes">Ghi chú</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Hoàn tất ghi danh
                    </button>
                    <a href="{{ route('course-items.waiting-lists', $courseItem->id) }}" class="btn btn-secondary ms-2">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const initialFee = {{ $initialFee }};
        
        // Xử lý khi chọn loại chiết khấu
        $('#discount_type').change(function() {
            const type = $(this).val();
            $('.discount-field').addClass('d-none');
            
            if (type === 'percentage') {
                $('#percentage_field').removeClass('d-none');
            } else if (type === 'fixed') {
                $('#amount_field').removeClass('d-none');
            } else if (type === 'custom') {
                $('#custom_field').removeClass('d-none');
            }
            
            calculateFee();
        });
        
        // Xử lý khi nhập % chiết khấu
        $('#discount_percentage').on('input', function() {
            calculateFee();
        });
        
        // Xử lý khi nhập số tiền chiết khấu
        $('#discount_amount').on('input', function() {
            calculateFee();
        });
        
        // Xử lý khi nhập học phí tùy chỉnh
        $('#final_fee').on('input', function() {
            const customFee = parseFloat($(this).val()) || 0;
            const discount = initialFee - customFee;
            const discountPercent = (discount / initialFee * 100).toFixed(1);
            
            $('#display_discount').text(formatNumber(discount));
            $('#display_discount_percent').text(discountPercent);
            $('#display_final_fee').text(formatNumber(customFee));
            
            // Cập nhật giá trị các trường ẩn
            $('input[name="discount_amount"]').val(discount);
            $('input[name="discount_percentage"]').val(discountPercent);
            $('input[name="final_fee"]').val(customFee);
            
            // Cập nhật giá trị thanh toán ban đầu
            $('#initial_payment').val(customFee);
        });
        
        // Tính toán học phí cuối cùng
        function calculateFee() {
            let discountAmount = 0;
            let discountPercent = 0;
            let finalFee = initialFee;
            
            const discountType = $('#discount_type').val();
            
            if (discountType === 'percentage') {
                discountPercent = parseFloat($('#discount_percentage').val()) || 0;
                discountAmount = initialFee * discountPercent / 100;
                finalFee = initialFee - discountAmount;
            } else if (discountType === 'fixed') {
                discountAmount = parseFloat($('#discount_amount').val()) || 0;
                discountPercent = (discountAmount / initialFee * 100).toFixed(1);
                finalFee = initialFee - discountAmount;
            } else if (discountType === 'custom') {
                finalFee = parseFloat($('#final_fee').val()) || 0;
                discountAmount = initialFee - finalFee;
                discountPercent = (discountAmount / initialFee * 100).toFixed(1);
            }
            
            // Cập nhật hiển thị
            $('#display_discount').text(formatNumber(discountAmount));
            $('#display_discount_percent').text(discountPercent);
            $('#display_final_fee').text(formatNumber(finalFee));
            
            // Cập nhật giá trị các trường ẩn
            $('input[name="discount_amount"]').val(discountAmount);
            $('input[name="discount_percentage"]').val(discountPercent);
            $('input[name="final_fee"]').val(finalFee);
            
            // Cập nhật giá trị thanh toán ban đầu
            $('#initial_payment').val(finalFee);
        }
        
        // Xử lý checkbox tạo thanh toán
        $('#has_payment').change(function() {
            if ($(this).is(':checked')) {
                $('#payment_fields').removeClass('d-none');
                // Đặt lại giá trị mặc định cho initial_payment bằng với học phí cuối cùng
                const finalFee = parseFloat($('input[name="final_fee"]').val()) || 0;
                $('#initial_payment').val(finalFee);
            } else {
                $('#payment_fields').addClass('d-none');
                // Xóa giá trị initial_payment để tránh gửi dữ liệu khi không chọn tạo thanh toán
                $('#initial_payment').val('');
            }
        });
        
        // Xử lý khi thay đổi phương thức thanh toán
        $('#payment_method').change(function() {
            const method = $(this).val();
            
            if (method === 'cash') {
                $('#collector_field').removeClass('d-none');
                $('#transaction_reference_field').addClass('d-none');
            } else if (method === 'bank_transfer' || method === 'card' || method === 'qr_code') {
                $('#transaction_reference_field').removeClass('d-none');
                $('#collector_field').addClass('d-none');
            } else {
                $('.payment-field').addClass('d-none');
            }
        });
        
        // Định dạng số
        function formatNumber(number) {
            return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        
        // Khởi tạo giá trị ban đầu
        calculateFee();
        $('#payment_method').change();
    });
</script>
@endpush 