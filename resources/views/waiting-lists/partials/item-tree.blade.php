<li>
    <div class="tree-item level-{{ $level }} {{ !$item->active ? 'inactive' : 'active' }} {{ $item->is_leaf ? 'leaf' : '' }}" data-id="{{ $item->id }}">
        @if ($item->children->count() > 0)
            <span class="toggle-icon" data-bs-toggle="collapse" data-bs-target="#children-{{ $item->id }}">
                <i class="fas fa-minus-circle"></i>
            </span>
        @else
            <span class="item-icon">
                @if ($item->is_leaf)
                    <i class="fas fa-book"></i>
                @else
                    <i class="fas fa-folder"></i>
                @endif
            </span>
        @endif
        
            <a href="{{ route('course-items.waiting-lists', $item->id) }}">{{ $item->name }}</a>
        
        @php
            $waitingCount = $waitingCountsByItem[$item->id] ?? 0;
        @endphp
        
        @if ($waitingCount > 0)
            <span class="badge badge-waiting rounded-pill">
                <i class="fas fa-user-clock me-1"></i>{{ $waitingCount }}
            </span>
        @endif
        
        <div class="item-actions">
            <a href="{{ route('course-items.waiting-lists', $item->id) }}" class="btn btn-sm btn-info" title="Xem danh sách chờ">
                <i class="fas fa-list-alt"></i>
            </a>
            
            @if ($item->is_leaf)
                <a href="{{ route('waiting-lists.create', ['course_item_id' => $item->id]) }}" class="btn btn-sm btn-success" title="Thêm học viên vào danh sách chờ">
                    <i class="fas fa-plus"></i>
                </a>
            @endif
            
            @php
                $contactNeededCount = App\Models\WaitingList::where('course_item_id', $item->id)
                    ->where('status', 'waiting')
                    ->where(function($query) {
                        $query->whereNull('last_contact_date')
                            ->orWhere('last_contact_date', '<', now()->subDays(7));
                    })
                    ->count();
            @endphp
            
            @if ($contactNeededCount > 0)
                <a href="{{ route('waiting-lists.needs-contact', ['course_item_id' => $item->id]) }}" class="btn btn-sm btn-warning" title="Học viên cần liên hệ ({{ $contactNeededCount }})">
                    <i class="fas fa-phone"></i>
                </a>
            @endif
            
            @php
                $highInterestCount = App\Models\WaitingList::where('course_item_id', $item->id)
                    ->where('interest_level', 'high')
                    ->count();
            @endphp
            
            @if ($highInterestCount > 0)
                <span class="badge bg-danger" title="Học viên quan tâm cao">
                    <i class="fas fa-fire"></i> {{ $highInterestCount }}
                </span>
            @endif
        </div>
    </div>
    
    @if ($item->children->count() > 0)
        <ul id="children-{{ $item->id }}" class="collapse show">
            @foreach ($item->children->sortBy('order_index') as $child)
                @include('waiting-lists.partials.item-tree', ['item' => $child, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li> 