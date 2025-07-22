@extends('layouts.app')

@section('page-title', 'Danh sách chờ - ' . $courseItem->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('waiting-lists.index') }}">Danh sách chờ</a></li>
    @foreach($breadcrumbs as $index => $item)
        @if($loop->last)
            <li class="breadcrumb-item active">{{ $item['name'] }}</li>
        @else
            <li class="breadcrumb-item"><a href="{{ route('course-items.waiting-lists', $item['id']) }}">{{ $item['name'] }}</a></li>
        @endif
    @endforeach
@endsection

@section('content')
<style>
    .card {
        margin-bottom: 1.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border-radius: 0.5rem;
        border: none;
    }
    .card-header {
        background-color: #fff;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 1rem 1.25rem;
    }
    .card-header h4 {
        margin-bottom: 0;
        font-weight: 600;
    }
    .table th {
        background-color: #f8f9fa;
        font-weight: 600;
    }
    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        padding: 1rem 1.5rem;
        font-weight: 500;
        border-bottom: 2px solid transparent;
    }
    .nav-tabs .nav-link.active {
        color: #007bff;
        border-bottom: 2px solid #007bff;
    }
    .nav-tabs .nav-link:hover {
        border-bottom: 2px solid #dee2e6;
    }
    .badge-count {
        background-color: #f8f9fa;
        color: #6c757d;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        margin-left: 0.5rem;
    }
    .stat-card {
        border-radius: 0.5rem;
        padding: 1rem;
        height: 100%;
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .stat-card .stat-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        opacity: 0.8;
    }
    .stat-card .stat-title {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 0.25rem;
    }
    .stat-card .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
    }
    .bg-blue-gradient {
        background: linear-gradient(45deg, #4e73df, #224abe);
        color: white;
    }
    .bg-red-gradient {
        background: linear-gradient(45deg, #e74a3b, #be2617);
        color: white;
    }
    .bg-yellow-gradient {
        background: linear-gradient(45deg, #f6c23e, #dda20a);
        color: white;
    }
    .bg-green-gradient {
        background: linear-gradient(45deg, #1cc88a, #169b6b);
        color: white;
    }
    .avatar {
        width: 40px;
        height: 40px;
        background-color: #4e73df;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
</style>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h4>{{ $courseItem->name }} - Danh sách chờ</h4>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('course-items.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại cây khóa học
            </a>
            <a href="{{ route('waiting-lists.create', ['course_item_id' => $courseItem->id]) }}" class="btn btn-primary ms-2">
                <i class="fas fa-plus"></i> Thêm học viên chờ
            </a>
        </div>
    </div>

    <!-- Thống kê -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-blue-gradient">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-title">Tổng học viên chờ</div>
                <div class="stat-value">{{ number_format($stats['total_waiting']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-red-gradient">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-title">Quan tâm cao</div>
                <div class="stat-value">{{ number_format($stats['high_interest']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-yellow-gradient">
                <div class="stat-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <div class="stat-title">Cần liên hệ</div>
                <div class="stat-value">{{ number_format($stats['needs_contact']) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-green-gradient">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-title">Đã ghi danh</div>
                <div class="stat-value">{{ number_format($stats['converted']) }}</div>
            </div>
        </div>
    </div>

    <!-- Thông tin khóa học -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <p><strong>Tên khóa:</strong> {{ $courseItem->name }}</p>
                </div>
                @if($courseItem->fee > 0)
                <div class="col-md-3">
                    <p><strong>Học phí:</strong> {{ number_format($courseItem->fee) }}đ</p>
                </div>
                @endif
                <div class="col-md-3">
                    <p>
                        <strong>Loại khóa:</strong> 
                        @if($courseItem->is_leaf)
                            <span class="badge bg-primary">Lớp học</span>
                        @else
                            <span class="badge bg-info">Nhóm khóa học</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-3">
                    <p>
                        <strong>Trạng thái:</strong> 
                        @if($courseItem->active)
                            <span class="badge bg-success">Đang mở</span>
                        @else
                            <span class="badge bg-secondary">Đã đóng</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4">
        @foreach($tabs as $key => $tab)
        <li class="nav-item">
            <a class="nav-link {{ $key == 'waiting' ? 'active' : '' }}" href="{{ $tab['url'] }}">
                {{ $tab['title'] }}
                <span class="badge-count">{{ $tab['count'] }}</span>
            </a>
        </li>
        @endforeach
    </ul>

    @if($childItems->count() > 0)
    <!-- Khóa con -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="m-0">Khóa học/lớp học con</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tên khóa học/lớp học</th>
                            <th class="text-center">Học viên chờ</th>
                            <th class="text-center">Trạng thái</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($childItems as $child)
                        <tr>
                            <td>{{ $child->name }}</td>
                            <td class="text-center">
                                @php
                                $childWaitingCount = \App\Models\WaitingList::where('course_item_id', $child->id)
                                                    ->where('status', 'waiting')
                                                    ->count();
                                @endphp
                                <span class="badge bg-warning">{{ $childWaitingCount }}</span>
                            </td>
                            <td class="text-center">
                                @if($child->active)
                                <span class="badge bg-success">Đang mở</span>
                                @else
                                <span class="badge bg-secondary">Đã đóng</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('course-items.waiting-lists', $child->id) }}" class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> Xem
                                </a>
                                <a href="{{ route('waiting-lists.create', ['course_item_id' => $child->id]) }}" class="btn btn-sm btn-success">
                                    <i class="fas fa-plus"></i> Thêm học viên
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="m-0">Danh sách học viên chờ</h5>
            <div>
                <form id="bulk-action-form" action="{{ route('enrollments.move-to-waiting') }}" method="POST" class="d-inline">
                    @csrf
                    <input type="hidden" id="selected-ids" name="selected_ids">
                    <button type="button" class="btn btn-sm btn-success" id="bulk-enroll-btn" disabled>
                        <i class="fas fa-user-plus me-1"></i>Ghi danh học viên đã chọn
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            @if($waitingLists->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th style="width: 30px;">
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th>Học viên</th>
                                <th>Khóa học</th>
                                <th>Mức độ quan tâm</th>
                                <th>Ngày thêm</th>
                                <th>Liên hệ cuối</th>
                                <th>Ghi chú</th>
                                <th style="width: 150px;">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($waitingLists as $waitingList)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="waiting-checkbox" value="{{ $waitingList->id }}">
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <span class="avatar">{{ strtoupper(substr($waitingList->student->full_name, 0, 1)) }}</span>
                                            </div>
                                            <div>
                                                <strong>{{ $waitingList->student->full_name }}</strong><br>
                                                <small class="text-muted">{{ $waitingList->student->phone }}</small><br>
                                                <small class="text-muted">{{ $waitingList->student->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($waitingList->courseItem->id != $courseItem->id)
                                            <span class="badge bg-info">Khóa con</span><br>
                                        @endif
                                        {{ $waitingList->courseItem->name }}
                                    </td>
                                    <td>
                                        @if($waitingList->interest_level == 'high')
                                            <span class="badge bg-danger">Cao</span>
                                        @elseif($waitingList->interest_level == 'medium')
                                            <span class="badge bg-warning">Vừa</span>
                                        @else
                                            <span class="badge bg-secondary">Thấp</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $waitingList->added_date->format('d/m/Y') }}
                                        <br>
                                        <small class="text-muted">{{ $waitingList->added_date->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        @if($waitingList->last_contact_date)
                                            {{ $waitingList->last_contact_date->format('d/m/Y') }}
                                            <br>
                                            <small class="text-muted">{{ $waitingList->last_contact_date->diffForHumans() }}</small>
                                        @else
                                            <span class="text-danger">Chưa liên hệ</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="max-height: 60px; overflow-y: auto; font-size: 0.85em;">
                                            {{ $waitingList->contact_notes ?? 'Không có ghi chú' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('waiting-lists.show', $waitingList->id) }}" class="btn btn-sm btn-info" title="Chi tiết">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form action="{{ route('waiting-lists.mark-contacted', $waitingList->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning" title="Đánh dấu đã liên hệ">
                                                    <i class="fas fa-phone"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('waiting-lists.move-to-enrollment', $waitingList->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Ghi danh">
                                                    <i class="fas fa-user-plus"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-3">
                    {{ $waitingLists->links() }}
                </div>
            @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Chưa có học viên nào trong danh sách chờ cho khóa học này.
                </div>
                <div class="text-center py-4">
                    <a href="{{ route('waiting-lists.create', ['course_item_id' => $courseItem->id]) }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Thêm học viên vào danh sách chờ
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Xử lý chọn/bỏ chọn tất cả
        $('#select-all').on('click', function() {
            $('.waiting-checkbox').prop('checked', $(this).prop('checked'));
            updateBulkButtons();
        });
        
        $('.waiting-checkbox').on('click', function() {
            updateBulkButtons();
            
            // Cập nhật trạng thái checkbox "Chọn tất cả"
            if ($('.waiting-checkbox:checked').length === $('.waiting-checkbox').length) {
                $('#select-all').prop('checked', true);
            } else {
                $('#select-all').prop('checked', false);
            }
        });
        
        function updateBulkButtons() {
            if ($('.waiting-checkbox:checked').length > 0) {
                $('#bulk-enroll-btn').prop('disabled', false);
            } else {
                $('#bulk-enroll-btn').prop('disabled', true);
            }
        }
        
        // Xử lý khi nhấp vào nút ghi danh hàng loạt
        $('#bulk-enroll-btn').on('click', function() {
            if (confirm('Bạn có chắc muốn ghi danh các học viên đã chọn?')) {
                // Lấy danh sách ID học viên đã chọn
                var selectedIds = [];
                $('.waiting-checkbox:checked').each(function() {
                    selectedIds.push($(this).val());
                });
                
                // Cập nhật input ẩn và gửi form
                $('#selected-ids').val(JSON.stringify(selectedIds));
                $('#bulk-action-form').submit();
            }
        });
    });
</script>
@endpush 