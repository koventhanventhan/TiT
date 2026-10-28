@extends('layouts.admin')

@section('title', 'Edit Student Entry')

@push('styles')
<style>
    .form-group label {
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 0.5rem;
    }
    .form-control {
        border-radius: 0.375rem;
        border: 1.0px solid #d1d5db;
        padding: 0.625rem 0.9375rem;
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
    [data-theme-version="dark"] .form-group label {
        color: #ffffff !important;
    }
    .form-control:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 0.1875rem rgba(99, 102, 241, 0.1);
    }
    .radio-group {
        display: flex;
        gap: 0.875rem;
        margin-top: 0.5rem;
    }
    .radio-label {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        cursor: pointer;
        padding: 0.75rem 1.25rem;
        border: 0.125rem solid rgba(139, 92, 246, 0.3);
        border-radius: 0.75rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: rgba(99, 102, 241, 0.1);
        backdrop-filter: blur(0.625rem);
        flex: 1;
        min-width: 7.5rem;
        justify-content: center;
    }
    .radio-label:hover {
        border-color: rgba(139, 92, 246, 0.6);
        background: rgba(99, 102, 241, 0.2);
        transform: translateY(-0.125rem);
        box-shadow: 0 0.25rem 0.75rem rgba(139, 92, 246, 0.3);
    }
    .radio-label span {
        color: #6366f1;
        font-weight: 500;
        font-size: 0.875rem;
        transition: color 0.3s ease;
    }
    .radio-label input[type="radio"] {
        width: 1.25rem;
        height: 1.25rem;
        cursor: pointer;
        accent-color: #8b5cf6;
        margin: 0;
    }
    .radio-label input[type="radio"]:checked + span {
        color: #4f46e5;
        font-weight: 600;
    }
    .radio-label.checked,
    .radio-label:has(input[type="radio"]:checked) {
        border-color: rgba(139, 92, 246, 0.8);
        background: linear-gradient(135deg, rgba(139, 92, 246, 0.25) 0%, rgba(99, 102, 241, 0.2) 100%);
        box-shadow: 
            0 0 0 0.1875rem rgba(139, 92, 246, 0.15),
            0 0.25rem 0.75rem rgba(139, 92, 246, 0.3);
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Edit Student Entry</h4>
            <a href="{{ route('admin.students.index') }}" class="btn btn-secondary btn-sm">
                <i class="flaticon-381-back"></i> Back to List
            </a>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        {{ session('warning') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

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

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Student Information</h4>
            </div>
            <div class="card-body">
                @if($student->parent_id && $student->parent)
                    <div class="alert alert-info mb-4" style="background-color: rgba(99, 102, 241, 0.1); border-left: 4px solid #6366f1; color: #e0e0e0; border-radius: 4px;">
                        <i class="flaticon-381-info text-info mr-2"></i> This student is a sibling. Parent Account: <a href="{{ route('admin.students.show', $student->parent_id) }}" class="text-info font-weight-bold" style="text-decoration: underline;">{{ $student->parent->full_name ?? $student->parent->name }}</a>
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.students.update', $student->id) }}" id="studentEditForm">
                    @csrf
                    @method('PUT')

                    @php
                        // Pre-decode subjects for easy checking
                        $currentSubjects = [];
                        if ($student->selected_subjects) {
                            if (is_array($student->selected_subjects)) {
                                $currentSubjects = $student->selected_subjects;
                            } else {
                                $decoded = json_decode($student->selected_subjects, true);
                                $currentSubjects = is_array($decoded) ? $decoded : array_map('trim', explode(',', (string)$student->selected_subjects));
                            }
                        }
                    @endphp

                    <div class="row">
                        <!-- Index Number & Batch Year (Permanent & Read-only) -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>Student Index Number</span>
                                    @if($student->index_number)
                                        <span class="badge badge-xs badge-success" style="font-size: 10px;"><i class="flaticon-381-lock-1"></i> Read-only</span>
                                    @else
                                        <span class="badge badge-xs badge-warning" style="font-size: 10px;">Pending Assignment</span>
                                    @endif
                                </label>
                                @php
                                    $instituteFormat = optional($student->institute)->getIndexFormat() ?? 'grade';
                                    $institutePrefix = optional($student->institute ?: \App\Models\Institute::find($student->institute_id ?: 1))->getIndexPrefix() ?? 'TIT';
                                    $seriesLabel = $instituteFormat === 'grade'
                                        ? 'Grade ' . ($student->batch_year ? sprintf('%02d', $student->batch_year) : ($student->current_grade ?? ''))
                                        : 'Batch \'' . $student->batch_year;
                                @endphp
                                @if($student->index_number)
                                    @php
                                        $isOldFormat = \App\Services\StudentIndexService::isOldOrWrongFormat($student->index_number, $student->institute ?: \App\Models\Institute::find($student->institute_id ?: 1));
                                        $suggestedConvertSeries = $instituteFormat === 'grade' ? (preg_replace('/[^0-9]/', '', $student->current_grade) ? sprintf('%02d', (int) preg_replace('/[^0-9]/', '', $student->current_grade)) : '08') : '';
                                    @endphp
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="{{ $student->index_number }} ({{ $seriesLabel }})" readonly style="background: rgba(56, 189, 248, 0.08); border-color: rgba(56, 189, 248, 0.4); color: #38bdf8; font-weight: 700; letter-spacing: 0.5px;">
                                        @if($isOldFormat)
                                            <div class="input-group-append">
                                                <span class="badge badge-warning d-flex align-items-center px-2 mr-1" style="font-size: 0.75rem;">old format</span>
                                                <button type="button" class="btn btn-warning btn-sm" onclick="promptConvertToNewFormat({{ $student->id }}, '{{ $student->index_number }}', '{{ $suggestedConvertSeries }}', '{{ $instituteFormat }}')">
                                                    <i class="flaticon-381-refresh-1"></i> Convert to new format
                                                </button>
                                            </div>
                                        @endif
                                        <div class="input-group-append">
                                            @php
                                                $reassignDefaultBatch = $student->batch_year ?? ($instituteFormat === 'grade' ? (preg_replace('/[^0-9]/', '', $student->current_grade) ?: '08') : 28);
                                            @endphp
                                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="promptReassignIndex({{ $student->id }}, '{{ $student->index_number }}', '{{ $reassignDefaultBatch }}', '{{ $instituteFormat }}', '{{ $institutePrefix }}')" title="Re-assign Index Number">
                                                <i class="flaticon-381-edit"></i> Re-assign
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-1">Index numbers are permanent and appear on PDF receipts.</small>
                                @else
                                    <div class="input-group">
                                        <input type="text" class="form-control" value="Pending / Not Assigned" readonly style="background: rgba(255, 255, 255, 0.05); color: #9ca3af;">
                                        <div class="input-group-append">
                                            @php
                                                if ($instituteFormat === 'grade') {
                                                    try {
                                                        $editSuggestedBatch = app(\App\Services\StudentIndexService::class)->deriveGradeSeries($student->current_grade);
                                                    } catch (\Throwable $e) {
                                                        $editSuggestedBatch = $student->batch_year ? sprintf('%02d', $student->batch_year) : '';
                                                    }
                                                } else {
                                                    $editSuggestedBatch = $student->batch_year ?? ($gradeMapping[$student->current_grade] ?? ($gradeMapping[preg_replace('/[^0-9]/', '', $student->current_grade)] ?? ''));
                                                }
                                            @endphp
                                            <button type="button" class="btn btn-info btn-sm" onclick="promptAssignIndex({{ $student->id }}, '{{ $editSuggestedBatch }}', '{{ $instituteFormat }}', '{{ $institutePrefix }}')">
                                                <i class="flaticon-381-id-card"></i> Assign Index
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-1">Generated automatically on confirmation or via the button above.</small>
                                @endif
                            </div>
                        </div>

                        <!-- Full Name -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Full Name<span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $student->full_name) }}" required>
                            </div>
                        </div>

                        <!-- Phone Number -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Phone / WhatsApp @if(!$student->parent_id)<span class="text-danger">*</span>@endif</label>
                                @if($student->parent_id && $student->parent)
                                    <input type="text" class="form-control" value="{{ $student->parent->phone_number }}" readonly disabled>
                                    <small class="text-muted d-block mt-1">Inherited from parent account</small>
                                @else
                                    <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $student->phone_number) }}" required>
                                @endif
                            </div>
                        </div>

                        <!-- Email Address -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Email Address <span class="text-danger">*</span></label>
                                @if($student->parent_id && $student->parent)
                                    <input type="email" class="form-control" value="{{ $student->parent->email }}" readonly disabled>
                                    <small class="text-muted d-block mt-1">Inherited from parent account</small>
                                @else
                                    <input type="email" name="email" class="form-control" value="{{ old('email', $student->email) }}" required>
                                @endif
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Password  <small class="text-muted">(Leave blank to keep current)</small></label>
                                <div style="position: relative;">
                                    <input type="password" name="password" id="editPassword" class="form-control" placeholder="Enter new password" minlength="8" style="padding-right: 3.125rem;">
                                     <button type="button" onclick="togglePassword('editPassword', 'editEyeIcon')" style="position: absolute; right: 0.625rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6366f1; font-size: 1.125rem; padding: 0.3125rem;">
                                         <i id="editEyeIcon" class="la la-eye"></i>
                                     </button>
                                </div>
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-info w-100" id="generatePasswordBtn" onclick="generateAndSendPassword({{ $student->id }}, 'student')">
                                        <i class="flaticon-381-paper-plane"></i> Generate & Send New Password
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Date of Birth -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Date of Birth  <span class="text-danger">*</span></label>
                                <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', is_string($student->date_of_birth) ? $student->date_of_birth : (optional($student->date_of_birth)->format('Y-m-d') ?? '')) }}" required max="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Gender  <span class="text-danger">*</span></label>
                                <select name="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="male" {{ old('gender', $student->gender) === 'male' ? 'selected' : '' }}>Male </option>
                                    <option value="female" {{ old('gender', $student->gender) === 'female' ? 'selected' : '' }}>Female </option>
                                </select>
                            </div>
                        </div>

                        <!-- School Name -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>School Name<span class="text-danger">*</span></label>
                                <input type="text" name="school_name" class="form-control" value="{{ old('school_name', $student->school_name) }}" required>
                            </div>
                        </div>

                        <!-- Medium of Learning -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Medium of Learning  <span class="text-danger">*</span></label>
                                <select name="medium" class="form-control" required>
                                    <option value="">Select Medium</option>
                                    <option value="tamil" {{ old('medium', $student->medium) === 'tamil' ? 'selected' : '' }}>Tamil </option>
                                    <option value="english" {{ old('medium', $student->medium) === 'english' ? 'selected' : '' }}>English </option>
                                </select>
                            </div>
                        </div>

                        <!-- Online Experience -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Online Experience  <span class="text-danger">*</span></label>
                                <select name="online_experience" class="form-control" required>
                                    <option value="">Select Experience</option>
                                    <option value="1" {{ old('online_experience', $student->online_experience) == 1 ? 'selected' : '' }}>Yes </option>
                                    <option value="0" {{ old('online_experience', $student->online_experience) == 0 ? 'selected' : '' }}>No </option>
                                </select>
                            </div>
                        </div>

                         <!-- Current Grade -->
                         <div class="col-md-6 mb-3">
                             <div class="form-group">
                                  <label>Current Grade <span class="text-danger">*</span></label>
                                  <select name="current_grade" id="current_grade" class="form-control" required>
                                      <option value="">Select Grade</option>
                                      @foreach([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13] as $grade)
                                          @php
                                              $gradeString = "தரம் $grade / Grade $grade";
                                              $currentGradeValue = old('current_grade', $student->current_grade);
                                              $isSelected = ($currentGradeValue == $gradeString || $currentGradeValue == $grade);
                                          @endphp
                                          <option value="{{ $grade }}" {{ $isSelected ? 'selected' : '' }}> Grade {{ $grade }}</option>
                                      @endforeach
                                  </select>
                             </div>
                         </div>

                        <!-- Device Used -->
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label>Device Used for Online Classes  <span class="text-danger">*</span></label>
                                <select name="device_used" class="form-control" required>
                                    <option value="">Select Device</option>
                                    @foreach(['Mobile', 'Tablet', 'Laptop', 'Desktop'] as $device)
                                        <option value="{{ $device }}" {{ old('device_used', $student->device_used) == $device ? 'selected' : '' }}>{{ $device }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Stream (Conditional for A/L) -->
                        <div class="col-md-6 mb-3" id="stream_container" style="display: none;">
                            <div class="form-group">
                                 <label>Stream / (A/L) <span class="text-danger">*</span></label>
                                 <select name="stream" id="stream" class="form-control">
                                     <option value="">Select Stream</option>
                                     <option value="arts" {{ old('stream', $student->stream) === 'arts' ? 'selected' : '' }}>A/L – ARTS</option>
                                     <option value="bio_maths" {{ old('stream', $student->stream) === 'bio_maths' ? 'selected' : '' }}>A/L – BIO & MATHS </option>
                                 </select>
                            </div>
                        </div>

                        <!-- Custom Fields -->
                        @if($student->custom_fields && count($student->custom_fields) > 0)
                            <div class="col-md-12 mb-3">
                                <h5 class="text-primary mb-3">Custom Registration Information</h5>
                                <div class="row">
                                    @foreach($student->custom_fields as $key => $value)
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label>{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                                <input type="text" name="custom_fields[{{ $key }}]" class="form-control" value="{{ (is_array($value) || is_object($value)) ? json_encode($value) : $value }}">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="col-md-12 mb-4">
                             <div class="form-group">
                                 <label class="d-block mb-3" style="font-size: 1rem; color: #ffab2d; font-weight: 700;">Select Subjects <span class="text-danger">*</span></label>
                                 
                                 {{-- Subjects for Grade 1-2 --}}
                                 <div class="subject-section" id="subjects_1_2" style="display: none;">
                                     <div class="row">
                                         @if(isset($subjects['grade_1_to_2']))
                                             @foreach($subjects['grade_1_to_2'] as $subject)
                                             <div class="col-md-4 col-6 mb-2">
                                                 <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 0.375rem; background: rgba(255,255,255,0.05); border: 1.0px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                     <input type="checkbox" name="selected_subjects[]" value="{{ $subject->name }}" style="width: 1.125rem; height: 1.125rem; accent-color: #ffab2d; cursor: pointer;" {{ in_array($subject->name, $currentSubjects) ? 'checked' : '' }}>
                                                     <span>{{ $subject->name }}</span>
                                                 </label>
                                             </div>
                                             @endforeach
                                         @endif
                                     </div>
                                 </div>

                                 {{-- Subjects for Grade 3 --}}
                                 <div class="subject-section" id="subjects_3" style="display: none;">
                                     <div class="row">
                                         @if(isset($subjects['grade_3']))
                                             @foreach($subjects['grade_3'] as $subject)
                                             <div class="col-md-4 col-6 mb-2">
                                                 <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 0.375rem; background: rgba(255,255,255,0.05); border: 1.0px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                     <input type="checkbox" name="selected_subjects[]" value="{{ $subject->name }}" style="width: 1.125rem; height: 1.125rem; accent-color: #ffab2d; cursor: pointer;" {{ in_array($subject->name, $currentSubjects) ? 'checked' : '' }}>
                                                     <span>{{ $subject->name }}</span>
                                                 </label>
                                             </div>
                                             @endforeach
                                         @endif
                                     </div>
                                 </div>

                                 {{-- Subjects for Grade 4 --}}
                                 <div class="subject-section" id="subjects_4" style="display: none;">
                                     <div class="row">
                                         @if(isset($subjects['grade_4']))
                                             @foreach($subjects['grade_4'] as $subject)
                                             <div class="col-md-4 col-6 mb-2">
                                                 <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 0.375rem; background: rgba(255,255,255,0.05); border: 1.0px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                     <input type="checkbox" name="selected_subjects[]" value="{{ $subject->name }}" style="width: 1.125rem; height: 1.125rem; accent-color: #ffab2d; cursor: pointer;" {{ in_array($subject->name, $currentSubjects) ? 'checked' : '' }}>
                                                     <span>{{ $subject->name }}</span>
                                                 </label>
                                             </div>
                                             @endforeach
                                         @endif
                                     </div>
                                 </div>

                                 {{-- Subjects for Grade 5 --}}
                                 <div class="subject-section" id="subjects_5" style="display: none;">
                                     <div class="row">
                                         @if(isset($subjects['grade_5']))
                                             @foreach($subjects['grade_5'] as $subject)
                                             <div class="col-md-4 col-6 mb-2">
                                                 <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 0.375rem; background: rgba(255,255,255,0.05); border: 1.0px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                     <input type="checkbox" name="selected_subjects[]" value="{{ $subject->name }}" style="width: 1.125rem; height: 1.125rem; accent-color: #ffab2d; cursor: pointer;" {{ in_array($subject->name, $currentSubjects) ? 'checked' : '' }}>
                                                     <span>{{ $subject->name }}</span>
                                                 </label>
                                             </div>
                                             @endforeach
                                         @endif
                                     </div>
                                 </div>

                                 {{-- Subjects for Grade 6-9 --}}
                                 <div class="subject-section" id="subjects_6_9" style="display: none;">
                                     <div class="row">
                                         @if(isset($subjects['grade_6_to_9']))
                                             @foreach($subjects['grade_6_to_9'] as $subject)
                                             <div class="col-md-4 col-6 mb-2">
                                                 <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 0.375rem; background: rgba(255,255,255,0.05); border: 1.0px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                     <input type="checkbox" name="selected_subjects[]" value="{{ $subject->name }}" style="width: 1.125rem; height: 1.125rem; accent-color: #ffab2d; cursor: pointer;" {{ in_array($subject->name, $currentSubjects) ? 'checked' : '' }}>
                                                     <span>{{ $subject->name }}</span>
                                                 </label>
                                             </div>
                                             @endforeach
                                         @endif
                                     </div>
                                 </div>

                                 {{-- Subjects for Grade 10-11 --}}
                                 <div class="subject-section" id="subjects_10_11" style="display: none;">
                                     <div class="row">
                                         @if(isset($subjects['grade_10_to_11']))
                                             @foreach($subjects['grade_10_to_11'] as $subject)
                                             <div class="col-md-4 col-6 mb-2">
                                                 <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 0.375rem; background: rgba(255,255,255,0.05); border: 1.0px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                     <input type="checkbox" name="selected_subjects[]" value="{{ $subject->name }}" style="width: 1.125rem; height: 1.125rem; accent-color: #ffab2d; cursor: pointer;" {{ in_array($subject->name, $currentSubjects) ? 'checked' : '' }}>
                                                     <span>{{ $subject->name }}</span>
                                                 </label>
                                             </div>
                                             @endforeach
                                         @endif
                                     </div>
                                 </div>

                                 {{-- Subjects for Arts --}}
                                 <div class="subject-section" id="subjects_arts" style="display: none;">
                                     <div class="row">
                                         @if(isset($subjects['arts_stream']))
                                             @foreach($subjects['arts_stream'] as $subject)
                                             <div class="col-md-4 col-6 mb-2">
                                                 <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 0.375rem; background: rgba(255,255,255,0.05); border: 1.0px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                     <input type="checkbox" name="selected_subjects[]" value="{{ $subject->name }}" style="width: 1.125rem; height: 1.125rem; accent-color: #ffab2d; cursor: pointer;" {{ in_array($subject->name, $currentSubjects) ? 'checked' : '' }}>
                                                     <span>{{ $subject->name }}</span>
                                                 </label>
                                             </div>
                                             @endforeach
                                         @endif
                                     </div>
                                 </div>

                                 {{-- Subjects for Bio/Maths --}}
                                 <div class="subject-section" id="subjects_bio_maths" style="display: none;">
                                     <div class="row">
                                         @if(isset($subjects['bio_maths_stream']))
                                             @foreach($subjects['bio_maths_stream'] as $subject)
                                             <div class="col-md-4 col-6 mb-2">
                                                 <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; padding: 0.5rem 0.75rem; border-radius: 0.375rem; background: rgba(255,255,255,0.05); border: 1.0px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                     <input type="checkbox" name="selected_subjects[]" value="{{ $subject->name }}" style="width: 1.125rem; height: 1.125rem; accent-color: #ffab2d; cursor: pointer;" {{ in_array($subject->name, $currentSubjects) ? 'checked' : '' }}>
                                                     <span>{{ $subject->name }}</span>
                                                 </label>
                                             </div>
                                             @endforeach
                                         @endif
                                     </div>
                                 </div>
                             </div>
                        </div>
                    </div>

                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary" style="background: #ffab2d; border-color: #ffab2d; color: #000; font-weight: 700;">
                            <i class="flaticon-381-save"></i> UPDATE STUDENT DETAILS
                        </button>
                        <button type="button" class="btn btn-warning ml-2" style="font-weight: 700;" onclick="submitPromoteForm()">
                            <i class="flaticon-381-upload"></i> PROMOTE STUDENT
                        </button>
                        <a href="{{ route('admin.students.index') }}" class="btn btn-dark ml-2">
                            CANCEL
                        </a>
                    </div>
                </form>

                <form id="promoteStudentForm" action="{{ route('admin.students.promote') }}" method="POST" style="display: none;">
                    @csrf
                    <input type="hidden" name="ids" value="{{ $student->id }}">
                </form>

                <div class="payment-management-section mt-5 border-top pt-4">
                    <h4 class="mb-4" style="color: #ffab2d; font-weight: 700;"><i class="flaticon-381-layer-1 mr-2"></i> MONTHLY PAYMENT STATUS</h4>

                    @if(empty($student->admin_confirmed_at) && $student->registration_status !== 'rejected')
                    <div class="p-4 rounded mb-4" id="confirm-and-mark-paid-card" style="background: rgba(56, 189, 248, 0.08); border-left: 0.3125rem solid #38bdf8;">
                        <h5 class="mb-2" style="color: #fff;">Confirm registration + mark paid</h5>
                        <p class="mb-3 small text-muted">Use this when the money for {{ now()->format('F Y') }} has already been received: the registration is confirmed, an index number is assigned (if missing) and the payment is marked as paid with a receipt.</p>
                        <form action="{{ route('admin.students.confirm-and-mark-paid', $student->id) }}" method="POST" onsubmit="if(!confirm('Money received? This confirms the registration and marks {{ now()->format('F Y') }} as paid.')) { return false; } this.querySelector('button[type=submit]').disabled=true;">
                            @csrf
                            @include('admin.partials.received-by-select', ['dark' => true])
                            <button type="submit" class="btn btn-info font-weight-bold" style="border-radius: 0.5rem;">CONFIRM + MARK PAID (MONEY RECEIVED)</button>
                        </form>
                    </div>
                    @endif

                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="p-4 rounded" style="background: rgba(255, 171, 45, 0.1); border-left: 0.3125rem solid #ffab2d; height: 100%;">
                                <h5 class="mb-3" style="color: #fff;">Quick Payment (Current Month)</h5>
                                <p class="mb-3">Mark student as paid for <strong>{{ now()->format('F Y') }}</strong></p>
                                <form action="{{ route('admin.students.mark-paid', $student->id) }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                                    @csrf
                                    <input type="hidden" name="year_month" value="{{ now()->format('Y-m-d') }}">
                                    @include('admin.partials.received-by-select', ['dark' => true])
                                    <button type="submit" class="btn btn-success font-weight-bold btn-block" style="border-radius: 0.5rem;">
                                        MARK PAID: {{ strtoupper(now()->format('F Y')) }}
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="p-4 rounded shadow-sm" style="background: rgba(255, 255, 255, 0.05); border: 1.0px dashed rgba(255, 255, 255, 0.2); height: 100%;">
                                <h5 class="mb-3" style="color: #fff;">Custom Payment Month</h5>
                                <form action="{{ route('admin.students.mark-paid', $student->id) }}" method="POST" onsubmit="this.querySelector('button[type=submit]').disabled=true;">
                                    @csrf
                                    <div class="form-group">
                                        <label class="small text-muted">Select Date 
                                             </label>
                                        <div class="input-group">
                                            <input type="date" name="year_month" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-info" style="border-top-right-radius: 0.5rem; border-bottom-right-radius: 0.5rem;">UPDATE</button>
                                            </div>
                                        </div>
                                    </div>
                                    @include('admin.partials.received-by-select', ['dark' => true])
                                </form>
                                <small class="text-muted d-block mt-2">Select a date to mark payment 
                                     </small>
                            </div>
                        </div>
                    </div>

                    {{-- Payment History Table --}}
                    @if(isset($payments) && $payments->count() > 0)
                    @php
                        $latestPaidPayment = $payments->where('status', 'paid')->sortByDesc('year_month')->first();
                    @endphp
                    <div class="mt-5 border-top pt-4">
                        <h4 class="mb-4" style="color: #4caf50; font-weight: 700;"><i class="flaticon-381-notepad mr-2"></i> PAYMENT HISTORY</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered" style="color: #fff;">
                                <thead style="background: rgba(76, 175, 80, 0.2);">
                                    <tr>
                                        <th style="color: #4caf50; font-weight: 700;">#</th>
                                        <th style="color: #4caf50; font-weight: 700;">Month</th>
                                        <th style="color: #4caf50; font-weight: 700;">Paid Date</th>
                                        <th style="color: #4caf50; font-weight: 700;">Amount</th>
                                        <th style="color: #4caf50; font-weight: 700;">Status</th>
                                        <th style="color: #4caf50; font-weight: 700;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $index => $payment)
                                    @php
                                        $isLatestPaid = ($payment->status === 'paid' && $latestPaidPayment && $latestPaidPayment->id === $payment->id);
                                    @endphp
                                    <tr style="border-bottom: 1.0px solid rgba(255,255,255,0.1);">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <strong>{{ $payment->year_month }}</strong>
                                            @if($payment->kind === 'topup')
                                                <div class="mt-1">
                                                    <span class="badge" style="background: #f59e0b; color: #fff; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px;">Subject top-up</span>
                                                    @php
                                                        $topupSubs = $payment->meta['added_subjects'] ?? [];
                                                    @endphp
                                                    @if(!empty($topupSubs))
                                                        <small class="d-block text-truncate" style="color: #cbd5e1; font-size: 0.7rem; max-width: 160px;" title="{{ is_array($topupSubs) ? implode(', ', $topupSubs) : $topupSubs }}">
                                                            + {{ is_array($topupSubs) ? implode(', ', $topupSubs) : $topupSubs }}
                                                        </small>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d') : '-' }}</td>
                                        <td>Rs. {{ number_format($payment->amount, 2) }}</td>
                                        <td>
                                            @if($payment->status === 'paid')
                                                <span class="badge" style="background: #4caf50; color: #fff; padding: 0.3125rem 0.75rem; border-radius: 1.25rem;">PAID</span>
                                                <div class="small mt-1 payment-method-hint" style="color: #cbd5e1;">
                                                    @if($payment->displayMethod() === 'online')
                                                        Online (PayHere){{ $payment->transaction_id ? ' · ' . $payment->transaction_id : '' }}
                                                    @else
                                                        Offline{{ $payment->receivedByLabel() ? ' · ' . $payment->receivedByLabel() : '' }}
                                                    @endif
                                                </div>
                                                <div class="mt-1">
                                                    @if($payment->receipt_sent_at)
                                                        <span class="badge badge-success" style="font-size: 10px; background: rgba(34, 197, 94, 0.2); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.4);" title="Delivered: {{ $payment->receipt_sent_at->format('Y-m-d H:i') }}">
                                                            <i class="fa fa-check-circle mr-1"></i> Receipt sent {{ $payment->receipt_sent_at->format('d M Y') }}
                                                        </span>
                                                    @elseif($payment->receipt_last_error)
                                                        <span class="badge badge-danger" style="font-size: 10px; background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.4);" title="{{ $payment->receipt_last_error }}">
                                                            <i class="fa fa-exclamation-circle mr-1"></i> Receipt failed – {{ \Illuminate\Support\Str::limit($payment->receipt_last_error, 24) }}
                                                        </span>
                                                    @else
                                                        <span class="badge badge-secondary" style="font-size: 10px; background: rgba(156, 163, 175, 0.2); color: #d1d5db; border: 1px solid rgba(209, 213, 219, 0.3);">
                                                            <i class="fa fa-clock-o mr-1"></i> Not sent yet
                                                        </span>
                                                    @endif
                                                </div>
                                            @elseif($payment->status === 'pending')
                                                <span class="badge" style="background: #ffab2d; color: #fff; padding: 0.3125rem 0.75rem; border-radius: 1.25rem;">PENDING</span>
                                            @elseif($payment->status === 'cancelled')
                                                <span class="badge" style="background: #757575; color: #fff; padding: 0.3125rem 0.75rem; border-radius: 1.25rem;">CANCELLED</span>
                                            @else
                                                <span class="badge" style="background: #f44336; color: #fff; padding: 0.3125rem 0.75rem; border-radius: 1.25rem;">{{ strtoupper($payment->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="nowrap-column">
                                            <div class="payment-actions-wrap">
                                                {{-- 1. Edit Button --}}
                                                <button type="button" 
                                                        class="btn btn-sm btn-action-edit" 
                                                        data-toggle="modal" 
                                                        data-target="#editPaymentModal_{{ $payment->id }}"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editPaymentModal_{{ $payment->id }}"
                                                        title="Edit payment {{ $payment->year_month }}" 
                                                        aria-label="Edit payment {{ $payment->year_month }}"
                                                        style="min-height: 36px; background: #ffab2d; border-color: #ffab2d; color: #1a103c; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px;">
                                                    <i class="fa fa-pencil"></i> <span>Edit</span>
                                                </button>

                                                {{-- 2. Context Action Button (Receipt / Resend / Revert / Mark Paid) --}}
                                                @if($payment->status === 'paid')
                                                    {{-- Receipt View/Download Button --}}
                                                    <a href="{{ route('admin.students.payments.receipt', ['id' => $student->id, 'payment' => $payment->id]) }}" 
                                                       target="_blank" 
                                                       class="btn btn-sm btn-action-receipt" 
                                                       title="View / Download PDF Receipt" 
                                                       aria-label="View / Download PDF Receipt"
                                                       style="min-height: 36px; background: #0284c7; border-color: #0284c7; color: #fff; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px;">
                                                        <i class="fa fa-file-pdf-o"></i> <span>Receipt</span>
                                                    </a>

                                                    {{-- Resend Receipt Button --}}
                                                    <form action="{{ route('admin.students.payments.resend-receipt', ['id' => $student->id, 'payment' => $payment->id]) }}" method="POST" style="display: inline-block; margin: 0;">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-action-resend" 
                                                                title="Resend receipt email & WhatsApp" 
                                                                aria-label="Resend receipt email & WhatsApp"
                                                                style="min-height: 36px; background: #6366f1; border-color: #6366f1; color: #fff; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px;">
                                                            <i class="fa fa-paper-plane"></i> <span>Resend</span>
                                                        </button>
                                                    </form>

                                                    <button type="button" 
                                                            class="btn btn-sm btn-action-revert" 
                                                            data-toggle="modal" 
                                                            data-target="#revertPaymentModal_{{ $payment->id }}"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#revertPaymentModal_{{ $payment->id }}"
                                                            title="Revert payment {{ $payment->year_month }} to pending" 
                                                            aria-label="Revert payment {{ $payment->year_month }} to pending"
                                                            style="min-height: 36px; background: #ff9800; border-color: #ff9800; color: #fff; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px;">
                                                        <i class="fa fa-undo"></i> <span>Revert</span>
                                                    </button>
                                                @else
                                                    <button type="button" 
                                                            class="btn btn-sm btn-action-mark-paid" 
                                                            data-toggle="modal" 
                                                            data-target="#markPaidModal_{{ $payment->id }}"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#markPaidModal_{{ $payment->id }}"
                                                            title="Mark payment {{ $payment->year_month }} as paid" 
                                                            aria-label="Mark payment {{ $payment->year_month }} as paid"
                                                            style="min-height: 36px; background: #4caf50; border-color: #4caf50; color: #fff; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px;">
                                                        <i class="fa fa-check"></i> <span>Mark Paid</span>
                                                    </button>
                                                @endif

                                                {{-- 3. Delete Button --}}
                                                <button type="button" 
                                                        class="btn btn-sm btn-action-delete" 
                                                        data-toggle="modal" 
                                                        data-target="#deletePaymentModal_{{ $payment->id }}"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deletePaymentModal_{{ $payment->id }}"
                                                        title="Delete payment {{ $payment->year_month }}" 
                                                        aria-label="Delete payment {{ $payment->year_month }}"
                                                        style="min-height: 36px; background: #f44336; border-color: #f44336; color: #fff; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 6px;">
                                                    <i class="fa fa-trash"></i> <span>Delete</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Modals for Payment {{ $payment->id }} --}}
                                    {{-- Edit Modal --}}
                                    <div class="modal fade" id="editPaymentModal_{{ $payment->id }}" tabindex="-1" role="dialog" aria-labelledby="editPaymentModalLabel_{{ $payment->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content" style="background: #1e1b4b; color: #fff; border: 1px solid rgba(255,255,255,0.15); border-radius: 12px;">
                                                <form action="{{ route('admin.students.payments.update', ['id' => $student->id, 'payment' => $payment->id]) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="payment_id" value="{{ $payment->id }}">
                                                    <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                                        <h5 class="modal-title text-white" id="editPaymentModalLabel_{{ $payment->id }}">
                                                            <i class="fa fa-pencil text-warning mr-2"></i> Edit Payment — {{ $payment->year_month }}
                                                        </h5>
                                                        <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body text-left">
                                                        @if($errors->any() && old('payment_id') == $payment->id)
                                                            <div class="alert alert-danger" style="background: rgba(244, 67, 54, 0.2); border: 1px solid #f44336; color: #fff;">
                                                                <ul class="mb-0 pl-3">
                                                                    @foreach($errors->all() as $err)
                                                                        <li>{{ $err }}</li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        @endif

                                                        <div class="form-group mb-3">
                                                            <label class="text-white font-weight-bold">Month (YYYY-MM) <span class="text-danger">*</span></label>
                                                            <input type="month" class="form-control" name="year_month" 
                                                                value="{{ old('payment_id') == $payment->id ? old('year_month', $payment->year_month) : $payment->year_month }}" 
                                                                required pattern="^\d{4}-(0[1-9]|1[0-2])$"
                                                                style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                                                        </div>

                                                        <div class="form-group mb-3">
                                                            <label class="text-white font-weight-bold">Paid Date</label>
                                                            <input type="date" class="form-control" name="paid_date" 
                                                                value="{{ old('payment_id') == $payment->id ? old('paid_date', ($payment->paid_at ? $payment->paid_at->format('Y-m-d') : '')) : ($payment->paid_at ? $payment->paid_at->format('Y-m-d') : '') }}"
                                                                style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                                                            <small class="text-muted">Cleared automatically if status is not Paid. Defaults to today if status is Paid and date is empty.</small>
                                                        </div>

                                                        @if($payment->kind === 'topup')
                                                            <div class="alert alert-warning py-1 px-2 mb-3" style="font-size: 0.75rem; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.35); color: #fcd34d;">
                                                                <i class="fa fa-info-circle mr-1"></i> Subject top-up payment. Amount can only be edited while pending.
                                                            </div>
                                                        @endif

                                                        <div class="form-group mb-3">
                                                            <label class="text-white font-weight-bold">Amount (Rs.) <span class="text-danger">*</span></label>
                                                            <input type="number" step="0.01" min="0" class="form-control" name="amount" 
                                                                value="{{ old('payment_id') == $payment->id ? old('amount', $payment->amount) : $payment->amount }}" 
                                                                required
                                                                {{ ($payment->kind === 'topup' && $payment->status !== 'pending') ? 'readonly' : '' }}
                                                                style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                                                        </div>

                                                        <div class="form-group mb-3">
                                                            <label class="text-white font-weight-bold">Status <span class="text-danger">*</span></label>
                                                            @php $curStatus = old('payment_id') == $payment->id ? old('status', $payment->status) : $payment->status; @endphp
                                                            <select class="form-control" name="status" required style="background: #1a103c; border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                                                                <option value="paid" {{ $curStatus === 'paid' ? 'selected' : '' }}>Paid</option>
                                                                <option value="pending" {{ $curStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                                                <option value="cancelled" {{ $curStatus === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                                <option value="failed" {{ $curStatus === 'failed' ? 'selected' : '' }}>Failed</option>
                                                            </select>
                                                        </div>

                                                        <div class="form-group mb-3">
                                                            <label class="text-white font-weight-bold">Payment Method</label>
                                                            @php $curMethod = old('payment_id') == $payment->id ? old('payment_method', $payment->payment_method) : $payment->payment_method; @endphp
                                                            <select class="form-control" name="payment_method" style="background: #1a103c; border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                                                                <option value="offline" {{ $curMethod === 'offline' ? 'selected' : '' }}>Offline</option>
                                                                <option value="online" {{ $curMethod === 'online' ? 'selected' : '' }}>Online</option>
                                                                <option value="bank_transfer" {{ $curMethod === 'bank_transfer' ? 'selected' : '' }}>Bank transfer</option>
                                                                <option value="cash" {{ $curMethod === 'cash' ? 'selected' : '' }}>Cash</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.1);">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal" style="min-height: 36px;">Cancel</button>
                                                        <button type="submit" class="btn btn-primary" style="background: #ffab2d; border-color: #ffab2d; color: #1a103c; font-weight: 700; min-height: 36px;">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Delete Modal --}}
                                    <div class="modal fade" id="deletePaymentModal_{{ $payment->id }}" tabindex="-1" role="dialog" aria-labelledby="deletePaymentModalLabel_{{ $payment->id }}" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered" role="document">
                                            <div class="modal-content" style="background: #1e1b4b; color: #fff; border: 1px solid rgba(244,67,54,0.3); border-radius: 12px;">
                                                <form action="{{ route('admin.students.payments.destroy', ['id' => $student->id, 'payment' => $payment->id]) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                                        <h5 class="modal-title text-white" id="deletePaymentModalLabel_{{ $payment->id }}">
                                                            <i class="fa fa-trash text-danger mr-2"></i> Delete Payment
                                                        </h5>
                                                        <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body text-left">
                                                        <p class="mb-2" style="font-size: 1rem;">
                                                            Delete payment <strong>{{ $payment->year_month }}</strong> – Rs. {{ number_format($payment->amount, 2) }} ({{ strtoupper($payment->status) }})? 
                                                            This cannot be undone.
                                                        </p>
                                                        @if($isLatestPaid)
                                                            <div class="alert alert-danger mt-3 mb-0" style="background: rgba(244, 67, 54, 0.2); border: 1px solid #f44336; color: #fca5a5; font-size: 0.875rem;">
                                                                <i class="fa fa-exclamation-triangle mr-1"></i> The student may be treated as unpaid for this month after deleting.
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.1);">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal" style="min-height: 36px;">Cancel</button>
                                                        <button type="submit" class="btn btn-danger" style="min-height: 36px; font-weight: 700; background: #f44336; border-color: #f44336;">Delete Payment</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    @if($payment->status === 'paid')
                                        {{-- Revert to Pending Modal --}}
                                        <div class="modal fade" id="revertPaymentModal_{{ $payment->id }}" tabindex="-1" role="dialog" aria-labelledby="revertPaymentModalLabel_{{ $payment->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content" style="background: #1e1b4b; color: #fff; border: 1px solid rgba(255,152,0,0.3); border-radius: 12px;">
                                                    <form action="{{ route('admin.students.payments.revert', ['id' => $student->id, 'payment' => $payment->id]) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                                            <h5 class="modal-title text-white" id="revertPaymentModalLabel_{{ $payment->id }}">
                                                                <i class="fa fa-undo text-warning mr-2"></i> Revert to Pending
                                                            </h5>
                                                            <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body text-left">
                                                            <p class="mb-0" style="font-size: 1rem;">
                                                                Revert payment for <strong>{{ $payment->year_month }}</strong> (Rs. {{ number_format($payment->amount, 2) }}) back to <strong>PENDING</strong>? This will clear the paid date.
                                                            </p>
                                                        </div>
                                                        <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.1);">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal" style="min-height: 36px;">Cancel</button>
                                                            <button type="submit" class="btn btn-warning" style="min-height: 36px; font-weight: 700; color: #fff; background: #ff9800; border-color: #ff9800;">Revert to Pending</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        {{-- Mark Paid Modal --}}
                                        <div class="modal fade" id="markPaidModal_{{ $payment->id }}" tabindex="-1" role="dialog" aria-labelledby="markPaidModalLabel_{{ $payment->id }}" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content" style="background: #1e1b4b; color: #fff; border: 1px solid rgba(76,175,80,0.3); border-radius: 12px;">
                                                    <form action="{{ route('admin.students.payments.mark-paid', ['id' => $student->id, 'payment' => $payment->id]) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                                            <h5 class="modal-title text-white" id="markPaidModalLabel_{{ $payment->id }}">
                                                                <i class="fa fa-check text-success mr-2"></i> Mark Month as Paid
                                                            </h5>
                                                            <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body text-left">
                                                            <p class="mb-3" style="font-size: 1rem;">
                                                                Mark payment for <strong>{{ $payment->year_month }}</strong> (Rs. {{ number_format($payment->amount, 2) }}) as <strong>PAID</strong>?
                                                            </p>
                                                            <div class="form-group mb-0">
                                                                <label class="text-white font-weight-bold">Paid Date</label>
                                                                <input type="date" class="form-control" name="paid_date" value="{{ date('Y-m-d') }}" required
                                                                    style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                                                            </div>
                                                            <div class="mt-3">
                                                                @include('admin.partials.received-by-select', ['dark' => true])
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.1);">
                                                            <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal" style="min-height: 36px;">Cancel</button>
                                                            <button type="submit" class="btn btn-success" style="min-height: 36px; font-weight: 700; background: #4caf50; border-color: #4caf50;">Mark Paid</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @else
                    <div class="mt-4 text-center p-4" style="background: rgba(255,255,255,0.05); border-radius: 0.5rem;">
                        <p class="mb-0" style="color: #938787;">No payment records yet</p>
                    </div>
                    @endif

                    <style>
                    .payment-actions-wrap {
                        display: flex;
                        align-items: center;
                        gap: 6px;
                        flex-wrap: wrap;
                        min-width: 220px;
                    }
                    @media (max-width: 575.98px) {
                        .payment-actions-wrap {
                            flex-direction: column;
                            align-items: stretch !important;
                            min-width: 0;
                        }
                        .payment-actions-wrap .btn {
                            width: 100%;
                            justify-content: center;
                        }
                    }
                    </style>

                    @if($errors->any() && old('payment_id'))
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            if (typeof $ !== 'undefined') {
                                $('#editPaymentModal_{{ old('payment_id') }}').modal('show');
                            }
                        });
                    </script>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const gradeSelect = document.getElementById('current_grade');
        const streamSelect = document.getElementById('stream');
        const streamContainer = document.getElementById('stream_container');
        
        function updateSubjectSections() {
            const gradeValue = gradeSelect.value || "";
            const streamValue = streamSelect.value || "";
            
            // Extract number from value
            let gradeNum = null;
            if (!isNaN(gradeValue) && gradeValue !== "") {
                gradeNum = parseInt(gradeValue);
            } else {
                const match = gradeValue.match(/Grade\s*(\d+)/i);
                gradeNum = match ? parseInt(match[1]) : null;
            }
            
            // Hide all sections first and DISABLE their checkboxes
            document.querySelectorAll('.subject-section').forEach(section => {
                section.style.display = 'none';
                section.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.disabled = true);
            });
            streamContainer.style.display = 'none';
            
            if (gradeNum) {
                if (gradeNum >= 1 && gradeNum <= 2) {
                    const sec = document.getElementById('subjects_1_2');
                    if (sec) { sec.style.display = 'block'; sec.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.disabled = false); }
                } else if (gradeNum === 3) {
                    const sec = document.getElementById('subjects_3');
                    if (sec) { sec.style.display = 'block'; sec.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.disabled = false); }
                } else if (gradeNum === 4) {
                    const sec = document.getElementById('subjects_4');
                    if (sec) { sec.style.display = 'block'; sec.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.disabled = false); }
                } else if (gradeNum === 5) {
                    const sec = document.getElementById('subjects_5');
                    if (sec) { sec.style.display = 'block'; sec.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.disabled = false); }
                } else if (gradeNum >= 6 && gradeNum <= 9) {
                    const sec = document.getElementById('subjects_6_9');
                    if (sec) { sec.style.display = 'block'; sec.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.disabled = false); }
                } else if (gradeNum >= 10 && gradeNum <= 11) {
                    const sec = document.getElementById('subjects_10_11');
                    if (sec) { sec.style.display = 'block'; sec.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.disabled = false); }
                } else if (gradeNum >= 12 && gradeNum <= 13) {
                    streamContainer.style.display = 'block';
                    if (streamValue === 'arts') {
                        const sec = document.getElementById('subjects_arts');
                        if (sec) {
                            sec.style.display = 'block';
                            sec.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.disabled = false);
                        }
                    } else if (streamValue === 'bio_maths') {
                        const sec = document.getElementById('subjects_bio_maths');
                        if (sec) {
                            sec.style.display = 'block';
                            sec.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.disabled = false);
                        }
                    }
                }
            }
        }
        
        if (gradeSelect) {
            gradeSelect.addEventListener('change', function() {
                const gradeValue = this.value || "";
                let gradeNum = null;
                if (!isNaN(gradeValue) && gradeValue !== "") {
                    gradeNum = parseInt(gradeValue);
                } else {
                    const match = gradeValue.match(/Grade\s*(\d+)/i);
                    gradeNum = match ? parseInt(match[1]) : null;
                }
                
                if (gradeNum < 12 && streamSelect) {
                    streamSelect.value = '';
                }
                updateSubjectSections();
            });
        }
        
        if (streamSelect) {
            streamSelect.addEventListener('change', updateSubjectSections);
        }
        
        // Initial call
        updateSubjectSections();
    });

    // Toggle password visibility
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('la-eye');
            icon.classList.add('la-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('la-eye-slash');
            icon.classList.add('la-eye');
        }
    }

    function generateAndSendPassword(id, type) {
        Swal.fire({
            title: 'Generate Password?',
            text: 'Are you sure you want to generate a new password and email it to this ' + type + '?',
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffab2d',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, generate it!',
            customClass: 'swal-dark-popup'
        }).then((result) => {
            if (result.value) {
                let btn = document.getElementById('generatePasswordBtn');
                let originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';
                btn.disabled = true;

                fetch('{{ route('admin.students.reset-password', $student->id, false) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                })
                .then(res => res.json())
                .then(data => {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    
                    if (data.success) {
                        Swal.fire({ title: 'Success', text: data.message, type: 'success', customClass: 'swal-dark-popup' });
                        document.getElementById('editPassword').value = '';
                    } else {
                        Swal.fire({ title: 'Error', text: data.message || 'An error occurred.', type: 'error', customClass: 'swal-dark-popup' });
                    }
                })
                .catch(err => {
                    console.error(err);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    Swal.fire({ title: 'Error', text: 'A network error occurred.', type: 'error', customClass: 'swal-dark-popup' });
                });
            }
        });
    }

    function submitPromoteForm() {
        Swal.fire({
            title: 'Promote Student?',
            html: "This will move the student to the next grade and carry forward matching subjects automatically.<br><br><label style='color:#fff;'><input type='checkbox' id='forceCheckbox'> Force promote (bypass 6-month safeguard)</label>",
            type: 'info',
            showCancelButton: true,
            confirmButtonColor: '#ffab2d',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, promote!',
            cancelButtonText: 'Cancel',
            customClass: 'swal-dark-popup',
            preConfirm: () => {
                return {
                    force: document.getElementById('forceCheckbox').checked
                };
            }
        }).then((result) => {
            if (result.value) {
                const form = document.getElementById('promoteStudentForm');
                if (result.value.force) {
                    let input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'force';
                    input.value = '1';
                    form.appendChild(input);
                }
                form.submit();
            }
        });
    }

    function promptAssignIndex(studentId, defaultBatch, format, prefix) {
        prefix = prefix || 'TIT';
        const isGrade = (format === 'grade');
        const defaultSeriesNum = defaultBatch ? parseInt(defaultBatch, 10) : '';
        const val = (!isNaN(defaultSeriesNum) && defaultSeriesNum >= 1 && defaultSeriesNum <= 99) ? String(defaultSeriesNum).padStart(2, '0') : '';
        const seriesLabel = isGrade ? 'Series (01–99, default = grade):' : 'Batch Year (01–99):';

        Swal.fire({
            title: 'Assign Student Index Number',
            html: `
                <div style="text-align: left; font-size: 0.9rem;">
                    <div class="form-group mb-2">
                        <label style="color: #fff; font-weight: 600; display: block; margin-bottom: 4px;">${seriesLabel}</label>
                        <input id="swal-series" type="text" value="${val}" placeholder="e.g. 08" class="form-control" style="background: #1f2937; color: #fff; border: 1px solid #4b5563; font-weight: bold;">
                    </div>
                    <div class="form-group mb-2">
                        <label style="color: #fff; font-weight: 600; display: block; margin-bottom: 4px;">Running Number (Optional):</label>
                        <input id="swal-seq" type="number" min="1" placeholder="Empty = next free number (e.g. 110 or 550)" class="form-control" style="background: #1f2937; color: #fff; border: 1px solid #4b5563;">
                        <small style="color: #9ca3af; font-size: 0.8rem;">Leave empty for next automatic number, or type an exact number.</small>
                    </div>
                    <div style="margin-top: 12px; padding: 10px; background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 6px; text-align: center;">
                        <span style="color: #cbd5e1; font-size: 0.85rem;">Preview: </span>
                        <strong id="swal-preview-index" style="color: #38bdf8; font-size: 1.15rem; letter-spacing: 0.5px;">${prefix}/${val || '01'}/...</strong>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Assign Index Number',
            confirmButtonColor: '#38bdf8',
            customClass: 'swal-dark-popup',
            onOpen: () => {
                const seriesInput = document.getElementById('swal-series');
                const seqInput = document.getElementById('swal-seq');
                const previewEl = document.getElementById('swal-preview-index');

                function updatePreview() {
                    const sRaw = seriesInput.value.trim();
                    const sNum = parseInt(sRaw, 10);
                    const sStr = (!isNaN(sNum) && sNum >= 1 && sNum <= 99) ? String(sNum).padStart(2, '0') : (sRaw || '??');
                    const seqRaw = seqInput.value.trim();
                    let seqStr = '<next>';
                    if (seqRaw) {
                        const seqNum = parseInt(seqRaw, 10);
                        if (!isNaN(seqNum) && seqNum > 0) {
                            seqStr = String(seqNum).padStart(3, '0');
                        } else {
                            seqStr = seqRaw;
                        }
                    }
                    previewEl.textContent = `${prefix}/${sStr}/${seqStr}`;
                }

                seriesInput.addEventListener('input', updatePreview);
                seqInput.addEventListener('input', updatePreview);
                updatePreview();
            },
            preConfirm: () => {
                const series = document.getElementById('swal-series').value.trim();
                const seq = document.getElementById('swal-seq').value.trim();
                const num = parseInt(series, 10);
                if (!series || isNaN(num) || num < 1 || num > 99) {
                    Swal.showValidationMessage('Series must be a number 01–99');
                    return false;
                }
                if (seq) {
                    const seqNum = parseInt(seq, 10);
                    if (isNaN(seqNum) || seqNum < 1) {
                        Swal.showValidationMessage('Running number must be at least 1');
                        return false;
                    }
                }
                return {
                    series: String(num).padStart(2, '0'),
                    seq: seq ? parseInt(seq, 10) : ''
                };
            }
        }).then((res) => {
            if (res.value) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('admin.students.assign-index', $student->id, false) }}';
                form.innerHTML = `
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="batch_year" value="${res.value.series}">
                    <input type="hidden" name="running_number" value="${res.value.seq}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function promptReassignIndex(studentId, currentIndex, currentBatch, format, prefix) {
        prefix = prefix || 'TIT';
        const isGrade = (format === 'grade');
        const defaultSeriesNum = currentBatch ? parseInt(currentBatch, 10) : '';
        const val = (!isNaN(defaultSeriesNum) && defaultSeriesNum >= 1 && defaultSeriesNum <= 99) ? String(defaultSeriesNum).padStart(2, '0') : '';
        const seriesLabel = isGrade ? 'New Series (01–99):' : 'New Batch Year (01–99):';

        Swal.fire({
            title: 'Re-assign Index Number?',
            html: `
                <div style="text-align: left; font-size: 0.9rem;">
                    <p style="color: #fca5a5; margin-bottom: 8px;">⚠️ WARNING: Student currently has <strong>${currentIndex}</strong>. Receipts and exam records may reference this number.</p>
                    <p style="color: #cbd5e1; margin-bottom: 12px; font-size: 0.85rem;">This action will be recorded in the system audit logs.</p>
                    <div class="form-group mb-2">
                        <label style="color: #fff; font-weight: 600; display: block; margin-bottom: 4px;">${seriesLabel}</label>
                        <input id="swal-new-series" type="text" value="${val}" placeholder="e.g. 08" class="form-control" style="background: #1f2937; color: #fff; border: 1px solid #4b5563; font-weight: bold;">
                    </div>
                    <div class="form-group mb-2">
                        <label style="color: #fff; font-weight: 600; display: block; margin-bottom: 4px;">Running Number (Optional):</label>
                        <input id="swal-new-seq" type="number" min="1" placeholder="Empty = next free number (e.g. 110 or 550)" class="form-control" style="background: #1f2937; color: #fff; border: 1px solid #4b5563;">
                        <small style="color: #9ca3af; font-size: 0.8rem;">Leave empty for next automatic number, or type an exact number.</small>
                    </div>
                    <div class="form-group mb-2">
                        <label style="color: #fff; font-weight: 600; display: block; margin-bottom: 4px;">Reason for Change (Required):</label>
                        <input id="swal-reassign-reason" type="text" class="form-control" placeholder="e.g. Corrected grade or batch / repeat exam" style="background: #1f2937; color: #fff; border: 1px solid #4b5563;">
                    </div>
                    <div style="margin-top: 12px; padding: 10px; background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 6px; text-align: center;">
                        <span style="color: #cbd5e1; font-size: 0.85rem;">Preview: </span>
                        <strong id="swal-reassign-preview-index" style="color: #38bdf8; font-size: 1.15rem; letter-spacing: 0.5px;">${prefix}/${val || '01'}/...</strong>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Confirm & Re-assign',
            confirmButtonColor: '#eab308',
            customClass: 'swal-dark-popup',
            onOpen: () => {
                const seriesInput = document.getElementById('swal-new-series');
                const seqInput = document.getElementById('swal-new-seq');
                const previewEl = document.getElementById('swal-reassign-preview-index');

                function updatePreview() {
                    const sRaw = seriesInput.value.trim();
                    const sNum = parseInt(sRaw, 10);
                    const sStr = (!isNaN(sNum) && sNum >= 1 && sNum <= 99) ? String(sNum).padStart(2, '0') : (sRaw || '??');
                    const seqRaw = seqInput.value.trim();
                    let seqStr = '<next>';
                    if (seqRaw) {
                        const seqNum = parseInt(seqRaw, 10);
                        if (!isNaN(seqNum) && seqNum > 0) {
                            seqStr = String(seqNum).padStart(3, '0');
                        } else {
                            seqStr = seqRaw;
                        }
                    }
                    previewEl.textContent = `${prefix}/${sStr}/${seqStr}`;
                }

                seriesInput.addEventListener('input', updatePreview);
                seqInput.addEventListener('input', updatePreview);
                updatePreview();
            },
            preConfirm: () => {
                const series = document.getElementById('swal-new-series').value.trim();
                const seq = document.getElementById('swal-new-seq').value.trim();
                const reason = document.getElementById('swal-reassign-reason').value.trim();
                const num = parseInt(series, 10);
                if (!series || isNaN(num) || num < 1 || num > 99) {
                    Swal.showValidationMessage('Series must be a number 01–99');
                    return false;
                }
                if (seq) {
                    const seqNum = parseInt(seq, 10);
                    if (isNaN(seqNum) || seqNum < 1) {
                        Swal.showValidationMessage('Running number must be at least 1');
                        return false;
                    }
                }
                if (!reason) {
                    Swal.showValidationMessage('Reason is required for audit log');
                    return false;
                }
                return {
                    series: String(num).padStart(2, '0'),
                    seq: seq ? parseInt(seq, 10) : '',
                    reason
                };
            }
        }).then((res) => {
            if (res.value) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('admin.students.reassign-index', $student->id, false) }}';
                form.innerHTML = `
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="batch_year" value="${res.value.series}">
                    <input type="hidden" name="running_number" value="${res.value.seq}">
                    <input type="hidden" name="reason" value="${res.value.reason.replace(/"/g, '&quot;')}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function promptConvertToNewFormat(studentId, currentIndex, suggestedSeries, format) {
        Swal.fire({
            title: 'Convert to New Format?',
            html: `<div style="text-align: left; font-size: 0.9rem;">
                     <p style="color: #cbd5e1; margin-bottom: 8px;">Current Index: <strong style="color: #f59e0b;">${currentIndex}</strong> (legacy format)</p>
                     <p style="color: #cbd5e1; margin-bottom: 12px; font-size: 0.85rem;">This will convert the index number to the official <strong>TIT/GG/NNN</strong> format and record an audit log.</p>
                     <label style="color: #fff; font-weight: 600; display: block; margin-bottom: 4px;">Grade Series (01-13):</label>
                     <input id="swal-convert-series" type="text" value="${suggestedSeries || '08'}" class="form-control text-center mb-3" style="width: 120px; font-weight: bold; background: #1f2937; color: #fff; border: 1px solid #4b5563;">
                   </div>`,
            showCancelButton: true,
            confirmButtonText: 'Convert Now',
            confirmButtonColor: '#10b981',
            customClass: 'swal-dark-popup',
            preConfirm: () => {
                const series = document.getElementById('swal-convert-series').value.trim();
                const num = parseInt(series, 10);
                if (!series || isNaN(num) || num < 1 || num > 13) {
                    Swal.showValidationMessage('Please enter a valid grade series between 01 and 13');
                    return false;
                }
                return String(num).padStart(2, '0');
            }
        }).then((res) => {
            if (res.value) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('admin.students.reassign-index', $student->id, false) }}';
                form.innerHTML = `
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="batch_year" value="${res.value}">
                    <input type="hidden" name="reason" value="Convert legacy index to standard grade format">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endpush
