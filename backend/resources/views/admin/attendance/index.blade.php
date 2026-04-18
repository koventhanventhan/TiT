@extends('layouts.admin')

@section('title', 'Attendance Management')

@push('styles')
<style>
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.1);
    }
    .att-table th {
        background-color: #f8fafc;
    }
</style>
@include('admin.partials.pagination-styles')
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Attendance Monitoring</h4>
            <div>
                <a href="{{ route('admin.zoom.index') }}" class="btn btn-secondary btn-sm">
                    <i class="flaticon-381-video-camera"></i> View Classes
                </a>
            </div>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
    </div>
@endif

@forelse($schedules as $s)
<div class="card mb-4">
    <div class="card-header bg-light">
        <h4 class="card-title text-primary"><strong>{{ $s->title }}</strong></h4>
        <span class="badge badge-outline-dark fs-12">{{ $s->scheduled_at->format('M d, Y @ H:i') }}</span>
    </div>
    <div class="card-body px-0">
        <div class="table-responsive">
            <table class="table table-responsive-md att-table mb-0">
                <thead>
                    <tr>
                        <th class="pl-4" style="width:3.125rem;">#</th>
                        <th>Attendee Name</th>
                        <th>Role Type</th>
                        <th>Current Status</th>
                        <th class="text-right pr-4">Manual Adjustment</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($s->attendances as $index => $att)
                    <tr>
                        <td class="pl-4"><strong>{{ $index + 1 }}</strong></td>
                        <td>{{ $att->user->full_name ?? $att->user->name ?? $att->user->email }}</td>
                        <td>
                            <span class="badge badge-xs light badge-{{ $att->role === 'teacher' ? 'info' : 'secondary' }}">
                                {{ strtoupper($att->role) }}
                            </span>
                        </td>
                        <td>
                            @if($att->status === 'present')
                                <span class="text-success font-w600"><i class="fa fa-circle mr-1"></i> Present</span>
                            @else
                                <span class="text-danger font-w600"><i class="fa fa-circle mr-1"></i> Absent</span>
                            @endif
                        </td>
                        <td class="text-right pr-4">
                            <form action="{{ route('admin.attendance.update') }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="attendance_id" value="{{ $att->id }}">
                                <div class="d-flex justify-content-end">
                                    <select name="status" class="form-control form-control-xs selectpicker" onchange="this.form.submit()" style="width: 6.875rem;">
                                        <option value="present" {{ $att->status === 'present' ? 'selected' : '' }} data-content="<span class='text-success'>Present</span>">Present</option>
                                        <option value="absent" {{ $att->status === 'absent' ? 'selected' : '' }} data-content="<span class='text-danger'>Absent</span>">Absent</option>
                                    </select>
                                </div>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @if($s->attendances->isEmpty())
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="text-muted">
                                <i class="flaticon-381-search-1 display-4 d-block mb-3"></i>
                                Nothing found for this session yet.
                            </div>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@empty
<div class="card">
    <div class="card-body text-center py-5">
        <p class="text-muted mb-0">No Zoom class schedules found in history.</p>
    </div>
</div>
@endforelse

@if($schedules->hasPages())
<div class="card mt-4 mb-5">
    <div class="card-body p-0">
        <div class="pagination-footer m-0" style="border-radius: 0.5rem; border: none;">
            <div class="pagination-info">
                Showing {{ $schedules->firstItem() }} to {{ $schedules->lastItem() }} of {{ $schedules->total() }} results
            </div>
            <div class="pagination-per-page">
                <span>Per page</span>
                <select disabled>
                    <option>10</option>
                    <option selected>15</option>
                    <option>25</option>
                    <option>50</option>
                </select>
            </div>
            {{ $schedules->links('vendor.pagination.custom') }}
        </div>
    </div>
</div>
@endif
@endsection
