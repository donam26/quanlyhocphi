@extends('layouts.app')

@section('page-title', 'Chi tiết Phiếu thu #' . $payment->id)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('payments.index') }}">Thanh toán</a></li>
    <li class="breadcrumb-item active">#{{ $payment->id }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card" id="payment-receipt">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-receipt me-2"></i>Chi tiết Phiếu thu
                </h5>
                <div class="float-end">
                    <a href="{{ route('payments.edit', $payment) }}" class="btn btn-sm btn-outline-warning">
                        <i class="fas fa-edit me-1"></i> Sửa
                    </a>
                    <button class="btn btn-sm btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> In phiếu
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h4>Thông tin Học viên</h4>
                        <p><strong>Họ tên:</strong> {{ $payment->enrollment->student->full_name }}</p>
                        <p><strong>SĐT:</strong> {{ $payment->enrollment->student->phone }}</p>
                        <p><strong>Email:</strong> {{ $payment->enrollment->student->email }}</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h4>Thông tin Phiếu thu</h4>
                        <p><strong>Mã phiếu thu:</strong> #{{ $payment->id }}</p>
                        <p><strong>Ngày tạo:</strong> {{ $payment->created_at->format('d/m/Y') }}</p>
                        <p><strong>Ngày thanh toán:</strong> {{ $payment->payment_date->format('d/m/Y') }}</p>
                        <p><strong>Trạng thái:</strong> 
                            <span class="badge bg-{{ $payment->status == 'confirmed' ? 'success' : ($payment->status == 'pending' ? 'warning' : 'danger') }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </p>
                    </div>
                </div>

                <hr>

                <h4>Chi tiết Thanh toán</h4>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Khóa học</th>
                            <th>Lớp</th>
                            <th>Phương thức</th>
                            <th class="text-end">Số tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $payment->enrollment->courseClass->course->name }}</td>
                            <td>{{ $payment->enrollment->courseClass->name }}</td>
                            <td>{{ $payment->payment_method }}</td>
                            <td class="text-end">{{ number_format($payment->amount) }} VNĐ</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Tổng cộng</td>
                            <td class="text-end fw-bold">{{ number_format($payment->amount) }} VNĐ</td>
                        </tr>
                    </tfoot>
                </table>

                <div class="row mt-4">
                    <div class="col-12">
                        <strong>Ghi chú:</strong>
                        <p>{{ $payment->notes ?: 'Không có ghi chú.' }}</p>
                    </div>
                </div>
                
                 <div class="row mt-4">
                    <div class="col-md-6">
                        <p class="fw-bold">Người lập phiếu</p>
                        <br><br>
                        <p>(Ký, họ tên)</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p class="fw-bold">Người nộp tiền</p>
                        <br><br>
                        <p>(Ký, họ tên)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        body * {
            visibility: hidden;
        }
        #payment-receipt, #payment-receipt * {
            visibility: visible;
        }
        #payment-receipt {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        .btn {
            display: none;
        }
    }
</style>
@endpush 