@extends('layouts.payment')

@section('page-title', 'Thanh toán học phí')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Thanh toán học phí</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-title">Thông tin thanh toán</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th>Học viên:</th>
                                <td>{{ $enrollment->student->full_name }}</td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $enrollment->student->email }}</td>
                            </tr>
                            <tr>
                                <th>Khóa học:</th>
                                <td>{{ $enrollment->courseItem->name }}</td>
                            </tr>
                            <tr>
                                <th>Số tiền cần thanh toán:</th>
                                <td class="text-danger fw-bold">{{ number_format($payment->amount) }} VNĐ</td>
                            </tr>
                        </table>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Hướng dẫn thanh toán:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Quét mã QR bằng ứng dụng ngân hàng</li>
                                <li>Chuyển khoản với nội dung: <strong>HP{{ $enrollment->student->id }}_{{ $enrollment->courseItem->id }}</strong></li>
                                <li>Số tiền chính xác: <strong>{{ number_format($payment->amount) }} VNĐ</strong></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="text-center">
                            <h5 class="mb-3">Quét mã QR để thanh toán</h5>
                            
                            @if(isset($qr_error))
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ $qr_error }}
                            </div>
                            @endif
                            
                            <div class="qr-container mb-3" id="qrcode"></div>
                            
                            <div class="text-center mb-3">
                                <button id="downloadQRBtn" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-download me-1"></i> Tải mã QR
                                </button>
                            </div>
                            
                            <div class="bank-info p-3 bg-light rounded border">
                                <p class="mb-1"><strong>Ngân hàng:</strong> {{ config('sepay.bank_name', 'Vietinbank') }}</p>
                                <p class="mb-1"><strong>Số tài khoản:</strong> {{ config('sepay.bank_number', '103870429701') }}</p>
                                <p class="mb-1"><strong>Chủ tài khoản:</strong> {{ config('sepay.account_owner', 'DO HOANG NAM') }}</p>
                                <p class="mb-0"><strong>Nội dung CK:</strong> HP{{ $enrollment->student->id }}_{{ $enrollment->courseItem->id }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-center">
                    <button id="checkStatusBtn" class="btn btn-success">
                        <i class="fas fa-sync-alt me-1"></i> Kiểm tra trạng thái thanh toán
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
$(document).ready(function() {
    // Kiểm tra nếu có QR image từ API
    @if(isset($qr_image) && !empty($qr_image))
    // Hiển thị QR image từ API SePay
    var qrContainer = document.getElementById("qrcode");
    qrContainer.innerHTML = '<img src="{{ $qr_image }}" alt="QR Code" class="img-fluid">';
    @elseif(isset($qrString) && !empty($qrString))
    // Tạo QR code từ chuỗi QR được tạo bởi server
    var qrcode = new QRCode(document.getElementById("qrcode"), {
        text: "{{ $qrString }}",
        width: 200,
        height: 200,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
    @else
    // Tạo QR code theo định dạng đơn giản khi không có dữ liệu từ API
    var bankNumber = "{{ config('sepay.bank_number', '103870429701') }}";
    var bankName = "{{ config('sepay.bank_name', 'Vietinbank') }}";
    var accountOwner = "{{ config('sepay.account_owner', 'DO HOANG NAM') }}";
    var amount = "{{ $payment->amount }}";
    var content = "HP{{ $enrollment->student->id }}_{{ $enrollment->courseItem->id }}";
    
    // Tạo URL QR SePay trực tiếp
    var qrImageUrl = 'https://qr.sepay.vn/img?acc=' + bankNumber 
        + '&bank={{ config('sepay.bank_code', 'ICB') }}' 
        + '&amount=' + amount 
        + '&des=' + encodeURIComponent(content)
        + '&template=compact';
    
    var qrContainer = document.getElementById("qrcode");
    qrContainer.innerHTML = '<img src="' + qrImageUrl + '" alt="QR Code" class="img-fluid">';
    @endif
    
    // Tải mã QR
    $('#downloadQRBtn').on('click', function() {
        var img = document.querySelector('#qrcode img');
        if (img) {
            // Nếu là hình ảnh, tải trực tiếp
            var link = document.createElement('a');
            link.href = img.src;
            if (img.src.startsWith('data:')) {
                // Nếu là data URL
                link.href = img.src;
            } else {
                // Nếu là URL thông thường, thêm &download=true
                link.href = img.src + '&download=true';
            }
            link.download = 'QR_thanh_toan_HP{{ $enrollment->student->id }}_{{ $enrollment->courseItem->id }}.png';
            link.click();
        } else {
            var canvas = document.querySelector('#qrcode canvas');
            if (canvas) {
                var image = canvas.toDataURL("image/png").replace("image/png", "image/octet-stream");
                var link = document.createElement('a');
                link.href = image;
                link.download = 'QR_thanh_toan_HP{{ $enrollment->student->id }}_{{ $enrollment->courseItem->id }}.png';
                link.click();
            } else {
                alert('Không thể tải mã QR');
            }
        }
    });
    
    // Biến lưu trạng thái kiểm tra
    let isChecking = false;
    
    // Kiểm tra trạng thái thanh toán
    $('#checkStatusBtn').on('click', function() {
        if (isChecking) return;
        
        isChecking = true;
        $('#checkStatusBtn').html('<i class="fas fa-spinner fa-spin me-1"></i> Đang kiểm tra...');
        
        // Gọi API kiểm tra thanh toán
        $.ajax({
            url: '{{ route("payment.gateway.check-direct", ["student_id" => $enrollment->student->id, "course_id" => $enrollment->courseItem->id]) }}',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                isChecking = false;
                
                if (response.success && response.confirmed) {
                    // Hiển thị thông báo thành công
                    $('.card-body').prepend(
                        '<div class="alert alert-success mb-4">' +
                        '<i class="fas fa-check-circle me-2"></i>' +
                        '<strong>Thanh toán đã được xác nhận!</strong>' +
                        '<p class="mb-0 mt-2">Cảm ơn bạn đã hoàn tất thanh toán. Thông tin đã được cập nhật trong hệ thống.</p>' +
                        '</div>'
                    );
                    
                    // Ẩn phần QR và hướng dẫn thanh toán
                    $('.qr-container').addClass('d-none');
                    $('#downloadQRBtn').addClass('d-none');
                    $('.alert-info').addClass('d-none');
                    
                    // Cập nhật nút kiểm tra
                    $('#checkStatusBtn').html('<i class="fas fa-check-circle me-1"></i> Đã xác nhận');
                    $('#checkStatusBtn').removeClass('btn-success').addClass('btn-outline-success');
                    $('#checkStatusBtn').prop('disabled', true);
                    
                    // Reload trang sau 5 giây để hiển thị kết quả
                    setTimeout(function() {
                        window.location.reload();
                    }, 5000);
                } else {
                    // Nếu chưa thanh toán, hiển thị thông báo
                    $('#checkStatusBtn').html('<i class="fas fa-sync-alt me-1"></i> Kiểm tra trạng thái thanh toán');
                    
                    // Hiển thị thông báo chưa thanh toán
                    const alertHTML = '<div id="status-alert" class="alert alert-info alert-dismissible fade show mt-3">' +
                        '<i class="fas fa-info-circle me-2"></i> ' +
                        'Chưa nhận được thông tin thanh toán. Vui lòng thử lại sau khi đã chuyển khoản.' +
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>';
                    
                    // Xóa thông báo cũ nếu có
                    $('#status-alert').remove();
                    
                    // Thêm thông báo mới
                    $('#checkStatusBtn').parent().after(alertHTML);
                }
            },
            error: function() {
                isChecking = false;
                $('#checkStatusBtn').html('<i class="fas fa-sync-alt me-1"></i> Kiểm tra trạng thái thanh toán');
                
                // Hiển thị thông báo lỗi
                const alertHTML = '<div id="status-alert" class="alert alert-danger alert-dismissible fade show mt-3">' +
                    '<i class="fas fa-exclamation-triangle me-2"></i> ' +
                    'Có lỗi xảy ra khi kiểm tra trạng thái. Vui lòng thử lại.' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                    '</div>';
                
                // Xóa thông báo cũ nếu có
                $('#status-alert').remove();
                
                // Thêm thông báo mới
                $('#checkStatusBtn').parent().after(alertHTML);
            }
        });
    });
    
    // Tự động kiểm tra trạng thái mỗi 30 giây
    let checkInterval = setInterval(function() {
        // Chỉ kiểm tra nếu không đang trong quá trình kiểm tra
        if (!isChecking) {
            $('#checkStatusBtn').trigger('click');
        }
    }, 30000);
    
    // Kiểm tra lần đầu sau 5 giây
    setTimeout(function() {
        $('#checkStatusBtn').trigger('click');
    }, 5000);
});
</script>
@endpush 