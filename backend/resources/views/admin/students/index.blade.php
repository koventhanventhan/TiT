@extends('layouts.admin')

@section('title', 'Student Entries')

@push('styles')
<style>
    /* Custom Pagination Styles */
    .pagination-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap; /* allow wrapping on small screens */
        gap: 1rem;
        background: #3b3363;/* Dark background */
        padding: 0.9375rem 1.5625rem;
        border-bottom-left-radius: 0.5rem;
        border-bottom-right-radius: 0.5rem;
        color: #9ca3af;
        font-size: 0.875rem;
        margin: -1.5625rem -1.875rem -1.5625rem -1.875rem; /* Offset card padding */
        border-top: 1.0px solid #ffffff;
    }

    .pagination-info {
        flex: 1 1 100%;
        text-align: center;
    }

    @media (min-width: 768px) {
        .pagination-info {
            flex: 1 1 auto;
            text-align: left;
        }
    }

    .pagination-per-page {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.625rem;
    }

    .pagination-per-page select {
        background: #1f2937;
        border: 1.0px solid #374151;
        color: #fff;
        padding: 0.25rem 0.75rem;
        border-radius: 0.375rem;
        cursor: pointer;
        outline: none;
    }

    .custom-pagination-container {
        display: flex;
        justify-content: flex-start;
        border: 1.0px solid #374151;
        border-radius: 0.375rem;
        overflow-x: auto;
        /* Custom scrollbar for container */
        scrollbar-width: thin;
        scrollbar-color: #EB8153 transparent;
        width: 100%;
    }
    @media (min-width: 768px) {
        .custom-pagination-container {
            width: auto;
            margin-left: auto;
        }
    }

    .pagination-item {
        padding: 0.5rem 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        text-decoration: none;
        border-right: 1.0px solid #374151;
        background: #1f2937;
        transition: all 0.2s;
        min-width: 2.5rem;
    }

    .pagination-item:last-child {
        border-right: none;
    }

    .pagination-item:hover:not(.disabled):not(.active) {
        background: #374151;
        color: #fff;
        text-decoration: none;
    }

    .pagination-item.active {
        color: #fbbf24; /* Active page color (orange/yellow) */
        font-weight: 600;
        background: #1f2937;
    }

    .pagination-item.disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Table Visibility Improvements */
    .table-responsive {
        overflow-x: auto;
        scrollbar-width: auto; /* For Firefox */
        scrollbar-color: #EB8153 #111827; /* For Firefox */
    }

    /* Custom Scrollbar for Chrome/Safari/Edge */
    .table-responsive::-webkit-scrollbar {
        height: 0.625rem; /* Thicker horizontal scrollbar */
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #111827;
        border-radius: 0.3125rem;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: #EB8153;
        border-radius: 0.3125rem;
        border: 0.125rem solid #111827;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: #d67044;
    }

    /* Compact Table Styles */
    .table.table-responsive-md th,
    .table.table-responsive-md td {
        padding: 0.5rem 0.4rem !important;
        font-size: 0.75rem !important;
        vertical-align: middle;
    }

    .table.table-responsive-md th {
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.0313rem;
        font-weight: 700 !important;
    }

    /* Prevent specific columns from wrapping to save space */
    .nowrap-column {
        white-space: nowrap;
    }

    [data-theme-version="dark"] .form-control {
        background-color: transparent !important;
        border-color: #eb8153 !important;
        color: #ffffff !important;
    }
    [data-theme-version="dark"] .form-control option {
        background-color: #1a152e !important;
        color: #ffffff !important;
    }
    [data-theme-version="dark"] .form-control::placeholder {
        color: #938787;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Student Entries</h4>
            <div>
                <button type="button" class="btn btn-danger btn-sm mr-2" id="bulkDeleteBtn" style="display: none;" onclick="submitBulkDelete()">
                    <i class="flaticon-381-trash-1"></i> Delete Selected (<span id="selectedCount">0</span>)
                </button>
                <a href="{{ route('admin.students.create') }}" class="btn btn-primary btn-sm">
                    <i class="flaticon-381-add-1"></i> Add Student
                </a>
            </div>
        </div>
    </div>
</div>

<form id="bulkDeleteForm" action="{{ route('admin.students.bulk-delete') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="ids" id="bulkDeleteIds">
</form>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">All Student Entries</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-responsive-md">
                        <thead>
                             <tr>
                                 <th style="width: 2rem;">
                                     <div class="custom-control custom-checkbox border-0">
                                         <input type="checkbox" class="custom-control-input" id="checkAll">
                                         <label class="custom-control-label" for="checkAll"></label>
                                     </div>
                                 </th>
                                 <th style="width: 2rem;">ID</th>
                                 <th style="min-width: 10rem;">Student</th>
                                 <th>Grade</th>
                                 <th class="nowrap-column">Contact</th>
                                 <th style="width: 3.125rem;">Sex</th>
                                 <th class="nowrap-column">Status/Payment</th>
                                 <th>Medium</th>
                                 <th>Subjects</th>
                                 <th class="nowrap-column">Created</th>
                                 <th style="width: 3rem;">Action</th>
                             </tr>
                         </thead>
                         <tbody>
                             @forelse($students as $student)
                             @php
                                 $latestPayment = $student->payments()->where('year_month', now()->format('Y-m'))->where('status', 'paid')->first();
                                 $paidThisMonth = $latestPayment !== null;
                                 
                                 $subjectsArray = [];
                                 if ($student->selected_subjects) {
                                     if (is_array($student->selected_subjects)) {
                                         $subjectsArray = $student->selected_subjects;
                                     } else {
                                         $decoded = json_decode($student->selected_subjects, true);
                                         if (is_array($decoded)) {
                                             $subjectsArray = $decoded;
                                         } else {
                                             $subjectsArray = array_map('trim', explode(',', (string)$student->selected_subjects));
                                         }
                                     }
                                 }
                                 $subjectCount = count($subjectsArray);
                             @endphp
                             <tr>
                                  <td>
                                      <div class="custom-control custom-checkbox">
                                          <input type="checkbox" class="custom-control-input student-checkbox" id="customCheckBox{{$student->id}}" value="{{$student->id}}">
                                          <label class="custom-control-label" for="customCheckBox{{$student->id}}"></label>
                                      </div>
                                  </td>
                                   <td class="nowrap-column"><strong>{{ $student->id }}</strong></td>
                                  <td>
                                      <div style="font-weight: 600; color: #ffab2d; font-size: 0.8125rem; line-height: 1.2;">{{ $student->full_name ?? $student->name }}</div>
                                      <div style="font-size: 0.6875rem; color: #9ca3af; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 15.625rem;">{{ $student->email }}</div>
                                  </td>
                                 <td class="nowrap-column">
                                     <span style="color:#a78bfa; font-weight:600;">
                                     {{ $student->current_grade ? 'G'.$student->current_grade : 'N/A' }}
                                     </span>
                                 </td>
                                 <td class="nowrap-column">
                                     <div style="font-weight: 500;">{{ $student->phone_number ?? 'N/A' }}</div>
                                 </td>
                                 <td>
                                     <div class="text-center">
                                         @if($student->gender === 'female')
                                             <span class="badge badge-pill badge-danger" style="width: 1.5rem; height: 1.5rem; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem;">G</span>
                                         @else
                                             <span class="badge badge-pill badge-primary" style="width: 1.5rem; height: 1.5rem; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.75rem;">M</span>
                                         @endif
                                     </div>
                                 </td>
                                 <td class="nowrap-column">
                                     <div style="margin-bottom: 0.125rem;">
                                         @if($student->deactivated_at)
                                             <span class="badge badge-xs badge-danger" style="padding: 0.125rem 0.3125rem; font-size: 0.625rem;">Deactivated</span>
                                         @elseif($student->admin_confirmed_at)
                                             <span class="badge badge-xs badge-success" style="padding: 0.125rem 0.3125rem; font-size: 0.625rem;">Confirmed</span>
                                         @else
                                             <span class="badge badge-xs badge-warning" style="padding: 0.125rem 0.3125rem; font-size: 0.625rem;">{{ $student->registration_status ?? 'pending' }}</span>
                                         @endif
                                     </div>
                                     <div>
                                         @if($paidThisMonth)
                                             <span class="badge badge-xs badge-outline-success" style="padding: 0rem 0.25rem; font-size: 0.625rem;">Paid</span>
                                             @if($latestPayment && $latestPayment->paid_at)
                                                 <span style="color: #4caf50; font-size: 0.625rem; margin-left: 0.1875rem;">{{ $latestPayment->paid_at->format('M d') }}</span>
                                             @endif
                                         @else
                                             <span class="badge badge-xs badge-outline-secondary" style="padding: 0rem 0.25rem; font-size: 0.625rem;">Not paid</span>
                                         @endif
                                     </div>
                                 </td>
                                 <td>
                                     <span class="badge badge-info light text-uppercase" style="font-weight: 600;">{{ $student->medium ?? 'N/A' }}</span>
                                 </td>
                                 <td>
                                     @if($subjectCount > 0)
                                         <div style="font-weight: 600; color: #4f46e5;">
                                             {{ $subjectCount }} {{ Str::plural('Subject', $subjectCount) }}
                                         </div>
                                     @else
                                         <small class="text-muted">None</small>
                                     @endif
                                 </td>
                                 <td class="nowrap-column"><div style="font-size: 0.6875rem;">{{ $student->created_at->format('M d, y') }}</div></td>
                                 <td>
                                     <div class="dropdown">
                                         <button type="button" class="btn btn-primary light btn-xs sharp" style="width: 1.5rem; height: 1.5rem;" data-toggle="dropdown">
                                             <svg width="0.75rem" height="0.75rem" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="12" cy="5" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="12" cy="19" r="2"/></g></svg>
                                         </button>
                                         <div class="dropdown-menu dropdown-menu-right">
                                              @if(!$student->admin_confirmed_at)
                                                 <form action="{{ route('admin.students.confirm', $student->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-success">Confirm Registration</button>
                                                </form>
                                            @endif
                                            <a class="dropdown-item" href="{{ route('admin.students.edit', $student->id) }}">Edit Details</a>
                                            @if($student->deactivated_at)
                                                <form action="{{ route('admin.students.activate', $student->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-success">Activate Student</button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.students.deactivate', $student->id) }}" method="POST" onsubmit="return confirm('Deactivate this student?');">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-warning">Deactivate Student</button>
                                                </form>
                                            @endif
                                            <form action="{{ route('admin.students.destroy', $student->id) }}" method="POST" onsubmit="return confirm('à®¨à®¿à®šà¯ à®šà®¯à®®à®¾à®• à®‡à®¨à¯ à®¤ à®®à®¾à®£à®µà®°à¯ˆ à®¨à¯€à®•à¯ à®• à®µà¯‡à®£à¯ à®Ÿà¯ à®®à®¾? (Are you sure you want to delete this student?)');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">Delete Student</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center" style="padding: 2.5rem; color: #6b7280;">No student entries found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($students->hasPages())
                <div class="pagination-footer">
                    <div class="pagination-info">
                        Showing {{ $students->firstItem() }} to {{ $students->lastItem() }} of {{ $students->total() }} results
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
                    {{ $students->links('vendor.pagination.custom') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('checkAll');
        const checkboxes = document.querySelectorAll('.student-checkbox');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const selectedCountSpan = document.getElementById('selectedCount');
        const bulkDeleteIdsInput = document.getElementById('bulkDeleteIds');

        function updateBulkDeleteBtn() {
            const selected = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
            if (selected.length > 0) {
                bulkDeleteBtn.style.display = 'inline-block';
                selectedCountSpan.textContent = selected.length;
                bulkDeleteIdsInput.value = selected.join(',');
            } else {
                bulkDeleteBtn.style.display = 'none';
            }
        }

        if (selectAll) {
            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateBulkDeleteBtn();
            });
        }

        checkboxes.forEach(cb => cb.addEventListener('change', updateBulkDeleteBtn));
    });

    function submitBulkDelete() {
        // Translation: Are you sure you want to delete the selected students?
        if (confirm('Are you sure you want to delete the selected students?')) {
            document.getElementById('bulkDeleteForm').submit();
        }
    }
</script>
@endpush
