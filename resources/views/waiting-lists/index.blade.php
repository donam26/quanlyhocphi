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
        padding: 8px;
        border-radius: 4px;
        background-color: #f8f9fa;
        margin-bottom: 5px;
        transition: all 0.2s;
    }
    .tree-item.inactive {
        opacity: 0.6;
    }
    .tree-item.ui-sortable-helper {
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        background-color: #e9f2ff;
        border: 1px solid #bedcff;
    }
    .tree-item.highlight {
        animation: highlight 3s;
    }
    @keyframes highlight {
        0% { background-color: #fff3cd; }
        100% { background-color: #f8f9fa; }
    }
    .tree-item > * {
        margin-right: 8px;
    }
    .toggle-icon {
        cursor: pointer;
        width: 20px;
        text-align: center;
    }
    .item-icon {
        width: 20px;
        text-align: center;
    }
    .item-actions {
        margin-left: auto;
    }
    .badge-waiting {
        background-color: #ff9800;
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.8em;
        margin-left: 8px;
    }
</style>

<!-- Thống kê tổng quan -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Tổng học viên chờ</h6>
                        @php
                            $totalWaiting = array_sum($waitingCountsByItem);
                        @endphp
                        <h2 class="mt-2 mb-0">{{ number_format($totalWaiting) }}</h2>
                    </div>
                    <i class="fas fa-user-clock fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Khóa học có danh sách chờ</h6>
                        <h2 class="mt-2 mb-0">{{ count($waitingCountsByItem) }}</h2>
                    </div>
                    <i class="fas fa-book fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
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
                        <a href="{{ route('waiting-lists.statistics') }}" class="btn btn-info btn-sm ms-2">
                            <i class="fas fa-chart-bar me-1"></i>Thống kê
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4>Danh sách chờ theo khóa học</h4>
        <div>
            <button class="btn btn-sm btn-info me-2" id="expand-all">
                <i class="fas fa-expand-arrows-alt me-1"></i>Mở rộng tất cả
            </button>
            <button class="btn btn-sm btn-secondary" id="collapse-all">
                <i class="fas fa-compress-arrows-alt me-1"></i>Thu gọn tất cả
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="tree-container">
            <ul class="course-tree">
                @foreach($rootCourseItems as $item)
                    @include('waiting-lists.partials.item-tree', ['item' => $item, 'level' => 0])
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Mở rộng/Thu gọn các mục
        $('.toggle-icon').on('click', function() {
            const $icon = $(this).find('i');
            const $childrenContainer = $(this).closest('li').children('ul');
            
            if ($icon.hasClass('fa-chevron-right')) {
                $icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
                $childrenContainer.slideDown(200);
            } else {
                $icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
                $childrenContainer.slideUp(200);
            }
        });
        
        // Mở rộng tất cả
        $('#expand-all').on('click', function() {
            $('.course-tree ul').slideDown(200);
            $('.toggle-icon i.fa-chevron-right').removeClass('fa-chevron-right').addClass('fa-chevron-down');
        });
        
        // Thu gọn tất cả
        $('#collapse-all').on('click', function() {
            $('.course-tree ul').slideUp(200);
            $('.toggle-icon i.fa-chevron-down').removeClass('fa-chevron-down').addClass('fa-chevron-right');
        });
    });
</script>
@endpush 