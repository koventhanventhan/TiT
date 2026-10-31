@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<!-- Summary Cards Row -->
<div class="row">
    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
        <div class="widget-stat card">
            <div class="card-body p-4">
                <div class="media ai-icon">
                    <span class="mr-3" style="width: 3.75rem; height: 3.75rem; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: rgba(255, 255, 255, 0.08); color: var(--adm-accent); border: 1px solid var(--adm-border);">
                        <i class="flaticon-381-user-7" style="font-size: 1.75rem;"></i>
                    </span>
                    <div class="media-body">
                        <p class="mb-1" style="font-size: 0.8125rem; color: var(--adm-text-2);">Total Students</p>
                        <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 700; color: var(--adm-text);">{{ number_format($totalStudents ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
        <div class="widget-stat card">
            <div class="card-body p-4">
                <div class="media ai-icon">
                    <span class="mr-3" style="width: 3.75rem; height: 3.75rem; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: rgba(255, 255, 255, 0.08); color: var(--adm-accent); border: 1px solid var(--adm-border);">
                        <i class="flaticon-381-user-8" style="font-size: 1.75rem;"></i>
                    </span>
                    <div class="media-body">
                        <p class="mb-1" style="font-size: 0.8125rem; color: var(--adm-text-2);">Total Teachers</p>
                        <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 700; color: var(--adm-text);">{{ number_format($totalTeachers ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
        <div class="widget-stat card">
            <div class="card-body p-4">
                <div class="media ai-icon">
                    <span class="mr-3" style="width: 3.75rem; height: 3.75rem; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: rgba(255, 255, 255, 0.08); color: var(--adm-accent); border: 1px solid var(--adm-border);">
                        <i class="flaticon-381-bookmark" style="font-size: 1.75rem;"></i>
                    </span>
                    <div class="media-body">
                        <p class="mb-1" style="font-size: 0.8125rem; color: var(--adm-text-2);">Total Courses</p>
                        <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 700; color: var(--adm-text);">{{ number_format($totalCourses ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
        <div class="widget-stat card">
            <div class="card-body p-4">
                <div class="media ai-icon">
                    <span class="mr-3" style="width: 3.75rem; height: 3.75rem; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: rgba(255, 255, 255, 0.08); color: var(--adm-accent); border: 1px solid var(--adm-border);">
                        <i class="flaticon-381-video-camera" style="font-size: 1.75rem;"></i>
                    </span>
                    <div class="media-body">
                        <p class="mb-1" style="font-size: 0.8125rem; color: var(--adm-text-2);">Active Classes (Today)</p>
                        <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 700; color: var(--adm-text);">{{ $activeClassesToday ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
        <div class="widget-stat card">
            <div class="card-body p-4">
                <div class="media ai-icon">
                    <span class="mr-3" style="width: 3.75rem; height: 3.75rem; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: rgba(255, 255, 255, 0.08); color: var(--adm-accent); border: 1px solid var(--adm-border);">
                        <i class="flaticon-381-diamond" style="font-size: 1.75rem;"></i>
                    </span>
                    <div class="media-body">
                        <p class="mb-1" style="font-size: 0.8125rem; color: var(--adm-text-2);">Total Revenue</p>
                        <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 700; color: var(--adm-text);">LKR {{ number_format($totalRevenue ?? 0, 2) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
        <a href="{{ route('admin.students.pending-payments') }}" style="text-decoration: none;">
            <div class="widget-stat card" style="transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='none'">
                <div class="card-body p-4">
                    <div class="media ai-icon">
                        @php $hasPending = ($pendingPayments ?? 0) > 0; @endphp
                        <span class="mr-3" style="width: 3.75rem; height: 3.75rem; display: flex; align-items: center; justify-content: center; border-radius: 12px; background: {{ $hasPending ? 'rgba(245, 158, 11, 0.15)' : 'rgba(255, 255, 255, 0.08)' }}; color: {{ $hasPending ? '#fbbf24' : 'var(--adm-accent)' }}; border: 1px solid {{ $hasPending ? 'rgba(245, 158, 11, 0.3)' : 'var(--adm-border)' }};">
                            <i class="flaticon-381-clock" style="font-size: 1.75rem;"></i>
                        </span>
                        <div class="media-body">
                            <p class="mb-1" style="font-size: 0.8125rem; color: var(--adm-text-2);">Pending Payments</p>
                            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 700; color: {{ $hasPending ? '#fbbf24' : 'var(--adm-text)' }};">{{ number_format($pendingPayments ?? 0) }}</h4>
                            @if(isset($pendingMonthly) && isset($pendingTopups))
                                <div style="font-size: 0.72rem; color: var(--adm-text-3); margin-top: 2px;">{{ $pendingMonthly }} monthly + {{ $pendingTopups }} top-ups</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Charts Section -->
