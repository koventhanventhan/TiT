@extends('layouts.admin')

@section('title', 'Admission Fees Settings')

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

    .fee-row {
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

    .fee-row:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(235, 129, 83, 0.3);
    }

    .form-control {
        background: rgba(0, 0, 0, 0.2) !important;
        border: 1.0px solid rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
        border-radius: 0.5rem !important;
        width: 150px;
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
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Admission Fees Setup</h4>
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

<div class="row">
    <div class="col-xl-8 offset-xl-2">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.settings.admission-fees.store') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <h5 class="text-primary">First-Time Admission Fees</h5>
                        <p style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">
                            Set the admission fee that will be automatically added to a student's <strong>first</strong> payment. Use the switch to turn the admission fee ON or OFF for specific grades.
                        </p>
                    </div>

                    @php
                        $configStr = \App\Models\SiteSetting::get('admission_fees_config', '{}');
                        $config = json_decode($configStr, true) ?? [];
                        // Ensure grades 1 to 13 exist
                        $grades = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13];
                    @endphp

                    @foreach($grades as $grade)
                        @php
                            $gradeConfig = $config[$grade] ?? ['enabled' => false, 'amount' => 500];
                        @endphp
                        <div class="fee-row">
                            <div class="d-flex align-items-center">
                                <h6 class="mb-0" style="font-size: 1.1rem; width: 120px;">Grade {{ $grade }}</h6>
                                
                                <label class="switch ml-3">
                                    <input type="checkbox" name="fees[{{ $grade }}][enabled]" value="1" {{ $gradeConfig['enabled'] ? 'checked' : '' }}>
                                    <span class="slider"></span>
                                </label>
                                <span class="ml-2" style="color: rgba(255,255,255,0.6); font-size: 0.85rem;">Enable Fee</span>
                            </div>
                            
                            <div class="d-flex align-items-center">
                                <span class="mr-2" style="color: rgba(255,255,255,0.6);">Amount (LKR)</span>
                                <input type="number" step="0.01" min="0" name="fees[{{ $grade }}][amount]" class="form-control text-right" value="{{ $gradeConfig['amount'] }}">
                            </div>
                        </div>
                    @endforeach

                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-primary btn-lg px-5">Save Admission Fees</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
