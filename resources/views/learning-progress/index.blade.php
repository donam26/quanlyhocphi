@extends('layouts.app')

@section('page-title', 'Quản lý tiến độ học tập')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Quản lý tiến độ học tập</h5>
                </div>
                <div class="card-body">
                    <h6 class="text-muted mb-3">Danh sách các khóa học đang có lộ trình chưa hoàn thành</h6>

                    @if(count($incompleteCoursesData) === 0)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Không có khóa học nào chưa hoàn thành lộ trình.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach($incompleteCoursesData as $courseData)
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">{{ $courseData['course']->name }}</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Tiến độ hoàn thành</span>
                                <span>{{ $courseData['progress_percentage'] }}%</span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: {{ $courseData['progress_percentage'] }}%;" 
                                     aria-valuenow="{{ $courseData['progress_percentage'] }}" 
                                     aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-users text-primary me-2"></i>
                                    <div>
                                        <div class="small text-muted">Học viên</div>
                                        <div class="fw-bold">{{ $courseData['total_students'] }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-list-check text-warning me-2"></i>
                                    <div>
                                        <div class="small text-muted">Lộ trình</div>
                                        <div class="fw-bold">{{ $courseData['completed_pathways'] }}/{{ $courseData['total_pathways'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="{{ route('learning-progress.course', $courseData['course']) }}" 
                               class="btn btn-primary">
                                <i class="fas fa-eye me-1"></i> Xem chi tiết
                            </a>
                            <a href="{{ route('learning-paths.edit', $courseData['course']->id) }}" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-edit me-1"></i> Chỉnh sửa lộ trình
                            </a>
                        </div>
                    </div>
                    <div class="card-footer text-muted">
                        <small><i class="fas fa-money-bill-wave me-1"></i> Học phí: {{ number_format($courseData['course']->fee) }} đồng</small>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection 