@extends('layouts.admin')

@section('title', 'Subscription & Plan')

@push('styles')
<style>
    .sub-card {
        background: var(--adm-surface, #ffffff);
        border: 1px solid var(--adm-border, #e2e8f0);
        color: var(--adm-text, #0f172a);
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    .sub-muted {
        color: var(--adm-text-3, #94a3b8);
    }
    .sub-secondary {
        color: var(--adm-text-2, #cbd5e1);
    }
    .sub-title {
        color: var(--adm-text, #0f172a);
    }
    .sub-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.8125rem;
        font-weight: 600;
        text-transform: capitalize;
    }
    .sub-badge-active {
        background-color: #dcfce7;
        color: #166534;
    }
    .sub-badge-expired {
        background-color: #fee2e2;
        color: #991b1b;
    }
    .sub-badge-grace {
        background-color: #fef3c7;
        color: #92400e;
    }
    .sub-badge-suspended {
        background-color: #fee2e2;
        color: #991b1b;
    }
    .usage-bar-track {
        background: var(--adm-input-bg, #f1f5f9);
        border-radius: 9999px;
        height: 0.75rem;
        overflow: hidden;
        margin-top: 0.5rem;
    }
    .usage-bar-fill {
        height: 100%;
        border-radius: 9999px;
        transition: width 0.3s ease;
    }
    .usage-bar-ok {
        background-color: #22c55e;
    }
    .usage-bar-near {
        background-color: #f59e0b;
    }
    .usage-bar-full {
        background-color: #ef4444;
    }
    .feature-pill {
        display: inline-flex;
        align-items: center;
        background: var(--adm-surface-2, #f8fafc);
        border: 1px solid var(--adm-border, #e2e8f0);
        color: var(--adm-text, #0f172a);
        padding: 0.375rem 0.875rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        margin: 0.25rem 0.25rem 0.25rem 0;
    }
    .feature-pill i {
        color: #22c55e;
        margin-right: 0.5rem;
    }
    .contact-card {
        background: var(--adm-surface-2, #f8fafc);
        border: 1px dashed var(--adm-border, #cbd5e1);
        color: var(--adm-text, #0f172a);
        border-radius: 0.75rem;
        padding: 1.25rem 1.5rem;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row page-titles mx-0 mb-4">
        <div class="col-sm-6 p-md-0">
            <div class="welcome-text">
                <h4 class="mb-1 sub-title" style="font-weight: 700;">Subscription & Limits</h4>
                <p class="mb-0 sub-secondary">Overview of your current plan, resource usage, and active features.</p>
            </div>
        </div>
        <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active"><a href="javascript:void(0)">Subscription</a></li>
            </ol>
        </div>
    </div>

    @if($summary['in_grace'])
        <div class="alert alert-danger mb-4 p-3 text-white" role="alert" style="background-color: #dc2626; border-color: #b91c1c; border-radius: 0.5rem;">
            <div class="d-flex align-items-center">
                <i class="flaticon-381-warning-1 mr-3" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1 font-weight-bold">
                    Your subscription expired on {{ $summary['expires_at'] ? $summary['expires_at']->format('Y-m-d') : 'recently' }}. Access will stop on {{ $summary['grace_ends_at'] ? $summary['grace_ends_at']->format('Y-m-d') : 'soon' }}. Contact the platform owner to renew.
                </div>
            </div>
        </div>
    @elseif($summary['is_expired'])
        <div class="alert alert-danger mb-4 p-3 text-white" role="alert" style="background-color: #b91c1c; border-color: #991b1b; border-radius: 0.5rem;">
            <div class="d-flex align-items-center">
                <i class="flaticon-381-lock mr-3" style="font-size: 1.5rem;"></i>
                <div class="flex-grow-1 font-weight-bold">
                    Subscription has ended. Public website and student portals are restricted. Please contact the platform owner to renew access.
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        {{-- Plan Overview --}}
        <div class="col-lg-6 col-12">
            <div class="sub-card h-100">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="sub-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em; font-weight: 600;">Current Plan</span>
                        <h3 class="mt-1 mb-0 font-weight-bold sub-title">{{ $summary['plan_name'] }}</h3>
                    </div>
                    <div>
                        @if($summary['is_primary'])
                            <span class="sub-badge sub-badge-active">Primary Platform</span>
                        @elseif($summary['status'] !== 'active')
                            <span class="sub-badge sub-badge-suspended">{{ ucfirst($summary['status']) }}</span>
                        @elseif($summary['in_grace'])
                            <span class="sub-badge sub-badge-grace">In Grace Period</span>
                        @elseif($summary['is_expired'])
                            <span class="sub-badge sub-badge-expired">Expired</span>
                        @else
                            <span class="sub-badge sub-badge-active">Active</span>
                        @endif
                    </div>
                </div>

                <div class="mb-4">
                    <div class="sub-muted" style="font-size: 0.875rem;">Validity & Expiry</div>
                    <div class="font-weight-bold mt-1 sub-title" style="font-size: 1.125rem;">
                        @if($summary['is_primary'] || !$summary['expires_at'])
                            <span>No expiry</span>
                        @else
                            <span>{{ $summary['expires_at']->format('M d, Y') }}</span>
                            @if($summary['is_expired'])
                                <span class="text-danger ml-2 font-weight-normal" style="font-size: 0.9rem;">(Expired)</span>
                            @elseif($summary['days_left'] !== null)
                                <span class="sub-secondary ml-2 font-weight-normal" style="font-size: 0.9rem;">({{ $summary['days_left'] }} days left)</span>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="pt-3" style="border-top: 1px solid var(--adm-border, #e2e8f0);">
                    <h5 class="mb-2 font-weight-bold sub-title" style="font-size: 1rem;">Included Features</h5>
                    @if(!empty($summary['features']))
                        <div class="d-flex flex-wrap">
                            @foreach($summary['features'] as $feature)
                                <div class="feature-pill">
                                    <i class="flaticon-381-check"></i>
                                    <span>{{ $feature }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="sub-muted mb-0" style="font-size: 0.9rem;">Standard institutional features enabled.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Resource Usage --}}
        <div class="col-lg-6 col-12 mt-4 mt-lg-0">
            <div class="sub-card h-100">
                <h5 class="mb-3 font-weight-bold sub-title">Resource Usage</h5>

                {{-- Students Usage --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold sub-title">Students</span>
                        <span class="sub-secondary" style="font-size: 0.9rem;">
                            <strong>{{ number_format($summary['student_used']) }}</strong> /
                            @if($summary['student_limit'] === null)
                                Unlimited
                            @else
                                {{ number_format($summary['student_limit']) }}
                            @endif
                        </span>
                    </div>
                    @if($summary['student_limit'] !== null && $summary['student_limit'] > 0)
                        <div class="usage-bar-track">
                            <div class="usage-bar-fill usage-bar-{{ $summary['student_state'] }}" style="width: {{ $summary['student_percent'] }}%;"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-1 sub-muted" style="font-size: 0.75rem;">
                            <span>{{ $summary['student_percent'] }}% used</span>
                            @if($summary['student_state'] === 'full')
                                <span class="text-danger font-weight-bold">Limit Reached</span>
                            @elseif($summary['student_state'] === 'near')
                                <span class="text-warning font-weight-bold">Near Capacity</span>
                            @endif
                        </div>
                    @else
                        <div class="sub-muted mt-1" style="font-size: 0.8rem;">No student capacity limit on current plan.</div>
                    @endif
                </div>

                {{-- Teachers Usage --}}
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold sub-title">Teachers</span>
                        <span class="sub-secondary" style="font-size: 0.9rem;">
                            <strong>{{ number_format($summary['teacher_used']) }}</strong> /
                            @if($summary['teacher_limit'] === null)
                                Unlimited
                            @else
                                {{ number_format($summary['teacher_limit']) }}
                            @endif
                        </span>
                    </div>
                    @if($summary['teacher_limit'] !== null && $summary['teacher_limit'] > 0)
                        <div class="usage-bar-track">
                            <div class="usage-bar-fill usage-bar-{{ $summary['teacher_state'] }}" style="width: {{ $summary['teacher_percent'] }}%;"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-1 sub-muted" style="font-size: 0.75rem;">
                            <span>{{ $summary['teacher_percent'] }}% used</span>
                            @if($summary['teacher_state'] === 'full')
                                <span class="text-danger font-weight-bold">Limit Reached</span>
                            @elseif($summary['teacher_state'] === 'near')
                                <span class="text-warning font-weight-bold">Near Capacity</span>
                            @endif
                        </div>
                    @else
                        <div class="sub-muted mt-1" style="font-size: 0.8rem;">No teacher capacity limit on current plan.</div>
                    @endif
                </div>

                {{-- Contact Owner Block --}}
                @if(!empty($summary['contact']['email']) || !empty($summary['contact']['phone']) || !empty($summary['contact']['whatsapp']))
                    <div class="contact-card mt-3">
                        <div class="font-weight-bold mb-1 sub-title" style="font-size: 0.9375rem;">
                            <i class="flaticon-381-help-1 mr-1 text-primary"></i> Need more resources or plan upgrade?
                        </div>
                        <p class="sub-secondary mb-2" style="font-size: 0.8125rem;">Contact the platform administrator to extend your limits or renew your plan.</p>
                        <div class="d-flex flex-wrap" style="gap: 1rem; font-size: 0.875rem;">
                            @if(!empty($summary['contact']['email']))
                                <div>
                                    <span class="sub-muted">Email:</span>
                                    <a href="mailto:{{ $summary['contact']['email'] }}" class="font-weight-bold text-primary ml-1">{{ $summary['contact']['email'] }}</a>
                                </div>
                            @endif
                            @if(!empty($summary['contact']['phone']))
                                <div>
                                    <span class="sub-muted">Phone:</span>
                                    <a href="tel:{{ $summary['contact']['phone'] }}" class="font-weight-bold sub-title ml-1">{{ $summary['contact']['phone'] }}</a>
                                </div>
                            @endif
                            @if(!empty($summary['contact']['whatsapp']))
                                <div>
                                    <span class="sub-muted">WhatsApp:</span>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $summary['contact']['whatsapp']) }}" target="_blank" rel="noopener noreferrer" class="font-weight-bold text-success ml-1">
                                        {{ $summary['contact']['whatsapp'] }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
