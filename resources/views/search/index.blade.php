@extends('layouts.app')

@section('page-title', 'Tìm kiếm học viên')

@section('breadcrumb')
<li class="breadcrumb-item active">Tìm kiếm</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-search me-2"></i>
            Tìm kiếm học viên theo SĐT hoặc họ tên
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('search') }}" method="POST" id="searchForm">
            @csrf
            <div class="row">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" 
                               class="form-control form-control-lg" 
                               name="search_term" 
                               id="searchInput"
                               placeholder="Nhập số điện thoại hoặc họ tên học viên..."
                               value="{{ request('search_term') }}"
                               autocomplete="off">
                        <button class="btn btn-primary btn-lg" type="submit">
                            <i class="fas fa-search me-2"></i>Tìm kiếm
                        </button>
                    </div>
                    <div id="searchSuggestions" class="position-absolute w-100 mt-1" style="z-index: 1000;"></div>
                </div>
                <div class="col-md-4">
                    <button type="button" class="btn btn-outline-secondary btn-lg w-100" data-bs-toggle="collapse" data-bs-target="#advancedSearch">
                        <i class="fas fa-sliders-h me-2"></i>Tìm kiếm nâng cao
                    </button>
                </div>
            </div>

            <!-- Advanced Search Options -->
            <div class="collapse mt-4" id="advancedSearch">
                <div class="border rounded p-3 bg-light">
                    <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Bộ lọc nâng cao</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Ngành học</label>
                            <select name="major_id" class="form-select">
                                <option value="">Tất cả ngành</option>
                                @foreach($majors as $major)
                                    <option value="{{ $major->id }}" {{ request('major_id') == $major->id ? 'selected' : '' }}>
                                        {{ $major->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Trạng thái học</label>
                            <select name="enrollment_status" class="form-select">
                                <option value="">Tất cả trạng thái</option>
                                <option value="enrolled" {{ request('enrollment_status') == 'enrolled' ? 'selected' : '' }}>Đang học</option>
                                <option value="completed" {{ request('enrollment_status') == 'completed' ? 'selected' : '' }}>Đã hoàn thành</option>
                                <option value="dropped" {{ request('enrollment_status') == 'dropped' ? 'selected' : '' }}>Đã nghỉ</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Trạng thái thanh toán</label>
                            <select name="payment_status" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                                <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Thanh toán một phần</option>
                                <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Chưa thanh toán</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Search Results -->
@if(isset($searchResults))
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>
                Kết quả tìm kiếm
                <span class="badge bg-primary ms-2">{{ $searchResults->count() }} kết quả</span>
            </h5>
            @if($searchResults->count() > 0)
                <div class="btn-group">
                    <button class="btn btn-sm btn-outline-success" onclick="exportResults()">
                        <i class="fas fa-file-excel me-1"></i>Xuất Excel
                    </button>
                    <button class="btn btn-sm btn-outline-primary" onclick="printResults()">
                        <i class="fas fa-print me-1"></i>In danh sách
                    </button>
                </div>
            @endif
        </div>
        <div class="card-body">
            @if($searchResults->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Học viên</th>
                                <th>Liên hệ</th>
                                <th>Khóa học hiện tại</th>
                                <th>Trạng thái học</th>
                                <th>Thanh toán</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($searchResults as $student)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                                {{ substr($student->full_name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ $student->full_name }}</div>
                                                <small class="text-muted">
                                                    <i class="fas fa-birthday-cake me-1"></i>
                                                    {{ $student->birth_date ? $student->birth_date->format('d/m/Y') : 'N/A' }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fas fa-phone me-1"></i>
                                            <strong>{{ $student->phone }}</strong>
                                        </div>
                                        @if($student->email)
                                            <div class="text-muted small">
                                                <i class="fas fa-envelope me-1"></i>
                                                {{ $student->email }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($student->activeEnrollments->count() > 0)
                                            @foreach($student->activeEnrollments as $enrollment)
                                                <div class="mb-1">
                                                    <span class="fw-medium">{{ $enrollment->courseClass->course->name }}</span>
                                                    <br>
                                                    <small class="text-muted">{{ $enrollment->courseClass->name }}</small>
                                                </div>
                                            @endforeach
                                        @else
                                            <span class="text-muted">Không có khóa học nào</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($student->activeEnrollments->count() > 0)
                                            @foreach($student->activeEnrollments as $enrollment)
                                                @if($enrollment->status === 'enrolled')
                                                    <span class="badge bg-success">Đang học</span>
                                                @elseif($enrollment->status === 'completed')
                                                    <span class="badge bg-info">Hoàn thành</span>
                                                @elseif($enrollment->status === 'dropped')
                                                    <span class="badge bg-warning">Đã nghỉ</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $enrollment->status }}</span>
                                                @endif
                                                <br>
                                            @endforeach
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($student->activeEnrollments->count() > 0)
                                            @foreach($student->activeEnrollments as $enrollment)
                                                @if($enrollment->payment_status === 'paid')
                                                    <span class="badge bg-success">Đã thanh toán</span>
                                                @elseif($enrollment->payment_status === 'partial')
                                                    <span class="badge bg-warning">Một phần</span>
                                                @else
                                                    <span class="badge bg-danger">Chưa thanh toán</span>
                                                @endif
                                                <br>
                                                <small class="text-muted">
                                                    {{ number_format($enrollment->getRemainingAmount()) }}đ
                                                </small>
                                                <br>
                                            @endforeach
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('search.student-history', $student) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Xem lịch sử">
                                                <i class="fas fa-history"></i>
                                            </a>
                                            <a href="{{ route('students.show', $student) }}" 
                                               class="btn btn-sm btn-outline-info" 
                                               title="Chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('enrollments.create', ['student_id' => $student->id]) }}" 
                                               class="btn btn-sm btn-outline-success" 
                                               title="Ghi danh mới">
                                                <i class="fas fa-user-plus"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Không tìm thấy kết quả</h5>
                    <p class="text-muted">
                        Không có học viên nào phù hợp với từ khóa 
                        <strong>"{{ request('search_term') }}"</strong>
                    </p>
                    <a href="{{ route('students.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Thêm học viên mới
                    </a>
                </div>
            @endif
        </div>
    </div>
@endif

<!-- Quick Stats -->
@if(isset($searchResults) && $searchResults->count() > 0)
    <div class="row">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stats-number">{{ $searchResults->count() }}</p>
                        <p class="stats-label">Tổng học viên</p>
                    </div>
                    <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stats-number">{{ $searchResults->filter(function($s) { return $s->activeEnrollments->count() > 0; })->count() }}</p>
                        <p class="stats-label">Đang học</p>
                    </div>
                    <i class="fas fa-user-check fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stats-number">{{ $searchResults->filter(function($s) { return $s->activeEnrollments->where('payment_status', '!=', 'paid')->count() > 0; })->count() }}</p>
                        <p class="stats-label">Chưa thanh toán đủ</p>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stats-number">{{ number_format($searchResults->flatMap(function($s) { return $s->activeEnrollments; })->sum(function($e) { return $e->getRemainingAmount(); }), 0, ',', '.') }}</p>
                        <p class="stats-label">Tổng nợ (VNĐ)</p>
                    </div>
                    <i class="fas fa-money-bill-wave fa-2x opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
@endif
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Auto-complete search
    $('#searchInput').on('input', function() {
        const searchTerm = $(this).val();
        
        if (searchTerm.length >= 2) {
            $.get('{{ route("api.search.autocomplete") }}', {
                term: searchTerm
            }, function(data) {
                displaySuggestions(data);
            });
        } else {
            $('#searchSuggestions').empty();
        }
    });

    // Handle suggestion click
    $(document).on('click', '.suggestion-item', function() {
        const studentName = $(this).data('name');
        const studentPhone = $(this).data('phone');
        
        $('#searchInput').val(studentName + ' (' + studentPhone + ')');
        $('#searchSuggestions').empty();
        $('#searchForm').submit();
    });

    // Hide suggestions when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#searchInput, #searchSuggestions').length) {
            $('#searchSuggestions').empty();
        }
    });
});

function displaySuggestions(data) {
    let html = '';
    
    if (data.length > 0) {
        html = '<div class="list-group shadow">';
        data.forEach(function(item) {
            html += `
                <a href="#" class="list-group-item list-group-item-action suggestion-item" 
                   data-name="${item.full_name}" data-phone="${item.phone}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${item.full_name}</strong>
                            <div class="text-muted small">${item.phone}</div>
                        </div>
                        <div class="text-end">
                            <small class="text-muted">
                                ${item.enrollments_count} khóa học
                            </small>
                        </div>
                    </div>
                </a>
            `;
        });
        html += '</div>';
    }
    
    $('#searchSuggestions').html(html);
}

function exportResults() {
    alert('Chức năng xuất Excel đang được phát triển');
}

function printResults() {
    window.print();
}

// Submit form on enter
$('#searchInput').on('keypress', function(e) {
    if (e.which === 13) {
        $('#searchForm').submit();
    }
});
</script>
@endsection 
 