@extends('layouts.admin')

@section('title', 'Exam Results')

@push('styles')
<style>
    .loading-overlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255,255,255,0.7);
        display: none;
        align-items: center; justify-content: center;
        z-index: 10;
    }
    .badge-grade { font-weight: bold; padding: 5px 10px; border-radius: 4px; }
    .grade-A { background: #dcfce7; color: #166534; }
    .grade-B { background: #e0f2fe; color: #075985; }
    .grade-C { background: #fef9c3; color: #854d0e; }
    .grade-S { background: #ffedd5; color: #9a3412; }
    .grade-F { background: #fee2e2; color: #991b1b; }
    .grade-W { background: #f3f4f6; color: #374151; }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Exam Results</h4>
            <div>
                <button type="button" class="btn btn-success btn-sm mr-2" data-toggle="modal" data-target="#importModal">
                    <i class="flaticon-381-upload"></i> Import Excel
                </button>
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addResultModal">
                    <i class="flaticon-381-add-1"></i> Add Manual Entry
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-12">
        <div class="card" style="position: relative;">
            <div class="loading-overlay" id="tableLoading">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">All Results</h4>
                <div class="d-flex gap-2">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search index no or name..." style="width: 250px;">
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-md" id="resultsTable">
                        <thead>
                            <tr>
                                <th>Index No</th>
                                <th>Student / Class</th>
                                <th>Term</th>
                                <th>Subject</th>
                                <th>Result/Marks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Filled by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Manual Result Modal -->
<div class="modal fade" id="addResultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="addResultForm">
                <div class="modal-header">
                    <h5 class="modal-title">Add Exam Result</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Index No <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="index_no" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Student Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="student_name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Term <span class="text-danger">*</span></label>
                            <select class="form-control" name="term" required>
                                <option value="">-- Select Term --</option>
                                @foreach($terms as $t)
                                    <option value="{{ $t }}">{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Class/Grade <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="grade" placeholder="e.g., Grade 10" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="subject" placeholder="e.g., Mathematics" required>
                    </div>
                    <div class="form-group">
                        <label>Result / Marks (Optional)</label>
                        <div class="d-flex gap-2">
                            <select class="form-control mr-2" name="result_grade">
                                <option value="">-- Result Grade --</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="S">S</option>
                                <option value="F">F</option>
                                <option value="W">W</option>
                            </select>
                            <input type="number" class="form-control" name="marks" placeholder="Marks (0-100)" min="0" max="100">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger light" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveResultBtn">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Import Excel Modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="importForm">
                <div class="modal-header">
                    <h5 class="modal-title">Import Document (Excel/PDF/Word)</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Term <span class="text-danger">*</span></label>
                        <select class="form-control" name="import_term" required>
                            <option value="">-- Select Term --</option>
                            @foreach($terms as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Class/Grade <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="import_grade" placeholder="e.g., Grade 10" required>
                    </div>
                    <div class="form-group">
                        <label>Select File <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="file" accept=".xlsx,.xls,.csv,.pdf,.docx" required>
                        <small class="text-muted mt-2 d-block">Ensure columns are: index_no, student_name, subject, marks, result_grade, rank</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger light" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="importBtn">Import Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    var API_URL = '/admin/exam-results/data';
    var results = [];

    // Setup CSRF and AJAX headers
    $.ajaxSetup({
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    $(document).ready(function() {
        fetchResults();

        // Search debounce
        let searchTimeout;
        $('#searchInput').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(fetchResults, 500);
        });

        // Add Result Form Submit
        $(document).on('click', '#saveResultBtn', function(e) {
            e.preventDefault();
            
            // Basic validation
            let index_no = $('input[name="index_no"]').val().trim();
            let student_name = $('input[name="student_name"]').val().trim();
            let term = $('[name="term"]').val().trim();
            let grade = $('input[name="grade"]').val().trim();
            let subject = $('input[name="subject"]').val().trim();

            let missing = [];
            if(!index_no) missing.push('Index No');
            if(!student_name) missing.push('Student Name');
            if(!term) missing.push('Term');
            if(!grade) missing.push('Class/Grade');
            if(!subject) missing.push('Subject');

            if(missing.length > 0) {
                Swal.fire({ title: 'Missing Fields', html: 'Please fill out the following required fields:<br>- ' + missing.join('<br>- '), type: 'warning', customClass: 'swal-dark-popup' });
                return;
            }

            let btn = $(this);
            btn.prop('disabled', true).text('Saving...');
            
            let data = {
                index_no: index_no,
                student_name: student_name,
                term: term,
                grade: grade,
                subject: subject,
                result_grade: $('select[name="result_grade"]').val(),
                marks: $('input[name="marks"]').val() || null
            };

            $.ajax({
                url: API_URL,
                type: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(res) {
                    $('#addResultModal').modal('hide');
                    $('#addResultForm')[0].reset();
                    Swal.fire({ title: 'Success', text: 'Exam result saved successfully!', type: 'success', customClass: 'swal-dark-popup' });
                    fetchResults();
                },
                error: function(err) {
                    btn.prop('disabled', false).text('Save changes');
                    let backendError = "Unknown Error";
                    if(err.responseJSON) {
                        if(err.responseJSON.errors) {
                            let msgs = [];
                            for(let k in err.responseJSON.errors) msgs.push(err.responseJSON.errors[k].join('\n'));
                            backendError = msgs.join('\n');
                        } else if (err.responseJSON.message) {
                            backendError = err.responseJSON.message;
                        } else {
                            backendError = JSON.stringify(err.responseJSON);
                        }
                    } else {
                        backendError = err.responseText || err.statusText;
                    }
                    Swal.fire({ title: 'Failed to Save', html: backendError.replace(/\n/g, '<br>'), type: 'error', customClass: 'swal-dark-popup' });
                },
                complete: function() {
                    // btn.prop('disabled', false).text('Save changes'); (handled in success/error)
                }
            });
        });

        // Import Form Submit
        $(document).on('click', '#importBtn', function(e) {
            e.preventDefault();
            let btn = $(this);
            let fileInput = $('input[name="file"]')[0].files[0];
            let termInput = $('#importForm select[name="import_term"]').val();
            let gradeInput = $('#importForm input[name="import_grade"]').val();

            if(!fileInput || !termInput || !gradeInput){
                Swal.fire({ title: 'Missing Fields', text: 'Please fill all required fields.', type: 'warning', customClass: 'swal-dark-popup' });
                return;
            }

            btn.prop('disabled', true).text('Importing...');
            let formData = new FormData();
            formData.append('file', fileInput);
            formData.append('term', termInput);
            formData.append('grade', gradeInput);

            $.ajax({
                url: API_URL + '/import',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    $('#importModal').modal('hide');
                    $('#importForm')[0].reset();
                    Swal.fire({ title: 'Success', text: res.message || 'Import successful!', type: 'success', customClass: 'swal-dark-popup' });
                    fetchResults();
                },
                error: function(err) {
                    if (err.responseJSON && err.responseJSON.error) {
                        Swal.fire({ title: 'Error', text: err.responseJSON.error, type: 'error', customClass: 'swal-dark-popup' });
                    } else {
                        Swal.fire({ title: 'Error', text: 'Error importing file. Please check format.', type: 'error', customClass: 'swal-dark-popup' });
                    }
                },
                complete: function() {
                    btn.prop('disabled', false).text('Import Data');
                }
            });
        });
    });

    function fetchResults() {
        $('#tableLoading').css('display', 'flex');
        let search = $('#searchInput').val();
        
        $.ajax({
            url: API_URL,
            type: 'GET',
            data: { search: search },
            success: function(res) {
                results = res.data || [];
                renderTable();
            },
            error: function(err) {
                console.error("Failed to fetch results", err);
            },
            complete: function() {
                $('#tableLoading').hide();
            }
        });
    }

    function renderTable() {
        let tbody = $('#resultsTable tbody');
        tbody.empty();

        if(results.length === 0) {
            tbody.append('<tr><td colspan="5" class="text-center text-muted py-4">No results found</td></tr>');
            return;
        }

        results.forEach(r => {
            let resClass = 'grade-' + (r.result_grade ? r.result_grade.charAt(0).toUpperCase() : 'W');
            let resDisplay = r.result_grade ? r.result_grade : (r.marks ? r.marks + '%' : 'N/A');
            
            let tr = `
                <tr>
                    <td class="font-w600 text-primary">${r.index_no}</td>
                    <td>${r.student_name} <small class="d-block text-muted">${r.grade}</small></td>
                    <td>${r.term}</td>
                    <td>${r.subject}</td>
                    <td><span class="badge-grade ${resClass}">${resDisplay}</span></td>
                    <td>
                        <button class="btn btn-danger shadow btn-xs sharp" onclick="deleteResult(${r.id})">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(tr);
        });
    }

    window.deleteResult = function(id) {
        Swal.fire({
            title: 'Delete Result?',
            text: 'Are you sure you want to delete this result?',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            customClass: 'swal-dark-popup'
        }).then((result) => {
            if (result.value) {
                $.ajax({
                    url: API_URL + '/' + id,
                    type: 'DELETE',
                    success: function() {
                        fetchResults();
                    },
                    error: function() {
                        Swal.fire({ title: 'Error', text: 'Failed to delete result.', type: 'error', customClass: 'swal-dark-popup' });
                    }
                });
            }
        });
    }
</script>
@endpush
