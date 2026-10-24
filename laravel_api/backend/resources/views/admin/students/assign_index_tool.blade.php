@extends('layouts.admin')

@section('title', 'Assign Index Numbers to Existing Students')

@push('styles')
<link href="{{ asset('admin-theme/vendor/sweetalert2/dist/sweetalert2.min.css') }}" rel="stylesheet">
<style>
    .stat-badge {
        font-size: 1.1rem;
        font-weight: 700;
        padding: 0.5rem 1rem;
        border-radius: 8px;
    }
    .grade-chip {
        display: inline-block;
        padding: 6px 14px;
        margin: 4px;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.15);
        cursor: pointer;
        transition: all 0.2s ease;
        user-select: none;
    }
    .grade-chip input {
        display: none;
    }
    .grade-chip.active {
        background: #eb8153;
        border-color: #eb8153;
        color: #fff;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(235, 129, 83, 0.4);
    }
    .section-box {
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .radio-card {
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: border-color 0.2s;
    }
    .radio-card:hover {
        border-color: #eb8153;
    }
    .radio-card.selected {
        border-color: #eb8153;
        background: rgba(235, 129, 83, 0.08);
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="mb-1" style="font-size: 1.5rem; font-weight: 700; color: #fff;">
                    <i class="flaticon-381-layer-1 text-primary mr-2"></i> Assign Index Numbers in Bulk
                </h4>
                <p class="text-muted mb-0">Safely assign unique permanent index numbers across all matching students with instant preview and progress tracking.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.settings.index-batches') }}" class="btn btn-outline-warning btn-sm">
                    <i class="flaticon-381-settings-4"></i> Grade-Batch Mapping
                </a>
                <a href="{{ route('admin.students.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="flaticon-381-list"></i> Back to Students
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Step 1: Configuration & Filters -->
    <div class="col-xl-6 col-lg-6">
        <div class="card h-100">
            <div class="card-header border-0 pb-0">
                <h5 class="card-title text-white">1. Configure Scope & Options</h5>
            </div>
            <div class="card-body">
                <form id="filterForm">
                    <!-- Status / Overview Badge -->
                    <div class="alert alert-info py-2 px-3 d-flex justify-content-between align-items-center" style="background: rgba(56, 189, 248, 0.1); border-color: rgba(56, 189, 248, 0.3); color: #38bdf8;">
                        <div>
                            <strong>{{ $unassignedCount }}</strong> unassigned active student(s)
                            <small class="d-block text-muted">Out of {{ $totalActiveCount }} confirmed students</small>
                        </div>
                        <span class="badge badge-primary">Institute: {{ $institute->name ?? 'Default' }}</span>
                    </div>

                    <!-- Grade Filter -->
                    <div class="section-box">
                        <label class="text-white font-weight-bold d-flex justify-content-between">
                            <span>Select Grades</span>
                            <span>
                                <a href="javascript:void(0)" id="selectAllGrades" class="small text-primary mr-2">Select All</a>
                                <a href="javascript:void(0)" id="clearAllGrades" class="small text-muted">Clear</a>
                            </span>
                        </label>
                        <div class="d-flex flex-wrap mt-2">
                            @forelse($gradesData as $g)
                                <label class="grade-chip active" id="chip-{{ md5($g->current_grade) }}">
                                    <input type="checkbox" name="grades[]" value="{{ $g->current_grade }}" checked>
                                    {{ $g->current_grade }}
                                    <span class="badge badge-dark ml-1" style="font-size: 0.75rem;">{{ $g->total_count - $g->assigned_count }} unassigned</span>
                                </label>
                            @empty
                                <span class="text-muted small">No student grade records found.</span>
                            @endforelse
                        </div>
                    </div>

                    <!-- Medium Filter -->
                    <div class="section-box">
                        <label class="text-white font-weight-bold mb-2">Medium</label>
                        <select name="medium" id="mediumFilter" class="form-control" style="background: #1f2937; color: #fff; border-color: #4b5563;">
                            <option value="all">All Media (English, Tamil, Both)</option>
                            <option value="english">English Medium (including Both)</option>
                            <option value="tamil">Tamil Medium (including Both)</option>
                            <option value="both">Both Media Only</option>
                        </select>
                    </div>

                    @if(optional($institute)->getIndexFormat() === 'grade')
                        <!-- Grade Mode Numbering Format -->
                        <div class="section-box">
                            <label class="text-white font-weight-bold mb-2">Numbering Format</label>
                            <div class="alert alert-primary py-2 px-3 mb-0" style="background: rgba(79, 70, 229, 0.15); border-color: rgba(79, 70, 229, 0.4); color: #a5b4fc;">
                                Index numbers will be generated as <strong>{{ $institute->getIndexPrefix() }}/&lt;Grade&gt;/&lt;Seq&gt;</strong> (e.g. <code>{{ $institute->getIndexPrefix() }}/08/001</code>). Each grade has its own independent counter.
                            </div>
                            <input type="hidden" name="batch_source" value="mapping">
                        </div>
                    @else
                        <!-- Batch Year Source (Legacy Batch Mode) -->
                        <div class="section-box">
                            <label class="text-white font-weight-bold mb-2">Batch Year Source</label>
                            
                            <div class="radio-card selected" onclick="selectBatchSource('mapping')">
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="batchSourceMapping" name="batch_source" value="mapping" class="custom-control-input" checked>
                                    <label class="custom-control-label font-weight-bold text-white" for="batchSourceMapping">
                                        Use Grade Mapping (Default & Recommended)
                                    </label>
                                </div>
                                <small class="text-muted d-block ml-4">
                                    Each student's batch year is resolved automatically from the 
                                    <a href="{{ route('admin.settings.index-batches') }}" target="_blank" class="text-primary">Grade &rarr; Batch settings</a>. Unmapped grades will be skipped safely.
                                </small>
                            </div>

                            <div class="radio-card" onclick="selectBatchSource('single')">
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="batchSourceSingle" name="batch_source" value="single" class="custom-control-input">
                                    <label class="custom-control-label font-weight-bold text-white" for="batchSourceSingle">
                                        One Single Batch Year for Everyone
                                    </label>
                                </div>
                                <div class="mt-2 ml-4" id="singleBatchContainer" style="display: none;">
                                    <div class="d-flex align-items-center">
                                        <span class="mr-2 text-white">Batch YY:</span>
                                        <input type="number" name="single_batch_year" id="singleBatchInput" class="form-control" style="width: 100px; font-weight: bold; font-size: 1.1rem; color: #fff; background: #1f2937; border-color: #4b5563;" min="20" max="99" placeholder="e.g. 28">
                                    </div>
                                    <small class="text-warning d-block mt-1">Caution: All selected students will receive this identical batch year.</small>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Sequence Numbering Order -->
                    <div class="section-box">
                        <label class="text-white font-weight-bold mb-2">Numbering Sequence Order</label>
                        <p class="small text-muted mb-2">Index numbers follow this order inside each (institute, batch year) sequence:</p>
                        
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" id="orderRegistered" name="order" value="registered" class="custom-control-input" checked>
                            <label class="custom-control-label text-white" for="orderRegistered">
                                <strong>Registration Order</strong> (users.created_at, then ID) &mdash; <span class="text-primary">Default</span>
                            </label>
                        </div>
                        <div class="custom-control custom-radio">
                            <input type="radio" id="orderName" name="order" value="name" class="custom-control-input">
                            <label class="custom-control-label text-white" for="orderName">
                                <strong>Alphabetical Order</strong> by student full name
                            </label>
                        </div>
                    </div>

                    <!-- Deactivated Toggle -->
                    <div class="custom-control custom-checkbox mb-4">
                        <input type="checkbox" class="custom-control-input" id="includeDeactivated" name="include_deactivated" value="1">
                        <label class="custom-control-label text-muted" for="includeDeactivated">
                            Include deactivated student accounts (Pending & rejected registrations are NEVER included)
                        </label>
                    </div>

                    <button type="button" class="btn btn-primary btn-block btn-lg" id="btnPreview" onclick="generatePreview()">
                        <i class="flaticon-381-search-1 mr-2"></i> Generate Preview & Validate
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Step 2 & 3: Preview and Execution Progress -->
    <div class="col-xl-6 col-lg-6">
        <!-- Initial Blank Placeholder -->
        <div class="card h-100" id="previewPlaceholder">
            <div class="card-body text-center d-flex flex-column justify-content-center align-items-center py-5">
                <div style="font-size: 3rem; color: #4b5563; margin-bottom: 1rem;">
                    <i class="flaticon-381-list-1"></i>
                </div>
                <h5 class="text-white">Ready to Preview</h5>
                <p class="text-muted" style="max-width: 400px;">
                    Select your filters on the left and click <strong>Generate Preview & Validate</strong>. No database modifications are made until you confirm the run.
                </p>
            </div>
        </div>

        <!-- Preview Card -->
        <div class="card" id="previewCard" style="display: none;">
            <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="card-title text-white">2. Preview & Validation Results</h5>
                <button type="button" class="btn btn-outline-info btn-xs" id="btnDownloadPreviewCsv" onclick="downloadPreviewCsv()">
                    <i class="flaticon-381-download"></i> Download Preview CSV
                </button>
            </div>
            <div class="card-body">
                <!-- Stat Cards -->
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="p-3 rounded text-center" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3);">
                            <span class="d-block text-muted small">Will Be Assigned</span>
                            <span class="text-success font-weight-bold" style="font-size: 1.8rem;" id="previewAssignedCount">0</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded text-center" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3);">
                            <span class="d-block text-muted small">Skipped (No Batch Year)</span>
                            <span class="text-danger font-weight-bold" style="font-size: 1.8rem;" id="previewSkippedCount">0</span>
                        </div>
                    </div>
                </div>

                <!-- Unmapped Grades Warning -->
                <div id="unmappedWarning" class="alert alert-warning py-2 mb-3" style="display: none; background: rgba(234, 179, 8, 0.1); border-color: rgba(234, 179, 8, 0.3); color: #fbbf24;">
                    <div class="d-flex align-items-center">
                        <i class="flaticon-381-warning-1 mr-2" style="font-size: 1.2rem;"></i>
                        <div>
                            <strong>Unmapped Grades Detected:</strong>
                            <div id="unmappedList" class="small mt-1"></div>
                        </div>
                    </div>
                </div>

                <!-- Batch Breakdown Table -->
                <h6 class="text-white font-weight-bold mb-2">Sequence Number Breakdown:</h6>
                <div class="table-responsive mb-4" style="max-height: 250px; overflow-y: auto;">
                    <table class="table table-sm text-white" id="summaryTable">
                        <thead class="text-muted" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <tr>
                                <th>Grade</th>
                                <th>Batch</th>
                                <th>Count</th>
                                <th>Generated Number Range</th>
                            </tr>
                        </thead>
                        <tbody id="summaryTableBody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>

                <!-- Action Button -->
                <button type="button" class="btn btn-success btn-block btn-lg" id="btnStartRun" onclick="confirmAndStartRun()">
                    <i class="flaticon-381-play-button mr-2"></i> Start Bulk Assignment
                </button>
            </div>
        </div>

        <!-- Progress Card (Shown during execution) -->
        <div class="card" id="progressCard" style="display: none;">
            <div class="card-header border-0 pb-0">
                <h5 class="card-title text-white">3. Assignment in Progress</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Assigning index numbers in safe chunks of 100 students. You may cancel at any time &mdash; already assigned numbers are permanent.
                </p>

                <div class="progress mb-2" style="height: 26px; border-radius: 13px; background: #1f2937;">
                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success font-weight-bold" style="width: 0%; font-size: 0.9rem;">
                        0%
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center text-muted small mb-4">
                    <span id="progressText">0 / 0 Assigned</span>
                    <span id="progressRemaining">Remaining: 0</span>
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-danger btn-sm" id="btnCancelRun" onclick="cancelRun()">
                        <i class="flaticon-381-multiply-1"></i> Cancel Run
                    </button>
                </div>
            </div>
        </div>

        <!-- Completion Summary Card -->
        <div class="card" id="completeCard" style="display: none;">
            <div class="card-header border-0 pb-0">
                <h5 class="card-title text-white">
                    <i class="flaticon-381-check-2 text-success mr-2"></i> Bulk Assignment Complete
                </h5>
            </div>
            <div class="card-body">
                <div class="p-4 rounded text-center mb-4" style="background: rgba(34, 197, 94, 0.08); border: 1px solid rgba(34, 197, 94, 0.25);">
                    <h3 class="text-success font-weight-bold mb-1" id="finalAssignedCount">0</h3>
                    <p class="text-white mb-0">Index Numbers Successfully Assigned</p>
                    <small class="text-muted d-block mt-2" id="finalIndexRange"></small>
                </div>

                <div class="d-flex justify-content-between text-muted small mb-4 p-3 rounded" style="background: rgba(255,255,255,0.03);">
                    <span>Skipped: <strong id="finalSkippedCount" class="text-white">0</strong></span>
                    <span>Errors: <strong id="finalErrorCount" class="text-danger">0</strong></span>
                </div>

                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('admin.students.index') }}" class="btn btn-primary btn-block">
                        <i class="flaticon-381-list mr-1"></i> View Students List
                    </a>
                    <button type="button" class="btn btn-outline-light btn-block" onclick="location.reload()">
                        <i class="flaticon-381-refresh mr-1"></i> Run Another Batch
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="exportPreviewForm" action="{{ route('admin.students.assign-index-tool.export-preview') }}" method="POST" style="display: none;">
    @csrf
    <div id="exportPreviewInputs"></div>
