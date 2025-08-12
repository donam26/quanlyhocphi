{{-- Partial để hiển thị children trong attendance tree --}}
@foreach($children as $child)
    <div class="tree-children">
        <div class="tree-item child-item {{ $child->active ? 'active' : 'inactive' }}" 
             data-id="{{ $child->id }}" 
             data-course-name="{{ $child->name }}">
            <div class="d-flex align-items-center p-2 border-bottom">
                <div class="me-2">
                    @if($child->children->count() > 0)
                        <i class="fas fa-chevron-down toggle-icon" 
                           data-bs-toggle="collapse" 
                           data-bs-target="#children-{{ $child->id }}"></i>
                    @else
                        <i class="fas fa-circle text-muted" style="font-size: 0.5rem;"></i>
                    @endif
                </div>
                <i class="fas fa-book text-info me-2"></i>
                <div class="flex-grow-1">
                    <div class="fw-bold">{{ $child->name }}</div>
                    @if($child->is_leaf && $child->fee > 0)
                        <small class="text-success">
                            <i class="fas fa-money-bill me-1"></i>{{ number_format($child->fee) }} VNĐ
                        </small>
                    @elseif($child->children->count() > 0)
                        <small class="text-muted">{{ $child->children->count() }} khóa học con</small>
                    @endif
                </div>
                <span class="badge bg-success attendance-count" 
                      id="count-{{ $child->id }}">0</span>
            </div>
        </div>
        
        @if($child->children->count() > 0)
            <div class="collapse" id="children-{{ $child->id }}">
                @include('attendance.partials.tree-children', [
                    'children' => $child->children->sortBy('order_index'),
                    'level' => ($level ?? 1) + 1
                ])
            </div>
        @endif
    </div>
@endforeach
