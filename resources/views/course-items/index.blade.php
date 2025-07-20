@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Quản lý khóa học</h2>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('course-items.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Thêm ngành mới
            </a>
            <a href="{{ route('course-items.tree') }}" class="btn btn-secondary ml-2">
                <i class="fas fa-project-diagram"></i> Xem cây
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h4>Danh sách ngành học</h4>
        </div>
        <div class="card-body">
            @if($rootItems->isEmpty())
                <div class="alert alert-info">
                    Chưa có ngành học nào được tạo. Hãy tạo ngành học đầu tiên.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Tên ngành</th>
                                <th>Mô tả</th>
                                <th>Số khóa học</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rootItems as $item)
                                <tr>
                                    <td>
                                        <a href="{{ route('course-items.show', $item->id) }}">
                                            {{ $item->name }}
                                        </a>
                                    </td>
                                    <td>{{ Str::limit($item->description, 100) }}</td>
                                    <td>{{ $item->children->count() }}</td>
                                    <td>
                                        @if($item->active)
                                            <span class="badge badge-success">Đang hoạt động</span>
                                        @else
                                            <span class="badge badge-secondary">Không hoạt động</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('course-items.show', $item->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('course-items.students', $item->id) }}" class="btn btn-sm btn-primary" title="Xem học viên">
                                                <i class="fas fa-users"></i>
                                            </a>
                                            <a href="{{ route('course-items.edit', $item->id) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('course-items.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Bạn có chắc muốn xóa ngành học này?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('course-items.toggle-active', $item->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{ $item->active ? 'btn-warning' : 'btn-success' }}">
                                                    <i class="fas {{ $item->active ? 'fa-ban' : 'fa-check' }}"></i>
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
</div>
@endsection 