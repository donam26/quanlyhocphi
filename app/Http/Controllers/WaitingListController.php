<?php

namespace App\Http\Controllers;

use App\Models\WaitingList;
use App\Models\Student;
use App\Models\CourseItem;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaitingListController extends Controller
{
    /**
     * Hiển thị danh sách chờ theo cấu trúc cây
     */
    public function index(Request $request)
    {
        // Lấy các khóa học gốc (không có parent) để hiển thị dạng cây
        $rootCourseItems = CourseItem::whereNull('parent_id')
                            ->where('active', true)
                            ->with(['children' => function($query) {
                                $query->where('active', true);
                            }])
                            ->orderBy('order_index')
                            ->get();
        
        // Đếm số lượng học viên trong danh sách chờ cho mỗi khóa học
        $waitingCountsByItem = WaitingList::selectRaw('course_item_id, COUNT(*) as count')
                                ->where('status', 'waiting')
                                ->groupBy('course_item_id')
                                ->pluck('count', 'course_item_id')
                                ->toArray();

        return view('waiting-lists.index', compact('rootCourseItems', 'waitingCountsByItem'));
    }
    
    /**
     * Hiển thị học viên chờ cho một khóa học cụ thể
     */
    public function showByCourseItem(CourseItem $courseItem)
    {
        // Lấy tất cả các ID khóa này và các khóa con
        $courseItemIds = [$courseItem->id];
        
        if (!$courseItem->is_leaf) {
            // Thêm ID của tất cả khóa con (recursive)
            $descendantIds = $courseItem->descendants()->pluck('id')->toArray();
            $courseItemIds = array_merge($courseItemIds, $descendantIds);
        }
        
        // Lấy danh sách chờ từ khóa học này và tất cả khóa con
        $waitingLists = WaitingList::with(['student', 'courseItem'])
                                ->whereIn('course_item_id', $courseItemIds)
                                ->where('status', 'waiting')
                                ->orderBy('added_date', 'desc')
                                ->paginate(20);
        
        // Lấy đường dẫn breadcrumb của khóa học
        $breadcrumbs = $courseItem->ancestors()->toArray();
        array_push($breadcrumbs, $courseItem);
        
        // Tổng hợp thống kê
        $stats = [
            'total_waiting' => $waitingLists->total(),
            'high_interest' => WaitingList::whereIn('course_item_id', $courseItemIds)
                                          ->where('status', 'waiting')
                                          ->where('interest_level', 'high')
                                          ->count(),
            'medium_interest' => WaitingList::whereIn('course_item_id', $courseItemIds)
                                          ->where('status', 'waiting')
                                          ->where('interest_level', 'medium')
                                          ->count(),
            'low_interest' => WaitingList::whereIn('course_item_id', $courseItemIds)
                                          ->where('status', 'waiting')
                                          ->where('interest_level', 'low')
                                          ->count(),
            'needs_contact' => WaitingList::whereIn('course_item_id', $courseItemIds)
                                          ->where('status', 'waiting')
                                          ->where(function($query) {
                                              $query->whereNull('last_contact_date')
                                                    ->orWhere('last_contact_date', '<', now()->subDays(7));
                                          })->count(),
            'converted' => WaitingList::whereIn('course_item_id', $courseItemIds)
                                     ->where('status', 'enrolled')
                                     ->count()
        ];
        
        // Lấy các khóa con trực tiếp để hiển thị trong tab
        $childItems = $courseItem->children()->orderBy('order_index')->get();
        
        // Chuẩn bị các tab
        $tabs = [
            'waiting' => [
                'title' => 'Đang chờ',
                'count' => $stats['total_waiting'],
                'url' => route('course-items.waiting-lists', $courseItem->id)
            ],
            'enrolled' => [
                'title' => 'Đã ghi danh',
                'count' => $stats['converted'],
                'url' => route('course-items.students', $courseItem->id)
            ]
        ];
        
        return view('waiting-lists.by-course', compact(
            'waitingLists', 
            'courseItem', 
            'breadcrumbs', 
            'stats', 
            'childItems',
            'tabs'
        ));
    }

    /**
     * Hiển thị form thêm vào danh sách chờ
     */
    public function create(Request $request)
    {
        $student = null;
        if ($request->filled('student_id')) {
            $student = Student::findOrFail($request->student_id);
        }

        // Lấy tất cả khóa học leaf (không có con) và active
        $courseItems = CourseItem::where('is_leaf', true)
                               ->where('active', true)
                               ->get();
        
        // Nhóm theo cấu trúc cây để hiển thị trong dropdown
        $groupedCourseItems = [];
        foreach ($courseItems as $item) {
            $path = $item->getPathAttribute();
            $groupedCourseItems[$item->id] = $path;
        }
        
        // Nếu có course_item_id trong request, load thông tin
        $selectedCourseItem = null;
        if ($request->filled('course_item_id')) {
            $selectedCourseItem = CourseItem::findOrFail($request->course_item_id);
        }

        return view('waiting-lists.create', compact('student', 'groupedCourseItems', 'selectedCourseItem'));
    }

    /**
     * Lưu học viên vào danh sách chờ
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'student_id' => 'required|exists:students,id',
            'course_item_id' => 'required|exists:course_items,id',
            'added_date' => 'required|date',
            'interest_level' => 'required|in:low,medium,high',
            'contact_notes' => 'nullable|string',
        ]);

        // Kiểm tra học viên đã có trong danh sách chờ khóa này chưa
        $existingWaiting = WaitingList::where('student_id', $validatedData['student_id'])
                                    ->where('course_item_id', $validatedData['course_item_id'])
                                    ->where('status', 'waiting')
                                    ->first();

        if ($existingWaiting) {
            return back()->withErrors(['error' => 'Học viên đã có trong danh sách chờ khóa học này rồi!']);
        }
        
        // Kiểm tra xem học viên đã ghi danh khóa này chưa
        $existingEnrollment = Enrollment::where('student_id', $validatedData['student_id'])
                                       ->where('course_item_id', $validatedData['course_item_id'])
                                       ->where('status', 'enrolled')
                                       ->first();
                                       
        if ($existingEnrollment) {
            return back()->withErrors(['error' => 'Học viên đã ghi danh khóa học này rồi!']);
        }

        $validatedData['status'] = 'waiting';
        $waitingList = WaitingList::create($validatedData);

        return redirect()->route('waiting-lists.show', $waitingList)
                        ->with('success', 'Thêm học viên vào danh sách chờ thành công!');
    }

    /**
     * Hiển thị chi tiết danh sách chờ
     */
    public function show(WaitingList $waitingList)
    {
        $waitingList->load(['student', 'courseItem']);

        return view('waiting-lists.show', compact('waitingList'));
    }

    /**
     * Hiển thị form chỉnh sửa
     */
    public function edit(WaitingList $waitingList)
    {
        // Lấy tất cả khóa học leaf (không có con) và active
        $courseItems = CourseItem::where('is_leaf', true)
                               ->where('active', true)
                               ->get();
        
        // Nhóm theo cấu trúc cây để hiển thị trong dropdown
        $groupedCourseItems = [];
        foreach ($courseItems as $item) {
            $path = $item->getPathAttribute();
            $groupedCourseItems[$item->id] = $path;
        }
        
        return view('waiting-lists.edit', compact('waitingList', 'groupedCourseItems'));
    }

    /**
     * Cập nhật thông tin danh sách chờ
     */
    public function update(Request $request, WaitingList $waitingList)
    {
        $validatedData = $request->validate([
            'course_item_id' => 'required|exists:course_items,id',
            'interest_level' => 'required|in:low,medium,high',
            'status' => 'required|in:waiting,contacted,enrolled,not_interested',
            'last_contact_date' => 'nullable|date',
            'contact_notes' => 'nullable|string',
        ]);

        $waitingList->update($validatedData);

        return redirect()->route('waiting-lists.show', $waitingList)
                        ->with('success', 'Cập nhật thông tin thành công!');
    }

    /**
     * Xóa khỏi danh sách chờ
     */
    public function destroy(WaitingList $waitingList)
    {
        try {
            $waitingList->delete();
            return redirect()->route('waiting-lists.index')
                            ->with('success', 'Xóa khỏi danh sách chờ thành công!');
        } catch (\Exception $e) {
            return redirect()->route('waiting-lists.index')
                            ->with('error', 'Không thể xóa!');
        }
    }

    /**
     * Cập nhật trạng thái đã liên hệ
     */
    public function markContacted(WaitingList $waitingList)
    {
        $waitingList->update([
            'status' => 'contacted',
            'last_contact_date' => now()->toDateString()
        ]);

        return redirect()->back()
                        ->with('success', 'Đã cập nhật trạng thái liên hệ!');
    }

    /**
     * Đánh dấu không quan tâm
     */
    public function markNotInterested(WaitingList $waitingList)
    {
        $waitingList->update(['status' => 'not_interested']);

        return redirect()->back()
                        ->with('success', 'Đã đánh dấu không quan tâm!');
    }

    /**
     * Danh sách học viên cần liên hệ
     */
    public function needsContact()
    {
        $waitingLists = WaitingList::with(['student', 'courseItem'])
                                  ->where(function($query) {
                                      $query->whereNull('last_contact_date')
                                            ->orWhere('last_contact_date', '<', now()->subDays(7));
                                  })
                                  ->where('status', 'waiting')
                                  ->orderBy('interest_level', 'desc')
                                  ->orderBy('added_date', 'asc')
                                  ->get()
                                  ->map(function($waiting) {
                                      $daysSinceAdded = $waiting->added_date->diffInDays(now());
                                      $daysSinceLastContact = $waiting->last_contact_date 
                                                            ? $waiting->last_contact_date->diffInDays(now())
                                                            : null;
                                      
                                      return [
                                          'waiting' => $waiting,
                                          'days_since_added' => $daysSinceAdded,
                                          'days_since_last_contact' => $daysSinceLastContact,
                                          'priority' => $this->calculateContactPriority($waiting)
                                      ];
                                  })
                                  ->sortByDesc('priority');

        return view('waiting-lists.needs-contact', compact('waitingLists'));
    }

    /**
     * Tính độ ưu tiên liên hệ
     */
    private function calculateContactPriority($waiting)
    {
        $priority = 0;
        
        // Mức độ quan tâm
        switch ($waiting->interest_level) {
            case 'high':
                $priority += 3;
                break;
            case 'medium':
                $priority += 2;
                break;
            case 'low':
                $priority += 1;
                break;
        }

        // Thời gian chưa liên hệ
        $daysSinceLastContact = $waiting->last_contact_date 
                              ? $waiting->last_contact_date->diffInDays(now())
                              : $waiting->added_date->diffInDays(now());
        
        if ($daysSinceLastContact >= 14) {
            $priority += 3;
        } elseif ($daysSinceLastContact >= 7) {
            $priority += 2;
        } elseif ($daysSinceLastContact >= 3) {
            $priority += 1;
        }

        return $priority;
    }

    /**
     * Chuyển học viên từ danh sách chờ sang ghi danh
     */
    public function moveToEnrollment(WaitingList $waitingList)
    {
        // Kiểm tra xem học viên đã ghi danh khóa này chưa
        $existingEnrollment = Enrollment::where('student_id', $waitingList->student_id)
                                       ->where('course_item_id', $waitingList->course_item_id)
                                       ->where('status', 'enrolled')
                                       ->first();
                                       
        if ($existingEnrollment) {
            return back()->withErrors(['error' => 'Học viên đã ghi danh khóa học này rồi!']);
        }
        
        // Chuyển hướng đến trang tạo ghi danh mới với thông tin từ danh sách chờ
        return redirect()->route('enrollments.create', [
            'student_id' => $waitingList->student_id,
            'course_item_id' => $waitingList->course_item_id,
            'waiting_list_id' => $waitingList->id
        ]);
    }

    /**
     * Chuyển học viên từ khóa học chính về danh sách chờ
     */
    public function moveFromEnrollment(Request $request)
    {
        $request->validate([
            'enrollment_id' => 'required|exists:enrollments,id',
            'reason' => 'required|string|max:500'
        ]);
        
        $enrollment = Enrollment::with(['student', 'courseItem'])->findOrFail($request->enrollment_id);
        
        // Kiểm tra học viên đã có trong danh sách chờ khóa này chưa
        $existingWaiting = WaitingList::where('student_id', $enrollment->student_id)
                                    ->where('course_item_id', $enrollment->course_item_id)
                                    ->where('status', 'waiting')
                                    ->first();

        if ($existingWaiting) {
            return back()->withErrors(['error' => 'Học viên đã có trong danh sách chờ khóa học này rồi!']);
        }
        
        // Tạo bản ghi danh sách chờ mới
        $waitingList = WaitingList::create([
            'student_id' => $enrollment->student_id,
            'course_item_id' => $enrollment->course_item_id,
            'added_date' => now(),
            'interest_level' => 'medium',
            'status' => 'waiting',
            'contact_notes' => 'Chuyển từ danh sách chính sang danh sách chờ. Lý do: ' . $request->reason
        ]);
        
        // Cập nhật trạng thái ghi danh
        $enrollment->update([
            'status' => 'cancelled',
            'notes' => $enrollment->notes . "\n[" . now()->format('d/m/Y H:i') . "] Chuyển sang danh sách chờ. Lý do: " . $request->reason
        ]);
        
        return redirect()->route('waiting-lists.show', $waitingList)
                        ->with('success', 'Đã chuyển học viên sang danh sách chờ thành công!');
    }

    /**
     * Báo cáo thống kê danh sách chờ
     */
    public function statistics()
    {
        $stats = [
            'total_waiting' => WaitingList::where('status', 'waiting')->count(),
            'high_interest' => WaitingList::where('status', 'waiting')
                                         ->where('interest_level', 'high')
                                         ->count(),
            'needs_contact' => WaitingList::where(function($query) {
                                      $query->whereNull('last_contact_date')
                                            ->orWhere('last_contact_date', '<', now()->subDays(7));
                                  })->where('status', 'waiting')->count(),
            'converted_this_month' => WaitingList::where('status', 'enrolled')
                                                ->whereMonth('updated_at', now()->month)
                                                ->count(),
        ];

        $waitingByCourse = WaitingList::where('status', 'waiting')
                                     ->with('courseItem')
                                     ->get()
                                     ->groupBy(function($item) {
                                         return $item->courseItem->getPathAttribute();
                                     })
                                     ->map(function($group) {
                                         return [
                                             'count' => $group->count(),
                                             'high_interest' => $group->where('interest_level', 'high')->count()
                                         ];
                                     });

        return view('waiting-lists.statistics', compact('stats', 'waitingByCourse'));
    }
}
