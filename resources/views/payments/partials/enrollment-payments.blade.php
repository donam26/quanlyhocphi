<div class="enrollment-info mb-4">
    <h5>Thông tin học viên</h5>
    <div class="row">
        <div class="col-md-6">
            <p><strong>Học viên:</strong> {{ $enrollment->student->full_name }}</p>
            <p><strong>Khóa học:</strong> {{ $enrollment->courseItem->name }}</p>
            <p><strong>Ngày ghi danh:</strong> {{ $enrollment->enrollment_date->format('d/m/Y') }}</p>
        </div>
        <div class="col-md-6">
            <p><strong>Học phí gốc:</strong> {{ number_format($enrollment->courseItem->fee) }} đ</p>
            <p><strong>Học phí cuối:</strong> {{ number_format($enrollment->final_fee) }} đ</p>
            <p>
                <strong>Trạng thái:</strong> 
                @if($remainingAmount <= 0)
                    <span class="badge bg-success">Đã thanh toán đủ</span>
                @else
                    <span class="badge bg-warning">Còn thiếu {{ number_format($remainingAmount) }} đ</span>
                @endif
            </p>
        </div>
    </div>
</div>

<h5>Lịch sử thanh toán</h5>
@if($payments->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Mã GD</th>
                    <th>Ngày thanh toán</th>
                    <th>Số tiền</th>
                    <th>Phương thức</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td>{{ $payment->transaction_reference ?? 'PT' . str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $payment->payment_date->format('d/m/Y H:i') }}</td>
                        <td class="text-end fw-bold">{{ number_format($payment->amount) }} đ</td>
                        <td>
                            @if($payment->payment_method == 'cash')
                                <span class="badge bg-success">Tiền mặt</span>
                            @elseif($payment->payment_method == 'bank_transfer')
                                <span class="badge bg-primary">Chuyển khoản</span>
                            @elseif($payment->payment_method == 'card')
                                <span class="badge bg-info">Thẻ</span>
                            @else
                                <span class="badge bg-secondary">Khác</span>
                            @endif
                        </td>
                        <td>
                            @if($payment->status == 'confirmed')
                                <span class="badge bg-success">Đã xác nhận</span>
                            @elseif($payment->status == 'pending')
                                <span class="badge bg-warning">Chờ xác nhận</span>
                            @elseif($payment->status == 'cancelled')
                                <span class="badge bg-danger">Đã hủy</span>
                            @elseif($payment->status == 'refunded')
                                <span class="badge bg-info">Đã hoàn tiền</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-secondary">
                    <td colspan="2" class="text-end fw-bold">Tổng thanh toán:</td>
                    <td class="text-end fw-bold">{{ number_format($paidAmount) }} đ</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
@else
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Chưa có lịch sử thanh toán nào.
    </div>
@endif 