</form>
@endsection

@push('scripts')
<script src="{{ asset('admin-theme/vendor/sweetalert2/dist/sweetalert2.min.js') }}"></script>
<script>
    let previewData = null;
    let isRunCancelled = false;
    let totalAssignedGlobal = 0;
    let totalSkippedGlobal = 0;
    let totalErrorsGlobal = 0;
    let firstAssignedIndex = null;
    let lastAssignedIndex = null;
    let excludedIds = [];

    // Chip click handler
    document.querySelectorAll('.grade-chip').forEach(chip => {
        chip.addEventListener('click', function(e) {
            const cb = this.querySelector('input[type="checkbox"]');
            if (e.target !== cb) {
                cb.checked = !cb.checked;
            }
            this.classList.toggle('active', cb.checked);
        });
    });

    document.getElementById('selectAllGrades').addEventListener('click', function() {
        document.querySelectorAll('.grade-chip').forEach(c => {
            c.classList.add('active');
            c.querySelector('input').checked = true;
        });
    });

    document.getElementById('clearAllGrades').addEventListener('click', function() {
        document.querySelectorAll('.grade-chip').forEach(c => {
            c.classList.remove('active');
            c.querySelector('input').checked = false;
        });
    });

    function selectBatchSource(mode) {
        document.getElementById('batchSourceMapping').checked = (mode === 'mapping');
        document.getElementById('batchSourceSingle').checked = (mode === 'single');
        document.querySelectorAll('.radio-card').forEach(c => c.classList.remove('selected'));
        if (mode === 'mapping') {
            document.querySelectorAll('.radio-card')[0].classList.add('selected');
            document.getElementById('singleBatchContainer').style.display = 'none';
        } else {
            document.querySelectorAll('.radio-card')[1].classList.add('selected');
            document.getElementById('singleBatchContainer').style.display = 'block';
            document.getElementById('singleBatchInput').focus();
        }
    }

    function getFormData() {
        const form = document.getElementById('filterForm');
        const selectedGrades = Array.from(form.querySelectorAll('input[name="grades[]"]:checked')).map(cb => cb.value);
        const batchSourceInput = form.querySelector('input[name="batch_source"]:checked') || form.querySelector('input[name="batch_source"]');
        const batchSource = batchSourceInput ? batchSourceInput.value : 'mapping';
        const singleBatchInput = document.getElementById('singleBatchInput');
        const singleBatchYear = singleBatchInput ? singleBatchInput.value : '';
        const order = form.querySelector('input[name="order"]:checked').value;
        const includeDeactivated = document.getElementById('includeDeactivated').checked ? 1 : 0;
        const mediumInput = document.getElementById('mediumFilter');
        const medium = mediumInput ? mediumInput.value : 'all';

        return {
            grades: selectedGrades,
            medium: medium,
            batch_source: batchSource,
            single_batch_year: singleBatchYear,
            order: order,
            include_deactivated: includeDeactivated,
            _token: '{{ csrf_token() }}'
        };
    }

    function generatePreview() {
        const data = getFormData();
        if (data.batch_source === 'single' && (!data.single_batch_year || data.single_batch_year < 20 || data.single_batch_year > 99)) {
            Swal.fire('Invalid Batch Year', 'Please enter a valid 2-digit batch year between 20 and 99.', 'warning');
            return;
        }

        const btn = document.getElementById('btnPreview');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i> Generating Preview...';

        fetch('{{ route("admin.students.assign-index-tool.preview") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="flaticon-381-search-1 mr-2"></i> Generate Preview & Validate';

            if (!res.success) {
                Swal.fire('Error', res.error || 'Preview failed', 'error');
                return;
            }

            previewData = res;
            renderPreview(res);
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="flaticon-381-search-1 mr-2"></i> Generate Preview & Validate';
            Swal.fire('Request Failed', err.message, 'error');
        });
    }

    function renderPreview(res) {
        document.getElementById('previewPlaceholder').style.display = 'none';
        document.getElementById('previewCard').style.display = 'block';
        document.getElementById('progressCard').style.display = 'none';
        document.getElementById('completeCard').style.display = 'none';

        document.getElementById('previewAssignedCount').textContent = res.total_students;
        document.getElementById('previewSkippedCount').textContent = res.skipped_count;

        // Unmapped warning
        const unmappedDiv = document.getElementById('unmappedWarning');
        const unmappedList = document.getElementById('unmappedList');
        if (res.skipped_count > 0 && res.skipped && res.skipped.length > 0) {
            const countsPerGrade = {};
            res.skipped.forEach(s => {
                countsPerGrade[s.grade] = (countsPerGrade[s.grade] || 0) + 1;
            });
            const textParts = Object.entries(countsPerGrade).map(([g, count]) => `${g} (${count} students)`);
            unmappedList.innerHTML = textParts.join(', ') + ' &mdash; no batch year configured in Index Number Batches.';
            unmappedDiv.style.display = 'block';
        } else {
            unmappedDiv.style.display = 'none';
        }

        // Summary table
        const tbody = document.getElementById('summaryTableBody');
        tbody.innerHTML = '';
        if (res.grade_summaries && res.grade_summaries.length > 0) {
            res.grade_summaries.forEach(gs => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="font-weight-bold text-white">${gs.grade}</td>
                    <td><span class="badge badge-primary">'${gs.batch_year}</span></td>
                    <td>${gs.count}</td>
                    <td style="font-family: monospace; color: #38bdf8;">${gs.first_index} &hellip; ${gs.last_index}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No students eligible for assignment under current settings.</td></tr>';
        }

        const startBtn = document.getElementById('btnStartRun');
        startBtn.disabled = (res.total_students === 0);
    }

    function downloadPreviewCsv() {
        if (!previewData || !previewData.preview_rows || previewData.preview_rows.length === 0) {
            Swal.fire('No Data', 'No preview rows available to download.', 'info');
            return;
        }

        const container = document.getElementById('exportPreviewInputs');
        container.innerHTML = '';
        previewData.preview_rows.forEach((row, idx) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `rows[${idx}]`;
            input.value = JSON.stringify(row);
            container.appendChild(input);
        });

        document.getElementById('exportPreviewForm').submit();
    }

    function confirmAndStartRun() {
        if (!previewData || previewData.total_students === 0) {
            return;
        }

        Swal.fire({
            title: 'Confirm Bulk Assignment',
            html: `<strong>${previewData.total_students}</strong> students will receive permanent index numbers.<br><br>
                   <span class="text-warning small">This action cannot be undone. Number sequences will advance permanently.</span>`,
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Assign Permanent Numbers'
        }).then(result => {
            if (result.value) {
                startChunkedRun();
            }
        });
    }

    function startChunkedRun() {
        isRunCancelled = false;
        totalAssignedGlobal = 0;
        totalSkippedGlobal = 0;
        totalErrorsGlobal = 0;
        firstAssignedIndex = null;
        lastAssignedIndex = null;
        excludedIds = [];

        document.getElementById('previewCard').style.display = 'none';
        document.getElementById('progressCard').style.display = 'block';
        updateProgressUI(0, previewData.total_students);

        processNextChunk();
    }

    function processNextChunk() {
        if (isRunCancelled) {
            finishRun();
            return;
        }

        const formData = getFormData();
        formData.chunk_size = 100;
        formData.exclude_ids = excludedIds;
        formData.total_assigned = totalAssignedGlobal;
        formData.total_skipped = totalSkippedGlobal;
        formData.first_index = firstAssignedIndex;
        formData.last_index = lastAssignedIndex;

        fetch('{{ route("admin.students.assign-index-tool.run-chunk") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(formData)
        })
        .then(res => res.json())
        .then(res => {
            if (!res.success) {
                Swal.fire('Chunk Failed', res.error || 'Server error during chunk processing', 'error');
                finishRun();
                return;
            }

            totalAssignedGlobal += res.assigned_in_chunk;
            totalSkippedGlobal += res.skipped_in_chunk;
            totalErrorsGlobal += res.errors_in_chunk;

            if (res.assigned_list && res.assigned_list.length > 0) {
                if (!firstAssignedIndex) {
                    firstAssignedIndex = res.assigned_list[0].index_number;
                }
                lastAssignedIndex = res.assigned_list[res.assigned_list.length - 1].index_number;
            }

            if (res.newly_excluded_ids && res.newly_excluded_ids.length > 0) {
                excludedIds = excludedIds.concat(res.newly_excluded_ids);
            }

            updateProgressUI(totalAssignedGlobal, previewData.total_students, res.remaining);

            if (res.done || res.processed_in_chunk === 0 || res.remaining === 0) {
                finishRun(true);
            } else {
                // Next chunk
                setTimeout(processNextChunk, 100);
            }
        })
        .catch(err => {
            Swal.fire('Network Issue', 'Request interrupted: ' + err.message + '. Run is safe to resume.', 'error');
            finishRun();
        });
    }

    function updateProgressUI(current, total, remaining = null) {
        const pct = total > 0 ? Math.min(100, Math.round((current / total) * 100)) : 0;
        const bar = document.getElementById('progressBar');
        bar.style.width = pct + '%';
        bar.textContent = pct + '%';
        document.getElementById('progressText').textContent = `${current} / ${total} Assigned`;
        if (remaining !== null) {
            document.getElementById('progressRemaining').textContent = `Remaining: ${remaining}`;
        }
    }

    function cancelRun() {
        isRunCancelled = true;
        document.getElementById('btnCancelRun').disabled = true;
        document.getElementById('btnCancelRun').textContent = 'Cancelling...';
    }

    function finishRun(completed = false) {
        document.getElementById('progressCard').style.display = 'none';
        document.getElementById('completeCard').style.display = 'block';

        document.getElementById('finalAssignedCount').textContent = totalAssignedGlobal;
        document.getElementById('finalSkippedCount').textContent = totalSkippedGlobal;
        document.getElementById('finalErrorCount').textContent = totalErrorsGlobal;

        if (firstAssignedIndex && lastAssignedIndex) {
            document.getElementById('finalIndexRange').textContent = `Generated numbers: ${firstAssignedIndex} ... ${lastAssignedIndex}`;
        } else {
            document.getElementById('finalIndexRange').textContent = '';
        }

        if (completed) {
            Swal.fire('Completed!', `Successfully assigned index numbers to ${totalAssignedGlobal} students.`, 'success');
        } else {
            Swal.fire('Run Stopped', `Progress saved: ${totalAssignedGlobal} assigned. You may resume any time.`, 'info');
        }
    }
</script>
@endpush
