@extends('layouts.admin')

@section('title', 'Zoom Classes')

@push('styles')
<style>
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.1);
        margin-bottom: 1.25rem;
    }
</style>
@include('admin.partials.pagination-styles')
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Zoom Classes</h4>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-danger btn-sm mb-0" id="bulkDeleteBtn" style="display: none;" onclick="submitBulkDelete()">
                    <i class="flaticon-381-trash-1"></i> Delete (<span id="selectedCount">0</span>)
                </button>
                <a href="{{ route('admin.timetables.index') }}" class="btn btn-primary btn-sm mb-0">
                    <i class="flaticon-381-add-1"></i> New Class
                </a>
            </div>
        </div>
    </div>
</div>

<form id="bulkDeleteForm" action="{{ route('admin.zoom.bulk-delete') }}" method="POST" style="display: none;">
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

<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Recording Settings</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.settings.store') }}" method="POST">
            @csrf
            <h6 class="text-primary mb-2">Disable Automatic Recording Fetching</h6>
            <p class="text-muted mb-3">Select the grades for which automatic Zoom Cloud Recordings fetching should be <strong>DISABLED</strong>.</p>
            <div class="row mb-4">
                <div class="col-12">
                    @php
                        $disabledGrades = json_decode(\App\Models\SiteSetting::get('zoom_recordings_disabled_grades', '[]'), true) ?: [];
                        $allGrades = ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12', 'Grade 13'];
                    @endphp
                    <div class="d-flex flex-wrap" style="gap: 15px;">
                        @foreach($allGrades as $grade)
                        <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" class="custom-control-input" id="disable_{{ str_replace(' ', '', $grade) }}" name="zoom_recordings_disabled_grades[]" value="{{ $grade }}" {{ in_array($grade, $disabledGrades) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="disable_{{ str_replace(' ', '', $grade) }}" style="cursor: pointer;">{{ $grade }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <hr>
            <div class="row mt-3">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label for="recording_visibility_days">Recording Visibility Window (days)</label>
                        <input type="number" name="recording_visibility_days" id="recording_visibility_days" class="form-control" min="1" max="365" value="{{ \App\Models\SiteSetting::get('recording_visibility_days', 2) }}">
                        <small class="text-muted">Automated Zoom recordings older than this many days will be hidden from students. Default: 2 days.</small>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Save Recording Settings</button>
        </form>
    </div>
</div>


<div class="card">
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
                        <th style="width:5rem;"><strong>#</strong></th>
                        <th><strong>Session Details</strong></th>
                        <th><strong>Schedule</strong></th>
                        <th><strong>Subject & Grade</strong></th>
                        <th><strong>Teachers</strong></th>
                        <th><strong>Link</strong></th>
                        <th><strong>Actions</strong></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schedules as $s)
                    <tr>
                          <td>
                              <div class="custom-control custom-checkbox">
                                  <input type="checkbox" class="custom-control-input zoom-checkbox" id="customCheckBox{{$s->id}}" value="{{$s->id}}">
                                  <label class="custom-control-label" for="customCheckBox{{$s->id}}"></label>
                              </div>
                          </td>
                        <td><strong>{{ $s->id }}</strong></td>
                        <td>{{ $s->title }}</td>
                        <td>
                            <div>{{ $s->scheduled_at->format('M d, Y') }}</div>
                            <div class="fs-12 text-muted">{{ $s->scheduled_at->format('H:i') }}</div>
                        </td>
                        <td>
                            <div>{{ $s->subject ?? 'General' }}</div>
                            <div class="fs-12 text-primary">{{ $s->grade ?? 'All Grades' }}</div>
                        </td>
                        <td>
                            @if($s->teachers->count())
                                @foreach($s->teachers as $t)
                                    <div class="badge badge-outline-primary badge-xs mb-1">{{ $t->name }}</div>
                                @endforeach
                            @else
                                <span class="text-muted fs-12">Unassigned</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ $s->zoom_link }}" target="_blank" class="btn btn-xs btn-outline-info">
                                Open
                            </a>
                        </td>
                        <td>
                            <div class="d-flex">
                                <form action="{{ route('admin.zoom.notify', $s->id) }}" method="POST" class="mr-1" id="zoom-notify-{{ $s->id }}">
                                    @csrf
                                    <button type="button" class="btn btn-success shadow btn-xs sharp mr-1 swal-confirm-btn" 
                                        title="Notify"
                                        data-form-id="zoom-notify-{{ $s->id }}"
                                        data-title="Send Notification?"
                                        data-text="Send WhatsApp notification to all students and teachers?">
                                        <i class="fa fa-paper-plane"></i>
                                    </button>
                                </form>
                                <a href="{{ route('admin.zoom.edit', $s->id) }}" class="btn btn-primary shadow btn-xs sharp mr-1" title="Edit"><i class="fa fa-pencil"></i></a>
                                <form action="{{ route('admin.zoom.destroy', $s->id) }}" method="POST" class="d-inline" id="zoom-del-{{ $s->id }}">
                                    @csrf @method('DELETE')
                                    <button type="button" class="btn btn-danger shadow btn-xs sharp swal-confirm-btn" 
                                        title="Delete"
                                        data-form-id="zoom-del-{{ $s->id }}"
                                        data-title="Delete Zoom Class?"
                                        data-text="Are you sure you want to delete this zoom class?">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">No active zoom sessions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($schedules->hasPages())
        <div class="pagination-footer">
            <div class="pagination-info">
                Showing {{ $schedules->firstItem() }} to {{ $schedules->lastItem() }} of {{ $schedules->total() }} results
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
            {{ $schedules->links('vendor.pagination.custom') }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('checkAll');
        const checkboxes = document.querySelectorAll('.zoom-checkbox');
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
            title: 'Delete Zoom Classes?',
            text: 'Are you sure you want to delete the selected zoom classes?',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!',
            customClass: 'swal-dark-popup'
        }).then((result) => {
            if (result.value) {
                document.getElementById('bulkDeleteForm').submit();
            }
        });
    }
</script>
@endpush
