@extends('layouts.app')
@section('page-title', 'Danh sách chờ')

@section('breadcrumb')
<li class="breadcrumb-item active">Danh sách chờ</li>
@endsection

@section('page-actions')
<button class="btn btn-sm btn-info me-2" id="expand-all">
    <i class="fas fa-expand-arrows-alt"></i> Mở rộng tất cả
</button>
<button class="btn btn-sm btn-secondary me-2" id="collapse-all">
    <i class="fas fa-compress-arrows-alt"></i> Thu gọn tất cả
</button>
@endsection

@section('styles')
<link rel="stylesheet" href="{{ asset('css/course-tree.css') }}">
<style>
.waiting-count {
    background-color: #ffc107 !important;
    color: #000 !important;
    font-weight: bold;
    min-width: 20px;
    text-align: center;
}

.waiting-tree .tree-item {
    cursor: pointer;
    transition: all 0.3s ease;
}

.waiting-tree .tree-item:hover {
    background-color: #e3f2fd;
    transform: translateX(5px);
}

.waiting-tree .tree-item.selected {
    background-color: #bbdefb;
    border-left-color: #2196f3;
    box-shadow: 0 2px 8px rgba(33, 150, 243, 0.3);
}

/* Layout 2 cột cho trang waiting */
.waiting-layout {
    display: flex;
    gap: 20px;
    min-height: 500px;
}

.waiting-tree-column {
    flex: 0 0 400px;
    max-width: 400px;
}

.waiting-students-column {
    flex: 1;
    min-width: 0;
}

.waiting-tree-card {
    height: 600px;
    display: flex;
    flex-direction: column;
}

.waiting-tree-card .card-body {
    flex: 1;
    overflow: hidden;
    padding: 15px;
}

.waiting-tree-container {
    height: 100%;
    overflow-y: auto;
    padding-right: 10px;
}

.waiting-students-card {
    height: 600px;
    display: flex;
    flex-direction: column;
}

.waiting-students-card .card-body {
    flex: 1;
    overflow: hidden;
    padding: 15px;
}

.student-list-container {
    height: 100%;
    overflow-y: auto;
    padding-right: 10px;
}

.student-card {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    background: #fff;
    transition: all 0.2s ease;
}

.student-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-color: #2196f3;
}

.student-card.selected {
    border-color: #4caf50;
    background-color: #f1f8e9;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #666;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.empty-state i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 20px;
}

.bulk-actions {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    display: none;
}

.bulk-actions.show {
    display: block;
}

/* Responsive cho layout 2 cột */
@media (max-width: 1200px) {
    .waiting-tree-column {
        flex: 0 0 350px;
        max-width: 350px;
    }
}

@media (max-width: 992px) {
    .waiting-layout {
        flex-direction: column;
    }
    
    .waiting-tree-column {
        flex: none;
        max-width: none;
    }
    
    .waiting-tree-card,
    .waiting-students-card {
        height: 400px;
    }
}
</style>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        @if($rootItems->isEmpty())
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                Chưa có ngành học nào được tạo. Hãy tạo ngành học đầu tiên.
            </div>
        @else
            <div class="tab-and-search-container">
                <!-- Tab navigation -->
                <div class="tab-container">
                    <ul class="nav nav-tabs course-tabs" id="courseTab" role="tablist">
                        @foreach($rootItems as $index => $rootItem)
                            <li class="nav-item" role="presentation">
                                <button class="nav-link @if(($currentRootItem && $currentRootItem->id == $rootItem->id) || (!$currentRootItem && $index === 0)) active @endif" 
                                        id="tab-{{ $rootItem->id }}" 
                                        data-bs-toggle="tab" 
                                        data-bs-target="#content-{{ $rootItem->id }}" 
                                        type="button" 
                                        role="tab" 
                                        aria-controls="content-{{ $rootItem->id }}" 
                                        aria-selected="@if(($currentRootItem && $currentRootItem->id == $rootItem->id) || (!$currentRootItem && $index === 0)) true @else false @endif">
                                    {{ $rootItem->name }}
                                    <span class="waiting-count badge ms-2" id="tab-count-{{ $rootItem->id }}">0</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
                
                <!-- Search box -->
                <div class="search-container">
                    <input type="text" id="student-search" class="form-control" placeholder="Tìm kiếm học viên..." autocomplete="off">
                    <span class="search-clear"><i class="fas fa-times-circle"></i></span>
                </div>
            </div>
            
            <!-- Tab content -->
            <div class="tab-content" id="courseTabContent">
                @foreach($rootItems as $index => $rootItem)
                    <div class="tab-pane fade @if(($currentRootItem && $currentRootItem->id == $rootItem->id) || (!$currentRootItem && $index === 0)) show active @endif" 
                         id="content-{{ $rootItem->id }}" 
                         role="tabpanel" 
                         aria-labelledby="tab-{{ $rootItem->id }}"
                         data-root-id="{{ $rootItem->id }}">
                        
                        <div class="waiting-layout">
                            <!-- Cây khóa học bên trái -->
                            <div class="waiting-tree-column">
                                <div class="card waiting-tree-card">
                                    <div class="card-header">
                                        <h6 class="mb-0">
                                            <i class="fas fa-sitemap"></i> 
                                            Khóa học - {{ $rootItem->name }}
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="waiting-tree-container">
                                            <ul class="course-tree waiting-tree">
                                                @if($currentRootItem && $currentRootItem->id == $rootItem->id)
                                                    {{-- Nếu đang ở tab cụ thể, chỉ hiển thị các con trực tiếp --}}
                                                    @foreach($rootItem->children->sortBy('order_index') as $childItem)
                                                        <li>
                                                            <div class="tree-item level-2 waiting-course-item {{ $childItem->active ? 'active' : 'inactive' }}" 
                                                                 data-id="{{ $childItem->id }}" 
                                                                 data-course-name="{{ $childItem->name }}">
                                                                <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#waiting-children-{{ $childItem->id }}">
                                                                    <i class="fas fa-minus-circle"></i>
                                                                </span>
                                                                <span class="item-icon"><i class="fas fa-book"></i></span>
                                                                <span class="course-link">{{ $childItem->name }}</span>
                                                                <span class="waiting-count badge ms-auto" id="waiting-count-{{ $childItem->id }}">0</span>
                                                            </div>
                                                            @include('course-items.partials.waiting-children-tree', ['children' => $childItem->children, 'parentId' => $childItem->id])
                                                        </li>
                                                    @endforeach
                                                @else
                                                    {{-- Hiển thị đầy đủ cả nút gốc và con --}}
                                                    <li>
                                                        <div class="tree-item level-1 waiting-course-item {{ $rootItem->active ? 'active' : 'inactive' }}" 
                                                             data-id="{{ $rootItem->id }}" 
                                                             data-course-name="{{ $rootItem->name }}">
                                                            <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#waiting-tab-children-{{ $rootItem->id }}">
                                                                <i class="fas fa-minus-circle"></i>
                                                            </span>
                                                            <span class="item-icon"><i class="fas fa-graduation-cap"></i></span>
                                                            <span class="course-link">{{ $rootItem->name }}</span>
                                                            <span class="waiting-count badge ms-auto" id="waiting-count-{{ $rootItem->id }}">0</span>
                                                        </div>
                                                        @include('course-items.partials.waiting-children-tree', ['children' => $rootItem->children, 'parentId' => $rootItem->id, 'tabPrefix' => 'waiting-tab-'])
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Danh sách học viên chờ bên phải -->
                            <div class="waiting-students-column">
                                <div class="card waiting-students-card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <i class="fas fa-users"></i> 
                                            Học viên đang chờ: <span id="selected-course-name-{{ $rootItem->id }}">Chọn khóa học</span>
                                        </h6>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-success add-student-to-waiting" data-root-id="{{ $rootItem->id }}">
                                                <i class="fas fa-plus"></i> Học viên
                                            </button>
                                            <button type="button" class="btn btn-outline-primary refresh-waiting-list" data-root-id="{{ $rootItem->id }}">
                                                <i class="fas fa-sync-alt"></i> Làm mới
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <!-- Bulk actions -->
                                        <div class="bulk-actions" id="bulk-actions-{{ $rootItem->id }}">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span>
                                                    <strong id="selected-count-{{ $rootItem->id }}">0</strong> học viên được chọn
                                                </span>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-success bulk-confirm-waiting" data-root-id="{{ $rootItem->id }}">
                                                        <i class="fas fa-check-double"></i> Xác nhận hàng loạt
                                                    </button>
                                                    <button type="button" class="btn btn-secondary clear-selection" data-root-id="{{ $rootItem->id }}">
                                                        <i class="fas fa-times"></i> Bỏ chọn
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div id="waiting-students-container-{{ $rootItem->id }}" class="student-list-container">
                                            <div class="empty-state">
                                                <i class="fas fa-hand-pointer"></i>
                                                <h5>Chọn một khóa học</h5>
                                                <p class="text-muted">Chọn một khóa học từ cây bên trái để xem danh sách học viên đang chờ</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<!-- Modal xác nhận học viên từ danh sách chờ -->
<div class="modal fade" id="confirmWaitingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Xác nhận học viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Bạn có chắc chắn muốn xác nhận học viên <strong id="confirm-student-name"></strong>?</p>
                
                <div class="mb-3">
                    <label for="confirm-course-select" class="form-label">Chuyển vào khóa học</label>
                    <select class="form-select select2" id="confirm-course-select">
                        <option value="">Giữ nguyên khóa học hiện tại</option>
                    </select>
                    <div class="form-text">Chọn khóa học khác nếu muốn chuyển học viên</div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm-discount" class="form-label">Chiết khấu (%)</label>
                    <input type="number" class="form-control" id="confirm-discount" min="0" max="100" value="0">
                    <div class="form-text">Nhập phần trăm chiết khấu (0-100)</div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm-notes" class="form-label">Ghi chú</label>
                    <textarea class="form-control" id="confirm-notes" rows="2" placeholder="Ghi chú thêm (tùy chọn)"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success" id="confirm-waiting-btn">
                    <i class="fas fa-check"></i> Xác nhận
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal xem chi tiết học viên -->
<div class="modal fade" id="viewStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết học viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center" id="student-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Đang tải...</span>
                    </div>
                </div>
                <div id="student-details" style="display: none;">
                    <p>Chi tiết học viên sẽ được hiển thị ở đây</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal thêm học viên vào danh sách chờ -->
<div class="modal fade" id="addStudentToWaitingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm học viên vào danh sách chờ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Tìm học viên</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="student-search-input" class="form-label">Tìm kiếm học viên</label>
                                    <input type="text" class="form-control" id="student-search-input" 
                                           placeholder="Nhập tên hoặc số điện thoại...">
                                </div>
                                <div id="student-search-results" class="list-group" style="max-height: 300px; overflow-y: auto;">
                                    <div class="text-center text-muted py-3">
                                        Nhập từ khóa để tìm kiếm học viên
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Thông tin đăng ký</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="selected-student-info" class="form-label">Học viên đã chọn</label>
                                    <div id="selected-student-info" class="alert alert-info">
                                        Chưa chọn học viên nào
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="waiting-course-select" class="form-label">Khóa học đăng ký</label>
                                    <select class="form-select select2" id="waiting-course-select">
                                        <option value="">Chọn khóa học</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="waiting-notes" class="form-label">Ghi chú</label>
                                    <textarea class="form-control" id="waiting-notes" rows="3" 
                                              placeholder="Ghi chú về đăng ký (tùy chọn)"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-success" id="add-to-waiting-btn" disabled>
                    <i class="fas fa-plus"></i> Thêm vào danh sách chờ
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let selectedCourseId = null;
    let selectedCourseName = '';
    let currentRootId = null;
    
    // Xử lý expand/collapse all
    $('#expand-all').on('click', function() {
        $('.collapse').addClass('show');
        $('.toggle-icon i').removeClass('fa-plus-circle').addClass('fa-minus-circle');
    });
    
    $('#collapse-all').on('click', function() {
        $('.collapse').removeClass('show');
        $('.toggle-icon i').removeClass('fa-minus-circle').addClass('fa-plus-circle');
    });
    
    // Xử lý toggle icon khi collapse/expand
    $(document).on('shown.bs.collapse', '.collapse', function() {
        $(this).prev().find('.toggle-icon i').removeClass('fa-plus-circle').addClass('fa-minus-circle');
    });
    
    $(document).on('hidden.bs.collapse', '.collapse', function() {
        $(this).prev().find('.toggle-icon i').removeClass('fa-minus-circle').addClass('fa-plus-circle');
    });
    
    // Load số lượng chờ cho tất cả khóa học khi trang load
    loadAllWaitingCounts();
    
    // Xử lý click vào khóa học trong danh sách chờ
    $(document).on('click', '.waiting-course-item', function() {
        const rootId = $(this).closest('.tab-pane').data('root-id');
        
        // Bỏ chọn tất cả trong tab hiện tại
        $(`.tab-pane[data-root-id="${rootId}"] .waiting-course-item`).removeClass('selected');
        $(this).addClass('selected');
        
        selectedCourseId = $(this).data('id');
        selectedCourseName = $(this).data('course-name');
        currentRootId = rootId;
        
        $(`#selected-course-name-${rootId}`).text(selectedCourseName);
        loadWaitingStudents(selectedCourseId, rootId);
    });
    
    // Load danh sách học viên chờ
    function loadWaitingStudents(courseId, rootId) {
        $(`#waiting-students-container-${rootId}`).html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <p class="mt-2">Đang tải danh sách học viên...</p>
            </div>
        `);
        
        $.ajax({
            url: `/course-items/${courseId}/waiting-list`,
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .done(function(response) {
                console.log('Response:', response); // Debug log
                const students = response.students || [];
                displayWaitingStudents(students, rootId);
                updateWaitingCount(courseId, students.length);
            })
            .fail(function(xhr, status, error) {
                console.error('Error loading waiting students:', error);
                $(`#waiting-students-container-${rootId}`).html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Có lỗi xảy ra khi tải danh sách học viên: ${error}
                    </div>
                `);
            });
    }
    
    // Hiển thị danh sách học viên chờ
    function displayWaitingStudents(students, rootId) {
        // Kiểm tra và đảm bảo students là array
        if (!Array.isArray(students)) {
            console.warn('Students is not an array:', students);
            students = [];
        }
        
        if (students.length === 0) {
            $(`#waiting-students-container-${rootId}`).html(`
                <div class="empty-state">
                    <i class="fas fa-user-check"></i>
                    <h5>Không có học viên chờ</h5>
                    <p class="text-muted">Không có học viên nào đang chờ trong khóa học này</p>
                </div>
            `);
            return;
        }
        
        let html = '';
        
        students.forEach(function(student) {
            html += `
                <div class="student-card" data-enrollment-id="${student.enrollment_id}">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="form-check">
                                <input class="form-check-input student-checkbox" type="checkbox" 
                                       value="${student.enrollment_id}" data-root-id="${rootId}">
                                <label class="form-check-label">
                                    <h6 class="mb-1">${student.full_name}</h6>
                                </label>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="fas fa-phone"></i> ${student.phone}
                                    </small>
                                    ${student.email ? `<br><small class="text-muted"><i class="fas fa-envelope"></i> ${student.email}</small>` : ''}
                                </div>
                                <div class="col-md-6">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i> Đăng ký: ${student.request_date}
                                    </small>
                                    ${student.notes ? `<br><small class="text-muted"><i class="fas fa-sticky-note"></i> ${student.notes}</small>` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="btn-group btn-group-sm ms-3">
                            <button type="button" class="btn btn-success confirm-single-waiting" 
                                    data-enrollment-id="${student.enrollment_id}"
                                    data-student-name="${student.full_name}"
                                    title="Xác nhận">
                                <i class="fas fa-check"></i>
                            </button>
                            <button type="button" class="btn btn-info view-student-detail" 
                                    data-student-id="${student.student_id}"
                                    title="Xem chi tiết">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-danger remove-from-waiting" 
                                    data-enrollment-id="${student.enrollment_id}"
                                    title="Xóa khỏi danh sách chờ">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });
        
        $(`#waiting-students-container-${rootId}`).html(html);
    }
    
    // Cập nhật số lượng chờ
    function updateWaitingCount(courseId, count) {
        $(`#waiting-count-${courseId}`).text(count);
        
        // Cập nhật tổng số cho tab
        updateTabCount();
    }
    
    // Cập nhật số lượng cho tab
    function updateTabCount() {
        $('.course-tabs .nav-link').each(function() {
            const tabId = $(this).attr('id').replace('tab-', '');
            let totalCount = 0;
            
            $(`.waiting-count[id^="waiting-count-"]`).each(function() {
                const courseId = $(this).attr('id').replace('waiting-count-', '');
                // Kiểm tra xem course này có thuộc tab hiện tại không
                if ($(this).closest('.tab-pane').data('root-id') == tabId) {
                    totalCount += parseInt($(this).text()) || 0;
                }
            });
            
            $(`#tab-count-${tabId}`).text(totalCount);
        });
    }
    
    // Load số lượng chờ cho tất cả khóa học
    function loadAllWaitingCounts() {
        $('.waiting-course-item').each(function() {
            const courseId = $(this).data('id');
            $.ajax({
                url: `/course-items/${courseId}/waiting-count`,
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .done(function(response) {
                    updateWaitingCount(courseId, response.count);
                })
                .fail(function(xhr, status, error) {
                    console.error(`Error loading count for course ${courseId}:`, error);
                });
        });
    }
    
    // Xử lý checkbox học viên
    $(document).on('change', '.student-checkbox', function() {
        const rootId = $(this).data('root-id');
        const checkedCount = $(`.student-checkbox[data-root-id="${rootId}"]:checked`).length;
        
        $(`#selected-count-${rootId}`).text(checkedCount);
        
        if (checkedCount > 0) {
            $(`#bulk-actions-${rootId}`).addClass('show');
        } else {
            $(`#bulk-actions-${rootId}`).removeClass('show');
        }
        
        // Toggle student card selection
        if ($(this).is(':checked')) {
            $(this).closest('.student-card').addClass('selected');
        } else {
            $(this).closest('.student-card').removeClass('selected');
        }
    });
    
    // Xử lý xác nhận đơn lẻ
    $(document).on('click', '.confirm-single-waiting', function() {
        const enrollmentId = $(this).data('enrollment-id');
        const studentName = $(this).data('student-name');
        
        $('#confirm-student-name').text(studentName);
        $('#confirm-waiting-btn').data('enrollment-id', enrollmentId);
        
        // Load danh sách khóa học
        loadCourseOptions();
        
        // Reset form
        $('#confirm-discount').val(0);
        $('#confirm-notes').val('');
        $('#confirm-course-select').val('');
        
        $('#confirmWaitingModal').modal('show');
    });
    
    // Load danh sách khóa học cho dropdown
    function loadCourseOptions() {
        $('#confirm-course-select').html('<option value="">Đang tải...</option>');
        
        $.ajax({
            url: '/api/course-items/leaf-courses',
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .done(function(response) {
            let options = '<option value="">Giữ nguyên khóa học hiện tại</option>';
            
            if (response.courses && response.courses.length > 0) {
                response.courses.forEach(function(course) {
                    options += `<option value="${course.id}">${course.name} (${course.path})</option>`;
                });
            }
            
            $('#confirm-course-select').html(options);
        })
        .fail(function() {
            $('#confirm-course-select').html('<option value="">Lỗi tải danh sách khóa học</option>');
        });
    }
    
    // Xử lý xác nhận từ modal
    $('#confirm-waiting-btn').on('click', function() {
        const enrollmentId = $(this).data('enrollment-id');
        const discount = $('#confirm-discount').val() || 0;
        const newCourseId = $('#confirm-course-select').val();
        const notes = $('#confirm-notes').val();
        
        // Disable button để tránh click nhiều lần
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
        
        confirmWaitingStudent(enrollmentId, discount, newCourseId, notes);
    });
    
    // Xác nhận học viên
    function confirmWaitingStudent(enrollmentId, discount = 0, newCourseId = '', notes = '') {
        const data = {
            _token: $('meta[name="csrf-token"]').attr('content'),
            discount_percentage: discount
        };
        
        // Thêm khóa học mới nếu có
        if (newCourseId) {
            data.new_course_id = newCourseId;
        }
        
        // Thêm ghi chú nếu có
        if (notes) {
            data.notes = notes;
        }
        
        $.post(`/enrollments/${enrollmentId}/confirm-waiting`, data)
        .done(function(response) {
            $('#confirmWaitingModal').modal('hide');
            
            // Reset form
            $('#confirm-discount').val(0);
            $('#confirm-notes').val('');
            $('#confirm-course-select').val('');
            
            // Re-enable button
            $('#confirm-waiting-btn').prop('disabled', false).html('<i class="fas fa-check"></i> Xác nhận');
            
            // Reload danh sách
            if (selectedCourseId && currentRootId) {
                loadWaitingStudents(selectedCourseId, currentRootId);
            }
            
            // Reload số lượng chờ
            loadAllWaitingCounts();
            
            let message = 'Đã xác nhận học viên thành công!';
            if (newCourseId) {
                message += ' Học viên đã được chuyển sang khóa học mới.';
            }
            
            showAlert('success', message);
        })
        .fail(function(xhr) {
            // Re-enable button
            $('#confirm-waiting-btn').prop('disabled', false).html('<i class="fas fa-check"></i> Xác nhận');
            
            showAlert('danger', 'Có lỗi xảy ra: ' + (xhr.responseJSON?.message || 'Lỗi không xác định'));
        });
    }
    
    // Xử lý xác nhận hàng loạt
    $(document).on('click', '.bulk-confirm-waiting', function() {
        const rootId = $(this).data('root-id');
        const selectedIds = $(`.student-checkbox[data-root-id="${rootId}"]:checked`).map(function() {
            return $(this).val();
        }).get();
        
        if (selectedIds.length === 0) {
            showAlert('warning', 'Vui lòng chọn ít nhất một học viên');
            return;
        }
        
        if (confirm(`Bạn có chắc chắn muốn xác nhận ${selectedIds.length} học viên đã chọn?`)) {
            bulkConfirmWaiting(selectedIds, rootId);
        }
    });
    
    // Xác nhận hàng loạt
    function bulkConfirmWaiting(enrollmentIds, rootId) {
        $.post('/enrollments/bulk-confirm-waiting', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            enrollment_ids: enrollmentIds
        })
        .done(function(response) {
            if (selectedCourseId && currentRootId) {
                loadWaitingStudents(selectedCourseId, currentRootId);
            }
            
            showAlert('success', `Đã xác nhận ${response.confirmed_count} học viên thành công!`);
        })
        .fail(function(xhr) {
            showAlert('danger', 'Có lỗi xảy ra: ' + (xhr.responseJSON?.message || 'Lỗi không xác định'));
        });
    }
    
    // Bỏ chọn tất cả
    $(document).on('click', '.clear-selection', function() {
        const rootId = $(this).data('root-id');
        $(`.student-checkbox[data-root-id="${rootId}"]`).prop('checked', false).trigger('change');
    });
    
        // Xử lý xem chi tiết học viên
    $(document).on('click', '.view-student-detail', function() {
        const studentId = $(this).data('student-id');
        $('#student-loading').show();
        $('#student-details').hide();
        $('#viewStudentModal').modal('show');
        
        // Load chi tiết học viên
        loadStudentDetails(studentId);
    });
    
    // Load chi tiết học viên
    function loadStudentDetails(studentId) {
        $.ajax({
            url: `/api/students/${studentId}/details`,
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .done(function(response) {
            $('#student-loading').hide();
            
            if (response.success && response.student) {
                const student = response.student;
                const enrollments = response.enrollments || [];
                
                let enrollmentHistory = '';
                if (enrollments.length > 0) {
                    enrollmentHistory = enrollments.map(enrollment => `
                        <tr>
                            <td>${enrollment.course_name}</td>
                            <td><span class="badge bg-${getStatusColor(enrollment.status)}">${getStatusText(enrollment.status)}</span></td>
                            <td>${enrollment.enrollment_date}</td>
                            <td>${enrollment.final_fee}</td>
                        </tr>
                    `).join('');
                } else {
                    enrollmentHistory = '<tr><td colspan="4" class="text-center text-muted">Chưa có lịch sử ghi danh</td></tr>';
                }
                
                $('#student-details').html(`
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0"><i class="fas fa-user"></i> Thông tin cá nhân</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr><th width="40%">Họ tên:</th><td><strong>${student.full_name}</strong></td></tr>
                                        <tr><th>Số điện thoại:</th><td>${student.phone}</td></tr>
                                        <tr><th>Email:</th><td>${student.email || 'Chưa có'}</td></tr>
                                        <tr><th>Ngày sinh:</th><td>${student.date_of_birth || 'Chưa có'}</td></tr>
                                        <tr><th>Địa chỉ:</th><td>${student.address || 'Chưa có'}</td></tr>
                                        <tr><th>Ngày tạo:</th><td>${student.created_at}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0"><i class="fas fa-chart-line"></i> Thống kê</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm table-borderless">
                                        <tr><th width="60%">Tổng số khóa đã đăng ký:</th><td><strong>${response.stats.total_enrollments}</strong></td></tr>
                                        <tr><th>Đang học:</th><td><span class="badge bg-success">${response.stats.enrolled_count}</span></td></tr>
                                        <tr><th>Đang chờ:</th><td><span class="badge bg-warning">${response.stats.waiting_count}</span></td></tr>
                                        <tr><th>Đã hoàn thành:</th><td><span class="badge bg-primary">${response.stats.completed_count}</span></td></tr>
                                        <tr><th>Tổng học phí đã đóng:</th><td><strong class="text-success">${response.stats.total_paid}</strong></td></tr>
                                        <tr><th>Còn nợ:</th><td><strong class="text-danger">${response.stats.total_unpaid}</strong></td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0"><i class="fas fa-history"></i> Lịch sử ghi danh</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Khóa học</th>
                                            <th>Trạng thái</th>
                                            <th>Ngày đăng ký</th>
                                            <th>Học phí</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${enrollmentHistory}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                `).show();
            } else {
                $('#student-details').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Không thể tải thông tin học viên
                    </div>
                `).show();
            }
        })
        .fail(function() {
            $('#student-loading').hide();
            $('#student-details').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Có lỗi xảy ra khi tải thông tin học viên
                </div>
            `).show();
        });
    }
    
    // Helper functions
    function getStatusColor(status) {
        const colors = {
            'enrolled': 'success',
            'waiting': 'warning', 
            'completed': 'primary',
            'cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    }
    
    function getStatusText(status) {
        const texts = {
            'enrolled': 'Đang học',
            'waiting': 'Đang chờ',
            'completed': 'Hoàn thành', 
            'cancelled': 'Đã hủy'
        };
        return texts[status] || status;
    }
    
    // Xử lý xóa khỏi danh sách chờ
    $(document).on('click', '.remove-from-waiting', function() {
        const enrollmentId = $(this).data('enrollment-id');
        const studentCard = $(this).closest('.student-card');
        const studentName = studentCard.find('h6').text();
        
        if (confirm(`Bạn có chắc chắn muốn xóa "${studentName}" khỏi danh sách chờ?\n\nHành động này sẽ hủy đăng ký và không thể hoàn tác.`)) {
            removeFromWaiting(enrollmentId, studentCard);
        }
    });
    
    // Xóa học viên khỏi danh sách chờ
    function removeFromWaiting(enrollmentId, studentCard) {
        // Disable button
        studentCard.find('.remove-from-waiting').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: `/api/enrollments/${enrollmentId}/cancel`,
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                reason: 'Xóa khỏi danh sách chờ'
            }
        })
        .done(function(response) {
            if (response.success) {
                // Fade out và remove card
                studentCard.fadeOut(300, function() {
                    $(this).remove();
                    
                    // Update counts
                    if (selectedCourseId && currentRootId) {
                        loadWaitingStudents(selectedCourseId, currentRootId);
                    }
                    loadAllWaitingCounts();
                });
                
                showAlert('success', 'Đã xóa học viên khỏi danh sách chờ thành công!');
            } else {
                studentCard.find('.remove-from-waiting').prop('disabled', false).html('<i class="fas fa-times"></i>');
                showAlert('danger', response.message || 'Có lỗi xảy ra');
            }
        })
        .fail(function(xhr) {
            studentCard.find('.remove-from-waiting').prop('disabled', false).html('<i class="fas fa-times"></i>');
            showAlert('danger', 'Có lỗi xảy ra: ' + (xhr.responseJSON?.message || 'Lỗi không xác định'));
        });
    }
    
    // Làm mới danh sách
    $(document).on('click', '.refresh-waiting-list', function() {
        const rootId = $(this).data('root-id');
        if (selectedCourseId && currentRootId === rootId) {
            loadWaitingStudents(selectedCourseId, rootId);
        }
    });
    
    // Xử lý nút thêm học viên
    $(document).on('click', '.add-student-to-waiting', function() {
        const rootId = $(this).data('root-id');
        currentRootId = rootId;
        
        // Reset modal
        $('#student-search-input').val('');
        $('#student-search-results').html('<div class="text-center text-muted py-3">Nhập từ khóa để tìm kiếm học viên</div>');
        $('#selected-student-info').html('Chưa chọn học viên nào').removeClass('alert-success').addClass('alert-info');
        $('#waiting-notes').val('');
        $('#add-to-waiting-btn').prop('disabled', true).data('student-id', '');
        
        // Load danh sách khóa học
        loadCourseOptionsForWaiting();
        
        $('#addStudentToWaitingModal').modal('show');
    });
    
    // Load danh sách khóa học cho modal thêm học viên
    function loadCourseOptionsForWaiting() {
        $('#waiting-course-select').html('<option value="">Đang tải...</option>');
        
        $.ajax({
            url: '/api/course-items/leaf-courses',
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .done(function(response) {
            let options = '<option value="">Chọn khóa học</option>';
            
            if (response.courses && response.courses.length > 0) {
                response.courses.forEach(function(course) {
                    options += `<option value="${course.id}">${course.name} (${course.path})</option>`;
                });
            }
            
            $('#waiting-course-select').html(options);
            
            // Reinitialize Select2 sau khi load options
            $('#waiting-course-select').select2({
                theme: 'bootstrap-5',
                placeholder: 'Chọn khóa học',
                allowClear: true
            });
            
            // Tự động chọn khóa học hiện tại nếu có
            if (selectedCourseId) {
                $('#waiting-course-select').val(selectedCourseId).trigger('change');
            }
        })
        .fail(function() {
            $('#waiting-course-select').html('<option value="">Lỗi tải danh sách khóa học</option>');
        });
    }
    
    // Tìm kiếm học viên
    let searchTimeout;
    $('#student-search-input').on('input', function() {
        const searchTerm = $(this).val().trim();
        
        clearTimeout(searchTimeout);
        
        if (searchTerm.length < 2) {
            $('#student-search-results').html('<div class="text-center text-muted py-3">Nhập ít nhất 2 ký tự để tìm kiếm</div>');
            return;
        }
        
        $('#student-search-results').html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Đang tìm kiếm...</div>');
        
        searchTimeout = setTimeout(function() {
            searchStudents(searchTerm);
        }, 500);
    });
    
    // Tìm kiếm học viên qua API
    function searchStudents(searchTerm) {
        $.ajax({
            url: '/api/students',
            method: 'GET',
            data: {
                search: searchTerm,
                limit: 10
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .done(function(response) {
            if (response.success && response.data && response.data.length > 0) {
                let html = '';
                response.data.forEach(function(student) {
                    html += `
                        <div class="list-group-item list-group-item-action student-search-item" 
                             data-student-id="${student.id}"
                             data-student-name="${student.full_name}"
                             data-student-phone="${student.phone}"
                             data-student-email="${student.email || ''}">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${student.full_name}</h6>
                                <small>ID: ${student.id}</small>
                            </div>
                            <p class="mb-1"><i class="fas fa-phone"></i> ${student.phone}</p>
                            ${student.email ? `<small><i class="fas fa-envelope"></i> ${student.email}</small>` : ''}
                        </div>
                    `;
                });
                $('#student-search-results').html(html);
            } else {
                $('#student-search-results').html('<div class="text-center text-muted py-3">Không tìm thấy học viên nào</div>');
            }
        })
        .fail(function() {
            $('#student-search-results').html('<div class="text-center text-danger py-3">Có lỗi xảy ra khi tìm kiếm</div>');
        });
    }
    
    // Chọn học viên từ kết quả tìm kiếm
    $(document).on('click', '.student-search-item', function() {
        const studentId = $(this).data('student-id');
        const studentName = $(this).data('student-name');
        const studentPhone = $(this).data('student-phone');
        const studentEmail = $(this).data('student-email');
        
        // Highlight selected item
        $('.student-search-item').removeClass('active');
        $(this).addClass('active');
        
        // Update selected student info
        $('#selected-student-info').html(`
            <strong>${studentName}</strong><br>
            <i class="fas fa-phone"></i> ${studentPhone}<br>
            ${studentEmail ? `<i class="fas fa-envelope"></i> ${studentEmail}` : ''}
        `).removeClass('alert-info').addClass('alert-success');
        
        // Enable add button and store student ID
        $('#add-to-waiting-btn').prop('disabled', false).data('student-id', studentId);
    });
    
    // Xử lý thêm học viên vào danh sách chờ
    $('#add-to-waiting-btn').on('click', function() {
        const studentId = $(this).data('student-id');
        const courseId = $('#waiting-course-select').val();
        const notes = $('#waiting-notes').val();
        
        if (!studentId || !courseId) {
            showAlert('warning', 'Vui lòng chọn học viên và khóa học');
            return;
        }
        
        // Disable button
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang thêm...');
        
        addStudentToWaiting(studentId, courseId, notes);
    });
    
    // Thêm học viên vào danh sách chờ
    function addStudentToWaiting(studentId, courseId, notes) {
        $.ajax({
            url: '/api/enrollments',
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                student_id: studentId,
                course_item_id: courseId,
                status: 'waiting',
                notes: notes,
                enrollment_date: new Date().toISOString().split('T')[0]
            }
        })
        .done(function(response) {
            $('#addStudentToWaitingModal').modal('hide');
            
            // Re-enable button
            $('#add-to-waiting-btn').prop('disabled', false).html('<i class="fas fa-plus"></i> Thêm vào danh sách chờ');
            
            // Reload danh sách nếu đang xem khóa học này
            if (selectedCourseId && currentRootId) {
                loadWaitingStudents(selectedCourseId, currentRootId);
            }
            
            // Reload số lượng chờ
            loadAllWaitingCounts();
            
            showAlert('success', 'Đã thêm học viên vào danh sách chờ thành công!');
        })
        .fail(function(xhr) {
            // Re-enable button
            $('#add-to-waiting-btn').prop('disabled', false).html('<i class="fas fa-plus"></i> Thêm vào danh sách chờ');
            
            showAlert('danger', 'Có lỗi xảy ra: ' + (xhr.responseJSON?.message || 'Lỗi không xác định'));
        });
    }
    
    // Hiển thị thông báo
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        $('body').append(alertHtml);
        
        // Tự động ẩn sau 5 giây
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
    
    // Tìm kiếm học viên
    $('#student-search').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        
        $('.student-card').each(function() {
            const studentName = $(this).find('h6').text().toLowerCase();
            const studentPhone = $(this).find('.fa-phone').parent().text().toLowerCase();
            
            if (studentName.includes(searchTerm) || studentPhone.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });
});
</script>
@endpush 