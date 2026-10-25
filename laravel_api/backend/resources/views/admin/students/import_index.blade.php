@extends('layouts.admin')

@section('title', 'Import Existing Index Numbers')

@push('styles')
<link href="{{ asset('admin-theme/vendor/sweetalert2/dist/sweetalert2.min.css') }}" rel="stylesheet">
<style>
    .upload-box {
        border: 2px dashed rgba(235, 129, 83, 0.4);
        border-radius: 12px;
        padding: 40px 20px;
        text-align: center;
        background: rgba(235, 129, 83, 0.03);
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .upload-box:hover {
        border-color: #eb8153;
        background: rgba(235, 129, 83, 0.08);
    }
    .status-badge-valid {
        background: rgba(34, 197, 94, 0.15);
        color: #22c55e;
        border: 1px solid rgba(34, 197, 94, 0.3);
        border-radius: 12px;
        padding: 2px 10px;
        font-weight: 600;
        font-size: 0.75rem;
    }
    .status-badge-error {
        background: rgba(239, 68, 68, 0.15);
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.3);
        border-radius: 12px;
        padding: 2px 10px;
        font-weight: 600;
        font-size: 0.75rem;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="mb-1" style="font-size: 1.5rem; font-weight: 700; color: #fff;">
                    <i class="flaticon-381-file-1 text-primary mr-2"></i> Import Existing Index Numbers from CSV
                </h4>
                <p class="text-muted mb-0">Import pre-assigned index numbers (e.g. from paper records or Excel) without colliding with auto-generated sequences.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.students.import-index.template') }}" class="btn btn-outline-success btn-sm">
                    <i class="flaticon-381-download"></i> Download CSV Template
                </a>
                <a href="{{ route('admin.students.assign-index-tool') }}" class="btn btn-outline-info btn-sm">
                    <i class="flaticon-381-layer-1"></i> Auto Assign Tool
                </a>
                <a href="{{ route('admin.students.index') }}" class="btn btn-outline-light btn-sm">
                    <i class="flaticon-381-list"></i> Back to Students
                </a>
            </div>
        </div>
    </div>
</div>

@php
    $hasFirstImport = \App\Models\ActivityLog::where('action', 'student_index_imported')->exists();
@endphp
@if(!$hasFirstImport)
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-warning d-flex align-items-center" style="background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.4); color: #fcd34d; border-radius: 8px; padding: 16px 20px;">
            <i class="flaticon-381-warning-1 mr-3" style="font-size: 1.8rem; color: #f59e0b;"></i>
            <div>
                <strong style="font-size: 1rem; color: #fff; display: block; margin-bottom: 4px;">Important Order of Operations: Import CSV Before Auto-Assigning</strong>
                <span>If you have existing paper records (e.g. Grade 08 students 001–065), you <strong>MUST import the CSV first</strong> before running any bulk or auto-assignment. The CSV import records those pre-assigned numbers and advances the sequence counter so new students automatically start from the next number (e.g. 066).</span>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <!-- Left Column: File Upload & Format Guidelines -->
    <div class="col-xl-5 col-lg-5">
        <div class="card">
            <div class="card-header border-0 pb-0">
                <h5 class="card-title text-white">Upload CSV File</h5>
            </div>
            <div class="card-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <div class="upload-box mb-3" onclick="document.getElementById('csvFileInput').click()">
                        <i class="flaticon-381-upload text-primary" style="font-size: 2.5rem;"></i>
                        <h6 class="text-white mt-2 mb-1" id="fileLabel">Click to select CSV file</h6>
                        <span class="text-muted small">Max 5MB (.csv format)</span>
                        <input type="file" id="csvFileInput" name="csv_file" accept=".csv,text/csv" style="display: none;" onchange="onFileSelected(this)">
                    </div>

                    <div class="alert alert-info py-2 px-3 small" style="background: rgba(56, 189, 248, 0.08); border-color: rgba(56, 189, 248, 0.25); color: #7dd3fc;">
                        <strong>Institute:</strong> {{ $institute->name ?? 'Default Institute' }} (Prefix: <code>{{ $institute->index_prefix ?? 'TiT' }}</code>)
                    </div>

                    <button type="button" class="btn btn-primary btn-block" id="btnUploadPreview" onclick="uploadAndPreview()" disabled>
                        <i class="flaticon-381-search-1 mr-1"></i> Validate & Preview CSV
                    </button>
                </form>

                <hr style="border-color: rgba(255,255,255,0.1);" class="my-4">

                <h6 class="text-white font-weight-bold mb-2">CSV Column Requirements:</h6>
                <p class="text-muted small mb-2">The CSV must include headers with one of the following formats:</p>
                <ul class="text-muted small pl-3 mb-3">
                    <li><strong>Owner's Format (Recommended)</strong>: Columns <code>grade, medium, student_name, index_number</code> (e.g. <code>Grade 8, English, M. Nidhuran, TIT/08/001</code>). Unassigned/reserved sequence rows can use <code>-</code> for name.</li>
                    <li><strong>Identifier Format</strong>: <code>index_number</code> plus one of <code>student_id</code>, <code>username</code>, <code>phone</code>, or <code>email</code>.</li>
                </ul>

                <div class="p-3 rounded" style="background: rgba(0,0,0,0.3); font-family: monospace; font-size: 0.8rem; color: #cbd5e1;">
                    grade,medium,student_name,index_number<br>
                    Grade 8,English,A. Student Name,TIT/08/001<br>
                    Grade 8,English,-,TIT/08/003<br>
                    Grade 8,Tamil,M. Tamil Student,TIT/08/015
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Validation Preview & Run -->
    <div class="col-xl-7 col-lg-7">
        <!-- Initial Placeholder -->
        <div class="card h-100" id="previewPlaceholder">
            <div class="card-body text-center d-flex flex-column justify-content-center align-items-center py-5">
                <div style="font-size: 3rem; color: #4b5563; margin-bottom: 1rem;">
                    <i class="flaticon-381-file-2"></i>
                </div>
                <h5 class="text-white">Validation Preview</h5>
                <p class="text-muted" style="max-width: 420px;">
                    Upload your CSV file on the left to preview parsed rows, test sequence conflicts, and verify student matches before importing.
                </p>
            </div>
        </div>

        <!-- Preview Results Card -->
        <div class="card" id="previewResultsCard" style="display: none;">
            <div class="card-header border-0 pb-0 d-flex justify-content-between align-items-center">
                <h5 class="card-title text-white">Validation Summary</h5>
                <span class="badge badge-dark" id="previewTotalBadge">0 Rows</span>
            </div>
            <div class="card-body">
                <!-- Stat Counts -->
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="p-3 rounded text-center" style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3);">
                            <span class="d-block text-muted small">Valid Rows</span>
                            <span class="text-success font-weight-bold" style="font-size: 1.8rem;" id="validRowsCount">0</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded text-center" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3);">
                            <span class="d-block text-muted small">Errors Detected</span>
                            <span class="text-danger font-weight-bold" style="font-size: 1.8rem;" id="errorRowsCount">0</span>
                        </div>
                    </div>
                </div>

                <!-- Mode Selection -->
                <div class="p-3 rounded mb-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);">
                    <label class="text-white font-weight-bold mb-2 small">Import Options:</label>
                    <div class="custom-control custom-radio mb-2">
                        <input type="radio" id="modeSkipErrors" name="import_mode" value="skip" class="custom-control-input" checked>
                        <label class="custom-control-label text-white small" for="modeSkipErrors">
                            <strong>Import valid rows only</strong> (skip rows with errors)
                        </label>
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" id="modeAbortErrors" name="import_mode" value="abort" class="custom-control-input">
                        <label class="custom-control-label text-white small" for="modeAbortErrors">
                            <strong>Abort entire import</strong> if any row fails validation (All-or-nothing)
                        </label>
                    </div>
                </div>

                <!-- Rows Preview Table -->
                <div class="table-responsive mb-4" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-sm text-white" id="validationTable">
                        <thead class="text-muted" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Index Number</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody id="validationTableBody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>

                <!-- Run Button -->
                <button type="button" class="btn btn-success btn-block btn-lg" id="btnExecuteImport" onclick="confirmAndExecuteImport()">
                    <i class="flaticon-381-check-2 mr-2"></i> Execute Import Now
                </button>
            </div>
        </div>

        <!-- Success Result Card -->
        <div class="card" id="importSuccessCard" style="display: none;">
            <div class="card-header border-0 pb-0">
                <h5 class="card-title text-white">
                    <i class="flaticon-381-check-2 text-success mr-2"></i> Import Completed
                </h5>
            </div>
            <div class="card-body text-center py-4">
                <h2 class="text-success font-weight-bold mb-1" id="finalImportedCount">0</h2>
                <p class="text-white mb-2">Index Numbers Successfully Imported & Sequence Advanced</p>
                <p class="text-muted small mb-4">
                    The sequence counter has been automatically updated so future auto-assigned index numbers will never collide with imported numbers.
                </p>

                <div class="d-flex gap-2 justify-content-center">
                    <a href="{{ route('admin.students.index') }}" class="btn btn-primary">
                        <i class="flaticon-381-list mr-1"></i> View Students List
                    </a>
                    <button type="button" class="btn btn-outline-light" onclick="location.reload()">
                        <i class="flaticon-381-refresh mr-1"></i> Import Another File
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin-theme/vendor/sweetalert2/dist/sweetalert2.min.js') }}"></script>
<script>
    let validationResult = null;

    function onFileSelected(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            document.getElementById('fileLabel').textContent = file.name;
            document.getElementById('btnUploadPreview').disabled = false;
        }
    }

    function uploadAndPreview() {
        const fileInput = document.getElementById('csvFileInput');
        if (!fileInput.files || !fileInput.files[0]) {
            Swal.fire('No File', 'Please select a CSV file first.', 'warning');
            return;
        }

        const formData = new FormData();
        formData.append('csv_file', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');

        const btn = document.getElementById('btnUploadPreview');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> Validating CSV...';

        fetch('{{ route("admin.students.import-index.preview") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="flaticon-381-search-1 mr-1"></i> Validate & Preview CSV';

            if (!res.success) {
                Swal.fire('Validation Error', res.error || 'Failed to parse CSV', 'error');
                return;
            }

            validationResult = res;
            renderValidation(res);
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="flaticon-381-search-1 mr-1"></i> Validate & Preview CSV';
            Swal.fire('Upload Failed', err.message, 'error');
        });
    }

    function renderValidation(res) {
        document.getElementById('previewPlaceholder').style.display = 'none';
        document.getElementById('previewResultsCard').style.display = 'block';
        document.getElementById('importSuccessCard').style.display = 'none';

        document.getElementById('previewTotalBadge').textContent = `${res.total} Rows`;
        document.getElementById('validRowsCount').textContent = res.valid_count;
        document.getElementById('errorRowsCount').textContent = res.error_count;

        const tbody = document.getElementById('validationTableBody');
        tbody.innerHTML = '';

        res.rows.forEach(r => {
            const tr = document.createElement('tr');
            const isValid = (r.status === 'valid');
            let nameHtml = `<span class="font-weight-bold text-white">${r.student_name || '—'}</span>`;
            if (r.is_reserved) {
                nameHtml += ` <span class="badge badge-warning text-dark ml-1" style="font-size: 0.7rem;">RESERVED</span>`;
            } else if (r.candidates && r.candidates.length > 1) {
                nameHtml += `
                    <div class="mt-1">
                        <select class="form-control form-control-sm text-white" style="background: #1f2937; border-color: #f59e0b; font-size: 0.75rem;" onchange="onCandidateSelected(${r.row_number}, this.value)">
                            <option value="">-- Ambiguous match: select student (${r.candidates.length} candidates) --</option>
                            ${r.candidates.map(c => `<option value="${c.id}" ${c.id === r.student_id ? 'selected' : ''}>#${c.id} - ${c.full_name || c.name} (${c.current_grade || ''}, ${c.medium || ''})</option>`).join('')}
                        </select>
                    </div>
                `;
            } else if (r.student_id) {
                nameHtml += ` <small class="text-muted">(#${r.student_id})</small>`;
            } else {
                nameHtml += ` <small class="text-muted">(Unmatched)</small>`;
            }

            let msgHtml = isValid ? '<span class="text-muted">Ready to import</span>' : `<span class="text-danger">${r.error_message}</span>`;
            if (r.warnings && r.warnings.length) {
                msgHtml += `<div class="small text-warning mt-1">${r.warnings.join('<br>')}</div>`;
            }

            tr.innerHTML = `
                <td>${r.row_number}</td>
                <td>${nameHtml}</td>
                <td style="font-family: monospace; color: #38bdf8;">${r.index_number || 'N/A'}</td>
                <td>
                    <span class="${isValid ? 'status-badge-valid' : 'status-badge-error'}">
                        ${isValid ? 'VALID' : 'ERROR'}
                    </span>
                </td>
                <td class="small">
                    ${msgHtml}
                </td>
            `;
            tbody.appendChild(tr);
        });

        const execBtn = document.getElementById('btnExecuteImport');
        execBtn.disabled = (res.valid_count === 0);
    }

    function onCandidateSelected(rowNumber, studentId) {
        if (!validationResult || !validationResult.rows) return;
        const row = validationResult.rows.find(r => r.row_number === rowNumber);
        if (!row) return;
        row.student_id = studentId ? parseInt(studentId, 10) : null;
        const candidate = (row.candidates || []).find(c => c.id === row.student_id);
        if (candidate) {
            row.student_name = candidate.full_name || candidate.name;
        }
    }

    function confirmAndExecuteImport() {
        if (!validationResult || validationResult.valid_count === 0) {
            Swal.fire('Cannot Import', 'There are no valid rows to import.', 'warning');
            return;
        }

        const isAbortMode = document.getElementById('modeAbortErrors').checked;
        if (isAbortMode && validationResult.has_errors) {
            Swal.fire('Import Aborted', 'Errors were detected and "Abort entire import if any row fails" is selected. Please fix errors in CSV first.', 'error');
            return;
        }

        Swal.fire({
            title: 'Confirm Index Import',
            html: `You are about to import <strong>${validationResult.valid_count}</strong> student index numbers into the database.<br><br>
                   <span class="text-info small">Number sequence counters will automatically advance to guarantee future numbers never collide.</span>`,
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Execute Import'
        }).then(result => {
            if (result.value) {
                runImport(isAbortMode);
            }
        });
    }

    function runImport(abortOnError) {
        const btn = document.getElementById('btnExecuteImport');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i> Importing...';

        fetch('{{ route("admin.students.import-index.run") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                validated_rows: validationResult.rows,
                abort_on_error: abortOnError,
                _token: '{{ csrf_token() }}'
            })
        })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="flaticon-381-check-2 mr-2"></i> Execute Import Now';

            if (!res.success) {
                Swal.fire('Import Failed', res.error || 'Server error', 'error');
                return;
            }

            document.getElementById('previewResultsCard').style.display = 'none';
            document.getElementById('importSuccessCard').style.display = 'block';
            document.getElementById('finalImportedCount').textContent = res.imported_count;

            Swal.fire('Import Complete', `Successfully imported ${res.imported_count} student index numbers.`, 'success');
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="flaticon-381-check-2 mr-2"></i> Execute Import Now';
            Swal.fire('Request Failed', err.message, 'error');
        });
    }
</script>
@endpush
