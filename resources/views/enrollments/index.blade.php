@extends('layouts.app')

@section('page-title', 'Danh sách ghi danh')

@section('breadcrumb')
<li class="breadcrumb-item active">Ghi danh</li>
@endsection

@section('page-actions')
<button type="button" class="btn btn-primary" onclick="createEnrollment()">
    <i class="fas fa-plus-circle me-1"></i> Thêm ghi danh mới
</button>
@endsection

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <div class="mb-3">
            <div class="btn-group" role="group" aria-label="Bộ lọc nhanh">
                <a href="{{ route('enrollments.index', ['status' => 'waiting']) }}" class="btn {{ request('status') == 'waiting' ? 'btn-warning' : 'btn-outline-warning' }}">
                    <i class="fas fa-user-clock me-1"></i> Danh sách chờ
                </a>
                <a href="{{ route('enrollments.index', ['status' => 'active']) }}" class="btn {{ request('status') == 'active' ? 'btn-success' : 'btn-outline-success' }}">
                    <i class="fas fa-user-graduate me-1"></i> Đang học
                </a>
                <a href="{{ route('enrollments.index', ['status' => 'completed']) }}" class="btn {{ request('status') == 'completed' ? 'btn-success' : 'btn-outline-success' }}">
                    <i class="fas fa-check-circle me-1"></i> Đã hoàn thành
                </a>
                <a href="{{ route('enrollments.index', ['payment_status' => 'pending']) }}" class="btn {{ request('payment_status') == 'pending' ? 'btn-danger' : 'btn-outline-danger' }}">
                    <i class="fas fa-exclamation-circle me-1"></i> Chưa thanh toán
                </a>
            </div>
        </div>
        
        <form action="{{ route('enrollments.index') }}" method="GET" class="row align-items-center g-2" id="enrollmentSearchForm">
            <div class="col-md-3">
                <div class="form-group">
                    <label class="visually-hidden" for="student_search">Tìm học viên</label>
                    <select id="student_search" name="student_id" class="form-select">
                        @if(request('student_id'))
                            @php
                                $student = \App\Models\Student::find(request('student_id'));
                            @endphp
                            @if($student)
                                <option value="{{ $student->id }}" selected>{{ $student->full_name }} - {{ $student->phone }}</option>
                            @endif
                        @endif
                    </select>
                </div>
            </div>
            
            <div class="col-md-2">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Trạng thái --</option>
                    <option value="waiting" {{ request('status') == 'waiting' ? 'selected' : '' }}>Danh sách chờ</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Đang học</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Đã hoàn thành</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Đã hủy</option>
                </select>
            </div>
            
            <div class="col-md-2">
                <select name="payment_status" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Thanh toán --</option>
                    <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Đã thanh toán</option>
                    <option value="partial" {{ request('payment_status') == 'partial' ? 'selected' : '' }}>Thanh toán một phần</option>
                    <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Chưa thanh toán</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <select name="course_item_id" class="form-select">
                    <option value="">-- Tất cả khóa học --</option>
                    @foreach(\App\Models\CourseItem::where('is_leaf', true)->where('active', true)->get() as $item)
                        <option value="{{ $item->id }}" {{ request('course_item_id') == $item->id ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if(request('status') == 'waiting')
            <div class="col-md-1">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="needs_contact" value="1" id="needsContactCheck" {{ request('needs_contact') ? 'checked' : '' }} onchange="this.form.submit()">
                    <label class="form-check-label" for="needsContactCheck">
                        Cần liên hệ
                    </label>
                </div>
            </div>
            @endif
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-1"></i> Tìm kiếm
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="mb-0">
                    Danh sách ghi danh 
                    @if(request('status'))
                        - {{ ucfirst(request('status')) }}
                    @endif
                </h5>
            </div>
            <div class="col-auto">
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        <span class="text-success fw-bold">{{ formatCurrency($totalPaid) }} đ đã thu</span>
                    </div>
                    <div class="col-auto">
                        <span class="text-warning fw-bold">{{ formatCurrency($totalFees - $totalPaid) }} đ chưa thu</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Học viên</th>
                        <th>Khóa học</th>
                        <th>Ngày ghi danh</th>
                        <th>Học phí</th>
                        <th>Đã thanh toán</th>
                        <th>Còn lại</th>
                        <th>Trạng thái</th>
                        <th style="width: 150px;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrollments as $enrollment)
                    <tr>
                        <td>{{ $enrollment->id }}</td>
                        <td>
                            <span class="d-block fw-bold">{{ $enrollment->student->full_name }}</span>
                            <small class="text-muted">{{ $enrollment->student->phone }}</small>
                        </td>
                        <td>
                            <span class="d-block">{{ $enrollment->courseItem->name }}</span>
                        </td>
                        <td>{{ $enrollment->enrollment_date ? $enrollment->enrollment_date->format('d/m/Y') : 'N/A' }}</td>
                        <td>{{ formatCurrency($enrollment->final_fee) }} đ</td>
                        <td>{{ formatCurrency($enrollment->getTotalPaidAmount()) }} đ</td>
                        <td>
                            <span class="{{ $enrollment->getRemainingAmount() > 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                {{ formatCurrency($enrollment->getRemainingAmount()) }} đ
                            </span>
                        </td>
                        <td>
                            <x-enrollment-status-badge :status="$enrollment->status" />
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-primary" onclick="showEnrollmentDetails({{ $enrollment->id }})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-success" onclick="editEnrollment({{ $enrollment->id }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">Không tìm thấy ghi danh nào</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $enrollments->withQueryString()->links() }}
    </div>
</div>

<!-- Modal xem chi tiết ghi danh -->
<div class="modal fade" id="viewEnrollmentModal" tabindex="-1" aria-labelledby="viewEnrollmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewEnrollmentModalLabel">Chi tiết ghi danh #<span id="enrollment-id"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading spinner -->
                <div id="enrollment-loading" class="text-center my-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2">Đang tải thông tin ghi danh...</p>
                </div>
                
                <!-- Nội dung chi tiết -->
                <div id="enrollment-details" style="display: none;">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Thông tin cơ bản</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Học viên</dt>
                                <dd class="col-sm-8"><span id="enrollment-student"></span></dd>
                                
                                <dt class="col-sm-4">Khóa học</dt>
                                <dd class="col-sm-8"><span id="enrollment-course"></span></dd>
                                
                                <dt class="col-sm-4">Ngày ghi danh</dt>
                                <dd class="col-sm-8"><span id="enrollment-date"></span></dd>
                                
                                <dt class="col-sm-4">Trạng thái</dt>
                                <dd class="col-sm-8"><span id="enrollment-status"></span></dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Thông tin thanh toán</h6>
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Học phí</dt>
                                <dd class="col-sm-8"><span id="enrollment-fee"></span></dd>
                                
                                <dt class="col-sm-4">Đã thanh toán</dt>
                                <dd class="col-sm-8"><span id="enrollment-paid"></span></dd>
                                
                                <dt class="col-sm-4">Còn lại</dt>
                                <dd class="col-sm-8"><span id="enrollment-remaining"></span></dd>
                                
                                <dt class="col-sm-4">Trạng thái</dt>
                                <dd class="col-sm-8"><span id="enrollment-payment-status"></span></dd>
                            </dl>
                        </div>
                    </div>
                    
                    <div class="mb-4" id="enrollment-notes-section">
                        <h6 class="fw-bold mb-2">Ghi chú</h6>
                        <p id="enrollment-notes" class="p-3 bg-light rounded"></p>
                    </div>
                    
                    <div class="mb-3" id="enrollment-payments-section">
                        <h6 class="fw-bold mb-2">Thanh toán</h6>
                        <div id="enrollment-payments"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="btn-edit-enrollment">
                    <i class="fas fa-edit me-1"></i> Chỉnh sửa
                </button>
                <a href="#" class="btn btn-success" id="btn-add-payment">
                    <i class="fas fa-money-bill-wave me-1"></i> Thêm thanh toán
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal chỉnh sửa ghi danh -->
<div class="modal fade" id="editEnrollmentModal" tabindex="-1" aria-labelledby="editEnrollmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEnrollmentModalLabel">Chỉnh sửa ghi danh</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading spinner -->
                <div id="edit-enrollment-loading" class="text-center my-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2">Đang tải thông tin ghi danh...</p>
                </div>
                
                <!-- Form chỉnh sửa -->
                <form id="enrollmentEditForm" method="POST" style="display: none;">
                    @csrf
                    <input type="hidden" id="edit-enrollment-id" name="id">
                    <input type="hidden" id="edit-course-item-id" name="course_item_id">
                    <input type="hidden" id="edit-course-fee" name="course_fee">
                    
                    <div class="mb-3">
                        <label class="form-label">Học viên</label>
                        <p class="form-control-plaintext fw-bold" id="edit-student-name"></p>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-enrollment-date" class="form-label">Ngày ghi danh</label>
                        <input type="date" class="form-control" id="edit-enrollment-date" name="enrollment_date" required>
                        <div class="invalid-feedback" id="edit-enrollment-date-error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-status" class="form-label">Trạng thái</label>
                        <select class="form-select" id="edit-status" name="status" required>
                            <option value="active">Đang học</option>
                            <option value="waiting">Danh sách chờ</option>
                            <option value="completed">Đã hoàn thành</option>
                            <option value="cancelled">Đã hủy</option>
                        </select>
                        <div class="invalid-feedback" id="edit-status-error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-discount-percentage" class="form-label">Giảm giá (%)</label>
                        <input type="number" class="form-control" id="edit-discount-percentage" name="discount_percentage" min="0" max="100" step="0.01" value="0">
                        <div class="invalid-feedback" id="edit-discount-percentage-error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-discount-amount" class="form-label">Giảm giá (VNĐ)</label>
                        <input type="number" class="form-control" id="edit-discount-amount" name="discount_amount" min="0" value="0">
                        <div class="invalid-feedback" id="edit-discount-amount-error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-final-fee-display" class="form-label">Học phí cuối cùng</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="edit-final-fee-display" readonly>
                            <span class="input-group-text">VNĐ</span>
                        </div>
                        <input type="hidden" id="edit-final-fee" name="final_fee">
                        <div class="invalid-feedback" id="edit-final-fee-error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-notes" class="form-label">Ghi chú</label>
                        <textarea class="form-control" id="edit-notes" name="notes" rows="3"></textarea>
                        <div class="invalid-feedback" id="edit-notes-error"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="save-enrollment-btn">
                    <i class="fas fa-save me-1"></i> Lưu thay đổi
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal tạo ghi danh mới -->
<div class="modal fade" id="createEnrollmentModal" tabindex="-1" aria-labelledby="createEnrollmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEnrollmentModalLabel">Tạo ghi danh mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="enrollmentCreateForm" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="create-student-id" class="form-label">Học viên</label>
                        <select class="form-select select2" id="create-student-id" name="student_id" data-placeholder="Chọn học viên..." required>
                            <option value="">-- Chọn học viên --</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">
                                    {{ $student->full_name }} - {{ $student->phone }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="create-student-id-error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create-course-item-id" class="form-label">Khóa học</label>
                        <select class="form-select" id="create-course-item-id" name="course_item_id" required>
                            <option value="">-- Chọn khóa học --</option>
                            @foreach($courseItems as $courseItem)
                                <option value="{{ $courseItem->id }}" data-fee="{{ $courseItem->fee }}">
                                    {{ $courseItem->name }} - {{ formatCurrency($courseItem->fee) }} đ
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="create-course-item-id-error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create-enrollment-date" class="form-label">Ngày ghi danh</label>
                        <input type="date" class="form-control" id="create-enrollment-date" name="enrollment_date" required value="{{ date('Y-m-d') }}">
                        <div class="invalid-feedback" id="create-enrollment-date-error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create-status" class="form-label">Trạng thái</label>
                        <select class="form-select" id="create-status" name="status" required>
                            <option value="active" selected>Đang học</option>
                            <option value="waiting">Danh sách chờ</option>
                            <option value="completed">Đã hoàn thành</option>
                        </select>
                        <div class="invalid-feedback" id="create-status-error"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="create-notes" class="form-label">Ghi chú</label>
                        <textarea class="form-control" id="create-notes" name="notes" rows="3"></textarea>
                        <div class="invalid-feedback" id="create-notes-error"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-primary" id="save-new-enrollment-btn">
                    <i class="fas fa-save me-1"></i> Tạo ghi danh
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Container cho toast messages -->
<div class="toast-container position-fixed bottom-0 end-0 p-3"></div>
@endsection

@section('styles')
<style>
    /* Fix cho Select2 trong trang enrollments */
    .select2-container {
        width: 100% !important;
    }
    
    .select2-container .select2-selection--single {
        height: 38px !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--single {
        padding: 0.375rem 2.25rem 0.375rem 0.75rem !important;
    }
    
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
        padding-left: 0 !important;
    }
    
    /* Đảm bảo dropdown hiển thị đúng */
    .select2-dropdown {
        z-index: 9999;
    }
</style>
@endsection

@push('scripts')
<script src="{{ asset('js/enrollment.js') }}"></script>
@endpush 
 