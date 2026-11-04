@extends('layouts.admin')

@section('title', 'Index Number Series')

@section('content')
<div class="index-series-page">
    <!-- Page Title -->
    <div class="row page-titles mx-0 mb-3">
        <div class="col-sm-6 p-md-0">
            <div class="welcome-text">
                <h4 class="page-title-text mb-0" style="color: var(--adm-text);">Index Number Series & Format Settings</h4>
                <p class="mb-0" style="color: var(--adm-text-2);">Control index number series (01–99), running numbers, and format configuration for {{ $institute->name }}.</p>
            </div>
        </div>
        <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex align-items-center">
            <a href="{{ route('admin.students.assign-index-tool') }}" class="btn btn-outline-warning btn-sm mr-2" style="min-height: 38px; display: inline-flex; align-items: center;">
                <i class="flaticon-381-refresh-1 mr-1"></i> Bulk Tool & Repair
            </a>
            <a href="{{ route('admin.students.index') }}" class="btn btn-outline-light btn-sm" style="min-height: 38px; display: inline-flex; align-items: center;">
                <i class="flaticon-381-user-7 mr-1"></i> Students List
            </a>
        </div>
    </div>

    <!-- 1. Institute Format & Prefix Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0" style="color: var(--adm-text);"><i class="flaticon-381-settings-2 mr-2 text-primary"></i>Format & Prefix Configuration</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.settings.index-series.update-config') }}" method="POST">
                @csrf
                <div class="row">
                    <!-- Format Switch -->
                    <div class="col-md-6 mb-3">
                        <label class="font-weight-bold d-block mb-2" style="color: var(--adm-text);">Index Format</label>
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="format_grade" name="index_format" value="grade" class="custom-control-input" {{ $institute->getIndexFormat() === 'grade' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="format_grade">
                                <strong style="color: var(--adm-text);">Grade format <code>TIT/08/001</code> (recommended)</strong>
                                <div style="color: var(--adm-text-2);" class="small">Series represents student's grade (01–13 or custom 01–99), padded to at least 3 digits.</div>
                            </label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="format_batch" name="index_format" value="batch" class="custom-control-input" {{ $institute->getIndexFormat() === 'batch' ? 'checked' : '' }}>
                            <label class="custom-control-label" for="format_batch">
                                <strong style="color: var(--adm-text);">Batch format (legacy)</strong>
                                <div style="color: var(--adm-text-2);" class="small">Series represents exam year (e.g. '28 for 2028), start sequence typically 100.</div>
                            </label>
                        </div>
                    </div>

                    <!-- Prefix & Start Number -->
                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold" style="color: var(--adm-text);">Index Prefix</label>
                        <input type="text" name="index_prefix" class="form-control form-control-dark" value="{{ $institute->getIndexPrefix() }}" required maxlength="10">
                        <small style="color: var(--adm-text-2);">e.g. <code>TIT</code>. Warning: Changing prefix only affects newly issued numbers.</small>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="font-weight-bold" style="color: var(--adm-text);">Start Number for New Series</label>
                        <input type="number" name="index_seq_start" class="form-control form-control-dark" value="{{ $institute->index_seq_start ?? ($institute->getIndexFormat() === 'grade' ? 1 : 100) }}" required min="1">
                        <small style="color: var(--adm-text-2);">Default start sequence when a series is first used (e.g. 1).</small>
                    </div>
                </div>

                <!-- Format Impact Notice -->
                <div class="format-notice p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <span class="text-warning font-weight-bold"><i class="flaticon-381-warning-1 mr-1"></i> Format Switch Summary:</span>
                            <span class="ml-2" style="color: var(--adm-text);">
                                Switching format does <strong>NOT</strong> rewrite existing numbers.
                                Total students with index: <strong>{{ $totalStudentsWithIndex }}</strong>.
                                Under Grade format: <strong>{{ $oldIfGrade }}</strong> in legacy format.
                                Under Batch format: <strong>{{ $oldIfBatch }}</strong> in legacy format.
                            </span>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <a href="{{ route('admin.students.assign-index-tool') }}" class="btn btn-sm btn-outline-warning" style="min-height: 38px; display: inline-flex; align-items: center;">
                                <i class="flaticon-381-wrench mr-1"></i> Repair Tool
                            </a>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-sm" style="min-height: 38px; display: inline-flex; align-items: center;">
                    <i class="flaticon-381-save mr-1"></i> Save Configuration
                </button>
            </form>
        </div>
    </div>

    <!-- 2. Series Counters Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0" style="color: var(--adm-text);"><i class="flaticon-381-list-1 mr-2 text-primary"></i>Index Number Series & Running Counters</h5>
            <span class="badge badge-info font-weight-bold">Institute Scoped (#{{ $institute->id }})</span>
        </div>
        <div class="card-body">
            <p style="color: var(--adm-text-2);" class="small mb-3">
                Every series that has an active counter is listed below. You can update the <strong>Next Number</strong> directly.
                Rules: The next number must be strictly greater than the highest number already issued in that series (validated against existing students).
                All changes are recorded in the audit log.
            </p>

            <div class="table-responsive">
                <table class="table series-table">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Series (01–99)</th>
                            <th style="width: 20%;">Last Used Number</th>
                            <th style="width: 25%;">Next Automatic Number</th>
                            <th style="width: 15%;">Students Count</th>
                            <th class="action-col" style="width: 25%;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($seriesList as $row)
                            <tr>
                                <td data-label="Series">
                                    <strong style="color: var(--adm-accent); font-size: 1.1rem; letter-spacing: 0.5px;">{{ $row['series'] }}</strong>
                                    <div style="color: var(--adm-text-2);" class="small">
                                        @if((int)$row['series'] <= 13)
                                            Grade {{ (int)$row['series'] }}
                                        @else
                                            Custom / Batch
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Last Used">
                                    @if($row['last_seq'] > 0)
                                        <span class="badge badge-dark font-weight-bold" style="font-size: 0.95rem;">{{ $row['last_seq'] }}</span>
                                        @if($row['highest_issued'] > $row['last_seq'])
                                            <div class="text-warning small">Max student seq: {{ $row['highest_issued'] }}</div>
                                        @endif
                                    @else
                                        <span style="color: var(--adm-text-2);">None used yet</span>
                                    @endif
                                </td>
                                <td data-label="Next Number">
                                    <form action="{{ route('admin.settings.index-series.update-counter') }}" method="POST" class="d-flex align-items-center" id="counter-form-{{ $row['batch_year'] }}">
                                        @csrf
                                        <input type="hidden" name="series" value="{{ $row['batch_year'] }}">
                                        <input type="number" name="next" min="{{ max(1, $row['highest_issued'] + 1) }}" value="{{ $row['next_seq'] }}" class="form-control form-control-dark form-control-sm" style="width: 120px;" required>
                                        <small style="color: var(--adm-text-2);" class="ml-2">Min: {{ max(1, $row['highest_issued'] + 1) }}</small>
                                    </form>
                                </td>
                                <td data-label="Students">
                                    <span class="badge badge-primary px-2 py-1">{{ $row['students_count'] }} student(s)</span>
                                </td>
                                <td data-label="Action" class="series-action-cell action-col">
                                    <button type="submit" form="counter-form-{{ $row['batch_year'] }}" class="btn btn-outline-info btn-sm" style="min-height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="flaticon-381-save mr-1"></i> Update Next
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4" style="color: var(--adm-text-2);">
                                    No active series counters found. Add a series below to pre-set its starting number.
                                </td>
                            </tr>
                        @endforelse

                        <!-- Add Series Row -->
                        <tr style="background: var(--adm-surface-2); border-top: 2px solid var(--adm-border) !important;">
                            <td data-label="New Series">
                                <form action="{{ route('admin.settings.index-series.add-series') }}" method="POST" id="add-series-form">
                                    @csrf
                                    <input type="text" name="series" placeholder="e.g. 08" class="form-control form-control-dark form-control-sm" style="width: 90px;" required maxlength="2">
                                    <small style="color: var(--adm-text-2);">01–99</small>
                                </form>
                            </td>
                            <td data-label="Status">
                                <span style="color: var(--adm-text-2);">New series</span>
                            </td>
                            <td data-label="Next Number">
                                <input type="number" name="next" form="add-series-form" min="1" value="{{ $institute->index_seq_start ?? 1 }}" class="form-control form-control-dark form-control-sm" style="width: 120px;" required>
                                <small style="color: var(--adm-text-2);">Next automatic sequence</small>
                            </td>
                            <td data-label="Students">
                                <span style="color: var(--adm-text-2);">-</span>
                            </td>
                            <td data-label="Action" class="series-action-cell action-col">
                                <button type="submit" form="add-series-form" class="btn btn-success btn-sm" style="min-height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="flaticon-381-add-1 mr-1"></i> + Add Series
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
