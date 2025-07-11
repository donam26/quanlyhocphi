@extends('layouts.app')

@section('page-title', 'Quản lý danh sách chờ')

@section('breadcrumb')
<li class="breadcrumb-item active">Danh sách chờ</li>
@endsection

@section('content')
<!-- Filter & Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('waiting-lists.index') }}">
            <div class="row">
                <div class="col-md-3">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Tìm theo tên, SĐT..." 
                               value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="course_id" class="form-select">
                        <option value="">Tất cả khóa học</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="interest_level" class="form-select">
                        <option value="">Tất cả mức độ</option>
                        <option value="high" {{ request('interest_level') == 'high' ? 'selected' : '' }}>Quan tâm cao</option>
                        <option value="medium" {{ request('interest_level') == 'medium' ? 'selected' : '' }}>Quan tâm vừa</option>
                        <option value="low" {{ request('interest_level') == 'low' ? 'selected' : '' }}>Quan tâm thấp</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="waiting" {{ request('status') == 'waiting' ? 'selected' : '' }}>Đang chờ</option>
                        <option value="contacted" {{ request('status') == 'contacted' ? 'selected' : '' }}>Đã liên hệ</option>
                        <option value="enrolled" {{ request('status') == 'enrolled' ? 'selected' : '' }}>Đã ghi danh</option>
                        <option value="not_interested" {{ request('status') == 'not_interested' ? 'selected' : '' }}>Không quan tâm</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="needs_contact" value="1" 
                                   {{ request('needs_contact') ? 'checked' : '' }}>
                            <label class="form-check-label small">Cần liên hệ</label>
                        </div>
                        <a href="{{ route('waiting-lists.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Thêm mới
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Quick Stats -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $waitingLists->total() }}</p>
                    <p class="stats-label">Tổng danh sách chờ</p>
                </div>
                <i class="fas fa-clock fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $waitingLists->count() }}</p>
                    <p class="stats-label">Quan tâm cao</p>
                </div>
                <i class="fas fa-star fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card danger">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $waitingLists->count() }}</p>
                    <p class="stats-label">Cần liên hệ</p>
                </div>
                <i class="fas fa-phone fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $waitingLists->count() }}</p>
                    <p class="stats-label">Đã liên hệ</p>
                </div>
                <i class="fas fa-check fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
</div>

