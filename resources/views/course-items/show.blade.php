@extends('layouts.app')

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
    .handle {
        cursor: move;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
    }
    .handle:hover {
        background-color: #e9ecef;
    }
    .order-index {
        display: inline-block;
        width: 1.5rem;
        height: 1.5rem;
        line-height: 1.5rem;
        text-align: center;
        background-color: #f8f9fa;
        border-radius: 50%;
        font-size: 0.75rem;
        font-weight: bold;
    }
    .btn-group .btn {
        margin-right: 0.125rem;
    }
    .breadcrumb {
        background-color: transparent;
        padding: 0;
    }
</style>
<div class="container">
    <div class="row mb-3">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('course-items.index') }}">Quản lý khóa học</a></li>
                    @foreach($breadcrumbs as $index => $item)
                        @if($loop->last)
                            <li class="breadcrumb-item active" aria-current="page">{{ $item->name }}</li>
                        @else
                            <li class="breadcrumb-item"><a href="{{ route('course-items.show', $item->id) }}">{{ $item->name }}</a></li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('course-items.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-8">
            <h2>{{ $courseItem->name }} 
                @if(!$courseItem->active)
                    <span class="badge badge-secondary">Không hoạt động</span>
                @endif
            </h2>
        </div>
        <div class="col-md-4 text-right">
            <div class="btn-group">
                <a href="{{ route('course-items.edit', $courseItem->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Chỉnh sửa
                </a>
                @if(!$courseItem->is_leaf)
                    <a href="{{ route('course-items.create', ['parent_id' => $courseItem->id]) }}" class="btn btn-success">
                        <i class="fas fa-plus"></i> Thêm khóa con
                    </a>
                @endif
                <a href="{{ route('course-items.students', $courseItem->id) }}" class="btn btn-info">
                    <i class="fas fa-user-graduate"></i> Học viên
                </a>
            </div>
        </div>
    </div>

    @if($courseItem->is_leaf)
        <!-- Nút lá - Hiển thị thông tin khóa học -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Thông tin khóa học</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Cấp độ trong cây:</strong> {{ $courseItem->level }}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Học phí:</strong> {{ number_format($courseItem->fee, 0, ',', '.') }} đồng</p>
                        <p>
                            <strong>Loại học:</strong>
                            @if($courseItem->has_online && $courseItem->has_offline)
                                Online & Offline
                            @elseif($courseItem->has_online)
                                Online
                            @elseif($courseItem->has_offline)
                                Offline
                            @else
                                Chưa xác định
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Nút nhánh - Hiển thị các khóa học con -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Các khóa học con</h4>
                <div>
                    <a href="{{ route('course-items.create', ['parent_id' => $courseItem->id]) }}" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Thêm khóa con
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($courseItem->children->isEmpty())
                    <div class="alert alert-info">
                        Chưa có khóa học con nào được tạo.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped" id="sortable-items">
                            <thead>
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="30%">Tên khóa học</th>
                                    <th width="15%">Học phí</th>
                                    <th width="15%">Loại học</th>
                                    <th width="10%">Trạng thái</th>
                                    <th width="25%">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($courseItem->children->sortBy('order_index') as $child)
                                    <tr data-id="{{ $child->id }}">
                                        <td>
                                            <span class="handle btn btn-sm btn-light">
                                                <i class="fas fa-arrows-alt"></i>
                                            </span>
                                            <span class="order-index">{{ $child->order_index }}</span>
                                        </td>
                                        <td>
                                            <a href="{{ route('course-items.show', $child->id) }}">
                                                {{ $child->name }}
                                                @if($child->is_leaf)
                                                    <span class="badge badge-info">Khóa cuối</span>
                                                @endif
                                            </a>
                                        </td>
                                        <td>
                                            @if($child->fee)
                                                {{ number_format($child->fee, 0, ',', '.') }} đ
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($child->has_online && $child->has_offline)
                                                <span class="badge badge-primary">Online</span>
                                                <span class="badge badge-secondary">Offline</span>
                                            @elseif($child->has_online)
                                                <span class="badge badge-primary">Online</span>
                                            @elseif($child->has_offline)
                                                <span class="badge badge-secondary">Offline</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($child->active)
                                                <span class="badge badge-success">Hoạt động</span>
                                            @else
                                                <span class="badge badge-secondary">Không hoạt động</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('course-items.show', $child->id) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('course-items.students', $child->id) }}" class="btn btn-sm btn-info" title="Học viên">
                                                    <i class="fas fa-user-graduate"></i>
                                                </a>
                                                <a href="{{ route('course-items.edit', $child->id) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('course-items.destroy', $child->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa khóa học này?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('course-items.toggle-active', $child->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $child->active ? 'btn-warning' : 'btn-success' }}">
                                                        <i class="fas {{ $child->active ? 'fa-ban' : 'fa-check' }}"></i>
                                                    </button>
                                                </form>
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
    @endif
</div>

@if(!$courseItem->is_leaf && !$courseItem->children->isEmpty())
    @push('scripts')
    <script>
        $(function() {
            $("#sortable-items tbody").sortable({
                handle: '.handle',
                placeholder: 'ui-state-highlight',
                helper: function(e, tr) {
                    var $originals = tr.children();
                    var $helper = tr.clone();
                    $helper.children().each(function(index) {
                        $(this).width($originals.eq(index).width());
                    });
                    return $helper;
                },
                update: function(event, ui) {
                    // Cập nhật lại thứ tự hiển thị
                    let items = [];
                    $('#sortable-items tbody tr').each(function(index) {
                        const id = $(this).data('id');
                        items.push({
                            id: id,
                            order: index + 1
                        });
                        $(this).find('.order-index').text(index + 1);
                    });
                    
                    // Gửi Ajax để cập nhật thứ tự
                    $.ajax({
                        url: '{{ route("course-items.update-order") }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            items: items
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success('Đã cập nhật thứ tự hiển thị');
                            } else {
                                toastr.error('Có lỗi xảy ra khi cập nhật thứ tự');
                            }
                        },
                        error: function() {
                            toastr.error('Có lỗi xảy ra khi cập nhật thứ tự');
                        }
                    });
                }
            }).disableSelection();
            
            // Thêm CSS cho sortable
            $("<style>")
                .prop("type", "text/css")
                .html(`
                    .ui-sortable-helper {
                        display: table;
                        background-color: #f8f9fa;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    }
                    .ui-state-highlight {
                        height: 3em;
                        line-height: 3em;
                        background-color: #fffde7 !important;
                        border: 1px dashed #ffc107;
                    }
                    .handle {
                        cursor: move;
                    }
                `)
                .appendTo("head");
        });
    </script>
    @endpush
@endif
@endsection 