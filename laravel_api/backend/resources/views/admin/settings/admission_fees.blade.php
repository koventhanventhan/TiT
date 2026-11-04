@extends('layouts.admin')

@section('title', 'Admission Fees')

@section('content')
<div class="admission-fees-page">
    <div class="row page-titles mx-0 mb-4">
        <div class="col-sm-6 p-md-0">
            <div class="welcome-text">
                <h4 class="page-title-text mb-0">Admission Fees</h4>
            </div>
        </div>
        <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.packages.index') }}">Payment Settings</a></li>
                <li class="breadcrumb-item active"><a href="javascript:void(0)">Admission Fees</a></li>
            </ol>
        </div>
    </div>

    <!-- 1. First-Time Admission Fees Form (Primary Content First) -->
    <div class="card mb-4" data-testid="admission-fees-form-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="flaticon-381-settings-2 mr-2 text-primary"></i> First-Time Admission Fees
            </h5>
            <span class="badge badge-primary font-weight-bold">Per-Grade Setup</span>
        </div>
        <div class="card-body">
            <p class="mb-4 text-muted-theme">
                Set the admission fee that will be automatically added to a student's first payment. Use the switch to turn the admission fee ON or OFF for specific grades.
            </p>

            <form action="{{ route('admin.settings.admission-fees.store') }}" method="POST" id="admissionFeesForm">
                @csrf
                @php
                    $configStr = \App\Models\SiteSetting::get('admission_fees_config', '{}');
                    $config = json_decode($configStr, true) ?? [];
                    $grades = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13];
                @endphp

                <div class="fee-rows-list">
                    @foreach($grades as $grade)
                        @php
                            $gradeConfig = $config[$grade] ?? ['enabled' => false, 'amount' => 500];
                            $isEnabled = !empty($gradeConfig['enabled']);
                            $amount = $gradeConfig['amount'] ?? 500;
                        @endphp
                        <div class="fee-row {{ !$isEnabled ? 'fee-row--disabled' : '' }}" data-grade="{{ $grade }}">
                            <div class="fee-row__meta">
                                <span class="fee-grade-title">Grade {{ $grade }}</span>
                                <div class="fee-toggle-wrapper">
                                    <label class="switch fee-switch">
                                        <input type="checkbox" 
                                               id="fee_toggle_{{ $grade }}" 
                                               name="fees[{{ $grade }}][enabled]" 
                                               value="1" 
                                               {{ $isEnabled ? 'checked' : '' }} 
                                               onchange="toggleGradeFee(this, {{ $grade }})">
                                        <span class="slider"></span>
                                        <span class="sr-only">Toggle admission fee for Grade {{ $grade }}</span>
                                    </label>
                                    <label for="fee_toggle_{{ $grade }}" class="fee-toggle-text mb-0 cursor-pointer">
                                        {{ $isEnabled ? 'Fee Enabled' : 'Fee Disabled' }}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="fee-row__input-group">
                                <label for="fee_amount_{{ $grade }}" class="fee-amount-label mb-0">Amount (LKR)</label>
                                <div class="fee-input-container">
                                    <input type="number" 
                                           id="fee_amount_{{ $grade }}"
                                           step="0.01" 
                                           min="0" 
                                           inputmode="decimal" 
                                           name="fees[{{ $grade }}][amount]" 
                                           class="form-control fee-control text-right {{ !$isEnabled ? 'is-disabled' : '' }}" 
                                           value="{{ $amount }}"
                                           {{ !$isEnabled ? 'readonly' : '' }}>
                                    @error('fees.' . $grade . '.amount')
                                        <span class="text-danger small mt-1 d-block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-2 text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5 fee-submit-btn">
                        Save Admission Fees
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. How Monthly Payments Work (Help Panel Below Form, Collapsible) -->
    <details class="card mb-4 payment-policy-details" id="paymentPolicyDetails" data-testid="admin-payment-rules-card">
        <summary class="card-header d-flex justify-content-between align-items-center payment-policy-summary">
            <span class="d-flex align-items-center">
                <i class="fa fa-info-circle mr-2 text-primary"></i>
                <span class="h5 card-title mb-0 d-inline">How monthly payments work</span>
            </span>
            <span class="badge badge-info font-weight-bold ml-2">Policy Overview</span>
        </summary>
        <div class="card-body">
            <p class="mb-4 text-muted-theme">
                The platform enforces automated monthly billing, access gating, reminders, and deactivations based on real-time student payment states:
            </p>

            <div class="policy-items-list">
                <div class="policy-item">
                    <div class="policy-badge-col">
                        <span class="badge badge-primary policy-day-badge">Day 1</span>
                    </div>
                    <div class="policy-content-col">
                        <h6 class="policy-item-title mb-1">1st of the Month – Due Date</h6>
                        <p class="policy-item-desc mb-0">
                            Monthly fees become due on the 1st of each month; students without a confirmed payment enter payment due status, temporarily restricting access to class materials and live sessions until settled.
                        </p>
                    </div>
                </div>

                <div class="policy-item">
                    <div class="policy-badge-col">
                        <span class="badge badge-warning text-dark policy-day-badge">Day 2</span>
                    </div>
                    <div class="policy-content-col">
                        <h6 class="policy-item-title mb-1">Automated Payment Reminder</h6>
                        <p class="policy-item-desc mb-0">
                            Automated reminders are dispatched via WhatsApp and Email to all active enrolled students with unpaid balances for the current month.
                        </p>
                    </div>
                </div>

                <div class="policy-item">
                    <div class="policy-badge-col">
                        <span class="badge badge-danger policy-day-badge">Day 5</span>
                    </div>
                    <div class="policy-content-col">
                        <h6 class="policy-item-title mb-1">Automated Account Deactivation</h6>
                        <p class="policy-item-desc mb-0">
                            Students who remain unpaid by Day 5 are automatically deactivated, issued suspension notices via WhatsApp and Email, and a summary is sent to the administrator.
                        </p>
                    </div>
                </div>

                <div class="policy-item">
                    <div class="policy-badge-col">
                        <span class="badge badge-info policy-day-badge">Channels</span>
                    </div>
                    <div class="policy-content-col">
                        <h6 class="policy-item-title mb-1">Payment Channels (Online, Offline, Advance)</h6>
                        <p class="policy-item-desc mb-0">
                            Online payments through PayHere activate access instantly, offline bank slips unlock access once verified and marked paid by an admin, and students can request 1 to 12 months in advance offline.
                        </p>
                    </div>
                </div>

                <div class="policy-item">
                    <div class="policy-badge-col">
                        <span class="badge badge-success policy-day-badge">Receipts</span>
                    </div>
                    <div class="policy-content-col">
                        <h6 class="policy-item-title mb-1">Instant Digital Receipts &amp; Subject Top-Ups</h6>
                        <p class="policy-item-desc mb-0">
                            Official digital receipts are dispatched via Email and WhatsApp immediately after a payment is confirmed, and enrolled students adding subjects mid-month receive a pending top-up for the fee difference while keeping new subjects locked until paid.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </details>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var details = document.getElementById('paymentPolicyDetails');
    if (details) {
        if (window.innerWidth >= 768) {
            details.open = true;
        } else {
            details.open = false;
        }
    }
});

function toggleGradeFee(checkbox, grade) {
    var row = checkbox.closest('.fee-row');
    if (!row) return;
    var input = row.querySelector('.fee-control');
    var label = row.querySelector('.fee-toggle-text');
    if (checkbox.checked) {
        row.classList.remove('fee-row--disabled');
        if (input) {
            input.removeAttribute('readonly');
            input.classList.remove('is-disabled');
        }
        if (label) label.textContent = 'Fee Enabled';
    } else {
        row.classList.add('fee-row--disabled');
        if (input) {
            input.setAttribute('readonly', 'readonly');
            input.classList.add('is-disabled');
        }
        if (label) label.textContent = 'Fee Disabled';
    }
}
</script>
@endsection
