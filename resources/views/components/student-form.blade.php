{{-- Component Form thống nhất cho tạo mới và chỉnh sửa học viên --}}
@php
    $prefix = $type ?? 'create'; // 'create' hoặc 'edit'
    $isEdit = $prefix === 'edit';
@endphp

<!-- Nav tabs -->
<ul class="nav nav-tabs" id="{{ $prefix }}-student-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="{{ $prefix }}-personal-tab" data-bs-toggle="tab"
                data-bs-target="#{{ $prefix }}-personal" type="button" role="tab"
                aria-controls="{{ $prefix }}-personal" aria-selected="true">
            <i class="fas fa-user me-2"></i>Thông tin cá nhân
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="{{ $prefix }}-invoice-tab" data-bs-toggle="tab"
                data-bs-target="#{{ $prefix }}-invoice" type="button" role="tab"
                aria-controls="{{ $prefix }}-invoice" aria-selected="false">
            <i class="fas fa-file-invoice me-2"></i>Thông tin hóa đơn
        </button>
    </li>
</ul>

<!-- Tab panes -->
<div class="tab-content mt-3" id="{{ $prefix }}-student-tab-content">
    <!-- Tab Thông tin cá nhân -->
    <div class="tab-pane fade show active" id="{{ $prefix }}-personal" role="tabpanel"
         aria-labelledby="{{ $prefix }}-personal-tab">
        <div class="row">
    {{-- Thông tin cá nhân --}}
    <div class="col-md-6">
        <h6 class="text-primary mb-3"><i class="fas fa-user me-2"></i>Thông tin c2á nhân</h6>
        
        {{-- Họ --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-first-name" class="form-label">Họ <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="{{ $prefix }}-first-name" name="first_name" required>
            <div class="invalid-feedback" id="{{ $prefix }}-first-name-error"></div>
        </div>

        {{-- Tên --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-last-name" class="form-label">Tên <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="{{ $prefix }}-last-name" name="last_name" required>
            <div class="invalid-feedback" id="{{ $prefix }}-last-name-error"></div>
        </div>

        {{-- Giới tính --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-gender" class="form-label">Giới tính</label>
            <select class="form-select" id="{{ $prefix }}-gender" name="gender">
                <option value="">Chọn giới tính</option>
                <option value="male">Nam</option>
                <option value="female">Nữ</option>
                <option value="other">Khác</option>
            </select>
            <div class="invalid-feedback" id="{{ $prefix }}-gender-error"></div>
        </div>

        {{-- Ngày sinh --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-date-of-birth" class="form-label">Ngày sinh</label>
            <input type="text" class="form-control" id="{{ $prefix }}-date-of-birth" name="date_of_birth" 
                   placeholder="dd/mm/yyyy" data-inputmask="'mask': '99/99/9999'">
            <div class="invalid-feedback" id="{{ $prefix }}-date-of-birth-error"></div>
        </div>

        {{-- Số điện thoại --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
            <input type="tel" class="form-control" id="{{ $prefix }}-phone" name="phone" required>
            <div class="invalid-feedback" id="{{ $prefix }}-phone-error"></div>
        </div>

        {{-- Email --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-email" class="form-label">Email</label>
            <input type="email" class="form-control" id="{{ $prefix }}-email" name="email">
            <div class="invalid-feedback" id="{{ $prefix }}-email-error"></div>
        </div>

        {{-- Tỉnh thành --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-province" class="form-label">Tỉnh thành</label>
            <select class="form-select select2" id="{{ $prefix }}-province" name="province_id">
                <option value="">Chọn tỉnh thành</option>
            </select>
            <div class="invalid-feedback" id="{{ $prefix }}-province-error"></div>
        </div>
    </div>

    {{-- Thông tin bổ sung --}}
    <div class="col-md-6">
        <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Thông tin bổ sung</h6>

        {{-- Nơi công tác hiện tại --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-current-workplace" class="form-label">Nơi công tác hiện tại</label>
            <input type="text" class="form-control" id="{{ $prefix }}-current-workplace" name="current_workplace">
            <div class="invalid-feedback" id="{{ $prefix }}-current-workplace-error"></div>
        </div>

        {{-- Kinh nghiệm kế toán --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-experience" class="form-label">Kinh nghiệm kế toán (năm)</label>
            <input type="number" class="form-control" id="{{ $prefix }}-experience" name="accounting_experience_years" min="0">
            <div class="invalid-feedback" id="{{ $prefix }}-experience-error"></div>
        </div>

        {{-- Nơi sinh --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-place-of-birth-select" class="form-label">Nơi sinh</label>
            <select class="form-select select2" id="{{ $prefix }}-place-of-birth-select">
                <option value="">Chọn nơi sinh</option>
            </select>
            <input type="hidden" id="{{ $prefix }}-place-of-birth" name="place_of_birth">
            <div class="invalid-feedback" id="{{ $prefix }}-place-of-birth-error"></div>
        </div>

        {{-- Dân tộc --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-nation-select" class="form-label">Dân tộc</label>
            <select class="form-select select2" id="{{ $prefix }}-nation-select">
                <option value="">Chọn dân tộc</option>
            </select>
            <input type="hidden" id="{{ $prefix }}-nation" name="nation">
            <div class="invalid-feedback" id="{{ $prefix }}-nation-error"></div>
        </div>

        {{-- Hồ sơ bản cứng --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-hard-copy-documents" class="form-label">Hồ sơ bản cứng</label>
            <select class="form-select" id="{{ $prefix }}-hard-copy-documents" name="hard_copy_documents">
                <option value="">Chọn trạng thái</option>
                <option value="submitted">Đã nộp</option>
                <option value="not_submitted">Chưa nộp</option>
            </select>
            <div class="invalid-feedback" id="{{ $prefix }}-hard-copy-documents-error"></div>
        </div>

        {{-- Bằng cấp --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-education-level" class="form-label">Bằng cấp</label>
            <select class="form-select" id="{{ $prefix }}-education-level" name="education_level">
                <option value="">Chọn bằng cấp</option>
                <option value="secondary">VB2</option>
                <option value="vocational">Trung cấp</option>
                <option value="associate">Cao đẳng</option>
                <option value="bachelor">Đại học</option>
                <option value="master">Thạc sĩ</option>
            </select>
            <div class="invalid-feedback" id="{{ $prefix }}-education-level-error"></div>
        </div>

        {{-- Chuyên môn đào tạo --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-training-specialization" class="form-label">Chuyên môn đào tạo</label>
            <input type="text" class="form-control" id="{{ $prefix }}-training-specialization" name="training_specialization">
            <div class="invalid-feedback" id="{{ $prefix }}-training-specialization-error"></div>
        </div>
    </div>
</div>

{{-- Ghi chú --}}
<div class="row">
    <div class="col-12">
        <h6 class="text-primary mb-3 mt-3"><i class="fas fa-sticky-note me-2"></i>Ghi chú</h6>
        <div class="mb-3">
            <label for="{{ $prefix }}-notes" class="form-label">Ghi chú</label>
            <textarea class="form-control" id="{{ $prefix }}-notes" name="notes" rows="3"
                      placeholder="Nhập ghi chú về học viên (nếu có)"></textarea>
            <div class="invalid-feedback" id="{{ $prefix }}-notes-error"></div>
        </div>
    </div>
</div>
        </div>

    <!-- Tab Thông tin hóa đơn -->
    <div class="tab-pane fade" id="{{ $prefix }}-invoice" role="tabpanel"
         aria-labelledby="{{ $prefix }}-invoice-tab">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Thông tin này sẽ được sử dụng khi xuất hóa đơn điện tử. Tất cả các trường đều không bắt buộc.
                </div>
            </div>
        </div>
        <div class="row">
    <div class="col-md-6">
        {{-- Tên đơn vị --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-company-name" class="form-label">Tên đơn vị</label>
            <input type="text" class="form-control" id="{{ $prefix }}-company-name" name="company_name"
                   placeholder="Nhập tên đơn vị (nếu có)">
            <div class="invalid-feedback" id="{{ $prefix }}-company-name-error"></div>
        </div>

        {{-- Mã số thuế --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-tax-code" class="form-label">Mã số thuế</label>
            <input type="text" class="form-control" id="{{ $prefix }}-tax-code" name="tax_code"
                   placeholder="Nhập mã số thuế (nếu có)">
            <div class="invalid-feedback" id="{{ $prefix }}-tax-code-error"></div>
        </div>

        {{-- Email nhận hóa đơn --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-invoice-email" class="form-label">Email nhận hóa đơn</label>
            <input type="email" class="form-control" id="{{ $prefix }}-invoice-email" name="invoice_email"
                   placeholder="Nhập email nhận hóa đơn (nếu có)">
            <div class="invalid-feedback" id="{{ $prefix }}-invoice-email-error"></div>
        </div>
    </div>
    <div class="col-md-6">
        {{-- Địa chỉ đơn vị --}}
        <div class="mb-3">
            <label for="{{ $prefix }}-company-address" class="form-label">Địa chỉ đơn vị nhận hóa đơn</label>
            <textarea class="form-control" id="{{ $prefix }}-company-address" name="company_address" rows="3"
                      placeholder="Nhập địa chỉ đơn vị (nếu có)"></textarea>
            <div class="invalid-feedback" id="{{ $prefix }}-company-address-error"></div>
        </div>
    </div>
</div>
        </div>
    </div>
</div>

{{-- CSS cho tab --}}
<style>
.nav-tabs .nav-link {
    color: #6c757d;
    border: 1px solid transparent;
    border-top-left-radius: 0.375rem;
    border-top-right-radius: 0.375rem;
}

.nav-tabs .nav-link:hover {
    border-color: #e9ecef #e9ecef #dee2e6;
    isolation: isolate;
}

.nav-tabs .nav-link.active {
    color: #495057;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}

.tab-content {
    border: 1px solid #dee2e6;
    border-top: none;
    border-radius: 0 0 0.375rem 0.375rem;
    padding: 1rem;
    background-color: #fff;
    max-height: 50vh;
    overflow-y: auto;
}

.tab-pane {
    min-height: auto;
}

/* Đảm bảo modal có kích thước phù hợp */
.modal-dialog {
    max-height: 90vh;
}

.modal-content {
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.modal-body {
    flex: 1;
    overflow-y: auto;
    max-height: calc(90vh - 120px); /* Trừ đi header và footer */
}
</style>

{{-- Script khởi tạo form --}}
<script>
$(document).ready(function() {
    // Khởi tạo input mask cho ngày sinh
    if (typeof Inputmask !== 'undefined') {
        Inputmask({
            mask: '99/99/9999',
            placeholder: 'dd/mm/yyyy',
            clearIncomplete: true
        }).mask('#{{ $prefix }}-date-of-birth');
    }

    // Khởi tạo tab và Select2 khi modal được hiển thị
    $('#{{ $prefix === 'create' ? 'createStudentModal' : 'editStudentModal' }}').on('shown.bs.modal', function() {
        // Khởi tạo Bootstrap tabs
        var triggerTabList = [].slice.call(document.querySelectorAll('#{{ $prefix }}-student-tabs button'));
        triggerTabList.forEach(function (triggerEl) {
            var tabTrigger = new bootstrap.Tab(triggerEl);
        });

        // Đảm bảo tab đầu tiên được active
        $('#{{ $prefix }}-personal-tab').tab('show');

        if (window.studentFormComponent) {
            window.studentFormComponent.initializeFormComponents('{{ $prefix }}');
        }
    });
});
</script>
