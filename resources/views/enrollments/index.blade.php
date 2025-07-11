@extends('layouts.app')

@section('title', 'Danh sách ghi danh')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Danh sách ghi danh</h1>
        <a href="{{ route('enrollments.create') }}" class="btn btn-primary">
            <i class="fas fa-plus-circle mr-1"></i> Thêm ghi danh mới
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="stats-number">{{ $enrollments->count() }}</p>
                    <p class="stats-label">Tổng ghi danh</p>
                    <i class="fas fa-graduation-cap fa-2x opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card warning">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="stats-number">{{ $pendingCount ?? 0 }}</p>
                    <p class="stats-label">Chưa thanh toán</p>
                    <i class="fas fa-clock fa-2x opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card danger">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="stats-number">{{ number_format($totalPaid ?? 0) }}</p>
                    <p class="stats-label">Đã thanh toán (VND)</p>
                    <i class="fas fa-money-bill-wave fa-2x opacity-75"></i>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <p class="stats-number">{{ number_format($totalUnpaid ?? 0) }}</p>
                    <p class="stats-label">Còn nợ (VND)</p>
                    <i class="fas fa-exclamation-circle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-white py-3">
            <div class="row">
                <div class="col-md-8">
                    <form action="{{ route('enrollments.index') }}" method="GET" class="d-flex gap-2">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Tìm theo tên học viên..." name="search" value="{{ request('search') }}">
                            <button class="btn btn-outline-secondary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                        
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Trạng thái --</option>
                            <option value="enrolled" {{ request('status') == 'enrolled' ? 'selected' : '' }}>Đã ghi danh</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                        
                        <select name="payment_status" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Trạng thái thanh toán --</option>
                            <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                            <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Thanh toán một phần</option>
                            <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Chưa thanh toán</option>
                        </select>
                    </form>
                </div>
                <div class="col-md-4 text-end">
                    <a href="{{ route('enrollments.unpaid') }}" class="btn btn-warning">
                        <i class="fas fa-exclamation-circle mr-1"></i> Danh sách chưa thanh toán
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Học viên</th>
                            <th>Lớp học</th>
                            <th>Ngày ghi danh</th>
                            <th>Học phí</th>
                            <th>Đã thanh toán</th>
                            <th>Còn lại</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($enrollments as $enrollment)
                            <tr>
                                <td>{{ $enrollment->id }}</td>
                                <td>
                                    <div class="fw-bold">{{ $enrollment->student->full_name }}</div>
                                    <div class="small text-muted">{{ $enrollment->student->phone }}</div>
                                </td>
                                <td>
                                    <div>{{ $enrollment->courseClass->name }}</div>
                                    <div class="small text-muted">{{ $enrollment->courseClass->course->name }}</div>
                                </td>
                                <td>{{ $enrollment->enrollment_date->format('d/m/Y') }}</td>
                                <td>{{ number_format($enrollment->final_fee) }} VND</td>
                                <td>{{ number_format($enrollment->getTotalPaidAmount()) }} VND</td>
                                <td>
                                    @php
                                        $remaining = $enrollment->getRemainingAmount();
                                    @endphp
                                    @if($remaining > 0)
                                        <span class="text-danger">{{ number_format($remaining) }} VND</span>
                                    @else
                                        <span class="text-success">0 VND</span>
                                    @endif
                                </td>
                                <td>
                                    @if($enrollment->status == 'enrolled')
                                        <span class="badge bg-success">Đã ghi danh</span>
                                    @else
                                        <span class="badge bg-danger">Đã hủy</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('enrollments.show', $enrollment) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($remaining > 0)
                                            <a href="{{ route('payments.create', $enrollment) }}" class="btn btn-sm btn-success">
                                                <i class="fas fa-money-bill"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">Không tìm thấy dữ liệu ghi danh nào</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($enrollments->hasPages())
            <div class="card-footer">
                {{ $enrollments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection 
 