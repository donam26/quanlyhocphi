@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Danh sách học viên - {{ $courseItem->name }}</h4>
            <div>
                <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#importModal">
                    <i class="fas fa-file-import"></i> Import học viên
                </button>
                <a href="{{ route('course-items.index') }}" class="btn btn-secondary">Quay lại</a>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                Tổng số học viên: <strong>{{ $studentCount }}</strong> | 
                Tổng số lượt đăng ký: <strong>{{ $enrollmentCount }}</strong>
            </div>
            
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Họ tên</th>
                            <th>Số điện thoại</th>
                            <th>Email</th>
                            <th>Khóa học</th>
                            <th>Ngày đăng ký</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $student)
                        <tr>
                            <td>{{ $student['student']->full_name }}</td>
                            <td>{{ $student['student']->phone }}</td>
                            <td>{{ $student['student']->email }}</td>
                            <td>{{ $student['course_item'] }}</td>
                            <td>{{ $student['enrollment_date']->format('d/m/Y') }}</td>
                            <td>
                                @if($student['status'] == 'enrolled')
                                    <span class="badge bg-primary">Đang học</span>
                                @elseif($student['status'] == 'completed')
                                    <span class="badge bg-success">Hoàn thành</span>
                                @elseif($student['status'] == 'dropped')
                                    <span class="badge bg-danger">Đã nghỉ</span>
                                @else
                                    <span class="badge bg-secondary">{{ $student['status'] }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('students.show', $student['student']->id) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Import Học viên -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import học viên vào {{ $courseItem->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('course-items.students.import', $courseItem->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="import_file" class="form-label">File Excel/CSV</label>
                        <input type="file" class="form-control" id="import_file" name="import_file" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">
                            Hỗ trợ định dạng Excel (.xlsx, .xls) và CSV (.csv)
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <h6 class="alert-heading">Yêu cầu dữ liệu:</h6>
                        <p>File cần có các cột sau:</p>
                        <ul>
                            <li>Họ (chứa họ và tên đệm)</li>
                            <li>Tên</li>
                            <li>Ngày sinh (định dạng DD/MM/YYYY)</li>
                            <li>Nơi sinh</li>
                            <li>Giới (Nam/Nữ)</li>
                            <li>Ghi chú (ON/OFF: trạng thái đang học/nghỉ)</li>
                        </ul>
                        <p>Hàng đầu tiên của file được xem là tiêu đề và sẽ không được import.</p>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="auto_enroll" name="auto_enroll" value="1" checked>
                        <label class="form-check-label" for="auto_enroll">
                            Tự động đăng ký học viên vào khóa học
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('course-items.students.download-template') }}" class="btn btn-outline-primary">
                        <i class="fas fa-download"></i> Tải mẫu
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection 