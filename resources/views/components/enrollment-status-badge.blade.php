@props(['status'])

@php
use App\Enums\EnrollmentStatus;

$statusEnum = EnrollmentStatus::fromString($status);
@endphp

@if($statusEnum)
    {!! $statusEnum->badge() !!}
@else
    <span class="badge bg-secondary">{{ $status }}</span>
@endif 