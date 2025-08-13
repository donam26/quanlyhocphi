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

                {{-- Nội dung chi tiết --}}
                <div id="student-details" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3"><i class="fas fa-user me-2"></i>Thông tin cá nhân</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Họ và tên:</th>
                                    <td><strong id="student-name"></strong></td>
                                </tr>
                                <tr>
                                    <th>Mã học viên:</th>
                                    <td><span class="badge bg-primary" id="student-id"></span></td>
                                </tr>
                                <tr>
                                    <th>Giới tính:</th>
                                    <td id="student-gender"></td>
                                </tr>
                                <tr>
                                    <th>Ngày sinh:</th>
                                    <td id="student-dob"></td>
                                </tr>
                                <tr>
                                    <th>Số điện thoại:</th>
                                    <td id="student-phone"></td>
                                </tr>
                                <tr>
                                    <th>Email:</th>
                                    <td id="student-email"></td>
                                </tr>
                                <tr>
                                    <th>Tỉnh thành:</th>
                                    <td id="student-address"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3"><i class="fas fa-briefcase me-2"></i>Thông tin nghề nghiệp</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <th width="40%">Nơi công tác:</th>
                                    <td id="student-workplace"></td>
                                </tr>
                                <tr>
                                    <th>Kinh nghiệm kế toán:</th>
                                    <td id="student-experience"></td>
                                </tr>
                                <tr>
                                    <th>Nơi sinh:</th>
                                    <td id="student-place-of-birth"></td>
                                </tr>
                                <tr>
                                    <th>Dân tộc:</th>
                                    <td id="student-nation"></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- Thông tin bổ sung --}}
                    <div id="student-custom-fields-section" style="display: none;">
                        <h6 class="text-primary mb-3 mt-4"><i class="fas fa-info-circle me-2"></i>Thông tin bổ sung</h6>
                        <div id="student-custom-fields"></div>
                    </div>

                    {{-- Ghi chú --}}
                    <div id="student-notes-section" style="display: none;">
                        <h6 class="text-primary mb-3 mt-4"><i class="fas fa-sticky-note me-2"></i>Ghi chú</h6>
                        <div class="alert alert-info">
                            <p id="student-notes" class="mb-0"></p>
                        </div>
                    </div>

                    {{-- Khóa học đã đăng ký --}}
                    <div id="student-enrollments-section" style="display: none;">
                        <h6 class="text-primary mb-3 mt-4"><i class="fas fa-graduation-cap me-2"></i>Khóa học đã đăng ký</h6>
                        <div id="student-enrollments"></div>
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
                <a href="#" class="btn btn-success" id="btn-add-enrollment">
                    <i class="fas fa-plus me-1"></i>Thêm khóa học
                </a>
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

{{-- Toast Container --}}
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11000;">
    {{-- Toasts will be dynamically added here --}}
</div>
