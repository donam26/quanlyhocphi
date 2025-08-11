@if($children && $children->count() > 0)
    <div class="collapse show" id="{{ $tabPrefix ?? '' }}waiting-children-{{ $parentId }}">
        <ul class="course-tree">
            @foreach($children->sortBy('order_index') as $child)
                <li>
                    <div class="tree-item level-{{ $child->level }} waiting-course-item {{ $child->active ? 'active' : 'inactive' }}" 
                         data-id="{{ $child->id }}" 
                         data-course-name="{{ $child->name }}"
                         @if(!$child->is_leaf) title="Click để xem tất cả học viên đang chờ từ khóa này và các khóa con" @endif>
                        @if($child->children && $child->children->count() > 0)
                            <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#{{ $tabPrefix ?? '' }}waiting-children-{{ $child->id }}">
                                <i class="fas fa-minus-circle"></i>
                            </span>
                        @else
                            <span class="toggle-icon-placeholder"></span>
                        @endif
                        
                        <span class="item-icon">
                            @if($child->is_leaf)
                                <i class="fas fa-file-alt"></i>
                            @else
                                <i class="fas fa-folder"></i>
                            @endif
                        </span>
                        
                        <span class="course-link">{{ $child->name }}</span>
                        <span class="waiting-count badge ms-auto" id="waiting-count-{{ $child->id }}">0</span>
                    </div>
                    
                    @if($child->children && $child->children->count() > 0)
                        @include('course-items.partials.waiting-children-tree', [
                            'children' => $child->children, 
                            'parentId' => $child->id,
                            'tabPrefix' => $tabPrefix ?? ''
                        ])
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif 