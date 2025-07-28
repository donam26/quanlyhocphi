@extends('layouts.app')

@section('page-title', 'Thêm học viên vào danh sách chờ')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('waiting-lists.index') }}">Danh sách chờ</a></li>
    <li class="breadcrumb-item active">Thêm học viên</li>
@endsection

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Thêm học viên vào danh sách chờ</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('waiting-lists.store') }}" method="POST">
                @csrf
                
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <!-- Chọn học viên -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2 mb-3">1. Thông tin học viên</h5>
                    </div>
                    <div class="col-md-6">
                        @if($student)
                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                            <div class="alert alert-info">
                                <strong>Học viên:</strong> {{ $student->full_name }} ({{ $student->phone }})<br>
                                <strong>Email:</strong> {{ $student->email }}<br>
                                <strong>Ngày sinh:</strong> {{ $student->birthdate ? $student->birthdate->format('d/m/Y') : 'Không có' }}
                                <a href="{{ route('waiting-lists.create') }}" class="btn btn-sm btn-outline-secondary float-end">Đổi học viên</a>
                            </div>
                        @else
                            <div class="form-group mb-3">
                                <label for="student_id" class="form-label">Chọn học viên <span class="text-danger">*</span></label>
                                <select name="student_id" id="student_id" class="form-select select2" required>
                                    <option value="">-- Chọn học viên --</option>
                                    @foreach (\App\Models\Student::orderBy('full_name')->get() as $studentOption)
                                        <option value="{{ $studentOption->id }}">
                                            {{ $studentOption->full_name }} ({{ $studentOption->phone }})
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">
                                    <a href="{{ route('students.create') }}" target="_blank">
                                        <i class="fas fa-plus-circle"></i> Thêm học viên mới
                                    </a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Chọn khóa học -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2 mb-3">2. Thông tin khóa học</h5>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="course_item_id" class="form-label">Khóa học quan tâm <span class="text-danger">*</span></label>
                            <select name="course_item_id" id="course_item_id" class="form-select select2" required>
                                <option value="">-- Chọn khóa học --</option>
                                @foreach ($groupedCourseItems as $id => $path)
                                    <option value="{{ $id }}" {{ (request('course_item_id') == $id || (isset($selectedCourseItem) && $selectedCourseItem->id == $id)) ? 'selected' : '' }}>
                                        {{ $path }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Chọn khóa học mà học viên quan tâm</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="interest_level" class="form-label">Mức độ quan tâm <span class="text-danger">*</span></label>
                            <select name="interest_level" id="interest_level" class="form-select select2" required>
                                <option value="medium" selected>Trung bình</option>
                                <option value="high">Cao</option>
                                <option value="low">Thấp</option>
                            </select>
                            <div class="form-text">Đánh giá mức độ quan tâm của học viên với khóa học</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="added_date" class="form-label">Ngày thêm vào danh sách <span class="text-danger">*</span></label>
                            <input type="date" name="added_date" id="added_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                </div>
                
                <!-- Ghi chú -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="border-bottom pb-2 mb-3">3. Ghi chú</h5>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group mb-3">
                            <label for="contact_notes" class="form-label">Ghi chú liên hệ</label>
                            <textarea name="contact_notes" id="contact_notes" rows="3" class="form-control"></textarea>
                            <div class="form-text">Nhập thông tin ghi chú về học viên này</div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Thêm vào danh sách chờ
                        </button>
                        <a href="{{ route('waiting-lists.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times me-1"></i> Hủy
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection