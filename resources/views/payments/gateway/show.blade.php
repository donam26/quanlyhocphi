@extends('layouts.app')

@section('title', 'Thanh toán học phí')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header text-center bg-primary text-white">
                    <h4 class="mb-0">Thanh toán học phí</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <p>Xin chào <strong>{{ $payment->enrollment->student->full_name }}</strong>,</p>
                        <p>Vui lòng quét mã QR dưới đây để hoàn tất thanh toán cho khóa học:</p>
                        <h5 class="text-info">{{ $payment->enrollment->courseItem->name }}</h5>
                    </div>

                    <ul class="list-group list-group-flush mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Mã thanh toán:
                            <span class="badge bg-secondary">{{ config('sepay.pattern') . $payment->enrollment->student->id . $payment->id }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Số tiền cần thanh toán:
                            <strong class="text-danger fs-5">{{ number_format($payment->amount, 0, ',', '.') }} VND</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Nội dung chuyển khoản:
                            <strong>{{ config('sepay.pattern') . $payment->enrollment->student->id . $payment->id }}</strong>
                        </li>
                    </ul>

                    <!-- Khu vực hiển thị QR code -->
                    <div id="qr-display-area" class="text-center">
                        <div class="qr-container mb-3 p-3 border rounded">
                            <div class="spinner-border text-primary my-5" role="status" id="qr-loading">
                                <span class="visually-hidden">Đang tạo mã QR...</span>
                            </div>
                            <img id="qr-image" src="" alt="Mã QR thanh toán" class="img-fluid mb-3" style="max-width: 300px; display: none;">
                        </div>
                        
                        <div id="payment-details" class="mb-4" style="display: none;">
                            <h6 class="text-center mb-3">Thông tin chuyển khoản:</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tr>
                                        <td class="fw-bold" width="40%">Ngân hàng:</td>
                                        <td id="bank-name"></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Số tài khoản:</td>
                                        <td>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span id="bank-account-text"></span>
                                                <button class="btn btn-sm btn-outline-secondary copy-btn" data-clipboard-target="#bank-account-text">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Chủ tài khoản:</td>
                                        <td id="account-owner"></td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Số tiền:</td>
                                        <td>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span id="amount-text"></span>
                                                <button class="btn btn-sm btn-outline-secondary copy-btn" data-clipboard-target="#amount-text">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold">Nội dung CK:</td>
                                        <td>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span id="content-text"></span>
                                                <button class="btn btn-sm btn-outline-secondary copy-btn" data-clipboard-target="#content-text">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        
                        <div id="countdown-timer" class="mt-2 text-muted"></div>
                        <button class="btn btn-outline-secondary mt-3" id="refresh-qr" style="display: none;">Làm mới mã QR</button>
                    </div>

                    <div id="payment-status-area" class="mt-4">
                        <div class="alert alert-info text-center">Đang chờ thanh toán...</div>
                    </div>

                </div>
                <div class="card-footer text-muted text-center small">
                    <p>Hệ thống sẽ tự động cập nhật khi phát hiện thanh toán.</p>
                    <p>Nếu đã thanh toán nhưng chưa được cập nhật, vui lòng liên hệ với chúng tôi.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
<script>
$(document).ready(function() {
    let checkStatusInterval;
    new ClipboardJS('.copy-btn');

    $('.copy-btn').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var originalHTML = btn.html();
        btn.html('<i class="fas fa-check text-success"></i>');
        setTimeout(function() {
            btn.html(originalHTML);
        }, 1000);
    });
    
    // Tự động tạo mã QR khi trang load
    generateQrCode();
    
    // Nút refresh QR code
    $('#refresh-qr').on('click', function() {
        $('#qr-image').hide();
        $('#payment-details').hide();
        $('#qr-loading').show();
        $(this).hide();
        
        generateQrCode();
    });

    function generateQrCode() {
        $.ajax({
            url: '{{ route("payment.gateway.generate-qr", $payment) }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if(response.success) {
                    // Hiển thị mã QR
                    $('#qr-loading').hide();
                    $('#qr-image').attr('src', response.data.qr_image).show();
                    
                    // Hiển thị thông tin chuyển khoản
                    $('#bank-name').text(response.data.bank_name);
                    $('#bank-account-text').text(response.data.bank_account);
                    $('#account-owner').text(response.data.account_owner);
                    $('#amount-text').text(response.data.amount.toLocaleString('vi-VN') + ' VND');
                    $('#content-text').text(response.data.content);
                    
                    // Hiển thị các thông tin
                    $('#payment-details').show();
                    $('#refresh-qr').show();
                    
                    // Lưu thông tin giao dịch
                    $('#payment-status-area').html('<div class="alert alert-info text-center">Đang chờ thanh toán...</div>');
                    
                    // Hiển thị thông tin chi tiết về giao dịch
                    if (response.data.fallback) {
                        $('#countdown-timer').html('<div class="alert alert-warning">Hệ thống QR bị lỗi. Đang sử dụng QR dự phòng. Vui lòng nhập thông tin chuyển khoản thủ công.</div>');
                    }

                    // Bắt đầu kiểm tra trạng thái thanh toán
                    startCheckingPaymentStatus();
                } else {
                    $('#qr-loading').hide();
                    $('#countdown-timer').html('<div class="alert alert-danger">' + response.message + '</div>');
                    $('#refresh-qr').show();
                }
            },
            error: function(xhr) {
                $('#qr-loading').hide();
                var errorMsg = 'Có lỗi xảy ra. Vui lòng thử lại.';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                $('#countdown-timer').html('<div class="alert alert-danger">' + errorMsg + '</div>');
                $('#refresh-qr').show();
            }
        });
    }

    function startCheckingPaymentStatus() {
        if (checkStatusInterval) {
            clearInterval(checkStatusInterval);
        }
        
        checkStatusInterval = setInterval(function() {
            $.get('{{ route("payment.gateway.status", $payment) }}', function(data) {
                if (data.success && data.confirmed) {
                    clearInterval(checkStatusInterval);
                    $('#payment-status-area').html(`
                        <div class="alert alert-success text-center">
                            <h4 class="alert-heading">Thanh toán thành công!</h4>
                            <p>Cảm ơn bạn đã hoàn tất thanh toán. Giao dịch đã được ghi nhận.</p>
                            <hr>
                            <p class="mb-0">Mã giao dịch: ${data.transaction_reference || 'N/A'}</p>
                            <p class="mb-0">Thời gian: ${data.payment_date || 'N/A'}</p>
                            <hr>
                            <a href="{{ route('students.show', $payment->enrollment->student_id) }}" class="btn btn-success">Về trang học viên</a>
                        </div>
                    `);
                    $('#refresh-qr').hide();
                }
            });
        }, 5000); // Kiểm tra mỗi 5 giây
    }
});
</script>
@endpush 