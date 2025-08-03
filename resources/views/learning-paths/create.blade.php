@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Thêm lộ trình học tập cho "{{ $courseItem->name }}"</h4>
            <a href="{{ route('course-items.tree') }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('learning-paths.store', $courseItem->id) }}" method="POST">
                @csrf
                
                <div class="paths-container">
                    <div class="path-item mb-4 border p-3 rounded">
                        <div class="form-group mb-3">
                            <label for="path-title-1">Tên lộ trình</label>
                            <input type="text" class="form-control" id="path-title-1" name="paths[0][title]" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="path-description-1">Mô tả</label>
                            <textarea class="form-control" id="path-description-1" name="paths[0][description]" rows="2"></textarea>
                        </div>
                        <div class="form-group mb-3">
                            <label for="path-order-1">Thứ tự</label>
                            <input type="number" class="form-control" id="path-order-1" name="paths[0][order]" value="1" min="1" required>
                        </div>
                        <button type="button" class="btn btn-danger btn-sm remove-path" disabled>
                            <i class="fas fa-trash"></i> Xóa
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <button type="button" class="btn btn-info" id="add-path">
                        <i class="fas fa-plus"></i> Thêm lộ trình
                    </button>
                </div>
                
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Lưu lộ trình</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        let pathCount = 1;
        
        // Thêm lộ trình mới
        $('#add-path').click(function() {
            pathCount++;
            let newPath = `
                <div class="path-item mb-4 border p-3 rounded">
                    <div class="form-group mb-3">
                        <label for="path-title-${pathCount}">Tên lộ trình</label>
                        <input type="text" class="form-control" id="path-title-${pathCount}" name="paths[${pathCount-1}][title]" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="path-description-${pathCount}">Mô tả</label>
                        <textarea class="form-control" id="path-description-${pathCount}" name="paths[${pathCount-1}][description]" rows="2"></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label for="path-order-${pathCount}">Thứ tự</label>
                        <input type="number" class="form-control" id="path-order-${pathCount}" name="paths[${pathCount-1}][order]" value="${pathCount}" min="1" required>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm remove-path">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
            `;
            $('.paths-container').append(newPath);
            
            // Kích hoạt nút xóa cho lộ trình đầu tiên nếu có từ 2 lộ trình trở lên
            if (pathCount >= 2) {
                $('.remove-path').prop('disabled', false);
            }
        });
        
        // Xóa lộ trình
        $(document).on('click', '.remove-path', function() {
            $(this).closest('.path-item').remove();
            pathCount--;
            
            // Vô hiệu hóa nút xóa nếu chỉ còn 1 lộ trình
            if (pathCount <= 1) {
                $('.remove-path').prop('disabled', true);
            }
            
            // Cập nhật lại index và thứ tự
            $('.path-item').each(function(index) {
                $(this).find('input[name^="paths"][name$="[title]"]').attr('name', `paths[${index}][title]`);
                $(this).find('textarea[name^="paths"][name$="[description]"]').attr('name', `paths[${index}][description]`);
                $(this).find('input[name^="paths"][name$="[order]"]').attr('name', `paths[${index}][order]`);
            });
        });
    });
</script>
@endpush
@endsection 