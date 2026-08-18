@extends('layouts.admin')

@section('title', 'Create Zoom Class')

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
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Create Zoom Session</h4>
            <div>
                <a href="{{ route('admin.zoom.index') }}" class="btn btn-secondary btn-sm">
                    <i class="flaticon-381-back"></i> Back to Classes
                </a>
            </div>
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

<div class="row">
    <div class="col-xl-12">
        <form action="{{ route('admin.zoom.store') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Session Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Session Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="e.g. Advanced Biology Class" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Zoom Meeting Link</label>
                                <input type="url" name="zoom_link" class="form-control" value="{{ old('zoom_link') }}" placeholder="https://zoom.us/j/...">
                                <small class="text-muted mt-1 d-block">Leave blank to <strong>automatically create</strong> a Zoom meeting.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Date & Time <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Grade </label>
                                <input type="text" name="grade" class="form-control" value="{{ old('grade') }}" placeholder="e.g. Grade 10">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Subject / பாடம்</label>
                                <input type="text" name="subject" class="form-control" value="{{ old('subject') }}" placeholder="e.g. Mathematics">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Medium <span class="text-danger">*</span></label>
                                <select name="medium" class="form-control" required>
                                    <option value="tamil">Tamil</option>
                                    <option value="english">English</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Assign Teachers</label>
                                <select name="teacher_ids[]" class="form-control selectpicker" data-live-search="true" multiple data-size="7" data-actions-box="true">
                                    @foreach($teachers as $t)
                                        <option value="{{ $t->id }}" {{ in_array($t->id, old('teacher_ids', [])) ? 'selected' : '' }}>
                                            {{ $t->name }} ({{ $t->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Schedule Class Now</button>
                        <a href="{{ route('admin.zoom.index') }}" class="btn btn-light ml-2">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
