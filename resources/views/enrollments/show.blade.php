@extends('layouts.app')

@section('page-title', 'Chi tiết ghi danh')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('enrollments.index') }}">Đăng ký học</a></li>
    <li class="breadcrumb-item active">Chi tiết ghi danh</li>
@endsection

@section('content')
<div class="container-fluid">
    <!-- Thông báo -->
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Chi tiết ghi danh
                    </h5>
                    <div>
                        <a href="{{ route('enrollments.edit', $enrollment->id) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-edit me-1"></i> Chỉnh sửa
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Thông tin học viên</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Họ tên:</th>
                                    <td>
                                        <a href="{{ route('students.show', $enrollment->student->id) }}">
                                            {{ $enrollment->student->full_name }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Số điện thoại:</th>
                                    <td>{{ $enrollment->student->phone }}</td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td>{{ $enrollment->student->email ?: 'Không có' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="border-bottom pb-2 mb-3">Thông tin khóa học</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Khóa học:</th>
                                    <td>{{ $enrollment->courseItem->name }}</td>
                                </tr>
                                <tr>
                                    <th>Ngày ghi danh:</th>
                                    <td>{{ $enrollment->enrollment_date->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <th>Trạng thái:</th>
                                    <td>
                                        @if($enrollment->status == 'enrolled')
                                            <span class="badge bg-success">Đang học</span>
                                        @elseif($enrollment->status == 'completed')
                                            <span class="badge bg-info">Đã hoàn thành</span>
                                        @elseif($enrollment->status == 'dropped')
                                            <span class="badge bg-danger">Đã bỏ học</span>
                                        @elseif($enrollment->status == 'transferred')
                                            <span class="badge bg-warning">Đã chuyển lớp</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $enrollment->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h4>Thông tin thanh toán</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    <tr>
                                        <th>Học phí gốc:</th>
                                        <td>{{ number_format($enrollment->courseItem->fee) }} VNĐ</td>
                                    </tr>
                                    <tr>
                                        <th>Giảm giá:</th>
                                        <td>
                                            @if($enrollment->discount_percentage > 0)
                                                {{ $enrollment->discount_percentage }}% 
                                                ({{ number_format($enrollment->discount_amount) }} VNĐ)
                                            @elseif($enrollment->discount_amount > 0)
                                                {{ number_format($enrollment->discount_amount) }} VNĐ
                                            @else
                                                0 VNĐ
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Học phí cuối cùng:</th>
                                        <td>{{ number_format($enrollment->final_fee) }} VNĐ</td>
                                    </tr>
                                    <tr>
                                        <th>Đã thanh toán:</th>
                                        <td>{{ number_format($enrollment->getTotalPaidAmount()) }} VNĐ</td>
                                    </tr>
                                    <tr>
                                        <th>Còn thiếu:</th>
                                        <td class="{{ $enrollment->getRemainingAmount() > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($enrollment->getRemainingAmount()) }} VNĐ
                                            @if($enrollment->isFullyPaid())
                                                <span class="badge bg-success">Đã thanh toán đủ</span>
                                            @endif
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    @if(!empty($enrollment->custom_fields))
                    <div class="mb-4">
                        <h4>Thông tin tùy chỉnh</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th width="40%">Tên trường</th>
                                        <th>Giá trị</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($enrollment->custom_fields as $key => $value)
                                    <tr>
                                        <th>{{ $key }}</th>
                                        <td>{{ $value }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                    
                    @if($enrollment->notes)
                    <div class="mb-4"
                    <div class="row">
                        <div class="col-12">
                            <h6 class="border-bottom pb-2 mb-3">Ghi chú</h6>
                            <p class="mb-0">{{ $enrollment->notes }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
       
    </div>
</div>
@endsection 