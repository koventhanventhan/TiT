@extends('layouts.admin')

@section('title', 'Student Index Number Lists')

@push('styles')
<style>
    .index-badge {
        font-family: monospace;
        font-weight: 700;
        font-size: 0.85rem;
        color: #38bdf8;
        background: rgba(56, 189, 248, 0.1);
        border: 1px solid rgba(56, 189, 248, 0.3);
        padding: 3px 8px;
        border-radius: 4px;
        letter-spacing: 0.5px;
    }
    .medium-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .both-badge {
        font-size: 0.65rem;
        background: rgba(168, 85, 247, 0.2);
        color: #c084fc;
        border: 1px solid rgba(168, 85, 247, 0.4);
        padding: 2px 6px;
        border-radius: 3px;
        margin-left: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb & Header -->
    <div class="row page-titles mx-0 mb-4">
        <div class="col-sm-6 p-md-0">
            <div class="welcome-text">
                <h4><i class="flaticon-381-list text-primary mr-2"></i> Student Index Number Lists</h4>
                <p class="mb-0">View and download formal index number lists grouped by Grade and Medium.</p>
            </div>
        </div>
        <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.students.index') }}">Students</a></li>
                <li class="breadcrumb-item active"><a href="javascript:void(0)">Index Number Lists</a></li>
            </ol>
        </div>
    </div>

    <!-- Filter & Action Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.students.index-lists') }}" id="gradeSelectForm" class="row align-items-center">
                <div class="col-md-4 mb-2 mb-md-0">
                    <label class="font-weight-bold mr-2 mb-0">Select Grade:</label>
                    <select name="grade" class="form-control" onchange="document.getElementById('gradeSelectForm').submit()" style="background: var(--adm-input-bg); color: var(--adm-text); border-color: var(--adm-border); display: inline-block; width: auto; min-width: 180px;">
                        @foreach($grades as $g)
                            <option value="{{ $g }}" {{ $selectedGrade === $g ? 'selected' : '' }}>{{ $g }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8 text-md-right">
                    <a href="{{ route('admin.students.index-lists.pdf', ['grade' => $selectedGrade, 'medium' => 'english']) }}" target="_blank" class="btn btn-outline-primary btn-sm mr-2">
                        <i class="flaticon-381-print mr-1"></i> English Medium PDF
                    </a>
                    <a href="{{ route('admin.students.index-lists.pdf', ['grade' => $selectedGrade, 'medium' => 'tamil']) }}" target="_blank" class="btn btn-outline-info btn-sm mr-2">
                        <i class="flaticon-381-print mr-1"></i> Tamil Medium PDF
                    </a>
                    <a href="{{ route('admin.students.assign-index-tool') }}" class="btn btn-outline-secondary btn-sm mr-2">
                        <i class="flaticon-381-layer-1 mr-1"></i> Auto Assign
                    </a>
                    <a href="{{ route('admin.students.import-index') }}" class="btn btn-outline-light btn-sm">
                        <i class="flaticon-381-file mr-1"></i> Import CSV
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Medium Tables Row -->
    <div class="row">
        <!-- English Medium Table -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card h-100">
                <div class="medium-card-header" style="background: var(--adm-surface) !important; border-bottom: 1px solid var(--adm-border);">
                    <div>
                        <h5 class="mb-0 font-weight-bold">
                            <i class="flaticon-381-file-1 mr-1"></i> English Medium
                        </h5>
                        <small class="text-muted">{{ $selectedGrade }}</small>
                    </div>
                    <div>
                        <span class="badge badge-light text-dark font-weight-bold px-2 py-1 mr-2">{{ $englishStudents->count() }} Students</span>
                        <a href="{{ route('admin.students.index-lists.pdf', ['grade' => $selectedGrade, 'medium' => 'english']) }}" target="_blank" class="btn btn-xs btn-primary text-white" title="Download PDF">
                            <i class="fa fa-download"></i> PDF
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 45px; text-align: center;">#</th>
                                    <th>Student Name</th>
                                    <th style="width: 140px; text-align: center;">Index Number</th>
                                    <th style="width: 60px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($englishStudents as $idx => $s)
                                    <tr>
                                        <td style="text-align: center; color: #9ca3af;">{{ $idx + 1 }}</td>
                                        <td>
                                            <span class="font-weight-bold">{{ $s->full_name ?? $s->name }}</span>
                                            @if(strtolower($s->medium ?? '') === 'both')
                                                <span class="both-badge" title="Student is enrolled in both English & Tamil media">Both Media</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="index-badge">{{ $s->index_number }}</span>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="{{ route('admin.students.edit', $s->id) }}" class="btn btn-xs btn-outline-info" title="Edit Student">
                                                <i class="flaticon-381-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No assigned index numbers found for English Medium in {{ $selectedGrade }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tamil Medium Table -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card h-100">
                <div class="medium-card-header" style="background: var(--adm-surface) !important; border-bottom: 1px solid var(--adm-border);">
                    <div>
                        <h5 class="mb-0 font-weight-bold">
                            <i class="flaticon-381-file-1 mr-1"></i> Tamil Medium
                        </h5>
                        <small class="text-muted">{{ $selectedGrade }}</small>
                    </div>
                    <div>
                        <span class="badge badge-light text-dark font-weight-bold px-2 py-1 mr-2">{{ $tamilStudents->count() }} Students</span>
                        <a href="{{ route('admin.students.index-lists.pdf', ['grade' => $selectedGrade, 'medium' => 'tamil']) }}" target="_blank" class="btn btn-xs btn-success text-white" title="Download PDF">
                            <i class="fa fa-download"></i> PDF
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 45px; text-align: center;">#</th>
                                    <th>Student Name</th>
                                    <th style="width: 140px; text-align: center;">Index Number</th>
                                    <th style="width: 60px; text-align: right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($tamilStudents as $idx => $s)
                                    <tr>
                                        <td style="text-align: center; color: #9ca3af;">{{ $idx + 1 }}</td>
                                        <td>
                                            <span class="font-weight-bold">{{ $s->full_name ?? $s->name }}</span>
                                            @if(strtolower($s->medium ?? '') === 'both')
                                                <span class="both-badge" title="Student is enrolled in both English & Tamil media">Both Media</span>
                                            @endif
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="index-badge">{{ $s->index_number }}</span>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="{{ route('admin.students.edit', $s->id) }}" class="btn btn-xs btn-outline-info" title="Edit Student">
                                                <i class="flaticon-381-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            No assigned index numbers found for Tamil Medium in {{ $selectedGrade }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Unassigned Students in this Grade (if any) -->
    @if($unassignedStudents->isNotEmpty())
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                        <h5 class="card-title text-warning mb-0">
                            <i class="flaticon-381-warning-1 mr-1"></i> Pending / Not Assigned in {{ $selectedGrade }}
                            <span class="badge badge-warning text-dark ml-2">{{ $unassignedStudents->count() }} Students</span>
                        </h5>
                        <a href="{{ route('admin.students.assign-index-tool') }}" class="btn btn-warning btn-sm">
                            <i class="flaticon-381-layer-1 mr-1"></i> Assign in Bulk
                        </a>
                    </div>
                    <div class="card-body p-0 mt-3">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 45px; text-align: center;">#</th>
                                        <th>Student Name</th>
                                        <th>Medium</th>
                                        <th>Registered Date</th>
                                        <th style="text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($unassignedStudents as $idx => $s)
                                        <tr>
                                            <td style="text-align: center; color: #9ca3af;">{{ $idx + 1 }}</td>
                                            <td class="font-weight-bold">{{ $s->full_name ?? $s->name }}</td>
                                            <td><span class="badge badge-dark text-capitalize">{{ $s->medium ?? 'N/A' }}</span></td>
                                            <td class="text-muted">{{ $s->created_at ? $s->created_at->format('Y-m-d') : '—' }}</td>
                                            <td style="text-align: right;">
                                                <a href="{{ route('admin.students.edit', $s->id) }}" class="btn btn-xs btn-info">
                                                    Assign on Edit Page
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
