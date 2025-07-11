@extends('layouts.app')

@section('page-title', 'Thêm thanh toán mới')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Thanh toán</a></li>
    <li class="breadcrumb-item active">Thêm mới</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-plus-circle me-2"></i>Tạo phiếu thu
                </h5>
            </div>
            <div class="card-body">
                <form action="{{ route('payments.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label for="student_search" class="form-label">Tìm học viên</label>
                        <select id="student_search" class="form-control"></select>
                    </div>

                    <div id="enrollment_info" class="mt-4" style="display: none;">
                        <h6>Thông tin ghi danh</h6>
                        <div id="enrollment_details" class="p-3 border rounded">
                            <!-- Enrollment details will be loaded here via AJAX -->
                        </div>
                        
                        <input type="hidden" name="enrollment_id" id="enrollment_id">

                        <div class="row mt-3">
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">Số tiền <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="payment_date" class="form-label">Ngày thanh toán <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" id="payment_date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', date('Y-m-d')) }}" required>
                                @error('payment_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="payment_method" class="form-label">Phương thức thanh toán <span class="text-danger">*</span></label>
                                <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                    <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Tiền mặt</option>
                                    <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Chuyển khoản</option>
                                    <option value="other" {{ old('payment_method') == 'other' ? 'selected' : '' }}>Khác</option>
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 mb-3">
                                <label for="notes" class="form-label">Ghi chú</label>
                                <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Lưu thanh toán
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
                    results: $.map(data, function(item) {
                        return {
                            id: item.id,
                            text: item.text,
                            enrollments: item.enrollments
                        }
                    })
                };
            },
            cache: true
        }
    });

    $('#student_search').on('select2:select', function (e) {
        var data = e.params.data;
        if (data.enrollments && data.enrollments.length > 0) {
            var detailsHtml = '<ul>';
            data.enrollments.forEach(function(enrollment) {
                detailsHtml += `
                    <li>
                        <a href="#" class="select-enrollment" data-id="${enrollment.id}">
                            ${enrollment.course_class.course.name} - Lớp ${enrollment.course_class.name} (Còn nợ: ${enrollment.remaining_fee.toLocaleString()} VNĐ)
                        </a>
                    </li>
                `;
            });
            detailsHtml += '</ul>';
            $('#enrollment_details').html(detailsHtml);
            $('#enrollment_info').show();
        } else {
            $('#enrollment_details').html('<p class="text-muted">Học viên này chưa ghi danh khóa học nào.</p>');
            $('#enrollment_info').show();
        }
    });

    $(document).on('click', '.select-enrollment', function(e) {
        e.preventDefault();
        var enrollmentId = $(this).data('id');
        $('#enrollment_id').val(enrollmentId);
        
        $('.select-enrollment').removeClass('fw-bold text-success');
        $(this).addClass('fw-bold text-success');
        
        // Fetch remaining fee and suggest amount
        $.get(`/api/enrollments/${enrollmentId}/info`, function(data) {
            $('#amount').val(data.remaining_fee);
        });
    });

    // Handle initial student if provided
    @if(request()->has('student_id'))
        var studentId = {{ request('student_id') }};
        $.get(`/api/students/${studentId}/info`, function(data) {
            var option = new Option(data.full_name + ' - ' + data.phone, data.id, true, true);
            $('#student_search').append(option).trigger('change');
            $('#student_search').trigger({
                type: 'select2:select',
                params: {
                    data: {
                        id: data.id,
                        text: data.full_name + ' - ' + data.phone,
                        enrollments: data.enrollments
                    }
                }
            });
        });
    @endif
    
    // Handle initial enrollment if provided
    @if(request()->has('enrollment_id') && $enrollment)
        var enrollment = @json($enrollment);
        var student = enrollment.student;
        var course = enrollment.course_class.course;
        
        var studentText = `${student.full_name} - ${student.phone}`;
        var option = new Option(studentText, student.id, true, true);
        $('#student_search').append(option).trigger('change');

        var detailsHtml = `
            <p><strong>Học viên:</strong> ${student.full_name}</p>
            <p><strong>Lớp:</strong> ${course.name} - ${enrollment.course_class.name}</p>
            <p><strong>Học phí:</strong> ${enrollment.final_fee.toLocaleString()} VNĐ</p>
            <p><strong>Đã đóng:</strong> ${enrollment.total_paid.toLocaleString()} VNĐ</p>
            <p class="text-danger"><strong>Còn nợ:</strong> ${enrollment.remaining_fee.toLocaleString()} VNĐ</p>
        `;
        $('#enrollment_details').html(detailsHtml);
        $('#enrollment_id').val(enrollment.id);
        $('#amount').val(enrollment.remaining_fee);
        $('#enrollment_info').show();
    @endif
});
</script>
@endpush 