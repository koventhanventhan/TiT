@extends('layouts.admin')

@section('title', 'Zoom Classes')

@push('styles')
<style>
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.1);
        margin-bottom: 1.25rem;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Zoom Classes</h4>
            <a href="{{ route('admin.timetables.index') }}" class="btn btn-primary btn-sm">
                <i class="flaticon-381-add-1"></i> New Class
            </a>
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

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-responsive-md">
                <thead>
                    <tr>
                        <th style="width:5rem;"><strong>#</strong></th>
                        <th><strong>Session Details</strong></th>
                        <th><strong>Schedule</strong></th>
                        <th><strong>Subject & Grade</strong></th>
                        <th><strong>Teachers</strong></th>
                        <th><strong>Link</strong></th>
                        <th><strong>Actions</strong></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $s)
                    <tr>
                        <td><strong>{{ $s->id }}</strong></td>
                        <td>{{ $s->title }}</td>
                        <td>
                            <div>{{ $s->scheduled_at->format('M d, Y') }}</div>
                            <div class="fs-12 text-muted">{{ $s->scheduled_at->format('H:i') }}</div>
                        </td>
                        <td>
                            <div>{{ $s->subject ?? 'General' }}</div>
                            <div class="fs-12 text-primary">{{ $s->grade ?? 'All Grades' }}</div>
                        </td>
                        <td>
                            @if($s->teachers->count())
                                @foreach($s->teachers as $t)
                                    <div class="badge badge-outline-primary badge-xs mb-1">{{ $t->name }}</div>
                                @endforeach
                            @else
                                <span class="text-muted fs-12">Unassigned</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ $s->zoom_link }}" target="_blank" class="btn btn-xs btn-outline-info">
                                Open
                            </a>
                        </td>
                        <td>
                            <div class="d-flex">
                                <form action="{{ route('admin.zoom.notify', $s->id) }}" method="POST" class="mr-1" onsubmit="return confirm('Send WhatsApp notification to all students and teachers?');">
                                    @csrf
                                    <button type="submit" class="btn btn-success shadow btn-xs sharp mr-1" title="Notify">
                                        <i class="fa fa-paper-plane"></i>
                                    </button>
                                </form>
                                <a href="{{ route('admin.zoom.edit', $s->id) }}" class="btn btn-primary shadow btn-xs sharp mr-1" title="Edit"><i class="fa fa-pencil"></i></a>
                                <form action="{{ route('admin.zoom.destroy', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this zoom class?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger shadow btn-xs sharp" title="Delete"><i class="fa fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">No active zoom sessions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($schedules->hasPages())
    <div class="mt-4">
        {{ $schedules->links() }}
    </div>
@endif
@endsection
