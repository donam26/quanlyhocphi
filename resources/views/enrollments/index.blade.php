@extends('layouts.app')

@section('page-title', 'Danh sách ghi danh')

@section('breadcrumb')
<li class="breadcrumb-item active">Ghi danh</li>
@endsection

@section('page-actions')
<a href="{{ route('enrollments.create') }}" class="btn btn-primary">
    <i class="fas fa-plus-circle me-1"></i> Thêm ghi danh mới
</a>
@endsection

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <form action="{{ route('enrollments.index') }}" method="GET" class="row align-items-center g-2" id="enrollmentSearchForm">
            <div class="col-md-4">
                <select id="student_search" name="search" class="form-control select2-ajax">
                    @if(request('search'))
                        <option value="{{ request('search') }}" selected>{{ request('search') }}</option>
                    @endif
                </select>
            </div>
            
            <div class="col-md-3">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Trạng thái --</option>
                    <option value="enrolled" {{ request('status') == 'enrolled' ? 'selected' : '' }}>Đã ghi danh</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <select name="payment_status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Trạng thái thanh toán --</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                    <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Thanh toán một phần</option>
                    <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Chưa thanh toán</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <a href="{{ route('enrollments.unpaid') }}" class="btn btn-warning w-100">
                    <i class="fas fa-exclamation-circle me-1"></i> Chưa thanh toán
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
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
                                    <div>{{ $enrollment->courseItem->name }}</div>
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
                                            <a href="{{ route('payments.create', ['enrollment_id' => $enrollment->id]) }}" class="btn btn-sm btn-success">
                                                <i class="fas fa-money-bill"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('enrollments.edit', $enrollment) }}" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-3">Không có dữ liệu ghi danh</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="card-footer">
                {{ $enrollments->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Cấu hình Select2 cho ô tìm kiếm học viên
    $('#student_search').select2({
        theme: 'bootstrap-5',
        placeholder: 'Tìm theo tên học viên...',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '{{ route("api.search.autocomplete") }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.id,
                            text: item.text
                        };
                    })
                };
            },
            cache: true
        }
    });

    // Auto-submit khi search select2 thay đổi
    $('#student_search').on('select2:select', function(e) {
        var studentId = e.params.data.id;
        // Thay đổi URL để truy vấn theo ID học viên
        window.location.href = '{{ route("enrollments.index") }}?student_id=' + studentId;
    });

    // Xóa tìm kiếm khi clear
    $('#student_search').on('select2:clear', function(e) {
        window.location.href = '{{ route("enrollments.index") }}';
    });
});
</script>
@endpush 
 