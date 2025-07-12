@extends('layouts.app')

@section('page-title', 'Quản lý khóa học')

@section('breadcrumb')
<li class="breadcrumb-item active">Khóa học</li>
@endsection

@section('styles')
<style>
.tree-list, .tree-list ul {
    list-style: none;
    padding-left: 1.5em;
    margin-bottom: 0;
}
.tree-list > li {
    margin-bottom: 1em;
}
.tree-branch {
    font-weight: 600;
    font-size: 1.1em;
    margin-bottom: 0.5em;
    display: flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}
.tree-leaf {
    font-weight: 400;
    margin-bottom: 0.3em;
    display: flex;
    align-items: center;
}
.tree-icon {
    margin-right: 0.5em;
    font-size: 1.1em;
}
.badge-fee {
    background: #27ae60;
    color: #fff;
    font-weight: 500;
    margin-left: 0.5em;
    padding: 0.3em 0.7em;
    border-radius: 6px;
    font-size: 0.95em;
}
.badge-mode {
    margin-left: 0.3em;
    font-size: 0.85em;
    padding: 0.2em 0.6em;
    border-radius: 5px;
}
.tree-list ul {
    border-left: 2px solid #e0e0e0;
    margin-left: 0.7em;
    padding-left: 1.2em;
}
.tree-list li:hover > .tree-branch, .tree-list li:hover > .tree-leaf {
    background: #f8f9fa;
    border-radius: 5px;
}
.tree-toggle {
    margin-right: 0.5em;
    color: #3498db;
    font-size: 1.1em;
    cursor: pointer;
    transition: transform 0.2s;
}
.tree-toggle.collapsed {
    transform: rotate(-90deg);
    color: #aaa;
}
.tree-action {
    margin-left: 0.7em;
    font-size: 0.95em;
    color: #888;
    cursor: pointer;
    transition: color 0.2s;
}
.tree-action:hover {
    color: #e74c3c;
}
/* Tab ngành đẹp */
#majorTabs .nav-link {
    font-weight: 600;
    color: #34495e;
    border: none;
    border-bottom: 2px solid transparent;
    background: none;
    border-radius: 0;
    margin-right: 8px;
    transition: border-color 0.2s, color 0.2s;
}
#majorTabs .nav-link.active {
    color: #2c3e50;
    border-bottom: 2.5px solid #3498db;
    background: #f8f9fa;
}
@media (max-width: 600px) {
    .tree-list, .tree-list ul { padding-left: 0.7em; }
    .tree-branch, .tree-leaf { font-size: 1em; }
}
</style>
@endsection

@section('content')
<!-- Filter & Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('courses.index') }}">
            <div class="row">
                <div class="col-md-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Tìm theo tên khóa học..." 
                               value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="major_id" class="form-select">
                        <option value="">Tất cả ngành</option>
                        @foreach($majors as $major)
                            <option value="{{ $major->id }}" {{ request('major_id') == $major->id ? 'selected' : '' }}>
                                {{ $major->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Tất cả trạng thái</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Hoạt động</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Không hoạt động</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('courses.create') }}" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-2"></i>Thêm mới
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Courses List -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="fas fa-graduation-cap me-2"></i>
            Cây ngành - khóa học - lớp học
        </h5>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs mb-3" id="majorTabs" role="tablist">
            <!-- Tabs ngành sẽ render bằng JS -->
        </ul>
        <div id="treeContent">
            <div class="text-center text-muted">Đang tải dữ liệu...</div>
        </div>
    </div>
</div>

<!-- Modal động cho Thêm/Sửa/Xóa node -->
<div class="modal fade" id="treeModal" tabindex="-1" aria-labelledby="treeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="treeModalForm">
        <div class="modal-header">
          <h5 class="modal-title" id="treeModalLabel">Thao tác</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="node_id" id="modalNodeId">
          <input type="hidden" name="action" id="modalAction">
          <div class="mb-3">
            <label for="modalNodeName" class="form-label">Tên</label>
            <input type="text" class="form-control" id="modalNodeName" name="name" required>
          </div>
          <div class="mb-3" id="modalFeeGroup" style="display:none;">
            <label for="modalNodeFee" class="form-label">Học phí</label>
            <input type="number" class="form-control" id="modalNodeFee" name="fee" min="0">
          </div>
          <div class="mb-3" id="modalTypeGroup" style="display:none;">
            <label class="form-label">Loại</label>
            <select class="form-select" id="modalNodeType" name="type">
              <option value="">-- Chọn --</option>
              <option value="online">Online</option>
              <option value="offline">Offline</option>
            </select>
          </div>
          <div class="mb-3 text-danger" id="modalDeleteConfirm" style="display:none;">
            Bạn có chắc chắn muốn xóa node này không? Thao tác này không thể hoàn tác!
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary" id="modalSubmitBtn">Lưu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Toast thông báo -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999">
  <div id="treeToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="treeToastBody">Thành công!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<!-- Quick Stats -->
@if($courses->count() > 0)
<div class="row mt-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $courses->total() }}</p>
                    <p class="stats-label">Tổng khóa học</p>
                </div>
                <i class="fas fa-graduation-cap fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $courses->where('is_active', true)->count() }}</p>
                    <p class="stats-label">Đang hoạt động</p>
                </div>
                <i class="fas fa-play fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card warning">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $totalClasses ?? 0 }}</p>
                    <p class="stats-label">Tổng lớp học</p>
                </div>
                <i class="fas fa-chalkboard-teacher fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card danger">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="stats-number">{{ $courses->sum(function($c) { return $c->subCourses->count(); }) }}</p>
                    <p class="stats-label">Sub-courses</p>
                </div>
                <i class="fas fa-list fa-2x opacity-75"></i>
            </div>
        </div>
    </div>
</div>
@endif

<!-- Sub-courses Modal -->
<div class="modal fade" id="subCoursesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết Sub-courses</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="subCoursesContent">
                <!-- Content loaded by JavaScript -->
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="/node_modules/sortablejs/modular/sortable.core.esm.js"></script>
<script>
$(document).ready(function() {
    fetch('/tree')
        .then(res => res.json())
        .then(data => {
            if (!data || data.length === 0) {
                $('#treeContent').html('<div class="text-center text-muted">Không có dữ liệu ngành</div>');
                return;
            }
            // Render tabs ngành
            let tabHtml = '';
            data.forEach((major, idx) => {
                tabHtml += `<li class="nav-item" role="presentation">
                    <button class="nav-link${idx===0?' active':''}" id="tab-major-${major.id}" data-bs-toggle="tab" type="button" role="tab" aria-selected="${idx===0?'true':'false'}" onclick="showMajorTree(${major.id})">${major.name}</button>
                </li>`;
            });
            $('#majorTabs').html(tabHtml);
            // Render cây ngành đầu tiên
            showMajorTree(data[0].id, data);
            window.treeData = data; // Lưu lại để dùng khi chuyển tab
        });
});

function showMajorTree(majorId, data) {
    data = data || window.treeData;
    const major = data.find(m => m.id === majorId);
    if (!major) return;
    let html = renderCourseTree(major.courses);
    $('#treeContent').html(html);
}

// Đệ quy render sub-course nhiều cấp
function renderSubCourseTree(subCourses) {
    if (!subCourses || subCourses.length === 0) return '';
    let html = '<ul>';
    subCourses.forEach(sub => {
        const isLeaf = !sub.children || sub.children.length === 0;
        html += `<li>`;
        html += `<div class='tree-leaf'><span class='tree-icon' style='color:#e67e22'>🏷️</span>${sub.name}`;
        if (sub.has_online) html += ' <span class="badge bg-info badge-mode">Online</span>';
        if (sub.has_offline) html += ' <span class="badge bg-secondary badge-mode">Offline</span>';
        if (sub.fee) html += ` <span class="badge-fee">${formatFee(sub.fee)}</span>`;
        // Thao tác
        html += `<span class='tree-action' onclick='openTreeModal("edit", "sub-course", ${JSON.stringify(sub)})'>✏️</span>`;
        html += `<span class='tree-action' onclick='openTreeModal("delete", "sub-course", ${JSON.stringify(sub)})'>🗑️</span>`;
        html += `<span class='tree-action' onclick='openTreeModal("add", "sub-course", {parent_id: ${sub.id}})'>➕</span>`;
        html += `</div>`;
        if (!isLeaf) {
            html += renderSubCourseTree(sub.children);
        }
        html += `</li>`;
    });
    html += '</ul>';
    return html;
}

// Đệ quy render cây lớp học nhiều cấp
function renderClassTree(classes) {
    if (!classes || classes.length === 0) return '';
    let html = '<ul class="tree-list">';
    classes.forEach(cls => {
        const isLeaf = !cls.child_classes || cls.child_classes.length === 0;
        html += `<li>`;
        html += `<div class='${isLeaf ? 'tree-leaf' : 'tree-branch'}'>`;
        if (!isLeaf) html += `<span class='tree-toggle' onclick='toggleTree(this)'>▼</span>`;
        html += `<span class='tree-icon' style='color:${isLeaf ? '#16a085':'#8e44ad'}'>${isLeaf ? '👨‍🏫' : '📦'}</span>${cls.name}`;
        if (cls.type) html += ` <span class="badge bg-${cls.type==='online'?'info':'secondary'} badge-mode">${cls.type}</span>`;
        if (isLeaf && cls.fee) html += ` <span class="badge-fee">${formatFee(cls.fee)}</span>`;
        // Thao tác
        html += `<span class='tree-action' onclick='openTreeModal("edit", "class", ${JSON.stringify(cls)})'>✏️</span>`;
        html += `<span class='tree-action' onclick='openTreeModal("delete", "class", ${JSON.stringify(cls)})'>🗑️</span>`;
        html += `<span class='tree-action' onclick='openTreeModal("add", "class", {parent_id: ${cls.id}})'>➕</span>`;
        html += `</div>`;
        if (!isLeaf) {
            html += `<div class='tree-children'>` + renderClassTree(cls.child_classes) + `</div>`;
        }
        html += `</li>`;
    });
    html += '</ul>';
    return html;
}

// Đệ quy render cây ngành-khóa-lớp nhiều cấp
function renderCourseTree(courses) {
    if (!courses || courses.length === 0) return '<div class="text-muted">Không có khóa học</div>';
    let html = '<ul class="tree-list">';
    courses.forEach(course => {
        html += `<li>`;
        html += `<div class='tree-branch'><span class='tree-toggle' onclick='toggleTree(this)'>▼</span><span class='tree-icon' style='color:#2980b9'>📚</span>${course.name}`;
        // Thao tác
        html += `<span class='tree-action' onclick='openTreeModal("edit", "course", ${JSON.stringify(course)})'>✏️</span>`;
        html += `<span class='tree-action' onclick='openTreeModal("delete", "course", ${JSON.stringify(course)})'>🗑️</span>`;
        html += `<span class='tree-action' onclick='openTreeModal("add", "course", {parent_id: ${course.id}})'>➕</span>`;
        html += `</div>`;
        // Nếu có sub_courses thì render sub_course nhiều cấp
        if (course.is_complex && course.sub_courses && course.sub_courses.length > 0) {
            html += `<div class='tree-children'>` + renderSubCourseTree(course.sub_courses) + `</div>`;
        }
        // Nếu có lớp học thì render cây lớp học đệ quy
        if (course.classes && course.classes.length > 0) {
            html += `<div class='tree-children'>` + renderClassTree(course.classes) + `</div>`;
        }
        html += '</li>';
    });
    html += '</ul>';
    return html;
}
function formatFee(fee) {
    if (!fee || fee == 0) return '';
    return Number(fee).toLocaleString('vi-VN') + 'đ';
}
// Expand/collapse logic
function toggleTree(el) {
    const $el = $(el);
    const $children = $el.closest('li').children('.tree-children, ul');
    if ($children.is(':visible')) {
        $children.slideUp(150);
        $el.addClass('collapsed').text('▶');
    } else {
        $children.slideDown(150);
        $el.removeClass('collapsed').text('▼');
    }
}
// Mặc định đóng các nhánh con, chỉ mở nhánh đầu
$(document).on('ready ajaxComplete', function() {
    $('.tree-list > li > .tree-branch .tree-toggle').each(function(i, el) {
        if (i > 0) toggleTree(el);
    });
});

// Thay các alert thao tác bằng gọi openTreeModal
function openTreeModal(action, nodeType, nodeData = {}) {
    $('#treeModalLabel').text(
        action === 'add' ? 'Thêm mới ' + nodeType :
        action === 'edit' ? 'Sửa ' + nodeType :
        'Xóa ' + nodeType
    );
    $('#modalAction').val(action);
    $('#modalNodeId').val(nodeData.id || '');
    $('#modalNodeName').val(nodeData.name || '');
    $('#modalNodeFee').val(nodeData.fee || '');
    $('#modalNodeType').val(nodeData.type || '');
    // Ẩn/hiện các trường phù hợp
    $('#modalFeeGroup').toggle(nodeType === 'sub-course' || nodeType === 'class');
    $('#modalTypeGroup').toggle(nodeType === 'class');
    $('#modalDeleteConfirm').toggle(action === 'delete');
    $('#modalNodeName').prop('readonly', action === 'delete');
    $('#modalSubmitBtn').text(action === 'delete' ? 'Xóa' : 'Lưu');
    // Hiện modal
    var modal = new bootstrap.Modal(document.getElementById('treeModal'));
    modal.show();
}
// Sửa các nút thao tác trong render tree:
// html += `<span class='tree-action' onclick='openTreeModal("edit", "class", ${JSON.stringify(cls)})'>✏️</span>`;
// html += `<span class='tree-action' onclick='openTreeModal("delete", "class", ${JSON.stringify(cls)})'>🗑️</span>`;
// html += `<span class='tree-action' onclick='openTreeModal("add", "class", {parent_id: ${cls.id}})'>➕</span>`;
// Tương tự cho sub-course, course

