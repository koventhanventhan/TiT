@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<!-- Summary Cards Row -->
<div class="row">
    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
        <div class="widget-stat card">
            <div class="card-body p-4">
                <div class="media ai-icon">
                    <span class="mr-3 bgl-primary text-primary" style="width: 3.75rem; height: 3.75rem; display: flex; align-items: center; justify-content: center; border-radius: var(--t-radius-control, 0.5rem); background: var(--t-primary-soft); color: var(--t-primary);">
                        <i class="flaticon-381-user-7" style="font-size: 1.75rem;"></i>
                    </span>
                    <div class="media-body">
                        <p class="mb-1" style="font-size: 0.8125rem; color: var(--t-text-2);">Total Students</p>
                        <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 700; color: var(--t-text);">{{ number_format($totalStudents ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
        <div class="widget-stat card">
            <div class="card-body p-4">
                <div class="media ai-icon">
                    <span class="mr-3 bgl-primary text-primary" style="width: 3.75rem; height: 3.75rem; display: flex; align-items: center; justify-content: center; border-radius: var(--t-radius-control, 0.5rem); background: var(--t-primary-soft); color: var(--t-primary);">
                        <i class="flaticon-381-user-8" style="font-size: 1.75rem;"></i>
                    </span>
                    <div class="media-body">
                        <p class="mb-1" style="font-size: 0.8125rem; color: var(--t-text-2);">Total Teachers</p>
                        <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 700; color: var(--t-text);">{{ number_format($totalTeachers ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
        <div class="widget-stat card">
            <div class="card-body p-4">
                <div class="media ai-icon">
                    <span class="mr-3 bgl-primary text-primary" style="width: 3.75rem; height: 3.75rem; display: flex; align-items: center; justify-content: center; border-radius: var(--t-radius-control, 0.5rem); background: var(--t-primary-soft); color: var(--t-primary);">
                        <i class="flaticon-381-bookmark" style="font-size: 1.75rem;"></i>
                    </span>
                    <div class="media-body">
                        <p class="mb-1" style="font-size: 0.8125rem; color: var(--t-text-2);">Total Courses</p>
                        <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 700; color: var(--t-text);">{{ number_format($totalCourses ?? 0) }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
        <div class="widget-stat card">
            <div class="card-body p-4">
                <div class="media ai-icon">
                    <span class="mr-3 bgl-primary text-primary" style="width: 3.75rem; height: 3.75rem; display: flex; align-items: center; justify-content: center; border-radius: var(--t-radius-control, 0.5rem); background: var(--t-primary-soft); color: var(--t-primary);">
                        <i class="flaticon-381-video-camera" style="font-size: 1.75rem;"></i>
                    </span>
                    <div class="media-body">
                        <p class="mb-1" style="font-size: 0.8125rem; color: var(--t-text-2);">Active Classes (Today)</p>
                        <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 700; color: var(--t-text);">{{ $activeClassesToday ?? 0 }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
        <div class="widget-stat card">
            <div class="card-body p-4">
                <div class="media ai-icon">
                    <span class="mr-3 bgl-primary text-primary" style="width: 3.75rem; height: 3.75rem; display: flex; align-items: center; justify-content: center; border-radius: var(--t-radius-control, 0.5rem); background: var(--t-primary-soft); color: var(--t-primary);">
                        <i class="flaticon-381-diamond" style="font-size: 1.75rem;"></i>
                    </span>
                    <div class="media-body">
                        <p class="mb-1" style="font-size: 0.8125rem; color: var(--t-text-2);">Total Revenue</p>
                        <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 700; color: var(--t-text);">LKR {{ number_format($totalRevenue ?? 0, 2) }}</h4>
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
                        <span class="mr-3 bgl-warning text-warning" style="width: 3.75rem; height: 3.75rem; display: flex; align-items: center; justify-content: center; border-radius: var(--t-radius-control, 0.5rem); background: var(--t-warning-bg); color: var(--t-warning);">
                            <i class="flaticon-381-clock" style="font-size: 1.75rem;"></i>
                        </span>
                        <div class="media-body">
                            <p class="mb-1" style="font-size: 0.8125rem; color: var(--t-text-2);">Pending Payments</p>
                            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 700; color: var(--t-warning);">{{ number_format($pendingPayments ?? 0) }}</h4>
                            @if(isset($pendingMonthly) && isset($pendingTopups))
                                <div style="font-size: 0.72rem; color: var(--t-text-3); margin-top: 2px;">{{ $pendingMonthly }} monthly + {{ $pendingTopups }} top-ups</div>
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
            <div class="card-header-1" style="padding: 1.25rem 1.5625rem; border-bottom: 1.0px solid var(--t-border); background: var(--t-sidebar); color: var(--t-sidebar-text);">
                <h4 class="card-title text-white">Student Registration Trends (Last 7 Days)</h4>
            </div>
            <div class="card-body">
                <div id="registrationActivityChart" class="ct-chart ct-golden-section" style="height: 18.75rem;"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-12">
        <div class="card">
            <div class="card-header-1" style="padding: 1.25rem 1.5625rem; border-bottom: 1.0px solid var(--t-border); background: var(--t-sidebar); color: var(--t-sidebar-text);">
                <h4 class="card-title text-white">Academic Breakdown</h4>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 1.125rem 1.5625rem;">
                        <span><i class="flaticon-381-calendar-1 mr-3 text-primary"></i> Grade 12 Students</span>
                        <span class="badge badge-primary light badge-pill">{{ $academicBreakdown['grade_12'] ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 1.125rem 1.5625rem;">
                        <span><i class="flaticon-381-calendar-1 mr-3 text-info"></i> Grade 13 Students</span>
                        <span class="badge badge-info light badge-pill">{{ $academicBreakdown['grade_13'] ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 1.125rem 1.5625rem;">
                        <span><i class="flaticon-381-star-1 mr-3 text-warning"></i> Arts Stream</span>
                        <span class="badge badge-warning light badge-pill">{{ $academicBreakdown['arts'] ?? 0 }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 1.125rem 1.5625rem;">
                        <span><i class="flaticon-381-heart mr-3 text-danger"></i> Bio/Maths Stream</span>
                        <span class="badge badge-danger light badge-pill">{{ $academicBreakdown['bio_maths'] ?? 0 }}</span>
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
            <div class="card-header-1" style="padding: 1.25rem 1.5625rem; border-bottom: 1.0px solid var(--t-border); background: var(--t-sidebar); color: var(--t-sidebar-text);">
                <h4 class="card-title text-white">Recent Student Registrations</h4>
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
                            @foreach(($recentStudents ?? []) as $student)
                            <tr>
                                <td>
                                    <div style="font-weight: 500;">{{ $student->full_name ?? $student->name }}</div>
                                    <small class="text-muted">{{ $student->email }}</small>
                                </td>
                                <td>
                                    <small>Grade {{ $student->current_grade ?? 'N/A' }}</small><br>
                                    <small class="text-info">{{ strtoupper($student->stream ?? 'N/A') }}</small>
                                </td>
                                <td>
                                    @if($student->admin_confirmed_at)
                                        <span class="badge badge-xs badge-success">Confirmed</span>
                                    @else
                                        <span class="badge badge-xs badge-warning">Pending</span>
                                    @endif
                                </td>
                                <td><small>{{ $student->created_at->format('M d') }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Classes Section -->
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header-1" style="padding: 1.25rem 1.5625rem; border-bottom: 1.0px solid var(--t-border); background: var(--t-sidebar); color: var(--t-sidebar-text);">
                <h4 class="card-title text-white">Upcoming / Active Classes</h4>
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
                                <td style="font-weight: 500;">{{ $class->subject }}</td>
                                <td>Grade {{ $class->grade }}</td>
                                <td><small>{{ $class->scheduled_at->format('M d, h:i A') }}</small></td>
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
                                <td colspan="4" class="text-center py-4">No upcoming classes found</td>
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
            <div class="card-header-1" style="padding: 1.25rem 1.5625rem; border-bottom: 1.0px solid var(--t-border); background: var(--t-sidebar); color: var(--t-sidebar-text);">
                <h4 class="card-title text-white">Action Center</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="p-3 text-center border-right">
                            <h3 style="color: var(--t-primary);">{{ $pendingApprovals ?? 0 }}</h3>
                            <span style="color: var(--t-text-3);">Wait Confirmation</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 text-center border-right">
                            <h3 style="color: var(--t-warning);">{{ $pendingPayments ?? 0 }}</h3>
                            <span style="color: var(--t-text-3);">Payment Issues</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 text-center">
                            <h3 style="color: var(--t-primary);">{{ $totalMaterials ?? 0 }}</h3>
                            <span style="color: var(--t-text-3);">Study Materials</span>
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
