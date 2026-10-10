@extends('layouts.admin')

@section('title', 'Student Entries')

@push('styles')
<link href="{{ asset('admin-theme/vendor/sweetalert2/dist/sweetalert2.min.css') }}" rel="stylesheet">
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

    /* Extreme Compact Table Styles */
    .table.table-responsive-md th,
    .table.table-responsive-md td {
        padding: 0.4rem 0.25rem !important;
        font-size: 0.7rem !important;
        vertical-align: middle;
        line-height: 1.1;
    }

    .table.table-responsive-md th {
        white-space: nowrap;
        text-transform: uppercase;
        letter-spacing: 0.02rem;
        font-weight: 800 !important;
        background-color: rgba(0,0,0,0.1);
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
    
    /* Premium Filter Styles */
    .premium-filter-group {
        display: flex;
        align-items: center;
        gap: 16px;
        background: rgba(31, 41, 55, 0.4);
        padding: 8px 16px;
        border-radius: 30px;
        border: 1px solid rgba(255, 255, 255, 0.05);
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1), 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    
    .premium-select {
        appearance: none;
        background-color: rgba(17, 24, 39, 0.8) !important;
        border: 1px solid rgba(235, 129, 83, 0.5) !important;
        color: #fff !important;
        padding: 6px 36px 6px 16px !important;
        border-radius: 20px !important;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s ease;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23EB8153'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        background-size: 14px;
        min-width: 140px;
    }
    
    .premium-select:hover, .premium-select:focus {
        border-color: #eb8153 !important;
        box-shadow: 0 0 0 3px rgba(235, 129, 83, 0.15);
        outline: none;
    }

    .premium-toggle-wrapper {
        display: flex;
        align-items: center;
    }
    
    .premium-toggle-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 18px;
        border-radius: 20px;
        background: rgba(239, 68, 68, 0.08);
        border: 1px solid rgba(239, 68, 68, 0.25);
        color: #fca5a5;
        font-weight: 600;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        user-select: none;
    }

    .premium-toggle-input {
        display: none;
    }
    
    .premium-toggle-input:checked + .premium-toggle-btn {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        border-color: #ef4444;
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(239, 68, 68, 0.35);
        transform: translateY(-1px);
    }
    
    .premium-toggle-input:checked + .premium-toggle-btn svg {
        animation: pulse-ring 2s infinite;
    }

    @keyframes pulse-ring {
        0% { transform: scale(1); filter: drop-shadow(0 0 0 rgba(255,255,255,0.7)); }
        50% { transform: scale(1.1); filter: drop-shadow(0 0 4px rgba(255,255,255,0.5)); }
        100% { transform: scale(1); filter: drop-shadow(0 0 0 rgba(255,255,255,0)); }
    }
    
    /* Custom Dropdown Styling (Bootstrap Select) */
    .premium-filter-group .bootstrap-select .dropdown-toggle {
        outline: none !important;
        box-shadow: none !important;
    }
    
    /* Fix for placeholder text and selected text being too dark */
    .premium-filter-group .bootstrap-select .dropdown-toggle,
    .premium-filter-group .bootstrap-select .dropdown-toggle:hover,
    .premium-filter-group .bootstrap-select .dropdown-toggle:focus,
    .premium-filter-group .bootstrap-select .dropdown-toggle:active,
    .premium-filter-group .bootstrap-select .dropdown-toggle .filter-option,
    .premium-filter-group .bootstrap-select .dropdown-toggle .filter-option-inner,
    .premium-filter-group .bootstrap-select .dropdown-toggle .filter-option-inner-inner {
        color: #ffffff !important; 
    }
    
    .premium-filter-group .bootstrap-select .dropdown-menu {
        background-color: #1a152e !important;
        border: 1px solid rgba(235, 129, 83, 0.3) !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.5) !important;
        margin-top: 8px !important;
        padding: 8px 0 !important;
        overflow: hidden !important;
    }
    .premium-filter-group .bootstrap-select .dropdown-menu li a {
        color: #e5e7eb !important;
        padding: 8px 20px !important;
        font-weight: 500 !important;
        transition: all 0.2s ease;
    }
    .premium-filter-group .bootstrap-select .dropdown-menu li a:hover,
    .premium-filter-group .bootstrap-select .dropdown-menu li.active a,
    .premium-filter-group .bootstrap-select .dropdown-menu li.selected a {
        background-color: rgba(235, 129, 83, 0.15) !important;
        color: #eb8153 !important;
    }
    
    /* Hide the native caret since we added an SVG caret in premium-select */
    .premium-filter-group .bootstrap-select .dropdown-toggle .bs-caret {
        display: none !important;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Student Entries</h4>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <form action="{{ route('admin.students.index') }}" method="GET" class="mb-0 mr-3">
                    <div class="premium-filter-group">
                        <select name="grade" class="selectpicker" data-style="premium-select" onchange="this.form.submit()">
                            <option value=""> All Grades</option>
                            @for($i = 1; $i <= 13; $i++)
                                <option value="{{ $i }}" {{ request('grade') == $i ? 'selected' : '' }}>Grade {{ $i }}</option>
                            @endfor
                        </select>
                        
                        <div class="premium-toggle-wrapper">
                            <input type="checkbox" id="needsReviewFilter" name="needs_review" value="1" class="premium-toggle-input" {{ request('needs_review') == '1' ? 'checked' : '' }} onchange="this.form.submit()">
                            <label for="needsReviewFilter" class="premium-toggle-btn mb-0" title="Toggle to show students who need subject verification">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                                Needs Subject Review
                            </label>
                        </div>
                    </div>
                </form>
                <button type="button" class="btn btn-warning btn-sm mr-2" id="bulkPromoteBtn" style="display: none;" onclick="submitBulkPromote()">
                    <i class="flaticon-381-upload"></i> Promote Selected (<span id="promoteSelectedCount">0</span>)
                </button>
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

<form id="bulkPromoteForm" action="{{ route('admin.students.promote') }}" method="POST" style="display: none;">
    @csrf
    <input type="hidden" name="ids" id="bulkPromoteIds">
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
                                 <th style="width: 2rem;">
                                     <div class="custom-control custom-checkbox border-0">
                                         <input type="checkbox" class="custom-control-input" id="checkAll">
                                         <label class="custom-control-label" for="checkAll"></label>
                                     </div>
                                 </th>
                                 <th style="width: 3rem;">ID/DATE</th>
                                 <th style="max-width: 7.5rem;">STUDENT</th>
                                 <th class="text-center">INFO</th>
                                 <th class="nowrap-column">CONTACT</th>
                                 <th class="nowrap-column">STATUS/PAYMENT</th>
                                 <th class="text-center">SUBJ</th>
                                 <th style="width: 2rem;">ACT</th>
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
                                   <td>
                                       <div class="custom-control custom-checkbox">
                                           <input type="checkbox" class="custom-control-input student-checkbox" id="customCheckBox{{$student->id}}" value="{{$student->id}}">
                                           <label class="custom-control-label" for="customCheckBox{{$student->id}}"></label>
                                       </div>
                                   </td>
                                    <td class="nowrap-column">
                                       <div style="font-weight: 800; font-size: 0.75rem;">{{ $student->id }}</div>
                                       <div style="font-size: 0.6rem; opacity: 0.6;">{{ $student->created_at->format('M d, y') }}</div>
                                   </td>
                                  <td style="max-width: 7.5rem;">
                                      <div class="text-truncate" style="font-weight: 700; color: #ffab2d; font-size: 0.75rem;">{{ $student->full_name ?? $student->name }}</div>
                                      @if(empty($student->phone_number) && $student->parent_id && $student->parent)
                                          <div class="text-truncate" style="font-size: 0.6rem; color: #9ca3af;" title="Contact via: {{ $student->parent->full_name ?? $student->parent->name }} ({{ $student->parent->email }})">Contact via: {{ $student->parent->full_name ?? $student->parent->name }}</div>
                                      @else
                                          <div class="text-truncate" style="font-size: 0.6rem; color: #9ca3af;" title="{{ $student->email }}">{{ $student->email }}</div>
                                      @endif
                                  </td>
                                 <td class="nowrap-column text-center">
                                     <span style="color:#a78bfa; font-weight:800;">G{{ $student->current_grade ?: '?' }}</span>
                                     <span style="opacity: 0.3; margin: 0 0.125rem;">|</span>
                                     <span style="font-weight:800; color: {{ $student->gender === 'female' ? '#f87171' : '#60a5fa' }}">{{ $student->gender === 'female' ? 'G' : 'M' }}</span>
                                     <span style="opacity: 0.3; margin: 0 0.125rem;">|</span>
                                     <span style="font-weight:800; opacity: 0.7;">{{ $student->medium === 'tamil' ? 'TAM' : 'ENG' }}</span>
                                 </td>
                                 <td class="nowrap-column">
                                     <div style="font-weight: 500; font-size: 0.7rem;">
                                         @if($student->phone_number)
                                             {{ str_starts_with($student->phone_number, '94') ? '+' . $student->phone_number : $student->phone_number }}
                                         @elseif($student->parent_id && $student->parent && $student->parent->phone_number)
                                             {{ str_starts_with($student->parent->phone_number, '94') ? '+' . $student->parent->phone_number : $student->parent->phone_number }}
                                             <span style="font-size: 0.55rem; color: #9ca3af; font-style: italic;">(via parent)</span>
                                         @else
                                             N/A
                                         @endif
                                     </div>
                                 </td>
                                 <td class="nowrap-column">
                                     <div style="margin-bottom: 0.125rem;">
                                         @if($student->is_graduated)
                                             <span class="badge badge-xs badge-info" style="padding: 0.0625rem 0.25rem; font-size: 0.55rem; border-radius: 0.125rem;">Graduated</span>
                                         @elseif($student->deactivated_at)
                                             <span class="badge badge-xs badge-danger" style="padding: 0.0625rem 0.25rem; font-size: 0.55rem; border-radius: 0.125rem;">Deactivated</span>
                                         @elseif($student->admin_confirmed_at)
                                             <span class="badge badge-xs badge-success" style="padding: 0.0625rem 0.25rem; font-size: 0.55rem; border-radius: 0.125rem;">Confirmed</span>
                                         @else
                                             <span class="badge badge-xs badge-warning" style="padding: 0.0625rem 0.25rem; font-size: 0.55rem; border-radius: 0.125rem;">{{ $student->registration_status ?? 'pending' }}</span>
                                         @endif
                                     </div>
                                     <div style="display: flex; align-items: center; gap: 0.25rem;">
                                         @if($paidThisMonth)
                                             <span class="badge badge-xs badge-outline-success" style="padding: 0rem 0.1875rem; font-size: 0.55rem; border-width: 1px;">Paid</span>
                                             @if($latestPayment && $latestPayment->paid_at)
                                                 <span style="color: #4caf50; font-size: 0.55rem; font-weight: 500;">{{ $latestPayment->paid_at->format('M d') }}</span>
                                             @endif
                                         @else
                                             <span class="badge badge-xs badge-outline-secondary" style="padding: 0rem 0.1875rem; font-size: 0.55rem; border-width: 1px;">Not paid</span>
                                         @endif
                                     </div>
                                 </td>
                                 <td class="text-center">
                                     <div style="font-weight: 800; color: #4f46e5; font-size: 0.75rem;">
                                         {{ $subjectCount }}
                                     </div>
                                 </td>
                                 <td>
                                     <div class="dropdown">
                                         <button type="button" class="btn btn-primary light btn-xs sharp" style="width: 1.25rem; height: 1.25rem; padding: 0.125rem;" data-toggle="dropdown">
                                             <svg width="0.625rem" height="0.625rem" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="12" cy="5" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="12" cy="19" r="2"/></g></svg>
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
                                                <form action="{{ route('admin.students.activate', $student->id) }}" method="POST" id="activate-form-{{ $student->id }}">
                                                    @csrf
                                                    <button type="button" class="dropdown-item text-success swal-confirm-btn"
                                                        data-form-id="activate-form-{{ $student->id }}"
                                                        data-title="Activate Student?"
                                                        data-text="This student will be able to log in again."
                                                        data-confirm-text="Yes, activate!"
                                                        data-confirm-color="#28a745">Activate Student</button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.students.deactivate', $student->id) }}" method="POST" id="deactivate-form-{{ $student->id }}">
                                                    @csrf
                                                    <button type="button" class="dropdown-item text-warning swal-confirm-btn"
                                                        data-form-id="deactivate-form-{{ $student->id }}"
                                                        data-title="Deactivate Student?"
                                                        data-text="This student will no longer be able to log in."
                                                        data-confirm-text="Yes, deactivate!"
                                                        data-confirm-color="#f59e0b">Deactivate Student</button>
                                                </form>
                                            @endif
                                            <form action="{{ route('admin.students.destroy', $student->id) }}" method="POST" id="delete-form-{{ $student->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="dropdown-item text-danger swal-confirm-btn"
                                                    data-form-id="delete-form-{{ $student->id }}"
                                                    data-title="Delete Student?"
                                                    data-text="Are you sure? This action is permanent and cannot be undone."
                                                    data-confirm-text="Yes, delete!"
                                                    data-confirm-color="#d33">Delete Student</button>
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
<script src="{{ asset('admin-theme/vendor/sweetalert2/dist/sweetalert2.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('checkAll');
        const checkboxes = document.querySelectorAll('.student-checkbox');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const bulkPromoteBtn = document.getElementById('bulkPromoteBtn');
        const selectedCountSpan = document.getElementById('selectedCount');
        const promoteSelectedCountSpan = document.getElementById('promoteSelectedCount');
        const bulkDeleteIdsInput = document.getElementById('bulkDeleteIds');
        const bulkPromoteIdsInput = document.getElementById('bulkPromoteIds');

        function updateBulkDeleteBtn() {
            const selected = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);
            if (selected.length > 0) {
                bulkDeleteBtn.style.display = 'inline-block';
                bulkPromoteBtn.style.display = 'inline-block';
                selectedCountSpan.textContent = selected.length;
                promoteSelectedCountSpan.textContent = selected.length;
                bulkDeleteIdsInput.value = selected.join(',');
                bulkPromoteIdsInput.value = selected.join(',');
            } else {
                bulkDeleteBtn.style.display = 'none';
                bulkPromoteBtn.style.display = 'none';
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
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to delete the selected students. This action cannot be undone!",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel',
            customClass: 'swal-dark-popup'
        }).then(function(result) {
            if (result.value) {
                document.getElementById('bulkDeleteForm').submit();
            }
        });
    }

    function submitBulkPromote() {
        Swal.fire({
            title: 'Promote Students?',
            html: "You are about to promote the selected students to their next grade. This will carry forward their subjects and may flag them for subject review.<br><br><label style='color:#fff;'><input type='checkbox' id='forcePromote'> Force promote (bypass 6-month safeguard)</label>",
            type: 'info',
            showCancelButton: true,
            confirmButtonColor: '#ffab2d',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, promote them!',
            cancelButtonText: 'Cancel',
            customClass: 'swal-dark-popup',
            preConfirm: () => {
                if (document.getElementById('forcePromote') && document.getElementById('forcePromote').checked) {
                    let input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'force';
                    input.value = '1';
                    document.getElementById('bulkPromoteForm').appendChild(input);
                }
            }
        }).then(function(result) {
            if (result.value) {
                document.getElementById('bulkPromoteForm').submit();
            }
        });
    }


</script>
@endpush
