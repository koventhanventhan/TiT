@extends('layouts.admin')

@section('title', 'Pending Student Payments')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <div>
                <h4 class="mb-1" style="font-size: 1.5rem; font-weight: 600; color: var(--adm-text);">Pending Student Payments</h4>
                <div class="d-inline-flex align-items-center mt-1 flex-wrap gap-2">
                    <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.35); font-weight: 600; font-size: 0.8rem; border-radius: 12px; padding: 4px 10px;">
                        <i class="flaticon-381-diamond mr-1"></i> Total Pending: {{ $totalPendingCount ?? 0 }}
                    </span>
                    <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.35); font-weight: 600; font-size: 0.8rem; border-radius: 12px; padding: 4px 10px;">
                        Monthly: {{ $monthlyPendingCount ?? 0 }}
                    </span>
                    <span class="badge" style="background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.35); font-weight: 600; font-size: 0.8rem; border-radius: 12px; padding: 4px 10px;">
                        Subject Top-ups: {{ $topupPendingCount ?? 0 }}
                    </span>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('admin.students.advance-payments') }}" class="btn btn-outline-warning btn-sm mr-2">
                    <i class="flaticon-381-clock mr-1"></i> Advance Requests ({{ $advanceRequestsCount ?? 0 }})
                </a>
                <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="flaticon-381-user-7 mr-1"></i> All Students
                </a>
            </div>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif
