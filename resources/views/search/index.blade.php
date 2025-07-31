@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Tìm kiếm nâng cao</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Tìm kiếm</li>
    </ol>
    
    <!-- Form tìm kiếm -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-search me-1"></i>
            Nhập thông tin tìm kiếm
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('search') }}" id="search-form">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="term" class="form-label">Tìm kiếm theo tên hoặc số điện thoại học viên</label>
                            <div style="width: 100%">
                                <select id="student_search" name="term" class="form-control select2-ajax" style="width: 100%;">
                                    @if(isset($searchTerm))
                                        <option value="{{ $searchTerm }}" selected>{{ $searchTerm }}</option>
                                    @endif
                                </select>
                            </div>
                            @error('term')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i> Tìm kiếm
                            </button>
                            <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-users me-1"></i> Xem tất cả học viên
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Kết quả tìm kiếm -->
    @isset($studentsDetails)
        @if(count($studentsDetails) > 0)
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-check-circle me-1"></i>
                    Tìm thấy {{ $students->total() }} kết quả cho từ khóa "{{ $searchTerm }}"
                </div>
                <div class="card-body">
                    <div class="accordion" id="searchResults">
                        @foreach($studentsDetails as $index => $detail)
                            <div class="accordion-item border mb-3">
                                <h2 class="accordion-header" id="heading{{ $index }}">
                                    <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" 
                                        aria-expanded="{{ $index === 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $index }}">
                                        <div class="row w-100">
                                            <div class="col-md-4">
                                                <strong>{{ $detail['student']->full_name }}</strong>
                                                <span class="badge bg-secondary ms-2">{{ $detail['total_courses'] }} khóa học</span>
                                            </div>
                                            <div class="col-md-3">
                                                <i class="fas fa-phone-alt me-1"></i> {{ $detail['student']->phone }}
                                            </div>
                                            <div class="col-md-3">
                                                <i class="fas fa-envelope me-1"></i> {{ $detail['student']->email ?? 'Không có' }}
                                            </div>
                                            <div class="col-md-2">
                                                @if($detail['remaining'] <= 0)
                                                    <span class="badge bg-success">Đã thanh toán đủ</span>
                                                @else
                                                    <span class="badge bg-danger">Còn nợ: {{ number_format($detail['remaining'], 0, ',', '.') }} đ</span>
                                                @endif
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapse{{ $index }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" 
                                    aria-labelledby="heading{{ $index }}" data-bs-parent="#searchResults">
                                    <div class="accordion-body">
                                        <!-- Thông tin học viên -->
                                        <div class="row mb-4">
                                            <div class="col-md-8">
                                                <div class="card border-0">
                                                    <div class="card-body bg-light">
                                                        <h5 class="card-title">Thông tin học viên</h5>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <ul class="list-group list-group-flush">
                                                                    <li class="list-group-item bg-transparent">
                                                                        <span class="fw-bold">Họ và tên:</span> {{ $detail['student']->full_name }}
                                                                    </li>
                                                                    <li class="list-group-item bg-transparent">
                                                                        <span class="fw-bold">Số điện thoại:</span> {{ $detail['student']->phone }}
                                                                    </li>
                                                                    <li class="list-group-item bg-transparent">
                                                                        <span class="fw-bold">Email:</span> {{ $detail['student']->email ?? 'Không có' }}
                                                                    </li>
                                                                    <li class="list-group-item bg-transparent">
                                                                        <span class="fw-bold">Địa chỉ:</span> {{ $detail['student']->address ?? 'Không có' }}
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <ul class="list-group list-group-flush">
                                                                    <li class="list-group-item bg-transparent">
                                                                        <span class="fw-bold">Giới tính:</span> 
                                                                        @if($detail['student']->gender == 'male')
                                                                            Nam
                                                                        @elseif($detail['student']->gender == 'female')
                                                                            Nữ
                                                                        @else
                                                                            Khác
                                                                        @endif
                                                                    </li>
                                                                    <li class="list-group-item bg-transparent">
                                                                        <span class="fw-bold">Ngày sinh:</span> 
                                                                        {{ $detail['student']->date_of_birth ? $detail['student']->date_of_birth->format('d/m/Y') : 'Không có' }}
                                                                    </li>
                                                                    <li class="list-group-item bg-transparent">
                                                                        <span class="fw-bold">Nơi công tác:</span> {{ $detail['student']->current_workplace ?? 'Không có' }}
                                                                    </li>
                                                                    <li class="list-group-item bg-transparent">
                                                                        <span class="fw-bold">Kinh nghiệm (năm):</span> {{ $detail['student']->accounting_experience_years ?? 'Không có' }}
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="card h-100">
                                                    <div class="card-header bg-primary text-white">
                                                        <i class="fas fa-chart-pie me-1"></i> Thông tin tài chính
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="row mb-2">
                                                            <div class="col-7">Tổng học phí:</div>
                                                            <div class="col-5 text-end">{{ number_format($detail['total_fee'], 0, ',', '.') }} đ</div>
                                                        </div>
                                                        <div class="row mb-2">
                                                            <div class="col-7">Đã thanh toán:</div>
                                                            <div class="col-5 text-end">{{ number_format($detail['total_paid'], 0, ',', '.') }} đ</div>
                                                        </div>
                                                        <div class="row mb-3">
                                                            <div class="col-7">Còn thiếu:</div>
                                                            <div class="col-5 text-end">{{ number_format($detail['remaining'], 0, ',', '.') }} đ</div>
                                                        </div>
                                                        
                                                        <!-- Thanh tiến trình -->
                                                        @php
                                                            $paymentPercent = $detail['total_fee'] > 0 ? 
                                                                round(($detail['total_paid'] / $detail['total_fee']) * 100) : 0;
                                                        @endphp
                                                        <div class="progress mb-2">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                style="width: {{ $paymentPercent }}%;" 
                                                                aria-valuenow="{{ $paymentPercent }}" 
                                                                aria-valuemin="0" aria-valuemax="100">
                                                                {{ $paymentPercent }}%
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="d-grid gap-2 mt-3">
                                                            <a href="{{ route('search.student-history', $detail['student']->id) }}" class="btn btn-info">
                                                                <i class="fas fa-history me-1"></i> Xem lịch sử chi tiết
                                                            </a>
                                                            <a href="{{ route('students.show', $detail['student']->id) }}" class="btn btn-secondary">
                                                                <i class="fas fa-user me-1"></i> Chi tiết học viên
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Danh sách khóa học đã ghi danh -->
                                        @if(count($detail['enrollments']) > 0)
                                            <div class="table-responsive mb-4">
                                                <h5>
                                                    <i class="fas fa-graduation-cap me-1"></i>
                                                    Khóa học đã ghi danh ({{ count($detail['enrollments']) }})
                                                </h5>
                                                <table class="table table-bordered table-striped table-hover">
                                                    <thead class="table-primary">
                                                        <tr>
                                                            <th>Khóa học</th>
                                                            <th>Ngày ghi danh</th>
                                                            <th>Học phí</th>
                                                            <th>Đã nộp</th>
                                                            <th>Còn thiếu</th>
                                                            <th>Trạng thái</th>
                                                            <th>Thanh toán</th>
                                                            <th>Thao tác</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($detail['enrollments'] as $enrollment)
                                                            <tr>
                                                                <td>{{ $enrollment['course_item']->name }}</td>
                                                                <td>{{ $enrollment['enrollment_date'] }}</td>
                                                                <td class="text-end">{{ number_format($enrollment['final_fee'], 0, ',', '.') }} đ</td>
                                                                <td class="text-end">{{ number_format($enrollment['total_paid'], 0, ',', '.') }} đ</td>
                                                                <td class="text-end">{{ number_format($enrollment['remaining_amount'], 0, ',', '.') }} đ</td>
                                                                <td>
                                                                    @if($enrollment['status'] == 'Đang học')
                                                                        <span class="badge bg-success">Đang học</span>
                                                                    @elseif($enrollment['status'] == 'Đã hoàn thành')
                                                                        <span class="badge bg-primary">Đã hoàn thành</span>
                                                                    @elseif($enrollment['status'] == 'Tạm dừng')
                                                                        <span class="badge bg-warning text-dark">Tạm dừng</span>
                                                                    @else
                                                                        <span class="badge bg-secondary">{{ $enrollment['status'] }}</span>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if($enrollment['payment_status'] == 'Đã thanh toán đủ')
                                                                        <span class="badge bg-success">Đã thanh toán đủ</span>
                                                                    @else
                                                                        <span class="badge bg-danger">Còn thiếu</span>
                                                                    @endif
                                                                    <div class="small mt-1">{{ $enrollment['payment_method'] }}</div>
                                                                </td>
                                                                <td>
                                                                    <div class="btn-group btn-group-sm" role="group">
                                                                        <a href="{{ route('payments.quick', $enrollment['id']) }}" class="btn btn-primary" title="Thanh toán nhanh">
                                                                            <i class="fas fa-money-bill"></i>
                                                                        </a>
                                                                        <a href="{{ route('enrollments.show', $enrollment['id']) }}" class="btn btn-info" title="Xem chi tiết ghi danh">
                                                                            <i class="fas fa-eye"></i>
                                                                        </a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                        
                                        <!-- Danh sách chờ -->
                                        @if(count($detail['waiting_lists']) > 0)
                                            <div class="table-responsive">
                                                <h5>
                                                    <i class="fas fa-clock me-1"></i>
                                                    Danh sách chờ ({{ count($detail['waiting_lists']) }})
                                                </h5>
                                                <table class="table table-bordered table-striped table-hover">
                                                    <thead class="table-warning">
                                                        <tr>
                                                            <th>Khóa học</th>
                                                            <th>Ngày đăng ký</th>
                                                            <th>Ghi chú</th>
                                                            <th>Thao tác</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($detail['waiting_lists'] as $waitingList)
                                                            <tr>
                                                                <td>{{ $waitingList['course_item']->name }}</td>
                                                                <td>{{ $waitingList['registration_date'] }}</td>
                                                                <td>{{ $waitingList['notes'] ?? 'Không có ghi chú' }}</td>
                                                                <td>
                                                                    <div class="btn-group btn-group-sm" role="group">
                                                                        <a href="{{ route('enrollments.confirm-waiting', $waitingList['id']) }}" 
                                                                           class="btn btn-success" title="Chuyển sang ghi danh">
                                                                            <i class="fas fa-user-plus"></i>
                                                                        </a>
                                                                        <a href="{{ route('enrollments.show', $waitingList['id']) }}" 
                                                                           class="btn btn-info" title="Xem chi tiết danh sách chờ">
                                                                            <i class="fas fa-eye"></i>
                                                                        </a>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $students->links() }}
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-warning" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Không tìm thấy kết quả nào cho từ khóa "{{ $searchTerm }}". Vui lòng thử lại với từ khóa khác.
            </div>
        @endif
    @endisset
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Cấu hình Select2 cho ô tìm kiếm học viên
    $('#student_search').select2({
        theme: 'bootstrap-5',
        placeholder: 'Nhập tên hoặc số điện thoại học viên...',
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
        // Thêm student_id vào form hidden và submit
        var hiddenInput = $('<input>').attr({
            type: 'hidden',
            name: 'student_id',
            value: studentId
        });
        $('#search-form').append(hiddenInput);
        $('#search-form').submit();
    });

    // Xóa tìm kiếm khi clear
    $('#student_search').on('select2:clear', function(e) {
        $('#search-form').find('input[name="term"]').val('');
        $('#search-form').find('input[name="student_id"]').remove();
        $('#search-form').submit();
    });
});
</script>
@endpush
