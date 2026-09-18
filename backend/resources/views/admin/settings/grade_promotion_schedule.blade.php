@extends('layouts.admin')

@section('title', 'Grade Promotion Schedule')

@push('styles')
<style>
    .content-body {
        margin-top: 0 !important;
        padding-top: 1.25rem;
    }

    .card {
        border-radius: 0.75rem;
        box-shadow: 0 0.25rem 1.25rem rgba(0, 0, 0, 0.2);
        margin-bottom: 1.5625rem;
        background: rgba(43, 37, 72, 0.4) !important;
        border: 1.0px solid rgba(255, 255, 255, 0.1);
    }

    .card-header {
        border-bottom: 1.0px solid rgba(255, 255, 255, 0.1);
        background: transparent !important;
    }

    .card-title {
        color: #fff !important;
        font-weight: 600;
    }

    .schedule-row {
        background: rgba(255, 255, 255, 0.03);
        border: 1.0px solid rgba(255,255,255,0.1);
        border-radius: 0.75rem;
        padding: 1rem;
        margin-bottom: 1rem;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.3s ease;
    }

    .schedule-row:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(235, 129, 83, 0.3);
    }

    .schedule-row.disabled-row .date-input {
        opacity: 0.35;
        pointer-events: none;
    }

    .form-control {
        background: rgba(0, 0, 0, 0.2) !important;
        border: 1.0px solid rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
        border-radius: 0.5rem !important;
        width: 180px;
    }

    .form-control:focus {
        border-color: #EB8153 !important;
        box-shadow: 0 0 0 0.2rem rgba(235, 129, 83, 0.25) !important;
    }

    .text-primary {
        color: #EB8153 !important;
    }

    /* Custom Switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 24px;
        margin-bottom: 0;
    }

    .switch input { 
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(255, 255, 255, 0.2);
        transition: .4s;
        border-radius: 24px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: #EB8153;
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Grade Promotion Schedule</h4>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-xl-8 offset-xl-2">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.settings.grade-promotion-schedule.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <h5 class="text-primary">Scheduled Auto-Promotion</h5>
                        <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">
                            Set a <strong>one-time</strong> promotion date for each grade. On the scheduled date, all students in that grade will be automatically promoted to the next grade at 01:30 AM. The toggle automatically switches OFF after the promotion runs.
                        </p>
                        <p style="color: rgba(255,255,255,0.5); font-size: 0.8rem;">
                            <i class="flaticon-381-warning-1 mr-1"></i>
                            You can also run it manually: <code style="color: #EB8153;">php artisan promotions:auto-run</code>
                        </p>
                    </div>

                    @php
                        $configStr = \App\Models\SiteSetting::get('grade_promotion_schedule', '{}');
                        $config = json_decode($configStr, true) ?? [];
                        $grades = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13];
                    @endphp

                    @foreach($grades as $grade)
                        @php
                            $gradeConfig = $config[(string)$grade] ?? ['enabled' => false, 'date' => null];
                            $isEnabled = !empty($gradeConfig['enabled']);
                        @endphp
                        <div class="schedule-row {{ !$isEnabled ? 'disabled-row' : '' }}" id="row-{{ $grade }}">
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0" style="font-size: 1.1rem; width: 120px;">Grade {{ $grade }}</h6>
                                
                                <label class="switch ml-3">
                                    <input type="checkbox" 
                                           name="schedule[{{ $grade }}][enabled]" 
                                           value="1" 
                                           {{ $isEnabled ? 'checked' : '' }}
                                           onchange="toggleDateInput({{ $grade }}, this.checked)">
                                    <span class="slider"></span>
                                </label>
                                <span class="ml-2" style="color: rgba(255,255,255,0.6); font-size: 0.85rem;">
                                    {{ $grade >= 13 ? 'Graduate' : 'Promote to Grade ' . ($grade + 1) }}
                                </span>
                            </div>
                            
                            <div class="d-flex align-items-center date-input">
                                <span class="mr-2" style="color: rgba(255,255,255,0.6);">Date</span>
                                <input type="date" 
                                       name="schedule[{{ $grade }}][date]" 
                                       class="form-control" 
                                       id="date-{{ $grade }}"
                                       value="{{ $gradeConfig['date'] ?? '' }}"
                                       {{ !$isEnabled ? 'disabled' : '' }}>
                            </div>
                        </div>
                    @endforeach

                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-primary btn-lg px-5">Save Promotion Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleDateInput(grade, checked) {
        var row = document.getElementById('row-' + grade);
        var dateInput = document.getElementById('date-' + grade);
        if (checked) {
            row.classList.remove('disabled-row');
            dateInput.disabled = false;
        } else {
            row.classList.add('disabled-row');
            dateInput.disabled = true;
        }
    }
</script>
@endpush
