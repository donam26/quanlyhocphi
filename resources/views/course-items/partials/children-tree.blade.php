@php
    $tabPrefix = $tabPrefix ?? '';
    $children = $children ?? collect();
@endphp

<ul class="collapse show" id="{{ $tabPrefix }}children-{{ $parentId }}">
    @foreach($children->sortBy('order_index') as $courseItem)
        <li>
            <div class="tree-item level-2 {{ $courseItem->active ? 'active' : 'inactive' }}" data-id="{{ $courseItem->id }}">
                <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#{{ $tabPrefix }}children-{{ $courseItem->id }}">
                    <i class="fas fa-minus-circle"></i>
                </span>
                <span class="item-icon"><i class="fas fa-book"></i></span>
                <a href="{{ route('course-items.show', $courseItem->id) }}">{{ $courseItem->name }}</a>
                <div class="item-actions">
                    <form action="{{ route('course-items.toggle-active', $courseItem->id) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ $courseItem->active ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $courseItem->active ? 'Vô hiệu hóa' : 'Kích hoạt' }}">
                            <i class="fas {{ $courseItem->active ? 'fa-eye-slash' : 'fa-eye' }}"></i>
                        </button>
                    </form>
                    @if(!$courseItem->is_leaf)
                        <button type="button" class="btn btn-sm btn-success" title="Thêm khóa con" 
                            onclick="setupAddModal({{ $courseItem->id }}, '{{ $courseItem->name }}')">
                            <i class="fas fa-plus"></i>
                        </button>
                    @endif
                    <button type="button" class="btn btn-sm btn-primary" title="Chỉnh sửa" onclick="setupEditModal({{ $courseItem->id }})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" title="Xóa" onclick="confirmDelete({{ $courseItem->id }}, '{{ $courseItem->name }}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

            @if($courseItem->children->isNotEmpty())
                @include('course-items.partials.children-tree', ['children' => $courseItem->children, 'parentId' => $courseItem->id, 'tabPrefix' => $tabPrefix])
            @endif
        </li>
    @endforeach
</ul> 