<div class="row">
    <div class="col-xl-8 col-lg-12">
        <div class="card">
            <div class="card-header-1" style="padding: 1.25rem 1.5625rem; border-bottom: 1.0px solid var(--adm-border); background: var(--adm-surface); color: var(--adm-text);">
                <h4 class="card-title">Student Registration Trends (Last 7 Days)</h4>
            </div>
            <div class="card-body">
                @php
                    $hasRegistrations = isset($chartData) && is_array($chartData) && array_sum($chartData) > 0;
                @endphp
                @if($hasRegistrations)
                    <div id="registrationActivityChart" class="ct-chart ct-golden-section" style="height: 18.75rem;"></div>
                @else
                    <div class="d-flex flex-column align-items-center justify-content-center text-center py-5" style="min-height: 18.75rem;">
                        <i class="flaticon-381-calendar-1 mb-3" style="font-size: 3rem; color: var(--adm-accent); opacity: 0.7;"></i>
                        <h5 class="mb-1" style="color: var(--adm-text); font-weight: 600;">No registrations in the last 7 days</h5>
                        <p class="mb-0" style="color: var(--adm-text-3); font-size: 0.85rem;">New student signups will appear here as trends develop.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-12">
        <div class="card">
            <div class="card-header-1" style="padding: 1.25rem 1.5625rem; border-bottom: 1.0px solid var(--adm-border); background: var(--adm-surface); color: var(--adm-text);">
                <h4 class="card-title">Academic Breakdown</h4>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 1.125rem 1.5625rem; border-bottom: 1px solid var(--adm-border);">
                        <span><i class="flaticon-381-calendar-1 mr-3" style="color: var(--adm-accent);"></i> Grade 12 Students</span>
                        <span class="badge badge-primary light badge-pill">{{ $academicBreakdown['grade_12'] ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 1.125rem 1.5625rem; border-bottom: 1px solid var(--adm-border);">
                        <span><i class="flaticon-381-calendar-1 mr-3" style="color: var(--adm-accent);"></i> Grade 13 Students</span>
                        <span class="badge badge-primary light badge-pill">{{ $academicBreakdown['grade_13'] ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 1.125rem 1.5625rem; border-bottom: 1px solid var(--adm-border);">
                        <span><i class="flaticon-381-star-1 mr-3" style="color: var(--adm-accent);"></i> Arts Stream</span>
                        <span class="badge badge-primary light badge-pill">{{ $academicBreakdown['arts'] ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 1.125rem 1.5625rem; border-bottom: none;">
                        <span><i class="flaticon-381-heart mr-3" style="color: var(--adm-accent);"></i> Bio/Maths Stream</span>
                        <span class="badge badge-primary light badge-pill">{{ $academicBreakdown['bio_maths'] ?? 0 }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Activity Section -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header-1" style="padding: 1.25rem 1.5625rem; border-bottom: 1.0px solid var(--adm-border); background: var(--adm-surface); color: var(--adm-text);">
                <h4 class="card-title">Recent Student Registrations</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-md">
                        <thead>
                            <tr>
                                <th style="font-weight: 600;">Student</th>
                                <th style="font-weight: 600;">Academic</th>
                                <th style="font-weight: 600;">Status</th>
                                <th style="font-weight: 600;">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($recentStudents ?? []) as $student)
                            <tr>
                                <td>
                                    <div style="font-weight: 500; color: var(--adm-text);">{{ $student->full_name ?? $student->name }}</div>
                                    <small style="color: var(--adm-text-3);">{{ $student->email }}</small>
                                </td>
                                <td>
                                    <small style="color: var(--adm-text-2);">Grade {{ $student->current_grade ?? 'N/A' }}</small><br>
                                    <small style="color: var(--adm-accent);">{{ strtoupper($student->stream ?? 'N/A') }}</small>
                                </td>
                                <td>
                                    @if($student->admin_confirmed_at)
                                        <span class="badge badge-xs badge-success">Confirmed</span>
                                    @else
                                        <span class="badge badge-xs badge-warning">Pending</span>
                                    @endif
                                </td>
                                <td><small style="color: var(--adm-text-3);">{{ $student->created_at->format('M d') }}</small></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4" style="color: var(--adm-text-3);">No recent registrations</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Classes Section -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header-1" style="padding: 1.25rem 1.5625rem; border-bottom: 1.0px solid var(--adm-border); background: var(--adm-surface); color: var(--adm-text);">
                <h4 class="card-title">Upcoming / Active Classes</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-md">
                        <thead>
                            <tr>
                                <th style="font-weight: 600;">Subject</th>
                                <th style="font-weight: 600;">Grade</th>
                                <th style="font-weight: 600;">Scheduled At</th>
                                <th style="font-weight: 600;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($upcomingClasses ?? []) as $class)
                            <tr>
                                <td style="font-weight: 500; color: var(--adm-text);">{{ $class->subject }}</td>
                                <td style="color: var(--adm-text-2);">Grade {{ $class->grade }}</td>
                                <td><small style="color: var(--adm-text-3);">{{ $class->scheduled_at->format('M d, h:i A') }}</small></td>
                                <td>
                                    @if($class->scheduled_at->isPast() && $class->scheduled_at->addMinutes($class->duration)->isFuture())
                                        <span class="badge badge-xs badge-danger blink">LIVE</span>
                                    @else
                                        <a href="{{ $class->join_url }}" target="_blank" class="btn btn-primary btn-xs sharp"><i class="fa fa-video-camera"></i></a>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4" style="color: var(--adm-text-3);">No upcoming classes found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Center Area -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header-1" style="padding: 1.25rem 1.5625rem; border-bottom: 1.0px solid var(--adm-border); background: var(--adm-surface); color: var(--adm-text);">
                <h4 class="card-title">Action Center</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="p-3 text-center border-right" style="border-color: var(--adm-border) !important;">
                            <h3 style="color: var(--adm-accent);">{{ $pendingApprovals ?? 0 }}</h3>
                            <span style="color: var(--adm-text-3);">Wait Confirmation</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 text-center border-right" style="border-color: var(--adm-border) !important;">
                            <h3 style="color: #fbbf24;">{{ $pendingPayments ?? 0 }}</h3>
                            <span style="color: var(--adm-text-3);">Payment Issues</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 text-center">
                            <h3 style="color: var(--adm-accent);">{{ $totalMaterials ?? 0 }}</h3>
                            <span style="color: var(--adm-text-3);">Study Materials</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin-theme/js/dashboard/dashboard-1.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Registration Activity Chart
        @if($hasRegistrations)
        if (typeof Chartist !== 'undefined') {
            new Chartist.Bar('#registrationActivityChart', {
                labels: {!! json_encode($chartLabels ?? []) !!},
                series: [{!! json_encode($chartData ?? []) !!}]
            }, {
                low: 0,
                showArea: true,
                fullWidth: true,
                axisY: {
                    onlyInteger: true,
                    offset: 20
                }
            });
        }
        @endif
    });
</script>
<style>
    @keyframes blink {
        0% { opacity: 1; }
        50% { opacity: 0.4; }
        100% { opacity: 1; }
    }
    .blink {
        animation: blink 1s linear infinite;
    }
</style>
@endpush
