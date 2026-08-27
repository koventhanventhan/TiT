@extends('layouts.admin')

@section('title', 'Teachers')

@push('styles')
    @include('admin.partials.pagination-styles')
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Teachers</h4>
            <div>
                <button type="button" class="btn btn-danger btn-sm mr-2" id="bulkDeleteBtn" style="display: none;" onclick="submitBulkDelete()">
                    <i class="flaticon-381-trash-1"></i> Delete Selected (<span id="selectedCount">0</span>)
                </button>
                <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary btn-sm">
                    <i class="flaticon-381-add-1"></i> Add Teacher
                </a>
            </div>
        </div>
    </div>
</div>

<form id="bulkDeleteForm" action="{{ route('admin.teachers.bulk-delete') }}" method="POST" style="display: none;">
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
                <h4 class="card-title">All Teachers</h4>
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
                                <th style="font-weight: 600;">ID</th>
                                <th style="font-weight: 600;">Name</th>
                                <th style="font-weight: 600;">Email</th>
                                <th style="font-weight: 600;">Phone</th>
                                <th style="font-weight: 600;">subject</th>
                                <th style="font-weight: 600;">Status</th>
                                <th style="font-weight: 600;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($teachers as $t)
                            <tr>
                                  <td>
                                      <div class="custom-control custom-checkbox">
                                          <input type="checkbox" class="custom-control-input teacher-checkbox" id="customCheckBox{{$t->id}}" value="{{$t->id}}">
                                          <label class="custom-control-label" for="customCheckBox{{$t->id}}"></label>
                                      </div>
                                  </td>
                                <td><strong>{{ $t->teacher_unique_id ?? 'â€“' }}</strong></td>
                                <td>{{ $t->name }}</td>
                                <td>{{ $t->email }}</td>
                                <td>{{ $t->phone_number ?? 'â€“' }}</td>
                                <td>{{ $t->teacher_class ?? 'â€“' }}</td>
                                <td>
                                    @if($t->deactivated_at)
                                        <span class="badge badge-danger">Deactivated</span>
                                    @else
                                        <span class="badge badge-success">Active</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-primary light btn-xs sharp" data-toggle="dropdown">
                                            <svg width="1rem" height="1rem" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="12" cy="5" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="12" cy="19" r="2"/></g></svg>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a class="dropdown-item" href="{{ route('admin.teachers.edit', $t->id) }}">Edit Details</a>
                                            @if($t->deactivated_at)
                                                <form action="{{ route('admin.teachers.activate', $t->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-success">Activate Teacher</button>
                                                </form>
                                            @else
                                                <form action="{{ route('admin.teachers.deactivate', $t->id) }}" method="POST" id="teacher-deactivate-form-{{ $t->id }}">
                                                    @csrf
                                                    <button type="button" class="dropdown-item text-warning swal-confirm-btn"
                                                        data-form-id="teacher-deactivate-form-{{ $t->id }}"
                                                        data-title="Deactivate Teacher?"
                                                        data-text="This teacher will no longer be able to log in."
                                                        data-confirm-text="Yes, deactivate!"
                                                        data-confirm-color="#f59e0b">Deactivate Teacher</button>
                                                </form>
                                            @endif
                                            <form action="{{ route('admin.teachers.destroy', $t->id) }}" method="POST" id="teacher-delete-form-{{ $t->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="dropdown-item text-danger swal-confirm-btn"
                                                    data-form-id="teacher-delete-form-{{ $t->id }}"
                                                    data-title="Delete Teacher?"
                                                    data-text="Are you sure you want to PERMANENTLY DELETE this teacher?"
                                                    data-confirm-text="Yes, delete!"
                                                    data-confirm-color="#d33">Delete Teacher</button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 2.5rem; color: #6b7280;">No teachers found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($teachers->hasPages())
                <div class="pagination-footer">
                    <div class="pagination-info">
                        Showing {{ $teachers->firstItem() }} to {{ $teachers->lastItem() }} of {{ $teachers->total() }} results
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
                    {{ $teachers->links('vendor.pagination.custom') }}
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
        const checkboxes = document.querySelectorAll('.teacher-checkbox');
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
        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to delete the selected teachers. This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel',
            customClass: { popup: 'swal-dark-popup' }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('bulkDeleteForm').submit();
            }
        });
    }

    // SweetAlert confirmation for action buttons using Event Delegation
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.swal-confirm-btn');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation();
        
        var formId = btn.getAttribute('data-form-id');
        var title = btn.getAttribute('data-title') || 'Are you sure?';
        var text = btn.getAttribute('data-text') || 'This action cannot be undone.';
        var confirmText = btn.getAttribute('data-confirm-text') || 'Yes, proceed!';
        var confirmColor = btn.getAttribute('data-confirm-color') || '#d33';
        var icon = /delete/i.test(title) ? 'warning' : (/deactivate/i.test(title) ? 'warning' : 'question');

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancel',
            customClass: { popup: 'swal-dark-popup' }
        }).then(function(result) {
            if (result.isConfirmed) {
                var form = document.getElementById(formId);
                if (form) form.submit();
            }
        });
    });
</script>
@endpush
