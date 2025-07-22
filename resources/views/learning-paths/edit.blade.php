@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Chỉnh sửa lộ trình học tập cho "{{ $courseItem->name }}"</h4>
            <a href="{{ route('course-items.show', $courseItem->id) }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>

        <div class="card-body">
            <form action="{{ route('learning-paths.update', $courseItem->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="paths-container">
                    @forelse($paths as $index => $path)
                        <div class="path-item mb-4 border p-3 rounded">
                            <input type="hidden" name="paths[{{ $index }}][id]" value="{{ $path->id }}">
                            <div class="form-group mb-3">
                                <label for="path-title-{{ $index + 1 }}">Tên lộ trình</label>
                                <input type="text" class="form-control" id="path-title-{{ $index + 1 }}" 
                                    name="paths[{{ $index }}][title]" value="{{ $path->title }}" required>
                            </div>
                            <div class="form-group mb-3">
                                <label for="path-description-{{ $index + 1 }}">Mô tả</label>
                                <textarea class="form-control" id="path-description-{{ $index + 1 }}" 
                                    name="paths[{{ $index }}][description]" rows="2">{{ $path->description }}</textarea>
                            </div>
                            <div class="form-group mb-3">
                                <label for="path-order-{{ $index + 1 }}">Thứ tự</label>
                                <input type="number" class="form-control" id="path-order-{{ $index + 1 }}" 
                                    name="paths[{{ $index }}][order]" value="{{ $path->order }}" min="1" required>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm remove-path" {{ $paths->count() <= 1 ? 'disabled' : '' }}>
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                    @empty
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
                    @endforelse
                </div>
                
                <div class="mb-3">
                    <button type="button" class="btn btn-info" id="add-path">
                        <i class="fas fa-plus"></i> Thêm lộ trình
                    </button>
                </div>
                
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">Cập nhật lộ trình</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        let pathCount = {{ $paths->count() > 0 ? $paths->count() : 1 }};
        
        // Thêm lộ trình mới
        $('#add-path').click(function() {
            let newIndex = $('.path-item').length;
            let newPath = `
                <div class="path-item mb-4 border p-3 rounded">
                    <div class="form-group mb-3">
                        <label for="path-title-${newIndex + 1}">Tên lộ trình</label>
                        <input type="text" class="form-control" id="path-title-${newIndex + 1}" name="paths[${newIndex}][title]" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="path-description-${newIndex + 1}">Mô tả</label>
                        <textarea class="form-control" id="path-description-${newIndex + 1}" name="paths[${newIndex}][description]" rows="2"></textarea>
                    </div>
                    <div class="form-group mb-3">
                        <label for="path-order-${newIndex + 1}">Thứ tự</label>
                        <input type="number" class="form-control" id="path-order-${newIndex + 1}" name="paths[${newIndex}][order]" value="${newIndex + 1}" min="1" required>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm remove-path">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </div>
            `;
            $('.paths-container').append(newPath);
            pathCount++;
            
            // Kích hoạt nút xóa cho tất cả lộ trình nếu có từ 2 lộ trình trở lên
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
                // Giữ lại id nếu có
                let pathId = $(this).find('input[name^="paths"][name$="[id]"]');
                if (pathId.length > 0) {
                    pathId.attr('name', `paths[${index}][id]`);
                }
            });
        });
    });
</script>
@endpush
@endsection 