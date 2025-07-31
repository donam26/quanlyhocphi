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
            <div class="col-md-3">
                <select id="student_search" name="search" class="form-control select2-ajax" placeholder="Tìm học viên...">
                    @if(request('search'))
                        <option value="{{ request('search') }}" selected>{{ request('search') }}</option>
                    @endif
                </select>
            </div>
            
            <div class="col-md-2">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Trạng thái --</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Chờ xử lý</option>
                    <option value="waiting" {{ request('status') == 'waiting' ? 'selected' : '' }}>Danh sách chờ</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Đã xác nhận</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang học</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Đã hoàn thành</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <select name="payment_status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Thanh toán --</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                    <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Thanh toán một phần</option>
                    <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Chưa thanh toán</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <select name="course_item_id" class="form-select">
                    <option value="">-- Tất cả khóa học --</option>
                    @foreach(\App\Models\CourseItem::where('is_leaf', true)->where('active', true)->get() as $item)
                        <option value="{{ $item->id }}" {{ request('course_item_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if(request('status') == 'waiting')
            <div class="col-md-1">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="needs_contact" value="1" id="needsContactCheck" {{ request('needs_contact') ? 'checked' : '' }} onchange="this.form.submit()">
                    <label class="form-check-label" for="needsContactCheck">
                        Cần liên hệ
                    </label>
                </div>
            </div>
            @endif
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Tìm kiếm
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="mb-0">
                    @if(request('status') == 'waiting')
                        Danh sách chờ
                    @else
                        Danh sách ghi danh
                    @endif
                </h5>
            </div>
            <div class="col-auto">
                <span class="badge bg-info">{{ $enrollments->total() }} kết quả</span>
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
                            <th>Khóa học</th>
                            @if(request('status') == 'waiting')
                                <th>Ngày yêu cầu</th>
                            @else
                            <th>Ngày ghi danh</th>
                            @endif
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
                                <td>
                                    @if($enrollment->status == 'waiting' && $enrollment->request_date)
                                        {{ $enrollment->request_date->format('d/m/Y H:i') }}
                                    @else
                                        {{ $enrollment->enrollment_date->format('d/m/Y') }}
                                    @endif
                                </td>
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
                                    @if($enrollment->status == 'pending')
                                        <span class="badge bg-secondary">Chờ xử lý</span>
                                    @elseif($enrollment->status == 'waiting')
                                        <span class="badge bg-warning text-dark">Danh sách chờ</span>
                                    @elseif($enrollment->status == 'confirmed')
                                        <span class="badge bg-info">Đã xác nhận</span>
                                    @elseif($enrollment->status == 'active')
                                        <span class="badge bg-success">Đang học</span>
                                    @elseif($enrollment->status == 'completed')
                                        <span class="badge bg-primary">Đã hoàn thành</span>
                                    @elseif($enrollment->status == 'cancelled')
                                        <span class="badge bg-danger">Đã hủy</span>
                                    @else
                                        <span class="badge bg-dark">{{ $enrollment->status }}</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('enrollments.show', $enrollment->id) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('enrollments.edit', $enrollment->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($enrollment->status == 'waiting')
                                            <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#confirmModal-{{ $enrollment->id }}">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        @endif
                                    </div>
                                    
                                    @if($enrollment->status == 'waiting')
                                    <!-- Modal Xác nhận từ danh sách chờ -->
                                    <div class="modal fade" id="confirmModal-{{ $enrollment->id }}" tabindex="-1" aria-labelledby="confirmModalLabel-{{ $enrollment->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="confirmModalLabel-{{ $enrollment->id }}">Xác nhận ghi danh</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Bạn có chắc chắn muốn xác nhận ghi danh cho học viên <strong>{{ $enrollment->student->full_name }}</strong> vào khóa học <strong>{{ $enrollment->courseItem->name }}</strong>?</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                    <form action="{{ route('enrollments.confirm-waiting', $enrollment->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-success">Xác nhận ghi danh</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
    </div>
            <div class="card-footer">
        <div class="d-flex justify-content-center">
            {{ $enrollments->appends(request()->query())->links() }}
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
 