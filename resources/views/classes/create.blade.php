@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Thêm lớp học mới</h2>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('classes.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>Thông tin lớp học</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('classes.store') }}" method="POST">
                @csrf
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="course_item_id" class="form-label">Khóa học <span class="text-danger">*</span></label>
                        <select name="course_item_id" id="course_item_id" class="form-select @error('course_item_id') is-invalid @enderror" required>
                            <option value="">-- Chọn khóa học --</option>
                            @foreach($courseItems as $item)
                                <option value="{{ $item->id }}" {{ old('course_item_id', request('course_item_id')) == $item->id ? 'selected' : '' }}>
                                    {{ $item->path }} ({{ number_format($item->fee, 0, ',', '.') }} đ)
                                </option>
                            @endforeach
                        </select>
                        @error('course_item_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="name" class="form-label">Tên lớp <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="type" class="form-label">Loại lớp <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="online" {{ old('type') == 'online' ? 'selected' : '' }}>Online</option>
                            <option value="offline" {{ old('type') == 'offline' ? 'selected' : '' }}>Offline</option>
                            <option value="hybrid" {{ old('type') == 'hybrid' ? 'selected' : '' }}>Hybrid</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4">
                        <label for="batch_number" class="form-label">Khóa số</label>
                        <input type="number" class="form-control @error('batch_number') is-invalid @enderror" id="batch_number" name="batch_number" value="{{ old('batch_number', 1) }}" min="1">
                        @error('batch_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4">
                        <label for="max_students" class="form-label">Số lượng học viên tối đa</label>
                        <input type="number" class="form-control @error('max_students') is-invalid @enderror" id="max_students" name="max_students" value="{{ old('max_students', 50) }}" min="1">
                        @error('max_students')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Ngày bắt đầu</label>
                        <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date" value="{{ old('start_date') }}">
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">Ngày kết thúc</label>
                        <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date" value="{{ old('end_date') }}">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-4">
                        <label for="registration_deadline" class="form-label">Hạn đăng ký</label>
                        <input type="date" class="form-control @error('registration_deadline') is-invalid @enderror" id="registration_deadline" name="registration_deadline" value="{{ old('registration_deadline') }}">
                        @error('registration_deadline')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="status" class="form-label">Trạng thái</label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="planned" {{ old('status') == 'planned' ? 'selected' : '' }}>Dự kiến</option>
                            <option value="open" {{ old('status') == 'open' ? 'selected' : '' }}>Đang tuyển sinh</option>
                            <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>Đang học</option>
                            <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Đã kết thúc</option>
                            <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    <div class="col-md-6">
                        <label for="notes" class="form-label">Ghi chú</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu lớp học
                    </button>
                    <a href="{{ route('classes.index') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Hủy
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection 