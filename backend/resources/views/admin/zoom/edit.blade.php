@extends('layouts.admin')

@section('title', 'Edit Zoom Class')

@push('styles')
<style>
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.1);
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Edit Zoom Session</h4>
            <a href="{{ route('admin.zoom.index') }}" class="btn btn-secondary btn-sm">
                <i class="flaticon-381-back"></i> Back to Classes
            </a>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
    </div>
@endif

<div class="row">
    <div class="col-xl-12">
        <form action="{{ route('admin.zoom.update', $schedule->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Session Details</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Session Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $schedule->title) }}" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Zoom Connection URL</label>
                                <input type="url" name="zoom_link" class="form-control" value="{{ old('zoom_link', $schedule->zoom_link) }}">
                                @if($schedule->meeting_id)
                                    <small class="text-success mt-1 d-block">
                                        <i class="fa fa-check-circle"></i> This is an <strong>automated Zoom meeting</strong>. Changing the time will also update the Zoom meeting.
                                    </small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Reschedule Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at', $schedule->scheduled_at->format('Y-m-d\TH:i')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Subject</label>
                                <input type="text" name="subject" class="form-control" value="{{ old('subject', $schedule->subject) }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Medium <span class="text-danger">*</span></label>
                                <select name="medium" class="form-control" required>
                                    <option value="tamil" {{ old('medium', $schedule->medium) == 'tamil' ? 'selected' : '' }}>Tamil</option>
                                    <option value="english" {{ old('medium', $schedule->medium) == 'english' ? 'selected' : '' }}>English</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Target Grade</label>
                                <input type="text" name="grade" class="form-control" value="{{ old('grade', $schedule->grade) }}">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Update Teachers</label>
                                @php $selectedIds = old('teacher_ids', $schedule->teachers->pluck('id')->toArray()); @endphp
                                <select name="teacher_ids[]" class="form-control selectpicker" data-live-search="true" multiple data-size="7" data-actions-box="true">
                                    @foreach($teachers as $t)
                                        <option value="{{ $t->id }}" {{ in_array($t->id, $selectedIds) ? 'selected' : '' }}>
                                            {{ $t->name }} ({{ $t->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save Session Changes</button>
                        <a href="{{ route('admin.zoom.index') }}" class="btn btn-light ml-2">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
