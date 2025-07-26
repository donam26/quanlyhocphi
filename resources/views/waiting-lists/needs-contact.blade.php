@extends('layouts.app')

@section('page-title', 'Học viên cần liên hệ')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('waiting-lists.index') }}">Danh sách chờ</a></li>
<li class="breadcrumb-item active">Học viên cần liên hệ</li>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Danh sách học viên cần liên hệ</h4>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <form action="{{ route('waiting-lists.needs-contact') }}" method="GET" class="row g-3">
                            <div class="col-md-3">
                                <select name="course_item_id" class="form-select">
                                    <option value="">Tất cả khóa học</option>
                                    @foreach($rootCourseItems as $item)
                                        <option value="{{ $item->id }}" {{ request('course_item_id') == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                        @foreach($item->children as $child)
                                            <option value="{{ $child->id }}" {{ request('course_item_id') == $child->id ? 'selected' : '' }}>&nbsp;&nbsp;&nbsp;- {{ $child->name }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="priority" class="form-select">
                                    <option value="">Tất cả độ ưu tiên</option>
                                    <option value="high" {{ request('priority') == 'high' ? 'selected' : '' }}>Cao</option>
                                    <option value="medium" {{ request('priority') == 'medium' ? 'selected' : '' }}>Trung bình</option>
                                    <option value="low" {{ request('priority') == 'low' ? 'selected' : '' }}>Thấp</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Tìm kiếm theo tên, SĐT..." value="{{ request('search') }}">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Lọc</button>
                            </div>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Học viên</th>
                                    <th>Khóa học</th>
                                    <th>Thêm vào DS chờ</th>
                                    <th>Liên hệ gần nhất</th>
                                    <th>Độ ưu tiên</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($waitingLists as $index => $waiting)
                                <tr>
                                    <td>{{ $waitingLists->firstItem() + $index }}</td>
                                    <td>
                                        <a href="{{ route('students.show', $waiting->student_id) }}">
                                            {{ $waiting->student->full_name }}
                                        </a>
                                        <br>
                                        <span class="small text-muted">{{ $waiting->student->phone }}</span>
                                    </td>
                                    <td>{{ $waiting->courseItem->name }}</td>
                                    <td>{{ $waiting->added_date->format('d/m/Y') }}
                                        <br>
                                        <span class="small text-muted">{{ $days_since_added[$waiting->id] }} ngày trước</span>
                                    </td>
                                    <td>
                                        @if($waiting->last_contact_date)
                                            {{ $waiting->last_contact_date->format('d/m/Y') }}
                                            <br>
                                            <span class="small text-muted">{{ $days_since_last_contact[$waiting->id] }} ngày trước</span>
                                        @else
                                            <span class="badge bg-warning">Chưa liên hệ</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($priority[$waiting->id] == 'high')
                                            <span class="badge bg-danger">Cao</span>
                                        @elseif($priority[$waiting->id] == 'medium')
                                            <span class="badge bg-warning">Trung bình</span>
                                        @else
                                            <span class="badge bg-secondary">Thấp</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <!-- Đánh dấu đã liên hệ -->
                                            <form action="{{ route('waiting-lists.mark-contacted', $waiting->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="button" class="btn btn-sm btn-primary mark-contacted-btn" data-id="{{ $waiting->id }}" title="Đánh dấu đã liên hệ">
                                                    <i class="fas fa-phone"></i>
                                                </button>
                                            </form>
                                            
                                            <!-- Đánh dấu không quan tâm -->
                                            <form action="{{ route('waiting-lists.mark-not-interested', $waiting->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn đánh dấu học viên này là không quan tâm?');" title="Đánh dấu không quan tâm">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                            
                                            <!-- Chuyển sang ghi danh -->
                                            <a href="{{ route('enrollments.from-waiting-list', $waiting->id) }}" class="btn btn-sm btn-success" title="Chuyển sang ghi danh">
                                                <i class="fas fa-user-plus"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">Không có học viên nào cần liên hệ</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        {{ $waitingLists->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal đánh dấu đã liên hệ -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đánh dấu đã liên hệ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="contact-form" action="" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="contact_notes" class="form-label">Ghi chú khi liên hệ</label>
                        <textarea name="contact_notes" id="contact_notes" class="form-control" rows="3" placeholder="Nhập thông tin về cuộc gọi, phản hồi của học viên..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="interest_level" class="form-label">Mức độ quan tâm</label>
                        <select name="interest_level" id="interest_level" class="form-select">
                            <option value="high">Cao</option>
                            <option value="medium" selected>Trung bình</option>
                            <option value="low">Thấp</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.mark-contacted-btn').click(function() {
            const waitingId = $(this).data('id');
            const form = $(this).closest('form');
            
            // Cập nhật action của form trong modal
            $('#contact-form').attr('action', form.attr('action'));
            
            // Hiển thị modal
            $('#contactModal').modal('show');
        });
    });
</script>
@endpush 