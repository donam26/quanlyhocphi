@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-8">
            <h2>Chỉnh sửa khóa học "{{ $courseItem->name }}"</h2>
        </div>
        <div class="col-md-4 text-right">
            @if($courseItem->parent_id)
                <a href="{{ route('course-items.show', $courseItem->parent_id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            @else
                <a href="{{ route('course-items.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>Thông tin khóa học</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('course-items.update', $courseItem->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="form-group">
                    <label for="parent_id">Thuộc khóa học cha</label>
                    <select name="parent_id" id="parent_id" class="form-control @error('parent_id') is-invalid @enderror">
                        <option value="">-- Không có (Đây là ngành/khóa học gốc) --</option>
                        @foreach($possibleParents as $parent)
                            <option value="{{ $parent->id }}" @if((old('parent_id') ?? $courseItem->parent_id) == $parent->id) selected @endif>
                                {{ str_repeat('— ', $parent->level - 1) }} {{ $parent->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('parent_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group mt-4 mb-4">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="make_root" id="make_root" class="custom-control-input" value="1" @if(!$courseItem->parent_id) checked @endif>
                        <label class="custom-control-label" for="make_root">
                            <strong class="text-primary">Đặt làm khóa chính</strong> (khóa này sẽ hiển thị ở cấp cao nhất, không thuộc khóa nào)
                        </label>
                    </div>
                    @if($courseItem->parent_id)
                        <div class="alert alert-info mt-2" id="make_root_info" style="display: none;">
                            <i class="fas fa-info-circle"></i> Khi chọn làm khóa chính, khóa học này sẽ được đưa lên cấp cao nhất và hiển thị cùng cấp với các ngành học.
                        </div>
                    @endif
                </div>

                <div class="form-group">
                    <label for="name">Tên khóa học <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $courseItem->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="fee">Học phí (chỉ áp dụng cho khóa học cuối cùng)</label>
                    <div class="input-group">
                        <input type="number" name="fee" id="fee" class="form-control @error('fee') is-invalid @enderror" value="{{ old('fee', $courseItem->fee) }}" min="0" step="1000">
                        <div class="input-group-append">
                            <span class="input-group-text">VNĐ</span>
                        </div>
                    </div>
                    @if($courseItem->is_leaf)
                        <small class="form-text text-muted">Đây là khóa học cuối. Nếu xóa học phí (đặt về 0), nó có thể trở thành khóa học có các khóa con.</small>
                    @else
                        <small class="form-text text-muted">Nếu nhập học phí, khóa học này sẽ được đánh dấu là khóa học cuối (không thể có khóa con).</small>
                    @endif
                    @error('fee')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="active" id="active" class="custom-control-input" value="1" @if(old('active', $courseItem->active)) checked @endif>
                        <label class="custom-control-label" for="active">Đang hoạt động</label>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                    @if($courseItem->parent_id)
                        <a href="{{ route('course-items.show', $courseItem->parent_id) }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                    @else
                        <a href="{{ route('course-items.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Xử lý tương tác giữa checkbox "Đặt làm khóa chính" và dropdown chọn cha
        function handleRootToggle() {
            const makeRootChecked = $('#make_root').is(':checked');
            if (makeRootChecked) {
                // Nếu đặt làm khóa chính, vô hiệu hóa dropdown chọn cha
                $('#parent_id').prop('disabled', true);
                $('#parent_id').val('');
                $('#make_root_info').slideDown();
            } else {
                // Nếu không đặt làm khóa chính, kích hoạt dropdown chọn cha
                $('#parent_id').prop('disabled', false);
                $('#make_root_info').slideUp();
            }
        }

        // Kích hoạt xử lý khi trang tải
        handleRootToggle();

        // Kích hoạt xử lý khi checkbox thay đổi
        $('#make_root').change(function() {
            handleRootToggle();
        });

        // Xử lý khi chọn parent_id
        $('#parent_id').change(function() {
            const selectedId = $(this).val();
            // Nếu chọn một parent, bỏ chọn "Đặt làm khóa chính"
            if (selectedId) {
                $('#make_root').prop('checked', false);
                $('#make_root_info').slideUp();
            }
        });
    });
</script>
@endpush
@endsection 