@extends('layouts.admin')

@section('title', 'Weekly Timetable')

@push('styles')
<style>
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.1);
        margin-bottom: 1.25rem;
    }
    .timetable-header {
        background: var(--adm-surface);
        color: var(--adm-text);
        font-weight: bold;
        text-align: center;
        padding: 0.625rem;
        border-bottom: 0.125rem solid var(--adm-border);
    }
    .timetable-day {
        min-height: 12.5rem;
        border-right: 1.0px solid var(--adm-border);
        padding: 0.625rem;
    }
    .timetable-day:last-child {
        border-right: none;
    }
    .timetable-slot {
        background: var(--adm-input-bg);
        border-left: 0.25rem solid var(--adm-accent);
        padding: 0.625rem;
        margin-bottom: 0.625rem;
        border-radius: 0.25rem;
        box-shadow: 0 1.0px 0.1875rem rgba(0,0,0,0.3);
        font-size: 0.8125rem;
    }
    .timetable-slot.inactive {
        border-left-color: var(--adm-border);
        opacity: 0.7;
    }
    .slot-time {
        font-weight: bold;
        color: var(--adm-accent);
    }
    .slot-title {
        color: var(--adm-text);
        margin: 0.125rem 0;
    }
    .slot-details {
        font-size: 0.6875rem;
        color: var(--adm-text-2);
    }
    .slot-actions {
        margin-top: 0.3125rem;
        display: flex;
        gap: 0.3125rem;
    }

    /* Modal & Select Theme Variables */
    .modal-content {
        background-color: var(--adm-surface);
        color: var(--adm-text);
    }
    .modal-header {
        border-bottom: 1.0px solid var(--adm-border);
    }
    .modal-footer {
        border-top: 1.0px solid var(--adm-border);
    }
    .form-group label {
        color: var(--adm-text) !important;
    }
    .bootstrap-select .dropdown-toggle {
        background-color: var(--adm-input-bg) !important;
        border: 1.0px solid var(--adm-border) !important;
        color: var(--adm-text) !important;
    }
    .bootstrap-select .dropdown-menu,
    .bootstrap-select .dropdown-menu .inner,
    .dropdown-menu {
        background-color: var(--adm-surface) !important;
        border: 1.0px solid var(--adm-border) !important;
        color: var(--adm-text) !important;
    }
    .bootstrap-select .dropdown-menu li a,
    .dropdown-item {
        color: var(--adm-text) !important;
        background-color: transparent !important;
    }
    .bootstrap-select .dropdown-menu li a:hover,
    .bootstrap-select .dropdown-menu li.selected a,
    .dropdown-item:hover {
        background-color: var(--adm-accent) !important;
        color: #0f172a !important;
    }
    .bootstrap-select .bs-searchbox input {
        background-color: var(--adm-input-bg) !important;
        border-color: var(--adm-border) !important;
        color: var(--adm-text) !important;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: var(--adm-text);">Weekly Timetable</h4>
            <div>
                <button type="button" onclick="runSync()" class="btn btn-info btn-sm mr-2" title="Manual sync for the next 90 days">
                    <i class="fa fa-sync"></i> Sync to Zoom (90 Days)
                </button>
                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addSlotModal">
                    <i class="flaticon-381-add-1"></i> Add Slot
                </button>
            </div>
        </div>
        <div class="mt-2 text-muted">
            <small class="text-info"><i class="fa fa-info-circle"></i> Timetable slots are automatically synced to Zoom for the next <strong>90 days</strong> whenever you save or update.</small>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="row no-gutters">
            @foreach($days as $day)
            <div class="col">
                <div class="timetable-header">{{ $day }}</div>
                <div class="timetable-day">
                    @foreach($groupedTimetables[$day] as $slot)
                    <div class="timetable-slot {{ $slot->is_active ? '' : 'inactive' }}">
                        <div class="slot-time">{{ date('h:i A', strtotime($slot->start_time)) }}</div>
                        <div class="slot-title"><strong>{{ $slot->title }}</strong></div>
                        <div class="slot-details">
                            <div>Grade: {{ $slot->grade }} <span class="badge badge-sm badge-{{ $slot->medium == 'english' ? 'info' : ($slot->medium == 'both' ? 'success' : 'primary') }}">{{ ucfirst($slot->medium) }}</span></div>
                            <div>Subject: {{ $slot->subject->name ?? 'N/A' }}</div>
                            <div>Teacher: {{ $slot->teacher->name ?? 'N/A' }}</div>
                        </div>
                        <div class="slot-actions">
                            <button type="button" onclick='openEditModal({!! json_encode($slot) !!})' class="btn btn-xs btn-primary sharp"><i class="fa fa-pencil"></i></button>
                            <form action="{{ route('admin.timetables.toggle', $slot->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-xs btn-{{ $slot->is_active ? 'warning' : 'success' }} sharp">
                                    <i class="fa fa-{{ $slot->is_active ? 'pause' : 'play' }}"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.timetables.destroy', $slot->id) }}" method="POST" class="d-inline" id="tt-del-{{ $slot->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-xs btn-danger sharp swal-confirm-btn"
                                    data-form-id="tt-del-{{ $slot->id }}"
                                    data-title="Delete Slot?"
                                    data-text="Are you sure you want to delete this timetable slot?"><i class="fa fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Add Slot Modal -->
<div class="modal fade" id="addSlotModal">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Recurring Timetable Slot</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('admin.timetables.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Slot Title (e.g. Maths Class)</label>
                            <input type="text" name="title" class="form-control" placeholder="Enter title" required value="{{ old('title') }}">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Day of Week</label>
                            <select name="day_of_week" class="form-control" required>
                                @foreach($days as $day)
                                    <option value="{{ $day }}" {{ old('day_of_week') == $day ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Start Time</label>
                            <input type="time" name="start_time" class="form-control" required value="{{ old('start_time') }}">
                        </div>
                        <div class="form-group col-md-4">
                            <label>Duration (Minutes)</label>
                            <input type="number" name="duration" class="form-control" value="{{ old('duration', 60) }}" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Grade</label>
                            <select name="grade" class="form-control" required>
                                <option value="">Select Grade</option>
                                @foreach($grades as $grade)
                                    <option value="{{ $grade }}" {{ old('grade') == $grade ? 'selected' : '' }}>{{ $grade }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Medium</label>
                            <select name="medium" class="form-control" required>
                                <option value="tamil" {{ old('medium') == 'tamil' ? 'selected' : '' }}>Tamil</option>
                                <option value="english" {{ old('medium') == 'english' ? 'selected' : '' }}>English</option>
                                <option value="both" {{ old('medium') == 'both' ? 'selected' : '' }}>Both (Tamil + English)</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Subject</label>
                            <select name="subject_id" id="addSubjectSelect" class="form-control" required>
                                <option value="">Select Subject</option>
                                @foreach($subjects as $s)
                                    <option value="{{ $s->id }}" data-name="{{ $s->name }}" {{ old('subject_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Assigned Teacher</label>
                            <select name="teacher_id" id="addTeacherSelect" class="form-control" required>
                                <option value="">Select Teacher</option>
                                @foreach($teachers as $t)
                                    <option value="{{ $t->id }}" data-subject="{{ strtolower($t->teacher_class ?? '') }}">{{ $t->name }}{{ $t->teacher_class ? ' ('.$t->teacher_class.')' : '' }} - {{ $t->email }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Zoom Host Account (Optional)</label>
                            <select name="zoom_host_email" class="form-control">
                                <option value="">Default (me)</option>
                                @foreach($zoomUsers as $zu)
                                    <option value="{{ $zu['email'] }}" {{ old('zoom_host_email') == $zu['email'] ? 'selected' : '' }}>{{ $zu['display_name'] ?? $zu['email'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-12">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                <label class="custom-control-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Teacher data
    const allTeachers = @json($teachersJson);

    function runSync() {
        Swal.fire({
            title: 'Generate Zoom Links?',
            text: 'This will generate Zoom links for the next 90 days based on this timetable. Continue?',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffab2d',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, generate them!',
            customClass: 'swal-dark-popup'
        }).then((result) => {
            if (result.value) {
                window.location.href = "{{ route('admin.timetables.sync') }}";
            }
        });
    }

    function openEditModal(slot) {
        const form = $('#addSlotModal form');
        const modal = $('#addSlotModal');
        
        // Change title and action
        modal.find('.modal-title').text('Edit Recurring Timetable Slot');
        form.attr('action', '{{ route('admin.timetables.index') }}/' + slot.id);
        form.append('<input type="hidden" name="_method" value="PUT">');
        
        // Fill fields
        form.find('input[name="title"]').val(slot.title);
        form.find('select[name="day_of_week"]').val(slot.day_of_week);
        form.find('input[name="start_time"]').val(slot.start_time.substring(0, 5));
        form.find('input[name="duration"]').val(slot.duration);
        form.find('[name="grade"]').val(slot.grade);
        form.find('[name="medium"]').val(slot.medium);
        form.find('[name="subject_id"]').val(slot.subject_id);
        form.find('select[name="teacher_id"]').val(slot.teacher_id);
        
        form.find('select[name="zoom_host_email"]').val(slot.zoom_host_email);
        form.find('input[name="is_active"]').prop('checked', slot.is_active);
        
        modal.modal('show');
    }

    // Reset modal on close
    $('#addSlotModal').on('hidden.bs.modal', function () {
        const modal = $(this);
        const form = modal.find('form');
        modal.find('.modal-title').text('Add Recurring Timetable Slot');
        form.attr('action', "{{ route('admin.timetables.store') }}");
        form.find('input[name="_method"]').remove();
        form[0].reset();
    });
</script>
@endpush
