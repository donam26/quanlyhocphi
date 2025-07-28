@extends('layouts.app')

@section('page-title', 'Chi tiết danh sách chờ')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('waiting-lists.index') }}">Danh sách chờ</a></li>
    <li class="breadcrumb-item"><a href="{{ route('course-items.waiting-lists', $waitingList->courseItem->id) }}">{{ $waitingList->courseItem->name }}</a></li>
    <li class="breadcrumb-item active">Chi tiết</li>
@endsection

@section('content')
<div class="container">
    <div class="row mb-3">
        <div class="col-md-8">
            <h4>Chi tiết học viên chờ: {{ $waitingList->student->full_name }}</h4>
        </div>
        <div class="col-md-4 text-end">
            <div class="btn-group">
                <a href="{{ route('course-items.waiting-lists', $waitingList->courseItem->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                <a href="{{ route('waiting-lists.edit', $waitingList->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Chỉnh sửa
                </a>
                <form action="{{ route('waiting-lists.move-to-enrollment', $waitingList->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('Bạn có chắc muốn ghi danh học viên này?')">
                        <i class="fas fa-user-plus"></i> Ghi danh
                    </button>
                </form>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-4">
            <!-- Thông tin học viên -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Thông tin học viên</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="avatar-lg mx-auto">
                            {{ strtoupper(substr($waitingList->student->full_name, 0, 1)) }}
                        </div>
                        <h5 class="mt-3">{{ $waitingList->student->full_name }}</h5>
                        @if($waitingList->interest_level == 'high')
                            <span class="badge bg-danger">Quan tâm cao</span>
                        @elseif($waitingList->interest_level == 'medium')
                            <span class="badge bg-warning">Quan tâm vừa</span>
                        @else
                            <span class="badge bg-secondary">Quan tâm thấp</span>
                        @endif
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-phone me-2"></i> Số điện thoại</span>
                            <span>{{ $waitingList->student->phone }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-envelope me-2"></i> Email</span>
                            <span>{{ $waitingList->student->email ?: 'Chưa có' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-calendar me-2"></i> Ngày sinh</span>
                            <span>{{ $waitingList->student->birthdate ? $waitingList->student->birthdate->format('d/m/Y') : 'Chưa có' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-venus-mars me-2"></i> Giới tính</span>
                            <span>{{ $waitingList->student->gender == 'male' ? 'Nam' : 'Nữ' }}</span>
                        </li>
                    </ul>
                    <div class="mt-3 text-center">
                        <a href="{{ route('students.show', $waitingList->student->id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-user me-1"></i> Xem hồ sơ học viên
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <!-- Thông tin khóa học -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Thông tin khóa học</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Khóa học:</strong> {{ $waitingList->courseItem->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Học phí:</strong> {{ number_format($waitingList->courseItem->fee) }}đ</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <p><strong>Đường dẫn khóa học:</strong> {{ $waitingList->courseItem->getPathAttribute() }}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Chi tiết đăng ký chờ -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Chi tiết đăng ký chờ</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><strong>Ngày đăng ký:</strong> {{ $waitingList->added_date->format('d/m/Y') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p>
                                <strong>Trạng thái:</strong> 
                                @if($waitingList->status == 'waiting')
                                    <span class="badge bg-warning">Đang chờ</span>
                                @elseif($waitingList->status == 'contacted')
                                    <span class="badge bg-info">Đã liên hệ</span>
                                @elseif($waitingList->status == 'enrolled')
                                    <span class="badge bg-success">Đã ghi danh</span>
                                @else
                                    <span class="badge bg-secondary">Không quan tâm</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p>
                                <strong>Mức độ quan tâm:</strong> 
                                @if($waitingList->interest_level == 'high')
                                    <span class="badge bg-danger">Cao</span>
                                @elseif($waitingList->interest_level == 'medium')
                                    <span class="badge bg-warning">Vừa</span>
                                @else
                                    <span class="badge bg-secondary">Thấp</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Liên hệ lần cuối:</strong> {{ $waitingList->last_contact_date ? $waitingList->last_contact_date->format('d/m/Y') : 'Chưa liên hệ' }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <h6>Ghi chú liên hệ:</h6>
                            <div class="p-3 bg-light rounded">
                                {{ $waitingList->contact_notes ?: 'Không có ghi chú' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Lịch sử liên hệ -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Các thao tác</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <form action="{{ route('waiting-lists.mark-contacted', $waitingList->id) }}" method="POST" class="mb-3">
                                @csrf
                                <div class="input-group">
                                    <textarea name="contact_notes" class="form-control" placeholder="Nhập ghi chú liên hệ..."></textarea>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-phone me-1"></i> Đánh dấu đã liên hệ
                                    </button>
                                </div>
                            </form>
                            
                            <div class="d-flex justify-content-between">
                                <form action="{{ route('waiting-lists.move-to-enrollment', $waitingList->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Bạn có chắc muốn ghi danh học viên này?')">
                                        <i class="fas fa-user-plus me-1"></i> Ghi danh học viên
                                    </button>
                                </form>
                                
                                <form action="{{ route('waiting-lists.mark-not-interested', $waitingList->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Bạn có chắc muốn đánh dấu học viên không quan tâm?')">
                                        <i class="fas fa-times me-1"></i> Đánh dấu không quan tâm
                                    </button>
                                </form>
                                
                                <form action="{{ route('waiting-lists.destroy', $waitingList->id) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Bạn có chắc muốn xóa học viên này khỏi danh sách chờ?')">
                                        <i class="fas fa-trash me-1"></i> Xóa khỏi danh sách chờ
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
.avatar-lg {
    width: 80px;
    height: 80px;
    background-color: #4e73df;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    font-weight: bold;
}
</style>