<!-- Waiting Lists -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-list-alt me-2"></i>
            Danh sách chờ
            <span class="badge bg-primary ms-2">{{ $waitingLists->total() }} người</span>
        </h5>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-success" onclick="exportWaitingList()">
                <i class="fas fa-file-excel me-1"></i>Xuất Excel
            </button>
            <button class="btn btn-sm btn-outline-info" onclick="bulkContact()">
                <i class="fas fa-phone me-1"></i>Liên hệ hàng loạt
            </button>
            <button class="btn btn-sm btn-outline-warning" onclick="bulkEnroll()">
                <i class="fas fa-user-plus me-1"></i>Ghi danh hàng loạt
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        @if($waitingLists->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="3%">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th width="25%">Học viên</th>
                            <th width="20%">Khóa học quan tâm</th>
                            <th width="12%">Mức độ quan tâm</th>
                            <th width="12%">Ngày thêm</th>
                            <th width="12%">Liên hệ cuối</th>
                            <th width="10%">Trạng thái</th>
                            <th width="6%">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($waitingLists as $waiting)
                        <tr class="waiting-row">
                            <td>
                                <input type="checkbox" class="form-check-input waiting-checkbox" 
                                       value="{{ $waiting->id }}">
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-{{ $waiting->interest_level == 'high' ? 'danger' : ($waiting->interest_level == 'medium' ? 'warning' : 'secondary') }} text-white rounded-circle d-flex align-items-center justify-content-center me-3">
                                        {{ strtoupper(substr($waiting->student->full_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="fw-medium">{{ $waiting->student->full_name }}</div>
                                        <small class="text-muted">
                                            <i class="fas fa-phone me-1"></i>{{ $waiting->student->phone }}
                                        </small>
                                        @if($waiting->student->email)
                                            <br><small class="text-muted">
                                                <i class="fas fa-envelope me-1"></i>{{ $waiting->student->email }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div class="fw-medium">{{ $waiting->course->name }}</div>
                                    <small class="text-muted">{{ $waiting->course->major->name }}</small>
                                    @if($waiting->course->fee)
                                        <br><small class="text-success">
                                            {{ number_format($waiting->course->fee) }} VNĐ
                                        </small>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($waiting->interest_level == 'high')
                                    <span class="badge bg-danger">
                                        <i class="fas fa-star me-1"></i>Cao
                                    </span>
                                @elseif($waiting->interest_level == 'medium')
                                    <span class="badge bg-warning">
                                        <i class="fas fa-star-half-alt me-1"></i>Vừa
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="far fa-star me-1"></i>Thấp
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div>{{ $waiting->added_date->format('d/m/Y') }}</div>
                                <small class="text-muted">{{ $waiting->added_date->diffForHumans() }}</small>
                            </td>
                            <td>
                                @if($waiting->last_contact_date)
                                    <div>{{ $waiting->last_contact_date->format('d/m/Y') }}</div>
                                    <small class="text-muted">{{ $waiting->last_contact_date->diffForHumans() }}</small>
                                @else
                                    <span class="text-danger">
                                        <i class="fas fa-exclamation-triangle me-1"></i>Chưa liên hệ
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($waiting->status == 'waiting')
                                    <span class="badge bg-warning">Đang chờ</span>
                                @elseif($waiting->status == 'contacted')
                                    <span class="badge bg-info">Đã liên hệ</span>
                                @elseif($waiting->status == 'enrolled')
                                    <span class="badge bg-success">Đã ghi danh</span>
                                @else
                                    <span class="badge bg-secondary">Không quan tâm</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-success" 
                                            onclick="markContacted({{ $waiting->id }})"
                                            title="Đánh dấu đã liên hệ">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" 
                                            onclick="enrollStudent({{ $waiting->id }})"
                                            title="Ghi danh">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info" 
                                            onclick="viewDetails({{ $waiting->id }})"
                                            title="Chi tiết">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center p-3">
                <div class="small text-muted">
                    Hiển thị {{ $waitingLists->firstItem() }}-{{ $waitingLists->lastItem() }} 
                    của {{ $waitingLists->total() }} kết quả
                </div>
                {{ $waitingLists->withQueryString()->links() }}
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-list-alt fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Chưa có danh sách chờ</h5>
                <p class="text-muted">Thêm học viên vào danh sách chờ để theo dõi</p>
                <a href="{{ route('waiting-lists.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Thêm học viên vào danh sách chờ
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@section('styles')
<style>
.stats-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: none;
    transition: transform 0.3s ease;
    height: 100%;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}

.stats-card.warning {
    border-left: 4px solid #ffc107;
}

.stats-card.danger {
    border-left: 4px solid #dc3545;
}

.stats-card.success {
    border-left: 4px solid #28a745;
}

.stats-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
    color: #2d3748;
}

.stats-label {
    font-size: 0.875rem;
    color: #718096;
    margin-bottom: 0;
    font-weight: 500;
}

.avatar-sm {
    width: 32px;
    height: 32px;
    font-size: 0.75rem;
}

.waiting-row:hover {
    background-color: #f8f9fa;
}
</style>
@endsection

@section('scripts')
<script>
let selectedWaiting = [];

function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.waiting-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateSelectedWaiting();
}

function updateSelectedWaiting() {
    selectedWaiting = Array.from(document.querySelectorAll('.waiting-checkbox:checked'))
                            .map(cb => cb.value);
}

function markContacted(waitingId) {
    if (confirm('Đánh dấu đã liên hệ học viên này?')) {
        fetch(`/waiting-lists/${waitingId}/mark-contacted`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Có lỗi xảy ra!');
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra!');
        });
    }
}

function enrollStudent(waitingId) {
    if (confirm('Chuyển học viên này sang ghi danh?')) {
        window.location.href = `/enrollments/waiting-list/${waitingId}`;
    }
}

function viewDetails(waitingId) {
    window.location.href = `/waiting-lists/${waitingId}`;
}

function exportWaitingList() {
    window.location.href = '{{ route("waiting-lists.index") }}?export=excel';
}

function bulkContact() {
    updateSelectedWaiting();
    if (selectedWaiting.length === 0) {
        alert('Vui lòng chọn ít nhất một học viên!');
        return;
    }
    
    if (confirm(`Đánh dấu đã liên hệ ${selectedWaiting.length} học viên đã chọn?`)) {
        // Logic liên hệ hàng loạt
        alert('Chức năng liên hệ hàng loạt đang được phát triển');
    }
}

function bulkEnroll() {
    updateSelectedWaiting();
    if (selectedWaiting.length === 0) {
        alert('Vui lòng chọn ít nhất một học viên!');
        return;
    }
    
    alert('Chức năng ghi danh hàng loạt đang được phát triển');
}

// Auto-submit form when filters change
document.addEventListener('change', function(e) {
    if (e.target.matches('select[name="course_id"], select[name="interest_level"], select[name="status"], input[name="needs_contact"]')) {
        e.target.closest('form').submit();
    }
    
    if (e.target.classList.contains('waiting-checkbox')) {
        updateSelectedWaiting();
    }
});
</script>
@endsection 