// Submit form modal (demo AJAX)
$('#treeModalForm').on('submit', function(e) {
    e.preventDefault();
    var action = $('#modalAction').val();
    var nodeType = $('#treeModalLabel').text().toLowerCase().includes('ngành') ? 'major'
        : $('#treeModalLabel').text().toLowerCase().includes('khóa') ? 'course'
        : $('#treeModalLabel').text().toLowerCase().includes('sub-course') ? 'sub-course'
        : 'class';
    var id = $('#modalNodeId').val();
    var name = $('#modalNodeName').val();
    var fee = $('#modalNodeFee').val();
    var type = $('#modalNodeType').val();
    var parent_id = $(this).find('[name=parent_id]').val() || null;
    let url = '', method = '', data = {};
    if (nodeType === 'major') {
        url = action === 'add' ? '/api/majors' : `/api/majors/${id}`;
        method = action === 'add' ? 'POST' : (action === 'edit' ? 'PUT' : 'DELETE');
        data = { name };
    } else if (nodeType === 'course') {
        url = action === 'add' ? '/api/courses' : `/api/courses/${id}`;
        method = action === 'add' ? 'POST' : (action === 'edit' ? 'PUT' : 'DELETE');
        data = { name, parent_id };
    } else if (nodeType === 'sub-course') {
        url = action === 'add' ? '/api/sub-courses' : `/api/sub-courses/${id}`;
        method = action === 'add' ? 'POST' : (action === 'edit' ? 'PUT' : 'DELETE');
        data = { name, fee, parent_id };
    } else if (nodeType === 'class') {
        url = action === 'add' ? '/api/course-classes' : `/api/course-classes/${id}`;
        method = action === 'add' ? 'POST' : (action === 'edit' ? 'PUT' : 'DELETE');
        data = { name, fee, type, parent_id };
    }
    if (action === 'delete') {
        fetch(url, { method: 'DELETE', headers: { 'Accept': 'application/json' } })
            .then(res => {
                if (res.status === 204) {
                    $('#treeModal').on('hidden.bs.modal', function () {
                        showTreeToast('Đã xóa thành công!');
                        reloadTree();
                        $(this).off('hidden.bs.modal');
                    });
                    $('#treeModal').modal('hide');
                } else if (res.status === 404) {
                    showTreeToast('Lớp học không tồn tại hoặc đã bị xoá!', true);
                } else {
                    showTreeToast('Có lỗi khi xóa!', true);
                }
            })
            .catch(() => showTreeToast('Có lỗi khi xóa!', true));
        return;
    }
    fetch(url, {
        method: method,
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        $('#treeModal').modal('hide');
        showTreeToast('Thao tác thành công!');
        reloadTree();
    })
    .catch(() => showTreeToast('Có lỗi xảy ra!', true));
});
function showTreeToast(msg) {
    $('#treeToastBody').text(msg);
    var toast = new bootstrap.Toast(document.getElementById('treeToast'));
    toast.show();
}
// Sau khi render xong cây, kích hoạt drag & drop cho các ul.tree-list
function enableDragDrop() {
    document.querySelectorAll('.tree-list, .tree-list ul').forEach(function(ul) {
        if (!ul.classList.contains('sortable-enabled')) {
            ul.classList.add('sortable-enabled');
            Sortable.create(ul, {
                group: 'tree',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                onEnd: function (evt) {
                    // Lấy id node kéo và id node cha mới
                    var nodeId = $(evt.item).find('[data-node-id]').data('node-id');
                    var newParentLi = $(evt.to).closest('li');
                    var newParentId = newParentLi.length ? newParentLi.find('> .tree-branch, > .tree-leaf').data('node-id') : null;
                    var newOrder = Array.from(evt.to.children).indexOf(evt.item);
                    // Gửi AJAX cập nhật parent_id và order
                    $.ajax({
                        url: '/api/tree/move',
                        method: 'POST',
                        data: {
                            node_id: nodeId,
                            new_parent_id: newParentId,
                            new_order: newOrder,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function() {
                            showTreeToast('Di chuyển thành công!');
                            // Reload lại cây (hoặc cập nhật node động)
                            reloadTree();
                        },
                        error: function() {
                            showTreeToast('Có lỗi khi di chuyển!', true);
                        }
                    });
                }
            });
        }
    });
}
// Gọi enableDragDrop sau mỗi lần render cây
function reloadTree() {
    // ... code fetch lại dữ liệu cây và render lại ...
    // Sau khi render xong:
    enableDragDrop();
}
// Gọi enableDragDrop lần đầu sau khi trang ready
$(document).on('ready ajaxComplete', function() {
    enableDragDrop();
});
</script>
@endpush 