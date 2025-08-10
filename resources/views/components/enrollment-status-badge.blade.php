@props(['status'])

@php
use App\Enums\EnrollmentStatus;

// Xử lý cả trường hợp status là enum object hoặc string
if ($status instanceof EnrollmentStatus) {
    $statusEnum = $status;
} else {
    $statusEnum = EnrollmentStatus::fromString($status);
}
@endphp

@if($statusEnum)
    {!! $statusEnum->badge() !!}
@else
    <span class="badge bg-secondary">{{ $status }}</span>
@endif 