<?php

namespace App\Http\Controllers;

use App\Models\WaitingList;
use App\Models\Student;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WaitingListController extends Controller
{
    /**
     * Hiển thị danh sách chờ
     */
    public function index(Request $request)
    {
        $query = WaitingList::with(['student', 'course.major']);

        // Lọc theo khóa học
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Lọc theo mức độ quan tâm
        if ($request->filled('interest_level')) {
            $query->where('interest_level', $request->interest_level);
        }

        // Lọc học viên cần liên hệ
        if ($request->filled('needs_contact') && $request->needs_contact) {
            $query->where(function($q) {
                $q->whereNull('last_contact_date')
                  ->orWhere('last_contact_date', '<', now()->subDays(7));
            });
        }

        $waitingLists = $query->orderBy('added_date', 'desc')->paginate(20);
        $courses = Course::all();

        return view('waiting-lists.index', compact('waitingLists', 'courses'));
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

        $courses = Course::with('major')->where('active', true)->get();

        return view('waiting-lists.create', compact('student', 'courses'));
    }

    /**
     * Lưu học viên vào danh sách chờ
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'student_id' => 'required|exists:students,id',
            'course_id' => 'required|exists:courses,id',
            'added_date' => 'required|date',
            'interest_level' => 'required|in:low,medium,high',
            'contact_notes' => 'nullable|string',
        ]);

        // Kiểm tra học viên đã có trong danh sách chờ khóa này chưa
        $existingWaiting = WaitingList::where('student_id', $validatedData['student_id'])
                                    ->where('course_id', $validatedData['course_id'])
                                    ->where('status', 'waiting')
                                    ->first();

        if ($existingWaiting) {
            return back()->withErrors(['error' => 'Học viên đã có trong danh sách chờ khóa học này rồi!']);
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
        $waitingList->load(['student', 'course.major']);

        return view('waiting-lists.show', compact('waitingList'));
    }

    /**
     * Hiển thị form chỉnh sửa
     */
    public function edit(WaitingList $waitingList)
    {
        $courses = Course::with('major')->where('active', true)->get();

        return view('waiting-lists.edit', compact('waitingList', 'courses'));
    }

    /**
     * Cập nhật thông tin danh sách chờ
     */
    public function update(Request $request, WaitingList $waitingList)
    {
        $validatedData = $request->validate([
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
        $waitingLists = WaitingList::with(['student', 'course'])
                                  ->needContact()
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
     * Chuyển sang ghi danh (redirect to enrollment controller)
     */
    public function moveToEnrollment(WaitingList $waitingList)
    {
        return redirect()->route('enrollments.create', [
            'student_id' => $waitingList->student_id,
            'course_id' => $waitingList->course_id,
            'waiting_list_id' => $waitingList->id
        ]);
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
            'needs_contact' => WaitingList::needContact()->count(),
            'converted_this_month' => WaitingList::where('status', 'enrolled')
                                                ->whereMonth('updated_at', now()->month)
                                                ->count(),
        ];

        $waitingByCourse = WaitingList::where('status', 'waiting')
                                     ->with('course')
                                     ->get()
                                     ->groupBy('course.name')
                                     ->map(function($group) {
                                         return [
                                             'count' => $group->count(),
                                             'high_interest' => $group->where('interest_level', 'high')->count()
                                         ];
                                     });

        return view('waiting-lists.statistics', compact('stats', 'waitingByCourse'));
    }
}
