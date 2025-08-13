@extends('layouts.app')

@section('title', 'Cập nhật thông tin học viên hàng loạt')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-upload me-2"></i>
                        Cập nhật thông tin học viên hàng loạt
                    </h3>
                </div>
                
                <div class="card-body">
                    <!-- Hướng dẫn -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle me-2"></i>Hướng dẫn sử dụng:</h5>
                        <ul class="mb-0">
                            <li><strong>Chế độ "Chỉ cập nhật":</strong> Chỉ cập nhật thông tin của học viên đã có trong hệ thống</li>
                            <li><strong>Chế độ "Tạo mới và cập nhật":</strong> Tạo mới học viên nếu chưa có, cập nhật nếu đã có</li>
                            <li><strong>Số điện thoại</strong> là trường bắt buộc để xác định học viên</li>
                            <li><strong>Chỉ điền</strong> những thông tin cần cập nhật, để trống nếu không muốn thay đổi</li>
                            <li>Tải template mẫu để xem định dạng chính xác</li>
                        </ul>
                    </div>

                    <!-- Form upload -->
                    <form id="updateForm" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="file" class="form-label">
                                        <i class="fas fa-file-excel me-1"></i>
                                        Chọn file Excel
                                    </label>
                                    <input type="file" class="form-control" id="file" name="file" 
                                           accept=".xlsx,.xls,.csv" required>
                                    <div class="form-text">
                                        Chấp nhận file: .xlsx, .xls, .csv (tối đa 10MB)
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="update_mode" class="form-label">
                                        <i class="fas fa-cogs me-1"></i>
                                        Chế độ cập nhật
                                    </label>
                                    <select class="form-select" id="update_mode" name="update_mode" required>
                                        <option value="update_only">Chỉ cập nhật học viên hiện có</option>
                                        <option value="create_and_update">Tạo mới và cập nhật</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-primary" id="previewBtn">
                                        <i class="fas fa-eye me-1"></i>
                                        Xem trước
                                    </button>
                                    
                                    <button type="submit" class="btn btn-success" id="submitBtn">
                                        <i class="fas fa-upload me-1"></i>
                                        Cập nhật thông tin
                                    </button>
                                    
                                    <a href="{{ route('students.update.template') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-download me-1"></i>
                                        Tải template mẫu
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-eye me-2"></i>
                        Xem trước dữ liệu
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="previewContent">
                        <!-- Nội dung preview sẽ được load ở đây -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="button" class="btn btn-success" id="confirmUpdateBtn">
                        <i class="fas fa-check me-1"></i>
                        Xác nhận cập nhật
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Result Modal -->
    <div class="modal fade" id="resultModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-chart-bar me-2"></i>
                        Kết quả cập nhật
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="resultContent">
                        <!-- Kết quả sẽ được hiển thị ở đây -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Xem trước dữ liệu
    $('#previewBtn').click(function() {
        const fileInput = $('#file')[0];
        
        if (!fileInput.files.length) {
            showAlert('warning', 'Vui lòng chọn file trước khi xem trước.');
            return;
        }

        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('_token', $('input[name="_token"]').val());

        $.ajax({
            url: '{{ route("students.update.preview") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#previewBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Đang xử lý...');
            },
            success: function(response) {
                if (response.success) {
                    displayPreview(response.preview, response.total_rows);
                    $('#previewModal').modal('show');
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showAlert('error', response?.message || 'Có lỗi xảy ra khi xem trước dữ liệu.');
            },
            complete: function() {
                $('#previewBtn').prop('disabled', false).html('<i class="fas fa-eye me-1"></i>Xem trước');
            }
        });
    });

    // Submit form cập nhật
    $('#updateForm').submit(function(e) {
        e.preventDefault();
        performUpdate();
    });

    // Xác nhận cập nhật từ modal preview
    $('#confirmUpdateBtn').click(function() {
        $('#previewModal').modal('hide');
        performUpdate();
    });

    function performUpdate() {
        const formData = new FormData($('#updateForm')[0]);

        $.ajax({
            url: '{{ route("students.update.import") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#submitBtn, #confirmUpdateBtn').prop('disabled', true)
                    .html('<i class="fas fa-spinner fa-spin me-1"></i>Đang cập nhật...');
            },
            success: function(response) {
                if (response.success) {
                    displayResult(response);
                    $('#resultModal').modal('show');
                    $('#updateForm')[0].reset();
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showAlert('error', response?.message || 'Có lỗi xảy ra khi cập nhật dữ liệu.');
            },
            complete: function() {
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-upload me-1"></i>Cập nhật thông tin');
                $('#confirmUpdateBtn').prop('disabled', false).html('<i class="fas fa-check me-1"></i>Xác nhận cập nhật');
            }
        });
    }

    function displayPreview(data, totalRows) {
        if (!data || data.length === 0) {
            $('#previewContent').html('<div class="alert alert-warning">Không có dữ liệu để hiển thị.</div>');
            return;
        }

        let html = `
            <div class="alert alert-info">
                <strong>Tổng số dòng:</strong> ${totalRows} học viên
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-dark">
        `;

        // Header
        if (data[0]) {
            html += '<tr>';
            Object.keys(data[0]).forEach(key => {
                html += `<th>${key}</th>`;
            });
            html += '</tr></thead><tbody>';
        }

        // Data rows (chỉ hiển thị 5 dòng đầu)
        for (let i = 1; i < Math.min(data.length, 6); i++) {
            html += '<tr>';
            Object.values(data[i]).forEach(value => {
                html += `<td>${value || '<em class="text-muted">Trống</em>'}</td>`;
            });
            html += '</tr>';
        }

        html += '</tbody></table></div>';
        
        if (totalRows > 5) {
            html += `<div class="text-muted"><em>Chỉ hiển thị 5 dòng đầu. Tổng cộng có ${totalRows} dòng dữ liệu.</em></div>`;
        }

        $('#previewContent').html(html);
    }

    function displayResult(response) {
        const { updated_count, skipped_count, errors } = response.data;
        
        let html = `
            <div class="row">
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h3>${updated_count}</h3>
                            <p class="mb-0">Đã cập nhật</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <h3>${skipped_count}</h3>
                            <p class="mb-0">Bỏ qua</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <h3>${errors.length}</h3>
                            <p class="mb-0">Lỗi</p>
                        </div>
                    </div>
                </div>
            </div>
        `;

        if (errors.length > 0) {
            html += `
                <div class="mt-3">
                    <h6>Chi tiết lỗi:</h6>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
            `;
            errors.forEach(error => {
                html += `<li>${error}</li>`;
            });
            html += '</ul></div></div>';
        }

        $('#resultContent').html(html);
    }

    function showAlert(type, message) {
        const alertClass = type === 'error' ? 'alert-danger' : `alert-${type}`;
        const icon = type === 'error' ? 'fas fa-exclamation-triangle' : 
                    type === 'success' ? 'fas fa-check-circle' : 'fas fa-info-circle';
        
        const alert = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('.card-body').prepend(alert);
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            $('.alert').fadeOut();
        }, 5000);
    }
});
</script>
@endpush
