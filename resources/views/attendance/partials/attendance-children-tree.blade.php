@if($children && $children->count() > 0)
    <div class="collapse show" id="{{ $tabPrefix ?? '' }}attendance-children-{{ $parentId }}">
        <ul class="course-tree">
            @foreach($children->sortBy('order_index') as $child)
                <li>
                    <div class="tree-item level-{{ $child->level }} attendance-course-item {{ $child->active ? 'active' : 'inactive' }}" 
                         data-id="{{ $child->id }}" 
                         data-course-name="{{ $child->name }}">
                        @if($child->children && $child->children->count() > 0)
                            <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#{{ $tabPrefix ?? '' }}attendance-children-{{ $child->id }}">
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
                        <span class="attendance-count badge ms-auto" id="attendance-count-{{ $child->id }}">0</span>
                    </div>
                    
                    @if($child->children && $child->children->count() > 0)
                        @include('attendance.partials.attendance-children-tree', [
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