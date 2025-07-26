@extends('layouts.app')

@section('page-title', 'Tạo thanh toán mới')

@section('content')
<div class="container">
<div class="row">
        <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                    <h4><i class="fas fa-plus-circle"></i> Tạo phiếu thu</h4>
            </div>
                
            <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="student_search">Tìm học viên</label>
                                <select class="form-control" id="student_search"></select>
                                <div class="form-text text-muted">Nhập tên hoặc số điện thoại để tìm học viên</div>
                            </div>
                        </div>
                    </div>

                    <!-- Hiển thị thông tin ghi danh -->
                    <div id="enrollment_info" style="display: none;">
                        <h5 class="border-bottom pb-2 mb-3">Thông tin ghi danh</h5>
                        <div id="enrollment_details"></div>
                        </div>
                        
                    <!-- Form thanh toán -->
                    <div id="payment_form" style="display: none;" class="mt-4">
                        <h5 class="border-bottom pb-2 mb-3">Thông tin thanh toán</h5>
                        <form method="POST" action="{{ route('payments.store') }}">
                            @csrf
                        <input type="hidden" name="enrollment_id" id="enrollment_id">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="amount">Số tiền thanh toán <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input type="number" class="form-control @error('amount') is-invalid @enderror" 
                                                id="amount" name="amount" min="1000" required value="{{ old('amount') }}">
                                            <div class="input-group-append">
                                                <span class="input-group-text">VNĐ</span>
                                            </div>
                                        </div>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                    </div>
                            </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="payment_date">Ngày thanh toán <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control @error('payment_date') is-invalid @enderror" 
                                            id="payment_date" name="payment_date" required 
                                            value="{{ old('payment_date', date('Y-m-d')) }}">
                                @error('payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="payment_method">Hình thức thanh toán <span class="text-danger">*</span></label>
                                        <select class="form-control @error('payment_method') is-invalid @enderror" 
                                            id="payment_method" name="payment_method" required>
                                    <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                                    <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                                    <option value="other" {{ old('payment_method') == 'other' ? 'selected' : '' }}>Khác</option>
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                    </div>
                            </div>

                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label for="notes">Ghi chú</label>
                                        <input type="text" class="form-control @error('notes') is-invalid @enderror" 
                                            id="notes" name="notes" value="{{ old('notes') }}">
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                    </div>
                            </div>
                        </div>

                            <div class="form-group mt-3">
                            <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Lưu thanh toán
                            </button>
                                <a href="{{ route('payments.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Hủy
                                </a>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#student_search').select2({
        placeholder: 'Nhập tên hoặc SĐT học viên...',
        minimumInputLength: 2,
        ajax: {
            url: '{{ route("api.search.autocomplete") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        }
    });

    $('#student_search').on('select2:select', function (e) {
        var data = e.params.data;
        if (data.enrollments && data.enrollments.length > 0) {
            var detailsHtml = '<div class="alert alert-info mb-3">Chọn ghi danh để thanh toán:</div><div class="list-group mb-3">';
            data.enrollments.forEach(function(enrollment) {
                detailsHtml += `
                    <a href="#" class="list-group-item list-group-item-action select-enrollment" data-id="${enrollment.id}">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">${enrollment.course_name}</h5>
                            <small class="text-danger fw-bold">${numberFormat(enrollment.remaining_fee)} VNĐ còn thiếu</small>
                        </div>
                        <p class="mb-1">Học phí: ${numberFormat(enrollment.final_fee)} VNĐ</p>
                    </a>
                `;
            });
            detailsHtml += '</div>';
            $('#enrollment_details').html(detailsHtml);
            $('#enrollment_info').show();
        } else {
            $('#enrollment_details').html('<div class="alert alert-warning">Học viên này chưa ghi danh khóa học nào hoặc đã thanh toán đủ.</div>');
            $('#enrollment_info').show();
            $('#payment_form').hide();
        }
    });

    $(document).on('click', '.select-enrollment', function(e) {
        e.preventDefault();
        var enrollmentId = $(this).data('id');
        $('#enrollment_id').val(enrollmentId);
        
        $('.select-enrollment').removeClass('active');
        $(this).addClass('active');
        
        // Suggest remaining fee amount
        var remainingFee = $(this).find('small').text().replace(/[^\d]/g, '');
        $('#amount').val(remainingFee);
        
        // Show payment form
        $('#payment_form').show();
    });
    
    // Number format helper
    function numberFormat(number) {
        return new Intl.NumberFormat('vi-VN').format(number);
    }
    
    // Handle initial enrollment if provided
    @if(request()->has('enrollment_id') && $enrollment)
        // Set enrollment ID
        $('#enrollment_id').val({{ $enrollment->id }});
        
        // Show enrollment info
        var enrollmentHtml = `
            <div class="card mb-3">
                <div class="card-body">
                    <h5 class="card-title">Thông tin học viên</h5>
                    <p><strong>Họ tên:</strong> {{ $enrollment->student->full_name }}</p>
                    <p><strong>SĐT:</strong> {{ $enrollment->student->phone }}</p>
                    <p><strong>Email:</strong> {{ $enrollment->student->email }}</p>
                    
                    <h5 class="card-title mt-4">Thông tin khóa học</h5>
                    <p><strong>Khóa học:</strong> {{ $enrollment->courseItem->name }}</p>
                    <p><strong>Học phí:</strong> {{ number_format($enrollment->final_fee) }} VNĐ</p>
                    <p><strong>Còn thiếu:</strong> <span class="text-danger fw-bold">{{ number_format($enrollment->getRemainingAmount()) }} VNĐ</span></p>
                </div>
            </div>
        `;
        $('#enrollment_details').html(enrollmentHtml);
        $('#enrollment_info').show();
        
        // Set suggested amount
        $('#amount').val({{ $enrollment->getRemainingAmount() }});
        
        // Show payment form
        $('#payment_form').show();
    @endif
});
</script>
@endpush 