@if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif
@if (session('info'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        {{ session('info') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(($advanceRequestsCount ?? 0) > 0)
    <div class="alert alert-warning d-flex align-items-center justify-content-between p-3 mb-4" style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.35); border-radius: 8px;">
        <div style="color: #fbbf24; font-size: 0.9rem;">
            <i class="flaticon-381-warning-1 mr-2" style="font-size: 1.1rem; vertical-align: middle;"></i>
            <strong>{{ $advanceRequestsCount }} Pending Advance Payment Request(s)</strong> awaiting admin approval.
        </div>
        <a href="{{ route('admin.students.advance-payments') }}" class="btn btn-warning btn-sm font-weight-bold" style="white-space: nowrap;">
            Review Advance Requests &rarr;
        </a>
    </div>
@endif

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <h4 class="card-title mb-0">Pending Payments Table</h4>
                <form action="{{ route('admin.students.pending-payments') }}" method="GET" class="d-flex flex-wrap align-items-center gap-2 mb-0">
                    <div class="mr-2">
                        <select name="type" class="form-control form-control-sm" onchange="this.form.submit()" style="background: var(--adm-input-bg); border: 1px solid var(--adm-border); color: var(--adm-text); border-radius: 20px; padding: 4px 12px; height: 32px;">
                            <option value="all" {{ ($type ?? 'all') === 'all' ? 'selected' : '' }}>Type: All Pending</option>
                            <option value="monthly" {{ ($type ?? '') === 'monthly' ? 'selected' : '' }}>Type: Monthly Only</option>
                            <option value="topup" {{ ($type ?? '') === 'topup' ? 'selected' : '' }}>Type: Subject Top-ups</option>
                            <option value="advance" {{ ($type ?? '') === 'advance' ? 'selected' : '' }}>Type: Advance Requests &rarr;</option>
                        </select>
                    </div>

                    <div class="mr-2">
                        <select name="grade" class="form-control form-control-sm" onchange="this.form.submit()" style="background: var(--adm-input-bg); border: 1px solid var(--adm-border); color: var(--adm-text); border-radius: 20px; padding: 4px 12px; height: 32px;">
                            <option value="">All Grades</option>
                            @for($g = 1; $g <= 13; $g++)
                                <option value="{{ $g }}" {{ (string)($grade ?? '') === (string)$g ? 'selected' : '' }}>Grade {{ $g }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="mr-2">
                        <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search student name, phone, index..." class="form-control form-control-sm" style="max-width: 220px; height: 32px; border-radius: 20px; font-size: 0.78rem; background: var(--adm-input-bg) !important; border: 1px solid var(--adm-border) !important; color: var(--adm-text) !important; padding: 4px 12px;">
                    </div>

                    <button type="submit" class="btn btn-primary btn-sm" style="border-radius: 20px; padding: 4px 14px; height: 32px;">
                        Filter
                    </button>
                    @if(!empty($search) || !empty($grade) || ($type ?? 'all') !== 'all')
                        <a href="{{ route('admin.students.pending-payments') }}" class="btn btn-outline-light btn-sm ml-1" style="border-radius: 20px; padding: 4px 12px; height: 32px;">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-responsive-md mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 50px;">#ID</th>
                                <th>STUDENT</th>
                                <th>TYPE</th>
                                <th>MONTH & DETAILS</th>
                                <th>AMOUNT</th>
                                <th>METHOD</th>
                                <th>REQUESTED AT</th>
                                <th style="min-width: 280px;">ACTION</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments as $payment)
                            @php
                                $student = $payment->user;
                                $isTopup = ($payment->kind === 'topup');
                                $topupId = $payment->meta['topup_id'] ?? optional($payment->subjectTopup)->id;
                                $addedSubjects = $payment->meta['added_subjects'] ?? optional($payment->subjectTopup)->added_subjects;
                                if (is_string($addedSubjects)) {
                                    $addedSubjects = json_decode($addedSubjects, true) ?: [$addedSubjects];
                                }
                            @endphp
                            <tr>
                                <td>
                                    <span style="font-weight: 700; font-size: 0.78rem; color: #9ca3af;">#{{ $payment->id }}</span>
                                </td>
                                <td>
                                    @if($student)
                                        <div>
                                            <a href="{{ route('admin.students.edit', $student->id) }}" style="font-weight: 700; color: #ffab2d; text-decoration: none;" title="Edit student details">
                                                {{ $student->full_name ?? $student->name }}
                                            </a>
                                            @if($student->index_number)
                                                <span class="badge badge-info ml-1" style="font-size: 0.65rem; padding: 1px 5px; font-family: monospace;">{{ $student->index_number }}</span>
                                            @endif
                                        </div>
                                        <div style="font-size: 0.72rem; color: #9ca3af; margin-top: 2px;">
                                            <span style="color: #a78bfa; font-weight: 700;">G{{ $student->current_grade ?: '?' }}</span>
                                            <span style="opacity: 0.4; margin: 0 4px;">|</span>
                                            <span style="font-weight: 600;">{{ strtoupper($student->medium ?: 'ENG') }}</span>
                                            <span style="opacity: 0.4; margin: 0 4px;">|</span>
                                            <span>
                                                @if($student->phone_number)
                                                    {{ str_starts_with($student->phone_number, '94') ? '+' . $student->phone_number : $student->phone_number }}
                                                @elseif($student->parent_id && $student->parent && $student->parent->phone_number)
                                                    {{ str_starts_with($student->parent->phone_number, '94') ? '+' . $student->parent->phone_number : $student->parent->phone_number }}
                                                    <span style="font-size: 0.6rem; color: #6b7280;">(parent)</span>
                                                @else
                                                    {{ $student->email }}
                                                @endif
                                            </span>
                                        </div>
                                    @else
                                        <span class="text-muted" style="font-size: 0.8rem;">[Student deleted #{{ $payment->user_id }}]</span>
                                    @endif
                                </td>
                                <td>
                                    @if($isTopup)
                                        <span class="badge badge-warning" style="background: #f59e0b; color: #0f172a; font-weight: 700; padding: 3px 8px; border-radius: 4px;">Subject top-up</span>
                                    @else
                                        <span class="badge badge-primary" style="font-weight: 700; padding: 3px 8px; border-radius: 4px;">Monthly</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #e5e7eb; font-size: 0.85rem;">{{ $payment->year_month }}</div>
                                    @if($isTopup && !empty($addedSubjects) && is_array($addedSubjects))
                                        <div style="font-size: 0.72rem; color: #38bdf8; margin-top: 2px;" title="{{ implode(', ', $addedSubjects) }}">
                                            + {{ implode(', ', $addedSubjects) }}
                                        </div>
                                    @endif
                                    @if($isTopup && $student)
                                        @php
                                            $stPaidPayments = $student->payments()->where('year_month', $payment->year_month)->where('status', 'paid')->get();
                                            $stMonthly = (float) $stPaidPayments->filter(fn($p) => $p->kind !== 'topup')->sum('amount');
                                            $stAdm = method_exists($student, 'admissionFeeForMonth') ? $student->admissionFeeForMonth($payment->year_month, $stPaidPayments) : 0.0;
                                            $stMonthlyNet = max(0.0, $stMonthly - $stAdm);
                                            $stTopups = (float) $stPaidPayments->where('kind', 'topup')->sum('amount');
                                            $stTotal = $stMonthlyNet + $stTopups;
                                        @endphp
                                        <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 3px;">
                                            Paid this month: Rs {{ number_format($stTotal, 2) }} (monthly Rs {{ number_format($stMonthlyNet, 2) }} + top-ups Rs {{ number_format($stTopups, 2) }})
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: #10b981; font-size: 0.9rem;">
                                        Rs {{ number_format((float) $payment->amount, 2) }}
                                    </span>
                                </td>
                                <td>
                                    @if($payment->payment_method === 'online')
                                        <span class="badge badge-info" style="font-size: 0.7rem; padding: 2px 6px;" title="Online checkout initiated, unconfirmed by gateway callback">Online (Unconfirmed)</span>
                                    @else
                                        <span class="badge badge-secondary" style="font-size: 0.7rem; padding: 2px 6px;">Offline</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="font-size: 0.78rem; color: #d1d5db;">
                                        {{ $payment->created_at ? $payment->created_at->format('M d, Y') : '—' }}
                                    </div>
                                    <div style="font-size: 0.68rem; color: #9ca3af;">
                                        {{ $payment->created_at ? $payment->created_at->diffForHumans() : '' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                                        @if($student)
                                            @if($isTopup && $topupId)
                                                <form action="{{ route('admin.students.subject-topups.mark-paid', $topupId) }}" method="POST" class="d-inline-flex align-items-center mb-0" style="gap: 6px;" onsubmit="return confirm('Confirm received payment of Rs {{ number_format((float)$payment->amount, 2) }} for {{ $payment->year_month }}?');">
                                                    @csrf
                                                    @include('admin.partials.received-by-select')
                                                    <button type="submit" class="btn btn-sm btn-success py-1 px-2" style="font-size: 0.75rem; white-space: nowrap;" title="Confirm payment and unlock subjects">
                                                        <i class="flaticon-381-check-2 mr-1"></i> Mark Paid
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.students.payments.mark-paid', [$student->id, $payment->id]) }}" method="POST" class="d-inline-flex align-items-center mb-0" style="gap: 6px;" onsubmit="return confirm('Confirm received payment of Rs {{ number_format((float)$payment->amount, 2) }} for {{ $payment->year_month }}?');">
                                                    @csrf
                                                    @include('admin.partials.received-by-select')
                                                    <button type="submit" class="btn btn-sm btn-success py-1 px-2" style="font-size: 0.75rem; white-space: nowrap;" title="Mark Paid and issue receipt">
                                                        <i class="flaticon-381-check-2 mr-1"></i> Mark Paid
                                                    </button>
                                                </form>
                                            @endif
                                        @endif

                                        <form action="{{ route('admin.students.pending-payments.cancel', $payment->id) }}" method="POST" class="d-inline mb-0" onsubmit="return confirm('Are you sure you want to cancel this pending payment request?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" style="font-size: 0.75rem; white-space: nowrap;" title="Cancel pending payment request">
                                                Cancel
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-5" style="color: #6b7280;">
                                    <div style="font-size: 1.1rem; font-weight: 600; color: var(--adm-text-2); margin-bottom: 6px;">No pending payments found</div>
                                    <div style="font-size: 0.85rem;">All student payments have been reviewed and approved.</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($payments->hasPages())
                <div class="p-3">
                    {{ $payments->links('vendor.pagination.custom') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
