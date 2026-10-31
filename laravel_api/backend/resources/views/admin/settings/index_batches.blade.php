@extends('layouts.admin')

@section('title', 'Index Number Batches')

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
        color: var(--adm-text) !important;
        font-weight: 600;
    }

    .batch-row {
        background: rgba(255, 255, 255, 0.03);
        border: 1.0px solid rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        margin-bottom: 0.75rem;
        color: var(--adm-text);
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.3s ease;
    }

    .batch-row:hover {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(14, 165, 233, 0.4);
    }

    .form-control-batch {
        background: rgba(0, 0, 0, 0.3) !important;
        border: 1.0px solid rgba(255, 255, 255, 0.15) !important;
        color: #38bdf8 !important;
        font-weight: 700;
        font-size: 1.1rem;
        text-align: center;
        border-radius: 0.5rem !important;
        width: 110px;
        height: 42px;
    }

    .form-control-batch:focus {
        border-color: #0ea5e9 !important;
        box-shadow: 0 0 0 0.2rem rgba(14, 165, 233, 0.25) !important;
        outline: none;
    }

    .grade-count-badge {
        background: rgba(255, 255, 255, 0.08);
        color: #cbd5e1;
        border-radius: 20px;
        padding: 3px 10px;
        font-size: 0.8rem;
        font-weight: 500;
        margin-left: 8px;
    }

    .grade-count-badge.has-students {
        background: rgba(14, 165, 233, 0.15);
        color: #38bdf8;
        border: 1px solid rgba(14, 165, 233, 0.3);
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-1">Index Number Batches (Grade &rarr; Batch Year)</h4>
                    <p class="text-muted mb-0" style="font-size: 0.85rem; color: #9ca3af !important;">
                        Configure the default 2-digit batch year (20&ndash;99) for each grade. When confirming or creating a student, their permanent index number is generated automatically from this mapping.
                    </p>
                </div>
                <div>
                    <a href="{{ route('admin.students.assign-index-tool') }}" class="btn btn-info btn-sm">
                        <i class="flaticon-381-id-card-4"></i> Bulk Assign Tool
                    </a>
                </div>
            </div>

            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert" style="background: rgba(16, 185, 129, 0.2); border-color: rgba(16, 185, 129, 0.4); color: #34d399;">
                        <strong>Success!</strong> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4); color: #f87171;">
                        <ul class="mb-0">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <div class="alert alert-info py-2 px-3 mb-4" style="background: rgba(14, 165, 233, 0.1); border: 1px solid rgba(14, 165, 233, 0.25); color: #bae6fd; font-size: 0.85rem; border-radius: 0.5rem;">
                    <i class="flaticon-381-information mr-1"></i>
                    <strong>Important:</strong> Leave a grade's batch year empty if not yet determined. If a student's grade has no mapping, confirming them succeeds without creating an index number, and they can be assigned later using the bulk tool.
                </div>

                <form action="{{ route('admin.settings.index-batches.store') }}" method="POST">
                    @csrf

                    <div class="table-responsive">
                        @foreach($grades as $key => $g)
                            @php
                                $val = old("batches.{$key}", $mapping[$key] ?? ($mapping[(string)$key] ?? ''));
                            @endphp
                            <div class="batch-row">
                                <div class="d-flex align-items-center">
                                    <span style="font-weight: 600; font-size: 1rem; color: var(--adm-text); min-width: 120px;">
                                        {{ $g['display'] }}
                                    </span>
                                    <span class="grade-count-badge {{ $g['count'] > 0 ? 'has-students' : '' }}">
                                        {{ $g['count'] }} {{ \Illuminate\Support\Str::plural('student', $g['count']) }}
                                    </span>
                                    @if(!empty($val))
                                        <span class="badge badge-success ml-2" style="font-size: 0.7rem; background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4);">
                                            Mapped: '{{ $val }}
                                        </span>
                                    @else
                                        <span class="badge badge-secondary ml-2" style="font-size: 0.7rem; background: rgba(255, 255, 255, 0.1); color: var(--adm-text); border: 1px solid var(--adm-border); font-weight: 600;">
                                            Unmapped
                                        </span>
                                    @endif
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <label class="mb-0 mr-2 text-muted" style="font-size: 0.85rem;">Batch Year (YY):</label>
                                    <input type="number" 
                                           name="batches[{{ $key }}]" 
                                           class="form-control form-control-batch" 
                                           value="{{ $val }}" 
                                           min="20" 
                                           max="99" 
                                           placeholder="e.g. 28">
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4 py-2 font-weight-bold">
                            <i class="flaticon-381-save mr-1"></i> Save Batch Mappings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
