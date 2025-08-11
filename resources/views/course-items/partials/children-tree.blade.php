@php
    $children = $children ?? collect();
@endphp

<ul class="collapse show" id="children-{{ $parentId }}">
    @foreach($children->sortBy('order_index') as $courseItem)
        <li>
            <div class="tree-item level-2 {{ $courseItem->active ? 'active' : 'inactive' }} {{ $courseItem->is_leaf ? 'leaf' : '' }}" data-id="{{ $courseItem->id }}">
                <span class="sort-handle" title="Kéo để sắp xếp">
                    <i class="fas fa-arrows-alt"></i>
                </span>
                <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#children-{{ $courseItem->id }}">
                    <i class="fas fa-minus-circle"></i>
                </span>
                <span class="item-icon"><i class="fas fa-book"></i></span>
                <a href="javascript:void(0)" class="course-link" data-id="{{ $courseItem->id }}">
                    {{ $courseItem->name }}
                    @if($courseItem->is_leaf)
                        {!! $courseItem->status_badge !!}
                    @endif
                </a>
                <div class="item-actions">
                    @if(!$courseItem->is_leaf)
                        <button type="button" class="btn btn-sm btn-success open-add-child" title="Thêm khóa con" 
                            data-parent-id="{{ $courseItem->id }}" data-parent-name="{{ $courseItem->name }}">
                            <i class="fas fa-plus"></i>
                        </button>
                    @endif
                    
                    @if($courseItem->is_leaf)
                        @php $courseStatus = $courseItem->getStatusEnum(); @endphp
                        <button type="button" class="btn btn-sm btn-{{ $courseStatus === App\Enums\CourseStatus::ACTIVE ? 'warning' : 'success' }} toggle-course-status" 
                            data-course-id="{{ $courseItem->id }}" 
                            data-current-status="{{ $courseStatus->value }}"
                            title="{{ $courseStatus === App\Enums\CourseStatus::ACTIVE ? 'Kết thúc khóa học' : 'Mở lại khóa học' }}">
                            <i class="fas fa-{{ $courseStatus === App\Enums\CourseStatus::ACTIVE ? 'stop' : 'play' }}"></i>
                        </button>
                    @endif
                    
                    <button type="button" class="btn btn-sm btn-info open-students-modal" data-course-id="{{ $courseItem->id }}" title="Xem học viên">
                        <i class="fas fa-user-graduate"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-primary" title="Chỉnh sửa" onclick="setupEditModal({{ $courseItem->id }})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger open-delete-item" title="Xóa" data-id="{{ $courseItem->id }}" data-name="{{ $courseItem->name }}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

            @if($courseItem->children->isNotEmpty())
                @include('course-items.partials.children-tree', ['children' => $courseItem->children, 'parentId' => $courseItem->id])
            @endif
        </li>
    @endforeach
</ul> 