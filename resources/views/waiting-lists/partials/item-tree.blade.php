<li>
    <div class="tree-item {{ !$item->active ? 'inactive' : '' }}">
        @if ($item->children->count() > 0)
            <div class="toggle-icon">
                <i class="fas fa-chevron-right"></i>
            </div>
        @else
            <div class="item-icon">
                @if ($item->is_leaf)
                    <i class="fas fa-book"></i>
                @else
                    <i class="fas fa-folder"></i>
                @endif
            </div>
        @endif
        
        <span class="item-name">{{ $item->name }}</span>
        
        @php
            $waitingCount = $waitingCountsByItem[$item->id] ?? 0;
        @endphp
        
        @if ($waitingCount > 0)
            <span class="badge-waiting">
                <i class="fas fa-user-clock me-1"></i>{{ $waitingCount }}
            </span>
        @endif
        
        <div class="item-actions">
            {{-- Hiển thị nút xem cho TẤT CẢ các khóa học (bao gồm cả parent) --}}
            <a href="{{ route('course-items.waiting-lists', $item->id) }}" class="btn btn-sm btn-primary" title="Xem danh sách chờ">
                <i class="fas fa-list-alt"></i>
            </a>
            
            {{-- Chỉ hiển thị nút thêm học viên cho khóa leaf --}}
            @if ($item->is_leaf)
                <a href="{{ route('waiting-lists.create', ['course_item_id' => $item->id]) }}" class="btn btn-sm btn-success" title="Thêm học viên vào danh sách chờ">
                    <i class="fas fa-plus"></i>
                </a>
            @endif
        </div>
    </div>
    
    @if ($item->children->count() > 0)
        <ul style="display: none;">
            @foreach ($item->children as $child)
                @include('waiting-lists.partials.item-tree', ['item' => $child, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li> 