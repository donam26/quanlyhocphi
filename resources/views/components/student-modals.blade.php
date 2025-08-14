{{-- Component Modal thống nhất cho học viên --}}

{{-- Modal Xem Chi tiết Học viên --}}
<div class="modal fade" id="viewStudentModal" tabindex="-1" aria-labelledby="viewStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewStudentModalLabel">
                    <i class="fas fa-user me-2"></i>Thông tin học viên
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Loading --}}
                <div id="student-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2 text-muted">Đang tải thông tin học viên...</p>
                </div>

                {{-- Nội dung chi tiết với format giống edit --}}
                <div id="student-details" style="display: none;">
                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" id="view-student-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="view-personal-tab" data-bs-toggle="tab"
                                data-bs-target="#view-personal" type="button" role="tab"
                                aria-controls="view-personal" aria-selected="true">
                                <i class="fas fa-user me-2"></i>Thông tin cá nhân
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="view-additional-tab" data-bs-toggle="tab"
                                data-bs-target="#view-additional" type="button" role="tab"
                                aria-controls="view-additional" aria-selected="false">
                                <i class="fas fa-briefcase me-2"></i>Thông tin bổ sung
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="view-invoice-tab" data-bs-toggle="tab"
                                data-bs-target="#view-invoice" type="button" role="tab"
                                aria-controls="view-invoice" aria-selected="false">
                                <i class="fas fa-file-invoice me-2"></i>Thông tin hóa đơn
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="view-enrollment-tab" data-bs-toggle="tab"
                                data-bs-target="#view-enrollment" type="button" role="tab"
                                aria-controls="view-enrollment" aria-selected="false">
                                <i class="fas fa-graduation-cap me-2"></i>Thông tin ghi danh
                            </button>
                        </li>
                    </ul>

                    <!-- Tab panes -->
                    <div class="tab-content mt-3" id="view-student-tab-content">
                        <!-- Tab Thông tin cá nhân -->
                        <div class="tab-pane fade show active" id="view-personal" role="tabpanel"
                            aria-labelledby="view-personal-tab">
                            <div class="row">
                                {{-- Cột trái: Thông tin cá nhân cơ bản --}}
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3"><i class="fas fa-user me-2"></i>Thông tin cá nhân</h6>

                                    {{-- Họ --}}
                                    <div class="mb-3">
                                        <label class="form-label">Họ</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-first-name"></div>
                                    </div>

                                    {{-- Tên --}}
                                    <div class="mb-3">
                                        <label class="form-label">Tên</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-last-name"></div>
                                    </div>

                                    {{-- Giới tính --}}
                                    <div class="mb-3">
                                        <label class="form-label">Giới tính</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-gender"></div>
                                    </div>

                                    {{-- Ngày sinh --}}
                                    <div class="mb-3">
                                        <label class="form-label">Ngày sinh</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-date-of-birth"></div>
                                    </div>
                                </div>

                                {{-- Cột phải: Thông tin liên hệ --}}
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3"><i class="fas fa-phone me-2"></i>Thông tin liên hệ</h6>

                                    {{-- Số điện thoại --}}
                                    <div class="mb-3">
                                        <label class="form-label">Số điện thoại</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-phone"></div>
                                    </div>

                                    {{-- Email --}}
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-email"></div>
                                    </div>

                                    {{-- Địa chỉ --}}
                                    <div class="mb-3">
                                        <label class="form-label">Địa chỉ</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-address"></div>
                                    </div>

                                    {{-- Tỉnh thành --}}
                                    <div class="mb-3">
                                        <label class="form-label">Tỉnh thành</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-province"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Thông tin chi tiết --}}
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-primary mb-3 mt-3"><i class="fas fa-id-card me-2"></i>Thông tin chi tiết</h6>
                                </div>
                                <div class="col-md-4">
                                    {{-- Nơi sinh --}}
                                    <div class="mb-3">
                                        <label class="form-label">Nơi sinh</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-place-of-birth"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    {{-- Dân tộc --}}
                                    <div class="mb-3">
                                        <label class="form-label">Dân tộc</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-nation"></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    {{-- Số CCCD --}}
                                    <div class="mb-3">
                                        <label class="form-label">Số CCCD</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-id-number"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Ghi chú --}}
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Ghi chú</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-notes" style="min-height: 60px;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Thông tin bổ sung (chỉ dành cho Kế toán trưởng) -->
                        <div class="tab-pane fade" id="view-additional" role="tabpanel" aria-labelledby="view-additional-tab">
                            <div class="row">
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Thông tin bổ sung cho lớp Kế toán trưởng</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    {{-- Nơi công tác hiện tại --}}
                                    <div class="mb-3">
                                        <label class="form-label">Nơi công tác hiện tại</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-workplace"></div>
                                    </div>

                                    {{-- Số năm kinh nghiệm làm kế toán --}}
                                    <div class="mb-3">
                                        <label class="form-label">Số năm kinh nghiệm làm kế toán</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-experience"></div>
                                    </div>

                                    {{-- Chuyên môn đào tạo --}}
                                    <div class="mb-3">
                                        <label class="form-label">Chuyên môn đào tạo</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-training-specialization"></div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    {{-- Tài liệu bản cứng --}}
                                    <div class="mb-3">
                                        <label class="form-label">Tài liệu bản cứng</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-hard-copy-documents"></div>
                                    </div>

                                    {{-- Trình độ học vấn --}}
                                    <div class="mb-3">
                                        <label class="form-label">Trình độ học vấn</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-education-level"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Thông tin hóa đơn -->
                        <div class="tab-pane fade" id="view-invoice" role="tabpanel" aria-labelledby="view-invoice-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3"><i class="fas fa-building me-2"></i>Thông tin công ty</h6>

                                    {{-- Tên công ty --}}
                                    <div class="mb-3">
                                        <label class="form-label">Tên công ty</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-company-name"></div>
                                    </div>

                                    {{-- Mã số thuế --}}
                                    <div class="mb-3">
                                        <label class="form-label">Mã số thuế</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-tax-code"></div>
                                    </div>

                                    {{-- Địa chỉ công ty --}}
                                    <div class="mb-3">
                                        <label class="form-label">Địa chỉ công ty</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-company-address"></div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3"><i class="fas fa-envelope me-2"></i>Thông tin liên hệ</h6>

                                    {{-- Email nhận hóa đơn --}}
                                    <div class="mb-3">
                                        <label class="form-label">Email nhận hóa đơn</label>
                                        <div class="form-control-plaintext border rounded px-3 py-2 bg-light" id="view-invoice-email"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Thông tin ghi danh -->
                        <div class="tab-pane fade" id="view-enrollment" role="tabpanel" aria-labelledby="view-enrollment-tab">
                            <div id="enrollment-loading" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Đang tải...</span>
                                </div>
                                <p class="mt-2 text-muted">Đang tải thông tin ghi danh...</p>
                            </div>

                            <div id="enrollment-content" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="text-primary mb-0"><i class="fas fa-graduation-cap me-2"></i>Danh sách ghi danh</h6>
                                    <button type="button" class="btn btn-sm btn-success" data-student-action="enroll" data-student-id="">
                                        <i class="fas fa-plus me-1"></i>Thêm ghi danh mới
                                    </button>
                                </div>

                                <div id="enrollment-list">
                                    <!-- Danh sách ghi danh sẽ được load động -->
                                </div>

                                <div id="no-enrollments" class="text-center py-4" style="display: none;">
                                    <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">Chưa có ghi danh nào</h6>
                                    <p class="text-muted">Học viên chưa đăng ký khóa học nào</p>
                                    <button type="button" class="btn btn-primary" data-student-action="enroll" data-student-id="">
                                        <i class="fas fa-plus me-1"></i>Thêm ghi danh đầu tiên
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Đóng
                </button>
                <button type="button" class="btn btn-primary" data-student-action="edit" data-student-id="">
                    <i class="fas fa-edit me-1"></i>Chỉnh sửa
                </button>
                <button type="button" class="btn btn-success" data-student-action="enroll" data-student-id="">
                    <i class="fas fa-plus me-1"></i>Thêm khóa học
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Tạo mới Học viên --}}
<div class="modal fade" id="createStudentModal" tabindex="-1" aria-labelledby="createStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createStudentModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Thêm học viên mới
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="studentCreateForm" enctype="multipart/form-data">
                    @csrf
                    @include('components.student-form', ['type' => 'create'])
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Hủy
                </button>
                <button type="submit" form="studentCreateForm" class="btn btn-primary" id="save-new-student-btn">
                    <i class="fas fa-save me-1"></i>Lưu học viên
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Chỉnh sửa Học viên --}}
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-labelledby="editStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStudentModalLabel">
                    <i class="fas fa-user-edit me-2"></i>Chỉnh sửa thông tin học viên
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Loading --}}
                <div id="edit-student-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2 text-muted">Đang tải thông tin học viên...</p>
                </div>

                {{-- Form chỉnh sửa --}}
                <div id="edit-student-form" style="display: none;">
                    <form id="studentEditForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="edit-student-id" name="id">
                        @include('components.student-form', ['type' => 'edit'])
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Hủy
                </button>
                <button type="submit" form="studentEditForm" class="btn btn-primary" id="save-student-btn">
                    <i class="fas fa-save me-1"></i>Lưu thay đổi
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Ghi danh Học viên --}}
<div class="modal fade" id="enrollStudentModal" tabindex="-1" aria-labelledby="enrollStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="enrollStudentModalLabel">
                    <i class="fas fa-user-graduate me-2"></i>Ghi danh học viên
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Loading --}}
                <div id="enroll-student-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2 text-muted">Đang tải thông tin học viên...</p>
                </div>

                {{-- Form ghi danh --}}
                <div id="enroll-student-form" style="display: none;">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Ghi danh học viên: <strong id="enroll-student-name"></strong>
                    </div>
                    
                    <form id="enrollmentForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="enroll-student-id" name="student_id">
                        @include('components.enrollment-form')
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Hủy
                </button>
                <button type="submit" form="enrollmentForm" class="btn btn-success" id="enroll-btn">
                    <i class="fas fa-user-plus me-1"></i>Đăng ký
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Chỉnh sửa Ghi danh --}}
<div class="modal fade" id="editEnrollmentModal" tabindex="-1" aria-labelledby="editEnrollmentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEnrollmentModalLabel">
                    <i class="fas fa-edit me-2"></i>Chỉnh sửa thông tin ghi danh
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Loading --}}
                <div id="edit-enrollment-loading" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                    <p class="mt-2 text-muted">Đang tải thông tin ghi danh...</p>
                </div>

                {{-- Form chỉnh sửa ghi danh --}}
                <div id="edit-enrollment-form" class="d-none">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Chỉnh sửa ghi danh: <strong id="edit-enrollment-course-name"></strong>
                        <br>Học viên: <strong id="edit-enrollment-student-name"></strong>
                    </div>

                    <form id="enrollmentEditForm" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="edit-enrollment-id" name="enrollment_id">

                        <div class="row">
                            {{-- Thông tin khóa học --}}
                            <div class="col-12">
                                <h6 class="text-primary mb-3"><i class="fas fa-book me-2"></i>Thông tin khóa học</h6>

                                <div class="row">
                                    {{-- Ngày ghi danh --}}
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="edit-enrollment-date" class="form-label">Ngày ghi danh <span class="text-danger">*</span></label>
                                            <input type="text" name="enrollment_date" id="edit-enrollment-date" class="form-control"
                                                   required placeholder="dd/mm/yyyy" data-inputmask="'mask': '99/99/9999'">
                                            <div class="invalid-feedback" id="edit-enrollment-date-error"></div>
                                        </div>
                                    </div>

                                    {{-- Trạng thái --}}
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="edit-status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                                            <select name="status" id="edit-status" class="form-select" required>
                                                <option value="{{ \App\Enums\EnrollmentStatus::ACTIVE->value }}">{{ \App\Enums\EnrollmentStatus::ACTIVE->label() }}</option>
                                                <option value="{{ \App\Enums\EnrollmentStatus::WAITING->value }}">{{ \App\Enums\EnrollmentStatus::WAITING->label() }}</option>
                                                <option value="{{ \App\Enums\EnrollmentStatus::COMPLETED->value }}">{{ \App\Enums\EnrollmentStatus::COMPLETED->label() }}</option>
                                                <option value="{{ \App\Enums\EnrollmentStatus::CANCELLED->value }}">{{ \App\Enums\EnrollmentStatus::CANCELLED->label() }}</option>
                                            </select>
                                            <div class="invalid-feedback" id="edit-status-error"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Thông tin học phí --}}
                            <div class="col-12">
                                <h6 class="text-primary mb-3 mt-3"><i class="fas fa-money-bill me-2"></i>Thông tin học phí</h6>

                                <div class="row">
                                    {{-- Học phí cuối cùng --}}
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="edit-final-fee-display" class="form-label">Học phí cuối cùng</label>
                                            <div class="input-group">
                                                <input type="text" id="edit-final-fee-display" class="form-control" readonly>
                                                <span class="input-group-text">VNĐ</span>
                                            </div>
                                            <input type="hidden" name="final_fee" id="edit-final-fee">
                                            <div class="invalid-feedback" id="edit-final-fee-error"></div>
                                        </div>
                                    </div>

                                    {{-- Chiết khấu phần trăm --}}
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="edit-discount-percentage" class="form-label">Chiết khấu (%)</label>
                                            <div class="input-group">
                                                <input type="number" name="discount_percentage" id="edit-discount-percentage"
                                                       class="form-control" min="0" max="100" step="0.01">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="invalid-feedback" id="edit-discount-percentage-error"></div>
                                        </div>
                                    </div>

                                    {{-- Chiết khấu số tiền --}}
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="edit-discount-amount" class="form-label">Chiết khấu (VNĐ)</label>
                                            <input type="number" name="discount_amount" id="edit-discount-amount"
                                                   class="form-control" min="0" step="1000">
                                            <div class="invalid-feedback" id="edit-discount-amount-error"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Ghi chú --}}
                            <div class="col-12">
                                <h6 class="text-primary mb-3 mt-3"><i class="fas fa-sticky-note me-2"></i>Ghi chú</h6>

                                <div class="mb-3">
                                    <label for="edit-enrollment-notes" class="form-label">Ghi chú</label>
                                    <textarea name="notes" id="edit-enrollment-notes" class="form-control" rows="3"
                                              placeholder="Nhập ghi chú về ghi danh (nếu có)"></textarea>
                                    <div class="invalid-feedback" id="edit-enrollment-notes-error"></div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Hủy
                </button>
                <button type="submit" form="enrollmentEditForm" class="btn btn-primary" id="save-enrollment-btn">
                    <i class="fas fa-save me-1"></i>Lưu thay đổi
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Toast Container --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11000;">
    {{-- Toasts will be dynamically added here --}}
</div>
