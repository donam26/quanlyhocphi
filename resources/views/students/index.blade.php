@extends('layouts.app')

@section('page-title', 'Danh sách học viên')

@section('breadcrumb')
<li class="breadcrumb-item active">Học viên</li>
@endsection

@section('content')
<!-- Filter & Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('students.index') }}" id="studentSearchForm">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <select id="student_search" name="search" class="form-control select2-ajax" style="width: 100%;">
                            @if(request('search'))
                                <option value="{{ request('search') }}" selected>{{ request('search') }}</option>
                            @endif
                        </select>
                    </div>
                    @if(request('search'))
                    <small class="form-text text-muted mt-1">
                        <i class="fas fa-info-circle"></i> 
                        Kết quả tìm kiếm cho "{{ request('search') }}" - 
                        @if(preg_match('/^\d+$/', request('search')))
                            Đang tìm theo số điện thoại
                        @else
                            Đang tìm theo tên, SĐT hoặc CCCD
                        @endif
                    </small>
                    @endif
                </div>
                <div class="col-md-3">
                    <select name="course_item_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Tất cả khóa học</option>
                        @php
                            $courseItems = \App\Models\CourseItem::whereNull('parent_id')->get();
                        @endphp
                        @foreach($courseItems as $courseItem)
                            <option value="{{ $courseItem->id }}" {{ request('course_item_id') == $courseItem->id ? 'selected' : '' }}>
                                {{ $courseItem->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang học</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Hoàn thành</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Không hoạt động</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('students.create') }}" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-2"></i>Thêm mới
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Students List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-user-graduate me-2"></i>
            Danh sách học viên
            <span class="badge bg-primary ms-2">{{ $students->total() }} học viên</span>
        </h5>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-success" onclick="exportStudents()">
                <i class="fas fa-file-excel me-1"></i>Xuất Excel
            </button>
            <button class="btn btn-sm btn-outline-info" onclick="printStudents()">
                <i class="fas fa-print me-1"></i>In danh sách
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        @if($students->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="25%">Thông tin học viên</th>
                            <th width="20%">Liên hệ</th>
                            <th width="20%">Khóa học</th>
                            <th width="15%">Trạng thái</th>
                            <th width="15%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                        <tr>
                            <td>{{ $students->firstItem() + $index }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div>
                                        <div class="fw-medium">{{ $student->full_name }}</div>
                                        <small class="text-muted">
                                            <i class="fas fa-birthday-cake me-1"></i>
                                            {{ $student->birth_date ? $student->birth_date->format('d/m/Y') : 'N/A' }}
                                        </small>
                                        @if($student->id_number)
                                            <br><small class="text-muted">CCCD: {{ $student->id_number }}</small>
                                        @endif
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
                                @if($student->address)
                                    <div class="text-muted small">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        {{ Str::limit($student->address, 30) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($student->enrollments->count() > 0)
                                    @foreach($student->enrollments->take(2) as $enrollment)
                                        <div class="mb-1">
                                            <span class="fw-medium">{{ $enrollment->courseItem ? $enrollment->courseItem->name : 'N/A' }}</span>
                                            <br>
                                            <small class="text-muted">{{ $enrollment->enrollment_date ? $enrollment->enrollment_date->format('d/m/Y') : 'N/A' }}</small>
                                        </div>
                                    @endforeach
                                    @if($student->enrollments->count() > 2)
                                        <small class="text-muted">+{{ $student->enrollments->count() - 2 }} khóa khác</small>
                                    @endif
                                @else
                                    <span class="text-muted">Chưa có khóa học</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $activeEnrollments = $student->enrollments->where('status', 'enrolled');
                                    $completedEnrollments = $student->enrollments->where('status', 'completed');
                                @endphp
                                
                                @if($activeEnrollments->count() > 0)
                                    <span class="badge bg-success">Đang học ({{ $activeEnrollments->count() }})</span>
                                @elseif($completedEnrollments->count() > 0)
                                    <span class="badge bg-info">Hoàn thành ({{ $completedEnrollments->count() }})</span>
                                @else
                                    <span class="badge bg-secondary">Chưa học</span>
                                @endif

                                @php
                                    $unpaidCount = $student->enrollments->filter(function($e) {
                                        return $e->payment_status !== 'paid';
                                    })->count();
                                @endphp
                                
                                @if($unpaidCount > 0)
                                    <br><span class="badge bg-warning mt-1">Chưa TT: {{ $unpaidCount }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('students.show', $student) }}" 
                                       class="btn btn-sm btn-outline-primary" 
                                       title="Chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('students.edit', $student) }}" 
                                       class="btn btn-sm btn-outline-warning" 
                                       title="Chỉnh sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('enrollments.create', ['student_id' => $student->id]) }}" 
                                       class="btn btn-sm btn-outline-success" 
                                       title="Ghi danh">
                                        <i class="fas fa-user-plus"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="card-footer">
                {{ $students->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Chưa có học viên nào</h5>
                <p class="text-muted">Hãy thêm học viên đầu tiên để bắt đầu</p>
                <a href="{{ route('students.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Thêm học viên mới
                </a>
            </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Cấu hình Select2 cho ô tìm kiếm học viên với AJAX
    $('#student_search').select2({
        theme: 'bootstrap-5',
        placeholder: 'Tìm theo tên, SĐT, CCCD...',
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
                            id: item.id,  // Sử dụng ID của học viên
                            text: item.text
                        };
                    })
                };
            },
            cache: true
        }
    });

    // Auto-submit form khi select thay đổi
    $('select[name="course_item_id"], select[name="status"]').change(function() {
        $(this).closest('form').submit();
    });

    // Auto-submit khi search select2 thay đổi
    $('#student_search').on('select2:select', function(e) {
        var studentId = e.params.data.id;
        // Thay đổi URL để truy vấn theo ID học viên
        window.location.href = '{{ route("students.index") }}?student_id=' + studentId;
    });

    // Xóa tìm kiếm khi clear
    $('#student_search').on('select2:clear', function(e) {
        window.location.href = '{{ route("students.index") }}';
    });
});

function exportStudents() {
    alert('Chức năng xuất Excel đang được phát triển');
}

function printStudents() {
    window.print();
}
</script>
@endpush 
 