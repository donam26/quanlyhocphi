{{-- Component Form ghi danh học viên --}}

<div class="row">
    {{-- Thông tin khóa học --}}
    <div class="col-12">
        <h6 class="text-primary mb-3"><i class="fas fa-book me-2"></i>Thông tin khóa học</h6>
        
        {{-- Khóa học --}}
        <div class="mb-3">
            <label for="course_item_id" class="form-label">Khóa học <span class="text-danger">*</span></label>
            <select name="course_item_id" id="course_item_id" class="form-select" required>
                <option value="">-- Chọn khóa học --</option>
            </select>
            <div class="invalid-feedback" id="course-item-id-error"></div>
        </div>

        <div class="row">
            {{-- Ngày ghi danh --}}
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="enrollment_date" class="form-label">Ngày ghi danh <span class="text-danger">*</span></label>
                    <input type="text" name="enrollment_date" id="enrollment_date" class="form-control" 
                           value="{{ date('d/m/Y') }}" required placeholder="dd/mm/yyyy" 
                           data-inputmask="'mask': '99/99/9999'">
                    <div class="invalid-feedback" id="enrollment-date-error"></div>
                </div>
            </div>

            {{-- Trạng thái --}}
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="status" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                    <select name="status" id="status" class="form-select" required>
                        <option value="{{ \App\Enums\EnrollmentStatus::ACTIVE->value }}">{{ \App\Enums\EnrollmentStatus::ACTIVE->label() }}</option>
                        <option value="{{ \App\Enums\EnrollmentStatus::WAITING->value }}">{{ \App\Enums\EnrollmentStatus::WAITING->label() }}</option>
                        <option value="{{ \App\Enums\EnrollmentStatus::COMPLETED->value }}">{{ \App\Enums\EnrollmentStatus::COMPLETED->label() }}</option>
                        <option value="{{ \App\Enums\EnrollmentStatus::CANCELLED->value }}">{{ \App\Enums\EnrollmentStatus::CANCELLED->label() }}</option>
                    </select>
                    <div class="invalid-feedback" id="status-error"></div>
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
                    <label for="final_fee_display" class="form-label">Học phí cuối cùng</label>
                    <div class="input-group">
                        <input type="text" id="final_fee_display" class="form-control" readonly>
                        <span class="input-group-text">VNĐ</span>
                    </div>
                    <input type="hidden" name="final_fee" id="final_fee">
                    <div class="invalid-feedback" id="final-fee-error"></div>
                </div>
            </div>

            {{-- Chiết khấu phần trăm --}}
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="discount_percentage" class="form-label">Chiết khấu (%)</label>
                    <input type="number" name="discount_percentage" id="discount_percentage" class="form-control"
                           min="0" max="100" step="0.1" value="0">
                    <div class="invalid-feedback" id="discount-percentage-error"></div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Chiết khấu số tiền --}}
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="discount_amount" class="form-label">Chiết khấu (VNĐ)</label>
                    <input type="number" name="discount_amount" id="discount_amount" class="form-control"
                           min="0" step="1000" value="0">
                    <div class="invalid-feedback" id="discount-amount-error"></div>
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
            <label for="notes" class="form-label">Ghi chú</label>
            <textarea name="notes" id="notes" class="form-control" rows="3" 
                      placeholder="Nhập ghi chú về ghi danh (nếu có)"></textarea>
            <div class="invalid-feedback" id="notes-error"></div>
        </div>
    </div>
</div>

{{-- Script khởi tạo form --}}
<script>
$(document).ready(function() {
    // Khởi tạo input mask cho ngày ghi danh
    if (typeof Inputmask !== 'undefined') {
        Inputmask({
            mask: '99/99/9999',
            placeholder: 'dd/mm/yyyy',
            clearIncomplete: true
        }).mask('#enrollment_date');
    }
});
</script>
