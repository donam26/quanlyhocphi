@extends('layouts.app')

@section('page-title', 'Quản lý danh sách chờ')

@section('breadcrumb')
<li class="breadcrumb-item active">Danh sách chờ</li>
@endsection

@section('content')
<style>
    .tree-container {
        padding: 15px;
        overflow-x: auto;
        margin-bottom: 20px;
    }
    .course-tree, .course-tree ul {
        list-style-type: none;
        padding-left: 25px;
    }
    .course-tree li {
        position: relative;
        padding: 5px 0;
        border-left: 1px dashed #ccc;
    }
    .course-tree li::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 0;
        width: 20px;
        height: 1px;
        background-color: #ccc;
    }
    .tree-item {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        border-radius: 6px;
        background-color: #f8f9fa;
        margin-left: 15px;
        border-left: 4px solid #6c757d;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        min-width: 300px;
    }
    .tree-item:hover {
        background-color: #e9ecef;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .tree-item.active {
        background-color: #f8f9fa;
        border-left-color: #28a745;
    }
    .tree-item.inactive {
        background-color: #f1f1f1;
        border-left-color: #dc3545;
        opacity: 0.7;
    }
    .tree-item.leaf {
        border-left-color: #17a2b8;
    }
    .level-1 {
        font-size: 1.2rem;
        font-weight: bold;
        background-color: #f0f8ff;
    }
    .level-2 {
        font-size: 1.1rem;
        background-color: #f5f5f5;
    }
    .level-3 {
        font-size: 1rem;
        background-color: #f9f9f9;
    }
    .toggle-icon {
        margin-right: 8px;
        width: 20px;
        text-align: center;
        cursor: pointer;
        color: #6c757d;
        transition: color 0.2s;
    }
    .toggle-icon:hover {
        color: #0d6efd;
    }
    .item-icon {
        margin-right: 10px;
        width: 20px;
        text-align: center;
        color: #495057;
    }
    .tree-item a {
        color: #212529;
        text-decoration: none;
        flex-grow: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .tree-item a:hover {
        color: #0d6efd;
    }
    .item-actions {
        margin-left: auto;
        opacity: 0.3;
        transition: opacity 0.3s;
        white-space: nowrap;
        display: flex;
        gap: 3px;
    }
    .tree-item:hover .item-actions {
        opacity: 1;
    }
    .badge {
        margin-left: 5px;
        font-weight: 500;
        padding: 0.35em 0.65em;
    }
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    .highlight {
        animation: highlight-animation 3s;
    }
    @keyframes highlight-animation {
        0% { background-color: #fff3cd; box-shadow: 0 0 10px rgba(255, 193, 7, 0.8); }
        70% { background-color: #fff3cd; box-shadow: 0 0 10px rgba(255, 193, 7, 0.8); }
        100% { background-color: inherit; box-shadow: inherit; }
    }
    .badge-waiting {
        background-color: #ff9800;
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.8em;
        margin-left: 8px;
    }

    /* Thêm CSS cho tabs */
    .course-tabs {
        border-bottom: 1px solid #dee2e6;
    }
    
    .course-tabs .nav-link {
        margin-bottom: -1px;
        border: 1px solid transparent;
        border-top-left-radius: 0.25rem;
        border-top-right-radius: 0.25rem;
        padding: 0.5rem 1rem;
        color: #495057;
        background-color: #f8f9fa;
        transition: all 0.2s ease;
    }
    
    .course-tabs .nav-link:hover {
        border-color: #e9ecef #e9ecef #dee2e6;
        background-color: #e9ecef;
    }
    
    .course-tabs .nav-link.active {
        color: #0d6efd;
        background-color: #fff;
        border-color: #dee2e6 #dee2e6 #fff;
        font-weight: 600;
    }
    
    .tab-content > .tab-pane {
        display: none;
    }
    
    .tab-content > .active {
        display: block;
    }
    
    .tab-content > .show {
        opacity: 1;
    }
    
    .tab-content > .fade {
        transition: opacity 0.15s linear;
    }
    
    .tab-content > .fade:not(.show) {
        opacity: 0;
    }

    /* Status badges */
    .badge-status {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
    }

    .badge-waiting {
        background-color: #ff9800;
        color: white;
    }
    
    .badge-contacted {
        background-color: #17a2b8;
        color: white;
    }
    
    .badge-enrolled {
        background-color: #28a745;
        color: white;
    }
    
    .badge-not-interested {
        background-color: #dc3545;
        color: white;
    }

    /* Interest level badges */
    .badge-high {
        background-color: #dc3545;
        color: white;
    }
    
    .badge-medium {
        background-color: #fd7e14;
        color: white;
    }
    
    .badge-low {
        background-color: #6c757d;
        color: white;
    }

    .stats-card {
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .stats-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    /* Category Icons */
    .category-icon {
        margin-right: 8px;
        font-size: 1.2em;
    }

    .category-bg-primary {
        background-color: rgba(13, 110, 253, 0.15);
        color: #0d6efd;
    }

    .category-bg-success {
        background-color: rgba(25, 135, 84, 0.15);
        color: #198754;
    }

    .category-bg-warning {
        background-color: rgba(255, 193, 7, 0.15);
        color: #ffc107;
    }

    .category-bg-info {
        background-color: rgba(13, 202, 240, 0.15);
        color: #0dcaf0;
    }
</style>

<div class="container-fluid">
  
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="m-0">Thao tác nhanh</h5>
                        <div>
                            <a href="{{ route('waiting-lists.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-1"></i>Thêm học viên vào danh sách chờ
                            </a>
                            <a href="{{ route('waiting-lists.needs-contact') }}" class="btn btn-warning btn-sm ms-2">
                                <i class="fas fa-phone me-1"></i>Học viên cần liên hệ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Danh sách chờ theo khóa học</h4>
                <div>
                    <button class="btn btn-sm btn-info me-2" id="expand-all">
                        <i class="fas fa-expand-arrows-alt me-1"></i>Mở rộng tất cả
                    </button>
                    <button class="btn btn-sm btn-secondary me-2" id="collapse-all">
                        <i class="fas fa-compress-arrows-alt me-1"></i>Thu gọn tất cả
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if(count($rootCourseItems) > 0)
                <!-- Tab navigation -->
                <ul class="nav nav-tabs course-tabs mb-3" id="courseTab" role="tablist">
                    @php
                        $currentRootId = request('root_id');
                        $hasValidRootId = false;
                        
                        // Kiểm tra xem root_id có tồn tại trong danh sách không
                        if ($currentRootId) {
                            foreach ($rootCourseItems as $item) {
                                if ($item->id == $currentRootId) {
                                    $hasValidRootId = true;
                                    break;
                                }
                            }
                        }
                        
                        // Nếu không có root_id hợp lệ, sử dụng ID của ngành đầu tiên
                        if (!$hasValidRootId) {
                            $currentRootId = $rootCourseItems->first()->id;
                        }
                    @endphp
                    
                    @foreach($rootCourseItems as $index => $rootItem)
                        @php
                            $waitingCount = isset($waitingCountsByItem[$rootItem->id]) ? $waitingCountsByItem[$rootItem->id] : 0;
                            foreach($rootItem->children as $child) {
                                $waitingCount += isset($waitingCountsByItem[$child->id]) ? $waitingCountsByItem[$child->id] : 0;
                            }
                            
                            $isActive = ($rootItem->id == $currentRootId);
                        @endphp
                        
                        <li class="nav-item" role="presentation">
                            <button class="nav-link {{ $isActive ? 'active' : '' }}" 
                                    id="tab-{{ $rootItem->id }}" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#content-{{ $rootItem->id }}" 
                                    type="button" 
                                    role="tab" 
                                    aria-controls="content-{{ $rootItem->id }}" 
                                    aria-selected="{{ $isActive ? 'true' : 'false' }}">
                                {{ $rootItem->name }}
                                @if($waitingCount > 0)
                                    <span class="badge bg-warning">{{ $waitingCount }}</span>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>
                
                <!-- Tab content -->
                <div class="tab-content" id="courseTabContent">
                    @foreach($rootCourseItems as $index => $rootItem)
                        @php
                            $waitingCount = isset($waitingCountsByItem[$rootItem->id]) ? $waitingCountsByItem[$rootItem->id] : 0;
                            foreach($rootItem->children as $child) {
                                $waitingCount += isset($waitingCountsByItem[$child->id]) ? $waitingCountsByItem[$child->id] : 0;
                            }
                            
                            $isActive = ($rootItem->id == $currentRootId);
                        @endphp
                        
                        <div class="tab-pane fade {{ $isActive ? 'show active' : '' }}" 
                             id="content-{{ $rootItem->id }}" 
                             role="tabpanel" 
                             aria-labelledby="tab-{{ $rootItem->id }}">
                            <div class="tree-container">
                                <ul class="course-tree">
                                    <li>
                                        <div class="tree-item level-1 {{ !$rootItem->active ? 'inactive' : 'active' }}" data-id="{{ $rootItem->id }}">
                                            @if($rootItem->children->count() > 0)
                                                <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#children-{{ $rootItem->id }}">
                                                    <i class="fas fa-minus-circle"></i>
                                                </span>
                                            @else
                                                <span class="item-icon">
                                                    <i class="fas fa-graduation-cap"></i>
                                                </span>
                                            @endif
                                            
                                            <a href="{{ route('course-items.waiting-lists', $rootItem->id) }}">{{ $rootItem->name }}</a>
                                            
                                            @php
                                                $rootWaitingCount = $waitingCountsByItem[$rootItem->id] ?? 0;
                                            @endphp
                                            
                                            @if($rootWaitingCount > 0)
                                                <span class="badge badge-waiting rounded-pill">
                                                    <i class="fas fa-user-clock me-1"></i>{{ $rootWaitingCount }}
                                                </span>
                                            @endif
                                            
                                            <div class="item-actions">
                                                <a href="{{ route('course-items.waiting-lists', $rootItem->id) }}" class="btn btn-sm btn-info" title="Xem danh sách chờ">
                                                    <i class="fas fa-list-alt"></i>
                                                </a>
                                                
                                                @if($rootItem->is_leaf)
                                                    <a href="{{ route('waiting-lists.create', ['course_item_id' => $rootItem->id]) }}" class="btn btn-sm btn-success" title="Thêm học viên vào danh sách chờ">
                                                        <i class="fas fa-plus"></i>
                                                    </a>
                                                @endif
                                                
                                                @php
                                                    $contactNeededCount = App\Models\WaitingList::where('course_item_id', $rootItem->id)
                                                        ->where('status', 'waiting')
                                                        ->where(function($query) {
                                                            $query->whereNull('last_contact_date')
                                                                ->orWhere('last_contact_date', '<', now()->subDays(7));
                                                        })
                                                        ->count();
                                                @endphp
                                                
                                                @if ($contactNeededCount > 0)
                                                    <a href="{{ route('waiting-lists.needs-contact', ['course_item_id' => $rootItem->id]) }}" class="btn btn-sm btn-warning" title="Học viên cần liên hệ ({{ $contactNeededCount }})">
                                                        <i class="fas fa-phone"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        @if($rootItem->children->count() > 0)
                                            <ul id="children-{{ $rootItem->id }}" class="collapse show">
                                                @foreach($rootItem->children->sortBy('order_index') as $childItem)
                                                    @include('waiting-lists.partials.item-tree', ['item' => $childItem, 'level' => 2])
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                </ul>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-info">
                    Chưa có khóa học nào có danh sách chờ.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Kích hoạt tab đầu tiên khi tải trang nếu không có tab nào được chọn
        if ($('.course-tabs .nav-link.active').length === 0) {
            const firstTab = document.querySelector('#courseTab .nav-link');
            if (firstTab) {
                new bootstrap.Tab(firstTab).show();
            }
        }

        // Cập nhật URL khi chuyển tab
        $('.course-tabs .nav-link').on('shown.bs.tab', function (e) {
            const rootId = $(this).attr('id').replace('tab-', '');
            const url = new URL(window.location);
            url.searchParams.set('root_id', rootId);
            window.history.pushState({}, '', url);
        });
        
        // Mở rộng tất cả
        $('#expand-all').click(function() {
            $('.course-tree .collapse').collapse('show');
            $('.toggle-icon i').removeClass('fa-plus-circle').addClass('fa-minus-circle');
        });
        
        // Thu gọn tất cả
        $('#collapse-all').click(function() {
            $('.course-tree .collapse').collapse('hide');
            $('.toggle-icon i').removeClass('fa-minus-circle').addClass('fa-plus-circle');
        });
        
        // Thay đổi icon khi mở/đóng
        $('.collapse').on('show.bs.collapse', function() {
            $(this).siblings('.tree-item').find('.toggle-icon i').removeClass('fa-plus-circle').addClass('fa-minus-circle');
        });
        
        $('.collapse').on('hide.bs.collapse', function() {
            $(this).siblings('.tree-item').find('.toggle-icon i').removeClass('fa-minus-circle').addClass('fa-plus-circle');
        });
        
        // Thu gọn các cấp sâu hơn khi tải trang
        $('.course-tree li li ul').collapse('hide');
        $('.course-tree li li .tree-item .toggle-icon i').removeClass('fa-minus-circle').addClass('fa-plus-circle');
        
        // Xử lý click vào toggle-icon thủ công
        $('.toggle-icon').on('click', function() {
            const collapseId = $(this).data('bs-target') || $(this).attr('data-bs-target');
            if (collapseId) {
                const collapseElement = $(collapseId);
                if (collapseElement.hasClass('show')) {
                    collapseElement.collapse('hide');
                } else {
                    collapseElement.collapse('show');
                }
            }
        });
    });
</script>
@endpush 