@extends('layouts.app')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Tìm kiếm nâng cao</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Tìm kiếm</li>
    </ol>
    
    <!-- Form tìm kiếm -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-search me-1"></i>
            Nhập thông tin tìm kiếm
        </div>
        <div class="card-body">
            <form id="search-form">
                @csrf
                <div class="row align-items-end">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="term" class="form-label">Tìm kiếm theo tên hoặc số điện thoại học viên</label>
                            <div style="width: 100%">
                                <select id="student_search" name="term" class="form-control select2-ajax" style="width: 100%;">
                                    <option value="">Nhập tên hoặc số điện thoại...</option>
                                </select>
                            </div>
                            <div class="invalid-feedback" id="term-error"></div>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex justify-content-end">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary me-2" id="search-btn">
                                <i class="fas fa-search me-1"></i> Tìm kiếm
                            </button>
                            <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-users me-1"></i> Xem tất cả học viên
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Loading indicator -->
    <div id="loading-indicator" class="text-center d-none">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Đang tìm kiếm...</span>
        </div>
        <p class="mt-2">Đang tìm kiếm...</p>
    </div>
    
    <!-- Kết quả tìm kiếm -->
    <div id="search-results" class="d-none">
        <!-- Kết quả sẽ được load bằng JavaScript -->
    </div>
</div>

<!-- Include Search Modals Component -->
@include('components.search-modals')

@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
.search-result-card {
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    margin-bottom: 1rem;
}

.search-result-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.student-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.info-item {
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 0.375rem;
    border-left: 4px solid #007bff;
}

.info-item strong {
    color: #495057;
    font-size: 0.875rem;
}

.info-item span {
    color: #212529;
    font-weight: 500;
}

.course-card {
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 0.5rem;
    background: #fff;
}

.course-card:hover {
    background: #f8f9fa;
}

.payment-status-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.modal-xl {
    max-width: 95%;
}

@media (max-width: 768px) {
    .student-info-grid {
        grid-template-columns: 1fr;
    }
    
    .modal-xl {
        max-width: 100%;
        margin: 0.5rem;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('js/search-modal.js') }}"></script>
@endpush
